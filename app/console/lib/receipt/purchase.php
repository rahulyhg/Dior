<?php
class console_receipt_purchase{

    private static $eo_status = array(
        'PARTIN'=>'2',
        'FINISH'=>'3',
        
    );

    /**
     *
     * 采购单更新方法
     * @param array $data 采购单数据信息
     * 
     */
    public function update(&$data,&$msg){
        $oPo = &app::get('purchase')->model("po");
        $oProducts = &app::get('ome')->model("products");
        $Po = $oPo->dump(array('po_bn'=>$data['io_bn']),'po_id,memo,branch_id,supplier_id');
        $oPo_items = &app::get('purchase')->model("po_items");
        $po_id = $Po['po_id'];
        $items = $data['items'];
        $iostock_update = true;
        $io_status = $data['io_status'];
        $operator       = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        $iostock_data = array(
            'memo'=>$data['memo'],
            'operate_time'=>$data['operate_time'],
        
        );
        #检查货品否都存在
        if ($items){
            if(!$this->checkBnexist($po_id,$items)){
                $msg = '有货品不存在!';
                return false;
            }
            foreach($items as $item){
                $po_item = $oPo_items->dump(array('po_id'=>$po_id,'bn'=>$item['bn']),'price,defective_num,in_num,item_id,num');
                $products_detail = $oProducts->dump(array('bn'=>$item['bn']),'name,unit,goods_id,product_id');
                $defective_num = $item['defective_num']+$po_item['defective_num'];
                $in_num = $item['normal_num']+$po_item['in_num'];

                $status = 1;
                if($po_item['num']>$in_num+$po_item['out_num']){
                    $status = 2;
                }else if($po_item['num']==$po_item['in_num']+$po_item['out_num']){
                    $status=3;
                }
                #更新采购单明细数量和状态
                $item_data = array(
                    'item_id'=> $po_item['item_id'],
                    'defective_num'=>$defective_num,
                    'in_num'=>$in_num,
                    'status'=>$status,
                );
                $oPo_items->save($item_data);
                 //更新在途库存
                #确认在途预占库存
                if ($item['normal_num']>0){
                    #总数量
                    if ($in_num>$po_item['num']){
                        $effective_num = $in_num-$item['normal_num'];#已入库数量与请求数量差值
                        if ($effective_num<=$po_item['num']){#差值与原申请数量比较
                            $nums = $po_item['num']-$effective_num;
                        }else{
                            $nums = 0;
                        }
                    }else{
                        $nums = $item['normal_num'];
                    }
                    
                    
                    if ($nums>0){
                        $this->updateBranchProductArriveStore($Po['branch_id'], $products_detail['product_id'], $nums, '-');
                    }
                }
                

                

                #为供应商与商品建立关联
                if($products_detail['goods_id']!=''){
                    $supplier_goods = array(
                        'supplier_id' => $Po['supplier_id'],
                        'goods_id' => $products_detail['goods_id']
                    );
                    $su_goodsObj = &app::get('purchase')->model('supplier_goods');
                    $su_goodsObj->save($supplier_goods);//end

                }
                if ($item['normal_num']>0){
                    $iostock_data['items'][] = array(
                         'name' => $products_detail['name'],
                         'bn' => $item['bn'],
                         'price' => $po_item['price'],
                        'purchase_num' => $po_item['num'],
                        'nums' => $item['normal_num'],
                        'is_new' => $po_item['is_new'],
                        'memo' => $data['memo'],
                        'product_id'=>$products_detail['product_id'],
                        'goods_id'=>$products_detail['goods_id'],
                        'unit'=>$products_detail['unit'],
                        'item_id'=> $po_item['item_id'],
                        );
                }
                if($defective_num>0){#需要残损确认
                    $iostock_update = false;
                }
            
            }
        }
        
        if (count($iostock_data['items'])>0){
            
            $this->save_eo($po_id,'normal',$iostock_data);#更新出入库明细
        }
        #更新单据状态
        
        $eo_status = self::$eo_status[$io_status];
        $po_update_data = array('eo_status'=>$eo_status);
        if ($eo_status == '3'){#更新为入库完成
            $po_update_data['po_status'] = '4';
            #检测是否在途库存清除干净，否则需清除
            $this->cleanArriveStore($po_id,$Po['branch_id']);
        }
        #备注处理
        if ($data['memo']){
            $purchasereturnObj = kernel::single('console_receipt_purchasereturn');
            $po_update_data['memo'] = serialize($memo);
        }
        
        if (!$iostock_update){
            $po_update_data['defective_status'] = '1';#未确认
        }
        
        $result = $oPo->update($po_update_data,array('po_id'=>$po_id));
        return $result;
    }

    /**
     *
     * 采购单取消
     * @param array $po_bn 采购单编号
     */
    public function cancel($po_bn){
        $oPo = &app::get('purchase')->model("po");
        $oPo_items = &app::get('purchase')->model("po_items");
        $po_detail = $this->checkExist($po_bn);
        if ($po_detail){
            $po_data = array(
                'po_id'=>$po_detail['po_id'],
                'po_status'=>'2',
                'eo_status'=>'4'
            );
            $oPo->save($po_data);
            #取消在途库存
            $branch_id = $po_detail['branch_id'];
            $po_items = $oPo_items->getlist('item_id,num,in_num,out_num,product_id',array('po_id'=>$po_detail['po_id']),0,-1);
            foreach($po_items as $items){
                $num = $items['num']-$items['in_num']-$items['out_num'];
                $num = $num<0?0:$num;
                $r['item_id'] = $items['item_id'];
                $r['out_num'] = $items['out_num']+$num;
                $r['status'] = ($r['out_num']+$items['in_num'])>=$items['num']?'3':$items['status'];
                $oPo_items->save($r);
                $this->updateBranchProductArriveStore($branch_id, $items['product_id'], $items['num'], '-');
            }
        }
        return true;
        
    }

    /**
     *
     * 检查需要入库的货号是否存在于采购单中
     * @param array 
     */
    public function checkBnexist($po_id,$items){
        $oPo = &app::get('purchase')->model("po");
        $bn_array = array();
        foreach($items as $item){
            $bn_array[]=$item['bn'];
        }
        $bn_total = count($bn_array);
        
        $bn_array = '\''.implode('\',\'',$bn_array).'\'';
        
        $po_items = $oPo->db->selectrow('SELECT count(item_id) as count FROM sdb_purchase_po_items WHERE po_id='.$po_id.' AND bn in ('.$bn_array.')');
        
        if ($bn_total!=$po_items['count']){#比较数目是否相等
            return false;
        }
        return true;
    }

    /**
     *
     * 检查采购单是否存在判断
     * @param array $po_bn 采购单编号
     */
    public function checkExist($po_bn){
        $oPo = &app::get('purchase')->model("po");
        $Po = $oPo->dump(array('po_bn'=>$po_bn),'po_id,po_status,branch_id');
        return $Po;
    }

    /**
     *
     * 检查采购单是否有效
     * @param  $po_bn 采购单编号
     * @param  $status 根据传入状态判断对应状态是否可以操作
     */
    public function checkValid($po_bn,$status,&$msg){
        $po = $this->checkExist($po_bn);
        $po_status = $po['po_status'];
        switch($status){
            case 'PARTIN':
            case 'FINISH':
                if ($po_status=='2'){
                    $msg = '采购已取消不可以入库';
                    return false;
                }
                if ($po_status=='4'){
                    $msg = '已入库不可以再入库';
                    return false;
                }
                break;
            case 'CANCEL':
            case 'CLOSE':
                if ($po_status=='4'){
                    $msg = '采购已完成不可以取消';
                    return false;
                }
                break;
        }
        return true;
    }

    /**
    * 执行采购入库
    * $po_id 采购单号
    * type normal正常出入库 否则残损入库 
    * #为供应商与商品建立关联
    */
    function save_eo($po_id,$type,$iostock_data){
        $oPo = &app::get('purchase')->model("po");
        $supplierObj = &app::get('purchase')->model("supplier");
        $oEo = &app::get('purchase')->model("eo");
        $oProduct_batch = &app::get('purchase')->model("branch_product_batch");
        $iostockObj = kernel::single('console_iostockdata');
        $Po = $oPo->dump($po_id,'*');
        
        $branch_id = $Po['branch_id'];
        $supplier = $supplierObj->dump($Po['supplier_id'],'*');
        $amount=0;
        $history_data= array();
        $items = $iostock_data['items'];
        foreach($items as $item){
            $eo_items[$item['product_id']]=$item;
                
            //为供应商与商品建立关联
           if ($type == 'normal'){
               $history_data[]=array('product_id'=>$item['product_id'],'purchase_price'=>$item['price'],'store'=>$item['nums'],'branch_id'=>$Po['branch_id']);
           }
           
        }

        $iostock_instance = kernel::single('console_iostockorder');
        $eo_data = array (
            'iostockorder_name' => date('Ymd').'入库单',
            'supplier' => $supplier['name'],
            'supplier_id' => $Po['supplier_id'],
            'branch' => $Po['branch_id'],
            #'type_id' => ome_iostock::PURCH_STORAGE,
            'iso_price' => $Po['delivery_cost'],
            'memo' => $iostock_data['memo'],
            'operate_time'=>$iostock_data['operate_time'],
            'operator' => $data['operator'],
            'products' => $eo_items,
            'original_bn' => $Po['po_bn'],
            'original_id' => $po_id,
            'confirm' => 'Y',
            'extend' => array('po_type' => $Po['po_type']),
             );
        if ($type == 'normal'){//采购
            $eo_data['type_id'] = ome_iostock::PURCH_STORAGE;
        }else{//残损
            $eo_data['type_id'] = ome_iostock::DAMAGED_STORAGE;
            $damagedbranch = $iostockObj->getDamagedbranch( $Po['branch_id'] );
            $eo_data['branch'] = $damagedbranch['branch_id'];
        }
        $eo_data['eo_id'] = $iostock_instance->save_iostockorder($eo_data, $msg);
        $eo_data['eo_bn'] = $iostock_instance->getIoStockOrderBn();
    

        //日志备注
        $log_msg = '对编号为（'.$Po['po_bn'].'）的采购单进行采购入库，生成一张入库单编号为:'.$eo_data['eo_bn'];
    
        //更新采购单状态
        if(count($history_data)>0){
            foreach($history_data as $k2=>$v2){

                $v2['supplier_id']=$eo_data['supplier_id'];
                $v2['eo_id'] =$eo_data['eo_id'];
                $v2['eo_bn'] =$eo_data['eo_bn'];
                $v2['purchase_time']=time();
                $v2['in_num'] = $v2['store'];
                $oProduct_batch->save($v2);
            }
        }

        //保存入库单
        $eorder_data = array(
                'eo_id'       => $eo_data['eo_id'],
                'supplier_id' => $eo_data['supplier_id'],
                'eo_bn'       => $eo_data['eo_bn'],
                'po_id'       => $po_id,
                'amount'      => $amount,
                'entry_time'  => time(),
                'arrive_time' => $Po['arrive_time'],
                'operator'    => kernel::single('desktop_user')->get_name(),
                'branch_id'   => $branch_id,
                'status'      => $status,

            );
        $oEo->save($eorder_data);
        return true;
    }
    /**
    * 入库后扣减在途库存
    *
    */
    function updateBranchProductArriveStore($branch_id, $product_id, $num, $type='+'){
        $obranch_product = &app::get('ome')->model('branch_product');
        $branch_p = $obranch_product->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id));
        if ($branch_p){
            $obranch_product->change_arrive_store($branch_id, $product_id, $num, $type);
        }else {
            $bp = array(
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'store' => 0,
                'store_freeze' => 0,
                'last_modified' => time(),
                'arrive_store' => $num,//当入库时，扣减在途库存，如果此时库存中没有此条货品记录，似乎这里的$num应该为负数
                'safe_store' => 0
            );
            $obranch_product->save($bp);
        }
    }

    /**
    * 检测是否有在途库存未清除
    *
    */
    function cleanArriveStore($po_id,$branch_id){
        $oPo_items = &app::get('purchase')->model("po_items");
        $SQL = 'SELECT i.product_id,i.num,(i.in_num+i.out_num) as totoal_in_num FROM sdb_purchase_po_items as i WHERE i.po_id='.$po_id.' AND (i.in_num+i.out_num)<i.num ';
        $items = $oPo_items->db->select($SQL);
        $item = array();
        foreach($items as $item){
            $nums = $item['num']-$item['totoal_in_num'];
            $product_id =  $item['product_id'];
            $this->updateBranchProductArriveStore($branch_id, $product_id, $nums, '-');
        }
        
    }
}