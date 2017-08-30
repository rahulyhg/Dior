<?php
class ome_sync_api_log{
    
    /**
     * 自动重试同步请求
     * API同步日志每1分钟触发一次：以5分钟为基数将所有正在运行中的请求自动发起重试（最多3次）,超过3次的设置为失败)
     */
    public function auto_retry(){
        
        $now = time();//当前时间
        $every_time = 60*5;//重试间隔基数（秒数）
        $max_retry = '3';//最多重试次数
        // 需要模拟失败的callback的接口
        $callback_methods = array('store.trade.payment.add','store.trade.refund.add','store.items.quantity.list.update');
        $log_ids = array();
        
        //--获取所有超过5分钟未响应且重试次数小于3的正在运行中或发起中的同步日志记录
        //--采用分页读取，防止大数据并发量
        $oQueue = &app::get('base')->model('queue');
        $sql_counter = " SELECT count(*) ";
        $sql_list = " SELECT `log_id`,`retry`,`params` ";
        $sql_base = " FROM `sdb_ome_api_log` 
                 WHERE (`status`='running' OR `status`='sending') AND `api_type`='request' 
                 AND `retry`<'$max_retry' AND `last_modified`<({$now}-IF(`retry`<'1','1',`retry`+'1')*{$every_time}) ";
        //$sql = $sql_counter . $sql_base;
        $apiObj = &app::get('ome')->model('api_log');
        $filter = array(
            'status' => array('running','sending'),
            'api_type' => 'request',
            'retry|<' => (string)$max_retry,
            'last_modified|<' => (int)($now-$every_time),
        );
        $count = $apiObj->count($filter);
        //$count = kernel::database()->count($sql);
        if ($count){
            $page = 1;
            $limit = 50;
            $pagecount = ceil($count/$limit);
            for ($i=$page;$i<=$pagecount;$i++){
                $lim = ($i-1) * $limit;
                //$sql = $sql_list . $sql_base . " LIMIT " . $lim . "," . $limit;
                //$data = kernel::database()->select($sql);
                $data = $apiObj->getList('*',$filter,$lim,$limit);
                if ($data){
                    $sdfdata['log_id'] = array();
                    foreach ($data as $k=>$v){
                        $log_id = $v['log_id'];
                        $v = $apiObj->dump($log_id);
                        // 将超过10分钟的支付或者退款请求的且在运行中的任务自身模拟失败callback
                        if ($v['retry'] >= ($max_retry-1)){
                            $params = $callback_params = $callback = array();
                            if (!is_array($v['params'])){
                                $params = unserialize($v['params']);
                            }else{
                                $params = $v['params'];
                            }
                            $method = $params[0];
                            if (in_array($method, $callback_methods)){
                                $callback = $params[2];
                                // 模拟result返回结果类
                                $callback_params['log_id'] = $v['log_id'];
                                if(isset($callback[2]['shop_id'])){
                                    $callback_params['shop_id'] = $callback[2]['shop_id'];
                                }
                                $response = array('rsp'=>'fail','res'=>'请求超时');
                                $resultObj = kernel::single('ome_rpc_result', $response);
                                $resultObj->set_callback_params($callback_params);
                                // 调用同步任务的callback
                                if (kernel::single($callback[0])->$callback[1]($resultObj)){
                                    $log_ids[] = $v['log_id'];
                                }
                            }
                        }
                        if (!in_array($v['log_id'], $log_ids)){
                            $sdfdata['log_id'][] = $v['log_id'];
                        }
                    }
                    if ($sdfdata['log_id']){
                        $queueData = array(
                            'queue_title'=>'API同步自动重试'.$i.',共'.count($sdfdata['log_id']).'条)',
                            'start_time'=>$now,
                            'params'=>array(
                                'sdfdata'=>$sdfdata['log_id'],
                                'app' => 'ome',
                                'mdl' => 'api_log'
                            ),
                            'status' => 'hibernate',
                            'worker'=> 'ome_api_log_to_api.retry',
                        );
                        $oQueue->save($queueData);
                    }
                }
            }
        }
        
        $msg = '请求超时';
        //$where = " (`status`='running' OR `status`='sending') AND `api_type`='request' AND `retry`>='$max_retry' AND `last_modified`<'".($now-$every_time)."' ";
        //$sql = " UPDATE `sdb_ome_api_log` SET `last_modified`='".$now."',`status`='fail',`msg`='".$msg."' WHERE ";
        
        // 将所有重试次数超过3次且正在运行中或发起中的同步日志设置为失败
        //kernel::database()->exec($sql.$where);
        $updateSdf = array(
            'status' => 'fail',
            'msg' => $msg,
        );
        $updateFilter = array(
            'status' => array('running','sending'),
            'api_type' => 'request',
            'retry|>=' => $max_retry,
            'last_modified|<' => (int)($now-$every_time),
        );
        $apiObj->update($updateSdf,$updateFilter);

        // 将支付或者退款请求的同步任务设置为失败
        if (!empty($log_ids)){
            $log_ids = implode(',', $log_ids);
            $where = " `log_id` in ($log_ids) ";
            kernel::database()->exec($sql.$where);
        }
        return true;
    }
    
    /**
     * 自动清除同步日志
     * 每天检测将超过(默认15,可配置)天的日志数据清除(暂移到一张备份表当中)
     */
    public function clean(){
        
        $time = time();
        $clean_time = &app::get('ome')->getConf('ome.api_log.clean_time');
        if (empty($clean_time)) $clean_time = 15;
        $where = " WHERE `createtime`<'".($time-$clean_time*24*60*60)."' ";
        $insert_sql = " SELECT * FROM `sdb_ome_api_log` {$where} ";
        
//        //备份到副表
//        $apicopy_sql = "INSERT IGNORE INTO `sdb_ome_api_log_copy` {$insert_sql} ";
//        $insert_result = kernel::database()->exec($apicopy_sql);
        //从主表删除
        $del_sql = " DELETE FROM `sdb_ome_api_log` $where ";
        kernel::database()->exec($del_sql);
        kernel::database()->exec('OPTIMIZE TABLE `sdb_ome_api_log`');
        return true;
    }
    
}