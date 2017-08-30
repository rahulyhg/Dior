<?php
class ome_payment_type{
    
    /**
     * 支付类型
     * @return array
     */
    static function pay_type(){
        
         $pay_type = array (
           'online' => '在线支付',
           'offline' => '线下支付',
           'deposit' => '预存款支付',
         );
        return $pay_type;
    }
    
    /**
     * 支付类型名称
     * @access public static
     * @param string $type 支付类型标识
     * @return mixed 支付类型名称
     */
    public static function pay_type_name($type=NULL){
        if (empty($type)) return NULL;
        $pay_type = self::pay_type();
        return $pay_type[$type];
    }
    
    /**
     * 获取前端店铺的支付方式
     * @param String $shop_id 店铺ID
     * @param String $pay_type 付款类型
     * @return mixed 当前店铺下的支付方式数据
     */
    public function paymethod($shop_id=NULL,$pay_type=''){
        if (empty($shop_id)) return NULL;
        $payment_shopObj = &app::get('ome')->model('payment_shop');
        $payment_cfgObj = &app::get('ome')->model('payment_cfg');
        $payment_shop_bn = $payment_shopObj->getList('pay_bn', array('shop_id'=>$shop_id), 0 ,-1);
        $pay_bn = $payment = array();
        if ($payment_shop_bn){
            foreach ($payment_shop_bn as $paykey=>$payval){
                $pay_bn[] = $payval['pay_bn'];
            }
            $paycfg_filter = array('pay_bn'=>$pay_bn);
            if ($pay_type){
                $paycfg_filter = array_merge($paycfg_filter, array('pay_type'=>$pay_type));
            }
            $payment = $payment_cfgObj->getList('id,custom_name,pay_bn', $paycfg_filter, 0, -1);
        }
        return $payment;
    }
    
    /**
     * 获取付款类型下的支付方式
     * @access public
     * @param Number $order_id 订单号
     * @param String $shop_id 前端店铺ID
     * @param String $pay_type 付款类型
     * @return 付款类型下的支付方式下拉框
     */
    public function payment_by_pay_type($order_id='',$shop_id='',$pay_type=''){
        
        if (empty($shop_id)){
            $objOrder = app::get('ome')->model('orders');
            $order_detail = $objOrder->dump($order_id, 'shop_id,pay_bn');
            $shop_id = $order_detail['shop_id'];
        }
        $shopObj = app::get('ome')->model('shop');
        $c2c_shop = ome_shop_type::shop_list();
        $shop_detail = $shopObj->dump(array('shop_id'=>$shop_id),'node_type,node_id');
        if (empty($shop_id) || empty($shop_detail['node_id'])){
            $oPayment = app::get('ome')->model('payments');
            $payment = $oPayment->getMethods($pay_type);
        }else{
            $payment = $this->paymethod($shop_id, $pay_type);
        }
        $options = '';
        foreach ((array)$payment as $k=>$v){
            $selected = '';
            if ($order_detail['pay_bn'] == $v['pay_bn']){
                $selected = 'selected="selected"';
            }
            $options .= '<option value="'.$v['id'].'" '.$selected.'>'.$v['custom_name'].'</option>';
        }
        $options .= '<option value="">请选择</option>';
        return $options;
    }
    
}