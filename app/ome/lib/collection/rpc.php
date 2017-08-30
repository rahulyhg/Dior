<?php
/**
* Collection 请求基类
* @author chenjun 2013.04.15
* @copyright shopex.cn
*/
class ome_collection_rpc{
    public $api;
    public $node_id;
    public $domain;


    public function __construct(){
        set_time_limit(0);
        $node_id = base_shopnode::node_id('ome');
        $this->node_id = $node_id;
        $this->domain = 'http://'.kernel::request()->get_host();
    }

    public function http($url = '',$time_out = '1',$query_params = array()){
        $http = kernel::single('base_httpclient');
        $response = $http->set_timeout($time_out )->post($url,$query_params,$headers);
        $data = json_decode($response,true);

        #记录请求日志与返回结果
        $log = date('Y-m-d H:i:s').' '.$url."\n";
        $log .= "Request:".json_encode($query_params)."\n";
        $log .= "Result:".json_encode($data)."\n\n";
        $this->log($log);

        return $data;
    }

    public function sign($api_url='',$params = array(),$api_method='post'){
        include_once APP_DIR.'/ome/statics/lib/oauth.php';
        error_reporting(0);
        @session_start();
        $config = array(
            'key' => 'AN5FUL',
            'secret' => '97D1K7AY78Z3AH06H16N',
            'site' => 'http://openapi.ishopex.cn',
            'oauth' => 'http://oauth.ishopex.cn',
        );
        $prism = new oauth2($config);
        $signArray = @$prism->sign($api_method, $api_url,$params,$this->get_timestamp());
        return http_build_query($signArray);
    }

    public function get_timestamp(){
        $http = kernel::single('base_httpclient');
        $response = $http->set_timeout(5)->post('http://openapi.ishopex.cn/api/platform/timestamp',$query_params,$headers);
        return $response;
    }

    public function log($content = ''){
        $date = date('Ymd');
        $logDir = ROOT_DIR.'/script/update/logs';
        error_log($content,3,$logDir.'/collections_'.$date.'.txt');
    }

}