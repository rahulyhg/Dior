<?php
/**
 * @ windows下自动执行的脚本
 */


//-------------------------------------------------------

if(isset($_SERVER['SERVER_NAME']) || isset($_SERVER['SERVER_PROTOCOL'])){
    echo "Hey! It's forbidden for you! Get out!";
    exit;
}

$root_dir = realpath(dirname(__FILE__).'/../../');
require_once($root_dir."/config/config.php");
define('APP_DIR',ROOT_DIR."/app/");

require_once(APP_DIR.'/base/kernel.php');
if(!kernel::register_autoload()){
    require(APP_DIR.'/base/autoload.php');
}

@include_once(APP_DIR.'/base/defined.php');

//-------------------------------------------------------

//---常量定义---
if (PHP_OS == 'WINNT')
    define('PHP_EXEC',dirname(ini_get('extension_dir')).'/php');
else
    define('PHP_EXEC',PHP_BINDIR.'/php');
define('EXEC_TIME',1);//单位秒
//-------------

 //需要执行的脚本列表
 /*
if ($service = kernel::servicelist('crontab.script')){
    $scripts = array();
    foreach ($service as $instance){
        $class_name = explode('_', get_class($instance));
        $app_name =  $class_name[0];
        if (method_exists($instance,'get')){
            $file = $instance->get();
            if ($file){
                foreach ((array)$file as $name){
                    $scripts[] = $app_name.'/'.trim($name);
                }
            }
        }
    }
}
*/
$support_app = array('omeanalysts');
if(!empty($support_app))
{
	foreach($support_app as $app_name)
	{
		$obj_name = $app_name.'_crontab_getscript';
		$obj = new $obj_name;
		if (method_exists($obj,'get')){
			$file = $obj->get();
			if ($file){
				foreach ((array)$file as $name){
					$script[] = $app_name.'/'.trim($name);
				}
			}
		}
	}
}

$process = array();
foreach ($scripts as $value) {

    $tmp = process_load($value);
    if ($tmp) {

        $process[] = $tmp;
    
    }

}

while(true) {

    if (count($process) == 0) break;
    
    sleep(EXEC_TIME);
    
    foreach ($process as $idx => $value) {

        process_over($value,$idx);

    }

}

echo "\n\nfinish";


    //载入进程
function process_load($script)
{
    $file_ident = md5($script);

    base_kvstore::instance('lockscript_ome')->fetch($file_ident,$lock_pid);
    if ($lock_pid) {

        settype($lock_pid,'string');
        $taskStr = exec('tasklist /FI "pid eq '.$lock_pid.'" /FO table');
        if ($taskStr && strpos($taskStr,$lock_pid)) {
            
            return;
        }

    }

    $descriptorspec = array(
       0 => array('pipe', 'r'),
       1 => array('pipe', 'w'),
       2 => array('file', DATA_DIR.'/logs/win_auto_script.log','a')
    );

    $process = proc_open(PHP_EXEC . ' ' . APP_DIR . $script, $descriptorspec, $pipes, null, $_ENV);
    if (is_resource($process)) {

        $return = array('resource'=>$process,'ident'=>$file_ident,'file'=>$script);
        $sts = proc_get_status($process);
        base_kvstore::instance('lockscript_ome')->store($file_ident,$sts['pid']);
        echo "\nload: ".APP_DIR.$script;
    
    } else
        $return = '';

    return $return;
}

    //结束进程
function process_over($value,$idx)
{

    $sts = proc_get_status($value['resource']);
    
    if ($sts['running'] === false) {

        base_kvstore::instance('lockscript_ome')->delete($value['ident']);
        unset($GLOBALS['process'][$idx]);
        if (proc_close($value['resource'])) {
            echo "\nover_error: ".APP_DIR.$value['file'];
        } else {
            echo "\nover: ".APP_DIR.$value['file'];
        }
    
    }/* else {

        base_kvstore::instance('lockscript_ome')->fetch($value['ident'],$lock_pid);
        if ($lock_pid && time() > $lock_pid + 4) {

            base_kvstore::instance('lockscript_ome')->delete($value['ident']);
            proc_close($value['resource']);
            unset($GLOBALS['process'][$idx]);
            echo "\nforce over: ".APP_DIR.$value['file'];

        }

    }*/
}
