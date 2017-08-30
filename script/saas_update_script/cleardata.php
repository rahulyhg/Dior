#!/usr/bin/env php
<?php
//由于部分站点使用了其他客户的体验数据，现在需要清除
//留了商品、店铺、快递单模板、仓库、确认小组、以及一些初始数据没有清除

define('ROOT_DIR',realpath(dirname(__FILE__).'/../../'));
define('APP_DIR',ROOT_DIR."/app/");

if(isset($argv[1])){
    $server_name = $argv[1];
    $_SERVER['SERVER_NAME'] = $server_name;
}else{
    $server_name = "";
}


require(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

$shell = new base_shell_loader;

$db = kernel::database();

$tables = array(
    'sdb_taoguan_abnormal',
    'sdb_taoguan_abnormal_type',
    'sdb_taoguan_api_log',
    'sdb_taoguan_branch_groups',
    'sdb_taoguan_branch_pos',
    'sdb_taoguan_branch_product',
    'sdb_taoguan_branch_product_pos',
    'sdb_taoguan_delivery',
    'sdb_taoguan_delivery_items',
    'sdb_taoguan_delivery_order',
    'sdb_taoguan_delivery_return',
    'sdb_taoguan_dly_corp',
    'sdb_taoguan_dly_corp_area',
    'sdb_taoguan_dly_items_pos',
    'sdb_taoguan_members',
    'sdb_taoguan_operation_log',
    'sdb_taoguan_order_items',
    'sdb_taoguan_order_objects',
    'sdb_taoguan_order_pmt',
    'sdb_taoguan_order_relations',
    'sdb_taoguan_orders',
    'sdb_taoguan_payment_cfg',
    'sdb_taoguan_payments',
    'sdb_taoguan_refund_apply',
    'sdb_taoguan_refunds',
    'sdb_taoguan_reship',
    'sdb_taoguan_reship_items',
    'sdb_taoguan_return_process',
    'sdb_taoguan_return_process_items',
    'sdb_taoguan_return_product',
    'sdb_taoguan_return_product_items',
    'sdb_taoguan_return_product_problem',
    'sdb_taoguan_return_product_problem_type',
    'sdb_taoguan_return_refund_apply',
    'sdb_taoguan_shop_members',
    'sdb_taoguan_stock_change_log',
    'sdb_taoguanauto_autobind',
    'sdb_taoguanauto_autoconfirm',
    'sdb_taoguanauto_autodispatch',
    'sdb_taoguankpi_deliverier_invoice',
    'sdb_taoguankpi_deliverier_log',
    'sdb_taoguankpi_employee',
    'sdb_taoguankpi_packager_invoice',
    'sdb_taoguankpi_packager_log',
    'sdb_taoguankpi_pickinger_invoice',
    'sdb_taoguankpi_pickinger_log',
    'sdb_taoguankpi_servicer_invoice',
    'sdb_taoguankpi_servicer_log',
    'sdb_taoguanpkg_pkg_goods',
    'sdb_taoguanpkg_pkg_product',
    'sdb_taoguanpurchase_appropriation',
    'sdb_taoguanpurchase_appropriation_items',
    'sdb_taoguanpurchase_branch_product_batch',
    'sdb_taoguanpurchase_credit_sheet',
    'sdb_taoguanpurchase_eo',
    'sdb_taoguanpurchase_eo_items',
    'sdb_taoguanpurchase_inventory',
    'sdb_taoguanpurchase_inventory_filter',
    'sdb_taoguanpurchase_inventory_items',
    'sdb_taoguanpurchase_po',
    'sdb_taoguanpurchase_po_items',
    'sdb_taoguanpurchase_taoguanpurchase_payments',
    'sdb_taoguanpurchase_taoguanpurchase_refunds',
    'sdb_taoguanpurchase_returned_taoguanpurchase',
    'sdb_taoguanpurchase_returned_taoguanpurchase_items',
    'sdb_taoguanpurchase_statement',
    'sdb_taoguanpurchase_supplier',
    'sdb_taoguanpurchase_supplier_brand',
    'sdb_taoguanpurchase_supplier_goods',
);

echo "start to clear date.....".$server_name."\n";
foreach($tables as $table){
    $sql = "DELETE FROM ".$table;
    $db->exec($sql);
}
echo "clear date ok!\n";