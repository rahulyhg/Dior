<?php
/**
 * 校验任务类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_autochk extends taskmgr_task_abstract {

    protected $_process_id = 'log_id';

    protected $_gctime = 1800;

    protected $_timeout = 120;

}