#!/usr/bin/env php
<?php
//更新脚本

if(isset($argv[1])){
    $server_name = $argv[1];
    $_SERVER['SERVER_NAME'] = $server_name;
}

$root_dir = realpath(dirname(__FILE__).'/../../');
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");
@include_once(APP_DIR.'/base/defined.php');

require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

cachemgr::init(false);

$get_order_sql = "SELECT o.order_id,i.product_id,i.nums,i.sendnum FROM sdb_taoguan_orders AS o 
                  LEFT JOIN sdb_taoguan_order_items AS i ON(o.order_id=i.order_id) 
                  WHERE o.process_status NOT IN('cancel','remain_cancel') AND o.ship_status in('0','2') AND o.status='active' AND i.delete='false'";
$orders = kernel::database()->select($get_order_sql);

$p_freeze = array();
foreach($orders as $order){
    $product_id = $order['product_id'];
    $freeze = $order['nums'] - $order['sendnum'];
    if(isset($p_freeze[$product_id])){
        $p_freeze[$product_id] += $freeze;
    }else{
        $p_freeze[$product_id] = $freeze;
    }
}
kernel::database()->exec("UPDATE sdb_taoguan_products SET store_freeze=0",true);

foreach($p_freeze as $product_id=>$store_freeze){
    $sql = "UPDATE sdb_taoguan_products SET store_freeze=".$store_freeze." WHERE product_id=".$product_id;
    kernel::database()->exec($sql,true);
}

    echo (isset($server_name)?$server_name:'')."success....\n";