<?php
/**
* 接口日志类
* @author chenjun 2013.01.21
* @copyright shopex.cn
*/
class ome_apilog_rpc{
    public $api;
    public $node_id;
    public $domain;
    public $server_vesion = '1.0';
    private $apiTimeout = array(
        'tg.api.insert' => 1,
        'tg.api.update' => 1,
        'tg.api.count' => 5,
        'tg.api.query' => 5,
        'tg.api.detail' => 5,
    );

    public function __construct(){
        set_time_limit(0);
        $node_id = base_shopnode::node_id('ome');
        $this->node_id = $node_id;
        $this->domain = 'http://'.kernel::request()->get_host();
    }

    /**
    * 发起
    * @access public
    * @param array $filter
    * @return mixed
    */
    public function request($api = '',$api_url,$params = array()){
        $system = array(
            'api' => $api,
            'node_id' => $this->node_id,
            'domain' => $this->domain,
            'server_vesion' => $this->server_vesion,
        );
        $query_params = array_merge($system,$params);
        $time_out = $this->apiTimeout[$api];
        $response = $this->http($api_url,$time_out,$query_params);
        return $response;
    }


    public function http($url = '',$time_out = '1',$query_params = array()){
        $http = kernel::single('base_httpclient');
        $response = $http->set_timeout($time_out )->post($url,$query_params,$headers);
        $data = json_decode($response,true);
        return $data;
    }



}