<?php
class promotion_process{
	
	function __construct($app){
		set_time_limit(0);
		ini_set("memory_limit","256M"); 
		ini_set("max_execution_time",0); 
		ini_set("max_input_time",0);
        $this->app = $app;
		$this->promotion=kernel::single("promotion_solutions")->getSolution();
		$this->objPromotion=$this->app->model('orders');
    }
	
	function begin(){
		kernel::single("promotion_orders")->getOrdersData($data);
		kernel::single("promotion_orders")->getOrdersDetailsData($data);
		
		if(!empty($data)){
			if(count($this->promotion)<=0){
				foreach($data as $orders){
					$this->ToEnd($orders['order_id']);
				}
			}else{
				$this->process($data);
			}
		}
	}
	
	function process($data){
    	foreach($data as $order){
			$this->filterPromotion($order);
			$this->ToEnd($order['order_id']);
    	}
	} 
	
	function filterPromotion(&$order){
        if(empty($this->promotion)) {
            return true;
        }
        
		foreach($this->promotion as $promotion){
			//需要重写的
			if(isset($promotion['conditions_serialize']['relate'])){
				$promotion['conditions_serialize']['goods']['checkamount']=1;
				$promotion['conditions']=str_replace("order","",$promotion['conditions']);
			}
			//
			if ($service_conditions = kernel::servicelist('conditions_lists')){ 
				foreach($service_conditions as $object=>$instance){
			        if(method_exists($instance, 'validateConditions')){//echo "<pre>";print_r($service_conditions);print_r($promotion);exit();
					    if(!$instance->validateConditions($promotion,$order)){
							continue 2;
						}
					}
				}
			}
			//符合条件的应用优惠
			kernel::single("promotion_actions_".$promotion['actions'])->validateActions($promotion,$order);
		}
	}
	
	function ToEnd($order_id){
		$sql="UPDATE sdb_ome_orders SET is_u_g='true' WHERE order_id='$order_id'";
		return $this->objPromotion->db->exec($sql);
	}
}
