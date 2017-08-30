<?php
/**
 * 获取域名进入相应的定时任务队列抽象类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_timerintodq extends taskmgr_task_abstract {

    protected $_gctime = 3600;

	/**
     * 线程入口
     * 
     * @param void
     * @return void
     */
    public function run() {
        //标记开始工作 
        $this->worker->_start();
        //进入任务执行代码
        $this->doTimer();
        //标记任务完成
        $this->worker->_stop();

        $this->_complete = true;

    }

    public function doTimer(){
        $host = $this->getHost();

        while(true){
            $s_time = time();
            $now_task = str_replace('domainqueue','',$this->_taskName);

            $routerKey = sprintf('erp.task.%s.*', $now_task);
            $connecterClass = sprintf('taskmgr_connecter_%s', __CONNECTER_MODE);
            $rp_connecter = new $connecterClass();
            $rp_connecter->load($now_task, $this->_config);


            $params = array(
                'data' => array(
                    'mdkey' => md5($host),
                    'task_type' => $now_task
                ),
                'url' => 'http://'.$host.'/index.php/openapi/autotask/service'
            );

            $params['data']['sign'] = taskmgr_rpc_sign::gen_sign($params['data']);
            $msg = json_encode($params);

            $push_result = $rp_connecter->publish($msg, $routerKey);
            if($push_result !== false){
                $push_result = 'succ';
            }else{
                $push_result = 'fail';
            }

            //$rp_connecter->disconnect();

            $info = sprintf("%s\t%s\t%s", $host, 'into '.$now_task.' domain queue task', $push_result);
            taskmgr_log::log($this->_taskName,$info);


            $e_time = time();
            $use_time = $e_time - $s_time;
            if($use_time < $this->_looptime){
                $wait_time = $this->_looptime - $use_time;
                sleep($wait_time);
            }

            $gc = $this->isGC();
            if (!$gc) {
                break;
            }
        }
    }

    public function getHost(){
        return DOMAIN;
    }
}