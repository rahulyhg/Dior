<?php
class ome_sync_product{

    /**
     * 执行库存同步任务
     */
    function run_stock_sync(){
        $shop_info = kernel::database()->select("SELECT shop_id,node_type,node_id FROM sdb_ome_shop WHERE node_id IS NOT NULL");
        if($shop_info){
            //重置商品的冻结库存
            //$this->reset_freeze();
            foreach($shop_info as $v){
            	if (!$v['node_id']) continue;
                $shop_id = $v['shop_id'];
                $node_type = $v['node_type'];
                $queue_title = "sync_stock_".$shop_id;

                // 更新店铺的库存同步时间
                $last_store_sync_end = app::get('ome')->getConf('store_sync_end'.$shop_id);
                $store_sync_from = $last_store_sync_end?$last_store_sync_end:0;
                $store_sync_end = time();

                $cursor_id = 0;
                $params = array(
                    'store_sync_from'=>$store_sync_from,
                    'store_sync_end'=>$store_sync_end,
                    'shop_id' => $shop_id,
                    'node_type' => $node_type,
                );

                while(true) {
                    if(!$this->sync_stock($cursor_id,$params)){
                        break 1;
                    }
                }
            }
            return true;
        }else{
            return false;
        }
    }

    function add_stock_sync(){
        $shop_info = kernel::database()->select("SELECT shop_id,node_type,node_id FROM sdb_ome_shop WHERE node_id IS NOT NULL");
        if($shop_info){
            foreach($shop_info as $v){
            	if (!$v['node_id']) continue;
                $shop_id = $v['shop_id'];
                $node_type = $v['node_type'];
                $queue_title = "sync_stock_".$shop_id;
                if(!kernel::database()->selectrow("SELECT queue_id FROM sdb_base_queue WHERE worker='ome_sync_product.sync_stock' AND queue_title='".$queue_title."'")){
                    $last_store_sync_end = app::get('ome')->getConf('store_sync_end'.$shop_id);
                    $store_sync_from = $last_store_sync_end?$last_store_sync_end:0;
                    $store_sync_end = time();

                    app::get('ome')->setConf('store_sync_from'.$shop_id,$store_sync_from);
                    app::get('ome')->setConf('store_sync_end'.$shop_id,$store_sync_end);

                    $params = array(
                            'store_sync_from'=>$store_sync_from,
                            'store_sync_end'=>$store_sync_end,
                            'shop_id' => $shop_id,
                            'node_type' => $node_type,
                        );

                    $data = array(
                        'queue_title'=>$queue_title,
                        'start_time'=>time(),
                        'params'=>$params,
                        'cursor_id' => 0,
                        'worker'=>'ome_sync_product.sync_stock',
                    );
                    $queue_id = app::get('base')->model('queue')->insert($data);
                    app::get('base')->model('queue')->runtask($queue_id);

                    //$log = &app::get('ome')->model('api_log');
                    //$log->write_log($log->gen_id(), '库存增加同步,队列ID：'. $queue_id . ' 店铺ID：'. $shop_id, 'ome_sync_product', 'add_stock_sync', '', '', 'response', 'success', var_export($params, true) . '<BR>'. var_export($data, true));
                }
            }
            return true;
        }else{
            return false;
        }
    }

    function sync_stock(&$cursor_id,$params){

        if (!is_array($params)){
            $params = unserialize($params);
        }
        $limit = 20;
        $shop_id = $params['shop_id'];
        $node_type = $params['node_type'];
        $store_sync_from = $params['store_sync_from'];
        $store_sync_end = $params['store_sync_end'];
        $offset = $cursor_id;

        //if($offset==0) $this->reset_freeze();//重置商品的冻结库存

        //获取回写库存
        if ($stock_service = kernel::service('service.stock')){
            if(method_exists($stock_service,'calculate_stock')){
                $stocks = $stock_service->calculate_stock($shop_id, $store_sync_from, $store_sync_end, $offset, $limit);
            }
        }
        if ($stocks){
            if(is_array($stocks) && count($stocks)>0){
                foreach(kernel::servicelist('service.stock') as $object=>$instance){
                    if(method_exists($instance,'update_stock')){
                        $instance->update_stock($stocks,$shop_id,$node_type);
                    }
                }
            }
            if($offset==0){
                app::get('ome')->model('shop')->update(array('last_store_sync_time'=>$store_sync_end),array('shop_id'=>$shop_id));
                app::get('ome')->setConf('store_sync_from'.$shop_id,$store_sync_from);
                app::get('ome')->setConf('store_sync_end'.$shop_id,$store_sync_end);
            }
            $offset = $offset + $limit;
            $cursor_id = $offset;
            return true;
        }else{
            return false;
        }
    }

    /**
     * 重置商品的冻结库存
     */
    function reset_freeze($product_id=0){
        $productObj = &app::get('ome')->model('products');

        $product_id = intval($product_id);
        $get_order_sql = "SELECT o.order_id,i.product_id,i.nums,i.sendnum FROM sdb_ome_orders AS o
                      LEFT JOIN sdb_ome_order_items AS i ON(o.order_id=i.order_id)
                      WHERE o.ship_status in ('0','2') AND o.status='active' AND i.delete='false'";
        if($product_id>0) $get_order_sql .= " AND i.product_id=$product_id ";
        $orders = kernel::database()->select($get_order_sql);
        $p_freeze = array();
        foreach($orders as $order){
            $freeze = $order['nums'] - $order['sendnum'];
            if($freeze<0) {
                $freeze = 0;
            }
            if(isset($p_freeze[$order['product_id']])){
                $p_freeze[$order['product_id']] += $freeze;
            }else{
                $p_freeze[$order['product_id']] = $freeze;
            }
        }
        
        #[格式化is_retrial]复审订单中的原始商品冻结库存  ExBOY 2014.06.20
        $sql        = "SELECT o.order_id, i.product_id, i.nums, i.sendnum, i.delete FROM sdb_ome_orders AS o
                      LEFT JOIN sdb_ome_order_items AS i ON(o.order_id=i.order_id)
                      WHERE o.ship_status in ('0','2') AND o.status='active' AND o.process_status='is_retrial' AND i.product_id=".$product_id;
        $temp       = kernel::database()->select($sql);
        if(!empty($temp))
        {
        	$order_list = array();
        	$order_ids  = array();
        	foreach($temp as $key => $row)
        	{
        		$order_id                 = $row['order_id'];
        		$order_ids[$order_id]     = $order_id;
        		
        		$order_list[$order_id]    = $row;
        	}
        	
        	//获取复审的订单信息
        	$order_ids     = implode(',', $order_ids);
        	$order_data    = array();
        	if(!empty($order_ids))
        	{
        		$sql      = "SELECT id, order_id FROM sdb_ome_order_retrial WHERE order_id in(".$order_ids.") AND status in('0', '2') AND retrial_type='normal'";
        		$temp     = kernel::database()->select($sql);
        		foreach($temp as $key => $row)
        		{
        			$retrial_id  = $row['id'];
        			$order_id    = $row['order_id'];
        			
        			//获取复审订单原始商品的冻结库存快照表
        			$sql         = "SELECT order_id, product_id, buy_num FROM sdb_ome_order_retrial_store_freeze 
        			                 WHERE retrial_id='".$retrial_id."' AND order_id='".$order_id."' AND edit_num=0 AND is_old='true'";
        			$temp_data   = kernel::database()->select($sql);
        			foreach($temp_data as $key_j => $val_j)
        			{
        				$order_id               = $val_j['order_id'];
        				$order_data[$order_id]  = $val_j;
        			}
        		}
        	}
        	
        	//[优占]原始复审订单商品的冻结库存
        	if($order_data)
        	{
        		foreach ($order_list as $key => $val)
        		{
        			$buy_num     = intval($order_data[$key]['buy_num']);
        			if($val['delete'] == 'true' && $buy_num)
        			{
        				$freeze      = $order_data[$key]['buy_num'];
        				$p_freeze[$val['product_id']]   += $freeze;//删除的商品，手动增加冻结库存
        			}
        			elseif($val['nums'] < $buy_num)
        			{
        				$freeze      = $buy_num - $val['nums'];
                        $p_freeze[$val['product_id']]   += $freeze;//减少的购买数量，手动增加冻结库存
        			}
        		}
        	}
            unset($temp, $temp_data, $order_list, $order_ids, $order_data);
        }
        
        #update
        $get_order_sql = "UPDATE sdb_ome_products SET store_freeze=0";
        if($product_id>0) $get_order_sql .= " WHERE product_id=$product_id ";
        kernel::database()->exec($get_order_sql);

        foreach($p_freeze as $productId=>$store_freeze){
            //$productObj->chg_product_store_freeze($productId,$store_freeze,'=');
            //$sql = "UPDATE sdb_ome_products SET store_freeze=".$store_freeze . ",last_modified=" . time() . ",max_store_lastmodify=" .time()." WHERE product_id=".$product_id;
            //kernel::database()->exec($sql);
            //danny_freeze_stock_log
            $lastinfo = kernel::database()->selectrow('select goods_id,bn,store_freeze from sdb_ome_products where product_id ='.intval($productId));

            $sql = "UPDATE sdb_ome_products SET store_freeze=".$store_freeze ." WHERE product_id=".$productId;
            kernel::database()->exec($sql);

            //danny_freeze_stock_log
            $currentinfo = kernel::database()->selectrow('select store_freeze from sdb_ome_products where product_id ='.intval($productId));
            $log = array(
                    'log_type'=>'order',
                    'mark_no'=>uniqid(),
                    'oper_time'=>time(),
                    'product_id'=>$productId,
                    'goods_id'=>$lastinfo['goods_id'],
                    'bn'=>$lastinfo['bn'],
                    'stock_action_type'=>'覆盖',
                    'last_num'=>$lastinfo['store_freeze'],
                    'change_num'=>$store_freeze,
                    'current_num'=>$currentinfo['store_freeze'],
            );
            kernel::single('ome_freeze_stock_log')->changeLog($log);
        }

        $this->reset_branch_freeze($product_id);
    }

    /**
     * 重置仓库的冻结库存
     */
    function reset_branch_freeze($product_id){
        $branchProductObj = &app::get('ome')->model('branch_product');

        // reset branch store_freeze 2011.12.28
        $sql = "update sdb_ome_branch_product set store_freeze=0";
        if($product_id>0) $sql .= " where product_id=$product_id ";
        kernel::database()->exec($sql);

        $sql = 'select a.branch_id,sum(b.number) as total_num,b.product_id,b.bn
            from sdb_ome_delivery as a
                left join sdb_ome_delivery_items as b
                on a.delivery_id=b.delivery_id
            where
                a.status in ("progress","ready","stop") and a.process="false" and type="normal" and a.parent_id=0
            ';
        if($product_id>0) $sql .= " and b.product_id=$product_id ";
        $sql .= " group by b.product_id,a.branch_id ";
        $deliverys = kernel::database()->select($sql);
        //echo('<pre>');var_dump($deliverys);
        foreach($deliverys as $v){
            $total_num = intval($v['total_num']);
            $branch_id = $v['branch_id'];
            $productId = $v['product_id'];
            $bn = $v['bn'];

            $branchProductObj->chg_product_store_freeze($branch_id,$productId,$total_num,'=');
            /*$sql = "update sdb_ome_branch_product
                    set store_freeze=$total_num
                    where product_id=$product_id and branch_id=$branch_id";
            kernel::database()->exec($sql);*/
                //danny_freeze_stock_log
                $product_info = kernel::database()->selectrow('select goods_id,bn from sdb_ome_products where product_id ='.$productId);
                $lastinfo = kernel::database()->selectrow('select store_freeze from sdb_ome_branch_product where product_id ='.$productId.' AND branch_id = '.$branch_id);
                $branchinfo = kernel::database()->selectrow('select name from sdb_ome_branch where branch_id = '.$branch_id);

                $sql = "UPDATE sdb_ome_branch_product SET store_freeze=".$total_num." WHERE product_id=".$productId." AND branch_id = ".$branch_id;
                kernel::database()->exec($sql);

                //danny_freeze_stock_log
                $currentinfo = kernel::database()->selectrow('select store_freeze from sdb_ome_branch_product where product_id ='.$productId.' AND branch_id = '.$branch_id);
                $log = array(
                        'log_type'=>'delivery',
                        'mark_no'=>uniqid(),
                        'oper_time'=>time(),
                        'product_id'=>$productId,
                        'goods_id'=>$product_info['goods_id'],
                        'bn'=>$product_info['bn'],
                        'branch_id'=>$branch_id,
                        'branch_name'=>$branchinfo['name'],
                        'stock_action_type'=>'覆盖',
                        'last_num'=>$lastinfo['store_freeze'],
                        'change_num'=>$total_num,
                        'current_num'=>$currentinfo['store_freeze'],
                );
                kernel::single('ome_freeze_stock_log')->changeLog($log);


            unset($v);
        }
        unset($deliverys,$sql);
    }

    /**
     * 执行店铺所有商品的库存同步
     */
    function shop_stock_sync($shop_id=''){
        $sql = "SELECT shop_id,node_type,node_id FROM sdb_ome_shop WHERE node_id IS NOT NULL";
        if(!empty($shop_id)){
            $where = " and shop_id='".$shop_id."'";
            $sql .= $where;
        }
        $shop_info = kernel::database()->select($sql);
        if($shop_info){
            foreach($shop_info as $v){
            	if (!$v['node_id']) continue;
                $shop_id = $v['shop_id'];
                $node_type = $v['node_type'];
                $queue_title = "sync_stock_".$shop_id;

                $cursor_id = 0;
                $params = array(
                    'store_sync_from'=>time(),
                    'store_sync_end'=>time(),
                    'shop_id' => $shop_id,
                    'node_type' => $node_type,
                );

                while(true) {
                    if(!$this->shop_sync_stock($cursor_id,$params)){
                        break 1;
                    }
                }
            }
            return true;
        }else{
            return false;
        }
    }

    function shop_sync_stock(&$cursor_id,$params){
        if (!is_array($params)){
            $params = unserialize($params);
        }
        $limit = 20;
        $shop_id = $params['shop_id'];
        $node_type = $params['node_type'];
        $store_sync_from = $params['store_sync_from'];
        $store_sync_end = $params['store_sync_end'];
        $offset = $cursor_id;

        //if($offset==0) $this->reset_freeze();//重置商品的冻结库存

        //获取回写库存
        if ($stock_service = kernel::service('service.stock')){
            if(method_exists($stock_service,'shop_calculate_stock')){
                $stocks = $stock_service->shop_calculate_stock($shop_id, $store_sync_from, $store_sync_end, $offset, $limit);
            }
        }

        if ($stocks){
            if(is_array($stocks) && count($stocks)>0){
                foreach(kernel::servicelist('service.stock') as $object=>$instance){
                    if(method_exists($instance,'update_stock')){
                        $instance->update_stock($stocks,$shop_id,$node_type);
                    }
                }
            }

            // 更新店铺的库存同步时间
            if($offset==0){
                app::get('ome')->model('shop')->update(array('last_store_sync_time'=>$store_sync_end),array('shop_id'=>$shop_id));
                app::get('ome')->setConf('store_sync_from'.$shop_id,$store_sync_from);
                app::get('ome')->setConf('store_sync_end'.$shop_id,$store_sync_end);
            }
            $offset = $offset + $limit;
            $cursor_id = $offset;
            return true;
        }else{
            return false;
        }
    }
}