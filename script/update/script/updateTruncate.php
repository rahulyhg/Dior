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

//清空生成唯一bn表
$endTime = strtotime(date("Y-m-d"));
$sql = "DELETE FROM `sdb_ome_concurrent` WHERE `current_time`<'".$endTime."'";
$db->exec($sql);
$sql = 'OPTIMIZE TABLE `sdb_ome_concurrent`';
$db->exec($sql);

//重围冻结库存
//danny_freeze_stock_log
define('FRST_OPER_NAME','system');
define('FRST_TRIGGER_OBJECT_TYPE','crontab定时重置所有商品冻结库存');
define('FRST_TRIGGER_ACTION_TYPE','updateTruncate.php');
$productObj = kernel::single('ome_sync_product');
$productObj->reset_freeze();

ilog("TRUNCATE TABLE sdb_ome_concurrent $domain Ok.");

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/truncate_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
