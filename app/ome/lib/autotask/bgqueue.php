<?PHP
/**
 * 后台队列任务处理类
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class ome_autotask_bgqueue
{
    public function process($params, &$error_msg=''){
        set_time_limit(0);
        ignore_user_abort(1);
        $queueObj = app::get('base')->model('queue');
        $queues = $queueObj->getList('queue_id',array(),0,100);
        if($queues){
            foreach ($queues as $queue) {
                $queueObj->runtask($queue['queue_id']);
            }
        }

        return true;
    }
}