<?php
/**
* 支付信息
*
* @category apibusiness
* @package apibusiness/lib/adapter
* @author chenping<chenping@shopex.cn>
* @version $Id: payments.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_payments
{
    /**
     * 保存
     *
     * @return void
     * @author 
     **/
    public function save(&$data,$mustUpdate = null)
    {
        $rs = app::get('ome')->model('payments')->save($data,$mustUpdate);

        return $rs;
    }

    /**
     * 保存
     *
     * @return void
     * @author 
     **/
    public function insert(&$data)
    {
        $rs = app::get('ome')->model('payments')->insert($data);

        return $rs;
    }

    /**
     * 更新
     *
     * @return void
     * @author 
     **/
    public function update($data,$filter,$mustUpdate = null)
    {
        $rs = app::get('ome')->model('payments')->update($data,$filter,$mustUpdate);

        return $rs;
    }

    

    /**
     * 查询
     *
     * @return void
     * @author 
     **/
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        $rs = app::get('ome')->model('payments')->getList($cols,$filter,$offset,$limit,$orderType);

        return $rs;
    }

    public function create_payments(&$sdf){
        $rs = app::get('ome')->model('payments')->create_payments($sdf);

        return $rs;
    }
}