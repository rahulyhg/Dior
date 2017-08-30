<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class rpc_service{

    private $start_time;
    private $path = array();
    private $finish = false;
    static $node_id;

    /**
    * api入口
    * @access public
    */
    public function process(){

        if(!kernel::is_online()){
            die('error');
        }else{
            require(ROOT_DIR.'/config/config.php');
            @include(APP_DIR.'/base/defined.php');
        }

        ignore_user_abort();
        set_time_limit(0);
        $this->process_id = $this->gen_uniq_process_id();
        header('Process-id: '.$this->process_id);
        header('Connection: close');
        flush();
        
        if(strtolower($_SERVER['HTTP_CONTENT_ENCODING']) == 'gzip'){
            $_input = fopen('php://input','rb');
            while(!feof($_input)){
                $_post .= fgets($_input);
            }
            fclose($_input);
            $_post = utils::gzdecode($_post);
            parse_str($_post, $post);
            if($post){
                $_POST = array_merge($_GET, $post);
            }
        }//todo: uncompress post data
        
        $this->begin(__FUNCTION__);
        set_error_handler(array(&$this,'error_handle'),E_ERROR);
        set_error_handler(array(&$this,'user_error_handle'),E_USER_ERROR);

        $this->start_time = $_SERVER['REQUEST_TIME']?$_SERVER['REQUEST_TIME']:time();

        #系统级参数过滤
        list($method,$params) = $this->parse_rpc_request($_POST);
        
        #sign校验
        $this->sign_check($_POST);
        
        #防止多次重刷
        $repeat_access = false;
        if($_POST['task']){
            $data = array(
                'id'=>$_POST['task'],
                'network'=>$this->network, //要读到来源，要加密
                'method'=>$service,
                'calltime'=>$this->start_time,
                'params'=>$params,
                'type'=>'response',
                'process_id'=>$this->process_id,
                'callback'=>$_SERVER['HTTP_CALLBACK'],
            );
            $obj_rpc_poll = app::get('base')->model('rpcpoll');
            if ($obj_rpc_poll->db->select('SELECT id FROM ' . $obj_rpc_poll->table_name(1) . ' WHERE id=\''.$_POST['task'].'\' AND type=\'response\' LIMIT 0,30 LOCK IN SHARE MODE')) {
                $output = $this->end();
                $output = app::get('base')->_('禁止重复访问！');
                $repeat_access = true;
            }else{
                $obj_rpc_poll->insert($data);
            }
        }

        #接口分发
        if($repeat_access == false){
            $result = $this->dispatch('wms',$method,$params);
            $output = $this->end();
        }else{
            $result = array('rsp'=>'fail','msg'=>$output);
        }
       
        $this->rpc_response_end($result, $this->process_id, $result);
        echo json_encode($result);
        exit;
    }
    
    /**
    * 异步回传
    * @access public
    * @param Array $params url参数
    * @return 输出结果
    */
    public function callback($params){
    
        if(!kernel::is_online()){
            die('error');
        }else{
            require(ROOT_DIR.'/config/config.php');
            @include(APP_DIR.'/base/defined.php');
        }

        $args = explode('/',$params);
        $params_arr = array();
        foreach($args as $i=>$v){
            if($i%2){
                $params_arr[$k] = str_replace('%2F','/',$v);
            }else{
                $k = $v;
            }
        }

        if($_POST['node_id']){
            self::$node_id = $_POST['node_id'];
        }
        
        

        $obj_rpc_poll = app::get('base')->model('rpcpoll');
        $arr_rpc_id = explode('-', $params_arr['id']);
        $rpc_id = $arr_rpc_id[0];
        $rpc_calltime = $arr_rpc_id[1];
        $row = $obj_rpc_poll->getList('fail_times,callback,callback_params',array('id'=>$rpc_id,'calltime'=>$rpc_calltime,'type'=>'request'),0,1);
        //从rpcpoll里取node_id 郁闷啊。。么传呀
        if ($row) {
            if (!self::$node_id) {
                $tmp_callparams = unserialize($row[0]['callback_params']);
                self::$node_id = $tmp_callparams['node_id'];
                unset($tmp_callparams);
            }
        }

        #sign校验
        $this->sign_check($_POST,$params_arr);


        $fail_time = ($row[0]['fail_times']-1) ? ($row[0]['fail_times']-1) : 0;
        $obj_rpc_poll->update(array('fail_times'=>($row[0]['fail_times']-1)), array('id'=>$rpc_id,'calltime'=>$rpc_calltime,'type'=>'request'));
        
        $return = '';
        if($row){
            list($class,$method) = explode(':',$row[0]['callback']);
            if($class && $method){
                $callback_params = unserialize($row[0]['callback_params']);
                $return = kernel::single($class)->$method($_POST,$callback_params);
            }
        }

        if ($row[0]['fail_times']-1 <= 0)
        {
            $obj_rpc_poll->delete(array('id'=>$rpc_id,'calltime'=>$rpc_calltime,'type'=>'request'));
        }

        $return = rpc_func::msgOutput($return['rsp'],$return['msg'],$return['msg_code'],$return['data']);

        header('Content-type: text/plain');
        echo json_encode($return);
        exit;
    }

    /**
    * 获取异步地址
    * @access public
    * @param String $callback_class 异步返回类
    * @param String $callback_method 异步返回方法
    * @param Array $callback_params 异步返回参数
    * @return String callbackURL
    */
    public function callback_url($callback_class,$callback_method,$callback_params='',$rpc_id=''){
        if(empty($rpc_id)){
            $rpc_id = $this->rpc_id($callback_class,$callback_method,$callback_params);
        }
        $callback_url = kernel::base_url(1).kernel::url_prefix().'/callback/id/'.$rpc_id;
        return $callback_url;
    }

    public function rpc_id($callback_class,$callback_method,$callback_params='',$rpc_id=''){
        $obj_rpc_poll = app::get('base')->model('rpcpoll');
        $time = time();

        if(empty($rpc_id)){
            $microtime = utils::microtime();
            $rpc_id = str_replace('.','',strval($microtime));
            $randval = uniqid('', true);
            $rpc_id .= strval($randval);
            $rpc_id = md5($rpc_id);
        }

        $data = array(
            'id'=>$rpc_id,
            'calltime'=>$time,
            'type'=>'request',
            'callback'=>$callback_class.':'.$callback_method,
            'callback_params'=>$callback_params,
        );
        $rpc_id = $rpc_id.'-'.$time;

        $obj_rpc_poll->insert($data);
        return $rpc_id;
    }

    private function sign_check(&$_POST,$query_params=array()){

        #sign校验
        $sign = $_POST['sign'];
        unset($_POST['sign']);
        $this->func_instance = kernel::single('rpc_func');
        #适配器类型
        $adapter = $this->func_instance->getAdapterFlagByNodeId(self::$node_id);

        switch($adapter){
            case 'matrixwms':#矩阵开放平台WMS
                $app_id = $_POST['app_id'] ? $_POST['app_id'] : $query_params['app_id'];
                if ($app_id){
                    $app_id = substr($app_id, strpos($app_id, '.')+1,strlen($app_id));
                }else{
                    //如果不存在app_id的话,取系统主应用的main_app.
                    $app_exclusion = app::get('base')->getConf('system.main_app');
                    $app_id = $app_exclusion['app_id'];
                }
                
                if (!base_shopnode::token($app_id))
                    $sign_check = base_certificate::gen_sign($_POST);
                else
                    $sign_check = base_shopnode::gen_sign($_POST,$app_id);
                break;
            default:#其它直连WMS
                $signObj = kernel::single('rpc_sign');
                $sign_key = $this->func_instance->getSignKey(self::$node_id);
               
                $sign_check = $signObj->get_response_sign($_POST,$sign_key);
        }

        if($sign != $sign_check){
            
            $this->send_user_error('4003', 'sign error');
            return false;
        }
    }

    private function begin() 
    {
        register_shutdown_function(array(&$this, 'shutdown'));
        array_push($this->path,$key);
        @ob_start();
    }//End Function

    /**
    * dispatch
    */
    private function dispatch($adapter_type,$method,&$params){
        try{
            $dispatch_class_name = 'rpc_'.$adapter_type;
            if(class_exists($dispatch_class_name)){
                $funcObj = kernel::single('rpc_func');
                #内部sdf参数转换
                $dispatch_instance = kernel::single($dispatch_class_name);
                $log_info = array(); 
                $log_info[] = "接收数据成功";
                list($adapter_method,$adapter_params,$write_log) = $dispatch_instance->convert($method,$params);
               
                #日志记录
                $logModel = app::get("ome")->model('api_log');
                #调用适配器接口方法
                $adapter_instance = $funcObj->getResponseAdapter($adapter_type,self::$node_id);
                
                $adapter_params['node_id'] = self::$node_id;
                $unique = $funcObj->repeat_unique($params,$write_log);
                
                $logObj = kernel::single('middleware_log');
                
                
                if($logObj->is_repeat($unique)){
                     $write_log['log_title'].= '[数据重复]';
                    $rs = array('rsp'=>'success','msg'=>'数据重复');
                    $rs['data'] = array();

                }else{
                    $rs = $adapter_instance->$adapter_method($adapter_params);
                    $rs['rsp'] = isset($rs['rsp']) && $rs['rsp'] == 'succ' ? 'success' : $rs['rsp'];
                }
                    $rs['res'] = $rs['msg'] ? $rs['msg'] : '';
                
                
                $log_info[] = $rs['msg'];
                $new_params[0] = $write_log['api_method'];
                $new_params[1] = $params;
                $log_id = $logModel->gen_id();
                $logModel->write_log($log_id,
                                     $write_log['log_title'],
                                     get_class($this), 
                                     $write_log['api_method'], 
                                     $new_params, 
                                     '', 
                                     'response', 
                                     $rs['rsp'], 
                                     implode('<hr/>',(array)$log_info),
                                     '',
                                     $write_log['log_type'],
                                     $write_log['original_bn']);
                //
                $logObj->set_repeat($unique,$log_id);
                return $rs;                
                
            }
        }catch(Exception $e){
            return false;
        }
    }

    private function end($shutdown=false){
        if($this->path){
            $this->finish = true;
            $content = ob_get_contents();
            ob_end_clean();
            $name = array_pop($this->path);            
            if(defined('SHOP_DEVELOPER')){
                error_log("\n\n".str_pad(@date(DATE_RFC822).' ',60,'-')."\n".$content
                    ,3,ROOT_DIR.'/data/logs/trace.'.$name.'.log');
            }
            if($shutdown){
                $return = rpc_func::msgOutput('fail',$content);
                echo json_encode($return);
                exit;
            }
            return $content;
        }
    }
    
    public function shutdown(){
        $this->end(true);
    }

    private function parse_rpc_request($request){

        $system_params = array('method','date','format','v','sign','node_id');
        foreach($system_params as $name){
            $call[$name] = $request[$name];
            unset($request[$name]);
        }

        if(!isset($call['method'])){
            $this->send_user_error('4001', 'error method');
            return false;
        }

        if($call['node_id']){
            self::$node_id = $call['node_id']; 
        }
        
        return array($call['method'],$request);
    }

    private function gen_uniq_process_id(){
        return uniqid();
    }

    private function rpc_response_end($result, $process_id, $result_json)
    {
        if (isset($process_id) && $process_id)
        {
            $connection_aborted = $this->connection_aborted();
            $obj_rpc_poll = app::get('base')->model('rpcpoll');
            
            if($connection_aborted){
                $obj_rpc_poll->update(array('result'=>$result),array('process_id'=>$process_id,'type'=>'response'));
                if($_SERVER['HTTP_CALLBACK']){
                    $return = kernel::single('base_httpclient')->get($_SERVER['HTTP_CALLBACK'].'?'.json_encode($result_json));
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
    
    function error_handle($error_code, $error_msg){
        $this->send_user_error('4007', $error_msg);
    }

    function user_error_handle($error_code, $error_msg){
        $this->send_user_error('4007', $error_msg);
    }

    public function send_user_error($msg_code, $msg) 
    {
        $this->end();
        $res = rpc_func::msgOutput('fail',$msg,$msg_code);
        $this->rpc_response_end($msg,$this->process_id, $res);
        echo json_encode($res);
        exit;
    }//End Function

}
