<?php
/**
 +----------------------------------------------------------
 * 发货单状态回写列表
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2014-03-18 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class ome_finder_delivery_synclist
{
	var $detail_basic  = "发货单详情";
    function detail_basic($syn_id)
    {
    	$oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('delivery_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
        	return '';
        }
        $dly_id     = $row['delivery_id'];
        
        $render = app::get('ome')->render();
        $dlyObj = &app::get('ome')->model('delivery');
        $orderObj = &app::get('ome')->model('orders');
        $braObj = &app::get('ome')->model('branch');
        $opObj  = &app::get('ome')->model('operation_log');
        $dly = $dlyObj->dump($dly_id);
        $tmp = app::get('ome')->model('members')->dump($dly['member_id']);
        $dly['member_name'] = $tmp['account']['uname'];
        $dly['members'] = "手机：".$tmp['contact']['phone']['mobile']."<br>";
        $dly['members'] .= "电话：".$tmp['contact']['phone']['telephone']."<br>";
        empty($dly['branch_id'])?$branch_id=0:$branch_id=$dly['branch_id'];
        $shop = $dlyObj->getShopInfo($dly['shop_id']);
        $dly['area'] = $shop['area'];

        $orderIds = $dlyObj->getOrderIdByDeliveryId($dly_id);
        
        if ($orderIds)
        $ids = implode(',', $orderIds);
        if ($orderIds)
        foreach ($orderIds as $oid)
        {
            $order = $orderObj->dump($oid);
            $order_bn[] = $order['order_bn'];
        }

        /* 发货单日志 */
        $logdata = $opObj->read_log(array('obj_id'=>$dly_id,'obj_type'=>'delivery@ome'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }

        /* 同批处理的订单日志 */
        $order_ids = $dlyObj->getOrderIdByDeliveryId($dly_id);
        $orderLogs = array();
        foreach($order_ids as $v){
            $order = $orderObj->dump($v,'order_id,order_bn');
            $orderLogs[$order['order_bn']] = $opObj->read_log(array('obj_id'=>$v,'obj_type'=>'orders@ome'), 0, -1);
            foreach($orderLogs[$order['order_bn']] as $k=>$v){
                if($v)
                    $orderLogs[$order['order_bn']][$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
            }
        }

        $dlyorderObj = &app::get('ome')->model('delivery_order');
        #根据物流单号，获取会员备注与订单备注
        $markInfo = $dlyorderObj->getMarkInfo($dly_id);
        $custom_mark = array();#会员备注
        $mark_text = array();#订单备注
        foreach($markInfo as $key=>$v){
            $custom_mark[$v['order_bn']] = kernel::single('ome_func')->format_memo($v['custom_mark']);
            $mark_text[$v['order_bn']] = kernel::single('ome_func')->format_memo($v['mark_text']);
        
        }
        $render->pagedata['custom_mark'] = $custom_mark;#会员备注与订单备注信息
        $render->pagedata['mark_text'] = $mark_text;#会员备注与订单备注信息  
        $render->pagedata['write']    = $this->write;
        $render->pagedata['url']    = $this->url;
        $render->pagedata['log']      = $logdata;
        $render->pagedata['orderLogs'] = $orderLogs;
        $render->pagedata['dly_corp'] = $braObj->get_corp($branch_id,$dly['consignee']['area']);//$dlyObj->db->select($sql);
        $dly['create_time'] = date('Y-m-d H:i:s',$dly['create_time']);
        $render->pagedata['dly']      = $dly;
        $render->pagedata['order_bn'] = $order_bn;

        $render->pagedata['status'] = $_GET['status'];        

        return $render->fetch('admin/delivery/delivery_detail.html');
    }
    
    var $detail_item    = "货品详情";
    function detail_item($syn_id)
    {
    	$oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('delivery_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $dly_id     = $row['delivery_id'];
        
        $render = app::get('ome')->render();
        $dlyObj = &app::get('ome')->model('delivery');
        $pObj = &app::get('ome')->model('products');
        $items = $dlyObj->getItemsByDeliveryId($dly_id);
        
        /*获取货品优惠金额*/
        $dlyorderObj = &app::get('ome')->model('delivery_order');
        $dly_order = $dlyorderObj->getlist('*',array('delivery_id'=>$dly_id),0,-1);

        $pmt_orders = $dlyObj->getPmt_price($dly_order);
        $sale_orders = $dlyObj->getsale_price($dly_order);

        $pmt_order = array();
        
        $delivery = $dlyObj->dump($dly_id);
        if ($items)
        foreach ($items as $key => $item)
        {
            //将商品的显示名称改为后台的显示名称
            $productInfo= $pObj->getList('name,spec_info',array('bn'=>$items[$key]['bn']));
            //$item_pos = $dlyObj->getItemPosByItemId($item['item_id']);
            $items[$key]['spec_info'] = $productInfo[0]['spec_info'];
            $items[$key]['product_name'] = $productInfo[0]['name'];
            $items[$key]['pmt_price'] = $pmt_order[$items[$key]['bn']]['pmt_price'];
            $items[$key]['sale_price'] = ($sale_orders[$items[$key]['bn']]*$item['number'])-$pmt_order[$items[$key]['bn']]['pmt_price'];

            $items[$key]['price'] = $sale_orders[$items[$key]['bn']];

        }
        $render->pagedata['write'] = $this->write;
        $render->pagedata['items'] = $items;
        $render->pagedata['dly']   = $delivery;

        return $render->fetch('admin/delivery/delivery_item.html');
    }
    
    var $detail_delivery    = "物流单列表";
    function detail_delivery($syn_id)
    {
        $oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('delivery_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $dly_id     = $row['delivery_id'];
        
        $dlyObj = &app::get('ome')->model('delivery');
        $dlyChildObj = &app::get('ome')->model('delivery_bill');
        $opObj = &app::get('ome')->model('operation_log');
        if(!empty($_POST)){
            $billarr =  $_POST["dlylist"];
            foreach($billarr as $k=>$v){
                $v = trim($v);
                if ($dlyObj->existExpressNoBill($v, $_POST['delivery_id'],$k)){
                    echo '<script>alert("已有此物流单号:'.$v.'")</script>'; break;
                }else{
                    # 判断此物流单号是否在主发货单中已经存在
                    $exist = $dlyObj->getList('delivery_id',array('logi_no'=>$v),0,1);
                    if ($exist) {
                        echo '<script>alert("已有此物流单号:'.$v.'")</script>'; break;
                    }

                    $billdata = array('logi_no'=>$v,);
                    $billfilter = array('log_id' => $k,);
                    $dlybillinfo = $dlyChildObj->dump(array('log_id'=>$k,'logi_no'=>$v));
                    if(!$dlybillinfo){
                        $dlybillinfoget = $dlyChildObj->dump(array('log_id'=>$k));

                        if(empty($dlybillinfoget['logi_no'])){
                            $logstr = '录入快递单号:'.$v;
                            $opObj->write_log('delivery_bill_add@ome', $dly_id, $logstr);
                        }else{
                            $logstr = '修改快递单号:'.$dlybillinfoget['logi_no'].'->'.$v;
                            $opObj->write_log('delivery_bill_modify@ome', $dly_id, $logstr);
                        }
                        $dlyChildObj->update($billdata,$billfilter);
                    }
                }
            }
        }
        $render = app::get('ome')->render();

        $braObj = &app::get('ome')->model('branch');


        $dly = $dlyObj->dump($dly_id);
        $delivery = $dlyObj->dump($dly_id);
        empty($dly['branch_id'])?$branch_id=0:$branch_id=$dly['branch_id'];
        $dlyChildList = $dlyChildObj->getList('*',array('delivery_id'=>$dly_id),0,-1);

        $render->pagedata['dlyChildListCount'] = count($dlyChildList);
        $render->pagedata['dlyChildList'] = $dlyChildList;
        $render->pagedata['dly_corp'] = $braObj->get_corp($branch_id,$dly['consignee']['area']);
        $render->pagedata['dly']   = $delivery;
        $render->pagedata['write'] = $this->write;
        
        return $render->fetch('admin/delivery/delivery_list.html');
    }
	
    var $detail_shipment    = '发货日志';
    function detail_shipment($syn_id)
    {
        $oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('order_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $order_id     = $row['order_id'];
        
        $render = app::get('ome')->render();
        $orderObj = &app::get('ome')->model('orders');
        $shipmentObj = & app::get('ome')->model('shipment_log');
        $userObj = app::get('desktop')->model('users');

        $order = $orderObj->dump($order_id);
        if ($order) {

            $orderBn = $order['order_bn'];
            $shipmentLogs = $shipmentObj->getList('*', array('orderBn' => $orderBn));
            foreach ($shipmentLogs as $k=>$log) {
                if ($shipmentLogs[$k]['receiveTime']) {
                    $shipmentLogs[$k]['receiveTime'] = date('Y-m-d H:i:s', $shipmentLogs[$k]['receiveTime']);
                } else {
                    $shipmentLogs[$k]['receiveTime'] = '&nbsp;';
                }
                if ($shipmentLogs[$k]['updateTime']) {
                    $shipmentLogs[$k]['updateTime'] = date('Y-m-d H:i:s', $shipmentLogs[$k]['updateTime']);
                } else {
                    $shipmentLogs[$k]['updateTime'] = '&nbsp;';
                }
                switch ($shipmentLogs[$k]['status']) {
                    case 'succ':
                        $shipmentLogs[$k]['status'] = '<font color="green">成功</font>';
                        break;
                    case 'fail':
                        $shipmentLogs[$k]['status'] = '<font color="red">失败</font>';
                        break;
                    default:
                        $shipmentLogs[$k]['status'] = '<font color="#000">运行中……</font>';
                        break;
                }

                if($log['ownerId'] == 16777215){
                    $shipmentLogs[$k]['ownerId'] = 'system';
                }else{
                    $user = $userObj->dump($log['ownerId'],'name');
                    $shipmentLogs[$k]['ownerId'] = $user['name'];
                }
            }
            $render->pagedata['order'] = $order;
            $render->pagedata['shipmentLogs'] = $shipmentLogs;
        }

        return $render->fetch('admin/order/detail_shipment.html');
    }
    
    var $detail_order = '订单基本信息';
    function detail_order($syn_id)
    {
        $oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('order_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $order_id     = $row['order_id'];
        
        $render = app::get('ome')->render();
        $oOrders = &app::get('ome')->model('orders');
        $oOperation_log = &app::get('ome')->model('operation_log');

        if($_POST){
            if($_POST['is_flag']){
                $data['order_id'] = $_POST['order_id'];
                $data['tax_no'] = $_POST['tax_no'];
                //新增是否开发票和发票抬头的修改 2012-7-19
                $data['is_tax'] = $_POST['is_tax'];
                $data['tax_title'] = $_POST['tax_title'];
                $oOrders->save($data);
                
                //更新开票订单 ExBOY 2014.04.08
                if(app::get('invoice')->is_installed())
                {
                    $Invoice       = &app::get('invoice')->model('order');
                    $Invoice->update_order($data);
                }

                //TODO:api，发票号的回写
                $oOperation_log->write_log('order_modify@ome',$_POST['order_id'],'录入及变更发票号');
            }else{
                $order_id = $_POST['order']['order_id'];

                $memo = "";
                if(isset($_POST['order_action'])){
                    switch($_POST['order_action']){
                        case "cancel" :
                            $memo = "订单被取消";

                            //TODO: 订单取消作为单独的日志记录
                            $oOrders->unfreez($order_id);
                            $oOrders->cancel_delivery($order_id);
                            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                            break;
                        case "order_limit_time" :
                            $plainData = $_POST['order'];
                            $plainData['order_limit_time'] = strtotime($plainData['order_limit_time']);
                            $oOrders->save($plainData);

                            $memo = "订单的有效时间被设置为".date("Y-m-d",$plainData['order_limit_time']);
                            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                            break;
                        case "order_payed" :
                            $memo = "确认订单付款";
                            $orderinfo = $oOrders->order_detail($order_id);
                            if ($orderinfo['payed'] == $orderinfo['total_amount'])
                            {
                                $plainData['pay_status'] = 1;
                                $oOrders->save($plainData);
                                $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                            }
                            break;
                        case "order_pause":
                            $memo = "订单暂停";
                            $oOrders->pauseOrder($order_id);
                            break;
                        case "order_renew":
                            $memo = "订单恢复";
                            $oOrders->renewOrder($order_id);
                            break;
                        default:
                            $memo = "订单内容修改";
                            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                            break;
                    }
                }else{
                    $memo = "订单内容修改";
                    $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
                }

            }

            //写操作日志
        }
        $order_detail = $oOrders->dump($order_id,"*",array("order_items"=>array("*")));
        $oRefund = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund->getList('*',array('order_id'=>$order_id),0,-1);
        $amount = 0;
        foreach ($refunddata as $row){
            if ($row['status'] != '3' && $row['status'] != '4'){
                $render->pagedata['isrefund'] = 'false';//如果退款申请没有处理完成
            }
        }
        if ($render->pagedata['isrefund'] == ''){
            if ($order_detail['pay_status'] == '5'){
                $render->pagedata['isrefund'] = 'false';//订单已全额退货
            }
        }
        $render->pagedata['is_c2cshop'] = in_array($order_detail['shop_type'],ome_shop_type::shop_list()) ?true:false;
        $render->pagedata['shop_name'] = ome_shop_type::shop_name($order_detail['shop_type']);
        $order_detail['mark_text'] = kernel::single('ome_func')->format_memo($order_detail['mark_text']);
        $order_detail['custom_mark'] = kernel::single('ome_func')->format_memo($order_detail['custom_mark']);
        $render->pagedata['total_amount'] = floatval($order_detail['total_amount']);
        $render->pagedata['payed'] = floatval($order_detail['payed']);
        $oMembers = &app::get('ome')->model('members');
        $member_id = $order_detail['member_id'];
        $render->pagedata['member'] = $oMembers->dump($member_id);
        $render->pagedata['url'] = kernel::base_url()."/app/".$render->app->app_id;


        //订单代销人会员信息
        $oSellagent = &app::get('ome')->model('order_selling_agent');
        $sellagent_detail = $oSellagent->dump(array('order_id'=>$order_id));
        if (!empty($sellagent_detail['member_info']['uname'])){
            $render->pagedata['sellagent'] = $sellagent_detail;
        }
        //发货人信息
        $order_consigner = false;
        if ($order_detail['consigner']){
            foreach ($order_detail['consigner'] as $shipper){
                if (!empty($shipper)){
                    $order_consigner = true;
                    break;
                }
            }
        }
        if ($order_consigner == false){
            //读取店铺发货人信息
            $oShop = &app::get('ome')->model('shop');
            $shop_detail = $oShop->dump(array('shop_id'=>$order_detail['shop_id']));
            $order_detail['consigner'] = array(
                'name' => $shop_detail['default_sender'],
                'mobile' => $shop_detail['mobile'],
                'tel' => $shop_detail['tel'],
                'zip' => $shop_detail['zip'],
                'email' => $shop_detail['email'],
                'area' => $shop_detail['area'],
                'addr' => $shop_detail['addr'],
            );
        }
        $sh_base_url = kernel::base_url(1);
        $render->pagedata['base_url'] = $sh_base_url;


        $is_edit_view = 'true';//
        if ($order_add_service = kernel::service('service.order.'.$order_detail['shop_type'])){
            if (method_exists($order_add_service, 'is_edit_view')){
                $order_add_service->is_edit_view($order_detail, $is_edit_view);
            }
        }

        if($order_detail['shipping']['is_cod'] == 'true'){
            $orderExtendObj = &app::get('ome')->model('order_extend');
            $extendInfo = $orderExtendObj->dump($order_id);
            $order_detail['receivable'] = $extendInfo['receivable'];
        }

        $render->pagedata['is_edit_view'] = $is_edit_view;
        $render->pagedata['order'] = $order_detail;
        if(in_array($_GET['act'],array('confirm','abnormal'))){
            $render->pagedata['operate'] = true;
            $render->pagedata['act_'.$_GET['act']] = true;
        }
        if(($_GET['act'] == 'dispatch' && $_GET['flt'] == 'buffer') || ($_GET['ctl'] == 'admin_order' && ($_GET['act'] == 'active' || $_GET['act'] == 'index'))){
            $render->pagedata['operate'] = true;
            $render->pagedata['act_confirm'] = true;
        }

        return $render->fetch('admin/order/detail_basic.html');
    }
    
    var $detail_goods   = '订单明细';
    function detail_goods($syn_id)
    {
        $oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('order_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $order_id     = $row['order_id'];
        
        $render = app::get('ome')->render();
        $oOrder = &app::get('ome')->model('orders');

        $item_list = $oOrder->getItemList($order_id,true);
        $item_list = ome_order_func::add_getItemList_colum($item_list);
        ome_order_func::order_sdf_extend($item_list);
        $orders = $oOrder->getRow(array('order_id'=>$order_id),'shop_type,order_source');
        $is_consign = false;
        #淘宝代销订单增加代销价
        if($orders['shop_type'] == 'taobao' && $orders['order_source'] == 'tbdx' ){
            kernel::single('ome_service_c2c_taobao_order')->order_sdf_extend($item_list);
            $is_consign = true;
        }

        $configlist = array();
        if ($servicelist = kernel::servicelist('ome.service.order.products'))
        foreach ($servicelist as $object => $instance){
            if (method_exists($instance, 'view_list')){
                $list = $instance->view_list();
                $configlist = array_merge($configlist, is_array($list) ? $list : array());
            }
        }

        $render->pagedata['is_consign'] = ($is_consign > 0)?true:false;
        $render->pagedata['configlist'] = $configlist;
        $render->pagedata['item_list'] = $item_list;
        $render->pagedata['object_alias'] = $oOrder->getOrderObjectAlias($order_id);
        return $render->fetch('admin/order/detail_goods.html');
    }
    
    var $detail_bill    = '收退款记录';
    function detail_bill($syn_id)
    {
        $oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('order_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $order_id     = $row['order_id'];
        
        $render = app::get('ome')->render();
        $oPayments = &app::get('ome')->model('payments');
        $oRefunds = &app::get('ome')->model('refunds');

        $payments = $oPayments->getList('payment_id,payment_bn,t_begin,download_time,money,paymethod',array('order_id'=>$order_id));
        $refunds = $oRefunds->getList('refund_bn,t_ready,download_time,money,paymethod,payment',array('order_id'=>$order_id));
        
        $paymentCfgModel = app::get('ome')->model('payment_cfg');
        foreach ($refunds as $key=>$refund) {
            if ($refund['paymethod']) {
                $paymentCfg = $paymentCfgModel->getList('custom_name',array('id'=>$refund['payment']),0,1);
                $refunds[$key]['paymethod'] = $paymentCfg[0]['custom_name'] ? $paymentCfg[0]['custom_name'] : '';
            }
        }

        foreach($payments as $k=>$v){
            $payments[$k]['t_begin'] = date('Y-m-d H:i:s',$v['t_begin']);
            if($v['download_time']) $payments[$k]['download_time'] = date('Y-m-d H:i:s',$v['download_time']);
        }

        $render->pagedata['payments'] = $payments;
        $render->pagedata['refunds'] = $refunds;

        return $render->fetch('admin/order/detail_bill.html');
    }
    
    var $detail_refund_apply    = '退款申请记录';
    function detail_refund_apply($syn_id)
    {
        $oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('order_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $order_id     = $row['order_id'];
        
        $render = app::get('ome')->render();
        $oRefund_apply = &app::get('ome')->model('refund_apply');

        $refund_apply = $oRefund_apply->getList('create_time,status,money,refund_apply_bn,refunded',array('order_id'=>$order_id));
        if($refund_apply){
            foreach($refund_apply as $k=>$v){
                $refund_apply[$k]['status_text'] = ome_refund_func::refund_apply_status_name($v['status']);
            }
        }

        $render->pagedata['refund_apply'] = $refund_apply;

        return $render->fetch('admin/order/detail_refund_apply.html');
    }
    
    var $detail_deliverylist    = '退发货记录';
    function detail_deliverylist($syn_id)
    {
        $oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('order_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $order_id     = $row['order_id'];
        
        $render = app::get('ome')->render();
        $oDelivery = &app::get('ome')->model('delivery');
        $oReship = &app::get('ome')->model('reship');

        $delivery = $oDelivery->getDeliveryByOrder('create_time,delivery_id,delivery_bn,logi_id,logi_no,logi_name,ship_name,delivery,branch_id,stock_status,deliv_status,expre_status,status,weight',$order_id);
        $reship = $oReship->getList('t_begin,reship_id,reship_bn,logi_no,ship_name,delivery',array('order_id'=>$order_id));

        foreach($delivery as $k=>$v){
            $delivery[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
        }

        $render->pagedata['delivery'] = $delivery;
        $render->pagedata['reship'] = $reship;

        return $render->fetch('admin/order/detail_delivery.html');
    }
    
    var $detail_history     = '订单操作记录';
    function detail_history($syn_id)
    {
        $oSync  = &app::get('ome')->model('delivery_sync');
        $row    = $oSync->getList('order_id', array('syn_id' => $syn_id), 0, 1);
        $row    = $row[0];
        if(empty($row))
        {
            return '';
        }
        $order_id     = $row['order_id'];
        
        $render = app::get('ome')->render();
        $orderObj = &app::get('ome')->model('orders');
        $logObj = &app::get('ome')->model('operation_log');
        $deliveryObj = &app::get('ome')->model('delivery');
        $ooObj = &app::get('ome')->model('operations_order');

        /* 本订单日志 */
        $history = $logObj->read_log(array('obj_id'=>$order_id,'obj_type'=>'orders@ome'),0,-1);
        foreach($history as $k=>$v){
            $data = $ooObj->getList('operation_id',array('log_id'=>$v['log_id']));
            if(!empty($data)){
                $history[$k]['flag'] ='true';
            }else{
                $history[$k]['flag'] ='false';
            }
            $history[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }

        /* 发货单日志 */
        $delivery_ids = $deliveryObj->getDeliverIdByOrderId($order_id);
        $deliverylog = $logObj->read_log(array('obj_id'=>$delivery_ids,'obj_type'=>'delivery@ome'), 0, -1);
        foreach($deliverylog as $k=>$v){
            $deliverylog[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }

        /* “失败”、“取消”、“打回”发货单日志 */
        $history_ids = $deliveryObj->getHistoryIdByOrderId($order_id);
        $deliveryHistorylog = array();
        foreach($history_ids as $v){
            $delivery = $deliveryObj->dump($v,'delivery_id,delivery_bn');
            $deliveryHistorylog[$delivery['delivery_bn']] = $logObj->read_log(array('obj_id'=>$v,'obj_type'=>'delivery@ome'), 0, -1);
            foreach($deliveryHistorylog[$delivery['delivery_bn']] as $k=>$v){
                $deliveryHistorylog[$delivery['delivery_bn']][$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
            }
        }

        /* 同批处理的订单日志 */
        $order_ids = $deliveryObj->getOrderIdByDeliveryId($delivery_ids);
        $orderLogs = array();
        foreach($order_ids as $v){
            if($v != $order_id){
                $order = $orderObj->dump($v,'order_id,order_bn');
                $orderLogs[$order['order_bn']] = $logObj->read_log(array('obj_id'=>$v,'obj_type'=>'orders@ome'), 0, -1);
                foreach($orderLogs[$order['order_bn']] as $k=>$v){
                    if($v)
                        $orderLogs[$order['order_bn']][$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
                }
            }
        }

        $render->pagedata['history'] = $history;
        $render->pagedata['deliverylog'] = $deliverylog;
        $render->pagedata['deliveryHistorylog'] = $deliveryHistorylog;
        $render->pagedata['orderLogs'] = $orderLogs;
        $render->pagedata['order_id'] = $order_id;


        return $render->fetch('admin/order/detail_history.html');
    }
    
    /*------------------------------------------------------ */
    //-- 显示行样式
    /*------------------------------------------------------ */
    function row_style($row)
    {
        $style = '';
        if($row['sync'] == 'succ')
        {
           $style .= ' list-even ';
        }
        elseif($row['sync'] == 'fail')
        {
            $style .= ' selected ';
        }
        elseif($row['sync'] == 'run')
        {
            $style .= ' highlight-row ';
        }
        
        return $style;
    }
}