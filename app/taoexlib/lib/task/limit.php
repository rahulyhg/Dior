<?php
// require_once(dirname(__FILE__)."/config/config.php");
class taoexlib_task_limit
{

    static private $_cacheObj = null;

    private $_key = '';

    private $_prefix = 'autotask_limit';

    private $_expire_time = 180;

    static $__RUNNING = 1;

    static $__FINISH = 2;

	/**
	 *
	 * @param string $host
	 * @param int $port
	 */
	public function  __construct()
	{
		$this->setKey();
	}

    private function connect()
    {
        // if(!isset(self::$_cacheObj)){
        //     if(defined('LIMIT_CACHE_CONFIG') && constant('LIMIT_CACHE_CONFIG')){
        //         self::$_cacheObj = new Memcache;
        //         $config = explode(',', LIMIT_CACHE_CONFIG);
        //         foreach($config AS $row){
        //             $row = trim($row);
        //             if(strpos($row, 'unix://') === 0){
        //                 self::$_cacheObj->addServer($row, 0);
        //             }else{
        //                 $tmp = explode(':', $row);
        //                 self::$_cacheObj->addServer($tmp[0], $tmp[1]);
        //             }
        //         }
        //     }else{
        //         trigger_error('can\'t load LIMIT_CACHE_CONFIG, please check it', E_USER_ERROR);
        //     }
        // }
    }

    private function setKey(){
        // $this->key = md5(sprintf('%s_%s', strtolower($_SERVER['SERVER_NAME']), $this->_prefix));
        $this->key = $this->_prefix;
    }

    private function getKey(){
        return $this->key;
    }

    private function fetch(&$result)
    {
        $key = $this->getKey();
        // $result = self::$_cacheObj->get($key);

        $result = cachecore::fetch($key);
        if($result === false){
            return false;
        }else{
            return true;
        }
    }

    private function connClose(){
        // self::$_cacheObj->close();
    }

    private function store($value)
    {
        $key = $this->getKey();
        // return self::$_cacheObj->set($key, $value, MEMCACHE_COMPRESSED, $this->_expire_time);

        return cachecore::store($key, $value, $this->_expire_time);
    }

    public function available(){

        // $this->connect();

        if(!$this->fetch($status) || $status != self::$__RUNNING){
            return true;
        }else{
            return false;
        }
    }

    public function setStatus($status){
        return $this->store($status);
    }

}