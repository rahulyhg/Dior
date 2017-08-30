<?php
/**
* 发票
*
* @category apibusiness
* @package apibusiness/response/order/component
* @author chenping<chenping@shopex.cn>
* @version $Id: tax.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_component_tax extends apibusiness_response_order_component_abstract
{
    const _APP_NAME = 'ome';

    /**
     * 订单格式转换
     *
     * @return void
     * @author 
     **/
    public function convert()
    {}
    
    /**
     * 更新订单旗标
     *
     * @return void
     * @author 
     **/
    public function update()
    {
        if ($this->_platform->_ordersdf['tax_title'] != $this->_platform->_tgOrder['tax_title']) {
            $this->_platform->_newOrder['tax_title'] = $this->_platform->_ordersdf['tax_title'];

            $this->_platform->_apiLog['info'][] = '发票抬头发生变更：'.var_export($this->_platform->_ordersdf['tax_title'],true);
        }
        
        if ($this->_platform->_ordersdf['is_tax'] != $this->_platform->_tgOrder['is_tax']) {
            $this->_platform->_newOrder['is_tax'] = $this->_platform->_ordersdf['is_tax'];
        }
    }
}