<?php
class giftcard_magento_response_search
{	
	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function search($arrOpenId){
		$open_id=$arrOpenId['open_id'];
		if(empty($open_id)){
			return array('status'=>'fail','msg'=>'请传入open_id');exit();
		}
		$arrRetrun=array();
		$objOrder=kernel::single("ome_mdl_orders");
		
		$isExisit=$objOrder->count(array('wechat_openid'=>$open_id));
		if($isExisit<1)return array('status'=>'succ','msg'=>'succ','data'=>$arrRetrun);
		
		$arrOrders=$objOrder->db->select("SELECT o.order_id,o.order_bn,o.logi_no,o.ship_addr,o.ship_name,o.ship_area,o.ship_mobile,o.wechat_openid,o.total_amount,o.ship_status,o.pay_status,o.route_status,o.createtime FROM sdb_ome_orders o WHERE o.wechat_openid='$open_id' ORDER BY o.createtime DESC");
		
		if(empty($arrOrders))return array('status'=>'succ','msg'=>'succ','data'=>$arrRetrun);
		
		foreach($arrOrders as $k=>$order){
			$order_status='';
			if($order['route_status']=="1"){
				$order_status='已签收';
			}elseif($order['ship_status']=="1"){
				$order_status='已发货';
			}elseif($order['pay_status']=="1"){
				$order_status='未发货';
			}else{
				$order_status='未支付';
			}
			$order_id=$order['order_id'];
			$arrOrders[$k]['logi_name']='顺丰速递';
			$arrOrders[$k]['order_status']=$order_status;
			$arrOrders[$k]['createtime']=date("Y-m-d H:i:s",$order['createtime']);
			$arrOrders[$k]['pay_bn']='微信支付';
			
			list($city1, $city2, $city3) = explode('/',$order['ship_area']);
			$arrOrders[$k]['ship_area']=substr($city1,9).'-'.$city2.'-'.substr($city3,0,strpos($city3,":"));
			
			unset($arrOrders[$k]['route_status']);
			unset($arrOrders[$k]['ship_status']);
			unset($arrOrders[$k]['pay_status']);
			unset($arrOrders[$k]['order_id']);
			$arrOrders[$k]['items']=$objOrder->db->select("SELECT bn,price,nums,name FROM sdb_ome_order_items WHERE order_id='$order_id'");
		}
		return array('status'=>'succ','msg'=>'succ','data'=>$arrOrders);
	}
}
