<?php
/**
 * 物流
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_request_logistics extends erpapi_wms_request_abstract
{
    /**
     * 获取物流公司
     *
     * @return void
     * @author 
     **/
    public function logistics_getlist($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'].'获取物流公司';

        return $this->__caller->call(WMS_LOGISTICS_COMPANIES_GET, null, null, $title, 10);
    }
}