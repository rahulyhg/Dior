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

require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

$shell = new base_shell_loader;

//以下代码记得下次升级前去除
//-----------卸载部分APP 1.1.0.110410版本使用-----------------//
$sql = 'ALTER TABLE `sdb_ome_branch_product_pos` DROP PRIMARY KEY';
$sql2= 'ALTER TABLE `sdb_ome_branch_product_pos` ADD `pp_id` MEDIUMINT( 8 ) NULL AUTO_INCREMENT PRIMARY KEY FIRST';
/*$update_sql = "alter table `sdb_ome_branch_product_pos`
add column `pp_id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT first,
change `product_id` `product_id` int(10) UNSIGNED NOT NULL,
change `pos_id` `pos_id` mediumint(8) UNSIGNED NOT NULL,
drop primary key,
add primary key(`pp_id`)";*/
kernel::database()->exec($sql);
kernel::database()->exec($sql2);
//-----------卸载部分APP 1.1.0.110410版本使用-----------------//


//手动更新所有的app的信息
$app_manager = new base_application_manage;
$app_manager->update_local();

$shell->exec_command("update --ignore-download");

$func = new ome_func;
$func->disable_menu();

//以下代码记得下次升级前去除
//-----------卸载部分APP 1.1.0.110410版本使用-----------------//


$shell->exec_command("uninstall omekpi");
$shell->exec_command("uninstall omeapilog");
//$shell->exec_command("uninstall purchase");
//-----------卸载部分APP 1.1.0.110410版本使用-----------------//