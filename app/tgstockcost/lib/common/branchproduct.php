<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version osc---hanbingshu sanow@126.com
	* @date 2012-07-27
	* 仓库货品数据基类.不同产品继承此类。各自实现不同方法
*/
class tgstockcost_common_branchproduct
{
	/*获取表名*/
	public function table_name($real=false)
	{
		return "sdb_ome_branch_product";
	}

	private function branchproduct_filter($filter = array()){

        $branch_product_mdl = app::get("ome")->model("branch_product");

        $where = array(1);
		#仓库ID
		if(isset($filter['branch_id']) && $filter['branch_id'] && $filter['branch_id'] !=0){
			$where[] = " obp.branch_id=".intval($filter['branch_id']);
		}else{
            $Obranch = app::get('ome')->model('branch');
			$branchs = $Obranch->getList('branch_id');
			$branchs_id = array();
			foreach ($branchs as $v) {
				$branchs_id[] = $v['branch_id'];
			}
			$where[] = " obp.branch_id IN (".implode(',',$branchs_id).")";
			unset($branchs_id,$branchs,$Obranch);
		}

		#货号
		if(isset($filter['p_bn']) && $filter['p_bn']){
			$where[] = " op.bn=".$branch_product_mdl->db->quote($filter['p_bn']);
		}

		#货品名称
        if(isset($filter['product_name']) && $filter['product_name']){
        	$where[] = " op.name like '%".$filter['product_name']."%'";
        }

		#商品编号
        if(isset($filter['goods_bn']) && $filter['goods_bn']){
            $where[] = " g.bn=".$branch_product_mdl->db->quote($filter['goods_bn']);
        }
       
		#商品类型
        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' g.type_id = '.$filter['type_id'];
        }

		#品牌
        if(isset($filter['brand']) && $filter['brand']){
            $where[] = ' g.brand_id = '.$filter['brand'];
        }
        
        return implode($where,' and ');
	}

	/*
	*获取FINDER列表上仓库货品表数据(库存成本统计)
	*/
	function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
	{
		@ini_set('memory_limit','128M');
		
		$branch_product_mdl = app::get("ome")->model("branch_product");

		$sql = "select obp.*,op.bn,op.name,g.bn as goods_bn,op.spec_info,op.goods_id,g.brand_id,g.type_id,b.name as branch_name from sdb_ome_branch_product as obp JOIN (sdb_ome_products as op,sdb_ome_goods as g,sdb_ome_branch as b) ON obp.product_id=op.product_id and op.goods_id=g.goods_id and obp.branch_id=b.branch_id where op.visibility = 'true' and ".$this->branchproduct_filter($filter);
            
		if($orderType) $sql = $sql." order by ".$orderType;

		$aData = $branch_product_mdl->db->selectLimit($sql,$limit,$offset);

		foreach($aData as $a_k=>$a_v)
		{
			$aTmp['p.bn'] = $a_v['bn']?$a_v['bn']:'-';
			$aTmp['product_name'] = $a_v['name']?$a_v['name']:'-';
			$aTmp['bp.store'] = $a_v['store']?$a_v['store']:0;
			$aTmp['unit_cost'] = $a_v['unit_cost']?$a_v['unit_cost']:0;
			$aTmp['brand'] = $a_v['brand_id']?$a_v['brand_id']:'-';
			$aTmp['goods_bn'] = $a_v['goods_bn']?$a_v['goods_bn']:'-';
			$aTmp['goods_specinfo'] = $a_v['spec_info']?$a_v['spec_info']:'-';
			$aTmp['type_id'] = $a_v['type_id']?$a_v['type_id']:'-';
			$aTmp['inventory_cost'] = $a_v['inventory_cost']?$a_v['inventory_cost']:0;
			$aTmp['id'] = $a_v['product_id']."-".$a_v['branch_id'];
			$aTmp['branch_id'] = $a_v['branch_name']?$a_v['branch_name']:'-';
			$list[]= $aTmp;
		}

        unset($aData,$aTmp);

		return $list;
	}

	function header_getlist($cols='*', $filter=array())
	{
		@ini_set('memory_limit','128M');
		
		$branch_product_mdl = app::get("ome")->model("branch_product");

		$sql = "select ".$cols." from sdb_ome_branch_product as obp JOIN (sdb_ome_products as op,sdb_ome_goods as g,sdb_ome_branch as b) ON obp.product_id=op.product_id and op.goods_id=g.goods_id and obp.branch_id=b.branch_id where op.visibility = 'true' and ".$this->branchproduct_filter($filter);
            
		$aData = $branch_product_mdl->db->select($sql);

		return $aData;
	}

	public function branchproduct_count($filter = array()){

		@ini_set('memory_limit','128M');
		
		$branch_product_mdl = app::get("ome")->model("branch_product");

		$sql = "select count(obp.branch_id) as _count from sdb_ome_branch_product as obp JOIN (sdb_ome_products as op,sdb_ome_goods as g,sdb_ome_branch as b) ON obp.product_id=op.product_id and op.goods_id=g.goods_id and obp.branch_id=b.branch_id where op.visibility = 'true' and ".$this->branchproduct_filter($filter);

		return $branch_product_mdl->db->count($sql);

    }

	/*进销存统计调用方法*/

    private function stock_filter($filter = array()){

    	$branch_product = app::get("ome")->model("branch_product");

        $where = array(1);
		#品牌
		if(isset($filter['brand']) && $filter['brand'] ){
            $where[] = " g.brand_id=".intval($filter['brand']); 
        }

        #商品编号
        if(isset($filter['goods_bn']) && $filter['goods_bn']){
            $where[] = " g.bn=".$branch_product->db->quote($filter['goods_bn']);
        }
	
		#仓库
		if(isset($filter['branch_id']) && $filter['branch_id']){
            $where[] = " obp.branch_id=".intval($filter['branch_id']);
        }else{
            $Obranch = app::get('ome')->model('branch');
			$branchs = $Obranch->getList('branch_id');
			$branchs_id = array();
			foreach ($branchs as $v) {
				$branchs_id[] = $v['branch_id'];
			}
			$where[] = " obp.branch_id IN (".implode(',',$branchs_id).")";
			unset($branchs_id);
        }

		#货号
		if(isset($filter['product_bn']) && $filter['product_bn']){
            $where[] = " op.bn=".$branch_product->db->quote($filter['product_bn']);
        }

		#商品类型
        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' g.type_id = '.$filter['type_id'];
        }

		#货品名称
        if(isset($filter['product_name']) && $filter['product_name']){
        	$where[] = " op.name like '".trim($filter['product_name'])."%'";
        }   

        return implode($where,' and ');

    }	

	function stock_getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
	{
		//@ini_set('memory_limit','128M');

		if(empty($filter['time_from']) || empty($filter['time_to'])) return false;
		$stockcost_common_iostockrecord = $this->get_instance_iostockrecord();
		$branch_product = app::get("ome")->model("branch_product");

        $sql = "select obp.branch_id,obp.product_id,op.bn,op.name,op.spec_info,op.unit,g.bn as goods_bn,g.type_id,g.brand_id from sdb_ome_branch_product as obp JOIN (sdb_ome_products as op,sdb_ome_goods as g) ON obp.product_id=op.product_id and op.goods_id=g.goods_id where op.visibility = 'true' and ".$this->stock_filter($filter);

		$data = $branch_product->db->selectLimit($sql,$limit,$offset);

        $all_start = $all_in_data = $all_out_data = array();

        //$get_all_start = $this->get_start($filter['time_from'],'',$filter['branch_id'],true);//获取from_time时间段内的货品期初数据
        $get_all_start = $this->get_new_start($filter['time_from'],'',$filter['branch_id'],true);//获取from_time时间段内的货品期初数据

        foreach ($get_all_start as $k => $v) {
        	$all_start[$v['branch_id']][$v['product_id']]['stock_num'] = $v['stock_num'];
        	$all_start[$v['branch_id']][$v['product_id']]['unit_cost'] = $v['unit_cost'];
        	$all_start[$v['branch_id']][$v['product_id']]['inventory_cost'] = $v['inventory_cost'];
        }
        #获取期末数据
        $_get_end_data =  $this->get_end_data($filter['time_to'],'',$filter['branch_id'],true);
        foreach ($_get_end_data as $k => $v) {
            $get_end_data[$v['branch_id']][$v['product_id']]['stock_num'] = $v['stock_num'];
            $get_end_data[$v['branch_id']][$v['product_id']]['inventory_cost'] = $v['inventory_cost'];
        }

        unset($get_all_start);

        $get_in_data = $this->get_out_stock($filter['time_from'],$filter['time_to'],'',$filter['branch_id'],1,true);//获取from_time-to_time时间段内的货品入库数据

        foreach ($get_in_data as $k => $v) {
        	$all_in_data[$v['branch_id']][$v['product_id']]['nums'] = $v['nums'];
        	$all_in_data[$v['branch_id']][$v['product_id']]['unit_cost'] = $v['unit_cost'];
        	$all_in_data[$v['branch_id']][$v['product_id']]['inventory_cost'] = $v['inventory_cost'];
        }
        
        unset($get_in_data);

        $get_out_data = $this->get_out_stock($filter['time_from'],$filter['time_to'],'',$filter['branch_id'],0,true);//获取from_time-to_time时间段内的货品出库数据
        foreach ($get_out_data as $k => $v) {
        	$all_out_data[$v['branch_id']][$v['product_id']]['nums'] = $v['nums'];
        	$all_out_data[$v['branch_id']][$v['product_id']]['unit_cost'] = $v['unit_cost'];
        	$all_out_data[$v['branch_id']][$v['product_id']]['inventory_cost'] = $v['inventory_cost'];
        }
        unset($get_out_data);

		foreach($data as $k=>$val)
		{
			$aTmp['product_bn'] = $val['bn']?$val['bn']:'-';
			$aTmp['product_name'] = $val['name']?$val['name']:'-';

			$aTmp['goods_bn'] = $val['goods_bn']?$val['goods_bn']:'-';
			$aTmp['type_name'] = $val['type_id']?$val['type_id']:'-';
			$aTmp['brand_name'] = $val['brand_id']?$val['brand_id']:'-';
			$aTmp['spec_info'] = $val['spec_info']?$val['spec_info']:'-';
			$aTmp['unit'] = $val['unit']?$val['unit']:'-';
			

			//货品期初数据  
			
			$aTmp['start_nums'] = $all_start[$val['branch_id']][$val['product_id']]['stock_num']?$all_start[$val['branch_id']][$val['product_id']]['stock_num']:0;
			$aTmp['start_unit_cost'] = $all_start[$val['branch_id']][$val['product_id']]['unit_cost']?$all_start[$val['branch_id']][$val['product_id']]['unit_cost']:0;
			$aTmp['start_inventory_cost'] = $all_start[$val['branch_id']][$val['product_id']]['inventory_cost']?$all_start[$val['branch_id']][$val['product_id']]['inventory_cost']:0;
			
			//货品入库数据
			//$in_data = $this->get_out_stock($filter['time_from'],$filter['time_to'],$val['bn'],$val['branch_id'],1);
			$aTmp['in_nums'] = $all_in_data[$val['branch_id']][$val['product_id']]['nums']?$all_in_data[$val['branch_id']][$val['product_id']]['nums']:0;
			$aTmp['in_unit_cost'] = $all_in_data[$val['branch_id']][$val['product_id']]['unit_cost']?$all_in_data[$val['branch_id']][$val['product_id']]['unit_cost']:0;
			$aTmp['in_inventory_cost'] = $all_in_data[$val['branch_id']][$val['product_id']]['inventory_cost']?$all_in_data[$val['branch_id']][$val['product_id']]['inventory_cost']:0;

			//货品出库数据
			//$out_data = $this->get_out_stock($filter['time_from'],$filter['time_to'],$val['bn'],$val['branch_id'],0);
			$aTmp['out_nums'] = $all_out_data[$val['branch_id']][$val['product_id']]['nums']?$all_out_data[$val['branch_id']][$val['product_id']]['nums']:0;
			$aTmp['out_unit_cost'] = $all_out_data[$val['branch_id']][$val['product_id']]['unit_cost']?$all_out_data[$val['branch_id']][$val['product_id']]['unit_cost']:0;
			$aTmp['out_inventory_cost'] = $all_out_data[$val['branch_id']][$val['product_id']]['inventory_cost']?$all_out_data[$val['branch_id']][$val['product_id']]['inventory_cost']:0;
            
            //货品结存数据
			/* $aTmp['store'] = $aTmp['start_nums']+$aTmp['in_nums']-$aTmp['out_nums'];//期初数量+入库数量-出库数量
			$aTmp['inventory_cost'] = $aTmp['start_inventory_cost']+$aTmp['in_inventory_cost']-$aTmp['out_inventory_cost'];//期初库存成本+入库库存成本-出库库存成本
			if($aTmp['store']){
				$aTmp['unit_cost'] = round($aTmp['inventory_cost']/$aTmp['store'],2);
			}else{
				$aTmp['unit_cost'] = 0;
           }*/
           //新的货品结存数据
           $aTmp['store'] = $get_end_data[$val['branch_id']][$val['product_id']]['stock_num']?$get_end_data[$val['branch_id']][$val['product_id']]['stock_num']:0;
           $aTmp['inventory_cost'] = $get_end_data[$val['branch_id']][$val['product_id']]['inventory_cost']?$get_end_data[$val['branch_id']][$val['product_id']]['inventory_cost']:0;
           if($aTmp['store']){
               $aTmp['unit_cost'] = round($aTmp['inventory_cost']/$aTmp['store'],2);
            }else{
              $aTmp['unit_cost'] = 0;
			}

			//仓库
            $aTmp['branch_id'] = $val['branch_id'];

			$list[] = $aTmp;
		}

		unset($aTmp,$all_out_data,$all_in_data,$all_start,$data);
	    #echo "<pre>";print_r($list);
		return $list;
	}

	function stock_count($filter = array()){
		
		$branch_product = app::get("ome")->model("branch_product");

        $sql = "select count(obp.branch_id) as _count from sdb_ome_branch_product as obp JOIN (sdb_ome_products as op,sdb_ome_goods as g) ON obp.product_id=op.product_id and op.goods_id=g.goods_id where op.visibility = 'true' and ".$this->stock_filter($filter);

        return $branch_product->db->count($sql);
	}

	/*获取货品期初数据
	*@params $from_time期初时间,$product_id货品ID,$branch_id仓库ID
	*@return array() 期初数量，单位成本，库存成本等
	*/
	function get_start($from_time,$product_id = '',$branch_id = '',$is_all = false)
	{
		$from_time = date("Y-m-d",strtotime($from_time));

		$filter['stock_date'] = $from_time;
		if( isset($product_id) && $product_id ){
            $filter['product_id'] = $product_id;
		}

		if( isset($branch_id) && $branch_id ){
            $filter['branch_id'] = $branch_id;
		}

		$dailystock  = app::get("ome")->model("dailystock");
		$daily_data = $dailystock->getList("branch_id,product_id,stock_num,unit_cost,inventory_cost",$filter);

		if($is_all){
			return $daily_data;
		}

		return $daily_data[0];
	}
	#获取货品的期初数据
	function get_new_start($from_time,$product_id = '',$branch_id = '',$is_all = false){
	    #计算上一天的数据
	    $from_time = strtotime($from_time)-86400;
	    $from_time = date("Y-m-d", $from_time);
	    $filter['stock_date'] =  $from_time;
	    if( isset($product_id) && $product_id ){
	        $filter['product_id'] = $product_id;
	    }
	    
	    if( isset($branch_id) && $branch_id ){
	        $filter['branch_id'] = $branch_id;
	    }
	    $dailystock  = app::get("ome")->model("dailystock");
	    $daily_data = $dailystock->getList("branch_id,product_id,stock_num,inventory_cost",$filter);
	    
	    if($is_all){
	        return $daily_data;
	    }
	}
	#获取货品期末数据
	function get_end_data($to_time,$product_id = '',$branch_id = '',$is_all = false)
	{
	    $to_time = date("Y-m-d",strtotime($to_time));
	
	    $filter['stock_date'] = $to_time;
	    if( isset($product_id) && $product_id ){
	        $filter['product_id'] = $product_id;
	    }
	
	    if( isset($branch_id) && $branch_id ){
	        $filter['branch_id'] = $branch_id;
	    }
	
	    $dailystock  = app::get("ome")->model("dailystock");
	    $daily_data = $dailystock->getList("branch_id,product_id,stock_num,unit_cost,inventory_cost",$filter);
	
	    if($is_all){
	        return $daily_data;
	    }
	
	    return $daily_data[0];
	}


	/*获取指定时间范围内货品出入库数据
	*@params $from_time开始时间 2012-07-03,$to_time结束时间 2012-07-25,$product_id货品ID,$branch_id仓库ID
	*@return array() 出库数量，单位成本，库存成本等
	*/
	function get_out_stock($from_time,$to_time,$product_bn,$branch_id,$io_type)
	{
		$from_time = strtotime($from_time);
		$stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
		if($from_time<$stockcost_install_time) $from_time = $stockcost_install_time;
		$to_time = strtotime($to_time)+(24*3600-1);
		$iostock_mdl = app::get("ome")->model("iostock");
		$stockcost_common_iostockrecord = $this->get_instance_iostockrecord();
		$iostock_type_arr = $stockcost_common_iostockrecord->get_type_id($io_type);//出库类型ID数组
		$out_data = $iostock_mdl->db->selectrow("select sum(nums) as nums,sum(inventory_cost) as inventory_cost from sdb_ome_iostock where bn='".$product_bn."' and branch_id=".intval($branch_id)." and iotime>".intval($from_time)." and iotime<".intval($to_time)." and type_id in (".implode(',',$iostock_type_arr).")");
		if(!$out_data){
			$out_data['nums']=0;
			$out_data['unit_cost']=0;
			$out_data['inventory_cost']=0;
		}
		else{
			if($out_data['nums'])
				$out_data['unit_cost']=round($out_data['inventory_cost']/$out_data['nums'],2);
			else $out_data['unit_cost']=0;
		}
		return $out_data;
	}

	function get_instance_iostockrecord()
	{
		return kernel::single("tgstockcost_common_iostockrecord");
	}

	/*导出链接URL
	*@params $params 链接参数
	*@return string;
	*/
	function get_export_href($params)
	{
		return  'index.php?app=omeio&ctl=admin_task&act=create_export&_params[app]='.$params['app'].'&_params[mdl]='.$params['mdl'].'&_params[time_from]='.$params['time_from'].'&_params[time_to]='.$params['time_to'].'&_params[branch_id]='.$params['branch_id'].'&_params[brand_id]='.$params['brand_id'];
	}

	public function fgetlist_csv(&$data,$filter,$offset,$exportType =1,$pass_data=false){
		return true;
	}

    function export_csv($data){
    	 return true;
    }  
}