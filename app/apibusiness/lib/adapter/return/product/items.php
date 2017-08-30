<?php
/**
* 售后申请明细
*
* @category apibusiness
* @package apibusiness/lib/adapter/return/product
* @author chenping<chenping@shopex.cn>
* @version $Id: product.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_return_product_items
{
    public function batchSave($data)
    {
        $itemModel = app::get('ome')->model('return_product_items');

        $sql = ome_func::get_insert_sql($itemModel,$data);

        $rs = $itemModel->db->exce($sql);

        return $rs;
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $list = app::get('ome')->model('return_product_items')->getList($cols, $filter, $offset, $limit, $orderType);

        return $list;
    }
}