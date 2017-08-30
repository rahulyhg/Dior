<?php
/**
* 管理员信息
*
* @category apibusiness
* @package apibusiness/lib/adapter
* @author chenping<chenping@shopex.cn>
* @version $Id: users.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_users
{
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $rs = app::get('desktop')->model('users')->getList($cols, $filter, $offset, $limit, $orderType);

        return $rs;
    }
}