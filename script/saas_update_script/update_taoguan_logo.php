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


 app::get('desktop')->setConf('banner','');
 app::get('desktop')->setConf('logo','');
 app::get('desktop')->setConf('logo_desc','');
 echo "ok\n";
 exit;