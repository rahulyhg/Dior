<?php
/**
 * 爱推门数据还原
 * 
 * @author hzjsq@msn.com
 * @version 1.0
 */

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];
$delivery_bn = $argv[4];

if (empty($domain) || empty($order_id) || empty($host_id) || empty($delivery_bn)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

$deliModel = app::get('ome')->model('delivery');
$delivery = $deliModel->getList('*',array('delivery_bn'=>$delivery_bn,'verify'=>'true','process'=>'false'),0,1);
if(!$delivery) die('delivery is not exist!');
$delivery = $delivery[0];

# 出入库
$iostockModel = app::get('ome')->model('iostock');
$iostock = $iostockModel->getList('*',array('type_id'=>'3','original_bn'=>$delivery['delivery_bn'],'original_id'=>$delivery['delivery_id']));
$str = 'count:'.count($iostock);
$str .= var_export($iostock,true);
ilog($str);

$saleModel = app::get('ome')->model('sales');
$sales = $saleModel->getList('*',array('iostock_bn'=>$iostock[0]['iostock_bn']));
if($sales) die('sales has exist!');

$productModel = app::get('ome')->model('products');
foreach ($iostock as $key=>$value) {
    $product = $productModel->getList('product_id',array('bn'=>$value['bn']),0,1);
    if ($product) {
            $sql = 'update sdb_ome_branch_product set store=store+'.$value['nums'].',store_freeze=store_freeze+'.$value['nums'].' WHERE branch_id='.$value['branch_id'].' AND product_id='.$product[0]['product_id'];

            $productModel->db->exec($sql);

            $sql = 'update sdb_ome_products set store=store+'.$value['nums'].',store_freeze=store_freeze+'.$value['nums'].' WHERE product_id='.$product[0]['product_id'];
            $productModel->db->exec($sql);

            $sql = 'delete from sdb_ome_iostock WHERE iostock_id='.$value['iostock_id'].' limit 1';
            $productModel->db->exec($sql);
    }
}


/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/etm' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
