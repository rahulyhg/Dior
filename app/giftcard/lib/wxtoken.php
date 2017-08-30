<?php
class giftcard_wxtoken{
	
	public function __construct($app) {
        $this->app = $app;
		$this->arrSetting=$this->app->getConf("giftcard_setting");
		$this->objToken=$this->app->model('token');
    }
	
	public function set(){
		$accessToken=false;
		if($result=$this->post()){
			$arrWxToken=array();
			$arrWxToken['name']='WxToken';
			$arrWxToken['access_token']=$result['access_token'];
			$arrWxToken['createtime']=time();
			$arrWxToken['expires_in']=$result['expires_in'];
			if($this->objToken->save($arrWxToken)){
				$accessToken=$result['access_token'];
			}
		}
		return $accessToken;
	}
	
	public function get(){
		$arrWxToken=$this->objToken->getList("*",array('name'=>'WxToken'));
		$arrWxToken=$arrWxToken[0];
		if(empty($arrWxToken)){
			$accesstoken=$this->set();
		}else{
			$time=time()-300;
			if($time-$arrWxToken['expires_in']>$arrWxToken['createtime']){//超时重新获取
				$accesstoken=$this->set();
			}else{
				$accesstoken=$arrWxToken['access_token'];
			}
		}
		return $accesstoken;
	}
	
	public function post(){
		$url=$this->arrSetting['wxurl']."/cgi-bin/token?grant_type=client_credential&appid=".$this->arrSetting['wxappid']."&secret=".$this->arrSetting['wxappsecret'];
		
		$objLogs=$this->app->model('logs');
		$arrLogs=array();
		$arrLogs['order_bn']='Get Wechat Token';
		$arrLogs['status']='fail';
		$arrLogs['api_method']='wchattoken';
		$arrLogs['request']=json_encode($url);
		$arrLogs['response']='';
		$arrLogs['createtime']=time();
		$arrLogs['msg']='';
		$objLogs->save($arrLogs);
		
		$return=file_get_contents($url);
		$result=json_decode($return,true);
		if(isset($result['access_token'])&&!empty($result['access_token'])){
			$objLogs->update(array('response'=>$return,'status'=>'succ','msg'=>'succ'),array('log_id'=>$arrLogs['log_id']));
			return $result;
		}else{
			$objLogs->update(array('response'=>$return,'status'=>'fail','msg'=>'fail'),array('log_id'=>$arrLogs['log_id']));
			return false;
		}	
	}
	
}
