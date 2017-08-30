<?php
/**
 * 退货单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_selfwms_response_reship extends erpapi_wms_response_reship
{
    public function status_update($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'退货';
        $this->__apilog['original_bn'] = $params['reship_bn'];

        return $params;
    }
}
