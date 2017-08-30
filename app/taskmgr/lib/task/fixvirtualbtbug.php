<?php
/**
 * 兼容虚拟机下连redis出现过的丢批量发货任务的线程的情况
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_fixvirtualbtbug extends taskmgr_task_abstract {

    protected $_gctime = 3600;

	/**
     * 线程入口
     * 
     * @param void
     * @return void
     */
    public function run() {
        //标记任务开始
        $this->worker->_start();

        sleep(10);

        $gc = $this->isGC();
        if (!$gc) {
            break;
        }

        //标记任务完成
        $this->worker->_stop();
        $this->_complete = true;
    }

}