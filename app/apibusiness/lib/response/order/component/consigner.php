<?php
/**
* 发货人信息
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: b2cv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_consigner extends apibusiness_response_order_component_abstract
{
    const _APP_NAME = 'ome';
    /**
     * 数据转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {   
        if ($this->_platform->_ordersdf['consigner']) {
            $this->_platform->_newOrder['consigner']['name']   = $this->_platform->_ordersdf['consigner']['name'];
            $this->_platform->_newOrder['consigner']['area']   = $this->_platform->_ordersdf['consigner']['area_state'] . '/' . $this->_platform->_ordersdf['consigner']['area_city'] . '/' . $this->_platform->_ordersdf['consigner']['area_district'];
            $this->_platform->_newOrder['consigner']['addr']   = $this->_platform->_ordersdf['consigner']['addr'];
            $this->_platform->_newOrder['consigner']['zip']    = $this->_platform->_ordersdf['consigner']['zip'];
            $this->_platform->_newOrder['consigner']['tel']    = $this->_platform->_ordersdf['consigner']['telephone'];
            $this->_platform->_newOrder['consigner']['email']  = $this->_platform->_ordersdf['consigner']['email'];
            $this->_platform->_newOrder['consigner']['mobile'] = $this->_platform->_ordersdf['consigner']['mobile'];
        }
    }

    /**
     * 更新发货人
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        $consigner_update = kernel::single('ome_order_func')->update_consigner(
            $this->_platform->_tgOrder['order_id'],
            $this->_platform->_ordersdf['consigner'],
            $old_consigner,
            false
            );

        if ($consigner_update) {
            $this->_platform->_newOrder = array_merge($this->_platform->_newOrder,$consigner_update);
            if($consigner_update['consigner_name'])
                $this->_platform->_newOrder['consigner']['name']   = $consigner_update['consigner_name'];
            if($consigner_update['consigner_area'])
                $this->_platform->_newOrder['consigner']['area']   = $consigner_update['consigner_area'];
            if($consigner_update['consigner_addr'])
                $this->_platform->_newOrder['consigner']['addr']   = $consigner_update['consigner_addr'];
            if($consigner_update['consigner_zip'])
                $this->_platform->_newOrder['consigner']['zip']    = $consigner_update['consigner_zip'];
            if($consigner_update['consigner_tel'])
                $this->_platform->_newOrder['consigner']['tel']    = $consigner_update['consigner_tel'];
            if($consigner_update['consigner_email'])
                $this->_platform->_newOrder['consigner']['email']  = $consigner_update['consigner_email'];
            if($consigner_update['consigner_mobile'])
                $this->_platform->_newOrder['consigner']['mobile'] = $consigner_update['consigner_mobile'];

            $this->_platform->_apiLog['info'][] = '更新发货人信息$sdf结构：'.var_export($consigner_update,true);

            $logModel = app::get(self::_APP_NAME)->model('operation_log');
            $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'修改发货人信息');

            
        }
    }
}