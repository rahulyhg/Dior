<?php
class apibusiness_response_aftersalev2_feiniu_v1 extends apibusiness_response_aftersalev2_v1{
    #验证是否接收
    protected function canAccept($tgOrder=array()){
        if ($this->_refundsdf['status'] == 'success' || $this->_refundsdf['refund_type'] == 'refund') {
            if (bccomp($tgOrder['payed'], $this->_refundsdf['refund_fee'],3) < 0) {
                $this->_apiLog['title']  = '创建飞牛退款[退款单号：'.$this->_refundsdf['refund_id'].' ]';
                $this->_apiLog['info'][] = "接受参数：".var_export($this->_refundsdf,true);
                $this->_apiLog['info']['msg'] = '创建退款单失败：已支付金额('.$tgOrder['payed'].')小于退款金额('.$this->_refundsdf['refund_fee'].')';
                $this->exception(__METHOD__);
                return false;        
            }
        }
        #订单状态判断
        if ($tgOrder['process_status'] == 'cancel') {
            $this->_apiLog['info']['msg'] = '创建退款单失败:订单['.$this->_refundsdf['tid'].']已经取消，无法退款';
            $this->_apiLog['info'][] = "接受参数：".var_export($this->_refundsdf,true);
            $this->_apiLog['title']  = '创建飞牛退款[退款单号：'.$this->_refundsdf['refund_id'].' ]';
            $this->exception(__METHOD__);
           return false;
        }
        return true;
    }

    
    #添加退款单
    public function add(){
        parent::add();
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order_bn  = $this->_refundsdf['tid'];
        $shop_id     = $this->_shop['shop_id'];
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'ship_status,pay_status,status,process_status,order_id,payed,cost_payment,ship_status');
        $ship_status = array(0);
        #只接受未发货的售前退款。售后的,暂不接
        if(in_array($tgOrder['ship_status'],$ship_status)){
            $this->refund_add();
        }else{
            $this->_apiLog['info']['msg'] = '创建退款单失败:只接受未发货的售前退款';
            $this->_apiLog['info'][] = "接受参数：".var_export($this->_refundsdf,true);
            $this->_apiLog['title']  = '创建飞牛退款[退款单号：'.$this->_refundsdf['refund_id'].' ]';
            $this->exception(__METHOD__);
            return false;
        }
        return true;
    }
    
    protected function format_data($sdf){
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
                $oid = trim($item['item_id']);
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
                            'bn'=>   $oid,
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