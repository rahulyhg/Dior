<?php
/**
 * 修复支付单上店铺来源是空的情况
 *
 * @author yangminsheng
 * @version 1.0
 * @param $argv[1] 域名
 */
error_reporting(E_ALL ^ E_NOTICE);
$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];

if (empty($domain) || empty($order_id) || empty($host_id)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

function FixPaymentShop(){
   $db = kernel::database();
   $sql = 'select p.order_id,o.shop_id,p.payment_id from `sdb_ome_payments` p left join `sdb_ome_orders` o on o.order_id = p.order_id where p.shop_id IS NULL or p.shop_id = ""';
   $fixpayment = $db->select($sql);
   foreach($fixpayment as $k=>$v){

      $must_fix = 'update `sdb_ome_payments` set shop_id = "'.$v['shop_id'].'" where payment_id = '.$v['payment_id'];
      $result = @$db->exec($must_fix);
      if(!$result){
      	$del_sql = 'delete from `sdb_ome_payments` where payment_id = '.$v['payment_id'];
      	$db->exec($del_sql);
      }
   }
}

FixPaymentShop();
