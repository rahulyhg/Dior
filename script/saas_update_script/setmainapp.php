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

//设置mapp
$app_info = array(
    "exclusion" => "true",
    "value" => "true",
    "app_id" => "taoguan"
);
app::get('base')->setConf('system.main_app', $app_info);
echo "Set main app success!\n";
