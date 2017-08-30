<?php
/**
 * 还原部分被覆盖的数据
 * 
 * @author chenping<chenping@shopex.cn>
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

$db = kernel::database();

$opLogModel = app::get('ome')->model('operation_log');
$orderModel = app::get('ome')->model('orders');
$orderDeliv  =app::get('ome')->model('delivery_order');
$filter = array(
    'abnormal' => 'false',
    'is_fail' => 'false',
    'status' => 'active',
    'process_status' => 'unconfirmed',
    'is_auto' => 'false',
    'pause' => 'false',
    'group_id|bthan' => 0,
    'op_id|bthan' => 0,
);
$orderList = $orderModel->getList('order_id,order_bn,group_id,op_id',$filter);
$orderLog = array();
$system = kernel::single('ome_func')->get_system();
foreach ($orderList as $key => $value) {
    # 判断是否已经生成发货单
    $od = $orderDeliv->getList('*',array('order_id'=>$value['order_id']),0,1);
    if ($od) continue;
    
    $sql = ' update sdb_ome_orders set group_id=NULL , op_id=NULL where order_id='.$value['order_id'].' limit 1';
    $db->exec($sql);

    $opLogModel->write_log('order_dispatch@ome',$value['order_id'],"退回到暂存区",time(),$system);

    $orderLog[] = $value;
}
ilog('退回数量:'.count($orderLog));
$str = var_export($orderLog,true);
ilog($str);

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/callbackorder_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
