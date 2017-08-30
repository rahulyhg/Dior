<?php
class giftcard_jstoken{
	
	public function __construct($app) {
        $this->app = $app;
		$this->arrSetting=$this->app->getConf("giftcard_setting");
		$this->objToken=$this->app->model('token');
    }
	
	public function set(){
		$accessToken=false;
		if($result=$this->post()){
			$arrJsToken=array();
			$arrJsToken['name']='JsToken';
			$arrJsToken['access_token']=$result['access_token'];
			$arrJsToken['createtime']=time();
			$arrJsToken['expires_in']=$result['expires_in'];
			if($this->objToken->save($arrJsToken)){
				$accessToken=$result['access_token'];
			}
		}
		return $accessToken;
	}
	
	public function get(){
		$arrJsToken=$this->objToken->getList("*",array('name'=>'JsToken'));
		$arrJsToken=$arrJsToken[0];
		if(empty($arrJsToken)){
			$accesstoken=$this->set();
		}else{
			$time=time()-300;
			if($time-$arrJsToken['expires_in']>$arrJsToken['createtime']){//超时重新获取
				$accesstoken=$this->set();
			}else{
				$accesstoken=$arrJsToken['access_token'];
			}
		}
		return $accesstoken;
	}
	
	public function post(){
		$url=$this->arrSetting['jingurl']."/accessToken?appid=".$this->arrSetting['appid']."&secret=".$this->arrSetting['appsecret'];
	
		$objLogs=$this->app->model('logs');
		$arrLogs=array();
		$arrLogs['order_bn']='Get JX Wechat Token';
		$arrLogs['status']='fail';
		$arrLogs['api_method']='accesstoken';
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
