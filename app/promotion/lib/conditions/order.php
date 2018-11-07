<?php
class promotion_conditions_order{
    function checkData(&$data,&$msg){
		if($data['conditions']['order']['use']=="1"){
			$min=$data['conditions']['order']['min'];
			$max=$data['conditions']['order']['max'];
			if(empty($min)||empty($max)){
				$msg="订单金额区间不能为空";
				return false;
			}
			if($min<0||$max<0){
				$msg="订单金额区间必须大于0";
				return false;
			}
			if($min>=$max){
				$msg="订单金额最大值必须大于最小值";
				return false;
			}
			$data['rule']['conditions'][]='order';
		}
		
		
		return true;
	} 
	
	function validateConditions($promotion,&$order){
		if(strpos($promotion['conditions'],'order')===false){
			return true;
		}
		
		$order_total_amount=round($order['payed'],2);
		$min=round($promotion['conditions_serialize']['order']['min'],2);
		$max=round($promotion['conditions_serialize']['order']['max'],2);
		if($min<=$order_total_amount&&$order_total_amount<$max){
			return true;
		}
		return false;
	}
}
