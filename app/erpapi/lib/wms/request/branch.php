<?php
/**
 * 仓库
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_request_branch extends erpapi_wms_request_abstract
{
    /**
     * 获取仓库列表
     *
     * @return void
     * @author 
     **/
    public function branch_getlist($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'获取仓库列表';

        return $this->__caller->call(WMS_WAREHOUSE_LIST_GET, null, null, $title, 10);
    }
}