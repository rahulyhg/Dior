<?php
// require_once(dirname(__FILE__)."/config/config.php");
class taoexlib_params_cache
{

    static private $_cacheObj = null;

    private $_key = '';

    private $_expire_time = 1200;

	/**
	 *
	 * @param string $host
	 * @param int $port
	 */
	public function  __construct()
	{
		//$this->setKey($prefix);
		// $this->connect();
	}

    private function connect()
    {
        // if(!isset(self::$_cacheObj)){
        //     if(defined('PARAMS_CACHE_SERVER') && constant('PARAMS_CACHE_SERVER')){
        //         self::$_cacheObj = new Memcache;
        //         $config = explode(',', PARAMS_CACHE_SERVER);
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
        //         trigger_error('can\'t load PARAMS_CACHE_SERVER, please check it', E_USER_ERROR);
        //     }
        // }
    }


    private function getKey($prefix){
        // return md5(sprintf('%s_%s', strtolower($_SERVER['SERVER_NAME']), $prefix));

        return $prefix;
    }

    /*
    private function getKey(){
        return $this->key;
    }
    */

    public function fetch($key, &$result)
    {
        $key = $this->getKey($key);

        // $result = self::$_cacheObj->get($key);

        $result = cachecore::fetch($key);
        if($result === false){
            return false;
        }else{
            return true;
        }
    }

    public function connClose(){
        // self::$_cacheObj->close();
    }

    public function store($key, $value, $expTime = 0)
    {
		$expTime = intval($expTime);

		if ($expTime <=0 ) {
			$expTime = $this->_expire_time; 
		} 

        $key = $this->getKey($key);
        // return self::$_cacheObj->set($key, $value, MEMCACHE_COMPRESSED, $expTime);

        return cachecore::store($key, $value, $expTime);
    }

}