<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/13
 * Time: 10:15
 */
class creditorderapi_service{
    private $public_params=array('sign','random','version','method','app_key','format','timestamp');
    private $request_time='';  //系统接收请求时间
    private $check_time='';    //请求携带的时间戳用于校验
    private $format='';
    private $method='';
    private $request_data=array();
    public $code_list=array(
        '200'=>'Success',  //成功
        '401'=>'Time out', //请求超时
        '402'=>'Sign error', //签名错误
        '403'=>'Missing system parameters',  //缺少系统参数
        '404'=>'Method does not exist',   //方法不存在
        '405'=>'Server busy',  //服务器繁忙
        '300'=>'Custom error', //自定义错误(业务逻辑相关)
        '500'=>'Server error', //服务器错误
    );
    public function __init(){
        ignore_user_abort();
        set_time_limit(0);
        if(!kernel::is_online()){
            die('error');
        }else{
            require(ROOT_DIR.'/config/config.php');
            @include(APP_DIR.'/base/defined.php');
        }
    }
    public function process(){
        $this->__init();
        $this->request_time=microtime(true);
        $request_data=$this->get_params();
        list($obj,$method,$params)=$this->parse_request($request_data);
//        kernel::database()->beginTransaction();

        call_user_func(array($obj,$method),$params,$this);
    }
    private function get_params(){
        $method=$_SERVER['REQUEST_METHOD'];
//        if($method=='GET'){
//            $data=$_REQUEST;
//        }elseif($method=='POST'){
//            $data=json_decode($GLOBALS['HTTP_RAW_POST_DATA'],true);
//        }
        $data=$_REQUEST;
        $request_data=$this->check_security($data);
        $this->request_data=$request_data;
        return $request_data;
    }
    private function check_security($data){
        $this->method=$data['method'];
        //无效url拼接访问直接返回404
        if(!isset($data['method']) || !$data){
            header("HTTP/1.1 404 Not Found");
            exit;
        }
        return $data;
    }
    private function parse_request($params){
        if(!$this->check_public_params($params)){
            $this->error('403');
        }
        if(!$this->check_time($params)){
            $this->error('401');
        }
        if(!$this->check_sign($params)){
            $this->error('402');
        }
        list($public_params,$user_params)=$this->split_service_params($params);
        list($api_name,$api_method)=$this->build_service_method($public_params);
        
        if(strpos($api_name,'lvmh')!==false){
            $api_name = str_replace('lvmh','creditorderapi',$api_name);
        }
        $object=kernel::service($api_name);
        if(!is_object($object)){
            $this->error('404');
        }
        if(!method_exists($object,$api_method)){
            $this->error('404');
        }
        return array($object,$api_method,$user_params);
    }
    //检查公共参数
    private function check_public_params($params){
        foreach($this->public_params as $key){
            if(!isset($params[$key])){
                return false;
            }
        }
        return true;
    }
    private function check_time($params){
        $time_offset=60*5;  //超时时间300秒
        $this->check_time=$params['timestamp'];
        $request_time=$_SERVER['REQUEST_TIME'];
        $max_time=$request_time+$time_offset;
        $min_time=$request_time-$time_offset;
        if($params['timestamp']<$max_time && $params['timestamp']>$min_time){
            return true;
        }
        return false;
    }
    private function check_sign($params){
        $__sign=$params['sign'];
        unset($params['sign']);
        $sign_str=$this->get_sign_str($params);
        $secret_key=$this->get_secret_key($params['app_key']);
        $sign_str.=$secret_key;
        $sign=strtoupper(md5($sign_str));
        return $sign===$__sign;
    }
    private function get_sign_str($params){
        $sign_str='';
        ksort($params,2);
        foreach($params as $key=>$val){
            if($val==''){
                continue;
            }
            $sign_str.=$key.'='.$val.'&';
        }
        return $sign_str;
    }
    //获取指定来源的secret_key
    private function get_secret_key($app_key){
        //$secret_key=app::get('ome')->model('shop')->getList('secret_key',array('shop_bn'=>$app_key));
        $shopMdl  = app::get('ome')->model('shop');
        $shopInfo = $shopMdl->getList('*',array('shop_bn'=>$app_key));
        if(!empty($shopInfo)){
            $sql = "SELECT * FROM sdb_creditorderapi_apiconfig WHERE shop_id LIKE '%".$shopInfo['0']['shop_id']."%'";
            $secret_key = app::get('creditorderapi')->model('apiconfig')->db->select($sql);
            return $secret_key[0]['secret_key'];
        }
        return null;
        
    }
    //剥离公共参数和业务参数
    private function split_service_params($params){
        $public_params=array();
        $this->format=$params['format'];
        foreach($this->public_params as $key){
            $public_params[$key]=$params[$key];
            unset($params[$key]);
        }
        return array($public_params,$params);
    }
    //解析请求方法
    private function build_service_method($params){
        $request_method=$params['method'];
        $request_version=$params['version'];
        $args=explode('.',$request_method);
        $api_name=implode(array_slice($args,0,2),'.');
        $api_method=implode(array_slice($args, 2),'_');
        $api_version=str_replace('.','_',$request_version);
        return array(
            strtolower("{$api_name}"),
            strtolower("{$api_method}_{$api_version}")
        );
    }
    //调用失败时响应方法
    public function error($code,$message='',$data=''){
        $this->response($code,$message,$data);
    }
    //调用成功时响应方法
    public function response($code,$__message,$data='',$error=''){
        $message=empty($__message)?$this->code_list[$code]:$__message;
        //封装响应数据
        $result_root='response';
        $result=array(
            'code'=>$code,
            'message'=>$message,
            'data'=>$data,
        );
        $format=$this->get_result_format();
        switch($format){
            case 'json':
                $__result=json_encode($result);
                break;
            case 'xml':
                $__result=kernel::single('creditorderapi_tools_array2xml')->array2xml($result,$result_root);
        }
        echo $__result;
        //组装日志数据
        if($code=='200'){
//            kernel::database()->commit();
            $api_status='success';
        }else{
//            kernel::database()->rollBack();
            $api_status='fail';
        }
        $log_data=array(
            'api_handler'=>'response',
            'api_name'=>$this->method,
            'api_status'=>$api_status,
            'api_request_time'=>$this->request_time,
            'api_check_time' => $this->check_time,
            'http_runtime'=>$this->get_runtime(),
            'http_method'=>$_SERVER['REQUEST_METHOD'],
            'http_response_status'=>'200',
            'http_url'=>htmlspecialchars($_SERVER['REQUEST_URI']),
            'http_request_data'=>$this->request_data,
            'http_response_data'=>$result,
            'sys_error_data'=>!empty($error)?$error:'NULL',
        );
        app::get('creditorderapi')->model('api_log')->save($log_data);
        exit;
    }
    //获取响应数据类型
    private function get_result_format(){
        $format=strtolower($this->format);
        if (!in_array($format, array('json','xml'))) {
            $format = 'json';
        }
        return $format;
    }
    //计算执行时间
    private function get_runtime(){
        return sprintf('%.6f',microtime(true)-$this->request_time);
    }

}