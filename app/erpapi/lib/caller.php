<?php
class erpapi_caller{

    /**
     * CONFIG
     *
     * @var string
     **/
    private $__config = null;

    /**
     * RESULT
     *
     * @var string
     **/
    private $__result = null;

    public function set_config(erpapi_config $config)
    {
        $this->__config = $config;

        return $this;
    }

    public function set_result(erpapi_result $result)
    {
        $this->__result = $result;

        return $this;
    }


    /**
     * 异步请求
     *
     * @return void
     * @author 
     **/
    public function call($method,$params,$callback=array(),$title='',$time_out=10,$primary_bn='')
    {
        // 白名单
        if (false === $this->__config->whitelist($method)) {
            $response['rsp']       = 'fail';
            $response['err_msg']   = '接口被禁止';
            $response['res_ltype'] = 1;

            return $response;
        }

        // 记日志
        $apilogModel = app::get('ome')->model('api_log');
        $log_id = $apilogModel->gen_id();

        if ($callback['class'] && $callback['method']) {
            $callback['params']['log_id'] = $log_id;
        }

        // 请求
        $realtime = $callback ? false : true;
        $gzip     = isset($params['gzip']) ? $params['gzip'] : false; unset($params['gzip']);
        $rpc_id   = $params['rpc_id'] ? $params['rpc_id'] : null; unset($params['rpc_id']);



        $result = kernel::single('erpapi_rpc_caller')->set_timeout($time_out)
                                           ->set_realtime($realtime)
                                           ->set_config($this->__config)
                                           ->set_result($this->__result)
                                           ->set_callback($callback['class'], $callback['method'], $callback['params'])
                                           ->call($method, $params,  $rpc_id, $gzip);
                                           
        $result['msg'] = $result['err_msg'];
        $status = 'fail';
        if ($result['rsp'] == 'running') {
            $status = 'running';
        } elseif ($result['rsp'] == 'succ') {
            $status = 'success';

            $result['rsp'] = 'success';
        }

        // 如果是异常，超时重新加队列
        if ($realtime === false && $result['rsp'] == 'fail') {
            $failApiModel = app::get('erpapi')->model('api_fail');
            $failApiModel->publish_api_fail($method,$callback['params'],$result);
        }

        $logsdf = array(
            'log_id'        => $log_id,
            'task_name'     => $title,
            'status'        => $status,
            'worker'        => '',
            'params'        => serialize(array($method, $params, $callback)),
            'msg'           => var_export($result,true),
            'log_type'      => '',
            'api_type'      => 'request',
            'memo'          => '',
            'original_bn'   => $primary_bn,
            'createtime'    => time(),
            'last_modified' => time(),
            'msg_id'        => $result['msg_id'],
        );

        $apilogModel->insert($logsdf);

        return $result;
    }

    /**
     * 异步请求回调
     *
     * @return void
     * @author 
     **/
    public function callback($result)
    {
        if (!is_object($result)) return true;

        $callback_params = $result->get_callback_params();
        $rsp             = $result->get_status();
        $msg_id          = $result->get_msg_id();
        $msg             = $result->get_result();
        $response        = $result->get_response();

        $status = $rsp == 'succ' ? 'success' : 'fail';
        $log_id = $callback_params['log_id'];
        $msg = var_export($response,true);

        $apilogModel = app::get('ome')->model('api_log');
        $apilogModel->update(array('status'=>$status,'msg'=>$msg),array('log_id'=>$log_id));


        return array('rsp'=>$rsp, 'res'=>$msg, 'msg_id'=>$msg_id);
    }

    /**
     * 请求SHOPEX中心
     *
     * @return void
     * @author 
     **/
    public function center_call()
    {
    }
}