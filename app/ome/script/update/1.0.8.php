<?php
//删除ome内置角色 
$sql_roles = "delete from sdb_desktop_roles where `role_id` in('8','9','10','11','13','16','17')";
$sql_hasroles = "delete from sdb_desktop_hasrole where `role_id` in('8','9','10','11','13','16','17')";
kernel::database()->exec($sql_roles);
kernel::database()->exec($sql_hasroles);
//将数据管理，缓存队列管理禁止掉
kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='permission' AND menu_title='数据管理'");
kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='permission' AND menu_title='缓存,队列管理'");
//将地区管理打开
kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='false',display='true' WHERE menu_type='permission' AND app_id='ectools' AND menu_title='地区管理'");


// 对涉及到出入库、销售单老数据恢复的数据表进行备份
echo "start backup data...\n";
$backup_tables = array(
    'purchase_eo','purchase_eo_items','purchase_returned_purchase','purchase_returned_purchase_items',
    'purchase_inventory','purchase_inventory_items','ome_reship','ome_reship_items',
    'ome_products','desktop_users','purchase_appropriation','purchase_appropriation_items',
    'ome_return_product','ome_return_product_items','ome_return_process_items','ome_return_process',
    'ome_orders','ome_order_objects','ome_order_items','ome_members',
    'ome_delivery','ome_delivery_items','ome_delivery_order','ome_payments',
    'ome_branch','ome_shop','purchase_supplier','purchase_po','purchase_po_items',
);
$indexs = array(
    'ome_payments' => array('pay_type'),
    'ome_delivery' => array('is_bind','process'),
    'ome_return_product' => array('status'),
);
$prefix = DB_PREFIX;
$backdata_flag = true;
foreach ($backup_tables as $k=>$v){
    $old_table_name = $prefix .$v;
    $new_table_name = preg_replace("/{$prefix}purchase|{$prefix}ome|{$prefix}desktop/", "{$prefix}bak_omeoldrecovery", $old_table_name);
    $sql = "CREATE TABLE {$new_table_name} like {$old_table_name}";
    if (kernel::database()->exec($sql)){
        // 增加索引
        if ($index = $indexs[$v]){
            foreach($index as $index_value){
                $index_sql = "CREATE INDEX ".$index_value." ON {$new_table_name} ($index_value)";
                kernel::database()->exec($index_sql);
            }
        }
        // 插入数据
        $sql = "INSERT INTO {$new_table_name} SELECT * FROM {$old_table_name}";
        if (!kernel::database()->exec($sql)){
            $backdata_flag = false;
            echo "backup data error:{$sql}\n";
        }
    }else{
        $backdata_flag = false;
        echo "create table structure fail:{$sql}\n";
    }
}

if ($backdata_flag == true){
    echo "backup data success!\n";
}

$shell->exec_command("install omecsv");
$shell->exec_command("install iostock");
$shell->exec_command("install sales");