<?php
class ome_finder_api_log{
    public static $row = array();
    function __construct(){
        if(!in_array($_GET['p'][0],array('fail','all','running')) && $_GET['p'][1] != 'response'){
            unset($this->column_retry);
        }
    }

    /* 详情
     *
     */
    function detail_log($log_id){
        $render = app::get('ome')->render();
        //$oApilog = &app::get('ome')->model("api_log");
        if(!isset(self::$row[$log_id]) || !self::$row[$log_id]){
            $oApilog = &app::get('ome')->model("api_log");
            $apilog = $oApilog->dump($log_id);
            self::$row[$log_id] = $apilog;
        }else{
            $apilog = self::$row[$log_id];
        }

        // 批量同步成功的msg_id
        $addon = unserialize($apilog['addon']);
        if (isset($addon['msg_id'])){
            $render->pagedata['batch_msg_id'] = implode(',', $addon['msg_id']);
        }

        $apilog['params'] = unserialize($apilog['params']);
        //发起
        $apilog['send_api_name'] = $apilog['params'][0];#API名称
        if (is_array($apilog['params'][1])){
            foreach ($apilog['params'][1] as $key=>$val){
                if ($key == 'all_list_quantity'){
                    $apilog['all_list_quantity'] = $val;
                    continue;//排除显示所有库存Bn，单独放在外面显示
                }
                if ($key == 'list_quantity'){
                    $params .= $key."(待更新库存):".$val."<br/>";
                }else{
                    $params .= $key."=".$val."<br/>";
                }
            }
        }
        $apilog['send_api_params'] = $params;
        $apilog['send_api_callback'] = $apilog['params'][2];

        $apilog_msg = @json_decode($apilog['msg'],true);
        $api_arr = false;
        $msg = '';
        if (is_array($apilog_msg)){
            $api_arr = true;
            $msg = '-';
            if (is_array($apilog_msg)){
                foreach ($apilog_msg as $key=>$val){
                    $msg .= $key."=".urldecode($val)."<br/>";
                }
            }
        }else{
            $msg = preg_replace('/<script.*?>.*?<\/script>/si','',$apilog['msg']);
            $code_msg = ome_api_func::api_code2msg($msg, $log_id);
            if (!empty($code_msg)){
                $msg = $code_msg;
            }
        }
        $apilog['msg'] = $msg;
        $apilog['api_arr'] = $api_arr;
        $render->pagedata['apilog'] = $apilog;
        return $render->fetch("admin/api/detail.html");
    }

    var $column_retry='操作';
    var $addon_cols = "status,api_type,msg,sync";
    var $column_retry_width = "50";
    var $max_retry = 3;

    function column_retry($row){
        $log_id = $row['log_id'];
        if(!isset(self::$row[$log_id]) || !self::$row[$log_id]){
            $oApilog = &app::get('ome')->model("api_log");
            $row = $oApilog->dump($log_id);
            self::$row[$log_id] = $row;
        }else{
            $row = self::$row[$log_id];
        }
        //if($row['msg'] != '请求超时' && $row['msg'] != '更新部分库存失败'){
            //return false;
        //}

        // 库存同步不允许重试
        if(stristr($row['task_name'],'的库存')){
        	return false;
        }

        $log_id = $row['log_id'];
        $api_type = $row['api_type'];
        $finder_id = $_GET['_finder']['finder_id'];
        $button = "<a class=\"btn\" href=\"index.php?app=ome&ctl=admin_api_log&act=retry&p[0]={$log_id}&finder_id={$finder_id}\" target=\"dialog::{title:'日志重试', width:550, height:300}\">重试</a>";

        if (($row['status']=='fail' and $api_type=='request') || (in_array($row['status'], array('running','sending')) && $api_type == 'request' && $row['last_modified'] < (time()-1800) ))
        return $button;
    }

    var $column_status = "状态";
    var $column_status_width = "80";
    var $column_status_order = 120;
    public function column_status($rows) {
        $log_id = $rows['log_id'];
        if(!isset(self::$row[$log_id]) || !self::$row[$log_id]){
            $oApilog = &app::get('ome')->model("api_log");
            $row = $oApilog->dump($log_id);
            self::$row[$log_id] = $row;
        }else{
            $row = self::$row[$log_id];
        }
        if($rows['retry']<$this->max_retry && $rows['status']=='fail' && $rows['msg'] == '请求超时') {
            return '系统重试中';
        }else{
            switch($rows['status']) {
                case 'running':
                    return '运行中';
                break;
                case 'success':
                    return '成功';
                break;
                case 'fail':
                    return '失败';
                break;
                case 'sending':
                    return '发起中';
                break;
            }
        }
    }

}
?>