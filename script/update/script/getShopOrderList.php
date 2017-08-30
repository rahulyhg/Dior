<?php
/**
 * 获取指定时间范围内前端店铺的订单列表 每一小时执行一次
 * 
 * @author yangminsheng@shopex.com
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

kernel::single('ome_rpc_request_miscorder')->getlist_order();

$ome_syncorder = kernel::single("ome_syncorder");
$omequeueModel = kernel::single("ome_syncshoporder");
$apilog = &app::get('ome')->model('api_order_log');

$orderinfo = $omequeueModel->fetchAll($apilog);

if(!empty($orderinfo)){
    $i=0;
    while(true){
        if(!$orderinfo[$i]['order_bn']) return false;
        $params['order_bn'] = $orderinfo[$i]['order_bn'];
        $params['shop_id'] = $orderinfo[$i]['shop_id'];
        $params['log_id'] = $orderinfo[$i]['log_id'];
        $res = $ome_syncorder->get_order_list_detial($params);
        $i++;
    }

}

ilog("get orders $domain Ok.");

/**
 * 日志
 */
function ilog($str) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/getOrder_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
        echo date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n";
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
