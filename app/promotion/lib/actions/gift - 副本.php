<?php
class promotion_actions_gift{
    function checkData(&$data,&$msg){
		
		if(!is_array($data['actions_serialize']['gift']['bn'])){
			unset($data['actions_serialize']['gift']['bn']);
		}
		
		if(!is_array($data['actions_serialize']['gift']['pkg_bn'])){
			unset($data['actions_serialize']['gift']['pkg_bn']);
		}
		
		if(empty($data['actions_serialize']['gift']['bn'])&&empty($data['actions_serialize']['gift']['pkg_bn'])){
			$msg="请选择要赠送的商品";
			return false;
		} 
		return true;
		//echo "<pre>22";print_r($data);exit();
	} 
	
	function validateActions($promotion,&$order){
		$arrGift=array();
		$arrPkgPorudct=array();
		$arrGift['order_id']=$order['order_id'];
		$arrGift['rule_id']=$promotion['rule_id'];
		$products=$promotion['actions_serialize']['gift']['bn'];
		$nums=$promotion['actions_serialize']['gift']['nums'];
        $limit_nums=$promotion['actions_serialize']['gift']['limit_nums'];
        
		$pkg=$promotion['actions_serialize']['gift']['pkg_bn'];
		$pkg_nums=$promotion['actions_serialize']['gift']['pkg_nums'];
		$pkg_limit_nums=$promotion['actions_serialize']['gift']['pkg_limit_nums'];
        
		$i=0;
		if(!empty($pkg)){
			foreach($pkg as $k=>$goods_id){
				$ojbPorduct = app::get('omepkg')->model('pkg_product');
				$arrPkgPorudct=$ojbPorduct->getList("product_id,pkgnum",array('goods_id'=>$goods_id));
				foreach($arrPkgPorudct as $product){
					$arrGift['items'][$i]['product_id']=$product['product_id'];
                    $arrGift['items'][$i]['primary_key']=$goods_id;
					$arrGift['items'][$i]['nums']=$product['pkgnum']*$pkg_nums[$k];
                    $arrGift['items'][$i]['limit_nums']=$pkg_limit_nums[$k];
                    $arrGift['items'][$i]['type']='pkg';
					$i++;
				}
			}
		}
		if(!empty($products)){
			foreach($products as $k=>$product_id){
				$arrGift['items'][$i]['product_id']=$product_id;
                $arrGift['items'][$i]['primary_key']=$product_id;
				$arrGift['items'][$i]['nums']=$nums[$k];
                $arrGift['items'][$i]['limit_nums']=$limit_nums[$k];
                $arrGift['items'][$i]['type']='gift';
				$i++;
			}
		}
       
        foreach($arrGift['items'] as $gift) {
            $this->pullNums($arrGift['rule_id'], $gift);
        }
        echo "<pre>";print_r($arrGift);exit;
		//echo "<pre>";print_r($promotion);print_r($arrGift);print_r($products);exit();
		//判断是否限量
		if(!empty($limit_nums)&&$limit_nums>=0){
			if(!$this->validateNums($promotion['rule_id'],$limit_nums))return false;
		}
		
		$this->doAction($arrGift);
	}
    
    function pullNums($rule_id, $data) 
    {
        $objItems = kernel::single("promotion_mdl_order_items");

        if(preg_match("/^(0{1}|[1-9]{1,})$/", $data['limit_nums'])) {echo 1111;
            $limit_nums = $data['limit_nums'];
            $arrItems = $objItems->getList("*", array('rule_id'=>$rule_id, 'type'=>$data['type'], 'primary_key'=>$data['primary_key']));
            $arrItems = $arrItems[0];
            if(!empty($arrItems)) { 
                
            }else{
                
            }
        }
        
        
            
       
        
        echo "<pre>";print_r($data);print_r($arrItems);exit;
    }
	
	function validateNums($rule_id,$limit_nums){
		$objOrders=app::get('ome')->model('orders');
		$sql="SELECT COUNT(DISTINCT order_id) AS nums FROM  sdb_ome_order_items WHERE item_type='gift' AND rule_id='$rule_id' ";
		$arrNums=$objOrders->db->select($sql);
		$nums=$arrNums['0']['nums'];
		 
		if($nums>=$limit_nums){
			return false;
		}else{
			return true;
		}
	}
	
	function doAction($arrGift){//
		$objOrders=app::get('ome')->model('orders');
		$order_id=$arrGift['order_id'];
		$rule_id=$arrGift['rule_id'];
		foreach($arrGift['items'] as $product){
			$quantity=$product['nums'];
			$product_id=$product['product_id'];
			$arrProduct=array();
					
			$arrProduct=$objOrders->db->select("SELECT goods_id,name,bn FROM sdb_ome_products WHERE product_id='$product_id'");
					
			$name=$arrProduct[0]['name'];
			$goods_id=$arrProduct[0]['goods_id'];
			$bn=$arrProduct[0]['bn'];
					
			$o_sql="INSERT INTO sdb_ome_order_objects(order_id,obj_type,obj_alias,goods_id,bn,name,price,amount,quantity,pmt_price,sale_price) VALUES($order_id,'gift','赠品区块','$goods_id','$bn','$name',0,0,'$quantity',0,0)";
			if($objOrders->db->exec($o_sql)){
				$obj_id=$objOrders->db->lastinsertid();
				$i_sql="INSERT INTO sdb_ome_order_items(order_id,obj_id,product_id,bn,name,nums,item_type,rule_id) VALUES($order_id,'$obj_id','$product_id','$bn','$name','$quantity','gift','$rule_id')";
				if(!$objOrders->db->exec($i_sql)){
					error_log("item失败:".$order_id.":",3,DATA_DIR.'/iup/'.date("Ymd").'zjrorder.txt');
				}
			}else{
				error_log("object失败:".$order_id.":",3,DATA_DIR.'/iup/'.date("Ymd").'zjrorder.txt');	
			}
		}
	}
}
