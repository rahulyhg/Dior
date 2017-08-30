<?php
/**
* 店铺会员信息
*
* @category apibusiness
* @package apibusiness/lib/adapter/shop
* @author chenping<chenping@shopex.cn>
* @version $Id: members.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_shop_members
{
    /**
     * 获取一行
     *
     * @return void
     * @author 
     **/
    public function getRow($filter,$cols='*')
    {
        $member = app::get('ome')->model('shop_members')->getList($cols,$filter,0,1);

        return $member ? $member[0] : array();
    }
}