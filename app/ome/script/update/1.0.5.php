<?php
//计算branch_product的冻结库存
$sql = "select d.delivery_id,bp.branch_id,bp.product_id,bp.store_freeze,sum(di.number) as _amount from sdb_ome_delivery  d
        join sdb_ome_delivery_items di on d.delivery_id = di.delivery_id
        join sdb_ome_branch_product bp on bp.product_id = di.product_id and bp.branch_id=d.branch_id
        where d.status not in('cancel','back','succ','return_back') and d.process='false' and d.parent_id = 0 
        group by bp.branch_id,bp.product_id";
$orders = kernel::database()->select($sql);
kernel::database()->exec("UPDATE sdb_ome_branch_product SET store_freeze=0",true);
    $now = time();
    foreach($orders as $k=>$v){
    	$store_freeze = "store_freeze=".$v['_amount'].",";
        $sql = "update sdb_ome_branch_product set ".$store_freeze."last_modified=".$now." where `branch_id` =".$v['branch_id']." and `product_id` =".$v['product_id'];
        kernel::database()->exec($sql,'true');
    }

//计算product的冻结库存
$get_order_sql = "SELECT o.order_id,i.product_id,i.nums,i.sendnum FROM sdb_ome_orders AS o 
                  LEFT JOIN sdb_ome_order_items AS i ON(o.order_id=i.order_id) 
                  WHERE o.process_status NOT IN('cancel','remain_cancel') AND o.ship_status in (0,2) AND o.status='active' AND i.delete='false'";
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
kernel::database()->exec("UPDATE sdb_ome_products SET store_freeze=0",true);

foreach($p_freeze as $product_id=>$store_freeze){
    $sql = "UPDATE sdb_ome_products SET store_freeze=".$store_freeze." WHERE product_id=".$product_id;
    kernel::database()->exec($sql,true);
}



//set product_id
$depoly_info = base_setup_config::deploy_info();
$product_id = $depoly_info['product_id'];
app::get('desktop')->setConf('product_id',$product_id);