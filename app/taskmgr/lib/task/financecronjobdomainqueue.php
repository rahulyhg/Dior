<?php
/**
 * 域名进入自动对账任务队列类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_financecronjobdomainqueue extends taskmgr_task_timerintodq {

    protected $_looptime = 3600;

}