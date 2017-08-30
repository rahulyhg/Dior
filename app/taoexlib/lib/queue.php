<?php

class taoexlib_queue extends taoexlib_redis_redis {
	
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
	
	private $_queue_id;
	
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
	
	public function addLog($logfile,$data){
		$logfile = DATA_DIR.'/logs/'.date('Ymd').'/'.$logfile.'.log';
		if(!file_exists($logfile)){
            if(!is_dir(dirname($logfile)))  utils::mkdir_p(dirname($logfile));
        }
					
		error_log($data."\n",3,$logfile);	
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
	
    /**
     * 执行队列
     */
    public function run($type=null){
        return app::get('taoexlib')->model('queue')->flush($type);
    }
    
	function create($title, $worker, $params, $is_resume=null, $exec_mode=null, $exec_timeout=null){
        $queue = array(
            'queue_title' => $title,
            'worker' => $worker,
            'params' => $params,
            'start_time' => time(),
            'type' => $this->_key,
        	'host' => $_SERVER['SERVER_NAME'],
        );
        if ($is_resume){
            $queue['is_resume'] = $is_resume;
        }
        if ($exec_mode){
            $queue['exec_mode'] = $exec_mode;
        }
        if ($exec_timeout){
            $queue['exec_timeout'] = $exec_timeout;
        }
        
        $queueObj = app::get('taoexlib')->model('queue');
        $this->filter_value($queue);
        $queueObj->save($queue);
        if($queue['queue_id']){
            $this->push($queue);	
            $this->_queue_id = $queue['queue_id'];
        	return true;
        }else{
        	$this->_queue_id = 0;
        	return false;
        }
    }
    
	function filter_value(&$value){ 
	    if(is_array($value)){
	        foreach ($value as $k=>$v){
	            $this->filter_value($value[$k]);
	        }
	    }else{
	        $value = str_replace(array("\r\n","\r","\n"),'',$value);
	        
	    }
	}
    
    function resume($queue_id){
        if (!$queue_id) return false;
        $queueObj = app::get('taoexlib')->model('queue');
        $queue = $queueObj->dump($queue_id);
        if ($queue['is_resume'] == 'false') return false;
        $data = array(
            'status' => 'sleeping',
            'errmsg' => '',
        );
        $queueObj->update($data, array('queue_id'=>$queue_id));
        
        //再入队列执行
        $this->push($queue);
        
        return true;
    }
    
    function getId(){
    	
    	return $this->_queue_id;
    }
    
}