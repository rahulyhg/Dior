<?php
class promotion_conditions_goods{
    function checkData(&$data,&$msg){
		if(!empty($data['conditions']['goods']['product_id'][0])){
			if($data['conditions']['goods']['kinds']=="1"){
				if(empty($data['conditions']['goods']['nums'][0])||$data['conditions']['goods']['nums'][0]<=0){
					$msg='数量不能为空,并且大于0';
					return false;
				}
			}
			$data['rule']['conditions'][]='goods';
		}
		return true;
	} 
	
	function validateConditions($promotion,&$order){
		if(strpos($promotion['conditions'],'goods')===false){
			return true;
		}
		$conditions_kinds=$promotion['conditions_serialize']['goods']['kinds'];
		$conditions_type=$promotion['conditions_serialize']['goods']['type'];
		$conditions_isinclude=$promotion['conditions_serialize']['goods']['isinclude'];
		$conditions_range=$promotion['conditions_serialize']['goods']['range'];
		$conditions_goods=$promotion['conditions_serialize']['goods']['product_id'];
		
		//need delete
		$conditions_relate=$promotion['conditions_serialize']['goods']['checkamount'];
		
		$goods=array();
		$goods_nums=array();
		//need delete
		$goods_sales_price=array();
		foreach($order['order_objects'] as $key=>$product){
			$goods[$key]=$product['bn'];
			$goods_nums[$key]=$product['quantity'];
			//need delete
			$goods_sales_price[$key]=$product['sale_price'];
		}
	
		foreach($conditions_goods as $k=>$product_id){
			switch($promotion['conditions_serialize']['goods']['type']){
				case 'normal':
					$ojbPorduct=app::get('ome')->model('products');
					$arrPorudctBn=$ojbPorduct->getList("bn",array('product_id'=>$product_id));
					$conditions_goods[$k]=$arrPorudctBn[0]['bn'];
					break;
				case 'bind':
					$ojbPorduct = app::get('omepkg')->model('pkg_goods');
					$arrPorudctBn=$ojbPorduct->getList("pkg_bn",array('goods_id'=>$product_id));
					$conditions_goods[$k]=$arrPorudctBn[0]['pkg_bn'];
					break;
				default:
					return false;
			}
		}
		
		switch($conditions_kinds){
			case '1'://单商品
				$conditions_nums=$promotion['conditions_serialize']['goods']['nums'][0];
				if($conditions_isinclude=="1"){//如果包含
					foreach($goods as $key=>$bn){
						if(in_array($bn,$conditions_goods)&&$goods_nums[$key]>=$conditions_nums){
							return true;
						}
					}
					return false;
				}else{//如果排除
					//need update and delete
					if($conditions_relate=="1"){//关联金额部分
						$needsMinusAmount=0;
						foreach($goods as $key=>$bn){
							if(in_array($bn,$conditions_goods)){
								$needsMinusAmount=$goods_sales_price[$key];
								break;
							}
						}
						$total_amount=$order['payed'];
						$final_amount=$order['payed']-$needsMinusAmount;
						$min=round($promotion['conditions_serialize']['order']['min'],2);
						$max=round($promotion['conditions_serialize']['order']['max'],2);
						if($min<=$final_amount&&$final_amount<$max){
							return true;
						}
						return false;
					///////////////
					}else{
						foreach($goods as $key=>$bn){
							if(in_array($bn,$conditions_goods)){
								return false;
							}
						}
						return true;
					}
				}
				break;
			case '2'://多商品
				if($conditions_isinclude=="1"){//如果包含
					if($conditions_range=="1"){//包其中之一
						foreach($conditions_goods as $bn){
							if(in_array($bn,$goods)){
								return true;
							}
						}
						return false;
					}else{//全包
						foreach($conditions_goods as $bn){
							if(!in_array($bn,$goods))
								return false;
						}
						return true;
					}
				}else{//如果排除
					//need update and delete
					if($conditions_relate=="1"){//关联金额部分
						$needsMinusAmount=0;
						foreach($goods as $key=>$bn){
							if(in_array($bn,$conditions_goods)){
								$needsMinusAmount=$goods_sales_price[$key]+$needsMinusAmount;
							}
						}
						$total_amount=$order['payed'];
						$final_amount=$order['payed']-$needsMinusAmount;
						$min=round($promotion['conditions_serialize']['order']['min'],2);
						$max=round($promotion['conditions_serialize']['order']['max'],2);
						if($min<=$final_amount&&$final_amount<$max){
							return true;
						}
						return false;
						//echo "<pre>22";print_r($goods);print_r($order);print_r($needsMinusAmount);exit();
					//////
					}else{
						foreach($conditions_goods as $bn){
							if(in_array($bn,$goods))
								return false;
						}
						return true;
					}
				}
				break;
		}
		//echo "<pre>44";print_r($conditions_goods);print_r($goods);print_r($goods_nums);print_r($promotion);exit();
		
		return false;
	}
	
	function getEditData($conditions_list,$conditions){
		if(strpos($conditions,'goods')===false){
			return false;
		}
		$good_type=$conditions_list['goods']['type'];
		$arrData=array();
		if($good_type=="normal"){
			$ojbPorduct=app::get('ome')->model('products');
			foreach($conditions_list['goods']['product_id'] as $k=>$product_id){
				$arrPorudct=array();
				$arrPorudct=$ojbPorduct->getList("product_id,bn,name",array('product_id'=>$product_id));
				$arrPorudct=$arrPorudct[0];
				$arrData[$k]['bn']=$arrPorudct['bn'];
				$arrData[$k]['pkgtpl']="0";
				$arrData[$k]['name']=$arrPorudct['name'];
				$arrData[$k]['product_id']=$arrPorudct['product_id'];
				$arrData[$k]['visibility']='true';
				if($conditions_list['goods']['kinds']=='2'){
					$arrData[$k]['nums']=1;
				}else{
					$arrData[$k]['nums']=$conditions_list['goods']['nums'][$k];
				}
			}
		}else{
	        $ojbPorduct = app::get('omepkg')->model('pkg_goods');
			foreach($conditions_list['goods']['product_id'] as $k=>$goods_id){
				$arrPorudct=array();
				$arrPorudct=$ojbPorduct->getList("goods_id as product_id,pkg_bn as bn,name",array('goods_id'=>$goods_id));
				$arrPorudct=$arrPorudct[0];
				$arrData[$k]['bn']=$arrPorudct['bn'];
				$arrData[$k]['pkgtpl']="1";
				$arrData[$k]['name']=$arrPorudct['name'];
				$arrData[$k]['product_id']=$arrPorudct['product_id'];
				$arrData[$k]['visibility']='true';
				if($conditions_list['goods']['kinds']=='2'){
					$arrData[$k]['nums']=1;
				}else{
					$arrData[$k]['nums']=$conditions_list['goods']['nums'][$k];
				}
			}
		}
		return json_encode($arrData);
		//echo "<pre>";print_r($conditions);print_r($arrData);exit();
	}
}
