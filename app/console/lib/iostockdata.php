<?php
/**
* 出入库发起通知方法
* 数据组织
*/
class console_iostockdata{
    
   

   /***
    * 组织入库详情和明细
    * @iso_id 出入库单号
    */
    function get_iostockData($iso_id){
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        $iostockObj = kernel::single('ome_iostock');
        $oIsoItems = &app::get('taoguaniostockorder')->model("iso_items");
        $Iso = $oIso->dump(array('iso_id'=>$iso_id),'iso_bn,branch_id,type_id,create_time,memo,extrabranch_id,corp_id,supplier_id');
        $oSupplier = app::get('purchase')->model('supplier');
        $supplier = $oSupplier->dump($Iso['supplier_id'],'area,name,zip,addr,telphone,bn');
        
        $branch_detail = $this->getBranchByid($Iso['branch_id']);
        $iso_items = $oIsoItems->getList('product_id,bn,nums as num,product_name as name,price,defective_num,normal_num,iso_items_id',array('iso_id'=>$iso_id));
        $data = array(
            'io_bn' => $Iso['iso_bn'],
            'branch_bn'=> $branch_detail['branch_bn'],
            'branch_id'=> $Iso['branch_id'],
            'branch_type'=>$branch_detail['type'],
            'storage_code'=> $branch_detail['storage_code'],
            'create_time'=>$Iso['create_time'],
            'memo'=>$Iso['memo'],
            'type_id'=>$Iso['type_id'],
            'supplier_bn'=>$supplier['bn'],
            'extrabranch_id' => $Iso['extrabranch_id'],
        );
        $iostock_type = $iostockObj->getIoByType($Iso['type_id']);
        $extrabranch_id = $Iso['extrabranch_id'];
        if ($extrabranch_id){
            $extrabranch_detail = $this->getExtrabranch($extrabranch_id,$iostock_type);
            $data = array_merge($data,$extrabranch_detail);
        }
        //物流公司
        if ($Iso['corp_id']) {
            $corp_id = $Iso['corp_id'];
            $oDly_corp = app::get('ome')->model('dly_corp');
            $dly_corp = $oDly_corp->dump($corp_id,'type');
            $data['logi_code'] = $dly_corp['type'];
        }
        $total_goods_fee = 0;
        foreach($iso_items as $item){
            $total_goods_fee +=$item['price'] * $item['num'];
        }
        $data['total_goods_fee'] = $total_goods_fee;
        $data['items'] = $iso_items;
   
        return $data;
    }

   
    /**
    * 调拔入库
    *
    */
   function allocate_out($iso_id){
        $isoObj = &app::get('taoguaniostockorder')->model('iso');
        $oProducts = &app::get('ome')->model("products");
        $iso = $isoObj->dump($iso_id,'*');
        #取调拔单入的仓库
		$original_id = $iso['original_id'];
        $oAppropriation_items = &app::get('taoguanallocate')->model('appropriation_items');
        $appropriaton_items = $oAppropriation_items->getlist('*',array('appropriation_id'=>$original_id),0,1);
        $to_branch_id = $appropriaton_items[0]['to_branch_id'];
        $from_branch_id = $appropriaton_items[0]['from_branch_id'];
        $iso_itemsObj = &app::get('taoguaniostockorder')->model('iso_items');
        $iso_items = $iso_itemsObj->getlist('*',array('iso_id'=>$iso_id),0,-1);
        #生成调拔出库单根据出入库单
        #组织明细
        $items = array();
        
        foreach($iso_items as $iso_item){
            $Products = $oProducts->dump($iso_item['product_id'],'name,unit,goods_id,store');
            $items[$iso_item['product_id']] = array(
                'product_id' => $iso_item['product_id'],
                'product_bn' => $iso_item['bn'],
                'name' => $Products['name'],
                'bn' => $iso_item['bn'],
                'unit' => $iso_item['unit'],
                'store' => $Products['store'],
                'price' => $iso_item['price'],
                'nums' => $iso_item['normal_num'],
              );
        }
        $op_name = kernel::single('desktop_user')->get_name();
        $iostock_instance = kernel::single('console_iostockorder');
        $shift_data = array (
                'iostockorder_name' => date('Ymd').'入库单',
                'supplier_id' => $iso['supplier_id'],
                'branch' => $to_branch_id,
                'extrabranch_id'=>$from_branch_id,
                'type_id' => 4,//调拔入库
                'iso_price' => 0,
                'memo' => $iso['memo'],
                'operator' => $op_name,
                'products' => $items,
                'original_bn' => $iso['iso_bn'],
                'original_id' => $iso['iso_id'],
       			      'confirm' => 'N',
        );
        
        $result = $iostock_instance->save_iostockorder($shift_data,$msg);
//        if($result){
//            $this->notify_otherstock('0',$result,'create');
//            
//        }
        return $result;
   }
    /***
    * 获取仓库对应售后仓
    * @access public
    * @param Array $branch_id 仓库ID
    */
   function getDamagedbranch($branch_id){
       $oBranch = app::get('ome')->model('branch');
       $branch = $oBranch->db->selectrow("select branch_id,branch_bn FROM sdb_ome_branch WHERE branch_id=".$branch_id." AND type='damaged'");
       if ($branch) {
            return $branch;
       }else{
            $branch_damaged = $oBranch->db->selectrow("select branch_id,branch_bn FROM sdb_ome_branch WHERE parent_id=".$branch_id." AND type='damaged'");
            return $branch_damaged;
       }
       
       


   }

    /**
    * 根据仓库编号返回仓库名称
    *
    */
    function getBranchBybn($branch_bn){
        $oBranch = app::get('ome')->model('branch');
        $branch = $oBranch->db->selectrow("select branch_id,name FROM sdb_ome_branch WHERE branch_bn='$branch_bn'");
        return $branch;
    }
    /**
    * 获取仓库详情
    * @access public
    * @param  $branch_id 仓库ID
    * 
    * @return Array 
    */
    function getBranchByid($branch_id){
        $oBranch = app::get('ome')->model('branch');
        $branch_damaged = $oBranch->getlist('type,branch_id,branch_bn,storage_code',array('branch_id' => $branch_id),0,1);
        $branch_damaged = $branch_damaged[0];
       return $branch_damaged;

    }

    /**
    * 获取仓库详情
    * @access public
    * @param  $branch_id 仓库ID
    * 
    * @return Array 
    */
    function getBranchByStorageCode($storage_code){
        $oBranch = app::get('ome')->model('branch');
        $branchInfo = $oBranch->dump(array('storage_code' => $storage_code),'type,branch_id,branch_bn,storage_code');
       return $branchInfo ? $branchInfo : '';

    }

    /**
    * 发起转储单创建
    * @access public
    *
    * @return 
    */
    function notify_stockdump($stockdump_id,$method){
        
        $OStockdump = app::get('console')->model('stockdump');
        $Oitems = app::get('console')->model('stockdump_items');
        $stockdump = $OStockdump->dump($stockdump_id,'stockdump_bn,create_time,memo,from_branch_id,to_branch_id');
        $items = $Oitems->getlist('bn,product_name as name,num,appro_price as price',array('stockdump_id'=>$stockdump_id),0,-1);
        $from_branch_id = $stockdump['from_branch_id'];
        $to_branch_id = $stockdump['to_branch_id'];
        $from_branch = $this->getBranchByid($from_branch_id);
        $to_branch = $this->getBranchByid($to_branch_id);
        
        $branch_id = $stockdump['from_branch_id'];
        $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id);

        if ($method == 'create'){
            $method = 'create';
            $data = $stockdump;
            $data['src_storage'] = $from_branch['storage_code'];
            $data['dest_storage'] = $to_branch['storage_code'];
            $data['items'] = $items;
        }else if($method == 'cancel'){
            $method = 'updateStatus';
            $data = array(
                'stockdump_bn'=>$stockdump['stockdump_bn'],    
            );
        }else{
            echo '无此方法';
            exit;
        }
        $result = kernel::single('console_event_trigger_stockdump')->$method($wms_id, $data, true);
     
        return $result;
    }

    /*
     * 获取操作员管辖仓库
     * 
     */

    function getBranchByUser($dataType=null) {
        $oBops = &app::get('ome')->model('branch_ops');
        $Obranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_list = array();
        if (!$is_super){
            $opInfo = kernel::single('ome_func')->getDesktopUser();
            $op_id = $opInfo['op_id'];
            $bops_list = $oBops->getList('branch_id', array('op_id' => $op_id), 0, -1);
            if ($bops_list)
                foreach ($bops_list as $k => $v) {
                    $bps[] = $v['branch_id'];
                }
                if ($bps){
                    $branch_list = $Obranch->getList('branch_id,name,uname,phone,mobile', array('type'=>'main','branch_id' => $bps), 0, -1);
                }
        }else{
            $branch_list = $Obranch->getList('branch_id,name,uname,phone,mobile', array('type'=>'main'), 0, -1);
        }
        
        
        if ($branch_list)
            ksort($branch_list);
        return $branch_list;
    }

 

    /**
    * 获取外部仓库信息
    */
    function getExtrabranch($extrabranch_id,$iostock_type){
        $oExtrabranch = app::get('ome')->model('extrabranch');
        $extrabranch = $oExtrabranch->dump($extrabranch_id,'*');
        $area = $extrabranch['area'];
        $area = explode(':',$area);
        $area = explode('/',$area[1]);
        $extrabranch_detail = array();
        if ($iostock_type=='0'){#出库
                $extrabranch_detail = array(
                    'receiver_name'=>$extrabranch['uname'],
                    'receiver_zip'=>$extrabranch['zip'],
                    'receiver_state'=>$area[0],
                    'receiver_city'=>$area[1],
                    'receiver_district'=>$area[2],
                    'receiver_address'=>$extrabranch['address'],
                    'receiver_phone'=>$extrabranch['phone'],
                    'receiver_mobile'=>$extrabranch['mobile'],
                    'receiver_email'=>$extrabranch['email'],
                );
            }else{#入库
                $extrabranch_detail = array(
                    'shipper_name'=>$extrabranch['uname'],
                    'shipper_zip'=>$extrabranch['zip'],
                    'shipper_state'=>$area[0],
                    'shipper_city'=>$area[1],
                    'shipper_district'=>$area[2],
                    'shipper_address'=>$extrabranch['address'],
                    'shipper_phone'=>$extrabranch['phone'],
                    'shipper_mobile'=>$extrabranch['mobile'],
                    'shipper_email'=>$extrabranch['email'],
                );
            }
        return $extrabranch_detail;
    }
}
