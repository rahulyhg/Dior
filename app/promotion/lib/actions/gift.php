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
		$rule_id = $promotion['rule_id'];
		
        $gift_bn = $promotion['actions_serialize']['gift']['bn'];
		$nums = $promotion['actions_serialize']['gift']['nums'];
        $limit_nums = $promotion['actions_serialize']['gift']['limit_nums'];
        $gift_all = $promotion['actions_serialize']['gift']['all'];
        
		$pkg_bn = $promotion['actions_serialize']['gift']['pkg_bn'];
		$pkg_nums = $promotion['actions_serialize']['gift']['pkg_nums'];
		$pkg_limit_nums = $promotion['actions_serialize']['gift']['pkg_limit_nums'];
        $pkg_all = $promotion['actions_serialize']['gift']['pkg_all'];
        
		$i=0;
		if(!empty($pkg_bn)){
			foreach($pkg_bn as $k=>$goods_id){
                $arrGift['pkg'][$i]['goods_id'] = $goods_id;
                $arrGift['pkg'][$i]['primary_key'] = $goods_id;
                $arrGift['pkg'][$i]['nums'] = $pkg_nums[$k];
                $arrGift['pkg'][$i]['limit_nums'] = $pkg_limit_nums[$k];
                $i++;
			}
		}
		if(!empty($gift_bn)){
			foreach($gift_bn as $k=>$product_id){
				$arrGift['gift'][$i]['product_id'] = $product_id;
                $arrGift['gift'][$i]['primary_key'] = $product_id;
				$arrGift['gift'][$i]['nums'] = $nums[$k];
                $arrGift['gift'][$i]['limit_nums'] = $limit_nums[$k];
				$i++;
			}
		}

        foreach($arrGift as $type=>$items) {
            foreach($items as $k=>$item) {
                if(!$this->pullNums($rule_id, $item, $type)) {
                    unset($arrGift[$type][$k]);
                    continue;
                }
                $rand[$type][] = $k;
                $arrGift[$type][$k]['event'] = $item['event'];
                $arrGift[$type][$k]['type'] = $item['type'];
                $arrGift[$type][$k]['original_nums'] = $item['original_nums'];
            }
        }
       
        if(!empty($arrGift['gift'])) {
            if($gift_all != '1') {
                $gift[] = $arrGift['gift'][$rand['gift'][rand(0, count($rand['gift'])-1)]];
            }else{
                $gift = $arrGift['gift'];
            }
        }
       
        if(!empty($arrGift['pkg'])) {
            if($pkg_all != '1') {
                $pkg[] = $arrGift['pkg'][$rand['pkg'][rand(0, count($rand['pkg'])-1)]];
            }else{
                $pkg = $arrGift['pkg'];
            }
        }
        
        foreach(array_merge((array)$gift, (array)$pkg) as $events) {
            $this->updateNums($rule_id, $events);
        }
         
        if(!empty($pkg)) {
            $product = $arrPkgPorudct =array();
            $ojbPkgPorduct = app::get('omepkg')->model('pkg_product');
            foreach($pkg as $k=>$products) {
                $arrPkgPorudct = $ojbPkgPorduct->getList("product_id,pkgnum", array('goods_id'=>$products['goods_id']));
				foreach($arrPkgPorudct as $p){
					$product[] = array(
                        'product_id' => $p['product_id'],
                        'nums' => $p['pkgnum'] * $products['nums'],
                    );
				}
            }
        }
        
        $gifts['items'] = array_merge((array)$product, (array)$gift);
       //echo "<pre>";print_r($gifts);exit;
        if(!empty($gifts['items'])) {
            $gifts['order_id'] = $order['order_id'];
            $this->doAction($gifts);
        }
		
	}
    
    function pullNums($rule_id, &$data, $type = 'gift') 
    {
        $objItems = kernel::single("promotion_mdl_order_items");
        $limit_nums = $data['limit_nums'];
        
        if(preg_match("/^(0{1}|[1-9]{1,})$/", $limit_nums)) {
            $primary_key = $data['primary_key'];
            $nums = $data['nums'];
            
            $arrItems = $objItems->getList("*", array('rule_id'=>$rule_id, 'type'=>$type, 'primary_key'=>$primary_key));
            $arrItems = $arrItems[0];
            if(!empty($arrItems)) { 
                if($arrItems['nums'] + $nums > $limit_nums) {
                    return false;
                }
                $data['event'] = 'update';
                $data['original_nums'] = $arrItems['nums'];
                $data['type'] = $type;
                return true;
            }else{//need save
                if($nums > $limit_nums) {
                    return false;
                }
                $data['event'] = 'save';
                $data['type'] = $type;
                $data['original_nums'] = 0;
                return true;
            }
        }
        
        $data['event'] = 'never';
        return true;
    }
    
    function updateNums($rule_id, $data)
    { 
        if(empty($data))return true;
        
        $objItems = kernel::single("promotion_mdl_order_items");
        $event = $data['event'];
        $type = $data['type'];
        $primary_key = $data['primary_key'];
        $original_nums = $data['original_nums'];
        $nums = $data['nums'];
        
        switch($event) {
            case 'update':
                $objItems->update(array('nums'=>$original_nums + $nums), array('rule_id'=>$rule_id, 'type'=>$type, 'primary_key'=>$primary_key));
                break;
            case 'save':
                $save['rule_id'] = $rule_id;
                $save['type'] = $type;
                $save['primary_key'] = $primary_key;
                $save['nums'] = $nums;
                $objItems->save($save);
                break;
        }
    }
	
	function doAction($arrGift){//
		$objOrders=app::get('ome')->model('orders');
		$order_id=$arrGift['order_id'];
        
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
				$i_sql="INSERT INTO sdb_ome_order_items(order_id,obj_id,product_id,bn,name,nums,item_type) VALUES($order_id,'$obj_id','$product_id','$bn','$name','$quantity','gift')";
				if(!$objOrders->db->exec($i_sql)){
					error_log("item失败:".$order_id.":",3,DATA_DIR.'/iup/'.date("Ymd").'zjrorder.txt');
				}
			}else{
				error_log("object失败:".$order_id.":",3,DATA_DIR.'/iup/'.date("Ymd").'zjrorder.txt');	
			}
		}
	}
}
