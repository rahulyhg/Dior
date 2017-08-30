<?php
/**
* 商品信息
*
* @category apibusiness
* @package apibusiness/lib/adapter
* @author chenping<chenping@shopex.cn>
* @version $Id: goods.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_goods
{
    /**
     * 根据货号获取商品信息
     *
     * @return void
     * @author 
     **/
    public function getGoodBybn($goods_bn)
    {
        $goods = app::get('ome')->model('goods')->getList('*',array('bn' => $goods_bn),0,1);

        return $goods ? $goods[0] : array();
    }
}