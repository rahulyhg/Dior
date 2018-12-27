<?php
class promotion_conditions_area{
    function checkData(&$data,&$msg){
		if(!empty($data['conditions']['region']['id'])){
			$data['rule']['conditions'][]='region';
		}
		return true;
	} 
	
    function validateConditions($promotion,&$order){
        if(strpos($promotion['conditions'], 'region') === false){
            return true;
        }

        $region_id = $order['region_id'];
        $region = explode(',', $promotion['conditions_serialize']['region']['id']);

        if(!in_array($region_id, $region)) {
            return false;
        }

        return true;
	}
}
