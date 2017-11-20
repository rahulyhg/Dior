<?php
class giftcard_magento_request_product extends giftcard_magento_request
{	
	public function getProduct($card_id){
		$result=$this->post("index/info/wechat_card_id/".$card_id);
		if($result['success']=="1"&&!empty($result['cord_info']['cord_products'][0])){
			return $result['cord_info']['cord_products'];
		}
		return false;
	}
}
