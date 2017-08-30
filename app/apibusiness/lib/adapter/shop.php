<?php
/**
* ERP店铺信息
*
* @category apibusiness
* @package apibusiness/lib/adapter
* @author chenping<chenping@shopex.cn>
* @version $Id: shop.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_shop
{

    /**
     * 节点号获取店铺信息
     *
     * @param Int $node_id 节点
     * @return mix
     **/
    public function getShopByNodeId($node_id)
    {
        $filter = array('node_id' => $node_id);

        // $shop = app::get('ome')->model('shop')->getList('*',$filter,0,1);
        $shop = app::get('ome')->model('shop')->dump($filter);

        return $shop ? $shop : array();
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function update($data,$filter)
    {
        $result = app::get('ome')->model('shop')->update($data,$filter);

        return $result;
    }
}