<?php

class taskmgr_whitelist{

    //进队列业务逻辑处理任务
    static public function task_list(){
        return $_tasks = array(
            'autochk'      => 'wms_autotask_check',
            'autodly'      => 'wms_autotask_consign',
            'autorder'     => 'ome_autotask_combine',#自动审单 ExBOY
            'autoretryapi' => 'erpapi_autotask_retryapi',
        );
    }

    //定时任务
    static public function timer_list(){
        return $_tasks = array(
            'bgqueue' => 'ome_autotask_bgqueue',
            'misctask' => 'ome_autotask_misctask',
            'inventorydepth'=>'ome_autotask_inventorydepth',
            'financecronjob'=>'ome_autotask_financecronjob',
        );
    }

    //初始化域名进任务队列,这里的命名规范就是实际连的队列任务+domainqueue生成这个初始化任务的数组值
    static public function init_list(){
        return $_tasks = array(
            'bgqueuedomainqueue',
            'misctaskdomainqueue',
            'inventorydepthdomainqueue',
            'financecronjobdomainqueue',
        );
    }

    //导出任务
    static public function export_list(){
        return $_tasks = array(
            'exportsplit' => 'ome_autotask_exportsplit',
            'dataquerybysheet' => 'ome_autotask_dataquerybysheet',
            'dataquerybyquicksheet' => 'ome_autotask_dataquerybyquicksheet',
            'dataquerybywhole' => 'ome_autotask_dataquerybywhole',
            'createfile' => 'ome_autotask_createfile',
        );
    }

    //全部任务
    static public function get_all_task_list(){
        return array_merge(self::task_list(),self::timer_list(),self::export_list());
    }

    static public function get_task_types(){
    	return array('task','timer','init','export');
    } 
}