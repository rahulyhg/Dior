<?php
/**
 * PHP 进程管理器
 * 
 * @author hzjsq@msn.com
 * @version 0.1b
 */
require_once(LIB_DIR . '/queue.php'); 
 
class orderManager {
    
    /**
     * PHP执行文件名
     * @var String
     */
    private $executable = "/usr/local/php/bin/php";
    
    /**
     * 脚本所在目录
     * @var String
     */
    private $script = "/data/httpd/taoguan/crontab/thread/threadOrder.php";
    
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
     * 同时运行线程数
     * @var Integer
     */
    private $maxThread = 1;
    
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
     * 开始执行任务
     * 
     * @param void
     * @return void
     */
    public function exec() {
        
        $oQueue = new queue();
        
        for(;;) {
            //执行死循环
            while (($this->threadRunning < $this->maxThread) && ($oQueue->get_list_length(queue::__KEY) > 0)) {
                
                echo sprintf("Total %s orders in queue\n", $oQueue->get_list_length(queue::__KEY));
                $this->running[] =& new thread ($this->executable, $this->script, ' ', 10);
                sleep(1);
                $this->threadRunning++;
            }
        
            //检查是否已经结束 
            if (($this->threadRunning == 0) && ($oQueue->get_list_length(queue::__KEY) == 0)) {
                    
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
