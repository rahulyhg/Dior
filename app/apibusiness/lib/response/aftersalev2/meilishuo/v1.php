<?php
class apibusiness_response_aftersalev2_meilishuo_v1 extends apibusiness_response_aftersalev2_v1{
    
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array()){
        
        if ($this->_refundsdf['status'] == 'success' || $this->_refundsdf['refund_type'] == 'refund') {
        
            if (bccomp($tgOrder['payed'], $this->_refundsdf['refund_fee'],3) < 0) {
                $this->_apiLog['info']['msg'] = '退款失败,支付金额('.$tgOrder['payed'].')小于退款金额('.$this->_refundsdf['refund_fee'].')';
                return false;        
            }
        }
        // 订单状态判断
        if ($tgOrder['process_status'] == 'cancel') {
            $this->_apiLog['info']['msg'] = '订单['.$this->_refundsdf['tid'].']已经取消，无法退款';
            return false;
        }
        return true;
    }

    /**
     * 添加退款单
     *
     * @return void
     * @author 
     **/
    public function add(){
        parent::add();
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order_bn  = $this->_refundsdf['tid'];
        $shop_id     = $this->_shop['shop_id'];
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'pay_status,status,process_status,order_id,payed,cost_payment,ship_status');
        if (strtolower($this->_refundsdf['has_good_return']) == '1') {//需要退货才更新为售后单
            if (in_array($tgOrder['ship_status'],array('0'))) {
                #有退货，未发货的,做退款
                $this->refund_add();
            }else{
                #有退货，已发货的,做售后
                $this->aftersale_add();
            }
        }else{
            #无退货的，直接退款
            $this->refund_add();
        }
    }
    
    protected function format_data($sdf)
    {
        $sdf['modified'] = strtotime($sdf['modified']);
        $sdf['has_good_return'] = strtolower($sdf['has_good_return']);
        $sdf['good_return_time'] = strtotime($sdf['good_return_time']);
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order_objectModel = app::get(self::_APP_NAME)->model('order_objects');
        $oOrder_items = app::get(self::_APP_NAME)->model('order_items');
        $order_bn    = $sdf['tid'];
        $shop_id     = $this->_shop['shop_id'];
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'order_id');
        $order_id = $tgOrder['order_id'];
        if ($sdf['refund_item_list']) {
            $refund_item_list = json_decode($sdf['refund_item_list'],true);
            $refund_item_list = $refund_item_list['return_item'];
            $item_list = array();
            foreach ($refund_item_list as $k=>$item ) {
                $oid = trim($item['oid']);
                $order_object = $order_objectModel->dump(array('oid'=>$oid,'order_id'=>$order_id));
                if ($order_object) {
                    $obj_type = $order_object['obj_type'];
                    $obj_id = $order_object['obj_id'];
                    $order_items = $oOrder_items->getlist('bn,price,nums',array('obj_id'=>$obj_id));
                    if ($order_items) {
                        foreach ($order_items as $ok=>$ov) {
                            $item_list[$ok]=array(
                                'num'=>   $ov['nums'],
                                'bn'=>   $ov['bn'],
                                'price'=>$ov['price'],
                            );
                        }
                    }
                
                }else{
                     $item_list[]=array(
                            'num'=>   $item['nums'],
                            'bn'=>   $item['outer_id'],
                            'price'=>$item['price'],
                     );
                }
            }
            $sdf['refund_item_list'] = $item_list;
        }
 
        return $sdf;
    }
}

?>