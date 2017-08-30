<?php
class giftcard_wechat_request_refund extends giftcard_wechat_request{
		
	public function doRefund($arrOrder){
		$objOrder=app::get('ome')->model('refund_apply');
		foreach($arrOrder as $temp){
			foreach($temp as $order){
				$post['order_id']=$order['wx_order_bn'];
				if($result=$this->post(2,'/card/giftcard/order/refund',json_encode($post),'refund',$order['order_bn'],$msg)){
					$objOrder->updateCardRefund($order['apply_id']);
					echo $order['order_bn'].":退款成功<br>";
				}else{
					echo $order['order_bn'].":".$msg."<br>";
				}
			}
		}
	}
	
}
