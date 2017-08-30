<?php
class apibusiness_response_aftersalev2_taobao_v1 extends apibusiness_response_aftersalev2_v1{
    
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        
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

        if (strtolower($this->_refundsdf['has_good_return']) == 'true') {//需要退货才更新为售后单
            if (in_array($tgOrder['ship_status'],array('0'))) {
                $this->refund_add();
        
            }else{
                $this->aftersale_add();
            }
        }else{
            $this->refund_add();
        }
    }
    
    /**
     * 将数据统一格式化
     * @param   array    $sdf
     * @return  array    
     * @access  public
     * @author cyyr24@sina.cn
     */
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

    protected function refund_additional($refundinfo){
        #退款申请附加表
        $oRefund_tb = app::get(self::_APP_NAME)->model('refund_apply_taobao');
        $tb_data = array(
            'refund_apply_bn' => $refundinfo['refund_apply_bn'],
            'shop_id'         => $refundinfo['shop_id'],
            'oid'               => $this->_refundsdf['oid'],
            'apply_id'          => $refundinfo['apply_id'],
            'cs_status'         => $this->_refundsdf['cs_status'],
            'advance_status'    => $this->_refundsdf['advance_status'],
            'split_taobao_fee'  => $this->_refundsdf['split_taobao_fee'],
            'split_seller_fee'  => $this->_refundsdf['split_seller_fee'],
            'total_fee'         => $this->_refundsdf['total_fee'],
            'seller_nick'       => $this->_refundsdf['seller_nick'],
            'good_status'       => $this->_refundsdf['good_status'],
            'has_good_return'   => $this->_refundsdf['has_good_return'],
            'alipay_no'         => $this->_refundsdf['alipay_no'],
            'current_phase_timeout'=>strtotime($this->_refundsdf['current_phase_timeout']),
        );
        $oRefund_tb->save($tb_data);
    }

    protected function aftersale_additional($returninfo){
        
        $return_bn = $returninfo['return_bn'];
        $shop_id = $returninfo['shop_id'];
        $oReturn_tb = app::get(self::_APP_NAME)->model('return_product_taobao');
        $status      = self::$refund_status[strtoupper($this->_refundsdf['status'])];
        //将信息新增至关联表
        $return_tb_data = array(
            'return_id'       => $returninfo['return_id'],
            'shop_id'         => $shop_id,
            'return_bn'       => $return_bn,
            'shipping_type'   => $this->_refundsdf['shipping_type'],
            'cs_status'       => $this->_refundsdf['cs_status'],
            'advance_status'  => $this->_refundsdf['advance_status'],
            'split_taobao_fee'=> $this->_refundsdf['split_taobao_fee'],
            'split_seller_fee'=> $this->_refundsdf['split_seller_fee'],
            'total_fee'       => $this->_refundsdf['total_fee'],
            'buyer_nick'      => $this->_refundsdf['buyer_nick'],
            'seller_nick'     => $this->_refundsdf['seller_nick'],
            'good_status'     => $this->_refundsdf['good_status'],
            'has_good_return' => $this->_refundsdf['has_good_return'],
            'good_return_time'=> $this->_refundsdf['good_return_time'],
            'alipay_no'       => $this->_refundsdf['payment_id'],
            'ship_addr'       => $this->_refundsdf['receiver_address'],
            'outer_lastmodify'=>$this->_refundsdf['modified'],
            'oid'               => $this->_refundsdf['oid'],
            'current_phase_timeout'=>strtotime($this->_refundsdf['current_phase_timeout']),
        );
        if ($this->_refundsdf['tag_list']) {
            $tag_list = json_decode($this->_refundsdf['tag_list'],true);
            $return_tb_data['tag_list'] = serialize($tag_list);
        }
        if ($this->_refundsdf['address']) {
            $return_tb_data['address'] = $this->_refundsdf['address'];
        }
        $oReturn_tb->save( $return_tb_data );

    }
}

?>