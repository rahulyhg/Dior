<?php
class giftcard_magento_api{
	
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
			$post=$_POST['data'];
			$params=json_decode(base64_decode($post),true);
			$token=$params['token'];
			$api_method=$params['api_method'];
	
			$objLog=$this->app->model('logs');
			$log_dir=DATA_DIR.'/magento/'.$api_method.'/';
			if(!is_dir($log_dir))$res = mkdir($log_dir,0777,true);//创建日志目录
			
			error_log('Request:'.$post,3,$log_dir.date("Ymd").'zjrorder.txt');
			
			$arrLogs=array();
			$arrLogs['order_bn']=$params['order_bn'];
			$arrLogs['code']=$params['trade_no'];
			$arrLogs['open_id']=$params['open_id'];
			$arrLogs['status']='fail';
			$arrLogs['api_method']=$api_method;
			$arrLogs['api_type']='response';
			$arrLogs['request']=json_encode($params);
			$arrLogs['response']='';
			$arrLogs['createtime']=time();
			$arrLogs['ip']=kernel::single("giftcard_func")->getIp();
			$objLog->save($arrLogs);
			
			if(empty($token)||$token!=$arrSetting['jingtoken']){
				$this->send_user_error($arrLogs['log_id'],'fail','pwdError');
			}
			
			$objClass=kernel::single('giftcard_magento_response_'.$api_method);
			$result=$objClass->{$api_method}($params);
			$this->send_user_error($arrLogs['log_id'],$result['status'],$result['msg'],$result['data']);
		}catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_ERROR);
        }
    }
	
	
	function error_handle($error_code, $error_msg){
        $this->send_user_error('4007', $error_msg);
    }

    function user_error_handle($error_code, $error_msg){
        $this->send_user_error('4007', $error_msg);
    }

    public function send_user_error($log_id,$status='fail',$err_msg,$data=''){
		$objLog=$this->app->model('logs');
        $res = array(
            'rsp'      =>$status,
            'msg'      => $err_msg,
			'data'	   =>$data
        );
        $filter=array();
		$filter['order_bn']=$data;
		$filter['status']=$status;
		$filter['response']=json_encode($res);
		$filter['msg']=$err_msg;
	 	$objLog->update($filter,array('log_id'=>$log_id));
		echo json_encode($res);exit();
    }
	
}