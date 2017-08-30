<?php
class wms_event_trigger_reship{

    /**
     *
     * 退货更新状态发起方法
     * @param array 
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function updateStatus($wms_id, $data, $sync = false){
        
        //kernel::single('middleware_wms_response', $wms_id)->reship_result($data);
        $result = kernel::single('erpapi_router_response')->set_channel_id($wms_id)->set_api_name('wms.reship.status_update')->dispatch($data);
    }

    /**
     *
     * 退货更新状态发起方法发起的响应接收方法
     * @param string $po_bn
     */
    public function updateStatus_callback($res){

    }

}

?>
