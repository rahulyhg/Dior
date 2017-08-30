<?php
/**
 * 域名进入后台导入队列任务队列类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_bgqueuedomainqueue extends taskmgr_task_timerintodq {

    protected $_looptime = 60;

}