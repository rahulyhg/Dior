<?php
/**
* 收货人信息
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: consignee.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_consignee extends apibusiness_response_order_component_abstract
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
        if ($this->_platform->_ordersdf['consignee']) {
            $this->_platform->_newOrder['consignee']['name']      = $this->_platform->_ordersdf['consignee']['name'];
            $this->_platform->_newOrder['consignee']['area']      = $this->_platform->_ordersdf['consignee']['area_state'] . '/' . $this->_platform->_ordersdf['consignee']['area_city'] . '/' . $this->_platform->_ordersdf['consignee']['area_district'];
            $this->_platform->_newOrder['consignee']['addr']      = $this->_platform->_ordersdf['consignee']['addr'];
            $this->_platform->_newOrder['consignee']['zip']       = $this->_platform->_ordersdf['consignee']['zip'];
            $this->_platform->_newOrder['consignee']['telephone'] = $this->_platform->_ordersdf['consignee']['telephone'];
            $this->_platform->_newOrder['consignee']['email']     = $this->_platform->_ordersdf['consignee']['email'];
            $this->_platform->_newOrder['consignee']['r_time']    = $this->_platform->_ordersdf['consignee']['r_time'];
            $this->_platform->_newOrder['consignee']['mobile']    = $this->_platform->_ordersdf['consignee']['mobile'];
        }
    }

    /**
     * 修改收货人
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        $consignee_update = kernel::single('ome_order_func')->update_consignee(
                                                                $this->_platform->_tgOrder['order_id'],
                                                                $this->_platform->_ordersdf['consignee'],
                                                                $old_consignee,
                                                                false
                                                                );
        if ($consignee_update) {
            $process_status = array('unconfirmed','confirmed','splitting','splited');
            if (in_array($this->_platform->_tgOrder['process_status'], $process_status) && $this->_platform->_tgOrder['ship_status'] == '0') {
                $this->_platform->_newOrder = array_merge($this->_platform->_newOrder,$consignee_update);
                if($consignee_update['ship_name'])
                    $this->_platform->_newOrder['consignee']['name']      = $consignee_update['ship_name'];
                if($consignee_update['ship_area'])
                    $this->_platform->_newOrder['consignee']['area']      = $consignee_update['ship_area'];
                if($consignee_update['ship_addr'])
                    $this->_platform->_newOrder['consignee']['addr']      = $consignee_update['ship_addr'];
                if($consignee_update['ship_zip'])
                    $this->_platform->_newOrder['consignee']['zip']       = $consignee_update['ship_zip'];
                if($consignee_update['ship_tel'])
                    $this->_platform->_newOrder['consignee']['telephone'] = $consignee_update['ship_tel'];
                if($consignee_update['ship_email'])
                    $this->_platform->_newOrder['consignee']['email']     = $consignee_update['ship_email'];
                if($consignee_update['ship_time'])
                    $this->_platform->_newOrder['consignee']['r_time']    = $consignee_update['ship_time'];
                if($consignee_update['ship_mobile'])
                    $this->_platform->_newOrder['consignee']['mobile']    = $consignee_update['ship_mobile'];

                $this->_platform->_apiLog['info'][] = '收货人信息发生变化，$sdf结构：'.var_export($consignee_update,true);

                $logModel = app::get(self::_APP_NAME)->model('operation_log');
                $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'修改收货人信息');
            }
        }
    }
}