<?php
interface ome_tgservice_version_interface{
    
    public function install($params = array(),&$sass_params = array(),&$msg);

    public function update($params = array(),&$sass_params = array(),&$msg);
    
    public function main($operation = 'install',$params = array(),&$msg = '',$obj);

    public function callback_tosass($data = array());
}