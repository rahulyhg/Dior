<?php
$start_time = time();//开始时间
set_time_limit(0);

ilog('start queue');

$lib_dir = dirname(__FILE__) .'/../../script/lib';
require_once($lib_dir.'/init.php');

$db = kernel::database();
$row = $db->selectrow("select * from sdb_taoexlib_ietask where status='sleeping' order by task_id asc");
if(!$row){
	$end_time = time();
	ilog('start time:'.date('Y-m-d H:i:s',$start_time) . ' end time:'.date('Y-m-d H:i:s',$end_time) . ' queue is delete');
	exit;
}

$apiObj = kernel :: single('base_httpclient');

//$arr = explode('@', $param['worker']);
$class = 'taoexlib_ietask';
$method = 'export_id';
$params = array('task_id'=> $row['task_id'], 'use_slave_db' => '0');
$obj = kernel::single($class);
$errmsg = array();

//捕获运行时错误
//set_error_handler('_setErrorHandler');

if(method_exists($class, $method)){
	//$apiObj->post('http://'.$domain . '/crontab/api/api.php', array('api'=>'queue_running','queue_id'=>$queue_id)); 
	
	if($obj->$method($params)){
		$status = 'succ';
	}else{
		$status = 'failed';
	}
	
	$end_time = time();//完成时间
	$spend_time = $end_time-$start_time;
	//$apiObj->post('http://'.$domain . '/crontab/api/api.php', array('api'=>'queue_finish','queue_id'=>$queue_id,'spend_time'=>$spend_time,'status'=>$status)); 
	
	ilog('result:'.$status);
}else{
	$end_time = time();//完成时间
	$spend_time = $end_time-$start_time;
	//$apiObj->post('http://'.$domain . '/crontab/api/api.php', array('api'=>'queue_paused','queue_id'=>$queue_id,'spend_time'=>$spend_time)); 
	ilog('method no exists');
}

if(!empty($errmsg)){
	ilog('errmsg:');
	ilog(implode("\n", $errmsg));
}

ilog('start time:'.date('Y-m-d H:i:s',$start_time) . ' end time:'.date('Y-m-d H:i:s',$end_time) . ' spend:'.$spend_time);

/**
 * 日志
 */
function ilog($str) {

	global $domain;
	$filename = dirname(__FILE__) . '/../logs/' . date('Y-m-d') . '.log';
	$fp = fopen($filename, 'a');
	fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
	fclose($fp);
}

function _setErrorHandler($errno, $errstr, $errfile, $errline){
      global $errmsg;
      
      $str = 'errno:' . $errno . ' errstr:' . $errstr . ' errfile:' . $errfile . ' errline:' . $errline;
	  if(!in_array($str, $errmsg)){
	  	  $errmsg[] = $str;
	  }
      //ilog($str);
	  
	  return false;
}
