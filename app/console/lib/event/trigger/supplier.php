<?php
class console_event_trigger_supplier {

    
    
    /**
    *供应商同步
    */
    public function create($wms_id, &$data, $sync = false){
        
        // $result = kernel::single('middleware_wms_request', $wms_id)->supplier_create($data, $sync);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->supplier_create($data);
        
        return $result;
    }
}