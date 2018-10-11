<?php
$root_dir = realpath(dirname(__FILE__).'/../../../');
require_once($root_dir . "/config/config.php");
define('APP_DIR', ROOT_DIR . "/app/");
require_once(APP_DIR . '/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR . '/base/autoload.php');
}
require_once(APP_DIR . '/base/defined.php');
cachemgr::init(false);

echo "begin <br/>";

###### OMS->WMS 接口调用执行 ######
$sql       = "select id from sdb_qmwms_queue where status='2' and repeat_num<5";
$queueData = app::get('qmwms')->model('queue')->db->select($sql);

if(!empty($queueData)){
    kernel::single('qmwms_queue')->repeat_push_wms($queueData);
}

echo "<br/> end";