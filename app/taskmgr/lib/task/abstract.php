<?php
/**
 * 任务对像抽像类
 *
 * @author hzjsq@foxmail.com
 * @version 0.1b
 */

class taskmgr_task_abstract extends Stackable{

    //GC时间
    protected $_gctime = 600;
    //任务完成状态
    protected $_complete = false;
    //任务数据
    protected $_taskName = null;
    //任务对列ID
    protected $_config = null;
    //任务开始时间
    protected $_starttime = null;
    //curl 超时时间
    protected $_timeout = 15;

    /**
     * 析构
     */
    public function __construct($task, $config) {

        $this->_taskName = $task;
        $this->_config = $config;
        $this->_starttime = time();
        $this->_complete = false;
    }

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
        //$this->doTask();
        $connecterClass = sprintf('taskmgr_connecter_%s', __CONNECTER_MODE);
        
        $connecter = new $connecterClass();
        $connecter->load($this->_taskName, $this->_config);
        $connecter->consume(array($this, 'doTask'));
        //标记任务完成
        $this->worker->_stop();
        $this->_complete = true;
    }

    /**
     * 获取任务是否已经完成
     * 
     * @param void
     * @return boolean
     */
    public function isComplete() {
        
        return $this->_complete;
    }

    /**
     * 任务执行入口
     *
     * @param object $message
     * @param object $queue
     * @return boolean
     */
    public function doTask($message, $queue = null){

        if (empty($queue)) {
            $queue = $message;
        }

        $t = microtime(true); 
        
        $body = $message->getBody();
        if($body){
            $content = json_decode($body, true);
            $response = $this->curl($content);
            //usleep(1000);
            $e = microtime(true); 

            $_tmp=parse_url($content['url']);  
            $domain = $_tmp['host'];

            $info = sprintf("%s\t%s\t%s\t%s bytes\t%s", $domain, $content['data'][$this->_process_id], $response['code'], $response['body'], $e-$t);
            taskmgr_log::log($this->_taskName,$info);

            if($response['code'] == 200 && !empty($response['body'])){
                $result = json_decode($response['body'],true);
                if($result['rsp'] != 'succ'){
                    $this->requeue($content);
                } else {
                    //succ do nothing
                }
            }else{
                $this->requeue($content);
            }
            
            //nack不起作用,信息请求处理完后判断结果以后，再判断是否要重新进队列
            $queue->ack($message->getDeliveryTag());
        }

        $gc = $this->isGC();
        if (!$gc) {
            if (method_exists($queue, 'cancel')) {
                $queue->cancel();
            }
        }
        return $gc;
    }

    /**
     * 重新入队列
     *
     * @param array $params
     * @return null
     */
     protected function requeue($params){
        //验签生成，数据压缩
        unset($params['data']['sign']);
        $params['data']['fails'] = isset($params['data']['fails']) ? $params['data']['fails']+1 : 1;

        //超过3次直接记日志丢掉
        if($params['data']['fails'] > 3){
            $info = sprintf("%s\t%s", $params['url'], $params['data'][$this->_process_id]);
            taskmgr_log::log($this->_taskName.'-fails',$info);
            return true;
        }

        $params['data']['sign'] = taskmgr_rpc_sign::gen_sign($params['data']);
        $msg = json_encode($params);

        $routerKey = sprintf('erp.task.%s.*', $params['data']['task_type']);
        $connecterClass = sprintf('taskmgr_connecter_%s', __CONNECTER_MODE);
        $rp_connecter = new $connecterClass();
        $rp_connecter->load($this->_taskName, $this->_config);
        $rp_connecter->publish($msg, $routerKey);
        $rp_connecter->disconnect();
     }

    /**
     * 是否重启
     *
     * @param void
     * @return boolean
     */
    public function isGC() {

        if (time() >= ($this->_starttime + $this->_gctime)) {

            return false;
        } else {

            return true;
        }
    }
    
    /**
     * 通过URL获取结果
     */
    public function curl($data) {
        
        $ch = curl_init();
        $url = $data['url'];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data['data']);
        $result = curl_exec($ch);
        $code =  curl_getinfo($ch,CURLINFO_HTTP_CODE); 
        //$result = explode(',', $curl_result);
        curl_close($ch);
        return array('code' => $code, 'body' => $result);
    }

}
