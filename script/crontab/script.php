#!/usr/bin/env php
<?php
$root_dir = realpath(dirname(__FILE__).'/../../');
require_once($root_dir."/script/crontab/runtime.php");

/*
* 获取需要执行的所有脚本列表
*/
/*
if ($service = kernel::servicelist('crontab.script')){
    $script = array();
    foreach ($service as $instance){
        $class_name = explode('_', get_class($instance));
        $app_name =  $class_name[0];
        if (method_exists($instance,'get')){
            $file = $instance->get();
            if ($file){
                foreach ((array)$file as $name){
                    $script[] = $app_name.'/'.trim($name);
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
echo @implode(' ', $script);