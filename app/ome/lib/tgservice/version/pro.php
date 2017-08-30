<?php
/**
 * 淘管旗舰版
 *
 * @package default
 * @author 
 **/
class ome_tgservice_version_pro extends ome_tgservice_version_abstract implements ome_tgservice_version_interface{
    
    protected $release_version = 'tg.pro';
    
    const VERSION = 'pro';

    public function install($params = array(),&$sass_params = array(),&$msg,&$is_callback = false){
        $res = parent::install($params ,$sass_params ,$msg, $is_callback);
        if(!empty($this->deploy_info['version'][self::VERSION])){
            foreach((array)$this->deploy_info['version'][self::VERSION] as $app){
                $this->shell->exec_command(sprintf("install %s",$app));
            }
        }

        return $res;
    }
}
