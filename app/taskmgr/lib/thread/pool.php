<?php
/**
 * 任务管理池
 *
 * @author hzjsq@foxmail.com
 * @version 0.1b
 */

class taskmgr_thread_pool extends Pool {

	/**
	 * 像对列提交数据
	 *
	 * @param object $data
	 * @return void
	 */
	public function submit($data) {

		$workId= $this->getAvaiWorkId();

		if ($workId === null) {

			parent::submit($data);
		} else {

			parent::submitTo($workId, $data);
		}
	}

	/**
	 * 获取空闲线程
	 *
	 * @param void
	 * @return integer
	 */
	private function getAvaiWorkId() {

		if (empty($this->workers) ||  count($this->workers) < $this->size) {
			return null;
		}

		while(true) {

			foreach ($this->workers as $id => $worker) {

				if (!$worker->_isRuning()) {

					return $id;
				}
			}

			usleep(1000);
		}
	}
}
?>