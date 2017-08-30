<?php
/**
* 退款单信息
*
* @category apibusiness
* @package apibusiness/lib/adapter
* @author chenping<chenping@shopex.cn>
* @version $Id: refunds.php 2013-3-12 14:37Z
*/
class apibusiness_adapter_refunds
{
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
        $refund = app::get('ome')->model('refunds')->getList($cols,$filter,0,1);

        return $refund ? $refund[0] : array();
    }

    /**
     * 创建退款单
     *
     * @return Array
     * @author 
     **/
    public function create_refunds(&$sdf){
        
        $rs = app::get('ome')->model('refunds')->create_refunds($sdf);

        return $rs;
    }

}