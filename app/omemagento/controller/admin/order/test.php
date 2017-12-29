<?php
class omemagento_ctl_admin_order_test extends desktop_controller{
	var $name = "模拟测试";
    var $workground = "setting_tools";

	public function index(){
		$requestUrl = app::get('ome')->getConf('magento_setting');
		$this->pagedata['request_url'] = $requestUrl;
		$this->page('admin/order/test.html');
	}


	public function test(){
		$this->begin('index.php?app=omemagento&ctl=admin_order_test&act=index');

		$request_url = $_POST['request_url'];
	    app::get('ome')->setConf('magento_setting',$request_url);

		$this->end('true','同步成功！');
	}
}