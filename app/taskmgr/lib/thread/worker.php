<?php
/**
 * task worker 对像
 * 
 * @author hzjsq@foxmail.com
 * @version 0.1b
 */

class taskmgr_thread_worker extends Worker {

	//线程是否运行标记
	protected $_runing  = false;
    //
    protected $_task    = null;
    protected $_config  = null;
    
	/**
	 * 析构
	 */
	public function __construct() {
	   
	}

	/**
	 * 任务入口
	 *
	 * @param void
	 * @return void
	 */
	public function run() {
        
		//do nothing
		parent::run();			
	}

	/**
	 * 设置当前线程开始执行
	 *
	 * @param void
	 * @return void
	 */
	public function _start() {

		$this->_runing = true;
	}


	/**
	 * 设置线程运行结束标记
	 *
	 * @param void
	 * @return void
	 */
	public function _stop() {

		$this->_runing = false;
	}

	/**
	 * 获取线程是否正在运行
	 *
	 * @param void
	 * @return boolean
	 */
	public function _isRuning() {

		return $this->_runing;
	}
}