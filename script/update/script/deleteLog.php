<?php
/**
 * 根据传入的域名做初始化工作
 * 
 * @author hzjsq@msn.com
 * @version 1.0
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain) || empty($order_id) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$db = kernel::database();

//清除 7 天之前的同步日志
$endTime = time()-7*86400;//
$sql = 'TRUNCATE TABLE `sdb_ome_api_log_copy`';
$db->exec($sql);
$sql = "DELETE FROM `sdb_ome_api_log` WHERE `createtime`<'".$endTime."'";
$db->exec($sql);
//$sql = 'OPTIMIZE TABLE `sdb_ome_api_log`';
//$db->exec($sql);

//清除 15 天前的有通知消息
$sql = "DELETE FROM `sdb_base_rpcnotify` WHERE `notifytime`<'".$endTime."'";
$db->exec($sql);
//$sql = 'OPTIMIZE TABLE `sdb_base_rpcnotify`';
//$db->exec($sql);

ilog("DELETE Data From sdb_ome_api_log $domain Ok.");

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/delete_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
