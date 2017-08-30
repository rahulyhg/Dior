<?php
class wmsvirtual_rpc{

    private $_instance = '';

    public function setUserCallback($callback_class,$callback_method,$callback_params=null){
        $this->callback_class = $callback_class;
        $this->callback_method = $callback_method;
        $this->callback_params = $callback_params;
        return $this;
    }

    
    public function response($method,$params){
        if(!$this->_instance){
            
            $this->_instance = kernel::single('wmsvirtual_response');
        }
        return $this->_instance->call($method,$params);
    }

}
