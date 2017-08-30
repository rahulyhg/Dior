#!/usr/bin/env php
<?php
#升级时network中的矩阵地址没有更新，所以重新更新
define('ROOT_DIR',realpath(dirname(__FILE__).'/../../'));
define('APP_DIR',ROOT_DIR."/app/");

$server_name = $argv[1];
$_SERVER['SERVER_NAME'] = $server_name;


require(APP_DIR.'/base/kernel.php');
$shell = new base_shell_loader;



$db = kernel::database();

$sql1 = "UPDATE sdb_base_network SET node_url='http://matrix.ecos.shopex.cn',node_api=''";

$db->exec($sql1);
