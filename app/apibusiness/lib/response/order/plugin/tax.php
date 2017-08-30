<?php
/**
* 税票插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: tax.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_tax extends apibusiness_response_order_plugin_abstract
{
    /**
     * 订单完成后处理
     * 有新订单，自动插入发票订单表
     *
     * @return void
     * @author
     **/
    public function postCreate()
    {
        if(app::get('invoice')->is_installed() && ($this->_platform->_newOrder['is_tax'] == 'true' || $this->_platform->_newOrder['is_tax'] == '1'))
        {
            $telphone    = ($this->_platform->_newOrder['consignee']['mobile'] ? 
            $this->_platform->_newOrder['consignee']['mobile'] : $this->_platform->_newOrder['consignee']['telephone']);
            
            $data       = array(
                            'order_id' => $this->_platform->_newOrder['order_id'],
                            'order_bn' => $this->_platform->_newOrder['order_bn'],
                            'total_amount' => $this->_platform->_newOrder['total_amount'],//订单总额
                            'tax_company' => $this->_platform->_newOrder['tax_title'],//发票抬头
            
                            'ship_name' => $this->_platform->_newOrder['consignee']['name'],//客户名称,默认调用收货人
                            'ship_area' => $this->_platform->_newOrder['consignee']['area'],//客户收货地区
                            'ship_addr' => $this->_platform->_newOrder['consignee']['addr'],//客户地址
                            'ship_tel' => $telphone,//客户电话
                        );

            $inOrder    = &app::get('invoice')->model('order');
            $result     = $inOrder->insert_order($data);
        }
    }
}