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
$order['card_code']='209882512984';
$order['card_id']='pmZYgszlF0EOnQEK6EJU-kmg4ZgI';
$order['order_bn']='AQCAqhxgzq6PTDcKBpmWILO-Y0d1';
kernel::single("giftcard_wechat_request_order")->getCardCodeInfo($order);//½Å±¾