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

$sql = "select d.delivery_id,bp.branch_id,bp.product_id,bp.store_freeze,di.number,sum(di.number) as _amount from sdb_taoguan_delivery  d
        join sdb_taoguan_delivery_items di on d.delivery_id = di.delivery_id
        join sdb_taoguan_branch_product bp on di.product_id = bp.product_id and bp.branch_id=d.branch_id
        where d.status not in('cancel','back','succ') and d.process='false' and d.parent_id=0 
        group by bp.branch_id,bp.product_id";
$orders = kernel::database()->select($sql);
kernel::database()->exec("UPDATE sdb_taoguan_branch_product SET store_freeze=0",true);
    $now = time();
    foreach($orders as $k=>$v){
        $store_freeze = "store_freeze=".$v['_amount'].",";
        $sql = "update sdb_taoguan_branch_product set ".$store_freeze."last_modified=".$now." where `branch_id` =".$v['branch_id']." and `product_id` =".$v['product_id'];
        kernel::database()->exec($sql,'true');
    }

    echo (isset($server_name)?$server_name:'')."success....\n";