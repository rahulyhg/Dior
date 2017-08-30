#!/usr/bin/env php
<?php
//更新脚本

if(isset($argv[1])){
    $server_name = $argv[1];
    $_SERVER['SERVER_NAME'] = $server_name;
}

$certificate = json_decode(urldecode($argv[2]),true);
$new_node_id = $argv[3];

$root_dir = realpath(dirname(__FILE__).'/../../');
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");
@include_once(APP_DIR.'/base/defined.php');

require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

cachemgr::init(false);

$app_exclusion = app::get('base')->getConf('system.main_app');


$arr_shop_node_id = array(
    'node_id' => $new_node_id,
);
base_shopnode::set_node_id($arr_shop_node_id,$app_exclusion['app_id']);
base_certificate::set_certificate($certificate);

//应用证书
$certificate_id = kernel::single('base_certificate')->certi_id();
//应用节点号
$node_id = kernel::single('base_shopnode')->node_id('ome');


//比较
if($certificate_id == $certificate['certificate_id']  && $node_id == $new_node_id){
	echo $_SERVER['SERVER_NAME'].':ok';
}else{
	echo $_SERVER['SERVER_NAME'].':fail';
}


