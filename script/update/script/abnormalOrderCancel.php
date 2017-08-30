<?php
/**
 * 异常订单取消
 *
 * @author chenping@shopex.cn
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

$orderModel = app::get('ome')->model('orders');
$orders = $orderModel->getList('order_id,order_bn',array('abnormal'=>'true','is_fail'=>'false','archive'=>0));
if(!$orders || count($orders) > 3000) die('no orders or orders too large:'.count($orders));

foreach ($orders as $order) {
    $orderModel->cancel($order['order_id'],'系统取消订单',false,'async');
    ilog($order['order_bn'].' is canceled!');
}

echo count($orders).' row affect!!!';
/**
 * 日志
 */
function ilog($str) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/abnormalOrderCancel_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
