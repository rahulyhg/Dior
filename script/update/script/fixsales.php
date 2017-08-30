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

$db = kernel::database();

$begTime = 1325347200;
$endTime = 1355817600;

$sql = "select count(sale_id) as _c FROM `sdb_ome_sales` WHERE `sale_time` >= '".$begTime."' and `sale_time`<='".$endTime."'";
$sales_count = $db->selectrow($sql);

if($sales_count['_c'] > 100000){
    ilog2();
    exit;
}

ilog("update Sales $domain begin...");

//删除老数据
$sql = "select sale_id FROM `sdb_ome_sales` WHERE `sale_time` >= '".$begTime."' and `sale_time`<='".$endTime."'";
$saleList = $db->select($sql);
foreach($saleList as $sale){
    $delSales .= $sale['sale_id'].",";
}

$delSales = substr($delSales,0,-1);

$db->exec("delete from sdb_ome_sales where sale_id in (".$delSales.")");

$db->exec("delete from sdb_ome_sales_items where sale_id in (".$delSales.")");


$date_arr = array(
    '2012-01-01 00:00:00' => '2012-02-01 23:59:59',
    '2012-02-02 00:00:00' => '2012-03-01 23:59:59',
    '2012-03-02 00:00:00' => '2012-04-01 23:59:59',
    '2012-04-02 00:00:00' => '2012-05-01 23:59:59',
    '2012-05-02 00:00:00' => '2012-06-01 23:59:59',
    '2012-06-02 00:00:00' => '2012-07-01 23:59:59',
    '2012-07-02 00:00:00' => '2012-08-01 23:59:59',
    '2012-08-02 00:00:00' => '2012-09-01 23:59:59',
    '2012-09-02 00:00:00' => '2012-10-01 23:59:59',
    '2012-10-02 00:00:00' => '2012-11-01 23:59:59',
    '2012-11-02 00:00:00' => '2012-12-01 23:59:59',
    '2012-12-02 00:00:00' => '2012-12-18 16:00:00',
);

$obj = kernel::single('sales_updatescript_updatesales');
    foreach ($date_arr as $k=>$v){
        $obj->updateSales($k,$v);
        //echo 'from '.$k.' to '.$v;
    }

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

function ilog2() {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/updatesalesskip_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\n");
    fclose($fp);
}
