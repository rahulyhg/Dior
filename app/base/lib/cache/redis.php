<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

/*
 * @package base
 * @copyright Copyright (c) 2010, shopex. inc
 * @author edwin.lzh@gmail.com
 * @license 
 */
class base_cache_redis extends base_cache_abstract implements base_interface_cache
{
    static private $_cacheObj = null;

    function __construct() 
    {
        $this->connect();
        $this->check_vary_list();
    }//End Function

    public function connect() 
    {
        if(!isset(self::$_cacheObj)){
            if(defined('CACHE_REDIS_CONFIG') && constant('CACHE_REDIS_CONFIG')){
                self::$_cacheObj = new Redis;
                $config = explode(':', CACHE_REDIS_CONFIG);
                self::$_cacheObj->connect($config[0], $config[1]);
            }else{
                trigger_error('can\'t load CACHE_REDIS_CONFIG, please check it', E_USER_ERROR);
            }
        }
    }//End Function

    public function fetch($key, &$result) 
    {
        $result = self::$_cacheObj->get($key);
        if($result === false){
            return false;
        }else{
	    $result = json_decode($result, true);
            return true;
        }
    }//End Function

    public function store($key, $value, $ttl=0) 
    {
	$value = json_encode($value);
        return self::$_cacheObj->setex($key, $ttl, $value);
    }//End Function

    public function status() 
    {
        //$status = self::$_cacheObj->info();
        //$return['缓存获取'] = $status['cmd_get'];
        //$return['缓存存储'] = $status['cmd_set'];
        //$return['可使用缓存'] = $status['limit_maxbytes'];
        $return = array();
        return $return;
    }//End Function


    /**
     * 是否支持同步的自增单号处理
     */
    public function supportUUID() {

        return true;
    }

    /**
     * 累加
     */
    public function increment($key, $offset=1)
    {
        return self::$_cacheObj->incr($key, $offset);
    }//End Function

    /**
     * 递减
     */
    public function decrement($key, $offset=1)
    {
        return self::$_cacheObj->decr($key, $offset);
    }//End Function
    
}//End Class
