<?php
/**
 * WMS 发货单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_selfwms_response_delivery  extends erpapi_wms_response_delivery 
{
    public function status_update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'发货单更新('.$params['status'].')';
        $this->__apilog['original_bn'] = $params['delivery_bn'];

        return $params;
    }
}
