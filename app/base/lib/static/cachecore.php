<?php

class cachecore {

	/*
     * @var boolean $_enable
     * @access static private
     */
    static private $_enable = false;

    /*
     * @var string $_instance
     * @access static private
     */
    static private $_instance = null;

    /*
     * @var string $_instance_name
     * @access static private
     */
    static private $_instance_name = null;

	/*
     * 初始化
     * @var boolean $with_cache
     * @access static public
     * @return void
     */
    static public function init() 
    {
        if(defined('CACHE_STORAGE') && constant('CACHE_STORAGE')){
            self::$_instance_name = CACHE_STORAGE;
            self::$_enable = true;
        }else{
            self::$_instance_name = 'base_cache_nocache';    //todo：增加无cache类，提高无cache情况下程序的整体性能
            self::$_enable = false;
        }
        self::$_instance = null;
    }//End Function

    /*
     * 是否启用
     * @access static public
     * @return boolean
     */
    static public function enable() 
    {
        return self::$_enable;
    }//End Function

    /*
     * 获取cache_storage实例
     * @access static public
     * @return object
     */
    static public function instance() 
    {
    	if(is_null(self::$_instance_name)) {
    		self::init();
    	}

        if(is_null(self::$_instance)){
            //self::$_instance = kernel::single(self::$_instance_name);
	    self::$_instance = new self::$_instance_name;
        }//使用实例时再构造实例
        return self::$_instance;
    }//End Function

    /*
     * 获取缓存key
     * @var string $key
     * @access static public
     * @return string
     */
    static public function get_key($key) 
    {
        return md5(sprintf('%s_%s', KV_PREFIX, $key));
    }//End Function

    /**
     * 获取缓存
     */
    static public function fetch($key) {

        if(self::instance()->fetch(self::get_key($key), $data)){
            if($data['expires'] > 0 && time() > $data['expires']){
                return false;   
            }//todo:人工设置过期功能判断
            
            return $data['content'];
        }else{
            return false;
        }
    }
    
    /**
     * 获取缓存
     */
    static public function store($key, $value, $ttl=0) {

        $data = array('content' => $value);
        $data['expires'] = ($ttl > 0) ? time() + $ttl : 0;       //todo: 设置过期时间
        return self::instance()->store(self::get_key($key), $data, $ttl);
    } 
}
