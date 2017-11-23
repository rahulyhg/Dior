<?php
class giftcard_wechat_request{
	
	public function __construct($app) {
        $this->app = $app;
		$this->arrSetting=$this->app->getConf("giftcard_setting");
		$this->wx_access_token=kernel::single("giftcard_wxtoken")->get();
		$this->js_access_token=kernel::single("giftcard_jstoken")->get();
    }
	
	public function post($type,$method,$post=array(),$api_method='',$key_id='',&$msg=''){
		$objLogs=$this->app->model('logs');
		$arrLogs=array();
		$arrLogs['order_bn']=$key_id;
		$arrLogs['status']='fail';
		$arrLogs['api_method']=$api_method;
		$arrLogs['request']=$post;
		$arrLogs['response']='';
		$arrLogs['createtime']=time();
		$arrLogs['msg']='';
		$objLogs->save($arrLogs);
		
		//本地日志记录传什么
		$log_dir=DATA_DIR.'/wechat/'.$api_method.'/';
		if(!is_dir($log_dir))$res = mkdir($log_dir,0777,true);//创建日志目录
		error_log(date("Y-m-d H:i:s").$post,3,$log_dir.date("Ymd").'zjrorder.txt');
			
		switch($type){
			case 1://wx
				$access_token=$this->wx_access_token;
				break;
			case 2://js
				$access_token=$this->js_access_token;
				break;
			default:
				return false;
		}
		if(empty($access_token)){
			$objLogs->update(array('response'=>'access_token获取失败','status'=>'fail','msg'=>'fail'),array('log_id'=>$arrLogs['log_id']));
			return false;
		}
	
		$url=$this->arrSetting['wxurl'].$method."?access_token=".$access_token;
		$headers = array(
			"Content-type: application/json;charset='utf-8'","Accept: application/json","Cache-Control: no-cache","Pragma: no-cache",
		);

	    $ch = curl_init();//初始化一个cURL会话
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT,2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
		$output = curl_exec($ch); 
		$api_status = curl_getinfo($ch);
		curl_close($ch);
		$result=json_decode($output,true);

		error_log('Response:'.date("Y-m-d H:i:s").$output,3,$log_dir.date("Ymd").'zjrorder.txt');
		
		if($result['errcode']=="0"){
			$objLogs->update(array('response'=>$output,'status'=>'succ','msg'=>'succ'),array('log_id'=>$arrLogs['log_id']));
			return $result;
		}else{
			$objLogs->update(array('response'=>$api_status['http_code'].$output,'status'=>'fail','msg'=>'fail'),array('log_id'=>$arrLogs['log_id']));
			if($api_status['http_code']=="0"){
				$msg='timeout';
			}else{
				$msg=$result['errmsg'];
			}
			return false;
		}
		
		return $result;
	}
	
	public function object_array($array) { 
	    if(is_object($array)) { 
	        $array = (array)$array; 
		}if(is_array($array)) { 
	        foreach($array as $key=>$value) { 
		        $array[$key] = $this->object_array($value); 
			} 
		} 
		return $array; 
	}
}
