<?php
/**
 * 订单状态重新发送
 * @author lijun
 * @package omeftp_service_log
 *
 */
class omemagento_log_retry{

    public function order_status_retry(){
        $log_mdl = app::get('omemagento')->model('request_log');
        
        $failLogs = $log_mdl->getList('*',array('task_name'=>'更新订单状态','createtime|than'=>'1550419200','status'=>'fail','retry|lthan'=>'4'));
        foreach($failLogs as $log){

            $params = unserialize($log['original_params']);
            $method = $params['method'];
            unset($params['method']);
            $flag = kernel::single('omemagento_service_request')->retry_request($method,$params,$log['log_id'],$log['retry']);
        }
    }

}