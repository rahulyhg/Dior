<?php
class console_receipt_purchasereturn{

    private static $return_status = array(
        'PARTIN'=>'4',
        'FINISH'=>'2',
        'CANCEL'=>'3',
        'CLOSE'=>'3',
        'FAILED'=>'3'
        
    );
   /**
     *
     * 采购退货单更新方法
     * @param array $data 采购退货单数据信息
     * @msg 错误信息
     */
    public function update(&$data,&$msg){
        $rpObj = &app::get('purchase')->model('returned_purchase');
        $rp_itemObj = &app::get('purchase')->model('returned_purchase_items');
        $purchasereturn = $rpObj->dump(array('rp_bn'=>$data['io_bn']), '*', array('returned_purchase_items'=>array('*')));
        $io_status = $data['io_status'];
        //判断平台类型如果是科捷把明细赋一下
        $branch_id = $purchasereturn['branch_id'];
        $node_type =kernel::single('ome_branch')->getNodetypBybranchId($branch_id);
        if (in_array($node_type,array('kejie'))) {
            if ($io_status == 'FINISH' && !$data['items']) {

                $item_list = array();
                $purchase_items = $purchasereturn['returned_purchase_items'];
                foreach ( $purchase_items as $purchase ) {
                    $item_list[] = array(
                        'bn'=> $purchase['bn'],
                        'num'=>$purchase['num'],
                    );
                }
                $data['items'] = $item_list;
                unset($item_list);
            }
        }
        //
        $operator       = kernel::single('desktop_user')->get_name();
        $operator = $operator=='' ? 'system' : $operator;
        $rp_id = $purchasereturn['rp_id'];
        $iostock_update = true;
        $items = $data['items'];#货品明细
        
        $out_stock_data = array(
            'supplier_id' =>$purchasereturn['supplier_id'],
            'branch_id'=>$purchasereturn['branch_id'],
            'rp_bn'=>$purchasereturn['rp_bn'],
            'rp_id'=>$rp_id,
            'memo'=>$data['memo'],
            'operate_time'=>$data['operate_time'],
        );
       
        if ($items){
            #检查明细
            
            if (!$this->checkBnexist($rp_id,$items)){
                $msg = '不存在的货号';
                return false;
            }
            #检测库存是否不足
            if (!$this->checkStore($purchasereturn['branch_id'],$items,$msg)) {
                return false;
            }
            foreach($items as $item){
                $return_item = $rp_itemObj->dump(array('rp_id'=>$rp_id,'bn'=>$item['bn']),'item_id,num,out_num,product_id,price');
               
                $out_num = intval($return_item['out_num']);
                $out_num = $item['num'] + $out_num;
                $item_data = array(
                    'item_id'=> $return_item['item_id'],
                    'out_num'=>$out_num,
                 );
                
                $rp_itemObj->save($item_data);
                $out_stock_data['items'][] = array(
                    'bn'=>$item['bn'],
                    'product_id'=>$return_item['product_id'],
                    'nums'=>$item['num'],
                    'price'=>$return_item['price'],
                    'item_id'=>$return_item['item_id'],
                    'num'=>$return_item['num']//原数量
                );
            }

            if (count($out_stock_data['items'])>0){#有明细时才会执行退货
                
                $this->save_purchaseReturn($rp_id,$out_stock_data);
            }
            
        }
        
        $po_data = array(
                'return_status'=>self::$return_status[$io_status]
        );
        #备注处理
        if ($data['memo']){#有备注更新
            $po_data['memo'] = $this->format_memo($purchasereturn['memo'],$data['memo']);
        }
        
        $result = $rpObj->update($po_data,array('rp_id'=>$rp_id));
        if ($po_data['return_status']=='2'){
            $this->cleanFreezeStore($rp_id,$purchasereturn['branch_id']);
        }
        return $result;
    }

    /**
     *
     * 采购退款单取消
     * @param array $po_bn 采购退款单编号
     */
    public function cancel($data){
        $rpObj = &app::get('purchase')->model('returned_purchase');
        $rp_bn = $data['io_bn'];
        $purchasereturn = $this->checkExist($rp_bn);
        if ($purchasereturn){
            $po_data = array('return_status'=>'3');
            if ($data['memo']){#有备注更新
                $memo = $this->format_memo($purchasereturn['memo'],$data['memo']);
                $po_data['memo'] = $memo;
            }
            
            $result = $rpObj->update($po_data,array('rp_bn'=>$rp_bn));
            $this->clear_stockout_store_freeze($purchasereturn['rp_id'],$purchasereturn['branch_id'],'','-');
            return true;
        }
    }

    /**
     *
     * 检查采购退款单货号是否存在
     * @param array $rp_id 
     */
    public function checkBnexist($rp_id,$items){
        $rpObj = &app::get('purchase')->model('returned_purchase');
        $bn_array = array();
        foreach($items as $item){
            $bn_array[]=$item['bn'];
        }
        $bn_total = count($bn_array);
        
        $bn_array = '\''.implode('\',\'',$bn_array).'\'';
        $rp_items = $rpObj->db->selectrow('SELECT count(item_id) as count FROM sdb_purchase_returned_purchase_items WHERE rp_id='.$rp_id.' AND bn in ('.$bn_array.')');
       
        if ($bn_total!=$rp_items['count']){#比较数目是否相等
            return false;
        }
        return true;
    }

    /**
     *
     * 检查采购单是否存在判断
     * @param array $rp_bn 采购单编号
     */
    public function checkExist($rp_bn){
        $rpObj = &app::get('purchase')->model('returned_purchase');
        $purchasereturn = $rpObj->dump(array('rp_bn'=>$rp_bn), '*');
        
        return $purchasereturn;
    }

    /**
     *
     * 检查采购退款单是否有效
     * @param  $rp_bn 采购单编号
     * @param $status 需要执行状态
     * @msg 返回结果
     *
     */
    public function checkValid($rp_bn,$status,&$msg){
        $purchasereturn = $this->checkExist($rp_bn);
        $return_status = $purchasereturn['return_status'];
        switch($status){
            case 'PARTIN':
            case 'FINISH':
                if ($return_status=='2' || $return_status=='3' || $return_status=='5'){
                    $msg = '退货单状态为不可以入库';
                    return false;
                }
                break;
            case 'CANCEL':
            case 'CLOSE':
                if ($return_status=='2' || $return_status=='3' || $return_status=='5'){

                    $msg = '退货单状态为不可以取消';
                    return false;
                }
                break;
        }
        return true;
    }

    /**
    * 采购退货出库产生
    * 生成退货出入库单后生成出入库明细
    * 
    */
    public function save_purchaseReturn($rp_id,$out_stock_data){
        
        $oProducts = &app::get('ome')->model("products");
        $items = $out_stock_data['items'];
        foreach($items as $item){
           $Products = $oProducts->dump($item['product_id'],'name,unit,goods_id,store');
            $shift_items[$item['product_id']] = array(
                'product_id' => $item['product_id'],
                'product_bn' => $item['bn'],
                'name' => $item['name'],
                'bn' => $item['bn'],
                'unit' => $Products['unit'],
                'store' => $Products['store'],
                'price' => $item['price'],
                'nums' => $item['nums'],
              );
        }
        $op_name = kernel::single('desktop_user')->get_name();
        $iostock_instance = kernel::single('console_iostockorder');
        $shift_data = array (
                'iostockorder_name' => date('Ymd').'出库单',
                'supplier_id' => $out_stock_data['supplier_id'],
                'branch' => $out_stock_data['branch_id'],
                'type_id' => '10',
                'iso_price' => 0,
                'memo' => $out_stock_data['memo'],
                'operate_time'=>$out_stock_data['operate_time'],
                'operator' => $op_name,
                'products' => $shift_items,
                'original_bn' => $out_stock_data['rp_bn'],
                'original_id' => $rp_id,
       			      'confirm' => 'Y',
        );

        if ( method_exists($iostock_instance, 'save_iostockorder') ){
            $iostock_instance->save_iostockorder($shift_data, $msg);
        }
        #冻结库存
        $this->clear_stockout_store_freeze($rp_id,$out_stock_data['branch_id'],$items);
        return true;
    }
    

    /**
     * 参数校验
     * @param array $params 
     * @param string $msg 
     */
    private function checkParams($params,&$msg){
        return true;
    }

    /**
    * 冻结库存添加与释放
    * 
    */
    public function clear_stockout_store_freeze($rp_id,$branch_id,$items,$type='-'){
        
        $rp_itemObj = &app::get('purchase')->model('returned_purchase_items');
        
        $oProducts = &app::get('ome')->model('products');
        $oBranch_product = &app::get('ome')->model('branch_product');
        if (empty($items)){
            $items = $rp_itemObj->getlist('num,product_id',array('rp_id'=>$rp_id),0,-1);
        }
        if ($type == '+'){
            foreach($items as $item){
                $product_id = $item['product_id'];
                $num = $item['num'];
                $oProducts->chg_product_store_freeze($product_id,$num,'+','return_purchase');
                $oBranch_product->chg_product_store_freeze($branch_id,$product_id,$num,'+','return_purchase');
 
            }
        }else{
            foreach($items as $item){
               
                if ($item['nums']){#出入库方式的释放
                    $return_item = $rp_itemObj->dump(array('rp_id'=>$rp_id,'item_id'=>$item['item_id']),'num,out_num');
                    if ($return_item['out_num']>$return_item['num']){
                        #当出库数量大于原采购退货数量时，只释放原数量
                        $effective_num = $return_item['out_num']-$item['num'];
                        if ($effective_num<=$return_item['num']){
                            $nums = $item['nums']-$effective_num;
                        }else{
                            $nums = 0;
                        }
                        
                    }else{
                        $nums = $item['nums'];
                    }
                }else{#取消时释放
                    $nums = $item['num'];
                }
               
                if ($nums>0){
                    $product_id = $item['product_id'];
                    $oProducts->chg_product_store_freeze($product_id,$nums,'-','return_purchase');
                    $oBranch_product->chg_product_store_freeze($branch_id,$product_id,$nums,'-','return_purchase');
                    
                }
            
            }
        }
        

        return true;
    }

    /**
    * 转换备注
    *
    */
     function format_memo($oldmemo,$newmemo){
        if ($newmemo){#有备注更新
            $memo = array();
            $operator       = kernel::single('desktop_user')->get_name();
            $operator = $operator=='' ? 'system' : $operator;
            if (!$oldmemo){
                $oldmemo= unserialize($oldmemo);
                if ($oldmemo)
                foreach($oldmemo as $k=>$v){
                    $memo[] = $v;
                }
            }
            $memo[]= array('op_name'=>$operator, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>htmlspecialchars($newmemo));

            $memo = serialize($memo);
            return $memo;
        }
    }

    /**
    * 清除冻结库存
    */
    
    function cleanFreezeStore($rp_id,$branch_id){
        $oPo_items = &app::get('purchase')->model("returned_purchase_items");
        $SQL = 'SELECT i.product_id,i.num,i.out_num FROM sdb_purchase_returned_purchase_items as i WHERE i.rp_id='.$rp_id.' AND i.num>i.out_num ';
        $items = $oPo_items->db->select($SQL);
        $item = array();
        foreach($items as $items){

            $nums = $items['num']-$items['out_num'];
            $product_id =  $items['product_id'];
            $item[] = array(
                        'num'=>$nums,'product_id'=>$product_id        
            );
            
        }
        $this->clear_stockout_store_freeze($rp_id,$branch_id,$item,'-');
    }

    
    /**
     * 检查库存是否不足
     * @param   array    $items
     * @param   int      $branch_id
     * @return  string   $msg
     * @access  public
     */
    public function checkStore($branch_id,$items,&$msg)
    {
        $oBranch_product = &app::get('ome')->model('branch_product');
        $oProducts = &app::get('ome')->model('products');
        $error_msg = array();
        foreach($items as $item){
            $bn = $item['bn'];
            $products = $oProducts->dump(array('bn'=>$bn),'product_id');
            $store = $oBranch_product->getStoreByBranch($products['product_id'],$branch_id);
            
            if ($item['num'] > $store) {
                $error_msg[] = $bn.'库存不足';
            }
        }
        if (count($error_msg) > 0 ) {
            $msg = implode(',',$error_msg);
            return false;
        }
        return true;
    } // end func
}