<?php
/**
* fsockopen 连接类
* @package rpc_client_fsockopen
* @copyright www.shopex.cn
* @author Mr.dong 2011.7.26
*/
class rpc_client_fsockopen implements rpc_interface_caller{

    public $timeout = 10;

    /**
    * call调用方法
    * @access public
    * @param String $url 接口方api url
    * @param String $method 接口方api method
    * @param mixed $params 接口应用级参数
    * @param String $mode 连接模式：默认async异步,sync同步,service服务
    * @param Array $headers 头部信息
    * @return
    */
    public function call($url,$method,$params=array(),$mode='async',$headers=array()){

         $core_http = kernel::single('base_httpclient');
         $response = $core_http->set_timeout($this->timeout)->post($url,$params,$headers);
         if($response === HTTP_TIME_OUT){
             return false;
         }else{
             return $response;
         }
    }

    /**
    * 设置api通信异步返回处理
    * @access public
    * @param String $callback_class 异步返回类
    * @param String $callback_method 异步返回方法
    * @param Array $callback_params 异步返回参数
    * @return 当前实例
    */
    public function set_callback($callback_class,$callback_method,$callback_params=null){
        $this->callback_class = $callback_class;
        $this->callback_method = $callback_method;
        $this->callback_params = $callback_params;
    }

    /**
    * 设置api通信超时时间
    * @access public
    * @param Number $timeout 超时时间，单位:秒
    * @return 当前实例
    */
    public function set_timeout($timeout=10){
        $this->timeout = $timeout;
    }

    /**
    * 设置api通信版本
    * @access public
    * @param String $version api接口版本号
    * @return 当前实例
    */
    public function set_version($version='1.0'){
        $this->version = $version;
    }

    /**
    * 设置api通信数据传递格式
    * @access public
    * @param String $format 数据格式
    * @return 当前实例
    */
    public function set_format($format='json'){
        $this->format = $format;
    }

}