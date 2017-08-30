<?php
/**
 * 导出数据存储介质外部调用接口类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

//加载配置信息
require_once(dirname(__FILE__) . '/../../config/config.php');

class taskmgr_interface_cache{

	private static $__cacheObj;

	public function __construct($task_id){
		if(!isset(self::$__cacheObj)){
	        $cacheClass = sprintf('taskmgr_cache_%s', __CACHE_MODE);
	        self::$__cacheObj = new $cacheClass($task_id);
    	}
	}

    //存储数据
    public function store($key, $value, $ttl=0){
        return self::$__cacheObj->store($key, $value, $ttl);
    }

    //读取数据
    public function fetch($key, &$result){
    	return self::$__cacheObj->fetch($key, $result);
    }

    //统计类数据自增
    public function increment($key, $value){
    	return self::$__cacheObj->increment($key, $value);
    }

    //删除缓存数据
    public function delete($key){
    	return self::$__cacheObj->delete($key);
    }
}
