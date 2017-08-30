<?php
/**
 * 淘管基础版
 *
 * @package default
 * @author 
 **/
class ome_tgservice_version_basic extends ome_tgservice_version_abstract implements ome_tgservice_version_interface{
    
    protected $release_version = 'tg';

    const VERSION = 'basic';

    public function install($params = array(),&$sass_params = array(),&$msg,&$is_callback = false){
        parent::install($params ,$sass_params ,$msg ,$is_callback);
        if(!empty($this->deploy_info['version'][self::VERSION])){
            foreach((array)$this->deploy_info['version'][self::VERSION] as $app){
                
                if(!app::get($app)->is_installed()){
                    $this->shell->exec_command(sprintf("install %s",$app));
                }
                
            }
        }
        return true;
    }
}
