<?php
/**
 *	库内转储
 *	
 *	
 */
class console_receipt_stockdump{

    /**
     *    错误信息
     */
    public $error_info = array(
        'STOCKDUMP_BN_DOES_NOT_EXIST' => '转储单号不存在!',
        'STOCKDUMP_BN_IN_STATUS' => '转储单状态不能为 已入库/失败/取消',
        'SAVE_STOCKDUMP_RECORD_FAIL' => '保存转储单失败!',
        'SAVE_STOCKDUMP_ACTUAL_SHORTAGE' => '出库数量大于调出仓库实际库存,无法出库!',
        'STOCKDUMP_ACTUAL_SHORTAGE' => '出库数量大于调出仓库实际库存,无法出库,确认失败!',
    );
    
    static $in_status = array(
        'FINISH'=>'1',
        
    );
    /**
     *    失败信息回传
     */
    public function fail($msg=''){
        $fail = array('rsp'=>'fail','msg'=>$msg);
        return $fail;
    } 
    /**
     *    成功信息回传
     */
    public function success($msg=''){
        $success = array('rsp'=>'succ','msg'=>$msg);
        return $success;
    } 
    
    /**
     *    保存
     */
    public function do_save($appro_bn,$options){
        set_time_limit(0);
        $oAppro = app::get('console')->model('stockdump');
        $oAppro_items = app::get('console')->model('stockdump_items');
        $oBranch = &app::get('ome')->model("branch_product");
        
        //验证转储单是否存在
        $is_exist = $oAppro->dump(array('stockdump_bn'=>$appro_bn),'stockdump_bn,in_status,self_status');
        if(!$is_exist['stockdump_bn']){
            return $this->fail($this->error_info['STOCKDUMP_BN_DOES_NOT_EXIST']);
        }
        
        //判断转储单号不为已入库/失败/取消
        if($is_exist['in_status'] == 1 or $is_exist['in_status'] == 2 or $is_exist['self_status'] == 0){
            return $this->success($this->error_info['STOCKDUMP_BN_IN_STATUS']);

        }
        
        $appro_lists = $oAppro_items->getList(
            'item_id,stockdump_id,stockdump_bn,product_id,bn,num,appro_price',
            array('stockdump_bn'=>$appro_bn)
        );

        $item = array();
        foreach($options['items'] as $k=>$v){
            if($v['bn'] == ''){
                unset($options['items'][$k]);
            }
        }
        //格式化item
        if(count($options['items'])>0){
            foreach($options['items'] as $v){
                $item[trim($v['bn'])] = array(
                    'normal_num' => $v['num'],
                );
            }
        }
        $options['items'] = null;
        unset( $options['items'] );

        $in_status = $options['status'];
        kernel::database()->beginTransaction();
        
        foreach($appro_lists as $key=>$value){
            if( count($item) == 0){
                $in_nums = $value['num'];
            }else{
                $in_nums = $item[$value['bn']]['normal_num'];    
            }
            $appro_lists[$key]['in_nums'] = $in_nums;
            $oAppro_items->update(array('in_nums'=>$in_nums),array('item_id'=>$value['item_id']));
        }
        
        kernel::database()->commit();
        $appro_lists = $item = null;
        unset($appro_lists,$item);
        //更新转储单状态
        $this->update_status($appro_bn,'1');

        $is_stock_diffnum_bool =  $this->is_stock_diffnum($appro_bn);
       
        //状态为FINISH已入库
        if($options['status'] == 'FINISH' and $is_stock_diffnum_bool == false){
            //执行出入库事务
            $appro_lists = $oAppro_items->getList('stockdump_id,stockdump_bn,item_id,bn,num,in_nums,defective_num,appro_price',array('stockdump_bn'=>$appro_bn));
            
            $ioResult = $this->save_iostock($appro_lists);
            
            if($ioResult['rsp'] == 'fail'){
                return $ioResult;
            }
            //如果无差异,更改确认状态为无需确认
            $oAppro->update(array('confirm_type'=>0),array('stockdump_bn'=>$appro_bn));
        }else{
            //如果有差异,更改确认状态为未确认
            $oAppro->update(array('confirm_type'=>1),array('stockdump_bn'=>$appro_bn));
        }
        
        
        //更新转储单备注
        $oAppro->update(array('memo'=>$options['memo']),array('stockdump_bn'=>$appro_bn));
       
        return $this->success();
    }

    /**
     *    出入库事务
     */
    public function save_iostock(&$appro_lists){

         $oAppro = app::get('console')->model('stockdump');
         $original_operator = $oAppro->dump(array('stockdump_id'=>$appro_lists[0]['stockdump_id']),'stockdump_id,stockdump_bn     ,operator_name,from_branch_id,to_branch_id,from_branch_name,to_branch_name,memo');
         #$iostock = app::get('ome')->model('iostock');
         #$iostockObj = kernel::service('ome.iostock');
         $iostock_bn =  $original_operator['stockdump_bn'];
         $oBranchProduct = &app::get('ome')->model("branch_product");
         $oBranch = &app::get('ome')->model("branch");
         kernel::database()->beginTransaction();
         $iostockData_in = array(
            'branch_id'=> $original_operator['to_branch_id'],
            'original_bn'=>$iostock_bn,
            'original_id'=>$original_operator['stockdump_id'],
            'operator'=>$original_operator['operator_name'],
            'memo'=>$original_operator['memo'],
         );
         $iostockData_out = array(
            'branch_id'=> $original_operator['from_branch_id'],
            'original_bn'=>$iostock_bn,
            'original_id'=>$original_operator['stockdump_id'],
            'operator'=>$original_operator['operator_name'],
            'memo'=>$original_operator['memo'],
         );
         foreach($appro_lists as $key=>$value){
             //入库数据
            $item = array(
                'bn' => $value['bn'],
                'price'=>$value['appro_price'],
                'iso_items_id'=>$value['item_id'],
                'nums'=>$value['in_nums']
            );
            $iostockData_in['items'][] = $item;
            $iostockData_out['items'][] = $item;
            

            //出库单 差异数量检测 判断实际库存与出库数量 防止出现负库存
            $is_branch_product_data = $oBranchProduct->getList(
                'store,store_freeze',
                array(
                    'branch_id' => $original_operator['from_branch_id'],
                    'product_id' => $value['product_id']
                )
            );
            //实际可用库存
            $branch_product_available_nums = $is_branch_product_data[0]['store'] - $is_branch_product_data[0]['store_freeze'];
            
            //如果释放冻结库存后的实际库存仍然 小于 超出预计出库的数量 则确认失败
            if( $branch_product_available_nums < ($value['in_nums'] - $value['num']) ){
                return $this->fail($this->error_info['SAVE_STOCKDUMP_ACTUAL_SHORTAGE']);
            }
         }
        $iostockObj = kernel::single('console_iostockorder');
        //入库
       
        $iostockObj->confirm_iostockorder($iostockData_in,600,$msg);
       
        $iostockData_in = null;
        unset($iostockData_in);
        //出库，释放预占库存
        $this->clear_stockout_store_freeze($iostock_bn);
        $iostockObj->confirm_iostockorder($iostockData_out,9,$msg);
  
        $iostockData_out = null;
        unset($iostockData_out);
        kernel::database()->commit();
        return $this->success();
    }

    /**
     *    更新状态
     */
    public function update_status($appro_bn,$in_status){
        $oAppro = app::get('console')->model('stockdump');
        //修改调拨单的状态
        #$in_status = self::$in_status[$in_status];
        $oAppro->update(array('in_status'=>$in_status),array('stockdump_bn'=>$appro_bn));
        return true;
    }
    
 

    /**
     *    判断全部出入库时数量是否有差异
     *    false 无差异 true 有差异
     */
    public function is_stock_diffnum($appro_bn = ''){
        $result = false;//最终返回状态
        if($appro_bn == '') return $result;
        $sql = "SELECT COUNT(*) FROM sdb_console_stockdump_items WHERE stockdump_bn = '".$appro_bn."' AND in_nums != num";
        $tmp = kernel::database()->select($sql);
        if($tmp[0]['COUNT(*)']>0){
            $result = true;
        }else{
            $result = false;
        }
        
        return $result;
    }
    

    /**
     *    释放出库单预占库存量
     */
    public function clear_stockout_store_freeze($appro_bn){
        $oAppro = app::get('console')->model('stockdump');
        $oAppro_items = app::get('console')->model('stockdump_items');
        $pStockObj = kernel::single('console_stock_products');
        $oProducts = &app::get('ome')->model('products');
        $oBranch_product = &app::get('ome')->model('branch_product');
        $appro_lists = $oAppro_items->getList(
            'item_id,stockdump_id,stockdump_bn,product_id,bn,num,in_nums,appro_price',
            array('stockdump_bn'=>$appro_bn)
        );
        $appro_data = $oAppro->dump(array('stockdump_bn'=>$appro_bn),'from_branch_id,to_branch_id');
        $branch_id = $appro_data['from_branch_id'];
        foreach($appro_lists as $value){
            $product_id = $value['product_id'];
            $num = $value['num'];
            $oProducts->chg_product_store_freeze($product_id,$num,'-','stockdump');
            $oBranch_product->chg_product_store_freeze($branch_id,$product_id,$num,'-','stockdump');

        }

        return true;
    }

    public function splitIostock($iostockBn = '',&$iostockData = array(),$type = '',$msg = '',$iotype = ''){
        $iostockObj = kernel::service('ome.iostock');
        $max = 200;#每次发送条数
        $i = 1;
        $data = array();
        
        foreach($iostockData as $k=>$v){
           $data[$k] = $v;
           if($i == $max){
               $stock = $iostockObj->set($iostockBn,$data,$type,$msg,$iotype);
               $i = 1;
               $data = array();
           }else{
               $i++;
           }
           
        }
        if($i>1 && $i<=$max){
            $stock = $iostockObj->set($iostockBn,$data,$type,$msg,$iotype);
        }
        $data = array();
    }
    
    /***
    * 查询编号是否存在
    * @stockdump_bn 转储单编号
    */
    public function checkExist($stockdump_bn){
        $oStockdump = &app::get('console')->model('stockdump');
        $stockdump = $oStockdump->dump(array('stockdump_bn'=>$stockdump_bn),'*');
       
        return $stockdump;
    }

    /**
    * 查询对应状态是否可操作
    *
    */
    public function checkValid($stockdump_bn,$status,&$msg){
        $stockdump = $this->checkExist($stockdump_bn);
        $in_status = $stockdump['in_status'];
        $self_status = $stockdump['self_status'];
        switch($status){
            case 'PARTIN':
            case 'FINISH':
                if ($self_status=='0' || $self_status=='2'){
                    $msg = '转储单状态已取消或关闭,不可以入库';
                    return false;
                }
                break;
            case 'FAILED':
            case 'CANCEL':
            case 'CLOSE':
                if (($in_status=='1')  ){

                    $msg = '转储单状态为已入库不可以取消';
                    return false;
                }
                if ($self_status == '0'){
                    $msg = '转储单状态已取消不可以取消';
                    return false;
                }
                break;
        }
        return true;
    }
    
    /**
     *   转储单确认事务
     */
    public function confirm_stock($appro_bn){
        $oStockdump = app::get('console')->model('stockdump');
        $ostock_items = app::get('console')->model('stockdump_items');
        $oBranchProduct = &app::get('ome')->model("branch_product");
        $oBranch = &app::get('ome')->model("branch");

        $appro_lists = $ostock_items->getList(
            'item_id,stockdump_id,stockdump_bn,product_id,bn,num,in_nums,defective_num,appro_price',
            array('stockdump_bn'=>$appro_bn)
        );
        $original_operator = $oStockdump->dump(array('stockdump_bn'=>$appro_bn),'operator_name,from_branch_id,to_branch_id,from_branch_name,to_branch_name');

       
        $iostock_bn =  $appro_bn;//编号

        $ioResult = $this->save_iostock($appro_lists);
       
        if ($ioResult['rsp'] == 'succ'){
            //更新出入库单确认状态 增加确认人、确认时间
            $confirm_data = array(
                'confirm_type' => 2,
                'confirm_name' => kernel::single('desktop_user')->get_name(),
                'confirm_time' => time(),
            );
            
            $oStockdump->update($confirm_data,array('stockdump_bn'=>$iostock_bn));

            return $this->success();
        }else{
            return $ioResult;
        }
       

        
    }

    /**
    * 取消转储单
    */
    function cancel($stockdump_bn){
        $approObj = app::get('console')->model('stockdump');
        $type = $approObj->update(array('self_status'=>'0','response_time'=>time()),array('stockdump_bn'=>$stockdump_bn));
        $stockObj = kernel::single('console_stock');
        if ($type){
            $stockObj->clear_stockout_store_freeze($stockdump_bn);
        }
        return $type;
    }

}
