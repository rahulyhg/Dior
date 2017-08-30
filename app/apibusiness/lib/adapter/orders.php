<?php
/**
* ERP订单信息
*
* @category apibusiness
* @package apibusiness/lib/adapter
* @author chenping<chenping@shopex.cn>
* @version $Id: orders.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_orders
{
    /**
     * 获取店铺订单信息
     *
     * @param String $order_bn 订单号
     * @param String $shop_id 店铺ID
     * @return mix
     **/
    public function getOrder($order_bn,$shop_id)
    {
        $filter = array('order_bn' => $order_bn , 'shop_id' => $shop_id);

        $order = app::get('ome')->model('orders')->dump($filter , '*' , array('order_objects' => array('*',array('order_items' => array('*')))));

        return $order;
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $rs = app::get('ome')->model('orders')->getList($cols, $filter, $offset, $limit, $orderType);

        return $rs;
    }

    /**
     * 保存订单
     *
     * @param Array $order 订单结构数据
     * @return mix
     **/
    public function createOrder(&$order)
    {
        $rs = app::get('ome')->model('orders')->create_order($order);

        return $rs;
    }

    /**
     * 保存订单
     *
     * @param Array $order 订单结构数据
     * @return mix
     **/
    public function updateOrder($data,$filter)
    {
        $rs = app::get('ome')->model('orders')->update($data,$filter);

        return $rs;
    }

    /**
     * 取消订单
     *
     * @return void
     * @author 
     **/
    public function saveOrder($data)
    {
        $rs = app::get('ome')->model('orders')->save($data);

        return $rs;
    }

    /**
     * 取消订单
     *
     * @return void
     * @author 
     **/
    public function cancelOrder($order_id,$memo,$is_request=true,$mode='sync')
    {
        $rs = app::get('ome')->model('orders')->cancel($order_id,$memo,$is_request,$mode);

        return $rs;
    }


    /**
     * 订单快照
     *
     * @return void
     * @author 
     **/
    public function write_log_detail($log_id,$detail)
    {
        app::get('ome')->model('orders')->write_log_detail($log_id,$detail);
    }

    /**
     * 获取一行
     *
     * @return void
     * @author 
     **/
    public function getRow($filter,$cols='*')
    {
        $order = app::get('ome')->model('orders')->getList($cols,$filter);

        return $order ? $order[0] : array();
    }

    public function _format_productattr($productattr='',$product_id='',$original_str='')
    {
        $addon = app::get('ome')->model('orders')->_format_productattr($productattr,$product_id,$original_str);

        return $addon;
    }

}