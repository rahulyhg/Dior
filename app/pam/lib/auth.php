<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class pam_auth{

    private $account;
    static $instance = array();

    function __construct($type){
        $this->type = $type;
    }

    static function instance($type){
        if(!isset(self::$instance[$type])){
            self::$instance[$type] = new pam_auth($type);
        }
        return self::$instance[$type];
    }
    
    public function set_appid($appid){
        $this->appid = $appid;
    }
    function account(){
        if(!$this->account){
            $this->account = new pam_account($this->type);
        }
        return $this->account;
    }

    function get_name($module){
        return app::get('pam')->getConf('module.name.'.$module);
    }

    function is_module_valid($module,$app_id='b2c'){
        $obj = kernel::single($module);
        $config =$obj->get_config();
        $type = $app_id==='desktop' ? 'shopadmin':'site';
        return $config[$type.'_passport_status']['value'] == 'true' ?  true : false;
    }

    function get_callback_url($module){
        return kernel::openapi_url('openapi.pam_callback','login',array('module'=>$module,'type'=>$this->type,'appid' => $this->appid,'redirect'=>$this->redirect_url));
    }

    function set_redirect_url($url){
        $this->redirect_url = $url;
    }
    
    function is_enable_vcode(){
        if(!class_exists($this->appid.'_service_vcode'))
            return false;
        $vcode = kernel::single($this->appid.'_service_vcode');
        return $vcode->status();
        return false;
    }

}
