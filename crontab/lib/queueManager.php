<?php
/**
 * PHP 进程管理器
 * 
 * @author hzjsq@msn.com
 * @version 0.1b
 */
require_once(LIB_DIR . '/queue.php'); 
 
class queueManager {
    
    /**
     * PHP执行文件名
     * @var String
     */
    private $executable = "/usr/local/php/bin/php";
    
    /**
     * 脚本所在目录
     * @var String
     */
    private $script;
    
    /**
     * 所有执行的所有 HOSTS列表
     * @var Array
     */
    private $params = array();
    
    /**
     * 当前运行的线程数
     * @var Integer
     */
    private $threadRunning = 0;
    
    
    /**
     * 运行中的线程对象
     * var Array
     */
    private $running = array();
     
    /**
     * 等待时间
     * @var Integer
     */
    private $waitTime = 5000;
    
    
     /**
     * 同时运行线程数
     * @var Integer
     */
    private $maxThread = 5;
    
     /**
     * 队列线程分配
     * @var Integer
     */
    private $queueMaxThread = array('_REALTIME_QUEUE'=>0,'_NORMAL_QUEUE'=>5);
    
     /**
     * 队列线程分配
     * @var Integer
     */
    private $queueRunning = array('_REALTIME_QUEUE'=>0,'_NORMAL_QUEUE'=>0);
    
   
    
    public function __construct(){
    	$this->script = ROOT_DIR .'thread/threadQueue.php';
    }
    
    /**
     * 开始执行任务
     * 
     * @param void
     * @return void
     */
    public function exec() {
        
        $oQueue = new queue();
        
        for(;;) {
            //执行死循环
            while (($this->threadRunning < $this->maxThread)) {
            	
            	//检查队列是否开启
            	if(!$oQueue->is_start()){
            		
            		return true;
            	}
                
            	//实时队列优先 开启队列数根据队列分配来决定
            	if($oQueue->get_list_length(queue::__REALTIME_QUEUE) > 0 && $this->queueRunning[queue::__REALTIME_QUEUE] < $this->queueMaxThread[queue::__REALTIME_QUEUE]){
	                echo sprintf("Total %s queue in realtime queue\n", $oQueue->get_list_length(queue::__REALTIME_QUEUE));
	                $this->running[] =& new thread ($this->executable, $this->script, queue::__REALTIME_QUEUE , 3600);
	                usleep(200000);
	                $this->threadRunning++;
	                $this->queueRunning[queue::__REALTIME_QUEUE]++;
            	}else if($oQueue->get_list_length(queue::__NORMAL_QUEUE) > 0 && $this->queueRunning[queue::__NORMAL_QUEUE] < $this->queueMaxThread[queue::__NORMAL_QUEUE]){
            		echo sprintf("Total %s queue in narmal queue\n", $oQueue->get_list_length(queue::__NORMAL_QUEUE));
	                $this->running[] =& new thread ($this->executable, $this->script,queue::__NORMAL_QUEUE , 3600);
	                usleep(200000);
	                $this->threadRunning++;
	                $this->queueRunning[queue::__NORMAL_QUEUE]++;
            	}else{
            		break;
            	}
            }
        
            //检查是否已经结束 
            if (($this->threadRunning == 0) && ($oQueue->get_list_length(queue::__REALTIME_QUEUE) == 0) && ($oQueue->get_list_length(queue::__NORMAL_QUEUE) == 0) ) {
                    
                break;
            }
            
            //等待代码执行完成
              $this->sleep($this->waitTime);
      
              //检查已经完成的任务
            foreach ($this->running as $idx => $thread) {
                
                if (!$thread->isRunning() || $thread->isOverExecuted()) {
                    
                    //if (!$thread->isRunning()) 
                    //    echo sprintf("Done: %s\n", $thread->param);
                    //else 
                    //    echo sprintf("Kill: %s\n", $thread->param);
                    
                    proc_close($thread->resource);
                    //相应类型队列减少
                    $this->queueRunning[$thread->param]--;
                    unset($this->running[$idx]);
                    
                    $this->threadRunning--;
                }
            }
        }
    }
    
    /**
     * 等待，空循环
     * 
     * @param integer $times 循环次数
     * @return void 
     */
    private function sleep($times) {
        
        for ($i=0; $i<$times; $i++);
    }
}

class thread {
    
    /**
     * 句柄
     * @var resource
     */
    public $resource;
    
    /**
     * 管道
     * @var resource
     */
    public $pipes;
    
    /**
     * 执行参数
     * @var String
     */
    public $param;
    
    /**
     * 超时时长
     * @var Integer
     */
    private $maxExecTime;
    
    /**
     * 脚本开始执行时间
     * @var Integer
     */
    private $startTime;
    
    /**
     * 析构
     * 
     * @param string $executable PHP执行文件名
     * @param string $script PHP脚本名
     * @param string $param 脚本执行参数
     * @param integer $maxExecTime 超时间设置
     * @return void
     */
    function __construct(&$executable, &$script, $param, $maxExecTime) {
        
        $this->param = $param;
        $this->maxExecTime = $maxExecTime;
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $this->resource = proc_open($executable." ".$script." ".$this->param, $descriptorspec, $this->pipes, null, $_ENV);
        $this->startTime = time();
    }
    
    /**
     * 检查任务是否运行中
     * 
     * @param void
     * @return boolean
     */
    function isRunning() {
        
        $status = proc_get_status($this->resource);
        return $status["running"];
    }

    /**
     * 检查运行是否超时
     * 
     * @param void
     * @return boolean
     */
    function isOverExecuted() {
        
        if (($this->startTime + $this->maxExecTime) < time()) 
            return true;
        else 
            return false;
    }
}


