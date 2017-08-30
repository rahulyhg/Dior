<?php

class ome_mdl_api_log extends dbeav_model{

    public function getClass(){
        $class = array(
            'local' => 'ome_mdl_api_log_local',
            'api' => 'ome_mdl_api_log_api',
        );
        $switch = $class_name = '';
        if(!defined('APILOG_SWITCH')) $switch = 'local';
        else $switch = APILOG_SWITCH;
        if(isset($class[$switch]) && $class[$switch]){
            $class_name = $class[$switch];
        }else{
            $class_name = 'ome_mdl_api_log_local';
        }
        return kernel::single($class_name);
    }

    function gen_id(){
        $microtime = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    function _filter($filter,$tableAlias=NULL,$baseWhere=NULL){
        return $this->getClass()->_filter($filter,$tableAlias,$baseWhere);
    }

    /*
     * 写日志
     * @param int $log_id 日志id
     * @param string $task_name 操作名称
     * @param string $class 调用这次api请求方法的类
     * @param string $method 调用这次api请求方法的类函数
     * @param array $params 调用这次api请求方法的参数集合
     * @param string $msg 返回信息
     * @param string $addon[marking_value标识值，marking_type标识类型 ]
     *
     */
    function write_log($log_id,$task_name,$class,$method,$params,$memo='',$api_type='request',$status='running',$msg='',$addon='',$log_type='',$bn = ''){
        return $this->getClass()->write_log($log_id,$task_name,$class,$method,$params,$memo,$api_type,$status,$msg,$addon,$log_type,$bn);
    }

    function update_log($log_id,$msg=NULL,$status=NULL,$params=NULL,$addon=NULL){
        return $this->getClass()->update_log($log_id,$msg,$status,$params,$addon);
    }

    function is_repeat($key){
        return $this->getClass()->is_repeat($key);
    }

    function set_repeat($key,$log_id=''){
        return $this->getClass()->set_repeat($key,$log_id);
    }

    /*
     * 同步重试
     * 有单个重试与批量重试
     * @param array or int $log_id
     * @param string $retry_type 默认为单个重试，btach:为批量重试
     * @param string $isSelectedAll 是否全选
     * @param string $cursor 当前游标，用于循环选中重试
     */
    function retry($log_id='', $retry_type='', $isSelectedAll='', $cursor='0'){
        return $this->getClass()->retry($log_id,$retry_type,$isSelectedAll, $cursor);
    }

    /*
     * 发起API同步重试
     * @param array $row 发起重试数据
     */
    function start_api_retry($row){
         return $this->getClass()->start_api_retry($row);
    }

    public function count($filter=null){
        return $this->getClass()->count($filter);
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        return $this->getClass()->getList($cols,$filter,$offset,$limit,$orderType);
    }

    public function insert($data){
        return $this->getClass()->insert($data);
    }

    public function update($data,$filter,$mustUpdate = null){
        return $this->getClass()->update($data,$filter);
    }

    public function dump($filter,$field = '*',$subSdf = null){
        return $this->getClass()->dump($filter,$field,$subSdf);
    }
}
?>