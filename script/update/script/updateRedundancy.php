<?php
/**
 * 删除表冗余数据
 * 
 * @author chenping<chenping@shopex.cn>
 * @version 1.0
 * @param $argv[1] 域名
 * @param $argv[2] ip
 * @param $argv[3] 时间线
 * @param $argv[4] 数量
 * @param $argv[5] 类型(1:已付款，已发货、2:全额退款)
 */
error_reporting(E_ALL ^ E_NOTICE);

$domain = $argv[1];
$host_id = $argv[2];
$time = $argv[3];
isset($argv[4]) && $limit = (int)$argv[4];
isset($argv[5]) && $type = (int)$argv[5];
        
if ($argc==1) {
    die('format:<%path%>/php updateRedundancy.php <%domain%>  <%host_id%> <%date%> <%limit%> <%type%>');
}

if (empty($domain)) {
    die('need $argv[1]!');
}

if (empty($host_id)) {
    die('need $argv[2]!');
}

if (!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/',$time,$matches)) {
    die('need correct $argv[3] format: 2009-01-02!');
}

$time = strtotime($matches[0]);
if (!$time) {
    die('need validity time! ');
}
$now = time();
if (($now-$time)<(15*86400)) {
    die('need the date before 15 days ago!');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');
cachemgr::init(false);

$db = kernel::database();
$offset = 0; $count = 0; $plimit = 5000; $type = isset($type) ? $type : 1;
if ($type==1) {
    echo "start delete delivery order...\n";
}elseif($type==2){
    echo "start delete refund order...\n";
}else{
    echo 'finish.';exit;
}
if (isset($limit)) {
    $l = ($limit<$plimit) ? $limit : $plimit;
}else{
    $l = $plimit;
}
$orders = getOrders($time,0,$type,$l);
while ($orders) {
    deleteOrders($orders,$type);
    $offset += $plimit;
    if (isset($limit)) {
        if ($offset>=$limit) {
            break;
        }elseif($limit>$offset && $limit<=($offset+$plimit)){
            $plimit = $limit-$offset;
        }
    }
    $orders = getOrders($time,0,$type,$plimit);
}

//删除数据后一次性优化表
optimize('sdb_ome_orders');
optimize('sdb_ome_order_items');
optimize('sdb_ome_order_objects');
optimize('sdb_ome_order_pmt');
optimize('sdb_ome_payments');
optimize('sdb_ome_refunds');
optimize('sdb_ome_refund_apply');
optimize('sdb_ome_delivery');
optimize('sdb_ome_delivery_order');
optimize('sdb_ome_reship');
optimize('sdb_ome_delivery_items');
optimize('sdb_ome_delivery_items_detail');
optimize('sdb_ome_delivery_bill');
optimize('sdb_ome_delivery_log');
optimize('sdb_ome_reship_items');
optimize('sdb_ome_abnormal');
optimize('sdb_ome_operation_log');
optimize('sdb_ome_shipment_log');
optimize('sdb_ome_order_selling_agent');

echo $count." row affect.\n";


//获取要删除的订单号(只针对已付款，已发货)
function getOrders($time,$offset=0,$type=1,$limit=5000) 
{
    $sql = 'SELECT order_id,order_bn FROM sdb_ome_orders ';
    if ($type==1) {
        //已付款，已发货
        $sql .= ' WHERE status=\'active\' AND confirm=\'Y\' AND ship_status=\'1\' AND pay_status=\'1\' AND last_modified<'.$time;
    }elseif($type==2){
        //全额退款
        $sql .= ' WHERE process_status=\'cancel\' AND status=\'dead\' AND pay_status=\'5\' AND ship_status=\'0\' AND last_modified<'.$time;
    }else{
        return false;
    }
    $sql .= ' ORDER BY order_id LIMIT '.$offset.','.$limit;
    //$sql = 'SELECT order_id,order_bn FROM sdb_ome_orders WHERE confirm=\'Y\' AND ship_status=\'1\' AND pay_status=\'1\' AND last_modified<'.$time.' ORDER BY order_id LIMIT '.$offset.','.$limit;
    $orders = $GLOBALS['db']->select($sql);

    if (!$orders) {
        return false;
    }
    return $orders;
}

//删除订单
function deleteOrders($orders,$type=1) 
{
    if ($type!=1 && $type!=2) {
        exit;
    }

    $order_id = $order_bn = array();
    foreach ($orders as $key=>$value) {
        $order_id[] = $value['order_id'];
        $order_bn[] = $value['order_bn'];
        $GLOBALS['count']++;
    }

    // 订单明细
    deleteOrderItems($order_id);
    // 优惠
    deletePmt($order_id);
    // 收款记录
    deletePaymentBill($order_id);
    if ($type==1) {              //已付款，已发货
        // 发货单记录
        deleteDelivery($order_id);
        // 第三方回写发货日志
        deletePartyDeliveryLog($order_bn);

    }elseif($type==2){        //全额退款
        // 申请退款记录
        deleteRefundApply($order_id);
        // 退款记录
        deleteRefundBill($order_id);
        // 退货单据
        //deleteReship($order_id);
    }

    // 订单异常备注
    deleteAbnormalOrders($order_id);
    // 订单操作日志
    deleteOrderHistory($order_id);
    // 代销人
    deleteSellingAgent($order_id);

    $sql = 'DELETE FROM sdb_ome_orders WHERE order_id in('.implode(',',$order_id).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除订单明细
function deleteOrderItems($orders) 
{
    $sql = 'DELETE FROM sdb_ome_order_items WHERE order_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);

    $sql = 'DELETE FROM sdb_ome_order_objects WHERE order_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除订单优惠方案
function deletePmt($orders) 
{
    $sql = 'DELETE FROM sdb_ome_order_pmt WHERE order_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除收款记录
function deletePaymentBill($orders) 
{
    $sql = 'DELETE FROM sdb_ome_payments WHERE order_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除退款记录
function deleteRefundBill($orders) 
{
    $sql = 'DELETE FROM sdb_ome_refunds WHERE order_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除申请退款记录
function deleteRefundApply($orders) 
{
    $sql = 'DELETE FROM sdb_ome_refund_apply WHERE order_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除发货单记录
function deleteDelivery($orders) 
{
    //发货单据
    $deliverys = getDeliveryOrders($orders);
    if ($deliverys) {
        deleteDeliveryItems($deliverys);
        deleteMergeDeliveryItems($deliverys);
        deleteDeliveryBill($deliverys);
        deleteDeliveryLog($deliverys);
        deleteDeliveryHistory($deliverys);
        $sql = 'DELETE FROM sdb_ome_delivery WHERE delivery_id in('.implode(',',$deliverys).')';
        $GLOBALS['db']->exec($sql);
        ilog($sql);

        $sql = 'DELETE FROM sdb_ome_delivery_order WHERE order_id in('.implode(',',$orders).')';
        $GLOBALS['db']->exec($sql);
        ilog($sql);
    }
}

//删除退货单据
function deleteReship($orders) 
{
    //退货单据
    $reships = getReship($orders);
    if ($reships) {
        deleteReshipItems($reships);
        $sql = 'DELETE FROM sdb_ome_reship WHERE order_id in('.implode(',',$orders).')';
        $GLOBALS['db']->exec($sql);
        ilog($sql);
    }
}

function getDeliveryOrders($orders) 
{
    $sql = 'SELECT delivery_id FROM sdb_ome_delivery_order WHERE order_id in('.implode(',',$orders).')';
    $delivery = $GLOBALS['db']->select($sql);
    if (!$delivery) {
        return false;
    }
    return array_map('current',$delivery);
}

//删除发货单明细
function deleteDeliveryItems($deliverys) 
{
    $sql = 'DELETE FROM sdb_ome_delivery_items WHERE delivery_id in('.implode(',',$deliverys).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

// 删除大发货单
function deleteMergeDeliveryItems($deliverys) 
{
    $sql = 'DELETE FROM sdb_ome_delivery_items_detail WHERE delivery_id in('.implode(',',$deliverys).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除物流单
function deleteDeliveryBill($deliverys) 
{
    if ($GLOBALS['db']->select('SHOW TABLES LIKE \'sdb_ome_delivery_bill\' ')) {
        $sql = 'DELETE FROM sdb_ome_delivery_bill WHERE delivery_id in('.implode(',',$deliverys).')';
        $GLOBALS['db']->exec($sql);
        ilog($sql);
    }
}

//删除物流日志
function deleteDeliveryLog($deliverys) 
{
    $sql = 'DELETE FROM sdb_ome_delivery_log WHERE delivery_id in('.implode(',',$deliverys).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}


//删除退货明细
function deleteReshipItems($reships) 
{
    $sql = 'DELETE FROM sdb_ome_reship_items WHERE reship_id in('.implode(',',$reships).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

function getReship($orders) 
{
    $sql = 'SELECT reship_id FROM sdb_ome_reship WHERE order_id in('.implode(',',$orders).')';
    $reships = $GLOBALS['db']->select($sql);
    if (!$reships) {
        return false;
    }
    return array_map('current',$reships);
}


//删除订单异常备注
function deleteAbnormalOrders($orders) 
{
    $sql = 'DELETE FROM sdb_ome_abnormal WHERE order_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除订单操作日志
function deleteOrderHistory($orders) 
{
    $sql = 'DELETE FROM sdb_ome_operation_log WHERE obj_type=\'orders@ome\' AND obj_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

//删除发货日志
function deleteDeliveryHistory($deliverys) 
{
    $sql = 'DELETE FROM sdb_ome_operation_log WHERE obj_type=\'delivery@ome\' AND obj_id in('.implode(',',$deliverys).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

// 删除第三方回写发货日志
function deletePartyDeliveryLog($orderBns) 
{
    $sql = 'DELETE FROM sdb_ome_shipment_log WHERE orderBn in (\''.implode('\',\'',$orderBns).'\')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

function deleteSellingAgent($orders) 
{
    $sql = 'DELETE FROM sdb_ome_order_selling_agent WHERE order_id in('.implode(',',$orders).')';
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

function optimize($table)
{
    $sql = 'OPTIMIZE TABLE '.$table;
    $GLOBALS['db']->exec($sql);
    ilog($sql);
}

/**
 * 日志
 */
function ilog($str) {
	
	global $domain;
	$filename = dirname(__FILE__) . '/../logs/update_' . date('Y-m-d') . '.log';
	$fp = fopen($filename, 'a');
//echo date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n";
	fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
	fclose($fp);
}
