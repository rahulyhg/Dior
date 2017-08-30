<?php
class taoguaninventory_inventory{
 
	function save_inventory($data,&$msg){
		
		
		//$pos_id     = $data['pos_id'];
        $number     = $data['number'];
        $product_id = $data['product_id'];
        $branch_id  = $data['branch_id'];
        
        
        //$bppObj = &app::get('ome')->model('branch_product_pos');
        ///$bpObj  = &app::get('ome')->model('branch_product');
        //$bpsObj = &app::get('ome')->model('branch_pos');
        $bObj   = &app::get('ome')->model('branch');
        $oBranchProduct   = &app::get('ome')->model('branch_product');
        $pObj   = &app::get('ome')->model('products');
        $invObj = &app::get('taoguaninventory')->model('inventory');
        
        $invitemObj = &app::get('taoguaninventory')->model('inventory_items');
        
        $op_name = kernel::single('desktop_user')->get_name();
        $op_id   = kernel::single('desktop_user')->get_id();
        $op_id = $op_id ? $op_id : -1;
        $branch  = $bObj->dump($branch_id,'name');
        $confirm_status = isset($data['confirm_status']) && $data['confirm_status'] == 2 ? 2 : 1;
        
		/*if($data['in_online_inv']){//更新关联货位
			if(!is_array($data['asso_pos'])){
				$data['asso_pos'] = array();
			}
			$product_asso_pos = $invObj->getProductPos($product_id);
			$del_asso_pos = array();
			foreach($product_asso_pos as $pos){
				if(!in_array($pos['pos_id'], $data['asso_pos'])){
					$del_asso_pos[] = $pos['pos_id'];
				}
			}
			if(count($del_asso_pos)>0){
				$invObj->delProductPos($product_id,$del_asso_pos);
			}
		}*/
        
        
        $db = kernel::database();
        $aDate = explode('-',date('Y-m-d'));
        $sql = 'SELECT inventory_id,difference 
        		FROM sdb_taoguaninventory_inventory 
        		WHERE inventory_date >= '.mktime(0,0,0,$aDate[1],$aDate[2],$aDate[0]).' 
        		AND inventory_date <= '.mktime(23,59,59,$aDate[1],$aDate[2],$aDate[0]).' 
        		AND op_id='.$op_id.' 
        		AND  branch_id='.$branch_id.' 
        		AND inventory_type=3 
        		AND confirm_status=1  
        		ORDER BY inventory_date';
       
        $inventory = $db->selectRow($sql);
        if($inventory){
        	 $inv_id = $inventory['inventory_id'];
        }else{
        	  $inv['inventory_name']      = date("Ymd")."在线盘点表";
              $inv['inventory_bn']        = $invObj->gen_id();
                    $inv['inventory_date']      = time();
                    $inv['inventory_checker']   = $op_name;
                    $inv['second_checker']      = $op_name;
                    $inv['finance_dept']        = $op_name;
                    $inv['warehousing_dept']    = $op_name;
                    $inv['op_name']             = $op_name;
                    $inv['op_id']               = $op_id;
                    $inv['branch_id']           = $branch_id;
                    $inv['branch_name']         = $branch['name'];
                    $inv['inventory_type']      = '3';//在线盘点
                    $inv['confirm_status'] = $confirm_status;
                    
                    $invObj->save($inv);
                    $inv_id = $inv['inventory_id'];
                    $total = 0;
        }
        
        if($inv_id){
	        $p = $pObj->dump($product_id);
	        
	    	if(app::get('purchase')->is_installed()){
	            $poObj  = &app::get('purchase')->model('po');
	        	$price = $poObj->getPurchsePrice($product_id,'DESC');;
	        }else{
	            $price = 0;
	        }
	        
	        //$bp = $oBranchProduct->getList('store',array('product_id'=>$product_id,'branch_id'=>$branch_id),0,-1);
            $branch_store = $oBranchProduct->getStoreByBranch($product_id,$branch_id);
	        if($branch_store){
            	$accounts_num  = $branch_store;
            }else{
            	$accounts_num = 0;
            }
             //记录损益表
            $inv_item = array();
            $db = kernel::database();
            $row = $db->selectRow('SELECT item_id,price,accounts_num
            				FROM sdb_taoguaninventory_inventory_items WHERE inventory_id ='.$inv_id.'
            				AND product_id ='.$product_id);
            if($row['item_id']){
            	$inv_item['item_id'] = $row['item_id'];
            }
            
            $inv_item['inventory_id'] = $inv_id;
            $inv_item['product_id'] = $product_id;
            $inv_item['pos_id'] = 0;
            $inv_item['name'] = $p['name'];
            $inv_item['bn'] = $p['bn'];
            $inv_item['spec_info'] = $p['spec_info'];
            $inv_item['unit'] = $p['unit'];
            $inv_item['pos_name'] = '';
            $inv_item['accounts_num'] = $accounts_num;
            $inv_item['actual_num'] = $number;//实际数量
            $inv_item['shortage_over'] = $number-$branch_store;
            $inv_item['price'] = $price;
            $inv_item['availability'] = 'true';
            $inv_item['memo'] = '在线盘点，新增商品数量';
            
            //出入库类型
            /*if($inv_item['shortage_over'] > 0){
            	eval('$type='.get_class($iostock_instance).'::OVERAGE;');
            }else if($inv_item['shortage_over'] < 0){
            	eval('$type='.get_class($iostock_instance).'::INVENTORY;');
            }else{
            	$type = 0;
            }*/
            
            $invitemObj->save($inv_item);//记录导入明细
            //重新计算差异金额
            $invitems = $invitemObj->getList("*",array('inventory_id'=>$inv_id));
            $total = 0;
            foreach ($invitems as $invitem) {
                $total +=  $invitem['shortage_over']*$invitem['price'];
            }
            //更新盘点单
            $inv['inventory_id'] = $inv_id;
	        $inv['difference'] = $total;
	        $inv['import_status'] = '2';
	        $inv['update_status'] = '2';
	        $invObj->save($inv);
	        
	        if($confirm_status == 2 && $inv_item['shortage_over'] != 0){
	        	return $this->do_iostock($inv_id,$msg);
	        }else{
	        	return true;
	        }
        }
        
	}
	
 	/**
 	 * 
 	 * 生成盘点单出入库明细
 	 * @param unknown_type $inventory_id
 	 * @param unknown_type $type
 	 * @param unknown_type $msg
 	 */
    function do_iostock($inventory_id,&$msg){
    	$allow_commit = false;
		$result = 'error';
        kernel::database()->exec('begin');
        $iostock_instance = kernel::service('ome.iostock');
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            $iostock_data = $this->get_iostock_data($inventory_id);
            $inventory = array();//盘亏
            $overage = array();//盘盈
            //$oBranchProduct = &app::get('ome')->model('branch_product');
            foreach($iostock_data as $item_id=>$iostock){
            	if($iostock['nums'] > 0){
            		$overage[$item_id] = $iostock;
            	}else if($iostock['nums'] < 0){
            		$iostock['nums'] = abs($iostock['nums']);
            		$inventory[$item_id] = $iostock;
            	}
            }
            if(count($overage) > 0){
            	eval('$type='.get_class($iostock_instance).'::OVERAGE;');
            	$iostock_bn = $iostock_instance->get_iostock_bn($type);
            	$io = $iostock_instance->getIoByType($type);
	            if ( $iostock_instance->set($iostock_bn, $overage, $type, $overage_msg, $io) ){
	                $allow_commit = true;
				    $result = 'success';
	            }else{
				    $result = $overage_msg[0];
				}				
            }
            if(count($inventory) > 0){
            	$allow_commit = false;
				$result = 'error';
            	eval('$type='.get_class($iostock_instance).'::INVENTORY;');
            	$iostock_bn = $iostock_instance->get_iostock_bn($type);
            	$io = $iostock_instance->getIoByType($type);
	            if ( $iostock_instance->set($iostock_bn, $inventory, $type, $inventory_msg, $io) ){
	               $allow_commit = true;
				   $result = 'success';
	            }
            }
            
            if(count($overage) == 0 && count($inventory) == 0){
            	$allow_commit = true;
				$result = 'success';
            }
            
        }
        if ($allow_commit == true){
            kernel::database()->commit();
            return $result;
        }else{
            kernel::database()->rollBack();
            $msg['overage'] = $overage_msg;
            $msg['inventory'] = $inventory_msg;
            
            return $result;
        }
    }
	
	/**
     * 组织出库数据
     * @access public
     * @param String $iso_id 出入库ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($inventory_id){
        
        $invitemObj = &app::get('taoguaninventory')->model('inventory_items');
		$oBranchProduct = &app::get('ome')->model('branch_product');
        
        $iostock_data = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguaninventory_inventory` WHERE `inventory_id`=\''.$inventory_id.'\'';
        $inventory_detail = $db->selectrow($sql);
        $inv_items_detail = $invitemObj->getList('*', array('inventory_id'=>$inventory_id), 0, -1);
        if ($inv_items_detail){
            foreach ($inv_items_detail as $k=>$v){
                $iostock_data[$v['item_id']] = array(
                    'branch_id' => $inventory_detail['branch_id'],
                    'original_bn' => $inventory_detail['inventory_bn'],
                    'original_id' => $inventory_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $v['shortage_over'],
                    //'cost_tax' => 0,
                    'oper' => $inventory_detail['inventory_checker'],
                    'create_time' => $inventory_detail['inventory_date'],
                    'operator' => $inventory_detail['op_name'],
                    //'settle_method' => $inventory_detail['settle_method'],
                    //'settle_status' => $inventory_detail['settle_status'],
                    //'settle_operator' => $inventory_detail['settle_operator'],
                    //'settle_time' => $inventory_detail['settle_time'],
                    //'settle_num' => $inventory_detail['settle_num'],
                    //'settlement_bn' => $inventory_detail['settlement_bn'],
                    //'settlement_money' => $inventory_detail['settlement_money'],
                    'memo' => $inventory_detail['memo'],
                );
				
				// 检测货品和仓库的关联是否存在
				$branch_product = $oBranchProduct -> getList('*', array('branch_id'=>$inventory_detail['branch_id'],'product_id'=>$v['product_id']), 0, 1);
				if(!$branch_product) {
					$branch_product = array('branch_id'=>$inventory_detail['branch_id'],'product_id'=>$v['product_id'],'store'=>0,'store_freeze'=>0,'last_modified'=>time(),'arrive_store'=>0,'safe_store'=>0);
					$oBranchProduct -> save($branch_product);
				}
            }
        }
        return $iostock_data;
    }
    
   function check_inventory($data,&$msg){
    	$inventory_id = $data['inventory_id'];
		$result = $this->do_iostock($inventory_id,$msg);
    	
    	if($result == 'success'){
    		$oInventory = &app::get('taoguaninventory')->model('inventory');
    		$op_id   = kernel::single('desktop_user')->get_id();
    		$data = array('inventory_id'=>$inventory_id,
    					'confirm_status'=>2,
			    		'confirm_time'=>time(),
			    		'confirm_op'=>$op_id
    		);
    		return $oInventory->save($data);
    	}else{
    		return $result;
    	}
    }
   
    function inventory_create($data,&$msg){

        $mdl_branch_pos = &app::get('ome')->model('branch_pos');
        $mdl_branch_product_pos = &app::get('ome')->model('branch_product_pos');
        $mdl_branch_product  = &app::get('ome')->model('branch_product');
        $mdl_inventory_object = &app::get('taoguaninventory')->model('inventory_object');
        $bObj   = &app::get('ome')->model('branch');
        $mdl_products = &app::get('ome')->model('products');
        $pos_name = $data['pos_name'];
        $barcode = $data['barcode'];
        $branch_id = $data['branch_id'];
        $number     = $data['number'];
        $inventory_id = $data['inventory_id'];

        $products = $mdl_products->dump(array('bracode'=>$barcode),'product_id,name,bn,spec_info,unit');
        $product_id = $products['product_id'];
        if($pos_name){//如果有货号
            $branch_pos = $mdl_branch_pos->getlist('*',array('branch_id'=>$branch_id,'store_position'=>$pos_name),0,1);
            if(!$branch_pos){
                $branch_pos_data = array();
                $branch_pos_data['branch_id'] = $branch_id;
                $branch_pos_data['store_position'] = $pos_name;
                $branch_pos_data['create_time'] = time();
                $result = $mdl_branch_pos->save($branch_pos_data);
                $pos_id = $branch_pos_data['pos_id'];
            }else{
                $pos_id = $branch_pos[0]['pos_id'];
            }
            $branch_product_pos = $mdl_branch_product_pos->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id,'pos_id'=>$pos_id),'*');
            if(!$branch_product_pos){
                $branch_product_pos_data = array();
                $branch_product_pos_data['branch_id'] = $branch_id;
                $branch_product_pos_data['product_id'] = $product_id;
                $branch_product_pos_data['pos_id']   = $pos_id;
                $branch_product_pos_data['create_time']   = time();
                $mdl_branch_product_pos->save($branch_product_pos_data);
            }
        }
        $branch_product = $mdl_branch_product->getlist('*',array('branch_id'=>$branch_id,'product_id'=>$product_id),0,1);
        if(!$branch_product){
            $branch_product_data = array();
            $branch_product_data['branch_id'] = $branch_id;
            $branch_product_data['product_id'] = $product_id;
            $mdl_branch_product->save( $branch_product_data );
        }
        $invObj = &app::get('taoguaninventory')->model('inventory');

        $invitemObj = &app::get('taoguaninventory')->model('inventory_items');

        $op_name = kernel::single('desktop_user')->get_name();
        $op_id   = kernel::single('desktop_user')->get_id();
        $op_id = $op_id ? $op_id : -1;
        $branch  = $bObj->dump($branch_id,'name');
        $confirm_status = 2;
        $db = kernel::database();
        $aDate = explode('-',date('Y-m-d'));
        $sqlstr = '';
        if($pos_id){
            $sqlstr.=' AND io.pos_id='.$pos_id;
        }

        $sql = 'SELECT inv.inventory_id,inv.difference,inv.op_id,inv.inventory_name,io.obj_id  FROM sdb_taoguaninventory_inventory as inv left join sdb_taoguaninventory_inventory_object as io on inv.inventory_id=io.inventory_id
        		WHERE inv.branch_id='.$branch_id.'
        		AND inv.inventory_type=3
        		AND inv.confirm_status=1 AND io.product_id='.$product_id.$sqlstr.'
        		ORDER BY inv.inventory_id';
//echo $sql.'<br>';
        $inventory = $db->selectRow($sql);
        if(app::get('purchase')->is_installed()){
	               $poObj  = &app::get('purchase')->model('po');
	        	      $price = $poObj->getPurchsePrice($product_id,'DESC');;
	       }else{
	               $price = 0;
	       }
        $branch_store = $mdl_branch_product->getStoreByBranch($product_id,$branch_id);
        if($branch_store){
            $accounts_num  = $branch_store;
        }else{
            $accounts_num = 0;
        }
        if($inventory){
            //是否有同样的商品+仓库+货位
            if($inventory['op_id'] == $op_id){
                //更新明细信息
                $inv_object = array();
                $inv_object['oper_id'] = $op_id;
                $inv_object['oper_name'] = $op_name;
                $inv_object['oper_time'] = time();
                $inv_object['inventory_id'] = $inventory['inventory_id'];
                $inv_object['obj_id'] = $inventory['obj_id'];
                $inv_object['product_id'] = $product_id;
                $inv_object['pos_id'] = $pos_id;
                $inv_object['bn'] = $products['bn'];
                $inv_object['barcode'] = $data['barcode'];
                $inv_object['pos_name'] = $pos_name;
                $inv_object['actual_num'] = $number;
                $inv_item = array();
                $inv_id = $inventory['inventory_id'];
                $inv_item['inventory_id'] = $inventory['inventory_id'];
                $inv_item['accounts_num'] = $accounts_num;//帐面数量
                $inv_item['product_id'] = $product_id;
                $inv_item['price'] = $price;
                if($mdl_inventory_object ->save($inv_object)){
                    $inv_actual_total=$this->get_inventory_bybn($inventory['inventory_id'],$product_id);
                    $actual_num = $inv_actual_total;
                    $inv_item['actual_num'] = $actual_num;//实际数量
                    $inv_item['shortage_over'] = $actual_num-$branch_store;
                    $result = $this->update_inventory($inv_item);
                    return $result;
                }
            }else{
                $message='此商品已存在于盘点列表中,请确认!';
                return false;
            }
        }else{

            //插入数据
            $inv_object = array();
            $inv_object['oper_id'] = $op_id;
            $inv_object['oper_name'] = $op_name;
            $inv_object['oper_time'] = time();
            $inv_object['inventory_id'] = $inventory_id;
            $inv_object['product_id'] = $product_id;
            $inv_object['pos_id'] = $pos_id;
            $inv_object['bn'] = $products['bn'];
            $inv_object['barcode'] = $data['barcode'];
            $inv_object['pos_name'] = $pos_name;
            $inv_object['actual_num'] = $number;
            $total = 0;
            $inv_item_array = $invitemObj->dump(array('inventory_id'=>$inventory_id,'product_id'=>$product_id),'item_id');
            if($inv_item_array){
                $db = kernel::database();
                $inv_actual_total=$this->get_inventory_bybn($inventory_id,$product_id);
                $inv_item['inventory_id'] = $inventory_id;

                $inv_item['product_id'] = $product_id;
                $actual_num = $inv_actual_total+$number;

                $inv_item['actual_num'] = $actual_num;//实际数量
                $inv_item['shortage_over'] = $actual_num-$branch_store;
                $inv_item['price'] = $price;
                $total += $inv_item['shortage_over']*$price;

                $item_result=$this->update_inventory($inv_item);
                if($item_result){
                    $inv_object['item_id'] = $inv_item_array['item_id'];

                }
            }else{

            $inv_item = array();
            $inv_item['inventory_id'] = $inventory_id;

            $inv_item['product_id'] = $product_id;

            $inv_item['name'] = $products['name'];
            $inv_item['bn'] = $products['bn'];
            $inv_item['spec_info'] = $products['spec_info'];
            $inv_item['unit'] = $products['unit'];

            $inv_item['accounts_num'] = $accounts_num;//帐面数量

            $inv_item['actual_num'] = $number;//实际数量
            $inv_item['shortage_over'] = $number-$branch_store;
            $inv_item['price'] = $price;
            $inv_item['availability'] = 'true';
            $inv_item['memo'] = '在线盘点，新增商品数量';
            $total += $inv_item['shortage_over']*$price;
            $item_result = $invitemObj->save($inv_item);//记录导入明细
            if($item_result){
                $inv_object['item_id'] = $inv_item['item_id'];
            }

           }
            $obj_result=$mdl_inventory_object->save($inv_object);

            //更新盘点单
                if($item_result){
                    $inv['inventory_id'] = $inventory_id;
                    $inv['difference'] = $total;
                    $inv['import_status'] = '2';
                    $inv['update_status'] = '2';
                    $result = $invObj->save($inv);
                    return $result;
                }
        }
    }

    function save_inventorydata($data){
        $mdl_inventory = &app::get('taoguaninventory')->model('inventory');
        $op_name = kernel::single('desktop_user')->get_name();
        $op_id   = kernel::single('desktop_user')->get_id();
        $mdl_encoded_state = &app::get('taoguaninventory')->model('encoded_state');
        $get_state = $mdl_encoded_state->get_state();
        $op_id = $op_id ? $op_id : -1;
        $inv['inventory_name']      = $data['inventory_name'];
        $inv['inventory_bn']        = $get_state['inventory_bn'];
        $inv['inventory_date']      = time();
        $inv['add_time'] = strtotime($data['add_time']);
        $inv['inventory_checker']   = $op_name;
        $inv['second_checker']      = $op_name;
        $inv['finance_dept']        = $op_name;
        $inv['warehousing_dept']    = $op_name;
        $inv['op_name']             = $op_name;
        $inv['op_id']               = $op_id;
        $inv['branch_id']           = $data['branch_id'];
        $inv['branch_name']         = $data['branch_name'];
        $inv['inventory_type']      = '3';
        $inv['pos'] = $data['pos'];
        $inv['memo'] = $data['memo'];
        $inv['inventory_type'] = $data['inventory_type'];
       $result = $mdl_inventory->save($inv);
        if($result){
            $encoded_state_data = array();
            $encoded_state_data['currentno'] = $get_state['currentno'];
            $encoded_state_data['eid'] = $get_state['eid'];
            $mdl_encoded_state->save($encoded_state_data);
        }
       return $inv['inventory_id'];

   }

   function get_inventory_bybn($inventory_id,$product_id){

        $mdl_inventory_object = &app::get('taoguaninventory')->model('inventory_object');
        $db = kernel::database();
        $sql = 'SELECT sum(actual_num) as actual_num FROM sdb_taoguaninventory_inventory_object WHERE 	inventory_id='.$inventory_id.' AND product_id='.$product_id;
        $inventory_obj = $db->selectrow($sql);
        return $inventory_obj['actual_num'];


   }

   function update_inventory($data){
       $db = kernel::database();
       $sqlstr = '';
       if($data['price']!=''){
           $sqlstr.=',price='.$data['price'];
       }
       $sql = 'UPDATE sdb_taoguaninventory_inventory_items SET actual_num='.$data['actual_num'].',shortage_over='.$data['shortage_over'].$sqlstr.'  WHERE inventory_id='.$data['inventory_id'].' AND product_id='.$data['product_id'];
       $result = $db->exec($sql);
       return $result;
   }


}
