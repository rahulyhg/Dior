<?php
/**
* 矩阵api 连接类
* @package rpc_client_matrix
* @copyright www.shopex.cn
* @author Mr.dong 2011.7.7
*/
class rpc_client_matrix implements rpc_interface_caller{

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
        if (isset($params['Content-Encoding']) && $params['Content-Encoding'] == 'gzip'){
            $gzip = true;
        }
        if (isset($params[1]['task'])){
            $rpc_id = $params[1]['task'];
        }
        $async = $mode == 'async' || empty($mode) ? true : false;
        
        $this->network_id = '1';
        if ($async == true){// 异步
            $result = $this->async_call($method, $params, $rpc_id, $gzip);
            $response = $this->rpc_response ? json_decode($this->rpc_response,1) : '';
            if ($response){
                $msg_id = $response['msg_id'];
                $err_code = $response['res'];
                $err_msg = $response['err_msg'];
                $data = is_array($response['data']) ? $response['data'] : json_decode($response['data'],true);
            }
            if($result === true){
                $status = 'running';
            }else{
                $status = 'fail';
            }
            $rs = array_merge(rpc_func::msgOutput($status, $err_msg,$err_code,$data),array('msg_id'=>$msg_id,'err_msg'=>$err_msg,'err_code'=>$err_code));
            return $rs;
        }else{// 同步
            $result = $this->realtime_call($method, $params, $gzip);
            if ($result && is_array($result)){
                $msg_id = $result['msg_id'];
                $err_code = $result['res'];
                $err_msg = $result['err_msg'];
                $data = is_array($result['data']) ? $result['data'] : json_decode($result['data'],true);
            }
            if(isset($result['rsp']) && $result['rsp'] == 'succ'){
                $status = 'success';
            }else{
                $status = 'fail';
                if ($callerObj->status == RPC_RST_RUNNING){
                    $err_code = 'time_out';
                    $err_msg = '请求超时';
                }
            }
            $rs = array_merge(rpc_func::msgOutput($status, $err_msg,$err_code, $data),array('msg_id'=>$msg_id,'err_msg'=>$err_msg,'err_code'=>$err_code));
            return $rs;
        }
    }

    /**
    * 实时接口
    */
    public function realtime_call($method, $params, $gzip = false, $mode='sync') {

        $headers = array(
            'Connection' => $this->timeout,
        );
        if ($gzip) {
            $headers['Content-Encoding'] = 'gzip';
        }

        $query_params = array(
            'app_id' => 'ecos.' . $this->app->app_id,
            'method' => $method,
            'date' => date('Y-m-d H:i:s'),
            'format' => 'json',
            'certi_id' => base_certificate::certi_id(),
            'v' => $this->version,
            'from_node_id' => base_shopnode::node_id($this->app->app_id),
        );

        $query_params = array_merge((array) $params, $query_params);
        $query_params['sign'] = base_certificate::gen_sign($query_params);

        $url = $this->get_url($this->network_id);
        $tmp_url = explode('/',$url);
        $tmp_url2 = array_pop($tmp_url);
        if(empty($tmp_url2)){
            $url .= 'sync';
        }else{
            $url .= '/sync';
        }

        $core_http = kernel::single('base_httpclient');

        $response = $core_http->set_timeout($this->timeout)->post($url, $query_params, $headers);
        if ($response === HTTP_TIME_OUT) {
            return false;
        } else {
            $result = json_decode($response,1);
            if ($result) {
                return $result;
            } else {
                return false;
            }
        }
    }

    /**
    * 异步
    */
    public function async_call($method, $params, $rpc_id = null, $gzip = false) {
        $serviceObj = kernel::single('rpc_service');
        if (is_null($rpc_id)){
            $rpc_id = $serviceObj->rpc_id($this->callback_class,$this->callback_method,$this->callback_params);
        }else{
            $rpc_id = $serviceObj->rpc_id($this->callback_class,$this->callback_method,$this->callback_params,$rpc_id);
        }
        $obj_rpc_poll = app::get('base')->model('rpcpoll');
        $headers = array(
            'Connection'=>$this->timeout,
        );
        if($gzip){
            $headers['Content-Encoding'] = 'gzip';
        }

        $query_params = array(
            'app_id'=> 'ecos.'.$this->app->app_id,
            'method'=> $method,
            'date'=> date('Y-m-d H:i:s'),
            'callback_url'=> kernel::single('rpc_service')->callback_url($this->callback_class,$this->callback_method,$this-> callback_params,$rpc_id).'/app_id/'.$this->app->app_id ,
            'format'=> 'json',
            'certi_id'=> base_certificate::certi_id(),
            'v'=> $this->version,
            'from_node_id' => base_shopnode::node_id($this->app->app_id),
        );
        
        // rpc_id 分id 和 calltime
        $arr_rpc_key = explode('-', $rpc_id);
        $rpc_id = $arr_rpc_key[0];
        $rpc_calltime = $arr_rpc_key[1];
        $query_params['task'] = $rpc_id;
        $query_params = array_merge((array)$params,$query_params);
        if (!base_shopnode::token($this->app->app_id))
            $query_params['sign'] = base_certificate::gen_sign($query_params);
        else
            $query_params['sign'] = base_shopnode::gen_sign($query_params,$this->app->app_id);

        $url = $this->get_url($this->network_id);
        $tmp_url = explode('/',$url);
        $tmp_url2 = array_pop($tmp_url);
        if(empty($tmp_url2)){
            $url .= 'async';
        }else{
            $url .= '/async';
        }

        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout($this->timeout)->post($url,$query_params,$headers);

        kernel::log('Response: '.$response);
        if($this->callback_class && method_exists(kernel::single($this->callback_class), 'response_log')){
            $response_log_func = 'response_log';
            $callback_params = $this->callback_params ? array_merge($this->callback_params, array('rpc_key'=>$rpc_id.'-'.$rpc_calltime)) : array('rpc_key'=>$rpc_id.'-'.$rpc_calltime);
            kernel::single($this->callback_class)->$response_log_func($response, $callback_params);
        }

        if($response===HTTP_TIME_OUT){
            $headers = $core_http->responseHeader;
            kernel::log('Request timeout, process-id is '.$headers['process-id']);
            $obj_rpc_poll->update(array('process_id'=>$headers['process-id'])
                ,array('id'=>$rpc_id,'calltime'=>$rpc_calltime,'type'=>'request'));
            $this->status = RPC_RST_RUNNING;
            return false;
        }else{
            $result = json_decode($response);
            if($result){
                $this->error = $response->error;
                switch($result->rsp){
                case 'running':
                    $this->status = RPC_RST_RUNNING;
                    // 存入中心给的process-id也就是msg-id
                    $obj_rpc_poll->update(array('process_id'=>$result->msg_id),array('id'=>$rpc_id,'type'=>'request','calltime'=>$rpc_calltime));
                    return true;

                case 'succ':
                    //$obj_rpc_poll->delete(array('id'=>$rpc_id,'calltime'=>$rpc_calltime,'type'=>'request','fail_times'=>1));
                    $obj_rpc_poll->delete(array('id'=>$rpc_id,'calltime'=>$rpc_calltime,'type'=>'request','fail_times'=>1));
                    $this->status = RPC_RST_FINISH;
                    $method = $this->callback_method;
                    if ($method && $this->callback_class)
                        kernel::single($this->callback_class)->$method($result->data);
                    $this->rpc_response = $response;
                    return $result->data;

                case 'fail':
                    $this->error = 'Bad response';
                    $this->status = RPC_RST_ERROR;
                    $this->rpc_response = $response;
                    return false;
                }
            }else{
                //error 解码失败
            }
        }
    }

    /**
    * 获取矩阵请求地址
    * @param String $node 节点
    * @return String
    */
    private function get_url($node){
        $row = app::get('base')->model('network')->getlist('node_url,node_api', array('node_id'=>$this->network_id));
        if($row){
            if(substr($row[0]['node_url'],-1,1)!='/'){
                $row[0]['node_url'] = $row[0]['node_url'].'/';
            }
            if($row[0]['node_api']{0}=='/'){
                $row[0]['node_api'] = substr($row[0]['node_api'],1);
            }
            $url = $row[0]['node_url'].$row[0]['node_api'];
        }
        return $url;
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

    /**
    * 设置访问APP
    * @access public
    * @param String $app 调用者的app名称
    * @return 当前实例
    */
    public function set_app($app='ome'){
        $this->app = &app::get($app);
    }

}