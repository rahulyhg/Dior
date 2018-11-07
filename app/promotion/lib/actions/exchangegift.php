<?php
class promotion_actions_exchangegift extends promotion_process{
    function checkData($data,&$msg){
		 
		return true;
		//echo "<pre>22";print_r($data);exit();
	} 
	
	function validateActions(&$order){
		if(isset($order['exchangegift'])){
			$arrExchangeGift=array();
			foreach($order['exchangegift'] as $rule_id=>$value){
				$action=$this->actions['exchangegift'][$rule_id];
				$action_conditions=unserialize(unserialize($action['action_conditions']));
				$totalSendNums=0;
				foreach($order['items'] as $k=>$products){
					if($products['exchangegift']==$rule_id){
						$exchangeGift=$action_conditions['exchangegift']['bn'];
						$nums=$products['nums'];
						$b_nums=$value['b_nums'];
						$e_nums=$value['e_nums'];
						$isAddup=$value['isAddup'];
						if($isAddup=="1"){
							$multiple=floor($nums/$b_nums);
							$sendNums=$multiple*$e_nums;
						}else{
							$sendNums=1;
						}
						$arrExchangeGift[$rule_id][$products['product_id']]['sendnums']=$sendNums;
						$arrExchangeGift[$rule_id][$products['product_id']]['bn']=$products['bn'];
						$arrExchangeGift[$rule_id][$products['product_id']]['order_id']=$order['order_id'];
						$arrExchangeGift[$rule_id][$products['product_id']]['name']=$products['name'];
						$arrExchangeGift[$rule_id][$products['product_id']]['goods_id']=$products['goods_id'];
						$totalSendNums=$totalSendNums+$sendNums;
					}
				}//foreach
				
				foreach($order['items'] as $k=>$products){
					if($products['bn']==$action_conditions['exchangegift']['bn']){
						if($products['nums']<=$totalSendNums){
							$arrExchangeGift[$rule_id][$products['product_id']]['sendnums']=0;
							$arrExchangeGift[$rule_id][$products['product_id']]['delete']='true';
						}else{
							$arrExchangeGift[$rule_id][$products['product_id']]['sendnums']=$products['nums']-$totalSendNums;
							$arrExchangeGift[$rule_id][$products['product_id']]['delete']='false';
						}
						$arrExchangeGift[$rule_id][$products['product_id']]['bn']=$products['bn'];
						$arrExchangeGift[$rule_id][$products['product_id']]['order_id']=$order['order_id'];
						$arrExchangeGift[$rule_id][$products['product_id']]['name']=$products['name'];
						$arrExchangeGift[$rule_id][$products['product_id']]['goods_id']=$products['goods_id'];
						break;
					}
				}//foreach
			}//foreach
			//执行
			if(!$this->doAction($arrExchangeGift)){
				return false;
			}
			return true;
		}
		return true;
	}
	
	function doAction($arrExchangeGift){//echo "<pre>4";print_r($arrExchangeGift);print_r($order);exit();
		$objOrders=app::get('ome')->model('orders');
		foreach($arrExchangeGift as $rule_id=>$products){
			foreach($products as $k=>$product){
				$order_id=$product['order_id'];
				$bn=$product['bn'];
				$quantity=$product['sendnums'];
				$goods_id=$product['goods_id'];
				$name=$product['name'];
				if(isset($product['delete'])){
					if($product['delete']=="true"){//删除原赠品
						$i_sql="DELETE FROM sdb_ome_order_items WHERE bn='$bn' AND order_id='$order_id' AND item_type='gift'";
						$o_sql="DELETE FROM sdb_ome_order_objects WHERE bn='$bn' AND order_id='$order_id' AND obj_type='gift'";
					}else{
						$i_sql="UPDATE sdb_ome_order_items SET nums='$quantity' WHERE bn='$bn' AND order_id='$order_id' AND item_type='gift'";
						$o_sql="UPDATE sdb_ome_order_objects SET quantity='$quantity' WHERE bn='$bn' AND order_id='$order_id' AND obj_type='gift'";
					}
					if($objOrders->db->exec($o_sql)){
						if($objOrders->db->exec($i_sql)){
							continue;
						}else{
							error_log("item失败:".$products['order_id'].":",3,DATA_DIR.'/iup/'.date("Ymd").'zjrorder.txt');
							return false;
						}
					}else{
						error_log("object失败:".$products['order_id'].":",3,DATA_DIR.'/iup/'.date("Ymd").'zjrorder.txt');
						return false;
					}
				}else{
					$o_sql="INSERT INTO sdb_ome_order_objects(order_id,obj_type,obj_alias,goods_id,bn,name,price,amount,quantity,pmt_price,sale_price) VALUES($order_id,'gift','赠品区块','$goods_id','$bn','$name',0,0,'$quantity',0,0)";
					if($objOrders->db->exec($o_sql)){
						$obj_id=$objOrders->db->lastinsertid();
						$i_sql="INSERT INTO sdb_ome_order_items(order_id,obj_id,product_id,bn,name,nums,item_type) VALUES($order_id,'$obj_id','$product_id','$bn','$name','$quantity','gift')";
						if($objOrders->db->exec($i_sql)){
							continue;
						}else{
							error_log("item失败:".$products['order_id'].":",3,DATA_DIR.'/iup/'.date("Ymd").'zjrorder.txt');
							return false;
						}
						
					}else{
						error_log("object失败:".$products['order_id'].":",3,DATA_DIR.'/iup/'.date("Ymd").'zjrorder.txt');
						return false;
					}
				}
			}//foreach
		}//foreach
		return true;
	}
}
