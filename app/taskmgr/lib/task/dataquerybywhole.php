<?php
/**
 * 一次性查询取数据任务类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_dataquerybywhole extends taskmgr_task_abstract {

    protected $_process_id = 'task_id';

    protected $_gctime = 7200;

    protected $_timeout = 3600;

}