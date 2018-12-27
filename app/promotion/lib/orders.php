<?php
class promotion_orders{
	
    function getOrdersData(&$data){
	    $objOrder=app::get('ome')->model('orders');
		//$sql="SELECT order_id,order_bn,shop_id,payed,createtime,paytime FROM `sdb_ome_orders` where process_status in ('unconfirmed','is_retrial') AND `status`='active' AND pay_status='1' AND is_u_g='false' AND ship_status='0' AND shop_type='taobao' ORDER BY paytime ASC";
		$sql="SELECT order_id,order_bn,shop_id,payed,createtime,paytime,order_refer_source FROM `sdb_ome_orders` where order_bn='Test1541562131'";
		$arrOrders=$objOrder->db->select($sql);
		
		if(empty($arrOrders[0]['order_bn']))return false;
		$data=$arrOrders;
	} 
	
	function getOrdersDetailsData(&$data){ 
		$objOrder=app::get('ome')->model('orders');
		foreach($data as $k=>$order){
			$order_id=$order['order_id'];
			$sql="SELECT o.bn,o.quantity,o.obj_type,o.sale_price FROM sdb_ome_order_objects o WHERE o.order_id='$order_id' AND obj_type!='gift'"; 
			$arrDetails=$objOrder->db->select($sql);
			$data[$k]['order_objects']=$arrDetails;
		}
	}
}
