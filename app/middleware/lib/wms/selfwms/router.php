<?php
/**
* 业务接口处理分发
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_router{

    public function __construct(){
        $this->_abstract = kernel::single('middleware_wms_abstract');
    }

    /**
    * 接口请求分派
    * @param String $title 适配器接口标题
    * @param String $class 适配器接口类
    * @param String $method 适配器接口方法
    * @param Array $sdf 适配器接口参数
    * @param bool $sync 同异步类型:同步true,异步false
    * @return Array
    */
    public function request($title,$class,$method,$sdf,$sync=false){

        $rs = kernel::single($class)->$method($sdf,$sync);

        if($sync == false){
            #请求用户callback
            if(class_exists($this->callback_class)){
                if($_instance = kernel::single($this->callback_class)){
                    if(method_exists($_instance,$this->callback_method)){
                        $_instance->$this->callback_method($rs,$this->callback_params);
                    }
                }
            }
        }

        return $rs;
    }

}