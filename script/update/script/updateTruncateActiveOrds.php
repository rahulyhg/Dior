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


$sql = "SELECT DISTINCT order_id FROM sdb_ome_orders WHERE is_fail = 'false'
		AND archive=0 AND disabled ='false' AND createtime <1328025600";


//获取异常的失败订单
$orderList = $db->select($sql);

if(count($orderList) <= 0){
    exit;
}

foreach($orderList as $ord){
    $delOrds .= $ord['order_id'].",";
}

$delOrds = substr($delOrds,0,-1);

$db->exec("delete from sdb_ome_orders where order_id in (".$delOrds.")");

$db->exec("delete from sdb_ome_order_objects where order_id in (".$delOrds.")");

$db->exec("delete from sdb_ome_order_items where order_id in (".$delOrds.")");

$db->exec("delete from sdb_ome_order_pmt where order_id in (".$delOrds.")");

$db->exec("delete from sdb_ome_payments where order_id in (".$delOrds.")");



ilog("failorders truncate $domain Ok.",$delOrds);

/**
 * 日志
 */
function ilog($str,$delOrds) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/failorders_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\t" .$delOrds."\n");
    fclose($fp);
}
