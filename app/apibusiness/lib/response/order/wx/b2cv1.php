<?php
class apibusiness_response_order_wx_b2cv1 extends  apibusiness_response_order_wx_abstract{
    
    /**
     * 是否接收(除活动订单外的其他订单)
     *
     * @return void
     * @author
     **/
    protected function accept_dead_order(){
        $rs = parent::accept_dead_order();
        #订单取消的，先放过
        if ($rs == false && $this->_ordersdf['status'] == 'dead') {
            unset($this->_apiLog['info']['msg']);
            return true;
        }
        return $rs;
    }
    /*     
     * 是否接收订单
    *
    * @return void
    * @author
    **/
    protected function canAccept(){
        $result = parent::canAccept();
        if ($result === false) {
            return false;
        }
    
        # 未支付的款到发货订单拒收
        if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }
    
        return true;
    }    
            
    /**
     * 
     *
     * @return void
     * @author
     **/
    public function canCreate(){
        if ($this->_ordersdf['status'] != 'active') {
            $this->_apiLog['info']['msg'] = ($this->_ordersdf['status'] == 'dead') ? '取消的订单不接收' : '完成的订单不接收';
            return false;
        }
        #微信创建订单的时候，未支付订单不接受
        if($this->_ordersdf['pay_status'] != '1'){
            $this->_apiLog['info']['msg'] =  '未支付微信小店订单不接收';
            return false;
        }
    }      

    /**
     *
     *
     * @return void
     * @author
     **/
    protected function canUpdate(){
        $rs = parent::canUpdate();
    
         #全额退款，订单取消
        if ($this->_ordersdf['status'] == 'dead' && $this->_ordersdf['pay_status'] == '5') {
            if ($this->_tgOrder['status'] == 'active' && $this->_tgOrder['ship_status'] == '0') {
                $orderModel = app::get(self::_APP_NAME)->model('orders');
                #原单是部分退款，退款中的，更新为全额退款
                if ($this->_tgOrder['pay_status'] == '6' && $this->_tgOrder['pay_status'] == '4') {
                    $ordersdf = array(
                            'payed' => '0',
                    );
                    $orderModel->update($ordersdf,array('order_id' => $this->_tgOrder['order_id']));
                }
                $memo = '前端订单取消';
                $orderModel->cancel($this->_tgOrder['order_id'],$memo,false,'async');
    
                $this->_apiLog['info'][] = '返回值：订单取消成功';
                return true;
            } else {
                $this->_apiLog['info'][] = '返回值：取消订单,未更新';
                return false;
            }
        } 
        return $rs;
    }
    /**
     * 获取更新信息插件
     *
     * @return void
     * @author
     **/
     public function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();
        return $plugins;
    }   
}