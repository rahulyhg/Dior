<?php
class giftcard_pos_api{
	
	public $log_id;
	
	public function __construct($app) {
        $this->app = $app;
	}

	public function process($path){
		if(!kernel::is_online()){
            die('error');
        }else{
            require(ROOT_DIR.'/config/config.php');
            @include(APP_DIR.'/base/defined.php');
        }
		
		set_error_handler(array(&$this,'error_handle'),E_ERROR);
        set_error_handler(array(&$this,'user_error_handle'),E_USER_ERROR);
		
		try {
			$arrSetting=$this->app->getConf("giftcard_setting");
			$post=json_decode(urldecode($_POST['post_data']),true);
			$api_client_id=$arrSetting['api_client_id'];
			$token=$arrSetting['postoken'];
			$code=$post['code'];
			$sign=strtoupper(md5(strtoupper(md5($code)).$token.$api_client_id));
			
			list($channel_type, $business, $method) = explode('.',$post['method']);
			
			$objLog=$this->app->model('logs');
			$log_dir=DATA_DIR.'/pos/'.$method.'/';
			if(!is_dir($log_dir))$res = mkdir($log_dir,0777,true);//创建日志目录
			
			error_log('Request:'.$_POST,3,$log_dir.date("Ymd").'zjrorder.txt');
			
			$arrLogs=array();
			$arrLogs['order_bn']=$post['code'];
			$arrLogs['code']=$post['code'];
			$arrLogs['status']='fail';
			$arrLogs['api_method']=$method;
			$arrLogs['api_type']='response';
			$arrLogs['request']=json_encode($post);
			$arrLogs['response']='';
			$arrLogs['createtime']=time();
			$arrLogs['ip']=kernel::single("giftcard_func")->getIp();
			$objLog->save($arrLogs);
			$this->log_id=$arrLogs['log_id'];
			
			if(empty($sign)||$sign!=$post['sign']){
				$this->send_user_error($arrLogs['log_id'],'fail','sign Error','503');
			}
			
			$objClass=kernel::single('giftcard_pos_response_'.$method);
			$result=$objClass->{$method}($post);
			$this->send_user_error($arrLogs['log_id'],$result['status'],$result['msg'],$result['api_code'],$result['data']);
		}catch (Exception $e) {
            trigger_error('Unknow exception',E_USER_ERROR);
        }
    }
	
	
	function error_handle($error_code, $error_msg){
        $this->send_user_error($this->log_id,'fail',$error_msg,'403');
    }

    function user_error_handle($error_code, $error_msg){
        $this->send_user_error($this->log_id,'fail',$error_msg,'403');
    }

    public function send_user_error($log_id,$status='fail',$err_msg,$api_code='',$data=array()){
		$objLog=$this->app->model('logs');
        $res = array(
            'result'      =>$status,
            'msg'      => $err_msg,
			'code'	   =>$api_code
        );
		if($data['type']=="2"){
			unset($data['type']);
			$res=array_merge($res,$data);
		}
        $filter=array();
		$filter['status']=$status;
		$filter['response']=json_encode($res);
		$filter['msg']=$err_msg;
	 	$objLog->update($filter,array('log_id'=>$log_id));
		echo urlencode(json_encode($res));exit();
    }
	
}