<?php
/**
 * 重新生成售后单据
 * 
 * @author yangminsheng@shopex.com
 * @version 1.0
 */

//php d:\wamp\www\prerelease20120904\script\update\script\updateAftersale.php 192.168.132.54/prerelease20120904
//php updateAftersale.php 192.168.132.54/prerelease20120904
$domain = $argv[1];

$order_id = $argv[2];

$host_id = $argv[3];

if (empty($domain)) {

    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);


$pre_time = '2012-11-01';

$last_time = date('Y-m-d');

$result = kernel::single('sales_updatescript_updateaftersale')->updateAftersale($pre_time,$last_time);


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


?>