<?php
$root_dir = realpath(dirname(__FILE__).'/../../../');//echo $root_dir;exit();
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");
require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}
require_once(APP_DIR.'/base/defined.php');
cachemgr::init(false);
echo "begin <br/>";
$refund_info = array(
	0=>array(
		'oms_rma_id'=>49,
	),
);
kernel::single('omemagento_service_order')->update_status_test('210019253','refunding','',time(),$refund_info); // 定时读取AX库存信息，更新库存


echo "<br/> end";