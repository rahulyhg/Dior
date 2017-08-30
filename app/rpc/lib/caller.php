<?php
/**
* api请求通信方式类
* @package rpc_caller
* @copyright www.shopex.cn
* @author Mr.dong 2013.4.17
*/
class rpc_caller{

    private static $_caller_instance;
    private static $type;

    /**
    * 设置api通信类型
    * @access public
    * @param String $type 通信类型
    * @return 当前实例
    */
    public function conn($type='matrix'){
        self::$type = $type;
        if ( !isset(self::$_caller_instance[self::$type]) || !self::$_caller_instance[self::$type] ){
            $class_name = sprintf('rpc_client_%s',$type);
            try{
                $conn_instance = kernel::single($class_name);
                if ($conn_instance instanceof rpc_interface_caller){
                    self::$_caller_instance[self::$type] = $conn_instance;
                }else{
                    trigger_error($conn_instance.' must be implements rpc_interface_caller', E_USER_ERROR);
                }
            }catch(Exception $e){
                trigger_error($type.' request method NOT FOUND', E_USER_ERROR);
            }
        }
        return $this;
    }

    /**
    * api通信call调用方法
    * @access public
    * @param String $url 接口方api url
    * @param String $method 接口方api method
    * @param mixed $params 接口应用级参数
    * @param String $mode 连接模式：默认async异步,sync同步,service服务
    * @param Array $header 头部信息
    * @return
    */
    public function call($url,$method,$params=array(),$mode='async',$header=array()){
        if (empty($method) || empty($params)){
            trigger_error('method or params can not empty', E_USER_ERROR);
        }
        
        $async = $mode == 'async' || empty($mode) ? true : false;

        return self::$_caller_instance[self::$type]->call($url,$method,$params,$mode,$header);
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
        self::$_caller_instance[self::$type]->set_callback($callback_class,$callback_method,$callback_params);
        return $this;
    }

    /**
    * 设置api通信超时时间
    * @access public
    * @param Number $timeout 超时时间，单位:秒
    * @return 当前实例
    */
    public function set_timeout($timeout='1'){
        self::$_caller_instance[self::$type]->set_timeout($timeout);
        return $this;
    }

    /**
    * 设置api通信版本
    * @access public
    * @param String $version api接口版本号
    * @return 当前实例
    */
    public function set_version($version='1'){
        self::$_caller_instance[self::$type]->set_version($version);
        return $this;
    }

    /**
    * 设置api通信数据传递格式
    * @access public
    * @param String $format 数据格式
    * @return 当前实例
    */
    public function set_format($format='json'){
        self::$_caller_instance[self::$type]->set_format($format);
        return $this;
    }

    /**
    * 设置调用来源APP
    * @access public
    * @param String $app 调用者的app名称
    * @return 当前实例
    */
    public function set_app($app='ome'){
        self::$_caller_instance[self::$type]->set_app($app);
        return $this;
    }

}