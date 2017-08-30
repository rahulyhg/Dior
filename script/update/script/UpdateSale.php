<?php
/**
 * 修复销售单数据
 * 
 * 使用方法: php domainname 开始时间(2012-09-01) 结束时间(2012-10-30)
 * 
 * @author yangminsheng@shopex.com
 * @version 1.0
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain)  ) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

ilog("update Sales $domain begin...");

@ini_set('memory_limit','1024M');

$pre_time = '2012-11-01';

$last_time = date('Y-m-d');

$result = kernel::single('sales_updatescript_updatesales')->updateSales($pre_time,$last_time);


ilog("update Sales $domain OK.");

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/updatesales_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}

