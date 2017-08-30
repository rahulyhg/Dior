<?php
/**
 * 重新发货
 *
 * @author chenping<chenping@shopex.cn>
 * @version $2012-11-22 11:03Z
 */
error_reporting(E_ALL ^ E_NOTICE);


$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];
$logi_no = $argv[4];

if (empty($domain) || empty($order_id) || empty($host_id) || empty($logi_no)) {
    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$_SERVER['HTTP_HOST'] = $domain;

# 判断发货单是否存在
$deliveryModel = app::get('ome')->model('delivery');
$delivery = $deliveryModel->getList('*',array('logi_no'=>$logi_no),0,1);
if(!$delivery) die('delivery is not exist!');
$delivery = $delivery[0];

# 判断订单是否已经有出入库明细
$iostockModel = app::get('ome')->model('iostock');
$iostock = $iostockModel->getList('*',array('original_bn'=>$delivery['delivery_bn'],'original_id'=>$delivery['delivery_id']));
if ($iostock) {
# 如果已经生成明细，重新发请求
$deliveryModel->call_delivery_api($delivery['delivery_id'],false);
$str = '打发货接口，发货单:'.$delivery['delivery_bn'];
} else {
# 如果没生成明细，重走发货流程
$deliOrderModel = app::get('ome')->model('delivery_order');
$deliorder = $deliOrderModel->getList('*',array('delivery_id'=>$delivery['delivery_id']));
$order_id_list = array();
foreach ($deliorder as $key=>$value) {
$order_id_list[] = $value['order_id'];
}
$orderItemModel = app::get('ome')->model('order_items');
$orderItemModel->update(array('sendnum'=>0),array('order_id'=>$order_id_list));
#将发货单
$deliveryModel->update(array('status'=>'progress','process'=>'false'),array('delivery_id'=>$delivery['delivery_id']));

    $user = app::get('desktop')->model('users')->dump(array('super'=>1),'*',array( ':account@pam'=>array('*') ));
    $logData = array(
        'op_id' => $user['account']['account_id'],
        'op_name' => $user['account']['login_name'],
        'createtime' => strtotime('2000-1-1'),
        'batch_number' => 1,
        'log_type'=>'consign',
        'log_text'=>serialize(array($logi_no))
    );
    app::get('ome')->model('batch_log')->insert($logData);

    kernel::single('ome_crontab_script_consign')->process($logData['log_id'],array($logi_no));

    $str = '重走发货，快递单:'.$logi_no.';发货单:'.$delivery['delivery_bn'].';订单:'.implode('、',$order_id_list);
}

ilog($str);

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/retryConsign' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}