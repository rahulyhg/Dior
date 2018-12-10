<?php
class promotion_conditions_shop{
   
	function validateConditions($promotion,&$order){
		if(strpos($promotion['shop'],$order['shop_id'])===false){
			return false;
		}
        $source = explode(',', $promotion['source']);
        if(!in_array($order['order_refer_source'], $source)) {
            return false;
        }
		return true;
	}
}
