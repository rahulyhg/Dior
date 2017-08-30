<?php
/**
* 淘宝赠品插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: tbgift.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_outstorage extends apibusiness_response_order_plugin_abstract
{
    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        $rpcData = array();
        $rpcData['tid'] = $this->_platform->_newOrder['order_bn'];
        $rpcData['order_id'] = $this->_platform->_newOrder['order_id'];
        $rpcData['company_code'] = 'OTHER';
        $rpcData['company_name'] = '客户自提';
        $rpcData['logistics_no'] = sprintf('%u',crc32(uniqid()));

        $router = kernel::single('apibusiness_router_request');
        $router->setShopId($this->_platform->_ordersdf['shop_id'])->outstorage_request($rpcData);
        $this->_platform->_apiLog['info'][] = '发货出库请求参数：'.var_export($rpcData,true);
    }
}