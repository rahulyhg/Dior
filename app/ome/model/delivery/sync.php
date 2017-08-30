<?php
/**
 +----------------------------------------------------------
 * 发货单回写状态列表数据
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2014-07-16 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class ome_mdl_delivery_sync extends dbeav_model
{
    /*------------------------------------------------------ */
    //-- 获取列表数据[自定义]
    /*------------------------------------------------------ */
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
    {
        if(empty($orderType))$orderType = "dateline DESC";

        return parent::getList('*',$filter,$offset,$limit,$orderType);
    }
}
?>