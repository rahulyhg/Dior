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

$json='{"touser":"oDvUM0RvQCN-Qw0151YyNSTaooog","template_id":"j_d9ZdpAaABtYnM5v2D2Grwuwxkw36SFszhJgE990PE","page":"","form_id":"9bfcb7768e9f8eb6c0e220cbc3c4b6d7","data":{"keyword1":{"value":"GC003831662075993","color":"#173177"},"keyword2":{"value":"2017-08-08 16:07:31","color":"#173177"},"keyword3":{"value":"克丽丝汀迪奥迪奥小姐花漾淡香水 50ml","color":"#173177"},"keyword4":{"value":"李雷","color":"#173177"},"keyword5":{"value":"河北省-石家庄市-长安区 花木路500 ","color":"#173177"},"keyword6":{"value":"您可通过微信搜一搜搜索“迪奥”，进入迪奥官方商城小程序查询订单状态","color":"#173177"}},"emphasis_keyword":""}';
kernel::single("giftcard_wechat_request_message")->reSend($json,'GC003831662075993');//脚本