#!/usr/bin/env php
<?php
$dir = realpath(dirname(__FILE__));

include $dir."/../config/db_exinfo.php";
include "db_ex.php";


$db = new db_ex();

$sql = "SELECT * FROM crontab WHERE status='hibernate'";

$rows = $db->select($sql);

if($rows){
    foreach($rows as $v){
        $crontab_id = $v['crontab_id'];
        $db->exec("UPDATE crontab SET status='running',last_modify=".time()." WHERE crontab_id=".$crontab_id);
        
        $worker = $v['worker'];
        $params = $v['params'];
        
        $command = $dir."/../script/".$worker." '".$crontab_id."'".($params?" '".$params."'":"")." > /dev/null &";
        
        system($command);
    }
}