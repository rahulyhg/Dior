<?php
/**
 * 新增拒收的出入库类型
 */
error_reporting(E_ALL ^ E_NOTICE);

$domain = $argv[1];
$host_id = $argv[2];

if (empty($domain) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');
cachemgr::init(false);

$db = kernel::database();



$sjsql = "INSERT INTO `sdb_ome_iostock_type` (`type_id`, `type_name`) VALUES ('32', '拒收退货入库');";
$db->exec($sjsql);

//ilog('保存打印模板');




/**
 * 日志
 */
function ilog($str) {

	global $domain;
	$filename = dirname(__FILE__) . '/../logs/tmpl_' . date('Y-m-d') . '.log';
	$fp = fopen($filename, 'a');
    echo date("m-d H:i") . "\t" . $domain . "\t" . mb_convert_encoding($str, "gb2312", "utf-8"). "\n";
	fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
	fclose($fp);
}
