<?php
interface rpc_interface_caller{
    
    public function call($url,$method,$params=array(),$mode='async',$header=array());
    public function set_callback($callback_class,$callback_method,$callback_params=null);
    public function set_timeout($timeout=1);
    public function set_version($version='1.0');
    public function set_format($format='json');

}