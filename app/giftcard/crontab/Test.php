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
$order=json_decode('{"ToUserName":"gh_c85110c224cf","FromUserName":"omZYgs6BzKhv_HVmRNxo8wHMZNLo","CreateTime":"1502885269","MsgType":"event","Event":"giftcard_user_accept","PageId":"Wc6cT2KtvHc9E87Ttb9kN8xc3Tuxun4PdvzVZmdrPR0=","OrderId":"AQAACDR49a6PTDcKBpmWILONnwUA","IsChatRoom":"false","UnionId":"oXgeOwp7gpltUgMvf6LNQ5HMcPPw"}',true);

kernel::single("giftcard_jing_response_order")->update($order);//½Å±¾