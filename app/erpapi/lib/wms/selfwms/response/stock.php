<?php
/**
* 库存对账
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class erpapi_wms_selfwms_response_stock extends erpapi_wms_response_stock
{
    public function quantity($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'] . '库存对帐';   
        $this->__apilog['original_bn'] = $data['batch'];

        return $params;
    }
}
