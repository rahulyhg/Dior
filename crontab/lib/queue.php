<?php
/**
 * 队列
 * 
 * @author hzjsq@msn.com
 * @version 0.1b
 */
require_once(dirname(__FILE__) . '/redis/redis.php');
 
class queue extends php_redis {
    
    /**
   /**
	 * 队列KEY
	 * @var String
	 */
	private  $_key;
	
	/**
	 * 服务器IP地址
	 * @var String
	 */
	private $_host;
	
	/**
	 * 服务器端口
	 * @var String
	 */
	private $_port;
    
    const __NORMAL_QUEUE = '_NORMAL_QUEUE';
	const __REALTIME_QUEUE = '_REALTIME_QUEUE';
	const __TIMING_QUEUE = '_TIMING_QUEUE';
    
    
    /**
     * 析构
     */
    public function __construct() {
        
        $this->setNormalLevel();
		$this->_host = TG_QUEUE_HOST;
		$this->_port = TG_QUEUE_PORT;
		
		parent::__construct($this->_host, $this->_port);   
    }
    
    /**
     * 加入队列
     * 
     * @param mixed $value 增加的值
     * @return void
     */
    public function push($value) {

        $this->append($this->_key, $value);    
    }
    
    /**
     * 获取要操作值
     * 
     * @param void
     * @return mixed
     */
    public function pop() {
        
        return $this->lpop($this->_key);
    }
    
	public function setNormalLevel(){
		$this->_key = self::__NORMAL_QUEUE;
		
		return $this;
	}
	
	public function setRealTimeLevel(){
		$this->_key = self::__REALTIME_QUEUE;
		
		return $this;
	}
	
	public function setTimingLevel(){
		$this->_key = self::__TIMING_QUEUE;
		
		return $this;
	}
	
	public function stop(){
		$this->set('QUEUE_STATUS', 0);
	}
	
	public function start(){
		$this->set('QUEUE_STATUS', 1);
	}
	
	public function is_start(){
		if($this->get('QUEUE_STATUS')){
			return true;
		}else{
			return false;
		}
	}
}    