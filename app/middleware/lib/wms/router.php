<?php
/**
* 路由分发
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_router{

    public $_wms_id = NULL;#wms_id
    public $_adapter = NULL;
    public $_default_limit = 100;

    public function __construct(){
        $this->_abstract = kernel::single('middleware_wms_abstract');
    }

    /**
    * 发起
    * @param String $method 适配器接口方法
    * @param Array $sdf 适配器接口参数
    * @param bool $sync 同异步类型:同步true,异步false
    * @return
    */
    public function request($method,$sdf,$sync=false){

        #适配器通用过滤
        if(!$this->__AdapterFilter($msg)){
            return $this->_abstract->msgOutput('fail',$msg);
        }

        #传递节点号
        if($node_id = kernel::single('middleware_adapter')->getNodeIdByWmsId($this->_wms_id)){
            if(method_exists($this->_adapter,'setNodeId')){
                $this->_adapter->setNodeId($node_id);
            }
        }

        #设置用户callback
        if($this->callback_class && $this->callback_method){
            if(method_exists($this->_adapter,'setUserCallback')){
                $this->_adapter->setUserCallback($this->callback_class,$this->callback_method,$this->callback_params);
            }
        }

        #通用参数转换
        self::requestFormatParams($sdf);

        #调用适配器接口方法
        if(method_exists($this->_adapter,$method)){
            return $this->_adapter->$method($sdf,$sync);
        }else{
            return $this->_abstract->msgOutput('fail','Adapter method '.$method.' NOT EXISTS');
        }
    }

    /**
    * 接收
    * @param String $method 适配器接口方法
    * @param Array $params 适配器接口参数
    * @return
    */
    public function response($method,$params){

        if(!$this->__AdapterFilter($msg)){
            return $this->_abstract->msgOutput('fail',$msg);
        }

        #参数过滤
        if(!$this->__paramsFilter($method,$params,$msg,$msg_code)){
            return $this->_abstract->msgOutput('fail',$msg,$msg_code);
        }

        #传递节点号
        if($node_id = kernel::single('middleware_adapter')->getNodeIdByWmsId($this->_wms_id)){
            if(method_exists($this->_adapter,'setNodeId')){
                $this->_adapter->setNodeId($node_id);
            }
        }

        #通用参数转换
        $this->responseFormatParams($params);

        #调用适配器接口方法
        if(method_exists($this->_adapter,$method)){
            return $this->_adapter->$method($params);
        }else{
            return $this->_abstract->msgOutput('fail','Adapter method '.$method.' NOT EXISTS');
        }
    }

    /**
    * 实例化WMS适配器
    * @param String $adapter_api_name 适配器接口名
    * @param String $wms_id 仓储ID
    * @return
    */
    public function getAdapter($adapter_api_name,$wms_id=''){
        $_adapter_instance = NULL;
        $default_adapter = sprintf('middleware_wms_default%sAdapter',ucfirst($adapter_api_name));

        if($wms_id){
            $adapter_name = kernel::single('middleware_adapter')->getWmsById($wms_id);
            $adapter_class_name = sprintf('middleware_wms_%s_%s',$adapter_name,$adapter_api_name);
            try{
                if(class_exists($adapter_class_name)){
                    $_adapter_instance = kernel::single($adapter_class_name);
                }
            }catch(Exception $e){
                //
            }
        }
        return is_object($_adapter_instance) && !IS_NULL($_adapter_instance) ? $_adapter_instance : kernel::single($default_adapter);
    }

    /**
    * 分析接口分页数据
    */
    public function page_request($method,&$sdf=array(),$sync=false){
        if(empty($sdf)) return NULL;

        $limit = $this->_adapter->{$method.'_limit'};
        $limit = $limit ? $limit : $this->_default_limit;
        $total_page = ceil(count($sdf)/$limit);
        for($page=1;$page<=$total_page;$page++){
            $offset = ($page-1)*$limit;
            $page_sdf = array();
            for($key=$offset;$key<$offset+$limit;$key++){
                if(!isset($sdf[$key])) break;
                $page_sdf[] = $sdf[$key];
            }
            $rs = $this->request($method,$page_sdf,$sync);
        }
        return $rs;
    }

    /** 
    * 参数过滤
    * @access private
    */
    private function __paramsFilter($method,&$params,&$msg,&$msg_code){

        $method = explode('_',$method);
        $filter_class_name = sprintf('middleware_wms_params_%s',$method[0]);
        if(middleware_func::class_exists($filter_class_name) && $fileter_instance = kernel::single($filter_class_name)){
            if(method_exists($fileter_instance,$method[1])){
                if(!$fileter_instance->$method[1]($params,$msg,$msg_code)){
                    return false;
                }
            }
        }

        return true;
    }

    /** 
    * 适配器初始化检查
    * @access private
    */
    private function __AdapterFilter(&$msg){

        #绑定判断
        if ( !kernel::single('middleware_adapter')->isBind($this->_wms_id) ){
            $msg = 'wms not bind';
            return false;
        }
        return true;
    }

    /**
    * 发起参数格式化
    */
    private static function requestFormatParams(&$sdf){
        if(isset($sdf['create_time'])){
            $sdf['create_time'] = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);
        }
    }

    /**
    * 接收参数格式化
    */
    private function responseFormatParams(&$params){
        #wms信息
        $params['wms_id'] = $this->_wms_id;
    }

}