<?php
class giftcard_jing_response_order
{	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function order($order){	
		if($order['MsgType']!='event'||$order['Event']!='giftcard_pay_done')return array('status'=>'succ','msg'=>'事件异常');
		
		$objGift=$this->app->model("orders");
		
		$isExisitOrderId=$objGift->getList("OrderId",array('OrderId'=>$order['OrderId']));
		if(!empty($isExisitOrderId))return array('status'=>'succ','msg'=>'订单已存在');
		
		$data=array();
		$data['OrderId']=$order['OrderId'];
		$data['PageId']=$order['PageId'];
		$data['Event']=$order['Event'];
		$data['MsgType']=$order['MsgType'];
		$data['CreateTime']=$order['CreateTime'];
		$data['ToUserName']=$order['ToUserName'];
		$data['FromUserName']=$order['FromUserName'];
		if($objGift->save($data)){
			kernel::single("giftcard_wechat_request_order")->getOrders($order['OrderId']);
			return array('status'=>'succ','msg'=>'succ');
		}
		return array('status'=>'fail','msg'=>'fail');
		
	}
	
	public function update($order){
		if($order['MsgType']!='event'||$order['Event']!='giftcard_user_accept')return array('status'=>'succ','msg'=>'事件异常');
		
		$objGift=$this->app->model("orders");
		$isExisitWxOrderBn=$objGift->getList("OrderId,FromUserName",array('OrderId'=>$order['OrderId'],'status'=>'1'));
		$order_id=$isExisitWxOrderBn[0]['OrderId'];
		if(empty($order_id))return array('status'=>'fail','msg'=>'礼品卡订单不存在');
		
		$FromUserName=$order['FromUserName'];
		if($FromUserName==$isExisitWxOrderBn[0]['FromUserName']){
			return array('status'=>'succ','msg'=>'succ');
		}
		
		if($objGift->db->exec("UPDATE sdb_ome_orders SET is_accept_card='true' WHERE wx_order_bn='$order_id' AND shop_type='cardshop'")){
			return array('status'=>'succ','msg'=>'succ');
		}
		
		return array('status'=>'fail','msg'=>'fail');
	}
}
