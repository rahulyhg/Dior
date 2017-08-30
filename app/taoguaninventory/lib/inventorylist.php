<?php
class taoguaninventory_inventorylist{

    /*
    *保存盘点明细
    *@param array data
    *$msg
    */

    function save_inventory($data,&$msg){
        $invObj = &app::get('taoguaninventory')->model('inventory');
        /*盘点单日志*/
        $opObj  = &app::get('ome')->model('operation_log');
        /**/
        $invitemObj = &app::get('taoguaninventory')->model('inventory_items');
        $op_id   = kernel::single('desktop_user')->get_id();
        $pos_name = $data['pos_name'];
        $barcode = $data['barcode'];
        $branch_id = $data['branch_id'];
        $number     = $data['number'];
        $inventory_id = $data['inventory_id'];
        $product_id = $data['product_id'];
        $branch_product_result=$this->create_branch_product($branch_id,$product_id);
        if(!$branch_product_result){
            $msg='商品和仓库关联失败!';
            $opObj->write_log('inventory_modify@taoguaninventory', $data['inventory_id'], $msg);
        }
        if($pos_name){//如果有货号
            $pos_id = $this->create_branch_product_pos($branch_id,$pos_name,$product_id);
        }else{
            $pos_id=0;
        }
        $data['pos_id'] = $pos_id;
        $db = kernel::database();
        $aDate = explode('-',date('Y-m-d'));
        $sqlstr = '';
        $sqlstr.=' AND io.pos_id='.$pos_id;
        $sql = 'SELECT inv.inventory_id,inv.difference,inv.op_id,inv.inventory_name,io.obj_id,io.item_id  FROM sdb_taoguaninventory_inventory as inv
                left join sdb_taoguaninventory_inventory_object as io on inv.inventory_id=io.inventory_id
        		WHERE inv.branch_id='.$branch_id.' AND inv.confirm_status=1 AND io.product_id='.$product_id.$sqlstr.'
        		ORDER BY inv.inventory_id';

        $inventory = $db->selectRow($sql);
        //$price = $this->get_price($product_id,$branch_id);
        $accounts_num = $this->get_accounts_num($product_id,$branch_id);
        if($inventory){
            //是否有同样的商品+仓库+货位
            if($inventory['op_id'] == $op_id){
                //更新明细信息
                $old_inventory_id = $data['inventory_id'];

                unset($data['inventory_id']);
                $data['inventory_id'] = $inventory['inventory_id'];
                $data['item_id'] = $inventory['item_id'];
                $data['num_over'] = 1;
                $result=$this->update_inventory_item($data);


                if(!$result){
                    $msg = '商品更新失败!';

                }else{
                    $msg ='因此商品已存在于未确认盘点表中，且是同一管理员操作,所以此次盘点添加商品数据覆盖';
                    if($old_inventory_id!=$data['inventory_id']){
                        $msg.='添加至'.$inventory['inventory_name'].'中';
                    }
                }

                return $result;
            }else{
                $msg ='此商品已存在于盘点列表中,请确认!';


                return false;
            }
        }else{
            $invitem = $invitemObj->dump(array('inventory_id'=>$data['inventory_id'],'product_id'=>$product_id),'item_id');
            if($invitem){
                $data['item_id'] = $invitem['item_id'];
            }
           $result=$this->update_inventory_item($data);
           $this->update_inventorydifference($data['inventory_id']);
          $opObj->write_log('inventory_modify@taoguaninventory', $data['inventory_id'], '更新盘点明细');

            return $result;

        }

    }

    /*
    *创建商品与货位关系
    */

    public function create_branch_product_pos($branch_id,$pos_name,$product_id){
        $oBranch_pos = &app::get('ome')->model('branch_pos');
        $oBranch_product_pos = &app::get('ome')->model('branch_product_pos');
        if($pos_name!=' '){//如果有货号
            $branch_pos = $oBranch_pos->getlist('*',array('branch_id'=>$branch_id,'store_position'=>$pos_name),0,1);
            if(!$branch_pos){
                $branch_pos_data = array();
                $branch_pos_data['branch_id'] = $branch_id;
                $branch_pos_data['store_position'] = $pos_name;
                $branch_pos_data['create_time'] = time();
                $result = $oBranch_pos->save($branch_pos_data);
                $pos_id = $branch_pos_data['pos_id'];
            }else{
                $pos_id = $branch_pos[0]['pos_id'];
            }

            $branch_product_pos = $oBranch_product_pos->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id,'pos_id'=>$pos_id),'*');
            if(!$branch_product_pos){
                $branch_product_pos_data = array();
                $branch_product_pos_data['branch_id'] = $branch_id;
                $branch_product_pos_data['product_id'] = $product_id;
                $branch_product_pos_data['pos_id']   = $pos_id;
                $branch_product_pos_data['create_time']   = time();
                $result = $oBranch_product_pos->save($branch_product_pos_data);
            }
            return $pos_id;
        }
    }

    /*
    *创建商品与仓库关系
    */
    public function create_branch_product($branch_id,$product_id){
        $oBranch_product  = &app::get('ome')->model('branch_product');
        $branch_product = $oBranch_product->getlist('branch_id',array('branch_id'=>$branch_id,'product_id'=>$product_id),0,1);

        if(!$branch_product){
            $branch_product_data = array();
            $branch_product_data['branch_id'] = $branch_id;
            $branch_product_data['product_id'] = $product_id;

            $result = $oBranch_product->save( $branch_product_data );
        }
        return true;
    }

    /*
    * 获取商品账面数量
    * @param product_id branch_id
    */
    public function get_accounts_num($product_id,$branch_id){
        $oBranch_product  = &app::get('ome')->model('branch_product');
        $branch_store = $oBranch_product->getStoreByBranch($product_id,$branch_id);

        if($branch_store){
            $accounts_num  = $branch_store;
        }else{
            $accounts_num = 0;
        }
        return $accounts_num;
    }

    /*
    *获取商品单价
    *新增如果设置了成本取成本设置值
    */
    public function get_price($product_id,$branch_id){

        $setting_stockcost_cost = app::get("ome")->getConf("tgstockcost.cost");
        $setting_stockcost_get_value_type = app::get("ome")->getConf("tgstockcost.get_value_type");
        
        $tgstockcost = kernel::single("tgstockcost_taog_instance");
        $price = 0;
        if($setting_stockcost_get_value_type){
            $iostock = app::get("ome")->model("iostock");
            
            if($setting_stockcost_get_value_type == '1'){ //取货品的固定成本
                $price = $tgstockcost->get_product_cost($product_id);
            }
            elseif($setting_stockcost_get_value_type == '2'){ //取货品的单位平均成本  to 如果仓库货品表没有记录？
                $price = $tgstockcost->get_product_unit_cost($product_id,$branch_id);
            }
            elseif($setting_stockcost_get_value_type == '3'){//取货品的最近一次出入库成本  to 如果在该仓库下没有出入库记录？
                #
                $product = kernel::database()->selectrow('SELECT bn FROM sdb_ome_products WHERE product_id='.$product_id);
                $product_bn = $product['bn'];
                $price = $tgstockcost->get_last_product_unit_cost($product_bn,$branch_id,$product_id,0);
            }
            elseif($setting_stockcost_get_value_type == '4'){//取0
                $price = 0;
            }
        }else{

            if(app::get('purchase')->is_installed()){
               $poObj  = &app::get('purchase')->model('po');
               $price = $poObj->getPurchsePrice($product_id,'DESC');
               if(!$price){
                   $price = 0;
               }
           }else{
               $price = 0;
           }


        }
         
        
        return $price;
    }

    /*
    *获取盘点明细里商品实际数量总计
    */
    private function get_inventory_bybn($inventory_id,$product_id){
        $db = kernel::database();
        $sql = 'SELECT sum(actual_num) as actual_num FROM sdb_taoguaninventory_inventory_object
                WHERE inventory_id='.$inventory_id.' AND product_id='.$product_id;
        $inventory_obj = $db->selectrow($sql);
        return $inventory_obj['actual_num'];
    }

    /*
    *更新盘点单明细
    */
    public function update_inventory_item($data){
       $opObj  = &app::get('ome')->model('operation_log');
        $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');
        $oProducts= &app::get('ome')->model('products');
        $products = $oProducts->dump($data['product_id'],'barcode,product_id,name,bn,spec_info,unit');
       if($products){
            $data = array_merge($data,$products);
       }
       $inv_item_data = array();
       $inv_item_data['inventory_id'] = $data['inventory_id'];
        $inv_item_data['product_id'] = $data['product_id'];
        $inv_item_data['price'] = $price;
        $inv_item_data['availability'] = 'true';
        $inv_item_data['memo'] = '在线盘点，新增商品数量';
        $inv_item_data['oper_time'] = time();
       if($data['item_id']){
            $inv_item_data['item_id'] = $data['item_id'];
        }else{
            $inv_item = $oInventory_items->dump(array('inventory_id'=>$data['inventory_id'],'product_id'=>$data['product_id']),'item_id,actual_num');
            if($inv_item){
                $inv_item_data['item_id'] = $inv_item['item_id'];
            }
            $inv_item_data['name'] = $data['name'];
            $inv_item_data['bn'] = $data['bn'];

            $inv_item_data['spec_info'] = $data['spec_info'];
            $inv_item_data['unit'] = $data['unit'];
       }
       $inv_item_data['barcode'] = $data['barcode'];
       $inv_item_data['is_auto'] = $data['is_auto']=='1' ? '1':'0';
       $item_result = $oInventory_items->save($inv_item_data);

        if(!$item_result){
            $msg='明细表保存失败';

        }
       $data['item_id'] = $inv_item_data['item_id'];
       $total = 0;
       $obj_result = $this->create_inventory_obj($data);
        if(!$obj_result){
           $msg= 'obj表创建失败!';
           $opObj->write_log('inventory_modify@taoguaninventory', $data['inventory_id'], $msg);
        }

        return $obj_result;
   }

    /*
    *创建盘点单中间表
    * @param array
    *
    */
   public function create_inventory_obj($data){
       $oInventory_object = &app::get('taoguaninventory')->model('inventory_object');
       $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');

       if($data['pos_name'] && $data['pos_id']==''){
            $data['pos_id'] = $this->create_branch_product_pos($data['branch_id'],$data['pos_name'],$data['product_id']);
        }
       $inv_obj=$oInventory_object->dump(array('inventory_id'=>$data['inventory_id'],'product_id'=>$data['product_id'],'pos_id'=>$data['pos_id']),'item_id,obj_id');
        $inv_object = array();
        if($data['obj_id']){
            $inv_object['obj_id'] = $data['obj_id'];
        }
        if($data['num_over']==1){//数量是否覆盖标识
            if($inv_obj){
                $inv_object['obj_id'] = $inv_obj['obj_id'];
            }
        }
        $inv_object['oper_id'] = kernel::single('desktop_user')->get_id();;
        $inv_object['oper_name'] = kernel::single('desktop_user')->get_name();
        $inv_object['oper_time'] = time();
        $inv_object['inventory_id'] = $data['inventory_id'];
        $inv_object['product_id'] = $data['product_id'];
        $inv_object['pos_id'] = $data['pos_id'];
        $inv_object['bn'] = $data['bn'];
        $inv_object['barcode'] = $data['barcode'];
        $inv_object['pos_name'] = $data['pos_name'];
        $inv_object['actual_num'] = $data['number'];
        $inv_object['item_id'] = $data['item_id'];
        $result = $oInventory_object->save($inv_object);
        if($result){
            #成本价
            $price = $this->get_price($data['product_id'],$data['branch_id']);
            $inv_item_data['price'] = $price;
            
            $inv_item_data['accounts_num'] = $this->get_accounts_num($data['product_id'],$data['branch_id']);
            $inv_item_data['actual_num'] = kernel::single('taoguaninventory_inventorylist')->get_inventory_bybn($data['inventory_id'],$data['product_id']);
            $inv_item_data['shortage_over'] = $inv_item_data['actual_num']-$inv_item_data['accounts_num'];
            $inv_item_data['item_id']= $data['item_id'];
            $oInventory_items->save($inv_item_data);

        }
        return $result;
   }


    /*
    * 创建盘点表
    * @param data
    *
    */
    function create_inventory($data,&$msg){
        $oInventory = &app::get('taoguaninventory')->model('inventory');
        $opObj  = &app::get('ome')->model('operation_log');

        $op_name = kernel::single('desktop_user')->get_name();
        $op_id   = kernel::single('desktop_user')->get_id();
        $oEncoded_state = &app::get('taoguaninventory')->model('encoded_state');
        $get_state = $oEncoded_state->get_state('inventory');
        if(!$get_state){
            $msg='编码表信息不存在';
            return false;
        }
        $inventory_checker = $data['inventory_checker']=='' ? $op_name : $data['inventory_checker'];
        $second_checker    = $data['second_checker']=='' ? $op_name : $data['second_checker'];
        $finance_dept    = $data['finance_dept']=='' ? $op_name : $data['finance_dept'];
        $warehousing_dept    = $data['warehousing_dept']=='' ? $op_name : $data['warehousing_dept'];
        $op_id = $op_id ? $op_id : -1;
        $inv['inventory_name']      = $data['inventory_name'];
        $inv['inventory_bn']        = $get_state['state_bn'];
        $inv['inventory_date']      = time();
        $inv['add_time'] = strtotime($data['add_time']);
        $inv['inventory_checker']   = $inventory_checker;
        $inv['second_checker']      = $second_checker;
        $inv['finance_dept']        = $finance_dept;
        $inv['warehousing_dept']    = $warehousing_dept;
        $inv['op_name']             = $op_name;
        $inv['op_id']               = $op_id;
        $inv['branch_id']           = $data['branch_id'];
        $inv['branch_name']         = $data['branch_name'];
        $inv['inventory_type']      = $data['inventory_type'];
        $inv['pos'] = $data['pos'];
        $inv['memo'] = $data['memo'];
        $inv['inventory_type'] = $data['inventory_type'];
        $result = $oInventory->save($inv);
        if($result){
            $encoded_state_data = array();
            $encoded_state_data['currentno'] = $get_state['currentno'];
            $encoded_state_data['eid'] = $get_state['eid'];
            $oEncoded_state->save($encoded_state_data);
            //补全
            if($data['inventory_type']==2){
                $this->auto_product_list($inv['inventory_id'],$data['branch_id']);
            }
        }

       $opObj->write_log('inventory_modify@taoguaninventory', $inv['inventory_id'], '创建盘点单');
       return $inv['inventory_id'];

   }

   /*
   * 获得商品货位
   * @param data
   * return data
   */
   function get_product_pos($data){
        $oProduct_pos = &app::get('ome')->model('branch_product_pos');
        $oBranch_pos= &app::get('ome')->model('branch_pos');
        $product_pos_list = $oProduct_pos->getlist('pos_id,product_id',array('product_id'=>$data['product_id']),0,-1);
        foreach($product_pos_list as $k=>$v){
            $branch_pos = $oBranch_pos->dump(array('pos_id'=>$v['pos_id']),'store_position');
            $product_pos_list[$k]['pos_name'] = $branch_pos['store_position'];
        }
        return $product_pos_list;

   }

    /*
     *删除盘点明细
     *@param array
     *return boolean
    */
    function del_inventory($data){
       $act = $data['action'];
       $oInventory = &app::get('taoguaninventory')->model('inventory');
       $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');
       $oinventory_object = &app::get('taoguaninventory')->model('inventory_object');
       $oBranch_product  = &app::get('ome')->model('branch_product');
       $obj_id = intval($data['obj_id']);
       $item_id = intval($data['item_id']);
       $inventory_id = intval($data['inventory_id']);

       $inventory = $oInventory->dump($inventory_id,'branch_id,inventory_type');
       $items = $oInventory_items->dump(array('inventory_id'=>$inventory_id,'item_id'=>$item_id),'product_id');
       switch($act){
           case 'item':
                $result = $oInventory_items->delete(array('inventory_id'=>$inventory_id,'item_id'=>$item_id));
                if($result){
                    $oinventory_object->delete(array('inventory_id'=>$inventory_id,'item_id'=>$item_id));
                }
                if($inventory['inventory_type']=='2'){
                    $product_add = array();
                    $product_add['inventory_id']=$inventory_id;
                    $product_add['branch_id']=$inventory['branch_id'];
                    $product_add['product_id']=$items['product_id'];
                    $product_add['number']=0;
                    $product_add['is_auto']='1';

                    $this->update_inventory_item($product_add);
                }
               break;
           case 'obj':
               $inventory_object = $oinventory_object->dump($obj_id,'product_id,actual_num,item_id');
               $oinventory_object->delete(array('inventory_id'=>$inventory_id,'obj_id'=>$obj_id));
               $product_id = $inventory_object['product_id'];
               $branch_id = $inventory['branch_id'];
               $item_actual_num = $this->get_inventory_bybn($inventory_id,$product_id);

               if($item_actual_num==0){
                    $oInventory_items->delete(array('inventory_id'=>$inventory_id,'product_id'=>$product_id));
                    if($inventory['inventory_type']=='2'){
                        $product_add = array();
                        $product_add['inventory_id']=$inventory_id;
                        $product_add['branch_id']=$inventory['branch_id'];
                        $product_add['product_id']=$product_id;
                        $product_add['number']=0;
                        $product_add['is_auto']='1';

                        $this->update_inventory_item($product_add);
                    }

               }else{
                    $branch_store = $oBranch_product->getStoreByBranch($product_id,$branch_id);
                    if($branch_store){
                     $accounts_num  = $branch_store;
                    }else{
                     $accounts_num = 0;
                    }
                    $items_data = array();
                    $items_data['item_id'] = $inventory_object['item_id'];
                    $items_data['inventory_id'] = $inventory_object['inventory_id'];
                    $items_data['actual_num'] = $item_actual_num;
                    $items_data['accounts_num'] = $accounts_num;
                    $items_data['shortage_over'] = $item_actual_num-$accounts_num;
                    $oInventory_items->save($items_data);

               }
            break;
        }
    }

    /**
    * 刷新盘点单预盈亏
    */
    function refresh_shortage_over($inventory_id,$branch_id){
        #判断如果状态为已确认不刷新 避免打开的页面重复预盈亏
        $inventory_detail = &app::get('taoguaninventory')->model('inventory')->dump($inventory_id, 'confirm_status');
        $confirm_status = $inventory_detail['confirm_status'];
        if ($confirm_status=='1'){
            $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');
            $inv_item = $oInventory_items->getlist('name,bn,spec_info,item_id,unit,price,memo,actual_num,shortage_over,accounts_num,product_id',array('inventory_id'=>$inventory_id,'is_auto'=>'0'));
            foreach($inv_item as $k=>$v){
                $item_data = array();
                $accounts_num = $this->get_accounts_num($v['product_id'],$branch_id);
                $item_data ['accounts_num'] = $accounts_num;
                $item_data ['shortage_over']  = $v['actual_num']-$accounts_num;
                $item_data ['item_id']  = $v['item_id'];

                $result = $oInventory_items->save($item_data);

            }
        }
        return true;
    }


    /**
     * 确认盘点单
     * @access public
     * @param  $data $msg
     * @return boolean
     */
    public function confirm_inventory($data,&$msg){
        $oInventory = &app::get('taoguaninventory')->model('inventory');
        $iostock_instance = kernel::service('taoguan.iostock');
        //$refresh = $this->refresh_shortage_over($data['inventory_id'],$data['branch_id']);
        $oProducts= &app::get('ome')->model('products');
        $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');
        $pagelimit = 100;
        $total = $oInventory->getInventoryTotal($data['inventory_id']);
        $inventory = $oInventory->getlist('inventory_type',array('inventory_id'=>$data['inventory_id']),0,1);
        $inventory_type = $inventory[0]['inventory_type'];
        $page = ceil($total['count']/$pagelimit);
        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录

            if(count($total)<0){
                $msg='当前盘点单中无可以入库的商品';
                return false;
            }
            //
          for($i=1;$i<=$page;$i++){

            $inventory = array();//盘亏
            $overage = array();//盘盈
            $iostock_data = array();
            $default_store = array();
            $inventory_data = $oInventory_items->getList('item_id,product_id,bn,price,shortage_over,actual_num,accounts_num', array('inventory_id'=>$data['inventory_id']), $pagelimit*($i-1), $pagelimit,'item_id desc');
            foreach($inventory_data as $k=>$v){
                #
                $items = array();
                $items['item_id'] = $v['item_id'];
                $accounts_num = $this->get_accounts_num($v['product_id'],$data['branch_id']);
                $items['accounts_num'] = $accounts_num;
                $shortage_over = $v['actual_num']-$accounts_num;
                #如果账面数量有变更更新记录
                if($v['accounts_num']!=$accounts_num){
                    $oInventory_items->save($items);
                }
                #
                $iostock_data= array(
                    'branch_id' => $data['branch_id'],
                    'original_bn' => $data['inventory_bn'],
                    'original_id' => $data['inventory_id'],
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => $v['price'],
                    'nums' => $shortage_over,
                    'oper' => $data['inventory_checker'],
                    'create_time' => $data['inventory_date'],
                    'operator' => $data['op_name'],
                    'memo' => $data['memo'],
                );
                if($inventory_type=='4'){
                    $iostock_data['nums'] = abs($iostock_data['nums']);
                    $default_store[$v['item_id']] = $iostock_data;
                }else{
                    if($shortage_over>0){
                        $overage[$v['item_id']] = $iostock_data;
                    }else{
                        $iostock_data['nums'] = abs($iostock_data['nums']);
                        $inventory[$v['item_id']] = $iostock_data;
                    }
                }
            }
            if(count($default_store)>0){
                eval('$type='.get_class($iostock_instance).'::DEFAULT_STORE;');
                 $iostock_bn = $iostock_instance->get_iostock_bn($type);
                 $io = $iostock_instance->getIoByType($type);
                $result = $iostock_instance->set($iostock_bn, $default_store, $type, $default_store_msg, $io);
            }
            if(count($overage)>0){
                eval('$type='.get_class($iostock_instance).'::OVERAGE;');
                 $iostock_bn = $iostock_instance->get_iostock_bn($type);
                 $io = $iostock_instance->getIoByType($type);
                $result = $iostock_instance->set($iostock_bn, $overage, $type, $overage_msg, $io);

            }
            if(count($inventory)>0) {

                eval('$type='.get_class($iostock_instance).'::INVENTORY;');
                $iostock_bn = $iostock_instance->get_iostock_bn($type);
                $io = $iostock_instance->getIoByType($type);
                $result = $iostock_instance->set($iostock_bn, $inventory, $type, $inventory_msg, $io);

            }
         }
//
            if($result){
                $inventory_data = array(
                    'inventory_id' => $data['inventory_id'],
                    'confirm_status'=>2,
                );
                $oInventory->save($inventory_data);

            }
            return true;
        }


    }

    /**
     * 组织出库数据
     * @access public
     * @param  $inventory_id 出入库ID
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
                    'oper' => $inventory_detail['inventory_checker'],
                    'create_time' => $inventory_detail['inventory_date'],
                    'operator' => $inventory_detail['op_name'],
                    'memo' => $inventory_detail['memo'],
                );
            }
        }
        return $iostock_data;
    }

    /*
    * 更新盘点单差异值
    */
    function update_inventorydifference($inventory_id){
        $oInventory = &app::get('taoguaninventory')->model('inventory');
        $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');
        $inventory_items = $oInventory_items->getlist('shortage_over,price',array('inventory_id'=>$inventory_id),0,-1);
        $total = 0 ;
        foreach( $inventory_items as $k=>$v) {
            $total +=$v['shortage_over']*$v['price'];
        }
        $inventory_data = array(
            'inventory_id' => $inventory_id,
            'difference'    => $total
        );
        $result = $oInventory->save($inventory_data);
        return $result;
    }

    /*
    *更新盘点单状态值
    */
    function updateinventorystatus($data){
        $oInventory = &app::get('taoguaninventory')->model('inventory');
        $inventory_data = array(
            'inventory_id'  => $data['inventory_id'],
        );
        if($data['import_status']){
            $inventory_data['import_status'] = $data['import_status'];
        }
        if($data['update_status']){
            $inventory_data['update_status'] = $data['update_status'];
        }
        $oInventory->save($inventory_data);
    }

    /**
    *查询此货品是否可以操作
    */
    function checkproductoper($product_id,$branch_id=''){
        $db = kernel::database();
        $sql = 'SELECT count(i.bn) as count FROM sdb_taoguaninventory_inventory_items as i
                    left join sdb_taoguaninventory_inventory as inv on i.inventory_id=inv.inventory_id
                    WHERE i.product_id=\''.$product_id.'\' AND inv.branch_id='.$branch_id.' AND inv.confirm_status=1 ';

        $product = $db->selectrow($sql);
        if($product['count']>0){

            return false;
        }else{
            return true;
        }

    }

    function get_reset_product_list($inventory_id,$branch_id,$inventory_items){
        set_time_limit(0);
        $inventoryItemsObj = &app::get('taoguaninventory')->model('inventory_items');
        $db = kernel::database();
        if($inventory_items){
            $product_id_list = array();
            foreach($inventory_items as $k=>$v){
                $product_id_list[] = $v['product_id'];
            }

            $product_id_list = implode(',',$product_id_list);
            $sqlstr.=' AND product_id not in ('.$product_id_list.')';
          }else{
            $sqlstr.='';
          }
          $pagelimit = 100;

          $product = $db->selectrow('SELECT count(product_id) as count FROM sdb_ome_branch_product
                            WHERE branch_id='.$branch_id.$sqlstr);
          $total = $product['count'];
           $page = ceil($total/$pagelimit);
           for($i=1;$i<=$total;$i++){
                $offset = $pagelimit*($i-1);
                $offset = max($offset,0);
            $product_id_list_sql = 'SELECT product_id,store FROM sdb_ome_branch_product
                            WHERE branch_id='.$branch_id.$sqlstr.' LIMIT '.$offset .','.$pagelimit;

            $product_id_list = $db->select($product_id_list_sql);

             foreach( $product_id_list as $pk=>$pv){
                $product_list = array();
                $product_list['product_id'] = $pv['product_id'];

                //$accounts_num = $this->get_accounts_num($pv['product_id'],$branch_id);
                $accounts_num = $pv['store'];
                if($accounts_num>0){
                    $product_list['number'] = 0;
                    $product_list['branch_id'] = $branch_id;
                    $product_list['is_auto']='1';
                    $product_list['inventory_id'] = $inventory_id;

                    $invitem = $inventoryItemsObj->dump(array('inventory_id'=>$inventory_id,'product_id'=>$pv['product_id']),'item_id');
                    if(!$invitem){
                        $this->update_inventory_item($product_list);
                    }
                }
             }
           }


    }

    function auto_product_list($inventory_id,$branch_id){
        $db = kernel::database();
        $db->exec('begin');

        $sqlstr = 'SELECT '.$inventory_id.' AS inventory_id, \'1\' as is_auto,p.product_id,p.name,p.bn,p.spec_info,bp.store as accounts_num,0 as actual_num, -bp.store AS shortage_over,'.time().' as oper_time FROM sdb_ome_products AS p LEFT JOIN sdb_ome_branch_product AS bp ON p.product_id=bp.product_id WHERE bp.branch_id='.$branch_id.' AND bp.store>0';
        $item_sql = 'INSERT INTO sdb_taoguaninventory_inventory_items(inventory_id,is_auto,product_id,`name`,bn,spec_info,accounts_num,actual_num,shortage_over,oper_time) '.$sqlstr;

        $item_result = $db->exec($item_sql);
        if($item_result ){
            $item_obj_sql = 'INSERT INTO sdb_taoguaninventory_inventory_object(inventory_id,item_id,product_id,bn,actual_num,oper_time) SELECT '.$inventory_id.' as inventory_id,i.item_id as item_id,i.product_id,i.bn,i.actual_num,'.time().' as oper_time FROM sdb_taoguaninventory_inventory_items AS i  WHERE i.inventory_id='.$inventory_id;
            $obj_result = $db->exec($item_obj_sql);
            if($obj_result){
                $db->exec('commit');
            }else{
                $db->exec('rollback');
            }
        }else{
            $db->exec('rollback');
        }

    }

    function hide_add_product_list($inventory_id,$inventory_type,$branch_id){
        $inventoryItemsObj = &app::get('taoguaninventory')->model('inventory_items');
        if($inventory_type==2){//全盘
            $items = $inventoryItemsObj->getlist('item_id,product_id',array('inventory_id'=>$inventory_id),0,-1);
            $product_id_list = array();

            foreach($items as $k=>$v){

                 array_push($product_id_list,$v['product_id']);
            }

            $sqlstr = '';
            if($product_id_list){

                $product_id_list = implode(',',$product_id_list);
                $sqlstr.=' AND product_id not in ('.$product_id_list.')';
             }
                $product_id_list_sql = 'SELECT product_id FROM sdb_ome_branch_product WHERE store>0 AND branch_id='.$branch_id.$sqlstr;
                $add_product_id_list = kernel::database()->select($product_id_list_sql);
                if($add_product_id_list){
                    foreach($add_product_id_list as $key=>$val){
                        $product_add = array();
                        $product_add['inventory_id']=$inventory_id;
                        $product_add['branch_id']=$branch_id;
                        $product_add['product_id']=$val['product_id'];
                        $product_add['number']=0;
                        $product_add['is_auto']='1';
                        $this->update_inventory_item($product_add);
                    }
                }

        }else if($inventory_type==3){//部分
            $items = $inventoryItemsObj->getlist('item_id',array('inventory_id'=>$inventory_id,'is_auto'=>'1'),0,-1);
            foreach($items as $k=>$v){
                $del_data = array(
                    'action'=>'item',
                    'inventory_id'=>$inventory_id,
                    'item_id'=>$v['item_id']

                );

                $this->del_inventory($del_data);
            }

        }

    }

    /**
    * 测试当类型为期初时，仓库是否有进出库记录
    */
    function check_product_iostock($branch_id){
        $iostockObj = &app::get('ome')->model('iostock');
        $iostock = $iostockObj->getlist('branch_id',array('branch_id'=>$branch_id),0,1);
        if($iostock){
            return true;
        }else{
            return false;
        }
    }

    /**
    *
    */
    function get_inventorybybranch_id($branch_id){
        $inventoryObj = &app::get('taoguaninventory')->model('inventory');
        $inventory = $inventoryObj->getlist('inventory_id',array('branch_id'=>$branch_id));

        if($inventory){
            return true;
        }else{
            return false;
        }
    }

    /**
    *
    */
    function doajax_inventorylist($data,$itemId,&$fail,&$succ,&$fallinfo){

        $oInventory = &app::get('taoguaninventory')->model('inventory');
        $iostock_instance = kernel::service('taoguan.iostock');

        $oInventory_items = &app::get('taoguaninventory')->model('inventory_items');
        $inventory = $oInventory->getlist('inventory_type',array('inventory_id'=>$data['inventory_id']),0,1);
        $inventory_type = $inventory[0]['inventory_type'];

        if ( method_exists($iostock_instance, 'set') ){
            //存储出入库记录
            foreach($itemId as $item_id){
                $inventory = array();//盘亏
                $overage = array();//盘盈
                $iostock_data = array();
                $default_store = array();
                kernel::database()->exec('begin');
                $item_id = explode('||',$item_id);

                $item_id = $item_id[1];

                $items_data = $oInventory_items->getList('item_id,product_id,bn,price,shortage_over,actual_num,accounts_num,status', array('inventory_id'=>$data['inventory_id'],'item_id'=>$item_id));
                if($items_data[0]['status']=='true'){
                    //$succ++;
                    continue;
                }
                $items = array();
                $items['item_id'] = $item_id;
                $accounts_num = $this->get_accounts_num($items_data[0]['product_id'],$data['branch_id']);
                $items['accounts_num'] = $accounts_num;
                $shortage_over = $items_data[0]['actual_num']-$accounts_num;
                #如果账面数量有变更更新记录

                #更新盘点单明细为已盘点
                $item_result = kernel::database()->exec('UPDATE sdb_taoguaninventory_inventory_items SET `status`=\'true\',accounts_num='.$accounts_num.' WHERE item_id='.$item_id.' AND `status`="false"');
                if(!$item_result){
                    $fallinfo[] = '更新盘点明细状态失败，请联系管理员确认!';
                    
                    kernel::database()->exec('rollback'); continue;
                }
                #
                $iostock_data= array(
                    'branch_id' => $data['branch_id'],
                    'original_bn' => $data['inventory_bn'],
                    'original_id' => $data['inventory_id'],
                    'original_item_id' => $item_id,
                    'supplier_id' => 0,
                    'bn' => $items_data[0]['bn'],
                    'iostock_price' => $items_data[0]['price'],
                    'nums' => $shortage_over,
                    'oper' => $data['inventory_checker'],
                    'create_time' => $data['inventory_date'],
                    'operator' => $data['op_name'],
                    'memo' => $data['memo'],
                );


                if($inventory_type=='4'){
                    $iostock_data['nums'] = abs($iostock_data['nums']);
                    $default_store[$item_id] = $iostock_data;
                }else{
                    if($shortage_over>0){
                        $overage[$item_id] = $iostock_data;
                    }else{
                        $iostock_data['nums'] = abs($iostock_data['nums']);
                        $inventory[$item_id] = $iostock_data;
                    }
                }

                if(count($default_store)>0){
                eval('$type='.get_class($iostock_instance).'::DEFAULT_STORE;');
                 $iostock_bn = $iostock_instance->get_iostock_bn($type);
                 $io = $iostock_instance->getIoByType($type);
                $result = $iostock_instance->set($iostock_bn, $default_store, $type, $default_store_msg, $io);
                }
                if(count($overage)>0){
                    eval('$type='.get_class($iostock_instance).'::OVERAGE;');
                     $iostock_bn = $iostock_instance->get_iostock_bn($type);
                     $io = $iostock_instance->getIoByType($type);
                    $result = $iostock_instance->set($iostock_bn, $overage, $type, $overage_msg, $io);

                }
                if(count($inventory)>0){

                    eval('$type='.get_class($iostock_instance).'::INVENTORY;');
                    $iostock_bn = $iostock_instance->get_iostock_bn($type);
                    $io = $iostock_instance->getIoByType($type);
                    $result = $iostock_instance->set($iostock_bn, $inventory, $type, $inventory_msg, $io);

                }

                if($result){
                    $succ++;
                    kernel::database()->exec('commit');

                }else{
                    $fail++;

                    $fallinfo[] = $items_data[0]['bn'];

                    kernel::database()->exec('rollback');
                }

            }
            return true;
        }

    }

    function ajax_inventorylist($inventory_id){
        $inventory_items = &app::get('taoguaninventory')->model('inventory_items')->getList('item_id', array('inventory_id'=>$inventory_id,'status'=>'false'), 0, -1,'item_id desc');
        $item_id = array();
        
        foreach ($inventory_items as $inventory){
            $item_id[] = $inventory['item_id'];
        }
        return $item_id;

    }
}
