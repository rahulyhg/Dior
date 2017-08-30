<?php
/**
 * rpc返回结果模拟类
 * @copyright  shopex.cn
 * @author ome 2011.4.8
 */
 
class ome_rpc_result{

    function __construct($response){
        $this->response = $response;
    }

    function set_callback_params($params){
        $this->callback_params = $params;
    }

    function get_callback_params(){
        return $this->callback_params;
    }

    function get_status(){
        return $this->response['rsp'];
    }

    function get_data(){
        return json_decode($this->response['data'],1);
    }

    function get_result(){
        return $this->response['res'];
    }

}
