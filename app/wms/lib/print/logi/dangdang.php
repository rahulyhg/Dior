<?php
class wms_print_tmpl_logi_dangdang extends wms_print_tmpl_express{

	public function __construct($controller){
		$this->smarty = $controller;
	}

	public function setParams( $params = array() ){

		$message['tip1'] = '您现在使用的是 "当当代发物流" 模式，系统将自动将“当当订单号”作为“当当代发物流”的物流单号。';
		$message['tip2'] = '正式面单请使用“当当后台”进行打印。';
		$message['tip3'] = '在校验、发货 时,请扫描“当当后台”打印的面单，继续完成发货流程！';

		$this->smarty->pagedata['message'] = $message;

		return $this;
	}

	public function getTmpl(){

        $this->smarty->singlepage("admin/delivery/express_printbyshipping.html");

	}

}