<?php
class giftcard_pos_response_validate
{	
	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function validate($data){
		$card_code=$data['code'];
		$customer_code=$data['customer_code'];
		if(empty($card_code)||empty($customer_code)){
			return array('status'=>'fail','msg'=>'parameter invalid','api_code'=>'301');
		}
		
		$card_setting    = app::get('giftcard')->getConf('giftcard_setting');
		$arrOfflineStore=explode(',',$card_setting['offline_store']);
		if(!in_array($customer_code,$arrOfflineStore)){
			return array('status'=>'fail','msg'=>'The gift card can not redeem in this boutique','api_code'=>'203');
		}
		
		$objCard=kernel::single("giftcard_mdl_cards");
		$arrCard_code=array();
		$arrCard_code=$objCard->getList("order_bn,status,card_code,card_id,convert_type,createtime",array("card_code"=>$card_code));
		$arrCard_code=$arrCard_code[0];
		
		if(empty($arrCard_code['card_code'])){
			return array('status'=>'fail','msg'=>'invalid gift card','api_code'=>'101');
		}
		
		if($arrCard_code['convert_type']=="pkg"){
			return array('status'=>'fail','msg'=>'pkg can not redeem in PCD BTQ','api_code'=>'204');
		}
		
		if($arrCard_code['status']=="redeem"){
			return array('status'=>'fail','msg'=>'The gift card is already redeem','api_code'=>'201');
		}
		
		$card_id=$arrCard_code['card_id'];
		$data['order_bn']=$arrCard_code['order_bn'];
		$data['card_id']=$arrCard_code['card_id'];
		$data['card_code']=$arrCard_code['card_code'];
		
		if(!kernel::single("giftcard_wechat_request_order")->getCardCodeInfo($data,$msg,$card_begin_time,$card_end_time)){
			switch($msg){
				case '请求超时, 请重试':
					return array('status'=>'fail','msg'=>'Call wechat api timeout','api_code'=>'402');
					break;
				case '礼品卡过期或超额':
					return array('status'=>'fail','msg'=>'invalid gift card','api_code'=>'101');
					break;
			}
		}
		
		$arrProducts=array();
		if(!$arrProducts=kernel::single("giftcard_magento_request_product")->getProduct($card_id)){
			return array('status'=>'fail','msg'=>'The gift card can not redeem this product or quantity','api_code'=>'202');
		}
		
		$return=array();
		$return['card_code_ref']=substr($card_code,-4);
		$return['created_at']=$arrCard_code['createtime'];
		//$return['expires_on']=$card_end_time;//要更改
		$return['expires_on']=time()+60*60*24;
		$items=array();
		foreach($arrProducts as $k=>$product){
			$items[$k]['name']=$product['name'];
			$items[$k]['num']=1;
			$items[$k]['sku']=$product['sku'];
		}
		$return['products']=$items;
		$return['type']=2;
		//echo "<pre>333";print_r($data);print_r($return);exit();
		return array('status'=>'succ','msg'=>'succ','api_code'=>200,'data'=>$return);
	}
}
