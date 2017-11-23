<?php
class giftcard_queue{	
	
	public function __construct(&$app){
		$this->app = $app;
	}
	
	public function doQueue(){
		$objQueue=$this->app->model("queue");
		$arrQueue=$objQueue->getList("*",array('status|in'=>array(0,1)));
		
		foreach($arrQueue as $queue){
			$queue['status']=1;
			$objQueue->update($queue,array('id'=>$queue['id']));
			if(kernel::single('giftcard_queue_'.$queue['queue_type'])->run($queue)){
				$queue['status']=2;
				$objQueue->update($queue,array('id'=>$queue['id']));
			}
		}
		
	}
	
}
