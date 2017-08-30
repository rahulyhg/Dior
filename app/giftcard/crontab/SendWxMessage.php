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

$json='{"touser":"oDvUM0RvQCN-Qw0151YyNSTaooog","template_id":"j_d9ZdpAaABtYnM5v2D2Grwuwxkw36SFszhJgE990PE","page":"","form_id":"60812605c751ccfe5d685db6c8d600d6","data":{"keyword1":{"value":"GC004585166558512","color":"#173177"},"keyword2":{"value":"2017-08-10 20:25:55","color":"#173177"},"keyword3":{"value":"Dior迪奥烈艳蓝金唇膏","color":"#173177"},"keyword4":{"value":"黄皇","color":"#173177"},"keyword5":{"value":"上海-上海市-浦东新区 花木路500弄3号701","color":"#173177"},"keyword6":{"value":"您可通过微信搜一搜搜索“迪奥”，进入迪奥官方商城小程序查询订单状态","color":"#173177"}},"emphasis_keyword":""}';
kernel::single("giftcard_wechat_request_message")->reSend($json,'GC004585166558512');//脚本