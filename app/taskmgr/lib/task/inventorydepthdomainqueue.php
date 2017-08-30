<?php
/**
 * 域名进入库存回写定时任务队列类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_task_inventorydepthdomainqueue extends taskmgr_task_timerintodq {

    protected $_looptime = 300;

}