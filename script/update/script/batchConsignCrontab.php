<?php
/**
 * 批量发货自动脚本
 *
 * @author chenping<chenping@shopex.cn>
 * @version $2012-8-22 11:03Z
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

$_SERVER['HTTP_HOST'] = $domain;

$now = time(); $begintime = date("m-d H:i");

//ilog('开始执行批量发货...');

kernel::single('wms_crontab_script_consign')->exec_batch();

$spendtime = time()-$now; $endtime = date("m-d H:i");
ilog($begintime.'开始执行批量发货，'.$endtime.'执行结束，耗时：'.$spendtime.'秒');

/**
 * 日志
 */
function ilog($str) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/batchconsign' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}