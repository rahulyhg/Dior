<?php
/**
* 通信层公共函数库文件
* @copyright shopex.cn 2012.2.8
*/
class rpc_func{
    
    /**
    * 标准输出格式
    * @access public
    * @param String $rsp 状态:fail(失败)、success(成功)、warning(警告)
    * @param String $msg 消息
    * @param String $msg_code 错误代码
    * @param Array $data 数据
    * @return Array
    */
    public static function msgOutput($rsp='fail', $msg=null, $msg_code=null, $data=null){
        $rs = array(
            'rsp' => $rsp ? $rsp : 'fail',
            'msg' => $msg,
            'msg_code' => $msg_code,
            'data' => $data,
        );
        return $rs;
    }

    /**
    * 根据node_id获取adapter
    * @param String $node_id
    * @return String 
    */
    public function getAdapterFlagByNodeId($node_id=''){
        if(empty($node_id)) return NULL;
        return kernel::single('middleware_adapter')->getAdapterFlagByNodeId($node_id);
    }

    /**
    * 获取接收适配器实例
    * @param String $adapter_type
    * @param String $node_id
    * @return Object 
    */
    public function getResponseAdapter($adapter_type,$node_id=''){
        if(empty($node_id)) return NULL;
        return kernel::single('middleware_adapter')->getResponseAdapter($adapter_type,$node_id);
    }

    /**
    * 获取适配器sign密钥
    * @param String $node_id
    * @return String 
    */
    public function getSignKey($node_id=''){
        if(empty($node_id)) return NULL;
        return kernel::single('middleware_adapter')->getSignKey($node_id);
    }

    /*
    * 日志记录
    */
    public function write_log(&$params,$write_log,&$repeat=false){
        $original_bn = $write_log['original_bn'];
        $api_method = $write_log['api_method'];
        $log_title = $write_log['log_title'];
        $log_type = $write_log['log_type'];
        $return_value = $write_log['return_value'];
        $task = isset($params['task']) ? $params['task'] : '';

        if (empty($task)){
            $microtime = utils::microtime();
            $unique_key = str_replace('.','',strval($microtime));
            $randval = uniqid('', true);
            $unique_key .= strval($randval);
            $task = $original_bn.$unique_key;
        }
        $unique = $task.$api_method;

        $logObj = kernel::single('middleware_log');
        $repeat = false;
        if($logObj->is_repeat($unique)){
            $log_title .= '[数据重复]';
            $msg = '存在相同task的日志数据('.$task_id.'),防止业务多次处理';
            $status = 'fail';
            $repeat = true;
        }

        $log_id = $logObj->getLogId();
        $new_params[0] = $api_method;
        $new_params[1] = $params;
        $logObj->writeLog($log_id,$log_title,'','',$new_params,'','response','sending','接收数据成功','',$log_type,$original_bn);

        #存储任务唯一标识
        $logObj->set_repeat($unique,$log_id);

        return $log_id;
    }

    public function update_log($log_id,$rsp,$msg=''){
        $logObj = kernel::single('middleware_log');
        $logObj->updateLog($log_id,$msg,$rsp);
    }

    /**
     * 日期型转换时间戳
     * @param $string $date_time 日期字符串或时间戳
     */
    public static function date2time($date_time){
        if(preg_match('/^((\d{2,4})(-|\/)(\d{1,2})(-|\/)(\d{1,2})|(\d{4})(-|\/)(\d{1,2})(-|\/)(\d{1,2}) ((\d{1,2})(:|)(\d{1,2})|(\d{1,2})(:|)(\d{1,2})(:|)(\d{1,2})))$/',$date_time)){
                return strtotime($date_time);
        }else{
            return $date_time;
        }
    }

    
     /**
      * 判断是否重复日志
      * @param 
      * @return  
      * @access  public
      * @author sunjng@shopex.cn
      */
     public function repeat_unique($params,$write_log)
     {
        $original_bn = $write_log['original_bn'];
        $api_method = $write_log['api_method'];
        $task = isset($params['task']) ? $params['task'] : '';

        if (empty($task)){
            $microtime = utils::microtime();
            $unique_key = str_replace('.','',strval($microtime));
            $randval = uniqid('', true);
            $unique_key .= strval($randval);
            $task = $original_bn.$unique_key;
        }
        $unique = $task.$api_method;
        return $unique;
    }
}