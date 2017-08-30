<?php
/**
* 退款申请信息
*
* @category apibusiness
* @package apibusiness/lib/adapter/refund
* @author chenping<chenping@shopex.cn>
* @version $Id: apply.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_refund_apply
{
    /**
     * 生成退款申请单
     *
     * @return void
     * @author 
     **/
    public function create_refund_apply(&$data)
    {
        app::get('ome')->model('refund_apply')->create_refund_apply($data);
    }

    /**
     * 生成主建
     *
     * @return void
     * @author 
     **/
    public function gen_id()
    {
        $id = app::get('ome')->model('refund_apply')->gen_id();

        return $id;
    }

    /**
     * 更新
     *
     * @return void
     * @author 
     **/
    public function update($data,$filter,$mustUpdate = null)
    {
        $rs = app::get('ome')->model('refund_apply')->update($data,$filter,$mustUpdate);

        return $rs;
    }

    /**
     * 获取一行
     *
     * @param Array $filter 筛选条件
     * @param String $cols 筛选字段
     * @return Array
     * @author 
     **/
    public function getRow($filter,$cols = '*')
    {
        $refund = app::get('ome')->model('refund_apply')->getList($cols,$filter,0,1);

        return $refund ? $refund[0] : array();
    }
}