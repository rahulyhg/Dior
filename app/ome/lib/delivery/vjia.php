<?php
/**
 * 发货过程中对vjia的特殊处理
 *
 * @author shshuai
 * @package ome_delivery_vjia
 * @copyright www.shopex.cn 2013.08.16
 */

class ome_delivery_vjia{
    /**
     * @description 修改配送信息
     * @access public
     * @param int $order_id 订单ID
     * @return boolean
     */
    public function logistics_modify($order_id) {
        if(!$order_id){
            return false;
        }
        $order = app::get('ome')->model('orders')->dump($order_id, 'order_bn,shop_id');
        $rpcData = array();
        $rpcData['tid'] = $order['order_bn'];
        $rpcData['company_code'] = 'OTHER';
        $rpcData['company_name'] = '客户自提';
        $rpcData['logistics_no'] = md5(uniqid());

        $router = kernel::single('apibusiness_router_request');
        $router->setShopId($order['shop_id'])->logistics_modify($rpcData);
        return true;
    }
}