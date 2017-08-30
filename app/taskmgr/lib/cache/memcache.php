<?php
/**
 * 导出数据存储memcache类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

class taskmgr_cache_memcache extends taskmgr_cache_abstract implements taskmgr_cache_interface
{

    static private $_cacheObj = null;

    function __construct($path){
        $this->connect();
    }

    public function connect() 
    {
        if(!isset(self::$_cacheObj)){
            if(defined('__MEMCACHE_CONFIG') && constant('__MEMCACHE_CONFIG')){
                self::$_cacheObj = new Memcache;
                $config = explode(',', __MEMCACHE_CONFIG);
                foreach($config AS $row){
                    $row = trim($row);
                    if(strpos($row, 'unix://') === 0){
                        self::$_cacheObj->addServer($row, 0);
                    }else{
                        $tmp = explode(':', $row);
                        self::$_cacheObj->addServer($tmp[0], $tmp[1]);
                    }
                }
            }else{
                trigger_error('can\'t load __MEMCACHE_CONFIG, please check it', E_USER_ERROR);
            }
        }
    }

    public function fetch($key, &$result) 
    {
        $key = $this->create_key($key);
        $result = self::$_cacheObj->get($key);
        if($result === false){
            return false;
        }else{
            return true;
        }
    }

    public function store($key, $value, $ttl=0) 
    {
        $key = $this->create_key($key);
        return self::$_cacheObj->set($key, $value, MEMCACHE_COMPRESSED, $ttl);
    }
    
    public function delete($key) 
    {
        $key = $this->create_key($key);
        return self::$_cacheObj->set($key, '', MEMCACHE_COMPRESSED, 1);
    }

    public function increment($key, $value=1){
        $key = $this->create_key($key);
        return self::$_cacheObj->increment($key, $value);
    }
}
