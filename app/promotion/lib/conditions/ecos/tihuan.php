<?php
class promotion_conditions_ecos_tihuan extends promotion_process{
    function checkData($data,&$msg){
		return true;
	} 
	
	function validateConditions(&$order){
		$arrConditions=$this->checkTime($this->conditions['tihuan'],$order);
		if(empty($arrConditions))return true;
		foreach($arrConditions as $actions){
			$actions['conditions']=unserialize(unserialize($actions['conditions']));
			foreach($actions['conditions']['tihuan']['bn'] as $product_id){
				foreach($order['items'] as $k=>$products){
					if($products['product_id']==$product_id){
						//$order[$actions['action']][$actions['rule_id']]=1;
						$order['items'][$k][$actions['action']]=$actions['rule_id'];
						//$order[$actions['action']][$actions['rule_id']]['nums']=$products['nums']+$order[$actions['action']][$actions['rule_id']]['nums'];
						$order[$actions['action']][$actions['rule_id']]['b_nums']=$actions['conditions']['tihuan']['b_nums'];
						$order[$actions['action']][$actions['rule_id']]['e_nums']=$actions['conditions']['tihuan']['e_nums'];
						$order[$actions['action']][$actions['rule_id']]['isAddup']=$actions['conditions']['tihuan']['isAddup'];
					}
				}
			}
		}
		return true;
	}
}
