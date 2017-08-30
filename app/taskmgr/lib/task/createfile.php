<?php
/**
 * 分片导出数据组合任务类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_createfile extends taskmgr_task_abstract {

    protected $_process_id = 'task_id';

    protected $_gctime = 3600;

    protected $_timeout = 3600;

}