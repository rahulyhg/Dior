<?php
class console_receipt_allocate{

    /**
    * 保存调拔单出库数据
    * 并冻结对应商品库存
    *
    */
    function to_savestore($adata,$appropriation_type,$memo,$op_name,&$msg){
        $oAppropriation = &app::get('taoguanallocate')->model("appropriation");
        $oAppropriation_items = &app::get('taoguanallocate')->model("appropriation_items");
        $pStockObj = kernel::single('console_stock_products');
        $oProducts = &app::get('ome')->model("products");
        $oBranch = &app::get('ome')->model("branch_product");
        $op_name = $op_name=='' ? '未知' : $op_name;
        $appro_data = array(
           'type'=>$appropriation['type'],
            'create_time'=>time(),
            'operator_name'=>$op_name,
            'memo'=>$memo,
            'corp_id'=>$adata[0]['corp_id'],
        );
        $appro_data['appropriation_no'] =  $this->gen_appropriation_no();
        $oAppropriation->save($appro_data);
  
        $appropriation_id = $appro_data['appropriation_id'];
        
        foreach($adata as $k=>$v){
            $product = $oProducts->dump($v['product_id'],'bn,name');
            $from_branch_id=$v['from_branch_id'];
            $to_branch_id=$v['to_branch_id'];
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
                'num'=>$v['num'],
                'to_branch_num'=>$v['to_branch_num'],
                'from_branch_num'=>$v['from_branch_num'],
                );
            $oAppropriation_items->save($items_data);
//            //新增出库存冻结
//            $log_data = array(
//                'original_id'=> $rp_id,
//                'original_type'=>'purchase_return',
//                'memo'=>'新建调拔单出库增加仓库的冻结库存',
//            );
//            $pStockObj->branch_freeze($from_branch_id,$v['product_id'],$v['num'],$log_data);
            #改为在审核时冻结
        
        }
        if($appropriation_type==1){//直接调拨
            $result= $this->do_iostock($appropriation_id, $msg);
            return $result;
        }elseif($appropriation_type==2){//出入库调拨
            $result= $this->do_out_iostockorder($appropriation_id, $msg);
            return $result;
        }else{
            return false;
        }     
    }

    /**
   * 
   * 调拔单出库
   * @param  appropriation_id
   * @param  $msg
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
        $to_branch_id = 0;
        if ($app_items_detail){
            foreach ($app_items_detail as $k=>$v){
            	if(!$branch_id){
            		$branch_id = $v['from_branch_id'];
            	}

                if(!$to_branch_id){
                    $to_branch_id = $v['to_branch_id'];
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
           'extrabranch_id'=>$to_branch_id,
           'type_id' => $type, 
           'iso_price' => 0,
           'memo' => $app_detail['memo'], 
           'operator' => kernel::single('desktop_user')->get_name(), 
           'original_bn'=>'',
           'original_id'=>$appropriation_id,
           'products'=>$products
       );
        if ($app_detail['corp_id']) {
            $data['corp_id'] = $app_detail['corp_id'];
        }

        $iostockorder_instance = kernel::single('console_iostockorder');
        return $iostockorder_instance->save_iostockorder($data,$msg);
    }

    /**
 	 * 
 	 * 生成调拨单出入库明细
 	 * @param  $appropriation_id 
 	 * @param 
 	 * @param  $msg
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
                    'oper' => $app_detail['operator_name'],
                    'create_time' => $app_detail['create_time'],
                    'operator' => kernel::single('desktop_user')->get_name(),
                    'memo' => $app_detail['memo'],
                );
            }
        }
        return $iostock_data;
    }

    #生成16位的调拨单号
    private function gen_appropriation_no(){
        $i = rand(0,9);
        $appropriation_no = 'S'.date('YmdHis').$i;
        return $appropriation_no;
    }
}
?>