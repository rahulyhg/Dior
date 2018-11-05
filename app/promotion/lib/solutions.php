<?php
class promotion_solutions{
	function __construct($app){
        $this->app = $app;
    }
	
	function getSolution(){
		$ojbPromotion=$this->app->model('orders');
		$arrPromotion=$ojbPromotion->getList("*",array('status'=>'true'));
		
		if(empty($arrPromotion))return false;
		//echo "<pre>";print_r($arrPromotion);exit();
		foreach($arrPromotion as $k=>$v){
			$arrPromotion[$k]['conditions_serialize']=unserialize($v['conditions_serialize']);
			$arrPromotion[$k]['actions_serialize']=unserialize($v['actions_serialize']);
		}
		
		return $arrPromotion;
	}
	
}
