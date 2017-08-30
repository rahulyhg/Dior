<?php
/**
 * 物流对账单抓取发货单数据
 *
 * @author sunjing<sunjing@shopex.cn>
 * @version 1.0
 * @param $argv[1] 域名
 * @param $argv[2] ip
 */
error_reporting(E_ALL ^ E_NOTICE);
$domain = $argv[1];
$host_id = $argv[2];

if (empty($domain) || empty($host_id)) {
    die('No Params');
}
require_once(dirname(__FILE__) . '/../../lib/init.php');
cachemgr::init(false);
kernel::single('logisticsaccounts_estimate')->crontab_delivery();





