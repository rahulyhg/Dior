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
/*
$orderlist = array(
'20121022160995','20121022105129','20120719153834','20120709144408','20120609121351',
'20120607112211','20120601145767','20120601138873','20120601100246','20120531234341',
'20120531157072','20120531156617','20120531142367','20120531132848','20120531134207',
'20120531133579','20120531111089','20120531108370','20120531106977','20120528155412',
'20120528143602','20120525111926','20120524132110','20120523149802','20120522162201',
'20120522159711','20120522116873','20120522104555','20120518152050','20120405094752'
);*/
$orderlist = array(
'20120905152412','20120822152204','20120612116136','20120531130097','20120531121278',
'20120522156851','20120331135845'
);

$ooObj = app::get('ome')->model('operations_order');
$opLogModel = app::get('ome')->model('operation_log');
$orderModel = app::get('ome')->model('orders');
foreach ($orderlist as $key=>$order_bn) {
    # 读取最后一次订单快照
    $order = $orderModel->getList('order_id',array('order_bn'=>$order_bn,'pay_status'=>'3','ship_status'=>'1'),0,1);
    if(!$order) continue;
    $order = $order[0];
    
    $logOrderDetail = $ooObj->getList('*',array('order_id'=>$order['order_id']),0,1,'log_id desc');
    if(!$logOrderDetail) continue;
    $logOrderDetail = $logOrderDetail[0];
   
    $log = $opLogModel->getList('*',array('log_id'=>$logOrderDetail['log_id'],'operate_time|between'=>array(strtotime('2012-11-07'),strtotime('2012-11-08'))));
    if(!$log) continue;

    $detail = unserialize($logOrderDetail['order_detail']);

    $updateOrder = array(
        'pay_status' => $detail['pay_status'],
        'discount' => $detail['discount'],
        'pmt_goods' => $detail['pmt_goods'],
        'pmt_order' => $detail['pmt_order'],
        'total_amount' => $detail['total_amount'],
        'cur_amount' => $detail['cur_amount'],
        'payed' => $detail['payed'],
        'cost_item' => $detail['cost_item'],
        'is_tax' => $detail['is_tax'],
        'tax_no' => $detail['tax_no'],
        'cost_tax' => $detail['cost_tax'],
        'tax_title' => $detail['tax_title'],
        'shipping' =>  $detail['shipping'],
        'payinfo' =>$detail['payinfo'],
        'order_id' => $order['order_id'],
    );

    $orderModel->save($updateOrder); 
    $updateOrder['order_bn'] = $order_bn;

    $str = var_export($updateOrder,true);
    ilog($str);
}

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/repairOrder_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . "\n");
    fwrite($fp, $str . "\n");
    fclose($fp);
}
