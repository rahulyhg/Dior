<?php
/**
* 货品信息
*
* @category apibusiness
* @package apibusiness/lib/adapter
* @author chenping<chenping@shopex.cn>
* @version $Id: products.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_products
{
    /**
     * 根据货号获取货品信息
     *
     * @return void
     * @author 
     **/
    public function getProductBybn($product_bn)
    {
        $product = app::get('ome')->model('products')->getList('*',array('bn' => $product_bn),0,1);

        return $product ? $product[0] : array();
    }

    public function chg_product_store_freeze($product_id,$num,$operator='='){
        app::get('ome')->model('products')->chg_product_store_freeze($product_id,$num,$operator);
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $rs = app::get('ome')->model('products')->getList($cols, $filter, $offset, $limit, $orderType);

        return $rs;
    }

    /**
     * 读取一行
     *
     * @return void
     * @author 
     **/
    public function getRow($filter,$cols='*')
    {
        $product = app::get('ome')->model('products')->getList($cols,$filter,0,1);

        return $product ? $product[0] : array();
    }
}