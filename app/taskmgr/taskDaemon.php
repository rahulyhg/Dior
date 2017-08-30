#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) .'/shell/shell.php');

class taskDaemon {
    /* config */
    const pidfile = __CLASS__;
    const uid   = 0;
    const gid   = 0;

    public function __construct() {
        
    }

    private function daemon(){

        if (file_exists($this->pidfile)) {

            echo "The file $this->pidfile exists.\n";
            exit();
        }

        $pid = pcntl_fork();

        if ($pid == -1) {

             die('could not fork');
        } else if ($pid) {

            exit($pid);
        } else {
            file_put_contents($this->pidfile, getmypid());
            posix_setuid(self::uid);
            posix_setgid(self::gid);
            return(getmypid());
        }
    }

    private function start(){

        $pid = $this->daemon();

        $tasks = array();

		switch ($this->type) {
			case 'task':
				$tasks = $this->startTask();
				break;
			case 'timer':
				$tasks = $this->startTimer();
				break;
            case 'init':
				$tasks = $this->startInit();
				break;
            case 'export':
                $tasks = $this->startExport();
                break;
			default:
				$tasks_1 = $this->startTask();
				$tasks_2 = $this->startTimer();
                $tasks_3 = $this->startInit();
                $tasks_4 = $this->startExport();
				$tasks = array_merge($tasks_1,$tasks_2,$tasks_3,$tasks_4);
				break;
		}

        foreach($tasks as $key => $nthread){
            $nthread->synchronized(function($thread){
                $thread->notify();
            }, $nthread);
        }

    }

    private function startTask(){
        //一般业务处理任务
        foreach(taskmgr_whitelist::task_list() as $_task => $method) {
            $obj = new taskmgr_controller_task($_task);
            $obj->start();
            $tasks[$_task] = $obj;
        }
        $this->fixvirtualbtbug('taskmgr_controller_task', $tasks);
        return $tasks;
    }

    private function startTimer(){
        //定时任务
        foreach(taskmgr_whitelist::timer_list() as $_task => $method) {

            $obj = new taskmgr_controller_timer($_task);
            $obj->start();
            $tasks[$_task] = $obj;
        }
        $this->fixvirtualbtbug('taskmgr_controller_timer', $tasks);
        return $tasks;
    }

    private function startInit(){
        //初始化任务
        foreach(taskmgr_whitelist::init_list() as $_task) {

            $obj = new taskmgr_controller_init($_task);
            $obj->start();
            $tasks[$_task] = $obj;
        }
        $this->fixvirtualbtbug('taskmgr_controller_init', $tasks);
        return $tasks;
    }

    private function startExport(){
        //导出任务
        foreach(taskmgr_whitelist::export_list() as $_task => $method) {
            
            $obj = new taskmgr_controller_export($_task);
            $obj->start();
            $tasks[$_task] = $obj;
        }
        $this->fixvirtualbtbug('taskmgr_controller_export', $tasks);
        return $tasks;
    }

    private function fixvirtualbtbug($classname, &$tasks){
        //增加一个伪线程任务在最后
        $fix_task = 'fixvirtualbtbug';
        $obj = new $classname($fix_task);
        $obj->start();
        $tasks[$fix_task] = $obj;
    }

    private function stop(){

        if (file_exists($this->pidfile)) {

            $pid = file_get_contents($this->pidfile);
            posix_kill($pid, 9);
            unlink($this->pidfile);
        }
    }
    
    private function help($proc){

        printf("%s start | stop | help \n", $proc);
    }


    public function main($argv){

        if(count($argv) < 2){

            printf("please input help parameter\n");
            exit();
        }

        if(isset($argv[2]) && in_array($argv[2],taskmgr_whitelist::get_task_types())){
            $this->pidfile = '/var/run/erp-'.self::pidfile.'-'.$argv[2].'.pid';
            $this->type = $argv[2];
        }else{
            $this->pidfile = '/var/run/erp-'.self::pidfile.'.pid';
            $this->type = 'all';
        }
        
        if($argv[1] === 'stop'){
            $this->stop();
        }else if($argv[1] === 'start'){
            $this->start();
        }else{
            $this->help($argv[0]);
        }
    }
}

$taskDaemon = new taskDaemon();
$taskDaemon->main($argv);
