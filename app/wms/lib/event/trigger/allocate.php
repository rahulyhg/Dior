<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wms_event_trigger_allocate{

    /**
     * 请求控制台调拨入库事件
     */
    public function in_storage($wms_id, $data, $sync = false){
        
        //$result = kernel::single('middleware_wms_response', $wms_id)->stockin_result($data, $sync);
        return kernel::single('erpapi_router_response')->set_channel_id($wms_id)->set_api_name('wms.stockin.status_update')->dispatch($data);
    }

    /**
     * 请求控制台调拨出库事件
     */
    public function out_storage($wms_id, $data, $sync = false){
        //$result = kernel::single('middleware_wms_response', $wms_id)->stockout_result($data, $sync);
        return kernel::single('erpapi_router_response')->set_channel_id($wms_id)->set_api_name('wms.stockout.status_update')->dispatch($data);
    }

    /**
     *
     * 入库事件发起的响应接收方法
     * @param string $po_bn
     */
    public function in_storage_callback($res){

    }

    /**
     *
     * 出库事件发起的响应接收方法
     * @param string $po_bn
     */
    public function out_storage_callback($res){

    }
}

?>
