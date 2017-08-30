#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/config/config.php');
require_once(LIB_DIR . '/queue.php');

$queue_flag =  array('_REALTIME_QUEUE','_NORMAL_QUEUE','_TIMING_QUEUE');
$oQueue = new queue();

//if(isset($argv[1])){
	switch ($argv[1]){
		case 'help':
			echo " stop:\n start:\n all_delete:\n delete: N:\n";
			break;
		case 'view_info':
			/*if( isset($_GET['queue_flag']) && isset($queue_flag[$_GET['queue_flag']]) ){
				$flag = $queue_flag[$_GET['queue_flag']];
			}else if(isset($argv[1]) && isset($queue_flag[$argv[1]]) ){
				$flag = $queue_flag[$argv[1]];
			}else{
				echo 'no queue flag';
				exit;
			}
			echo $oQueue->get_list_length($flag);*/
			break;
		case 'stop':
			$oQueue->stop();
			echo 'stop';
			break;
		case 'start':
			$oQueue->start();
			echo 'start';
			break;
		case 'all_delete':
			foreach($queue_flag as $flag){
				$oQueue->delete($flag);
			}
			echo $argv[1].':delete';
			break;
		case 'delete':
			if(isset($argv[2])){
				$oQueue->delete($queue_flag[$argv[2]]);
				echo $argv[1].':delete';
			}else{
				echo '没有指定队列删除';
			}
			break;
		case 'pop':
			$param = $oQueue->pop();
			var_dump($param);
			break;
		default:
			echo 'queue stats:';
			echo $oQueue->is_start() ? 'start' : 'stop';
			echo "\n";
			foreach($queue_flag as $flag){
				echo $flag . ':' . $oQueue->get_list_length($flag) ."\n";
			}
			break;
	}
	
//}


