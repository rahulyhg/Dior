<?php
class ome_mdl_api_log_local extends dbeav_model{
    public function table_name($real=false){
        $tableName = 'api_log';
        return $real ? kernel::database()->prefix.'ome_'.$tableName : $tableName;
    }
    public function get_schema(){
        $schema = app::get('ome')->model('api_log')->get_schema();
        return $schema;
    }

    function gen_id(){
        return uniqid();
    }
    
    function _filter($filter,$tableAlias=NULL,$baseWhere=NULL){
        if (isset($filter['params'])){
            $wheresql = " AND `params` LIKE '%".$filter['params']."%'";
            unset($filter['params']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere).$wheresql;
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
        $time = time();
        $log_sdf = array(
            'log_id' => $log_id,
            'task_name' => $task_name,
            'status' => $status,
            'worker' => $class.':'.$method,
            'params' => serialize($params),
            'msg' => $msg,
            'log_type' => $log_type,
            'api_type' => $api_type,
            'memo' => $memo,
            'original_bn' => $bn,
            'createtime' => $time,
            'last_modified' => $time,
        );
        if (is_array($addon)){
            $log_sdf = array_merge($log_sdf,$addon);
        }

        return $this->save($log_sdf);
    }
    
    function update_log($log_id,$msg=NULL,$status=NULL,$params=NULL,$addon=NULL){

        //同步日志状态非success才进行修改
        $api_detail = $this->dump(array('log_id'=>$log_id), 'status');
        if ($api_detail['status'] != 'success'){
            $update_field = array('status','params','msg');
            $log_sdf = array();
            foreach ($update_field as $fields){
                if (!empty(${$fields})){
                    $log_sdf[$fields] = ${$fields};
                }
            }
            if(isset($log_sdf['params'])){
                $log_sdf['params'] = serialize($params);
            }
            if (is_array($addon)){
                $log_sdf = array_merge($log_sdf, (array)$addon);
            }

            $filter = array('log_id'=>$log_id);
            $this->update($log_sdf, $filter);
        }
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
        
        $max_retry = 0;
        
        if ($retry_type=='batch' && ( strstr($log_id,"|") || $isSelectedAll == '_ALL_' ) ){
            //批量重试
            $filter['status'] = 'fail';
            $filter['api_type'] = 'request';
            //$filter['sync'] = 'false';
            $filter['retry|bthan'] = $max_retry;

            $limit = 1;
            if ($isSelectedAll != '_ALL_'){
                $log_ids = explode('|',$log_id);
                $filter['log_id'] = $log_ids[$cursor];
                $lim = 0;
            }else{
                $lim = $cursor * $limit;
            }
            $row = $this->getList('*', $filter, $lim, $limit, ' createtime asc ');
            if ($row){
                foreach ($row as $k=>$v){
                    return $this->start_api_retry($v);
                }
            }
            if(!$log_ids[$cursor]){
                return array('task_name'=>'批量重试完成', 'status'=>'complete');
            }else{
                return array('task_name'=>'跳过成功任务', 'status'=>'skip');
            }
        }else{
            //单个按钮重试
            $row = $this->db->selectrow("SELECT * FROM sdb_ome_api_log WHERE log_id='".$log_id."' and status='fail' and retry>=$max_retry ");
            return $this->start_api_retry($row);
        }
    }
    
    /*
     * 发起API同步重试
     * @param array $row 发起重试数据
     */
    function start_api_retry($row){
         
        $worker = explode(":",$row['worker']);
        $class = $worker[0];
        $method = $worker[1];
        $params = unserialize($row['params']);
        $log_id = $row['log_id'];
        $log_type = $row['log_type'];
        $original_bn = $row['original_bn'];
        $queryparams = '';
        $status = 'fail';
        $msg = '手动重试';
        
        if($params && !strstr($row['task_name'],'的库存')){
            $return = $this->db->exec("UPDATE sdb_ome_api_log SET retry=retry+1,last_modified='".time()."',status='sending',msg='".$msg."' WHERE log_id='".$log_id."'");
            if (isset($params[1]['all_list_quantity'])){
                unset($params[1]['all_list_quantity']);
            }

            //重试前的扩展业务逻辑
            if ( kernel::single('ome_api_func')->retry_before($log_id,$log_type,$original_bn,$params) ){
                return array('task_name'=>$row['task_name'].$original_bn, 'status'=>'succ');
            }
        
            $eval = "kernel::single('$class')->$method(";
            if(is_array($params)){
                $i = 0;
                foreach($params as $v){
                    $tmp_param[$i] = $v;
                    $tmp_param_string[] = "\$tmp_param[$i]";
                    $i++;
                }
                $eval .= implode(",",$tmp_param_string);
            }else{
                $eval .= $params;
            }
            $eval .= ");";
            eval($eval);
            if ($return) $status = 'succ';
        }
        return array('task_name'=>$row['task_name'].$row['original_bn'], 'status'=>$status);
    }

    function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(empty($orderType))$orderType = "createtime DESC";
        return parent::getList('*',$filter,$offset,$limit,$orderType);
    }
    
    function is_repeat($key=''){
        $log = $this->getList('log_id',array('unique'=>md5($key)),0,1);
        return isset($log[0]['log_id']) ? $log[0]['log_id'] : '';
    }

    function set_repeat($key='',$log_id=''){
        $log_sdf['unique'] = md5($key);
        //return $this->update_log($log_id,$msg=NULL,$status=NULL,$params=NULL,$addon);
        $filter = array('log_id'=>$log_id);
        $this->update($log_sdf, $filter);
    }
}
?>