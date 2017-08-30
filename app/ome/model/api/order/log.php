<?php
class ome_mdl_api_order_log extends dbeav_model{

    var $defaultOrder = array('createtime DESC');

    function gen_id(){
        return uniqid();
    }

    /*
     * 写日志
     * @param int $log_id 日志id
     * @param string $task_name 操作名称
     * @param string $class 调用这次api请求方法的类
     * @param string $method 调用这次api请求方法的类函数
     * @param array $params 调用这次api请求方法的参数集合
     * @param array $api_type api请求类型
     * @param string $status 运行状态
     * @param string $msg 返回信息
     *
     */
    function write_log($log_id,$task_name,$class,$method,$params,$api_type='request',$status='running',$msg=''){
        $time = time();
        $log_sdf = array(
            'log_id' => $log_id,
            'task_name' => $task_name,
            'status' => $status,
            'worker' => $class.':'.$method,
            'params' => serialize($params),
            'msg' => $msg,
            'api_type' => $api_type,
            'shop_id' => $params['shop_id'],
            'order_bn' => $params['order_bn'],
            'shop_name' => $params['shop_name'],
            'createtime' => $time,
            'last_modified' => $time,
        );

        return $this->save($log_sdf);
    }

    function update_log($log_id,$msg=NULL,$status=NULL,$params=NULL,$msg_id){
        //同步日志状态非success才进行修改
        $api_detail = $this->dump(array('log_id'=>$log_id), 'status,params');
        if ($api_detail['status'] != 'success'){
            $msg_data = json_decode($msg,true);
            $log_sdf = array(
                'msg' => $msg,
                'status' => $status,
                'msg_id' => $msg_id,
            );


            if(!empty($params)){
            	if(!empty($api_detail['params'])){
            		$api_detail['params'] = unserialize($api_detail['params']);
            	}

            	$params = $params+$api_detail['params'];

                $log_sdf['params'] = serialize($params);
            }

            $filter = array('log_id'=>$log_id);
            $this->update($log_sdf, $filter);
        }
    }
}
