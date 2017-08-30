<?php
/**
 * 修复 出入库单上的出入库价格iostock_price
 * 
 * @author yangminsheng@shopex.com
 * @version 1.0
 */

//php d:\wamp\www\prerelease20120904\script\update\script\recordStockCost.php 192.168.132.54/prerelease20120904 11:04

$domain = $argv[1];

$order_id = $argv[2];

$host_id = $argv[3];


if (empty($domain)) {

    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);


$Oiostock = app::get('ome')->model('iostock');

$offset = 0;

while(update_iostock($offset,$Oiostock)){
	$offset++;
}


function update_iostock($offset,$Oiostock){

    $pre_time = strtotime('2012-11-27');

	$last_time = time();
    
    $limit = 1000;

    $where = 'o.type_id = 3 and o.create_time >= '.$pre_time.' and o.create_time < '.$last_time;
    
    $sql = 'select o.iostock_price,did.price,o.original_item_id,o.original_id from sdb_ome_iostock o left join sdb_ome_delivery_items_detail did on o.original_item_id = did.item_detail_id where '.$where.' limit '.$offset*$limit.','.$limit;

    $delivery_items_detail = $Oiostock->db->select($sql);

	foreach ($delivery_items_detail as $k => $v) {

		$Oiostock->update(array('iostock_price'=>$v['price']),array('original_item_id'=>$v['original_item_id'],'original_id'=>$v['original_id']));

	}

	if (!$delivery_items_detail){
        return false;
	}else{
		unset($delivery_items_detail);
		return true;
	}
    
}




