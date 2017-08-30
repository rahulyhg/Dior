<?php
/**
* 订单明细信息
*
* @category apibusiness
* @package apibusiness/lib/adapter/order
* @author chenping<chenping@shopex.cn>
* @version $Id: items.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_order_items
{
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $rs = app::get('ome')->model('order_items')->getList($cols, $filter, $offset, $limit, $orderType);

        return $rs;
    }
}