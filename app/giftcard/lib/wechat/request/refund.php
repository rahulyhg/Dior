<?php
class giftcard_wechat_request_refund extends giftcard_wechat_request{
		
	public function doRefund($arrOrder){
		$objOrder=app::get('ome')->model('refund_apply');
		$objCard=kernel::single("giftcard_mdl_cards");
		
		foreach($arrOrder as $temp){
			foreach($temp as $order){
				$post['order_id']=$order['wx_order_bn'];
				if($result=$this->post(2,'/card/giftcard/order/refund',json_encode($post),'refund',$order['order_bn'],$msg)){
					$objOrder->updateCardRefund($order['apply_id']);
					$arrUpdateCard=array();
					$arrUpdateCard['status']='refunded';
					$objCard->update($arrUpdateCard,array('wx_order_bn'=>$order['wx_order_bn']));
					echo $order['order_bn'].":退款成功<br>";
				}else{
					echo $order['order_bn'].":".$msg."<br>";
				}
			}
		}
	}
	
}
