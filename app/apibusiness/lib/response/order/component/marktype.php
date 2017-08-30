<?php
/**
* 订单备注旗标
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: marktype.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_marktype extends apibusiness_response_order_component_abstract
{
    const _APP_NAME = 'ome';

    /**
     * 订单格式转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {
        if ($this->_platform->_ordersdf['mark_type']) {
            $this->_platform->_newOrder['mark_type'] = $this->_platform->_ordersdf['mark_type'];
        }
    }
    
    /**
     * 更新订单旗标
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        if ($this->_platform->_ordersdf['mark_type'] && $this->_platform->_ordersdf['mark_type'] != $this->_platform->_tgOrder['mark_type']) {
            $this->_platform->_newOrder['mark_type'] = $this->_platform->_ordersdf['mark_type'];

            $this->_platform->_apiLog['info'][] = '订单旗标发生变化，$sdf结构：'.var_export($this->_platform->_ordersdf['mark_type'],true);

            $logModel = app::get(self::_APP_NAME)->model('operation_log');
            $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'修改订单旗标');
        }
    }
}