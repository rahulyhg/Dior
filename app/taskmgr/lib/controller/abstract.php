<?php
/**
 * 控制器基类
 *
 * @author hzjsq@foxmail.com
 * @version 0.1b
 */

abstract class taskmgr_controller_abstract extends Thread {

	//最大运行线程数
	protected $_maxThreadNums 	= 5;
	//数据提供者对像
	protected $_taskName		= null;
	//Work对像名
	protected $_workerclass		= 'taskmgr_thread_worker';
	//线程池对像
	protected $_pool 			= null;
	
	protected $_config          = null;

	/**
	 * 析构
	 */
    public function __construct($task){

        $this->_taskName = strtolower($task);
        $_fix = sprintf('__%s_CONFIG', strtoupper(__CONNECTER_MODE));
        $this->_config = $GLOBALS[$_fix];
        //$this->_config = $config;
        //$this->_pool = new taskmgr_thread_pool($this->_maxThreadNums, $this->_workerclass);
    }
    
    public function run() {

        $this->synchronized(function($thread){
            $thread->wait();
        }, $this);

        $className = sprintf('taskmgr_task_%s',strtolower($this->_taskName));
        
        $pool = new taskmgr_thread_pool($this->_maxThreadNums, $this->_workerclass);
        for($i=0; $i<$this->_maxThreadNums; $i++) {

            $pool->submit(new $className($this->_taskName,$this->_config));
        }
    }

}