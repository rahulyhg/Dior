<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/13
 * Time: 14:46
 */
class creditorderapi_api{
    public  $api_name = '';
    public  $api_method = '';
    public  $runtime='';
    public  $http_code='';
    public  $instance_data=array();

    public function rpc($request, $result_type='json')
    {
        $__key = md5(serialize(func_get_args()));
        if (isset($this->instance_data[$__key])){
            return $this->instance_data[$__key];
        }

        $method=empty($request['data'])?'GET':'POST';
        $request['method'] = $request['method'] ?:$method;
        $request_time = microtime(true);
        $response_data = $this->action($request);
        $this->runtime=microtime(true)-$request_time;
        $result = $this->result2array($response_data,$result_type);
        if (method_exists($this,'check_api_status')) {
            $status = call_user_func(array($this, 'check_api_status'), $result);
            $api_status = $status ?'success':'fail';
        } else {
            $api_status ='-';
        }
        //请求日志数据组装
        $data = array(
            'api_handler'=>'request',
            'api_name'=>$this->api_name(),
            'api_status'=>$api_status,
            'api_request_time'=>$request_time,
            'api_check_time' => time(),
            'http_runtime'=>$this->get_runtime(),
            'http_method'=>$request['method'],
            'http_response_status'=>$this->http_code,
            'http_url'=>$request['url'],
            'http_request_data'=>is_array($request['data']) ? $request['data'] : htmlspecialchars($request['data']),
            'http_response_data'=>$result,
            'sys_error_data'=>'NULL'
        );
        app::get('creditorderapi')->model('api_log')->save($data);
        if($api_status=='success'){
            $this->instance_data[$__key] = $result;
            return $result;
        }else{
            return false;
        }
    }


    public  function api_name()
    {
        return vsprintf('%s.%s', array($this->api_name,$this->api_method));
    }

    public function result2array($data,$type)
    {
        if(!empty($data)){
            switch ($type) {
                case 'json':
                    return json_decode($data, true);
                case 'xml':
                    return json_decode(json_encode(simplexml_load_string($data)), true);
            }
        }else{
           return false;
        }
    }

    public function get_runtime(){
        return sprintf('%.6f',$this->runtime);
    }

    //curl请求失败重试三次
    public function action($request,$retry=3){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$request['url']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($request['data']));
        $httpStatusCode='100';
        while($httpStatusCode!=200 && $retry--){
            $output=curl_exec($ch);
            $httpStatusCode=curl_getinfo($ch,CURLINFO_HTTP_CODE);
        }
        $this->http_code=$httpStatusCode;
        curl_close($ch);
        return $output;
    }

}