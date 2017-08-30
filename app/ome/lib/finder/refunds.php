<?php
class ome_finder_refunds{
    var $detail_basic = "退款单详情";
    
    function detail_basic($refund_id){
        $render = app::get('ome')->render();
        $oRefunds = &app::get('ome')->model('refunds');
        
        $render->pagedata['refund'] = $oRefunds->refund_detail($refund_id);
        $oOrders = &app::get('ome')->model('orders');
        $order_id = $render->pagedata['refund']['order_id'];
        $render->pagedata['order'] = $oOrders->order_detail($order_id);
        $render->pagedata['pay_type'] = ome_payment_type::pay_type();
        return $render->fetch('admin/refund/detail.html');
    }
    
    var $addon_cols = 'archive,order_id';
    var $column_order_id='订单号';
    var $column_order_id_width='100';
    function column_order_id($row)
    {
        $archive = $row[$this->col_prefix . 'archive'];
        
        $order_id = $row[$this->col_prefix . 'order_id'];
        
        if ($archive == '1' ) {
            $orderObj = app::get('archive')->model('orders');
            
        }else{
            $orderObj = app::get('ome')->model('orders');
            
        }
        $filter = array('order_id'=>$order_id);
        $order = $orderObj->dump($filter,'order_bn');

        return $order['order_bn'];
    }
}
?>