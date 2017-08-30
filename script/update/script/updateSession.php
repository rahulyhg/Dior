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

$sessionObj = kernel::single('ome_session');
$sessionObj->updateSession();

ilog("Update Session $domain Ok.");

/**
 * 日志
 */
function ilog($str) {
	
	global $domain;
	$filename = dirname(__FILE__) . '/../logs/update_' . date('Y-m-d') . '.log';
	$fp = fopen($filename, 'a');
        echo date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n";
	fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
	fclose($fp);
}
