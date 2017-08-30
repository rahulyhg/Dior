<?php
class archive_finder_orders{
    var $detail_basic = '基本信息';
    var $detail_goods = '订单明细';
    var $detail_bill = '收退款记录';
    var $detail_delivery = '退发货记录';
    var $detail_history = '订单操作记录';
    var $detail_abnormal = '订单异常备注';
  
    var $detail_refund_apply = '退款申请记录';
 

    var $addon_cols = "custom_mark,mark_text";

    var $column_custom_add='买家备注';
    var $column_custom_add_width = "100";
    function column_custom_add($row){
        $custom_mark = $row[$this->col_prefix.'custom_mark'];
        $custom_mark = kernel::single('ome_func')->format_memo($custom_mark);
        foreach ((array)$custom_mark as $k=>$v){
        	$html .= $v['op_content'].' '.$v['op_time'].' by '.$v['op_name']."<br />";
        }
        $html = strip_tags(htmlspecialchars($html));
        return "<div onmouseover='bindFinderColTip(event)' rel='{$html}'>".strip_tags(htmlspecialchars($custom_mark[$k]['op_content']))."<div>";
    }

    var $column_customer_add='客服备注';
    var $column_customer_add_width = "100";
    function column_customer_add($row){
        $mark_text = $row[$this->col_prefix.'mark_text'];
        $mark_text = kernel::single('ome_func')->format_memo($mark_text);
        foreach ((array)$mark_text as $k=>$v){
            $html .= $v['op_content'].' '.$v['op_time'].' by '.$v['op_name']."<br />";
        }
        $html = strip_tags(htmlspecialchars($html));
        return "<div onmouseover='bindFinderColTip(event)' rel='{$html}'>".strip_tags(htmlspecialchars($mark_text[$k]['op_content']))."<div>";
    }

    function detail_basic($order_id){

        $render = app::get('archive')->render();
        $oOrders = &app::get('archive')->model('orders');
        $order_detail = $oOrders->dump(array('order_id'=>$order_id),'*');
        $oRefund = &app::get('ome')->model('refund_apply');
        
        $render->pagedata['shop_name'] = ome_shop_type::shop_name($order_detail['shop_type']);
        $order_detail['mark_text'] = kernel::single('ome_func')->format_memo($order_detail['mark_text']);
        $order_detail['custom_mark'] = kernel::single('ome_func')->format_memo($order_detail['custom_mark']);
        $render->pagedata['total_amount'] = floatval($order_detail['total_amount']);
        $render->pagedata['payed'] = floatval($order_detail['payed']);
        $oMembers = &app::get('ome')->model('members');
        $member_id = $order_detail['member_id'];
        $render->pagedata['member'] = $oMembers->dump($member_id);

        if($order_detail['shipping']['is_cod'] == 'true'){
            $orderExtendObj = &app::get('ome')->model('order_extend');
            $extendInfo = $orderExtendObj->dump($order_id);
            $order_detail['receivable'] = $extendInfo['receivable'];
        }
        $render->pagedata['order'] = $order_detail;
        return $render->fetch('order/detail_basic.html');
    }

    function detail_goods($order_id){
        $render = app::get('archive')->render();
        $oOrder = &app::get('archive')->model('orders');

        $item_list = $oOrder->getItemList($order_id,true);
        

        $render->pagedata['item_list'] = $item_list;
        
        return $render->fetch('order/detail_goods.html');
    }

    function detail_delivery($order_id){
        $render = app::get('archive')->render();
        $oDelivery = &app::get('archive')->model('delivery');
        $delivery_detail = $oDelivery->get_delivery($order_id);

        $status_text = array ('succ' => '已发货','failed' => '发货失败','cancel' => '已取消','progress' => '等待配货','timeout' => '超时','ready' => '等待配货','stop' => '暂停','back' => '打回','return_back'=>'追回');
        foreach ($delivery_detail as &$delivery ) {
           
            $delivery['status_text']  = $status_text[$delivery['status']];
        }
       
        $render->pagedata['delivery_detail'] = $delivery_detail;
        $oReship = &app::get('ome')->model('reship');
        $reship = $oReship->getList('t_begin,reship_id,reship_bn,logi_no,ship_name,delivery',array('order_id'=>$order_id));
        $render->pagedata['reship'] = $reship;

        return $render->fetch('order/detail_delivery.html');
    }

    function detail_abnormal($order_id){
        $render = app::get('archive')->render();
        $oAbnormal = &app::get('ome')->model('abnormal');
        $abnormal = $oAbnormal->getList("*",array("order_id"=>$order_id),0,-1,'abnormal_id desc');
        if($abnormal){
            $oAbnormal_type = &app::get('ome')->model('abnormal_type');
            $abnormal_type = $oAbnormal_type->getList("*");
            $abnormal[0]['abnormal_memo'] = unserialize($abnormal[0]['abnormal_memo']);
            $render->pagedata['abnormal'] = $abnormal[0];
            $render->pagedata['abnormal_type'] = $abnormal_type;
            $render->pagedata['order_id'] = $order_id;
        }else{
            $render->pagedata['set_abnormal'] = false;
        }
        return $render->fetch('order/detail_abnormal.html');
    }
    
    
 
    function detail_bill()
    {
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

    function detail_refund_apply($order_id){
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

   

}

?>