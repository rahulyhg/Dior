<?php
/**
 * 导出数据文件存储介质外部调用接口类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

//加载配置信息
require_once(dirname(__FILE__) . '/../../config/config.php');

class taskmgr_interface_storage{

	private static $__storageObj;

	public function __construct(){
		if(!isset(self::$__storageObj)){
	        $storageClass = sprintf('taskmgr_storage_%s', __STORAGE_MODE);
	        self::$__storageObj = new $storageClass();
    	}
	}

    //存储数据成文件
    public function save($source_file, $task_id, &$url){
        return self::$__storageObj->save($source_file, $task_id, $url);
    }

    //读取文件获取数据
    public function get($url, $local_file){
    	return self::$__storageObj->get($url, $local_file);
    }

    //读取文件获取数据
    public function delete($url){
    	return self::$__storageObj->delete($url);
    }
}
