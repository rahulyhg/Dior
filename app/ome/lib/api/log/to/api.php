<?php
class ome_api_log_to_api {

    /**
     * API同步日志备份队列处理
     * 每天检测将超过7天的日志数据清除(暂移到一张备份表当中)
     * @param $cursor_id 队列当前游标
     * @param $params 参数
     */
    function run(&$cursor_id,$params){

        $Sdf = $params['sdfdata'];
        $sdf_data = array();
        if ($Sdf)
        foreach ($Sdf as $k=>$v){
            //备份到副表
            $sql = "INSERT INTO `sdb_ome_api_log_copy` SELECT * FROM `sdb_ome_api_log` WHERE `log_id`='".$v."' ";
            $insert_result = kernel::database()->exec($sql);
            if ($insert_result){
                //从主表删除
                $del_sql = " DELETE FROM `sdb_ome_api_log` WHERE `log_id`='".$v."' ";
                kernel::database()->exec($del_sql);
            }
        }
        return false;
    }
    
    /**
     * 自动重试响应失败中的同步日志
     * @param $cursor_id 队列当前游标
     * @param $params 参数
     */
    function retry(&$cursor_id,$params){
        
        $log_ids = $params['sdfdata'];
        if ($log_ids){
            $apiObj = &app::get('ome')->model('api_log');
            foreach ($log_ids as $k=>$v){
                //获取日志同步信息
                $row = $apiObj->dump(array('log_id'=>$v), '*');
                if($row['retry']>3){
                    continue;
                }
                $worker = explode(":",$row['worker']);
                $class = $worker[0];
                $method = $worker[1];
                $log_params = unserialize($row['params']);
                if (isset($log_params[1]['all_list_quantity'])){
                    unset($log_params[1]['all_list_quantity']);
                }
                $log_id = $row['log_id'];
                //更新同步日志状态及重试次数
                $msg = '自动重试';
                $updateSdf = array(
                    'retry' => (string)($row['retry']+1),
                    'status' => 'sending',
                    'msg' => $msg,
                );
                $updateFilter = array(
                    'log_id' => $log_id
                );
                $apiObj->update($updateSdf,$updateFilter);
                //kernel::database()->exec(" UPDATE `sdb_ome_api_log` SET `retry`=`retry`+1,
                // `last_modified`='".time()."',`status`='sending',`msg`='".$msg."' WHERE log_id='".$log_id."'");
                
                //发起同步日志
                if($log_params){
                    $eval = "kernel::single('$class')->$method(";
                    if(is_array($log_params)){
                        $i = 0;
                        foreach ($log_params as $v){
                            $tmp_param[$i] = $v;
                            $tmp_param_string[] = "\$tmp_param[$i]";
                            $i++;
                        }
                        $eval .= implode(",",$tmp_param_string);
                    }else{
                        $eval .= $log_params;
                    }
                    $eval .= ");";
                    eval($eval);
                }
            }
            return false;
        }
    }
}
