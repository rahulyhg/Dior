<?php
/**
 *  定时任务类控制器
 */

class taskmgr_controller_timer extends taskmgr_controller_abstract {
    
    protected $_maxThreadNums   = 1;

    /**
     * 创建线程，启动任务
     *
     * @param void
     * @return void
     */
    public function run() {
        //线程先不启动，等待全部创建完成
        $this->synchronized(function($thread){
            $thread->wait();
        }, $this);
        	
        $className = sprintf('taskmgr_task_%s',strtolower($this->_taskName));
        while (true) {
            $pool = new Pool($this->_maxThreadNums, $this->_workerclass);

            for($i=0; $i<$this->_maxThreadNums; $i++) {
                $pool->submit(new $className($this->_taskName,$this->_config));
            }

            $pool->shutdown();
            $pool->collect(function($work){
                return $work->isComplete();
            });

            unset($pool);
            taskmgr_log::log('system','The ' . $this->_taskName . ' POOL IS GC Now !!!!');
        }
    }
}
