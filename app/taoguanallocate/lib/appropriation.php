<?php
class taoguanallocate_appropriation{
 	
 	function to_savestore($adata,$appropriation_type,$memo,$op_name,$msg){
        $oAppropriation = &app::get('taoguanallocate')->model("appropriation");
        $oAppropriation_items = &app::get('taoguanallocate')->model("appropriation_items");
        $oProducts = &app::get('ome')->model("products");
        $oBranch = &app::get('ome')->model("branch_product");
        $op_name = $op_name=='' ? '未知' : $op_name;
        $appro_data = array(
            //'appropriation_id'=>$appropriation['appropriation_id'],
            'type'=>$appropriation['type'],
            'create_time'=>time(),
            'operator_name'=>$op_name,
            'memo'=>$memo
        );
        #生成调拨单号
        $appro_data['appropriation_no'] =  $this->gen_appropriation_no();
        $oAppropriation->save($appro_data);
  
        $appropriation_id = $appro_data['appropriation_id'];
        
        foreach($adata as $k=>$v){
            $product = $oProducts->dump($v['product_id'],'bn,name');
            $from_branch_id=$v['from_branch_id'];
            $to_branch_id=$v['to_branch_id'];
            //$from_pos_id=$v['from_pos_id'];
            //$to_pos_id=$v['to_pos_id'];
          
            $add_store_data =array(
                 'pos_id'=>$to_pos_id,'product_id'=>$v['product_id'],'num'=>$v['num'],'branch_id'=>$to_branch_id
            );
        
           
            $lower_store_data= array(
               'pos_id'=>$from_pos_id,'product_id'=>$v['product_id'],'num'=>$v['num'],'branch_id'=>$from_branch_id);
            $items_data = array(
                'appropriation_id'=>$appropriation_id,
                'bn'=>$product['bn'],
                'product_name'=>$product['name'],
                'product_id'=>$v['product_id'],
                'from_branch_id'=>$from_branch_id==''? 0:$from_branch_id,
                'from_pos_id'=>$from_pos_id=='' ? 0:$from_pos_id,
                'to_branch_id'=>$to_branch_id=='' ? 0:$to_branch_id,
                'to_pos_id'=>$to_pos_id=='' ? 0:$to_pos_id,
                'num'=>$v['num']
                );
            $oAppropriation_items->save($items_data);
              /*当货位号不相同时。是不同仓库不同货位上进行调拔。*/
            
            /*//出入库及销售单记录
	        $iostock_sales_set_result = true;
	        $iostock_sales_data = array();
	        $iostock_data = kernel::single('taoguanallocate_iostocksales')->get_iostock_data($appro_data['appropriation_id']);
	        $sales_data = kernel::single('taoguanallocate_iostocksales')->get_sales_data($appro_data['appropriation_id']);
	        $iostock_sales_data['iostock'] = $iostock_data;
	        $iostock_sales_data['sales'] = $sales_data;
	        if ( $iostock_sales_service = kernel::service('ome.service.iostock_sales') ){
	            if ( method_exists($iostock_sales_service, 'set') ){
	                $io = '0';//出入库类型：0出库1入库
	                $iostock_sales_set_result = $iostock_sales_service->set($iostock_sales_data, $io, $msg);
	            }
	        }
	        if ( $iostock_sales_set_result ){
	        	$oBranch->operate_branch_store($add_store_data,'add');
	        	$oBranch->operate_branch_store($add_store_data,'lower');
	        }*/
            
        }
     
     	if($appropriation_type == 1){//直接调拨
            return $this->do_iostock($appropriation_id, $msg);
        }else if($appropriation_type == 2){//出入库调拨，先生成出库单，出库单确认后生成入库单,出入库单确认后，生成出入库明细
            return $this->do_out_iostockorder($appropriation_id, $msg);
        }else{
        	return true;
        }
		
  }
  
  /**
   * 
   * Enter description here ...
   * @param unknown_type $appropriation_id
   * @param unknown_type $msg
   */
   function do_out_iostockorder($appropriation_id,&$msg){
       #判断是否开启固定成本，如果开启，price等于商品成本价
       $cost = false;
       if(app::get('tgstockcost')->is_installed()){
           $tgstockcost = app::get("ome")->getConf("tgstockcost.cost");
           if($tgstockcost == 2){
               $cost= true;
           }
       }
		$iostock_instance = kernel::service('ome.iostock');   
        $appitemObj = &app::get('taoguanallocate')->model('appropriation_items');
        $objProducts = &app::get('ome')->model('products');
        $products = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguanallocate_appropriation` WHERE `appropriation_id`=\''.$appropriation_id.'\'';
        $app_detail = $db->selectrow($sql);
        $app_items_detail = $appitemObj->getList('*', array('appropriation_id'=>$appropriation_id), 0, -1);
        $branch_id = 0;
        if ($app_items_detail){
            foreach ($app_items_detail as $k=>$v){
            	if(!$branch_id){
            		$branch_id = $v['from_branch_id'];
            	}
            	if($cost){
            	    #如果已经开启固定成本，则获取商品的成本价
            	    $product=$db->selectRow('select cost,unit from sdb_ome_products where product_id='.$v['product_id']);
            	}else{
            	    #如果没有开启，则不需要获取成本价
            	    $product = $objProducts->dump(array('product_id'=>$v['product_id']),'unit');
            	    #调拨出库时，获取对应的单位成本
            	    $unit_cost = $db->selectRow('select unit_cost from  sdb_ome_branch_product where branch_id='.$branch_id.' and product_id='.$v['product_id']);
            	    $product['cost'] = $unit_cost['unit_cost'];
            	}
                $products[$v['product_id']] = array(
	                'unit'=>$product['unit'],
					'name'=>$v['product_name'],
					'bn'=>$v['bn'],
					'nums'=>$v['num'],
                	'price'=>$product['cost']?$product['cost']:0
                );
            }
        }
        
        
        eval('$type='.get_class($iostock_instance).'::ALLOC_LIBRARY;');
	  	$data =array (
		 'iostockorder_name' => date('Ymd').'出库单', 
		 'supplier' => '', 
		 'supplier_id' => 0, 
		 'branch' => $branch_id, 
		 'type_id' => $type, 
		 'iso_price' => 0,
		 'memo' => $app_detail['memo'], 
		 'operator' => kernel::single('desktop_user')->get_name(), 
	  	 'original_bn'=>'',
	   	 'original_id'=>$appropriation_id,
		 'products'=>$products
		 );
		$iostockorder_instance = kernel::service('taoguaniostockorder.iostockorder');
        if ( method_exists($iostockorder_instance, 'save_iostockorder') ){
        	return $iostockorder_instance->save_iostockorder($data,$msg);
        }else{
        	$msg = '没有安装出入库单应用';
        	return false;
        }
   }
   
 	/**
   * 
   * Enter description here ...
   * @param unknown_type $appropriation_id
   * @param unknown_type $msg
   */
   function do_in_iostockorder($appropriation_id,&$msg){
       #判断是否开启固定成本法，如果开启，price等于商品成本价且不能修改
       $cost = false;
       if(app::get('tgstockcost')->is_installed()){
           $tgstockcost = app::get("ome")->getConf("tgstockcost.cost");
           if($tgstockcost == 2){
               $cost= true;
           }
       }
		$iostock_instance = kernel::service('ome.iostock');   
        $appitemObj = &app::get('taoguanallocate')->model('appropriation_items');
        $objProducts = &app::get('ome')->model('products');
        $products = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguanallocate_appropriation` WHERE `appropriation_id`=\''.$appropriation_id.'\'';
        $app_detail = $db->selectrow($sql);
        $app_items_detail = $appitemObj->getList('*', array('appropriation_id'=>$appropriation_id), 0, -1);
        $branch_id = 0;
        if ($app_items_detail){
            foreach ($app_items_detail as $k=>$v){
            	if(!$branch_id){
            		$branch_id = $v['to_branch_id'];
            	}
            	if($cost){
            	    #如果已经开启固定成本法，则获取商品的成本价
            	    $product=$db->selectRow('select cost,unit from sdb_ome_products where product_id='.$v['product_id']);
            	}else{
            	    #如果没有开启，则不需要获取成本价
            	    $product = $objProducts->dump(array('product_id'=>$v['product_id']),'unit');
            	    #调拨入库时，获取调拨出库单时的单位成本
            	    $sql= 'select 
            	                items.price from sdb_taoguaniostockorder_iso  as iso
            	           left join sdb_taoguaniostockorder_iso_items  as items
                           on iso.iso_id=items.iso_id
            	           where iso.original_id='.$appropriation_id.' AND items.product_id='.$v['product_id']. ' AND iso.type_id=40';
            	    $unit_cost = $db->selectRow($sql);
            	    $product['cost'] = $unit_cost['price'];
            	}
            	//$product = $objProducts->dump(array('product_id'=>$v['product_id']),'unit');
                $products[$v['product_id']] = array(
	                'unit'=>$product['unit'],
					'name'=>$v['product_name'],
					'bn'=>$v['bn'],
					'nums'=>$v['num'],
                    'price'=>$product['cost']?$product['cost']:0
                );
            }
        }
        
        
        eval('$type='.get_class($iostock_instance).'::ALLOC_STORAGE;');
	  	$data =array (
		 'iostockorder_name' => date('Ymd').'入库单', 
		 'supplier' => '', 
		 'supplier_id' => 0, 
		 'branch' => $branch_id, 
		 'type_id' => $type, 
		 'iso_price' => 0,
		 'memo' => $app_detail['memo'], 
		 'operator' => kernel::single('desktop_user')->get_name(),
	   	 'original_bn'=>'',
	   	 'original_id'=>$appropriation_id, 
		 'products'=>$products
		 );
		$iostockorder_instance = kernel::service('taoguaniostockorder.iostockorder');
        if ( method_exists($iostockorder_instance, 'save_iostockorder') ){
        	return $iostockorder_instance->save_iostockorder($data,$msg);
        }else{
        	$msg = '没有安装出入库单应用';
        	return false;
        }
   }
  
   /**
 	 * 
 	 * 生成调拨单出入库明细
 	 * @param unknown_type $appropriation_id
 	 * @param unknown_type $type
 	 * @param unknown_type $msg
 	 */
    function do_iostock($appropriation_id,&$msg){
    	$allow_commit = false;
        kernel::database()->exec('begin');
        $iostock_instance = kernel::service('ome.iostock');
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            $iostock_data = $this->get_iostock_data($appropriation_id);
            $out = array();//调出
            $in = array();//调入
            //$oBranchProduct   = &app::get('ome')->model('branch_product');
            foreach($iostock_data as $item_id=>$iostock){
            	$iostock['nums'] = abs($iostock['nums']);
            	
            	$iostock['branch_id'] = $iostock['from_branch_id'];
            	unset($iostock['from_branch_id']);
            	$out[$item_id] = $iostock;
            	
            	$iostock['branch_id'] = $iostock['to_branch_id'];
            	unset($iostock['to_branch_id']);
            	$in[$item_id] = $iostock;
            
            }
            if(count($out) > 0){
            	eval('$type='.get_class($iostock_instance).'::ALLOC_LIBRARY;');
            	$iostock_bn = $iostock_instance->get_iostock_bn($type);
            	$io = $iostock_instance->getIoByType($type);
	            if ( $iostock_instance->set($iostock_bn, $out, $type, $out_msg, $io) ){
	               $allow_commit = true;
	            }
            }
            if(count($in) > 0 && $allow_commit){
            	$allow_commit = false;
            	eval('$type='.get_class($iostock_instance).'::ALLOC_STORAGE;');
            	$iostock_bn = $iostock_instance->get_iostock_bn($type);
            	$io = $iostock_instance->getIoByType($type);
	            if ( $iostock_instance->set($iostock_bn, $in, $type, $in_msg, $io) ){
	               $allow_commit = true;
	            }
            }
            
        }
        if ($allow_commit == true){
            kernel::database()->commit();
            return true;
        }else{
            kernel::database()->rollBack();
            $msg['out_msg'] = $out_msg;
            $msg['in_msg'] = $in_msg;
            return false;
        }
        
    }
	
	/**
     * 组织出库数据
     * @access public
     * @param String $iso_id 出入库ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($appropriation_id){
        
        $appitemObj = &app::get('taoguanallocate')->model('appropriation_items');
        
        $iostock_data = array();
        $db = kernel::database();
        $sql = 'SELECT * FROM `sdb_taoguanallocate_appropriation` WHERE `appropriation_id`=\''.$appropriation_id.'\'';
        $app_detail = $db->selectrow($sql);
        $app_items_detail = $appitemObj->getList('*', array('appropriation_id'=>$appropriation_id), 0, -1);
        if ($app_items_detail){
            foreach ($app_items_detail as $k=>$v){

                $bp_data = $db->selectrow('select unit_cost from sdb_ome_branch_product where branch_id = '.$v['from_branch_id'].' and product_id = '.$v['product_id']);
                
                $iostock_data[$v['item_id']] = array(
                    'from_branch_id' => $v['from_branch_id'],
               		'to_branch_id' => $v['to_branch_id'],
                    'original_bn' => '',
                    'original_id' => $appropriation_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => $bp_data['unit_cost']?$bp_data['unit_cost']:0,
                    'nums' => $v['num'],
                    //'cost_tax' => 0,
                    'oper' => $app_detail['operator_name'],
                    'create_time' => $app_detail['create_time'],
                    'operator' => kernel::single('desktop_user')->get_name(),
                    //'settle_method' => $app_detail['settle_method'],
                    //'settle_status' => $app_detail['settle_status'],
                    //'settle_operator' => $app_detail['settle_operator'],
                    //'settle_time' => $app_detail['settle_time'],
                    //'settle_num' => $app_detail['settle_num'],
                    //'settlement_bn' => $app_detail['settlement_bn'],
                    //'settlement_money' => $app_detail['settlement_money'],
                    'memo' => $app_detail['memo'],
                );
            }
        }
        return $iostock_data;
    }
    #生成16位的调拨单号
    function gen_appropriation_no(){
        $i = rand(0,9);
        $appropriation_no = 'S'.date('YmdHis').$i;
        return $appropriation_no;
    }
}
