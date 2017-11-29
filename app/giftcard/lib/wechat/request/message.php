<?php
class giftcard_wechat_request_message extends giftcard_wechat_request{
		
	public function send($arrSend){
		if(empty($arrSend['wechat_openid'])||empty($arrSend['form_id'])){
			return true;
		}
		$arrTemplate=array();
		$arrTemplate['touser']=$arrSend['wechat_openid'];
		$arrTemplate['template_id']=$this->arrSetting['templateid'];
		$arrTemplate['page']='';
		$arrTemplate['form_id']=$arrSend['form_id'];
		$arrTemplate['data']=include_once('template/order.php');
		$arrTemplate['emphasis_keyword']='';
		
		$strProduct=empty($arrSend['order_objects'][0]['pkg_name'])?$arrSend['order_objects'][0]['name']:$arrSend['order_objects'][0]['pkg_name'];
		
		$strTemplate=json_encode($arrTemplate);
		$strTemplate=sprintf($strTemplate,$arrSend['order_bn'],date("Y-m-d H:i:s",time()),$strProduct,$arrSend['consignee']['name'],$arrSend['address_id']."  ".$arrSend['consignee']['addr'],'您可通过微信搜一搜搜索“迪奥”，进入迪奥官方商城小程序查询订单状态');
		
		$this->post(1,'/cgi-bin/message/wxopen/template/send',$strTemplate,'template',$arrSend['order_bn']);
	}
	
	public function reSend($json='',$order_bn=''){
		$this->post(1,'/cgi-bin/message/wxopen/template/send',$json,'template',$order_bn);
	}
	
}
