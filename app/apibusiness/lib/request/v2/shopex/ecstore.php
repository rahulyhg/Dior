<?php
/**
* ecstore(ECSTORE系统)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v2
* @author chenping<chenping@shopex.cn>
* @version $Id: ecstore.php 2013-13-12 14:44Z
*/
class apibusiness_request_v2_shopex_ecstore extends apibusiness_request_v2_shopex_abstract
{
    /**
     * 获取必要的发货数据
     *
     * @param Array $delivery 发货单信息
     * @return MIX
     * @author 
     **/
    protected function format_delivery($delivery)
    {
        $this->_shop['area'] = $delivery['consignee']['area'];

        return parent::format_delivery($delivery);
    }// TODO TEST
}