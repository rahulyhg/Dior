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

define('COMMAND_MODE', true);

if (empty($domain) || empty($order_id) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);
$charsetObj = kernel::single('base_charset');

$data = array();
$data['domain'] = $charsetObj->utf2local($domain);
$certi_id = base_certificate::certi_id();
$data['certi_id'] = $charsetObj->utf2local($certi_id);
$data['node_id'] = base_shopnode::node_id('ome');

$str .= '"'.implode('","',$data).'"';

ilog($str);

/**
 * 日志
 */
function ilog($str) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/nodeList_' . date('Y-m-d') . '.csv';
    $fp = fopen($filename, 'a');
        echo date("m-d H:i") . "\t" . $domain . "\n";
    fwrite($fp, $str. "\n");
    fclose($fp);
}