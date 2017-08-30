<?php
/**
 * 接口自动重试
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_autoretryapi extends taskmgr_task_abstract {

    protected $_process_id = 'log_id';

    protected $_gctime = 1800;

    protected $_timeout = 120;

   /**
    * undocumented function
    *
    * @return void
    * @author 
    **/
   public function doTask($message, $queue = null)
   {
        $body = $message->getBody();
        if(!$body) return true;
        
        $content = @json_decode($body, true);

        // 未达到指定时间重新丢队列
        if (time() < $content['data']['exectime']) {


            unset($content['data']['fails']);
            $this->requeue($content);

            $queue->ack($message->getDeliveryTag());

            $gc = $this->isGC();
            if (!$gc) {
                if (method_exists($queue, 'cancel')) {
                    $queue->cancel();
                }
            }
            return $gc;    
        }

        return parent::doTask($message, $queue);
   }
}