<?php
/**
 * 修复数据，由于误删店铺，导致订单店铺字段显示乱码
 *
 * @author chenping@shopex.cn
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


$sql = "SELECT order_bn FROM sdb_ome_orders GROUP BY order_bn HAVING count(1) >1 ";


//获取异常的失败订单
$orderList = $db->select($sql);

ilog("删除订单:",var_export($orderList));

$i = 0;
foreach($orderList as $ord){
    $sql = 'SELECT * FROM sdb_ome_orders WHERE order_bn="'.$ord['order_bn'].'" AND ship_status="0" AND process_status in ("unconfirmed","confirmed") AND shop_id="7475532f33690855079befbce30b4603"';
    $row = $db->selectrow($sql);
    if ($row) {
         ilog("删除订单:",var_export($row,true));

        $db->exec("delete from sdb_ome_orders where order_id in (".$row['order_id'].")");

        $db->exec("delete from sdb_ome_order_objects where order_id in (".$row['order_id'].")");

        $db->exec("delete from sdb_ome_order_items where order_id in (".$row['order_id'].")");

        $db->exec("delete from sdb_ome_order_pmt where order_id in (".$row['order_id'].")");

        $db->exec("delete from sdb_ome_payments where order_id in (".$row['order_id'].")");
        
        $i++;
    }
}

ilog("总共删除:",$i);

$db->exec('update sdb_ome_orders set shop_id="96a3be3cf272e017046d1b2674a52bd3" WHERE shop_id="7475532f33690855079befbce30b4603" ');
$db->exec('update sdb_ome_shop set shop_id="96a3be3cf272e017046d1b2674a52bd3" WHERE shop_id="7475532f33690855079befbce30b4603" ');

/**
 * 日志
 */
function ilog($str,$delOrds) {
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/updateOrderShop_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\t" .$delOrds."\n");
    fclose($fp);
}
