<?php
/**
 * 重新向前端打创建发货单接口
 * 
 * @author chenping@shopex.cn
 * @version 1.0
 */
error_reporting(E_ALL ^ E_NOTICE);


$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];
$logi_no = $argv[4];
$add_deli = $argv[5];
if (empty($domain) || empty($order_id) || empty($host_id) || empty($logi_no)) {
    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$_SERVER['HTTP_HOST'] = $domain;
# 判断发货单是否存在
$deliveryModel = app::get('ome')->model('delivery');
$delivery = $deliveryModel->getList('*',array('logi_no'=>$logi_no,'process'=>'true'),0,1);
if(!$delivery) die('delivery is not exist!');
$delivery = $delivery[0];


$db = kernel::database();
# 处理多发现象 BEGIN
 // 发货单对应订单
 $deliOrder = app::get('ome')->model('delivery_order')->getList('order_id',array('delivery_id'=>$delivery['delivery_id']));
 foreach ($deliOrder as $key=>$value) {
    # 订单对应的销售单
    $sales = app::get('ome')->model('sales')->getList('*',array('order_id'=>$value['order_id']));
    if(count($sales)>1){
        # 弹出第一个
        array_shift($sales);
        
        foreach ($sales as $key=>$delSale) {
            if ($delSale) {
                $str = var_export($delSale,true);
                ilog($str);

                $db->exec('delete from sdb_ome_sales where sale_id="'.$delSale['sale_id'].'" limit 1');
                $sale_items = app::get('ome')->model('sales_items')->getList('*',array('sale_id'=>$delSale['sale_id']));
                $str = var_export($sale_items,true);
                ilog($str);

                $db->exec('delete from sdb_ome_sales_items where sale_id="'.$v['sale_id'].'" limit '.count($sale_items));
                
                $iostock = app::get('ome')->model('iostock')->getList('*',array('iostock_bn'=>$delSale['iostock_bn']));
                if ($iostock) {
                    $str = var_export($iostock,true);
                    ilog($str);
                    $db->exec('delete from sdb_ome_iostock where iostock_bn="'.$delSale['iostock_bn'].'"');
                }

            }
        }
    }

    # 修改订单状态
    $db->exec('update sdb_ome_orders set ship_status="1" WHERE ship_status="2" and order_id="'.$value['order_id'].'" limit 1');
    $order_items = app::get('ome')->model('order_items')->getList('*',array('order_id'=>$value['order_id']));
    foreach ($order_items  as $k=>$v) {
        if ($v['sendnum'] > $v['nums']) {
            $db->exec('update sdb_ome_order_items set sendnum=nums where item_id="'.$v['item_id'].'" limit 1');
        }
    }
 }

# 处理多发现象 END
if ($add_deli == true) {
    $sql = 'update sdb_ome_delivery set logi_no=NULL WHERE delivery_id='.$delivery['delivery_id'].' limit 1';
    $deliveryModel->db->exec($sql);
}

//发货单创建
foreach(kernel::servicelist('service.delivery') as $object=>$instance){
    if(method_exists($instance,'delivery')){
        $instance->delivery($delivery['delivery_id']);
    }
}

if ($add_deli == true) {
    $sql = 'update sdb_ome_delivery set logi_no="'.$logi_no.'" WHERE delivery_id='.$delivery['delivery_id'].' limit 1';
    $deliveryModel->db->exec($sql);
}

ilog("向前端打创建发货单接口,物流单号：".$logi_no);

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/requestDelivery' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
