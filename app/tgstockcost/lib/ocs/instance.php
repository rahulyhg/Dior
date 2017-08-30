<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version osc---hanbingshu sanow@126.com
	* @date 2012-07-27
	* 产品路由类,OCS 和淘管 通过此路由调用不同的数据表对象
*/
class stockcost_ocs_instance implements stockcost_interface_cost
{
	/*创建期初数据队列*/
	public function create_queue()
	{
		$branch_mdl = app::get("ome")->model("branch");
		$branch_data = $branch_mdl->getList("branch_id,name");
		$oQueue = app::get("omequeue")->model("queue");
		foreach((array)$branch_data as $k=>$val)
		{
			$title=$val['name']." 仓库期初数据";
			$worker = 'run_queue@stockcost_ocs_instance';
			$params['branch_id'] = $val['branch_id'];
			omequeue_queue::instance()->addTask($title, $worker, $params, 'prior');
		}
	}
	
	/*执行队列*/
	function run_queue($params)
	{
		
		$branch_id = $params['branch_id'];
		$branch_product_mdl = app::get("ome")->model("branch_product");
		$fifo = app::get("stockcost")->model("fifo");
		$dailystock = app::get("ome")->model("dailystock");
		$aData = $branch_product_mdl->db->select("SELECT obp.product_id,obp.store,bps.bn,bps.cost FROM sdb_ome_branch_product AS obp LEFT JOIN sdb_ome_products AS bps ON obp.product_id=bps.product_id WHERE obp.branch_id=".intval($branch_id));
		app::get("ome")->setConf("stockcost_install_time",time()); //队列执行执行时设置安装时间
		foreach(($aData) as $k=>$val)
		{
			$branch_product_mdl->update(array("unit_cost"=>$val['cost'],"inventory_cost"=>$val['store']*$val['cost']),array("product_id"=>$val["product_id"],"branch_id"=>$branch_id));
			//安装后的当天的期初数据
			if($install_time = app::get("ome")->getConf("stockcost_install_time")){
				$dailystock_data = array();
				$dailystock_data['stock_date'] = date('Y-m-d',$install_time);
				$dailystock_data['branch_id'] = $branch_id;
				$dailystock_data['product_id'] = $val['product_id'];
				$dailystock_data['product_bn'] = $val['bn'];
				$dailystock_data['stock_num'] = $val['store'];
				$dailystock_data['unit_cost'] = $val['cost'];
				$dailystock_data['inventory_cost'] = $val['store']*$val['cost'];
				$dailystock_data['is_change'] = 1;
				$dailystock->save($dailystock_data);
			}
			if(app::get("stockcost")->getConf("stockcost_cost") == "4"){
				$save_data = array();
				$save_data['product_id']  = $val['product_id'];
				$save_data['branch_id']  = $branch_id;
				$save_data['product_bn']  = $val['bn'];
				$save_data['current_num']  = $val['store'];
				$save_data['in_num']  = $val['store'];
				$save_data['out_num']  = 0;
				$save_data['current_unit_cost']  = $val['cost'];
				$save_data['current_inventory_cost']  = $val['store']*$val['cost'];
				$save_data['is_sart']  = 1;
				$fifo->save($save_data);
			}
		}
		
	}
	/*销售出库 更新销售单成本金额和成本单价等字段*/
	function set_sales_iostock_cost($io,$data)
	{
		if($io!=1)return false;
		foreach((array)$data as $data_k=>$data_v)
		{
			if($data_v['type_id'] == 3){
				$this->update_sale_items($data_v['iostock_id']);
			}
		}

	}
	/*更新销售单明细的销售毛利等字段*/
	function update_sale_items($iostock_id='')
	{
		if(empty($iostock_id)) return false;
		$iostock = app::get("ome")->model("iostock");
		$aData = $iostock->getList("unit_cost,inventory_cost",array('iostock_id'=>$iostock_id));
		$iostock_data = $aData[0];
		if(!$iostock_data) return false;
		$sales_itmes = app::get("ome")->model("sales_items");
		$update_data = array();
		$cost_price = $iostock_data['unit_cost'];
		$cost_amount = $iostock_data['inventory_cost'];
		$update_data['cost_price'] = $iostock_data['unit_cost'];
		$sales_itmes->db->exec("UPDATE sdb_ome_sales_items set cost_price=$cost_price,cost_amount=$cost_amount,gross_sales=sales_amount-$cost_amount,gross_sales_rate=ROUND(gross_sales/sales_amount,4)*100 where iostock_id=".$sales_itmes->db->quote($iostock_id));
	}
	/*各种出入库操作实现*/
	function iostock_set($io,$data)
	{
		$setting_stockcost_cost = app::get("stockcost")->getConf("stockcost_cost");
		$setting_stockcost_get_value_type = app::get("stockcost")->getConf("stockcost_get_value_type");
		$iostock = app::get("ome")->model("iostock");
		if($io==0){//入库
			foreach((array)$data as $data_k=>$data_v)
			{
				$data_v['product_id'] = $this->get_product_id($data_v['bn']);
				if($data_v['type_id'] == 1 || $data_v['type_id']  == 4 || $data_v['type_id']  == 32){ //采购/调拨/其他入库
					$unit_cost = $data_v['iostock_price'];
				}
				elseif($data_v['type_id'] == 30 || $data_v['type_id']  == 31 ){ //退货/换货入库
					//取销售出库时记录的单位成本 
					$unit_cost= $this->get_sale_unit_cost($data_v); //如果是升级前的数据 是没有记录单位成本的 就是不计成本
				}
				elseif($data_v['type_id'] == 7 || $data_v['type_id']  == 60 ){ //调帐入库/盘盈
					$unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
				}
				else{															//其他不明情况 
					$unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
				}
				$this->update_iostock($data_v,$unit_cost,'+'); //更新出入库流水成本等字段和仓库货品表的库存成本和单位成本字段
				if($setting_stockcost_cost == '4') //先进先出 插入入库FIFO表
				{
					$this->insert_fifo($data_v,$unit_cost);
				}
			}
		}
		if($io==1){//出库
			//出库时 只要是先进先出法 出库单位成本都等于先进先出表的平均出库成本
			foreach((array)$data as $data_k=>$data_v)
			{
				$data_v['product_id'] = $this->get_product_id($data_v['bn']);
				if($setting_stockcost_cost == '4') $fifo_out_data = $this->fifo_stock($data_v);
				if($data_v['type_id'] == 3){ //销售出库
					if($setting_stockcost_cost == '2'){ //固定成本法
						$unit_cost = $this->get_product_cost($data_v['product_id']);
					}
					elseif($setting_stockcost_cost == '3'){ //平均成本法
						$unit_cost = $this->get_product_unit_cost($data_v['product_id'],$data_v['branch_id']);
					}
					elseif($setting_stockcost_cost == '4') //先进先出
					{
						$unit_cost = $fifo_out_data['unit_cost'];
					}
					//修改商品销售发货明细单
				}
				elseif($data_v['type_id'] == 10 || $data_v['type_id']  == 40){ //采购退货/调拨出库/其他出库  其他
					$unit_cost = $data_v['iostock_price'];
					if($setting_stockcost_cost == '4') $unit_cost = $fifo_out_data['unit_cost'];
				}
				elseif($data_v['type_id'] == 70 || $data_v['type_id']  == 6 ){ //调帐出库/盘亏及
					$unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
					if($setting_stockcost_cost == '4') $unit_cost = $fifo_out_data['unit_cost'];
				}
				else{															//其他不明情况 
					$unit_cost = $this->get_unit_cost($data_v['product_id'],$data_v['bn'],$data_v['branch_id']);
					if($setting_stockcost_cost == '4') $unit_cost = $fifo_out_data['unit_cost'];
				}
				$this->update_iostock($data_v,$unit_cost,'-',$fifo_out_data['inventory_cost_total']); //更新出入库流水成本等字段和仓库货品表的库存成本和单位成本字段
			}
		}
	}
	/*取货品单位成本*/
	function get_unit_cost($product_id,$product_bn,$branch_id)
	{
		$setting_stockcost_cost = app::get("stockcost")->getConf("stockcost_cost");
		$setting_stockcost_get_value_type = app::get("stockcost")->getConf("stockcost_get_value_type");
		$ome_products = app::get("ome")->model("products");
		if($setting_stockcost_get_value_type == '1'){ //取货品的固定成本
			$unit_cost = $this->get_product_cost($product_id);
		}
		elseif($setting_stockcost_get_value_type == '2'){ //取货品的单位平均成本  to 如果仓库货品表没有记录？
			$unit_cost = $this->get_product_unit_cost($product_id,$branch_id);
		}
		elseif($setting_stockcost_get_value_type == '3'){//取货品的最近一次出入库成本  to 如果在该仓库下没有出入库记录？
			$unit_cost = $this->get_last_product_unit_cost($product_bn,$branch_id,$product_id);
		}
		elseif($setting_stockcost_get_value_type == '4'){//取0
			$unit_cost = 0;
		}
		else $unit_cost = 0;
		return $unit_cost;
	}

	/*更新出入库流水的库存成本等字段和仓库货品表的库存成本和单位成本字段
	*@params $iodata出库流水数据 $unit_cost单位成本 $inventory_cost_total出库成本
	*/
	function update_iostock($iodata=array(),$unit_cost='',$operator='',$inventory_cost_total='')
	{
		if(empty($iodata) || empty($operator)) return false;
		$iostock = app::get("ome")->model("iostock");
		$inventory_cost = $unit_cost*$iodata['nums'];
		$last_row = $iostock->db->selectrow("select store,inventory_cost from sdb_ome_branch_product  where product_id=".intval($iodata['product_id'])." and branch_id=".intval($iodata['branch_id']));
		//todo 这里不防并发问题不大
		if(empty($inventory_cost_total))
			$inventory_cost = $unit_cost*$iodata['nums']; //出入库成本
		else
			$inventory_cost = $inventory_cost_total;
		switch($operator){ 
			case "+": //入库
					$now_num = $last_row['store'];  //结存数量 = 仓库货品表的库存数量
					$now_inventory_cost = $last_row['inventory_cost'] + $inventory_cost; //结存成本 = 仓库货品表的库存成本+入库成本
					$branch_product_sql = " UPDATE sdb_ome_branch_product set inventory_cost=inventory_cost+$inventory_cost,unit_cost=ROUND(inventory_cost/store,2) where branch_id=".intval($iodata['branch_id'])." and product_id=".intval($iodata['product_id']);
				break;
			case "-"://出库
					$now_num = $last_row['store'];  //结存数量 = 仓库货品表的库存数量
					$now_inventory_cost = $last_row['inventory_cost'] - $inventory_cost; //结存成本 = 仓库货品表的库存成本-入库成本
					$branch_product_sql = " UPDATE sdb_ome_branch_product set inventory_cost=inventory_cost-$inventory_cost,unit_cost=ROUND(inventory_cost/store,2)  where branch_id=".intval($iodata['branch_id'])." and product_id=".intval($iodata['product_id']);
		}
		if($now_num)
			$now_unit_cost = round($now_inventory_cost/$now_num,2);   //四舍五入 保留小数点两位
		else
			$now_unit_cost = 0;
		$iostock->db->exec($branch_product_sql) ;//更细仓库货品表的 库存成本和单位成本
		$iostock_update_data['unit_cost'] = $unit_cost ? $unit_cost :0;
        $iostock_update_data['iostock_price'] = $unit_cost ? $unit_cost :0;
		$iostock_update_data['inventory_cost'] = $inventory_cost ? $inventory_cost :0;
		$iostock_update_data['now_unit_cost'] = $now_unit_cost ? $now_unit_cost :0;
		$iostock_update_data['now_inventory_cost'] = $now_inventory_cost ? $now_inventory_cost:0;
		$iostock_update_data['now_num'] = $now_num ? $now_num :0;
		$iostock->update($iostock_update_data,array("iostock_id"=>$iodata['iostock_id']));
	}

	/*退货换货入库时取销售出库时的单位成本
	*@params $iostock_id 出入库流水iostock_id
	*@return 单位成本 float
	*/
	function get_sale_unit_cost($data_v)
	{
		$iostock = app::get("ome")->model("iostock");
		$original_item_id = $data_v['original_item_id'];

		$reship_row = $iostock->db->selectrow("select return_id from sdb_ome_reship where reship_id	=".intval($data_v['original_id']));
		$return_id = $reship_row['return_id'];

		$reship_items_row = $iostock->db->selectrow("select return_type,item_type from sdb_ome_reship_items where item_id=".intval($original_item_id));
		$item_type = $reship_items_row['item_type'];
		$return_type = $reship_items_row['return_type'];
		
		$detail_item_id_row = $iostock->db->selectrow("select detail_item_id from sdb_ome_return_product_items where return_id=".intval($return_id)." and product_id=".intval($data_v['product_id'])." and branch_id=".intval($data_v['branch_id'])." and item_type='".$item_type."' and status='".$return_type."'");
		$detail_item_id = $detail_item_id_row['detail_item_id'];

		$delivery_items_detail_row = $iostock->db->selectrow("select delivery_id from sdb_ome_delivery_items_detail where item_detail_id=".intval($detail_item_id));
		$delivery_id = $delivery_items_detail_row['delivery_id'];

		$iostock_row = $iostock->db->selectrow("select unit_cost from sdb_ome_iostock where original_id=".$delivery_id." and original_item_id=".$detail_item_id." and type_id=3");
		return $iostock_row['unit_cost'];
	}

	/**生成先进先出数据
	*@params $data array() 出入库流水数据
	*@parmas $unit_cost float 入库单位成本
	*@return bool
	*/
	function insert_fifo($data,$unit_cost)
	{
		$fifo = app::get("stockcost")->model("fifo");
		$fifo_sdf = array();
		$fifo_sdf['branch_id'] = $data['branch_id'];
		$fifo_sdf['product_id'] = $data['product_id'];
		$fifo_sdf['product_bn'] = $data['bn'];
		$fifo_sdf['stock_bn'] = $data['iostock_id'];
		$fifo_sdf['in_num'] = $data['nums'];
		$fifo_sdf['out_num'] = 0;
		$fifo_sdf['bill_bn'] = $data['original_bn'];
		$fifo_sdf['current_num'] = $data['nums'];
		$fifo_sdf['current_inventory_cost'] = $unit_cost*$data['nums'];
		$fifo_sdf['current_unit_cost'] = $unit_cost;
		return $fifo->save($fifo_sdf);
	}

	/**先进先出 出库 修改先进先出表数据
	*@params
	*@return array()
	*/
	
	function fifo_stock($data)
	{
		if(!$data['nums'] && empty($data['nums'])) return false;
		$inventory_cost_total = 0;
		$data_nums = $data['nums'];
		$iostock = app::get("ome")->model("iostock");
		$concurrentModel = app::get('ome')->model('concurrent');//防并发表
		while($data['nums']>0)
		{
			$fifo_first_row = $iostock->db->selectrow("select * from sdb_stockcost_fifo where branch_id=".intval($data['branch_id'])." and product_id=".intval($data['product_id'])." and in_num>0 and current_num>0 order by id ASC");
			if(empty($fifo_first_row)) break;
			$concurrentid = "F".$fifo_first_row['id']."I".$fifo_first_row['in_num']."F".$fifo_first_row['out_num']."O";
			if ($concurrentModel->is_pass($concurrentid,'IostockFiFo',false)){  //插入成功 可以操作
				if($fifo_first_row['current_num']>$data['nums']){  //在库数量大于出库数量
					$num = $data['nums'];
					$fifo_up_sql = "UPDATE sdb_stockcost_fifo set current_num=current_num-$num,out_num=out_num+$num,current_inventory_cost = current_unit_cost*current_num  where id=".intval($fifo_first_row['id']);
					$inventory_cost_total = $inventory_cost_total + $fifo_first_row['current_unit_cost']*$data['nums'];
					$data['nums'] = 0;
					$iostock->db->exec($fifo_up_sql);
				}
				else{
					$inventory_cost_total = $inventory_cost_total + $fifo_first_row['current_unit_cost']*$fifo_first_row['current_num'];
					$data['nums'] = $data['nums']-$fifo_first_row['current_num'];
					$delete_sql = "delete from sdb_stockcost_fifo where id=".intval($fifo_first_row['id']) ;
					$iostock->db->exec($delete_sql);
				}
			}
			else{ //插入失败有进程试图修改  并发  等待再循环
				usleep(100);
			}
		}
		$unit_cost = round($inventory_cost_total/$data_nums,2);

		$out_data['unit_cost'] = $unit_cost;
		$out_data['inventory_cost_total'] = $inventory_cost_total;
		return $out_data;
	}
	/* 货品的固定成本
	*@params $product_id 货品ID
	*@return float 
	*/
	function get_product_cost($product_id)
	{
		$products = app::get("ome")->model("products");
		$p_row = $products->db->selectrow("select cost from sdb_ome_products where product_id=".intval($product_id));
		$unit_cost = $p_row['cost'] ? $p_row['cost'] :0;
		return $unit_cost;
	}

	/* 货品在仓库的平均成本
	*@params $product_id 货品ID $branch_id 仓库ID
	*@return float 
	*/
	function get_product_unit_cost($product_id,$branch_id)
	{
		$branch_product = app::get("ome")->model("branch_product");
		$p_row = $branch_product->db->selectrow("select unit_cost from sdb_ome_branch_product where product_id=".intval($product_id)." and branch_id=".intval($branch_id));
		$unit_cost = $p_row['unit_cost'] ? $p_row['unit_cost'] :0;
		return $unit_cost;
	}

	/* 货品最近一次的出入库成本
	*@params $product_id 货品ID $branch_id 仓库ID
	*@return float 
	*/
	function get_last_product_unit_cost($product_bn,$branch_id,$product_id)
	{
		$stockcost_install_time = app::get("ome")->getConf("stockcost_install_time");
		$iostock = app::get("ome")->model("iostock");
		$p_row = $iostock->db->select("select iotime,unit_cost from sdb_ome_iostock where branch_id=".intval($branch_id)." and bn='".$product_bn."' order by iotime desc limit 1,1");
		if($p_row[0]['iotime']<$stockcost_install_time){//出入库时间小于APP安装时间 说明没有计算出入库成本 取仓库货品表
			$unit_cost = $this->get_product_unit_cost($product_id,$branch_id);
		}
		else{
			$unit_cost = $p_row[0]['unit_cost'] ? $p_row[0]['unit_cost'] :0;
		}
		return $unit_cost;
	}
	
	/*根据货品BN获取货品ID*/
	function get_product_id($bn)
	{
		$products = app::get("ome")->model("products");
		$aData = $products->db->selectrow("select product_id from sdb_ome_products where bn='".$bn."'");
		return $aData['product_id'];
	}
}