<?php
class giftcard_wechat_request_check{
		
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function check(){
		$ojbCard=kernel::single("giftcard_mdl_cards");
		$objOrder=app::get("ome")->model("orders");
		$arrOrder=$objOrder->db->select("SELECT order_id,order_bn,wx_order_bn,card_code,card_id,`status`,process_status,is_accept_card,total_amount,createtime,wechat_openid FROM `sdb_ome_orders` where shop_type='cardshop'");
		
		
		foreach($arrOrder as $order){
			$arrCards=array();
			$arrCards['p_order_id']=$order['order_id'];
			$arrCards['p_order_bn']=$order['order_bn'];
			$arrCards['order_bn']=$order['order_bn'];
			$arrCards['wx_order_bn']=$order['wx_order_bn'];
			
			$arrCards['card_code']=$order['card_code'];
			$arrCards['old_card_code']=$order['card_code'];
			$arrCards['wechat_openid']=$order['wechat_openid'];
				
			if($order['status']=="finish"){//已核销
				
				$arrRedeemCard=array();
				$arrRedeemCard=$objOrder->db->select("SELECT order_id,createtime,wechat_openid,form_id,card_code FROM `sdb_ome_orders` where shop_type='minishop' AND order_bn='".$order['order_bn']."'");
				$arrRedeemCard=$arrRedeemCard[0];
				
				$arrCards['order_id']=$arrRedeemCard['order_id'];
				$arrCards['status']='redeem';
				$arrCards['redeemtime']=$arrRedeemCard['createtime'];
				$arrCards['form_id']=$arrRedeemCard['form_id'];
				$arrCards['wechat_openid']=$arrRedeemCard['wechat_openid'];
				$arrCards['card_code']=$arrRedeemCard['card_code'];
			
			}else if($order['status']=="dead"){//退款
				$arrCards['status']='refunded';
			}else{//正常
				if($order['is_accept_card']=="true"){//已领取
					$arrCards['status']='accept';
				}else{
					$arrCards['status']='normal';//未领取
				}
			}
			
			
			$arrCards['card_type']='online';
			$arrCards['convert_type']='product';
			$arrCards['price']=$order['total_amount'];
			$arrCards['card_id']=$order['card_id'];
			$arrCards['createtime']=$order['createtime'];
			$arrCards['begin_time']='1502294400';
			$arrCards['end_time']='1514735999';
			
			if(!$ojbCard->save($arrCards)){
				echo "<pre>";print_r($arrCards);
			}	
		}
		
		
	}
	

}
