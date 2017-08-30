<?php
/**
 * 定时任务抽象处理类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_timer extends taskmgr_task_abstract {

    protected $_gctime = 3600;

    public function doTask($message, $queue = null){
        
        if (empty($queue)) {
            $queue = $message;
        }

        $body = $message->getBody();
        if($body){
            $content = json_decode($body, true);
            $response = $this->curl($content);

            $_tmp=parse_url($content['url']);  
            $domain = $_tmp['host'];

            $info = sprintf("%s\t%s\t%s", $domain, 'trigger '.$this->_taskName.' task', $response['code']);
            taskmgr_log::log($this->_taskName,$info);
            
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
}