<?php
class wms_print_tmpl_logi_amazon extends wms_print_tmpl_express{

	public function __construct($controller){
		$this->smarty = $controller;
	}

	public function setParams( $params = array() ){

		if(!$params['order_bn']) return $this;

		$order_Mdl = app::get('ome')->model('orders');

		$filter = array('self_delivery'=>'false','shop_type'=>'amazon');
		
		$filter['order_bn'] = implode(',',$params['order_bn']);

		$orders = $order_Mdl->getList('order_bn',$filter);

		if($orders){
			foreach ($orders as $v) {
				$order_bns[] = $v['order_bn'];
			}

			$extend_message = '订单为'.implode(',',$order_bns).'属于亚马逊配送，请去亚马逊后台进行打印。打印完后，将运单号录入到淘管对应的发货单上。';

			$this->smarty->pagedata['extend_message'] = $extend_message;
		}
		
		return $this;
	}

}