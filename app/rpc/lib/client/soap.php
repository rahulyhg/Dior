<?php
/**
* webservice api 连接类
* @package rpc_client_soap
* @copyright www.shopex.cn
* @author Mr.dong 2011.7.7
*/
class rpc_client_soap implements rpc_interface_caller{

    /**
    * call调用方法
    * @access public
    * @param String $url 接口方api url
    * @param String $method 接口方api method
    * @param mixed $params 接口应用级参数
    * @param String $mode 连接模式：默认async异步,sync同步,service服务
    * @param Array $header 头部信息
    * @return
    */
    public function call($url,$method,$params=array(),$mode='async',$header=array()){
        try {
            ini_set('default_socket_timeout', $this->timeout);
            $client = new SoapClient($url,
                        array("trace" => 1, 
                              "exceptions" => 0,
                              //"login"=>'',
                              //"password"=>'',
                              "encoding" => 'UTF-8',
                              "connection_timeout"=>$this->timeout,
                        )
                      );
            $client->soap_defencoding = 'utf-8';
            $client->decode_utf8 = false;
            $client->xml_encoding = 'utf-8';
            $result = $client->__soapCall($method, array($params));

            #结果分析
            $rs = self::object_to_array($result);

        }catch(SoapFault $e){
             $rs = $e->faultstring;
        }
        return $rs;
    }

    /**
    * 对象转数组
    * @param Object $obj
    * @return Array 
    */
    private static function object_to_array($obj) 
    { 
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj; 
        foreach ($_arr as $key => $val) 
        { 
            $val = (is_array($val) || is_object($val)) ? self::object_to_array($val) : $val; 
            $arr[$key] = $val;
        } 
        return $arr; 
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
    public function set_format($format='xml'){
        $this->format = $format;
    }

}