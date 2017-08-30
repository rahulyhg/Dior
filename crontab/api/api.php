<?php
$root_dir = realpath(dirname(__FILE__).'/../../');
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");

require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

$shell = new base_shell_loader;

$db = kernel::database();

switch ($_POST['api']){
	case 'queue_running':
		$db->exec('update sdb_taoexlib_queue set status="running",worker_active ='.time().' where queue_id='.$_POST['queue_id']);
		break;
	case 'queue_finish':
		$db->exec('update sdb_taoexlib_queue set status="'.$_POST['status'].'" ,worker_active ='.time().',spend_time='.$_POST['spend_time'].'  where queue_id='.$_POST['queue_id']);
		break;
	case 'queue_paused':
		$db->exec('update sdb_taoexlib_queue set status="paused" ,worker_active ='.time().' ,spend_time='.$_POST['spend_time'].' ,errmsg="no method" where queue_id='.$_POST['queue_id']);
		break;
	case 'ietask_running':
		$db->exec('UPDATE sdb_taoexlib_ietask SET last_time='.time().',status="running" WHERE task_id='.$_POST['task_id']);
		break;
	case 'ietask_finish':
		$db->exec('UPDATE sdb_taoexlib_ietask SET file_name="'.$_POST['file_name'].'",last_time='.time().',expire_time='.$_POST['expire_time'].',status="finished" WHERE task_id='.$_POST['task_id']);
		break;
	case 'ietask_finish_count':
		$db->exec('UPDATE sdb_taoexlib_ietask SET finish_count=finish_count+1,total_count=total_count+'.$_POST['total_count'].',last_time='.time().' WHERE task_id='.$_POST['task_id']);
		break;
	
}
