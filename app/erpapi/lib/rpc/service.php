<?php
class erpapi_rpc_service{

    private $start_time;
    private $format = 'json';
    private $path = array();
    private $finish = false;
    static  $node_id;


    /**
     * 外部入口
     *
     * @param String $path URL路径
     * @param String $source_type matrix|openapi|prism 
     **/
    public function process($path){
        if(!kernel::is_online()){
            die('error');
        }else{
            require(ROOT_DIR.'/config/config.php');
            @include(APP_DIR.'/base/defined.php');
        }

        // $this->__source_type = 'shopex';

        $this->handle();
    }

    private function begin()
    {
        register_shutdown_function(array(&$this, 'shutdown'));
        array_push($this->path,$key);
        @ob_start();
    }

    private function end($shutdown=false){
        if($this->path){
            $this->finish = true;
            $content = ob_get_contents();
            ob_end_clean();
            $name = array_pop($this->path);
 
            if($shutdown){
                $result = array(
                    'rsp'=>'fail',
                    'res'=>$content,
                    'data'=>null,
                );

                echo $this->formatObj->data_encode($result);
                exit;
            }

            return $content;
        }
    }

    public function shutdown(){
        $this->end(true);
    }


    //app_id     String     Y     分配的APP_KEY
    //method     String     Y     api接口名称
    //date     string     Y     时间戳，为datetime格式
    //format     string     Y     响应格式，xml[暂无],json
    //certi_id     int     Y     分配证书ID
    //v     string     Y     API接口版本号
    //sign     string     Y     签名，见生成sign
    // private function parse_rpc_request($request){

    //     $sign = $request['sign']; unset($request['sign']);

    //     $platform = $this->__router->get_object();

    //     $sign_check = $platform->get_sign()->gen_sign($request);

    //     if($sign != $sign_check){
    //         $this->send_user_error('4003', 'sign error');
    //         return false;
    //     }

    //     $system_params = array('app_id','method','date','format','certi_id','v','sign','node_id');
    //     foreach($system_params as $name){
    //         $call[$name] = $request[$name];
    //         unset($request[$name]);
    //     }

    //     return $request;
    // }

    private function gen_uniq_process_id(){
        return uniqid();
    }

    /**
     * 放入MQ处理
     *
     * @return void
     * @author 
     **/
    public function _mq_handle()
    {
        $data['spider_data']['url'] = sprintf("http://%s%s", $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']);
        $postAttr = array('_FROM_MQ_QUEUE=true');

        foreach ($_REQUEST as $key => $val) {

            $postAttr[] = $key . '=' . urlencode($val);
        }

        $data['spider_data']['params']    = empty($postAttr) ? '' : join('&', $postAttr);
        $data['relation']['to_node_id']   = base_shopnode::node_id('ome');
        $data['relation']['from_node_id'] = $_REQUEST['node_id'];
        $data['relation']['tid']          = $_REQUEST['msg_id'];
        $data['relation']['to_url']       = $data['spider_data']['url'];
        $data['relation']['time']         = time();

        $routerKey = 'tg.sys.rpc.'.$data['nodeId'];

        $message = json_encode($data);
        $mq = kernel::single('base_queue_mq');
        $mq->connect($GLOBALS['_MQ_RPC_CONFIG'], 'TG_RPC_EXCHANGE', 'TG_RPC_QUEUE');
        $mq->publish($message, $routerKey);
        
        $result = array(
            "rsp"=>"running",
            "res"=>"",
            "data"=>"",
        );

        echo $this->formatObj->data_encode($result);exit;
    }

    public function handle(){
        // 设置客户端断开连接时是否中断脚本的执行 true:中断
        ignore_user_abort(); set_time_limit(0);

        $this->process_id = $this->gen_uniq_process_id();
        header('Process-id: '.$this->process_id);
        header('Connection: close');
        flush();

        if(get_magic_quotes_gpc()){
            kernel::strip_magic_quotes($_REQUEST);
        }

        if(strtolower($_SERVER['HTTP_CONTENT_ENCODING']) == 'gzip'){
            $_input = fopen('php://input','rb');
            while(!feof($_input)){
                $_post .= fgets($_input);
            }
            fclose($_input);
            $_post = utils::gzdecode($_post);
            parse_str($_post, $post);
            if($post){
                if(get_magic_quotes_gpc()){
                    kernel::strip_magic_quotes($_GET);
                }
                $_REQUEST = array_merge($_GET, $post);
            }
        }

        if ($_REQUEST['format'] == 'xml') $this->format = 'xml';
        $this->formatObj = kernel::single('erpapi_format_'.$this->format);

        // 是否加入队列
        if (defined('SAAS_RPC_MQ') 
            && SAAS_RPC_MQ == 'true' 
            && !isset($_REQUEST['_FROM_MQ_QUEUE']) 
            && $_REQUEST['_FROM_MQ_QUEUE'] != 'true'
            && ($_REQUEST['callback'] || $_SERVER['HTTP_CALLBACK'] ) && false) {

            return $this->_mq_handle();
        }

        //todo: uncompress post data
        $rpc_id = $_REQUEST['task'] ? md5($_REQUEST['task'].$_REQUEST['method'].$_SERVER['HTTP_HOST']) : md5($_REQUEST['task'].$_REQUEST['sign'].$_SERVER['HTTP_HOST']);

        // 判断是否重复
        $apilogModel = app::get('ome')->model('api_log');
        if ($apilogModel->is_repeat($rpc_id)) {
            $this->send_user_success('4007', '不能重复');
        }


        $this->begin(__FUNCTION__);
        set_error_handler(array(&$this,'error_handle'),E_ERROR);
        set_error_handler(array(&$this,'user_error_handle'),E_USER_ERROR);

        $this->start_time = $_SERVER['REQUEST_TIME'] ? $_SERVER['REQUEST_TIME'] : time();

        // 验签
        $signRs = kernel::single('erpapi_router_response')
                          ->set_node_id($_REQUEST['node_id'])
                          ->set_api_name($_REQUEST['method'])
                          ->dispatch($_REQUEST,true);

        if ($signRs['rsp'] == 'fail') {
            trigger_error($signRs['msg'],E_USER_ERROR);
        }

        // 解析
        $_REQUEST['task'] = $rpc_id;

        $data = array(
            'id'         => $rpc_id,
            // 'network'    => $this->network, //要读到来源，要加密
            // 'method'     => $service,
            'calltime'   => $this->start_time,
            'params'     => $_REQUEST,
            'type'       => 'response',
            'process_id' => $this->process_id,
            'callback'   => $_SERVER['HTTP_CALLBACK'],
        );

        $obj_rpc_poll = app::get('base')->model('rpcpoll');
        // 防止多次重刷.
        if (!$obj_rpc_poll->db->select('SELECT id FROM ' . $obj_rpc_poll->table_name(1) . ' WHERE id=\''.$rpc_id.'\' AND type=\'response\' LIMIT 0,1')) {
            $obj_rpc_poll->insert($data);

            $rs = kernel::single('erpapi_router_response')
                          ->set_node_id($_REQUEST['node_id'])
                          ->set_api_name($_REQUEST['method'])
                          ->dispatch($_REQUEST);
            if ($rs['rsp'] == 'fail') {
                trigger_error($rs['msg'],E_USER_ERROR);
            }
            $result = $rs['data'];

            $output = $this->end();
        }else {
            $output = $this->end();
            $output = app::get('base')->_('该请求已经处理，不能在处理了！');
        }

        $result_json = array(
            'rsp'  => 'succ',
            'data' => $result,
            'res'  => strip_tags($output)
        );

        $this->rpc_response_end($result, $this->process_id, $result_json);

        echo $this->formatObj->data_encode($result_json);
    }

    private function rpc_response_end($result, $process_id, $result_json)
    {
        if (isset($process_id) && $process_id)
        {
            $connection_aborted = $this->connection_aborted();
            $obj_rpc_poll = app::get('base')->model('rpcpoll');

            if($connection_aborted){

                // 异步回调
                $obj_rpc_poll->update(array('result'=>$result),array('process_id'=>$process_id,'type'=>'response'));

                $callback = $_SERVER['HTTP_CALLBACK'] ? $_SERVER['HTTP_CALLBACK'] : $_REQUEST['callback'];
                if($callback){
                    $return = kernel::single('base_httpclient')->get($callback.'?'.json_encode($result_json));
                    $return = json_decode($return);

                    if($return->result=='ok'){
                        $obj_rpc_poll->delete(array('process_id'=>$process_id,'type'=>'response'));
                    }

                }else{
                    $obj_rpc_poll->delete(array('process_id'=>$process_id,'type'=>'response'));
                }
            }else{
                $obj_rpc_poll->delete(array('process_id'=>$process_id,'type'=>'response'));
            }
        }
    }

    private function connection_aborted(){
        $return = connection_aborted();
        if(!$return){
            if(is_numeric($_SERVER['HTTP_CONNECTION']) && $_SERVER['HTTP_CONNECTION']>0){
                if(time()-$this->start_time>=$_SERVER['HTTP_CONNECTION']){
                    $return = true;
                }
            }
        }
        return $return;
    }

    /**
     * 回调入口
     */ 
    public function async_result_handler($params){

        if (defined('SAAS_CALLBACK_MQ') && SAAS_CALLBACK_MQ == 'true' && false) {
            if (!isset($_REQUEST['_FROM_MQ_QUEUE']) && $_REQUEST['_FROM_MQ_QUEUE'] != 'true') {
                $this->_mq_async_result_handler($params);
            } else {

                $this->_real_async_result_handler($params);
            }
        } else {

             $this->_real_async_result_handler($params);
        }
    }

    private function _mq_async_result_handler($params) {

        $this->begin(__FUNCTION__);

        $data['spider_data']['url'] = sprintf("http://%s%s", $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']);
        $postAttr = array('_FROM_MQ_QUEUE=true');

        foreach ($_REQUEST as $key => $val) {

            $postAttr[] = $key . '=' . urlencode($val);
        }

        $data['spider_data']['params']    = empty($postAttr) ? '' : join('&', $postAttr);
        $data['relation']['to_node_id']   = base_shopnode::node_id('ome');
        $data['relation']['from_node_id'] = '0';
        $data['relation']['tid']          = $_REQUEST['msg_id'];
        $data['relation']['to_url']       = $data['spider_data']['url'];
        $data['relation']['time']         = time();

        $routerKey = 'tg.sys.callback.'.$data['nodeId'];

        $message = json_encode($data);
        $mq = kernel::single('base_queue_mq');
        $mq->connect($GLOBALS['_MQ_CALLBACK_CONFIG'], 'TG_CALLBACK_EXCHANGE', 'TG_CALLBACK_QUEUE');
        $mq->publish($message, $routerKey);
        
        if(!$return){
            $return = array(
                "rsp"=>"succ",
                "res"=>"the callback is into mq now !!!",
                "msg_id"=>"",
            );
        }

        $this->end();

        header('Content-type: text/plain');
        echo json_encode($return);
    }

    private function _real_async_result_handler($params){

        $this->begin(__FUNCTION__);
        set_error_handler(array(&$this,'user_error_handle'),E_USER_ERROR);

        $obj_rpc_poll = app::get('base')->model('rpcpoll');
        list($rpc_id,$rpc_calltime)   = explode('-', $params['id']);

        $filter = array('id'=>$rpc_id, 'calltime'=>$rpc_calltime, 'type'=>'request');

        $row = $obj_rpc_poll->getlist('id,fail_times,callback,callback_params,process_id,params,method',$filter,0,1);

        if($row){
            $row[0]['params'] = @unserialize($row[0]['params']);
            $row[0]['callback_params'] = @unserialize($row[0]['callback_params']);

            if(is_array($row[0]['callback_params'])){
                $row[0]['callback_params']['method'] = $row[0]['method'];
            }

            if ($row[0]['format'] == 'xml') $this->format = 'xml';
            $this->formatObj = kernel::single('erpapi_format_'.$this->format);

            $configObj       = unserialize($row[0]['callback_params']['config_class']);

            // 签名
            $sign = $_POST['sign']; unset($_POST['sign']);
            $sign_check = $configObj->gen_sign($_POST);
            if ($sign != $sign_check) {
                trigger_error('sign error!',E_USER_ERROR);
            }

            $fail_time = ($row[0]['fail_times']-1) ? ($row[0]['fail_times']-1) : 0;
            $obj_rpc_poll->update(array('fail_times'=>$fail_time), $filter);

            list($class,$method) = explode(':',$row[0]['callback']);
            if($class && $method){

                $return = kernel::single($class)->$method($_POST,$row[0]['callback_params']);

                if($return){
                    $notify = array(
                                'callback' => $row[0]['callback'],
                                'rsp'      => $return['rsp'],
                                'msg'      => $return['res'] ? $return['res'] : '',
                                'notifytime'=>time()
                            );
                    app::get('base')->model('rpcnotify')->insert($notify);
                }
            }
        }

        if (($row[0]['fail_times']-1) <= 0 && $return['rsp'] == 'succ')
        {
            $obj_rpc_poll->delete(array('id'=>$rpc_id,'calltime'=>$rpc_calltime,'type'=>'request'));
        }

        if(!$return){
            $return = array(
                "rsp"=>"fail",
                "res"=>$params['id'],
                "msg_id"=>$row[0]['id'],
            );
        }

        $this->end();

        header('Content-type: text/plain');
        echo json_encode($return);
    }

    function error_handle($error_code, $error_msg){
        $this->send_user_error('4007', $error_msg);
    }

    function user_error_handle($error_code, $error_msg){
        $this->send_user_error('4007', $error_msg);
    }

    public function send_user_error($code, $err_msg)
    {
        $this->end();
        $res = array(
            'rsp'      => 'fail',
            'res'      => $err_msg,
            'data'     => '',
            'msg'      => $err_msg,
            'msg_code' => $code, 
        );
        $this->rpc_response_end($err_msg,$this->process_id, $res);

        echo $this->formatObj->data_encode($res);
        exit;
    }//End Function

    public function send_user_success($code, $data)
    {
        $output = $this->end();
        $result_json = array(
            'rsp'=>'succ',
            'data'=>$data,
            'res'=>$code,
        );
        $this->rpc_response_end($data, $this->process_id, $result_json);

        echo $this->formatObj->data_encode($result_json);
        exit;
    }//End Function

}