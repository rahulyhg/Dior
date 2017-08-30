#!/usr/bin/env php
<?php
$dir = realpath(dirname(__FILE__));

include $dir."/../config/db_exinfo.php";
include "db_ex.php";


$db = new db_ex();

$hour = gmdate("G");

$now = gmmktime(gmdate('G'),0,0);

$sql = "SELECT * FROM cron_stock_safe WHERE exec_hour=".$hour." AND last_exec_time <= ".($now-24*60*60);

$rows = $db->select($sql);

if($rows){
    foreach($rows as $v){
        $worker = explode(":",$v['worker']);
        $class = $worker[0];
        $method = $worker[1];
        system($dir."/cmd.php \"".$v['server_name']."\" \"kernel::single('".$class."')->".$method."(".$v['branch_id'].");\"");
        $db->exec("UPDATE cron_stock_safe SET last_exec_time=".$now. " WHERE oid=".$v['oid']); 
    }
}

$crontab_id = $argv[1];

if($crontab_id){
    $db->exec("UPDATE crontab SET status='hibernate',last_modify=".time()." WHERE crontab_id=".$crontab_id);
}