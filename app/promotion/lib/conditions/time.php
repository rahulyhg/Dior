<?php
class promotion_conditions_time{
   
	function validateConditions($promotion,&$order){
		if($order['createtime']<$promotion['from_time']||$order['createtime']>$promotion['to_time']||$order['paytime']<$promotion['from_time']||$order['paytime']>$promotion['to_time']){
			return false;
		}
		return true;
	}
}
