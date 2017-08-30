#!/usr/bin/env php
<?php
//更新脚本

if(isset($argv[1])){
    $server_name = $argv[1];
    $_SERVER['SERVER_NAME'] = $server_name;
}

$root_dir = realpath(dirname(__FILE__).'/../../');
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");
@include_once(APP_DIR.'/base/defined.php');

require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

cachemgr::init(false);


//应用证书号
//$certi_id = kernel::single('base_certificate')->certi_id();

$certi = get_certificate();
$certi  = urlencode(json_encode($certi));
//应用节点号
$node_id = kernel::single('base_shopnode')->node_id('ome');
$info = $_SERVER['SERVER_NAME'] .'==='.$certi .'==='.$node_id;
echo $info;
