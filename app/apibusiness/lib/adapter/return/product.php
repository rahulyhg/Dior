<?php
/**
* 售后申请单
*
* @category apibusiness
* @package apibusiness/lib/adapter/return
* @author chenping<chenping@shopex.cn>
* @version $Id: goods.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_return_product
{
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $rs = app::get('ome')->model('return_product')->getList($cols, $filter, $offset, $limit, $orderType);

        return $rs;
    }

    public function create_return_product(&$sdf)
    {
        $rs = app::get('ome')->model('return_product')->create_return_product($sdf);

        return $rs;
    }

    public function getRow($filter,$cols='*')
    {
        $aftersale = app::get('ome')->model('return_product')->getList($cols,$filter,0,1);

        return $aftersale ? $aftersale[0] : array();
    }

    public function tosave($data,$api=FALSE)
    {
        $rs = app::get('ome')->model('return_product')->tosave($data,$api);

        return $rs;
    }

    public function saveinfo($data,$api=FALSE)
    {
        $rs = app::get('ome')->model('return_product')->saveinfo($data,$api);

        return $rs;
    }

    public function update($data,$filter,$mustUpdate = null)
    {
        $rs = app::get('ome')->model('return_product')->update($data,$filter,$mustUpdate);

        return $rs;
    }

    public function save(&$data,$mustUpdate = null)
    {
        $rs = app::get('ome')->model('return_product')->save($data,$mustUpdate);

        return $rs;
    }
}