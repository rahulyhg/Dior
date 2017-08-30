<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class base_session{
    
    private $_sess_id;
    private $_sess_key = 's';
    private $_session_started = false;
    private $_sess_expires = 60;
    private $_cookie_expires = 0;
    private $_session_destoryed = false;

    function __construct() 
    {
        if(defined('SESS_NAME') && constant('SESS_NAME'))    $this->_sess_key = constant('SESS_NAME');
        if(defined('SESS_CACHE_EXPIRE') && constant('SESS_CACHE_EXPIRE'))   $this->_sess_expires = constant('SESS_CACHE_EXPIRE');
    }//End Function
    
    public function sess_id(){
        return $this->_sess_id;
    }

    public function set_sess_expires($minute) 
    {
        $this->_sess_expires = $minute;
    }//End Function

    public function set_cookie_expires($minute) 
    {
        $this->_cookie_expires = ($minute > 0) ? $minute : 0;
        if(isset($this->_sess_id)){
            $cookie_path = kernel::base_url();
            $cookie_path = $cookie_path ? $cookie_path : "/";
            header(sprintf('Set-Cookie: %s=%s; path=%s; expires=%s; httpOnly;', $this->_sess_key, $this->_sess_id, $cookie_path, gmdate('D, d M Y H:i:s T', time()+$minute*60)), true);
        }
    }//End Function

    public function start(){
        if($this->_session_started !== true){
            $cookie_path = kernel::base_url();
            $cookie_path = $cookie_path ? $cookie_path : "/";
            if($this->_cookie_expires > 0){
                $cookie_expires = sprintf("expires=%s;",  gmdate('D, d M Y H:i:s T', time()+$this->_cookie_expires*60));
            }else{
                $cookie_expires = '';
            }
            if(isset($_GET['sess_id'])){
                $this->_sess_id = $_GET['sess_id'];
                if($_COOKIE[$this->_sess_key] != $_GET['sess_id'])
                    header(sprintf('Set-Cookie: %s=%s; path=%s; %s httpOnly;', $this->_sess_key, $this->_sess_id, $cookie_path, $cookie_expires), true);
            }elseif($_COOKIE[$this->_sess_key]){
                $this->_sess_id = $_COOKIE[$this->_sess_key];
            }elseif(!$this->_sess_id){
                $this->_sess_id = md5(microtime().base_request::get_remote_addr().mt_rand(0,9999));
                header(sprintf('Set-Cookie: %s=%s; path=%s; %s httpOnly;', $this->_sess_key, $this->_sess_id, $cookie_path, $cookie_expires), true);
            }
            if($this->getStore()->fetch($this->getKey($this->_sess_id), $_SESSION) === false){
                $_SESSION = array();
            }
            $this->_session_started = true;
            register_shutdown_function(array(&$this,'close'));
        }
        return true;
    }

    public function close($writeBack = true){
        if(strlen($this->_sess_id) != 32){
            return false;
        }
        if(!$this->_session_started){
            return false;
        }
        $this->_session_started = false;
        if(!$writeBack){
            return false;
        }
        if($this->_session_destoryed){
            return true;
        }else{
            return $this->getStore()->store($this->getKey($this->_sess_id), $_SESSION, ($this->_sess_expires * 60));
        }
    }
    
    public function destory(){
        if(!$this->_session_started){
            return false;
        }
        $this->_session_started = false;
        $res = $this->getStore()->store($this->getKey($this->_sess_id), array(), 1);
        if($res){
            $_SESSION = array();
            $this->_session_destoryed = true;
            $cookie_path = kernel::base_url();
            $cookie_path = $cookie_path ? $cookie_path : "/";
            header(sprintf('Set-Cookie: %s=%s; path=%s; httpOnly;', $this->_sess_key, $this->_sess_id, $cookie_path), true);
            unset($this->_sess_id);
            return true;
        }else{
            return false;
        }
    }

	/**
	 * 通过对常量　MEMCACHE 的识别返回不同的存储对像
	 *
	 * @author hzjsq
	 × @param void
	 * @return Object
	 */
	private function getStore() {
		
		if ($this->useCache()) {

			return cachecore::instance();
		} else {

			return base_kvstore::instance('sessions');	
		}
	}

	/**
	 * 通过对常量对缓存key进行适配
	 *
	 * @author hzjsq
	 × @param $key String
	 * @return String
	 */
	private function getKey($key) {
        
		if ($this->useCache()) {

			$key = 'S_' . $key; 
		}

		return $key;
	}

	/**
	 * 确定是否使用 MEMCACHE 缓存SESSION
	 *
	 * @author hzjsq
	 * @praram void
	 * @return Boolean
	 */
	private function useCache() {
		
		//判断是否具有缓存配置来判断能否使用Cache模块保存SESSION
		if(defined('CACHE_STORAGE') && in_array(constant('CACHE_STORAGE'), array('base_cache_memcache','base_cache_memcached','base_cache_redis'))){
			return true;
		} else {

			return false;
		}
	}
}
