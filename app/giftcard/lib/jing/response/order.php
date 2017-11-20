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
		$objCard=$this->app->model("cards");
		
		$isExisitWxOrderBn=$objGift->getList("OrderId,FromUserName",array('OrderId'=>$order['OrderId'],'status'=>'1'));
		$order_id=$isExisitWxOrderBn[0]['OrderId'];
		if(empty($order_id))return array('status'=>'fail','msg'=>'礼品卡订单不存在');
		//判断是否本人签收
		$FromUserName=$order['FromUserName'];
		if($FromUserName==$isExisitWxOrderBn[0]['FromUserName']){
			return array('status'=>'succ','msg'=>'succ');
		}
		
		$arrCardUpdate=array();
		$arrCardCode=array();
		$accept_time=$order['CreateTime'];
		if($order['IsChatRoom']=="true"){//群发 需要走脚本 来获取领取事件
			$arrCardUpdate['chatroom']='true';
			$arrCardUpdate['status']='accept';
			if(!$objCard->update($arrCardUpdate,array("wx_order_bn"=>$order_id))){
				return array('status'=>'fail','msg'=>'update accept fail');
			}
		}else{
			$arrCardCode=$objCard->getList("id,card_id",array("wx_order_bn"=>$order_id));
			foreach($arrCardCode as $card){//多卡 更新 期限
				$arrCardUpdate=$arrGoods=array();
				$id=$card['id'];
				$arrCardUpdate['begin_time']=$accept_time;
				$arrCardUpdate['end_time']=kernel::single("giftcard_order")->cardEndTime($accept_time,$card_id);
				$arrCardUpdate['status']='accept';
				if(!$objCard->update($arrCardUpdate,array("id"=>$id))){
					return array('status'=>'fail','msg'=>'update accept fail');
				}
			}
		}
		return array('status'=>'succ','msg'=>'succ');
	}
	//转赠
	public function card($card){
		if($card['MsgType']!='event'||$card['Event']!='user_get_card')return array('status'=>'succ','msg'=>'事件异常');
		
		if(!isset($card['OldUserCardCode'])||empty($card['OldUserCardCode'])||$card['IsGiveByFriend']=="0"){
			return array('status'=>'succ','msg'=>'succ');
		}
		
		$OldUserCardCode=$card['OldUserCardCode'];
		$NewUserCardCode=$card['UserCardCode'];
		$card_id=$card['CardId'];
		
		$arrCard_code=array();
		$objCard=$this->app->model("cards");
		$arrCard_code=$objCard->getList("card_code",array('card_code'=>$OldUserCardCode,'card_id'=>$card_id),0,1);
		$arrCard_code=$arrCard_code[0];
		//echo "<pre>";print_r($card);print_r($arrCard_code);exit();
		if(empty($arrCard_code))return array('status'=>'fail','msg'=>'老卡不存在');
		
		//更新新code 并且更新期限
		$accept_time=$card['CreateTime'];
		$arrCardUpdate=array();
		$arrCardUpdate['begin_time']=$accept_time;
		$arrCardUpdate['end_time']=kernel::single("giftcard_order")->cardEndTime($accept_time,$card_id);
		$arrCardUpdate['status']='accept';
		$arrCardUpdate['card_code']=$NewUserCardCode;
				
		if(!$objCard->update($arrCardUpdate,array('card_code'=>$OldUserCardCode,'card_id'=>$card_id))){
			return array('status'=>'fail','msg'=>'update accept fail');
		}
		
		return array('status'=>'succ','msg'=>'succ');
	}
}
