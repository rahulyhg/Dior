#!/usr/bin/env php
<?php
//由于之前使用的是sandbox的地址，同时升级过程中license不稳定导致很多站点license没有重新申请，node_id没申请到，所以需要强制更新所有的license并申请node_id

define('ROOT_DIR',realpath(dirname(__FILE__).'/../../'));
define('APP_DIR',ROOT_DIR."/app/");

$server_name = $argv[1];
$_SERVER['SERVER_NAME'] = $server_name;

require(APP_DIR.'/base/kernel.php');
$shell = new base_shell_loader;


//force to get license
base_certificate::register();

//force to get node_id
base_shopnode::register('taoguan');

