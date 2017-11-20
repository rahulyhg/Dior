<?php
class giftcard_jing_api{
	
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
			
			if(isset($GLOBALS['HTTP_RAW_POST_DATA'])&&!empty($GLOBALS['HTTP_RAW_POST_DATA'])){
				$data=(array)simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'],'SimpleXMLElement',LIBXML_NOCDATA);
				$MsgType=$data['MsgType'];
				$Event=$data['Event'];
				 
				error_log(date("Y-m-d H:i:s").'Request:'.$Event,3,DATA_DIR.'/jingtest/'.date("Ymd").'zjrorder.txt');
				
				$log_dir=DATA_DIR.'/jing/';
				if(!is_dir($log_dir))$res = mkdir($log_dir,0777,true);//创建日志目录
				
				if($MsgType=="event"){
					switch($Event){
						case 'giftcard_pay_done':
							error_log('Request:'.$Event.'-'.json_encode($data),3,$log_dir.date("Ymd").'zjrorder.txt');
							$objLog=$this->app->model('logs');
							$arrLogs=array();
							$arrLogs['order_bn']=$data['OrderId'];
							$arrLogs['status']='fail';
							$arrLogs['api_method']='getOrderId';
							$arrLogs['api_type']='response';
							$arrLogs['request']=json_encode($data);
							$arrLogs['response']='';
							$arrLogs['createtime']=time();
							$objLog->save($arrLogs);
							
							$objClass=kernel::single('giftcard_jing_response_order');
							$result=$objClass->order($data);
							$this->send_user_error($arrLogs['log_id'],$result['status'],$result['msg'],$result['data']);
							break;
						case 'giftcard_user_accept':
							error_log('Request:'.$Event.'-'.json_encode($data),3,$log_dir.date("Ymd").'zjrorder.txt');
							$objLog=$this->app->model('logs');
							$arrLogs=array();
							$arrLogs['order_bn']=$data['OrderId'];
							$arrLogs['status']='fail';
							$arrLogs['api_method']='acceptcard';
							$arrLogs['api_type']='response';
							$arrLogs['request']=json_encode($data);
							$arrLogs['response']='';
							$arrLogs['createtime']=time();
							$objLog->save($arrLogs);
							
							$objClass=kernel::single('giftcard_jing_response_order');
							$result=$objClass->update($data);
							$this->send_user_error($arrLogs['log_id'],$result['status'],$result['msg'],$result['data']);
							break;
						case 'user_get_card':
							error_log('Request:'.$Event.'-'.json_encode($data),3,$log_dir.date("Ymd").'zjrorder.txt');
							$objLog=$this->app->model('logs');
							$arrLogs=array();
							$arrLogs['order_bn']=$data['OldUserCardCode'];
							$arrLogs['code']=$data['UserCardCode'];
							$arrLogs['status']='fail';
							$arrLogs['api_method']='usergetcard';
							$arrLogs['api_type']='response';
							$arrLogs['request']=json_encode($data);
							$arrLogs['response']='';
							$arrLogs['createtime']=time();
							$objLog->save($arrLogs);
							
							$objClass=kernel::single('giftcard_jing_response_order');
							$result=$objClass->card($data);
							$this->send_user_error($arrLogs['log_id'],$result['status'],$result['msg'],$result['data']);
							break;
					}
				}
			}
			$res = array(
				'rsp'      =>'succ',
				'msg'      =>'',
				'data'	   =>''
			);
			echo json_encode($res);exit();
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
		$filter['status']=$status;
		$filter['response']=json_encode($res);
		$filter['msg']=$err_msg;
	 	$objLog->update($filter,array('log_id'=>$log_id));
		echo json_encode($res);exit();
    }
	
}