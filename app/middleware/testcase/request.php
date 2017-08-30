<?php
class request{
    
    public $__branch_bn = 'stockhouse';
    
    function call($method,$params,$sync,$userCallback=array()){

        $wms_id = kernel::single('ome_branch')->getWmsId($this->__branch_bn);
        $wms_request_instance = kernel::single('middleware_wms_request',$wms_id);
        if(is_array($userCallback) && $userCallback){
            $wms_request_instance->setUserCallback($userCallback['callback_class'],$userCallback['callback_method'],$userCallback['callback_params']);
        }
        return $wms_request_instance->$method($params,$sync);
    }
    
}