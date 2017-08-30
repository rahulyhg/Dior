<?php
/**
* vjia(凡客平台)直销订单处理 版本一
*
* @category apibusiness
* @package apibusiness/response/order/vjia
* @author shangshuai<shangshuai@shopex.cn>
* @version $Id: b2cv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_vjia_b2cv1 extends apibusiness_response_order_vjia_abstract
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

        $this->canCancelOrder = false;//是否能取消订单

        if( ($this->_tgOrder['ship_status'] == 0) && ($this->_tgOrder['shipping']['is_cod'] == 'true') && ($this->_ordersdf['status'] != 'active') && ($this->_ordersdf['shipping']['is_cod'] == 'true') ){
            $this->canCancelOrder = true;
        }elseif( ($this->_ordersdf['shipping']['is_cod'] == 'true') && ($this->_ordersdf['status'] != 'active') ){
            $this->_apiLog['info']['msg'] = '取消的订单不接收';
            return false;
        } 

        return parent::canUpdate();
    }


    /**
     * 更新订单前的操作
     *
     * @return void
     * @author 
     **/
    protected function preUpdate()
    {
        parent::preUpdate();

        if($this->canCancelOrder == true){
            $this->_newOrder['archive'] = '1';
            $this->_newOrder['process_status'] = 'cancel';
            $this->_newOrder['status'] = 'dead';
            $this->_newOrder['confirm'] = 'Y';
        }

    }

    /**
     * 更新完成后操作
     *
     * @return void
     * @author 
     **/
    protected function postUpdate()
    {
        parent::postUpdate();

        if($this->canCancelOrder == true){//订单取消,如果有发货单打回发货单
            $memo = '由于前端店铺:'.$this->_shop['name'].'订单取消，系统自动作废';
            $orderModel = app::get(self::_APP_NAME)->model('orders');
            $rs = $orderModel->cancel($this->_newOrder['order_id'],$memo,true,'async');
            $this->_apiLog['info'][] = $memo;
        }

    }
}