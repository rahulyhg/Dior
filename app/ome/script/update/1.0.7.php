<?php
// 删除支付方式编号为空的支付方式记录
kernel::database()->exec("DELETE FROM `sdb_ome_payment_cfg` WHERE `pay_bn` IS NULL OR `pay_bn`=''");

/* 同步前段店铺支付方式 */
$shopObj = &app::get('ome')->model('shop');
$shopList = $shopObj->getList('shop_id');
foreach($shopList as $shop){
    kernel::single("ome_payment_func")->sync_payments($shop['shop_id']);
}

/* “失败订单”修改之后，对old数据的处理 */
$orderObj = &app::get('omeapilog')->model('orders');
$omeOrderObj = &app::get('ome')->model('orders');
$orderList = $orderObj->getList('order_id');
foreach($orderList as $order){
    $orderInfo = $orderObj->dump($order['order_id'],'*:order_id',array('order_objects'=>array('*:obj_id,order_id',array('order_items'=>array('*:item_id,obj_id,order_id'))) ));

    $addon = unserialize($orderInfo['addon']);
    unset($orderInfo['addon']);
    
    //创建OME订单
    $orderInfo['is_fail'] = 'true';
    $orderInfo['edit_status'] = 'true';
    $omeOrderObj->create_order($orderInfo);

    //创建支付单
    $payment_sdf = $addon['payment_sdf'];
    if(!empty($payment_sdf)){
        $payment_sdf['shop_id'] = $order['shop_id'];
        $responseObj = '';
        kernel::single("ome_rpc_response_payment")->add($payment_sdf, $responseObj);
    }

    //订单优惠方案
    $pmt_detail = $addon['pmt_detail'];
    if(!empty($pmt_detail)){
        kernel::single('ome_order_rpc_response_order')->add_order_pmt($order['order_id'], $pmt_detail);
    }
    
    //代销人会员信息
    $selling_agent = $addon['selling_agent'];
    if(!empty($selling_agent)){
        kernel::single('ome_order_rpc_response_order')->update_selling_agent_info($order['order_id'], $selling_agent);
    }

    //删除异常订单
    $orderObj->delete(array('order_id'=>$order_id));
}


/* 发货单增加发货时间 */
$sql = 'UPDATE sdb_ome_delivery SET delivery_time=`last_modified` WHERE process=\'true\'';
kernel::database()->exec($sql);

/* 菜单优化 */
$desktop_user = kernel::single('desktop_user');
$desktop_user->get_conf('fav_menus',$fav_menus);
$delMenu = array('purchase_manager','aftersale_center','report_center');
if($fav_menus){
    foreach((array)$fav_menus as $key=>$menu){
        if(in_array($menu,$delMenu)){
            unset($fav_menus[$key]);
        }
    }
    $desktop_user->set_conf('fav_menus',$fav_menus);
}

//初始化iostock_type数据
$init_sdf = app::get('ome')->app_dir . '/initial/ome.iostock_type.sdf';
kernel::single('base_initial','ome')->init_sdf('ome','iostock_type',$init_sdf);