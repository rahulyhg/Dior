<?php
/**
 * 转储单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_selfwms_response_stockdump extends erpapi_wms_response_stockdump
{
    public function quantity($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'转储单';
        $this->__apilog['original_bn'] = $params['stockdump_bn'];
        
        return $params;
    }
}
