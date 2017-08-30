<?php
/**
 * 盘点
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_selfwms_response_inventory extends erpapi_wms_response_inventory
{
    public function add($params){
        $this->__apilog['title']       = $this->__channelObj->wms['channel_name'].'盘点';
        $this->__apilog['original_bn'] = $params['inventory_bn'];
        return $params;
    }
}
