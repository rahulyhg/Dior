#!/usr/bin/env php
<?php
define('ROOT_DIR',realpath(dirname(__FILE__).'/../../'));
define('APP_DIR',ROOT_DIR."/app/");

$server_name = $argv[1];
$_SERVER['SERVER_NAME'] = $server_name;


require(APP_DIR.'/base/kernel.php');
$shell = new base_shell_loader;


$shell->exec_command("kvstorerecovery");
$shell->exec_command("update");

$db = kernel::database();

$sql1 = "UPDATE sdb_desktop_menus SET disabled='true' WHERE menu_type='workground' AND app_id IN ('ectools','image')";
$sql2 = "UPDATE sdb_desktop_menus SET disabled='true' WHERE menu_type='permission' AND app_id IN ('ectools','image')";
$sql3 = "UPDATE sdb_desktop_menus SET disabled='true' WHERE menu_type='panelgroup' AND menu_title IN ('支付与货币','图片管理')";
$sql4 = "UPDATE sdb_desktop_menus SET disabled='true' WHERE menu_type='adminpanel' AND menu_title IN ('ectools_currency:货币管理','ectools_currency:运算设置','ectools_currency:支付方式管理','image_images:图片管理','image_images:图片配置','desktop_other:登陆认证设置','desktop_other:应用管理','image_images:商品图片配置','ectools_currency:商品精度设置')";

$db->exec($sql1);
$db->exec($sql2);
$db->exec($sql3);
$db->exec($sql4);

//内置taoguan角色
$roles = array(
    '接单员' => array('order_view','storage_stock_search'),      //角色名=>array('desktop.xml定义的permission','desktop.xml定义的permission')
    '订单调度员' => array('order_dispatch'),
    '订单确认员' => array('order_view','order_confirm'),
    '异常订单处理员' => array('order_abnormal'),
    '单据打印员' => array('process_receipts_print','process_product_refunded'),
    '出库校验员' => array('process_product_check'),
    '发货员' => array('process_consign'),
    '售后服务审核员' => array('aftersale_return_apply'),
    '退货收货员' => array('aftersale_sv_charge'),
    '退货质检员' => array('aftersale_sv_process'),
    '单据查看员' => array('invoice_order_payment','invoice_order_refund','invoice_delivery','invoice_reship','invoice_taoguanpurchase_payments','invoice_credit_sheet','invoice_taoguanpurchase_refunds','invoice_taoguanpurchase_payments_cancel','invoice_clearingtables','invoice_countertables'),
    '财务（订单）' => array('finance_payment_confirm','finance_refund_confirm'),
    '财务（采购）' => array('finance_taoguanpurchase_payments','finance_credit_sheet','finance_taoguanpurchase_refunds','finance_inventory_confirm'),
    '仓库管理员' => array('storage_stock_search','storage_stock','storage_branch_pos','storage_inventory_export','storage_inventory_import','storage_appropriation'),
    '商品管理员' => array('goods_view','goods_add','goods_type','goods_spec','goods_brand','goods_import'),
    '采购员' => array('taoguanpurchase_need','taoguanpurchase_po','taoguanpurchase_supplier'),
    '入库员' => array('taoguanpurchase_do_eo','taoguanpurchase_eo'),
);
$oRoles = &app::get('desktop')->model('roles');
$db = kernel::database();
foreach($roles as $key=>$role){
    $workground = array();
    foreach($role as $v){
        $workground[] = $v;
    }
    $data = array(
        'workground' => $workground,
    );
    $oRoles->update($data,array('role_name' => $key));
}

$desktop_user = new desktop_user();
$desktop_user->user_id = 1;
$workground = array('order_center','delivery_center','aftersale_center','finance_center','goods_manager','taoguanpurchase_manager','storage_center','report_center','invoice_center','setting_tools');
$desktop_user->set_conf('fav_menus',$workground);

//force to get license
base_certificate::register();

//force to get node_id
base_shopnode::register('taoguan');

$sql5 = "UPDATE sdb_base_network SET node_url='http://matrix.ecos.shopex.cn',node_api=''";
$db->exec($sql5);