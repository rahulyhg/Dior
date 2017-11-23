<?php
class giftcard_magento_request{
	
	public function __construct($app) {
        $this->app = $app;
		$this->arrSetting=$this->app->getConf("giftcard_setting");
    }
	
	public function post($post){
		$url=$this->arrSetting['magento_url'].$post;
		$output=file_get_contents($url);
		$result=json_decode($output,true);
		return $result;
	}
	
}
