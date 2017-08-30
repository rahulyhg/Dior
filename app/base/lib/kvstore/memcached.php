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
class base_kvstore_memcached extends base_kvstore_abstract implements base_interface_kvstore_base, base_interface_kvstore_extension 
{
    static private $_cacheObj;

    function __construct($prefix) 
    {
        $this->connect();
        $this->prefix = $prefix;
    }//End Function

    public function connect() 
    {
        if(!isset(self::$_cacheObj)){
            if(defined('KVSTORE_MEMCACHE_CONFIG') && constant('KVSTORE_MEMCACHE_CONFIG')){
                self::$_cacheObj = new Memcached;
                $config = explode(',', KVSTORE_MEMCACHE_CONFIG);
                foreach($config AS $row){
                    $row = trim($row);
                    if(strpos($row, 'unix:///') === 0){
                        //self::$_cacheObj->addServer($row, 0);
                    }else{
                        $tmp = explode(':', $row);
                        self::$_cacheObj->addServer($tmp[0], $tmp[1]);
                    }
                }
            }else{
                trigger_error('can\'t load KVSTORE_MEMCACHE_CONFIG, please check it', E_USER_ERROR);
            }
        }
    }//End Function

    public function fetch($key, &$value, $timeout_version=null) 
    {
        $store = self::$_cacheObj->get($this->create_key($key));
        if(self::$_cacheObj->getResultCode() != Memcached::RES_NOTFOUND){
            if($timeout_version < $store['dateline']){
                if($store['ttl'] > 0 && ($store['dateline']+$store['ttl']) < time()){
                    return false;
                }
                $value = $store['value'];
                return true;
            }
        }
        return false;
    }//End Function

    public function store($key, $value, $ttl=0) 
    {
        $store['value'] = $value;
        $store['dateline'] = time();
        $store['ttl'] = $ttl;
        return self::$_cacheObj->set($this->create_key($key), $store);
    }//End Function

    public function delete($key) 
    {
        return self::$_cacheObj->delete($this->create_key($key));
    }//End Function

    public function recovery($record) 
    {
        $key = $record['key'];
        $store['value'] = $record['value'];
        $store['dateline'] = $record['dateline'];
        $store['ttl'] = $record['ttl'];
        return self::$_cacheObj->set($this->create_key($key), $store);
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
        $ret = self::$_cacheObj->increment($this->create_key($key), $offset);

        if ($ret === false) {

            if(self::$_cacheObj->getResultCode() == Memcached::RES_NOTFOUND){
                //key 在memcache 中不存在，需创建
                if (self::$_cacheObj->set($this->create_key($key), $offset, 86400*32)) {

                    $ret = $offset;
                } else {

                    $ret = false;
                }
            }
        }
        return $ret;
    }//End Function

    /**
     * 递减
     */
    public function decrement($key, $offset=1)
    {
        $ret = self::$_cacheObj->decrement($this->create_key($key), $offset);

        if ($ret === false) {
            if(self::$_cacheObj->getResultCode() == Memcached::RES_NOTFOUND){ 
                //key 在memcache 中不存在，需创建
                $value = 0 - $offset;
                if (self::$_cacheObj->set($this->create_key($key), $value, 86400*32)) {

                    $ret = $value;
                } else {

                    $ret = false;
                }
            }
        }

        return $ret;
    }//End Function
}//End Class
