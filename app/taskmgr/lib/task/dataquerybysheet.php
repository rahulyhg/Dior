<?php
/**
 * 分片导出取数据任务类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_dataquerybysheet extends taskmgr_task_abstract {

    protected $_process_id = 'curr_sheet';

    protected $_gctime = 3600;

    protected $_timeout = 600;

}