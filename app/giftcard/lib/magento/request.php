<?php
class giftcard_magento_request{
	
	public function __construct($app) {
        $this->app = $app;
		$this->arrSetting=$this->app->getConf("giftcard_setting");
    }
	
	public function post($post){
		$url=$this->arrSetting['magento_url'].$post;
		$ch = curl_init();//初始化一个cURL会话
		curl_setopt($ch, CURLOPT_URL,$url);
		//curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HEADER,0);
		$output = curl_exec($ch);
		$result=json_decode($output,true);
		
		return $result;
	}
	
}
