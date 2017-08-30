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
$update_sql = "alter table `sdb_ome_branch_product_pos`
add column `pp_id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT first,
change `product_id` `product_id` int(10) UNSIGNED NOT NULL,
change `pos_id` `pos_id` mediumint(8) UNSIGNED NOT NULL,
drop primary key,
add primary key(`pp_id`)";
kernel::database()->exec($update_sql);
$shell->exec_command("update");