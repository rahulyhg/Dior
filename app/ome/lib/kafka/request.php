<?php
/**
 * Created by PhpStorm.
 * User: august.yao
 * Date: 2018/07/30
 * Time: 14:46
 */
class ome_kafka_request{

    public $api_name      = '';     // 接口地址
    public $api_method    = '';     // 接口名称
    public $runtime       = '';     // 调用接口执行时间
    public $http_code     = '';     // 返回状态码
    public $instance_data = array();// 临时实列对象

    /**
     * 发起请求
     * @param $request 请求数据
     * @param string $result_type 数据格式
     * @param int $apiLogId
     * @return bool|mixed
     */
    public function rpc($request, $result_type = 'json', $apiLogId = 0){
        // 查看是否有请求记录
        $__key = md5(serialize(func_get_args()));
        if (isset($this->instance_data[$__key])){
            return $this->instance_data[$__key];
        }

        $request_time  = microtime(true);   // 请求开始时间
        $response_data = $this->action($request);
        $this->runtime = microtime(true) - $request_time;   // 预估请求时间
        // 数据处理
        $result = $this->result2array($response_data, $result_type);
        // 判断返回状态
        if (method_exists($this, 'check_api_status')) {
            $status = call_user_func(array($this, 'check_api_status'), $result);
            $api_status = $status ? 'success' : 'fail';
        } else {
            $api_status = '-';
        }
        // 请求日志数据组装
        $data = array(
            'api_handler'          => 'request',
            'api_name'             => $this->api_name(),
            'api_status'           => $api_status,
            'api_request_time'     => $request_time,
            'api_check_time'       => time(),
            'http_runtime'         => $this->get_runtime(),
            'http_method'          => $request['method'],
            'http_response_status' => $this->http_code,
            'http_url'             => $request['url'],
            'http_request_data'    => is_array($request['data']) ? $request['data'] : htmlspecialchars($request['data']),
            'http_response_data'   => $result,
            'sys_error_data'       => 'NULL'
        );

        // 判断是否为重发
        if($apiLogId){
            $log_data = app::get('ome')->model('kafka_api_log')->dump(array('id' => $apiLogId), 'repeat_num');
            $data['id'] = $apiLogId;
            $data['repeat_num'] = $log_data['repeat_num'] + 1;
        }

        app::get('ome')->model('kafka_api_log')->save($data);
        if($api_status == 'success'){
            $this->instance_data[$__key] = $result;
            return $result;
        }else{
            return $result;
        }
    }

    /**
     * 返回完整请求地址
     * @return string
     */
    public function api_name(){
        return vsprintf('%s.%s', array($this->api_name, $this->api_method));
    }

    /**
     * 接口返回数据处理
     * @param $data 数据
     * @param $type 数据类型
     * @return bool|mixed
     */
    public function result2array($data, $type){
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
        return sprintf('%.6f', $this->runtime);
    }

    /**
     * curl-post请求失败重试三次
     * @param $request
     * @param int $retry
     * @return mixed
     */
    public function action($request, $retry = 3){

        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json","Cache-Control: no-cache",
            "Pragma: no-cache",
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request['url']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request['data']));
//        $httpStatusCode = '100';
//        while($httpStatusCode != 200 && $retry--){
            $output = curl_exec($ch);
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        }
        $this->http_code = $httpStatusCode;
        curl_close($ch);
        return $output;
    }
}