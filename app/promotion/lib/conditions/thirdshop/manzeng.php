<?php
class promotion_conditions_thirdshop_manzeng extends promotion_process{
    function checkData($data,&$msg){
		return true;
	} 
	
	function validateConditions(&$order){
		$arrConditions=$this->checkTime($this->conditions['manzeng'],$order);
		if(empty($arrConditions))return true;
		$o_money=$order['payed'];
		$i=0;
		foreach($arrConditions as $k=>$actions){
			$actions['conditions']=unserialize(unserialize($actions['conditions']));
			$money=$actions['conditions']['manzeng']['money'];
			$apart_from_pkg=$actions['conditions']['manzeng']['apart_from_pkg'];
			//$limitnums=$actions['conditions']['manzeng']['limitnum'];
			if($apart_from_pkg=="1"){
				foreach($order['items'] as $item_type){
					if($item_type['item_type']=='pkg'){
						unset($arrConditions[$k]);
						continue 2;
					}
				}
			}
			
			if($o_money<$money){
				unset($arrConditions[$k]);
				continue;
			}
			if($i>0){
				if($money>=$previousMoney){
					unset($arrConditions[$previousKey]);
				}else{
					unset($arrConditions[$k]);
					continue;
				}
			}
			$previousMoney=$money;
			$previousKey=$k;
			$i++;
		}
		if(empty($arrConditions))return true;
		
		foreach($arrConditions as $k=>$actions){
			$conditions=unserialize(unserialize($actions['conditions']));
			$limitnums=$conditions['manzeng']['limitnum'];
			if(!empty($limitnums)){
				$arrLimitNums=explode(',',$limitnums);
				foreach($arrLimitNums as $v){
					$arrLimitPorduct=explode(':',$v);
					$order[$actions['action']][$actions['rule_id']]['limitnums'][$arrLimitPorduct[0]]=$this->checkLimitBn($arrConditions[$k],$arrLimitPorduct);
				}
			}
			$order[$actions['action']][$actions['rule_id']]['nums']=1;
			$order[$actions['action']][$actions['rule_id']]['b_nums']=1;
			$order[$actions['action']][$actions['rule_id']]['e_nums']=1;
			$order[$actions['action']][$actions['rule_id']]['isAddup']='';
		}
		return true;
	}
}
