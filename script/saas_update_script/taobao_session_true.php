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

$db = kernel::database();
$shop_list = $db->select("SELECT * FROM sdb_ome_shop WHERE disabled='false'");
foreach($shop_list as $shop){
	if($shop['node_id']){
		 app::get('ome')->setConf('taobao_session_'.$shop['node_id'],'true');
	}
}
echo app::get('ome')->setConf('ome.branch.mode');exit;

