<?php
/**
* 函数库
*/
class middleware_wms_selfwms_func{
    
    /**
    * 获取适配器信息
    * @access public
    * @return Array
    */
    public function getAdapter(){
        if(app::get('wms')->is_installed()){
            $content = include APP_DIR.'/middleware/lib/wms/selfwms/config.php';
            return $content['adapter'];
        }
        return NULL;
    }

}