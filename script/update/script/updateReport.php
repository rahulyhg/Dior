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

//goodsamount  商品销量统计
//kernel::single('omeanalysts_crontab_script_goodsamount')->statistics();

//goodsrma 商品售后量统计
//kernel::single('omeanalysts_crontab_script_goodsrma')->statistics();

//ordersPrice  客单价分布情况
kernel::single('omeanalysts_crontab_script_ordersPrice')->orderPrice();

//ordersTime  下单时间分布情况
kernel::single('omeanalysts_crontab_script_ordersTime')->orderTime();

//rmatype  售后类型分布统计
kernel::single('omeanalysts_crontab_script_rmatype')->statistics();

//sale
kernel::single('omeanalysts_crontab_script_sale')->statistics();

//catSaleStatis 商品类目销售对比统计
kernel::single('omeanalysts_crontab_script_catSaleStatis')->statistics();

//productSaleRank  产品销售排行榜
kernel::single('omeanalysts_crontab_script_productSaleRank')->statistics();

//storeStatus  库存状况综合分析
kernel::single('omeanalysts_crontab_script_storeStatus')->statistics();
kernel::single('omeanalysts_crontab_script_bpStockDetail')->statistics();

ilog("update report $domain Ok.");

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/report_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
