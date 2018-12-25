<?php
/**
 * Created by PhpStorm.
 * User: Jinxing.zhou
 * Date: 2018/10/23
 * Time: 14:28
 */

$root_dir = realpath(dirname(__FILE__).'/../../../');//echo $root_dir;exit();
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");
require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}
require_once(APP_DIR.'/base/defined.php');
cachemgr::init(false);
echo "begin <br/>";
//发货SO文件
$objOrder = app::get('ome')->model('orders');

$order_info = $objOrder->db->select("select  sdb_ome_reship.order_id,delivery_id from sdb_ome_reship left join sdb_ome_delivery_order on sdb_ome_delivery_order.order_id=sdb_ome_reship.order_id
where is_check='7' and t_end>UNIX_TIMESTAMP('2018-12-10') and t_end<UNIX_TIMESTAMP('2018-12-10 19:05:00')");

foreach($order_info as $order){
	kernel::single('omeftp_service_reship')->delivery($order['delivery_id'],$order['order_id']);
}


echo "<br/> end";