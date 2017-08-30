<?php
/**
* 发货单信息
*
* @category apibusiness
* @package apibusiness/lib/adapter
* @author chenping<chenping@shopex.cn>
* @version $Id: delivery.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_delivery
{
    /**
     * 获取一行
     *
     * @return void
     * @author 
     **/
    public function getRow($filter,$cols = '*')
    {
        $delivery = app::get('ome')->model('delivery')->getList($cols,$filter,0,1);

        return $delivery ? $delivery[0] : array();
    }
}