<?php
class giftcard_wechat_request_check{
		
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function check(){
		$objGift=$this->app->model('orders');
		$arrLog=$objGift->db->select("SELECT `code`,request FROM `sdb_giftcard_logs` where api_method='exchangeOrder' and `status`='succ' order BY createtime desc LIMIT 600,200");
		
		$arrLogs=array();
		foreach($arrLog as $k=>$log){
			$a='';
			$a=json_decode($log['request'],true);
			$arrLogs[$k]['bn']=$a['products']['0']['bn'];
			$arrLogs[$k]['code']=$a['trade_no'];
		}
		
		
		foreach($arrLogs as $key=>$value){
			$code='';
			$arrOrder='';
			$code=$value['code'];
			$arrOrder=$objGift->db->select("SELECT i.bn,o.order_bn FROM sdb_ome_orders o LEFT JOIN sdb_ome_order_items i ON o.order_id=i.order_id WHERE o.card_code='$code' AND o.shop_type='minishop'");
			
			if($arrOrder['0']['bn']!=$value['bn']){
				echo "<pre>2222";print_r($arrOrder);
			}
			
		}
		echo 'succ';
	}
	

}
