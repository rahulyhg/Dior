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

$order_info = $objOrder->db->select("select reship_id,max(delivery_id) as delivery_id from sdb_ome_reship left join sdb_ome_delivery_order  on sdb_ome_delivery_order.order_id=sdb_ome_reship.order_id
where reship_bn in('201811041513005409','201811041513003927','201811041513007779','201811041513004306','201811041513001975','201811041513002743','201811041513008928','201811041513001936','201811041513007926','201811041513001640','201811041513002345','201811041513004934','201811041513004694','201811041513000118','201811041513007262','201811041513008853','201811041513003380','201811041513006067','201811041413001336','201811040913006471','201811040913006978','201811040913000216','201811040913007593','201811040913001743','201811040913008658','201811040913005942','201811040913004872','201811040913000984','201811040913002977','201811040913004038','201811022113006656','201811022113002189','201811022113001657','201811022113000291','201811022113001404','201811022113007051','201811022113002540','201811022113004408','201811022113000860','201811022113009830','201811022113006938','201811022113003226','201810312113002612','201810312113003797','201810312113005972','201810311813007054','201810311813003610','201810311813009649','201810311813007105','201810311813003980','201810311613008650','201810311313009577','201810311313003732','201810302013005294','201810301813000313','201810301813006972','201810301713003845','201810301713006979','201810301713006203','201810292013003523','201810291813007283','201810291713006465','201810291113003854','201810281713005961','201810281713000034','201810281713000593','201810281613007124')
group by reship_id");

foreach($order_info as $order){
	
	echo "<pre>";print_r($order);exit;
	kernel::single('omeftp_service_reship')->delivery($order['delivery_id'],$order['reship_id']);
	
}


echo "<br/> end";