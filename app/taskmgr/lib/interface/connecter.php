<?php
/**
 * 任务存储介质外部调用接口类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

//加载配置信息
require_once(dirname(__FILE__) . '/../../config/config.php');

class taskmgr_interface_connecter{

    static private $_curr_connecter = array();

    public function push($params){
        $connecterClass = sprintf('taskmgr_connecter_%s', __CONNECTER_MODE);

        $_fix = sprintf('__%s_CONFIG', strtoupper(__CONNECTER_MODE));
        $this->_config = $GLOBALS[$_fix];
        
        if(!$params['data'] || !$params['url'] || !($task_type = $params['data']['task_type'])){
            return false;
        }
        
        $task_list = taskmgr_whitelist::get_all_task_list();
        if(isset($task_list[$task_type])){
            if(!isset(self::$_curr_connecter[$task_type])){
                $connecter = new $connecterClass();
                $connecter->load($task_type, $this->_config);
                self::$_curr_connecter[$task_type] = $connecter;
            }else{
                $connecter = self::$_curr_connecter[$task_type];
            }

            //验签生成，数据压缩
            $params['data']['sign'] = taskmgr_rpc_sign::gen_sign($params['data']);
            $msg = json_encode($params);

            $routerKey = sprintf('erp.task.%s.*', $task_type);
            $connecter->publish($msg, $routerKey);
            //$connecter->disconnect();

            return true;
        }else{
            return false;
        }
    }
}
