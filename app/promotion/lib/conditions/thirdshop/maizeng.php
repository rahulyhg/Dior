<?php
class promotion_conditions_thirdshop_maizeng extends promotion_process{
    function checkData($data,&$msg){
		if($data['conditions']['conditions']!="maizeng"){
			return true;
		}
		if($data['conditions']['maizeng']['isAddup']=="1"){
			if(empty($data['conditions']['maizeng']['b_nums'])||empty($data['conditions']['maizeng']['e_nums'])){
				$msg="请选择数量";
				return false;
			}
		}
		if(empty($data['conditions']['maizeng']['bn'])){
			$msg="请选择商品";
			return false;
		}
		 
		
		return true;
		//echo "<pre>22";print_r($data);exit();
	} 
	
	function validateConditions(&$order){
		$arrConditions=$this->checkTime($this->conditions['maizeng'],$order);
		if(empty($arrConditions))return true;
		foreach($arrConditions as $actions){
			$actions['conditions']=unserialize(unserialize($actions['conditions']));
			$maizeng_bn=$actions['conditions']['maizeng']['bn'];
			foreach($order['items'] as $k=>$item){
				if($item['item_type']=="gift")continue;
				$bn=$item['bn'];
				if($maizeng_bn==$bn&&$item['nums']>=$actions['conditions']['maizeng']['b_nums']){
					$order[$actions['action']][$actions['rule_id']]['nums']=$item['nums'];
					$order[$actions['action']][$actions['rule_id']]['b_nums']=$actions['conditions']['maizeng']['b_nums'];
					$order[$actions['action']][$actions['rule_id']]['e_nums']=$actions['conditions']['maizeng']['e_nums'];
					$order[$actions['action']][$actions['rule_id']]['isAddup']=$actions['conditions']['maizeng']['isAddup'];
				}
			}
		}
		
		return true;
	}
}
