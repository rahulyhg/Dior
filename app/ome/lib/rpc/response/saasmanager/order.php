<?php
class ome_rpc_response_saasmanager_order
{
   
	function do_confirm_order($data,& $apiObj){
		$db = kernel::database();
		$order_bn = $data['order_bn'];
		$shop_id = $data['shop_id'];
		$row = $db->selectrow('select order_id,ship_status from sdb_ome_orders where shop_id="'.$shop_id.'" and  order_bn ="'.$order_bn.'"');
		
		if($row){
				if($row['ship_status'] == 0){
					$apiObj->error_handle('订单未发货');
				}else if($row['ship_status'] == 2){
					$apiObj->error_handle('订单部分发货');
				}else{
					$order_id = $row['order_id'];
					$db->exec('update sdb_ome_orders set process_status ="splited" where order_id='.$order_id);
					$row = $db->selectrow('select order_id,ship_status from sdb_ome_orders where shop_id="'.$shop_id.'" and order_bn ="'.$order_bn.'"');
					
					$apiObj->api_response('订单号：'.$order_bn.' 订单确认状态:'.$row['ship_status']);
				}
		}else{
			
			$apiObj->error_handle('没有此订单');
		}	
			
	}
	
	function do_pay_order($data,& $apiObj){
		$db = kernel::database();
		$order_bn = $data['order_bn'];
		$shop_id = $data['shop_id'];
		$row = $db->selectrow('select order_id,pay_status from sdb_ome_orders where shop_id="'.$shop_id.'" and order_bn ="'.$order_bn.'"');
		if($row){
				if($row['pay_status'] == 0){
					$order_id = $row['order_id'];
					$db->exec('update sdb_ome_orders set pay_status ="1" where order_id='.$order_id);
					$row = $db->selectrow('select order_id,pay_status from sdb_ome_orders where shop_id="'.$shop_id.'" and order_bn ="'.$order_bn.'"');
					
					$apiObj->api_response('订单号：'.$order_bn.' 订单支付状态:'.$row['pay_status']);
				}else{
					$apiObj->error_handle('订单不是未支付状态');
				}
		}else{
			$apiObj->error_handle('没有此订单');
		}	
				
	}

	
	function do_cancel_order($data,& $apiObj){
		$db = kernel::database();
		$order_bn_list = array($data['order_bn']);
		$shop_id = $data['shop_id'];
		foreach($order_bn_list as $order_bn){
			$order_bn = trim($order_bn);
			$row = $db->selectrow('select order_id from sdb_ome_orders where shop_id="'.$shop_id.'" and order_bn ="'.$order_bn.'"');
			if($row){
					$order_id = $row['order_id'];
					$db->exec('update sdb_ome_orders set process_status="cancel" where order_id='.$order_id);
					$row = $db->selectrow('select order_id,process_status from sdb_ome_orders where shop_id="'.$shop_id.'" and order_bn ="'.$order_bn.'"');
					$apiObj->api_response('订单号：'.$order_bn.' 订单状态:'.$row['process_status']);
			}else{
				$apiObj->error_handle('没有此订单');
			}	
		}
		
		
	}
    
    
}
