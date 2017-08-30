<?php
/**
* suning(苏宁平台)直销订单处理 版本一
*
* @category apibusiness
* @package apibusiness/response/order/suning
* @author chenping<chenping@shopex.cn>
* @version $Id: b2cv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_suning_b2cv1 extends apibusiness_response_order_suning_abstract
{


    /**
     * 是否接收(除活动订单外的其他订单)
     *
     * @return void
     * @author 
     **/
    protected function accept_dead_order(){

        if($this->_ordersdf['shipping']['is_cod'] == 'true' && $this->_ordersdf['status'] != 'active'){
            return true;
        }else{
            return parent::accept_dead_order();
        }
    }


    /**
     * 允许更新
     *
     * @return void
     * @author 
     **/
    protected function canUpdate()
    {
        if( ($this->_tgOrder['ship_status'] == 0) && ($this->_tgOrder['shipping']['is_cod'] == 'true') && ($this->_ordersdf['status'] != 'active') && ($this->_ordersdf['shipping']['is_cod'] == 'true') ){
            // 取消订单
            $memo = '前端店铺:'.$this->_shop['name'].'订单作废';

            $orderModel = app::get(self::_APP_NAME)->model('orders');
            $orderModel->cancel($this->_tgOrder['order_id'],$memo,false,'async');
            
            $this->_apiLog['info'][] = '返回值：订单取消成功！';
            $this->shutdown('add');

        }elseif( ($this->_ordersdf['shipping']['is_cod'] == 'true') && ($this->_ordersdf['status'] != 'active') ){
            $this->_apiLog['info']['msg'] = '取消的订单不接收';
            return false;
        }

        return parent::canUpdate();
    }
            
}