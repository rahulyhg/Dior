<?php
/**
* alibaba(阿里巴巴平台)直销订单处理
*
* @category apibusiness
* @package apibusiness/response/order/alibaba
* @author wangkezheng<wangkezheng@shopex.cn>
* @version $Id: b2cv1.php 2014-12-25 
*/
class apibusiness_response_order_alibaba_b2cv1 extends apibusiness_response_order_alibaba_abstract
{

    /**
     * 获取更新信息插件
     *
     * @return void
     * @author 
     **/
    public function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        $plugins[] = 'member';
        $plugins[] = 'promotion';
        $plugins[] = 'payment';
        $plugins[] = 'refundapply';
        return $plugins;
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