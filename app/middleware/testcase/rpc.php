<?php
class rpc{

    private $_instance = '';

    public function setUserCallback($callback_class,$callback_method,$callback_params=null){
        $this->callback_class = $callback_class;
        $this->callback_method = $callback_method;
        $this->callback_params = $callback_params;
        return $this;
    }

    public function request($method,$params,$sync=false){
        include 'request.php';
        $instance = new request();
        $userCallback = array($this->callback_class,$this->callback_method,$this->callback_params);
        return $instance->call($method,$params,$sync,$userCallback);
    }

    public function selfwms_response($branch_bn,$method,$params){
        $wms_id = kernel::single('ome_branch')->getWmsId($branch_bn);
        $wms_request_instance = kernel::single('middleware_wms_response',$wms_id);
        return $wms_request_instance->$method($params);
    }

    public function response($method,$params){
        if(!$this->_instance){
            include 'response.php';
            $this->_instance = new response();
        }
        return $this->_instance->call($method,$params);
    }

}
return new rpc();