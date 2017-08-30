#!/usr/bin/env php
<?php
//更新脚本

define('ROOT_DIR',realpath(dirname(__FILE__).'/../../../'));
define('APP_DIR',ROOT_DIR."/app/");

if(isset($argv[1])){
    $server_name = $argv[1];
    $_SERVER['SERVER_NAME'] = $server_name;
}


require(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

$shell = new base_shell_loader;

$shell->exec_command("cacheclean");
$shell->exec_command("kvstorerecovery");

//申请license
base_certificate::register();
//申请node节点id
base_shopnode::register('ome');
