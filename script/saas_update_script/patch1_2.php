#!/usr/bin/env php
<?php
#不知道为什么升级时shop表没有修复，现在重新修复
define('ROOT_DIR',realpath(dirname(__FILE__).'/../../'));
define('APP_DIR',ROOT_DIR."/app/");

$server_name = $argv[1];
$_SERVER['SERVER_NAME'] = $server_name;


require(APP_DIR.'/base/kernel.php');
$shell = new base_shell_loader;


$shell->exec_command("update");

$db = kernel::database();

$sql1 = "UPDATE sdb_taoguan_shop SET node_type='taobao' WHERE node_type = '' OR node_type IS NULL";

$db->exec($sql1);
