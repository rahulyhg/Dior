<?php
class giftcard_pos_response_redeem
{	
	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function redeem($data){
		$card_code=$data['code'];
		$customer_code=$data['customer_code'];
		$redeem_sku=$data['redeem_sku'];
		$redeem_quantity=$data['redeem_quantity'];
		
		if(empty($card_code)||empty($customer_code)||empty($redeem_sku)||empty($redeem_quantity)){
			return array('status'=>'fail','msg'=>'parameter invalid','api_code'=>'301');
		}
		
		$card_setting    = app::get('giftcard')->getConf('giftcard_setting');
		$arrOfflineStore=explode(',',$card_setting['offline_store']);
		if(!in_array($customer_code,$arrOfflineStore)){
			return array('status'=>'fail','msg'=>'The gift card can not redeem in this boutique','api_code'=>'203');
		}
		
		if($redeem_quantity!==1){
			return array('status'=>'fail','msg'=>'The gift card can not redeem this product or quantity','api_code'=>'202');
		}
		
		$objCard=kernel::single("giftcard_mdl_cards");
		$objQueue=$this->app->model("queue");
		
		$arrCard_code=array();
		$arrCard_code=$objCard->getList("id,order_bn,status,card_code,card_id,convert_type,createtime",array("card_code"=>$card_code));
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
		
		$transaction = $objQueue->db->beginTransaction();
		$redeemtime=time();
		//queue
		$arrQueue=array();
		$arrQueue['order_bn']=$arrCard_code['order_bn'];
		$arrQueue['queue_type']='statement';
		$arrQueue['createtime']=$redeemtime;
		$objQueue->save($arrQueue);
		
		$card_id=$arrCard_code['card_id'];
		$data['order_bn']=$arrCard_code['order_bn'];
		$data['card_id']=$arrCard_code['card_id'];
		$data['card_code']=$arrCard_code['card_code'];
		
		if(!kernel::single("giftcard_wechat_request_order")->getCardCodeInfo($data,$msg,$card_begin_time,$card_end_time)){
			
			$objQueue->db->rollBack();
			
			switch($msg){
				case '请求超时, 请重试':
					return array('status'=>'fail','msg'=>'Call wechat api timeout','api_code'=>'402');
					break;
				case '礼品卡过期或超额':
					return array('status'=>'fail','msg'=>'invalid gift card','api_code'=>'101');
					break;
			}
		}
		
		//update card
		$UpdateCard=array();
		$UpdateCard['status']='redeem';
		$UpdateCard['customer_code']=$customer_code;
		$UpdateCard['card_type']='offline';
		$UpdateCard['begin_time']=$card_begin_time;
		$UpdateCard['end_time']=$card_end_time;
		$UpdateCard['redeemtime']=$redeemtime;
		if(!$objCard->update($UpdateCard,array('id'=>$arrCard_code['id']))){
			$objQueue->db->rollBack();
			return array('status'=>'fail','msg'=>'Unknow exception','api_code'=>'403');
		}
		
		$arrProducts=array();
		if(!$arrProducts=kernel::single("giftcard_magento_request_product")->getProduct($card_id)){
			
			$objQueue->db->rollBack();
			
			return array('status'=>'fail','msg'=>'The gift card can not redeem this product or quantity','api_code'=>'202');
		}
		
		$boolValidateProduct=false;
		foreach($arrProducts as $product){
			if($product['sku']==$redeem_sku){
				$boolValidateProduct=true;
				break;
			}
		}
		if(!$boolValidateProduct){
			
			$objQueue->db->rollBack();
			
			return array('status'=>'fail','msg'=>'The gift card can not redeem this product or quantity','api_code'=>'202');
		}
	
		//核销
		if(!kernel::single("giftcard_wechat_request_order")->consume($data,$msg)){
			
			$objQueue->db->rollBack();
			
	        switch($msg){
				case '请求超时, 请重试':
					return array('status'=>'fail','msg'=>'Call wechat api timeout','api_code'=>'402');
					break;
				case '礼品卡核销失败':
					return array('status'=>'fail','msg'=>'This gift card can not redeem in PCD BTQ','api_code'=>'204');
					break;
			}
		}
		
		$objQueue->db->commit($transaction);
		$transaction=NULL;
		
		//暂时直接生成文件给ax
		kernel::single('giftcard_queue_statement')->run($arrQueue);
		
		return array('status'=>'succ','msg'=>'succ','api_code'=>200);
	}
}
