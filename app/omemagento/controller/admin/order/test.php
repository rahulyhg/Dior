<?php
class omemagento_ctl_admin_order_test extends desktop_controller{
	var $name = "模拟测试";
    var $workground = "setting_tools";

	public function index(){
		$this->page('admin/order/test.html');
	}


	public function test(){
		$this->begin('index.php?app=omemagento&ctl=admin_order_test&act=index');

		$obj = kernel::single('omemagento_service_order');
		$order_bn = $_POST['order_bn'];
		$status = $_POST['status'];
		if($status=='Shipped'){
			$tracking_code = $_POST['logi_no'];
		}
		$obj->update_status($order_bn,$status,$tracking_code);
		$this->end('true','同步成功！');
	}
}