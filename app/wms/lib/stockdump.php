<?php
/**
 *	库内转储
 *	
 *	
 */
class wms_stockdump{

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
        $success = array('rsp'=>'success','msg'=>$msg);
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
        $is_exist = $oAppro->dump(array('stockdump_bn'=>$appro_bn),'stockdump_bn,in_status,type,self_status');
        if(!$is_exist['stockdump_bn']){
            return $this->fail($this->error_info['STOCKDUMP_BN_DOES_NOT_EXIST']);
        }
        
        //判断转储单号不为已入库/失败/取消
        if($is_exist['in_status'] == 9 or $is_exist['in_status'] == 10 or $is_exist['self_status'] == 0){
            return $this->success($this->error_info['STOCKDUMP_BN_IN_STATUS']);

        }
        
        $appro_lists = $oAppro_items->getList(
            'item_id,stockdump_id,stockdump_bn,product_id,bn,num,appro_price',
            array('stockdump_bn'=>$appro_bn)
        );

        $item = array();
        foreach($options['item'] as $k=>$v){
            if($v['bn'] == ''){
                unset($options['item'][$k]);
            }
        }
        //格式化item
        if(count($options['item'])>0){
            foreach($options['item'] as $v){
                $item[trim($v['bn'])] = array(
                    'normal_num' => $v['num'],
                );
            }
        }
        $options['item'] = null;
        unset( $options['item'] );

        $in_status = $options['in_status'];
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
        $this->update_status($appro_bn,$options['in_status']);

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
         $original_operator = $oAppro->dump(array('stockdump_id'=>$appro_lists[0]['stockdump_id']),'stockdump_bn     ,type,operator_name,from_branch_id,to_branch_id,from_branch_name,to_branch_name');
         $iostock = app::get('ome')->model('iostock');
         $iostockObj = kernel::service('ome.iostock');
         $iostock_bn =  $original_operator['stockdump_bn'];
         $oBranchProduct = &app::get('ome')->model("branch_product");
         $oBranch = &app::get('ome')->model("branch");
         kernel::database()->beginTransaction();
         foreach($appro_lists as $key=>$value){
             //入库数据
            $iostockData_in[$key]['bn']               = $value['bn'];
            $iostockData_in[$key]['iostock_price']    = $value['appro_price'];
            $iostockData_in[$key]['branch_id']        = $original_operator['to_branch_id'];
            $iostockData_in[$key]['original_bn']      = $iostock_bn;
            $iostockData_in[$key]['original_id']      = $value['stockdump_id'];
            $iostockData_in[$key]['operator']         = $original_operator['operator_name'];
            $iostockData_in[$key]['type_id']          = 4; //入库类型
            $iostockData_in[$key]['nums']             = $value['in_nums'];
            //出库数据
            $iostockData_out[$key]['bn']               = $value['bn'];
            $iostockData_out[$key]['iostock_price']    = $value['appro_price'];
            $iostockData_out[$key]['branch_id']        = $original_operator['from_branch_id'];
            $iostockData_out[$key]['original_bn']      = $iostock_bn;     
            $iostockData_out[$key]['original_id']      = $value['stockdump_id'];
            $iostockData_out[$key]['original_item_id'] = $value['item_id'];
            $iostockData_out[$key]['operator']         = $original_operator['operator_name'];
            $iostockData_out[$key]['type_id']          = 40; //出库类型
            $iostockData_out[$key]['nums']             = $value['in_nums'];

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
        
        //入库
        $this->splitIostock($iostock_bn,$iostockData_in,4,$msg,0);
        $iostockData_in = null;
        unset($iostockData_in);
        //出库，释放预占库存
        $this->clear_stockout_store_freeze($iostock_bn);
        $this->splitIostock($iostock_bn,$iostockData_out,40,$msg,1);
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
        $oAppro->update(array('in_status'=>$in_status),array('stockdump_bn'=>$appro_bn));
        return true;
    }
    
    /**
     *    转储单确认事务
     */
    public function confirm_stock($appro_bn){
        $oAppro = app::get('console')->model('stockdump');
        $oAppro_items = app::get('console')->model('stockdump_items');
        $oBranchProduct = &app::get('ome')->model("branch_product");
        $oBranch = &app::get('ome')->model("branch");

        $appro_lists = $oAppro_items->getList(
            'item_id,stockdump_id,stockdump_bn,product_id,bn,num,in_nums,defective_num,appro_price',
            array('stockdump_bn'=>$appro_bn)
        );
        $original_operator = $oAppro->dump(array('stockdump_bn'=>$appro_bn),'type,operator_name,from_branch_id,to_branch_id,from_branch_name,to_branch_name');

        $iostock = app::get('ome')->model('iostock');
        $iostockObj = kernel::service('ome.iostock');
        $iostock_bn =  $appro_bn;//编号

        kernel::database()->beginTransaction();
        
        //增加库存部分出入库剩余出入库记录
        foreach($appro_lists as $key=>$value){
            //入库数据
            
            
            $iostockData_in[$key]['bn']               = $value['bn'];
            $iostockData_in[$key]['iostock_price']    = $value['appro_price'];
            $iostockData_in[$key]['branch_id']        = $original_operator['to_branch_id'];
            $iostockData_in[$key]['original_id']      = $value['stockdump_id'];
            $iostockData_in[$key]['original_bn']      = $iostock_bn;
            $iostockData_in[$key]['operator']         = $original_operator['operator_name'];
            $iostockData_in[$key]['type_id']          = 600; //入库类型
            $iostockData_in[$key]['nums']             = $value['num'];
            //出库数据
            $iostockData_out[$key]['bn']               = $value['bn'];
            $iostockData_out[$key]['iostock_price']    = $value['appro_price'];
            $iostockData_out[$key]['branch_id']        = $original_operator['from_branch_id'];
            $iostockData_out[$key]['original_bn']      = $iostock_bn;     //出库的原始单号
            $iostockData_out[$key]['original_id']      = $value['stockdump_id'];
            $iostockData_out[$key]['original_item_id'] = $value['item_id'];
            $iostockData_out[$key]['operator']         = $original_operator['operator_name'];
            $iostockData_out[$key]['type_id']          = 500; //出库类型
            $iostockData_out[$key]['nums']             = $value['num'];
            
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
                kernel::database()->rollback();
                return $this->fail($this->error_info['STOCKDUMP_ACTUAL_SHORTAGE']);
            }
            //更新入库数量
            $appro_list_data = array(
                'item_id'=>$value['item_id'],
                'in_nums'=>$value['num'],
            );
            $oAppro_items->save($appro_list_data);
            $parent_id = $to_branch_data = $defective_branch_data = null;
            
        }
       
        //入库操作
        $this->splitIostock($iostock_bn,$iostockData_in,4,$msg,0);

        //出库操作
        $this->clear_stockout_store_freeze($iostock_bn);
        $this->splitIostock($iostock_bn,$iostockData_out,40,$msg,1);

        kernel::database()->commit();

        //更新出入库单确认状态 增加确认人、确认时间
        $confirm_data = array(
            'confirm_type' => '2',
            'confirm_name' => kernel::single('desktop_user')->get_name(),
            'confirm_time' => time(),
            'in_status'=>'9',
        );
        
        $result = $oAppro->update($confirm_data,array('stockdump_bn'=>$iostock_bn));

        return $this->success();
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
        $appro_lists = $oAppro_items->getList(
            'item_id,stockdump_id,stockdump_bn,product_id,bn,num,in_nums,appro_price',
            array('stockdump_bn'=>$appro_bn)
        );
        $appro_data = $oAppro->dump(array('stockdump_bn'=>$appro_bn),'from_branch_id,to_branch_id');

        foreach($appro_lists as $value){
            //释放出库单预占仓库库存量
            $log_data = array(
                'original_id'=> $value['stockdump_id'],
                'original_type'=>'iostock',#kernel::single('ome_freeze_log')->get_original_type（）方法获取
                'memo'=>'单据出库释放出库单预占仓库库存量',
            );
            $pStockObj->branch_unfreeze($appro_data['from_branch_id'],$value['product_id'],$value['num'],$log_data);
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
    
    /**
    * 发起转储单请求
    *
    */
    function notify_stockdump($stockdump_bn,$method='confirm'){
        $oStockdump = app::get('console')->model('stockdump');
        $stockdump_detail = $oStockdump->dump(array('stockdump_bn'=>$stockdump_bn),'*');
        $oStock_items = app::get('console')->model('stockdump_items');
        
        $to_branch_id = $stockdump_detail['to_branch_id'];
        $branch_detail = kernel::single('wms_iostockdata')->getBranchByid($to_branch_id);
        $data = array(
            'io_source'=>'selfwms',
            'stockdump_bn'=>$stockdump_detail['stockdump_bn'],
            'memo'=>$stockdump_detail['memo'],
            'branch_bn'=>$branch_detail['branch_bn'],
            'operate_time'=>$stockdump_detail['operator_name']
        );
        $items = $oStock_items->getList('bn,num',array('stockdump_bn'=>$appro_bn));
        $data['items'] = $items;
        $wms_id = kernel::single('ome_branch')->getWmsIdById($to_branch_id);
        
        if ($method=='confirm'){
            $data['status'] = 'FINISH';
            
        }else if($method=='cancel'){
            $data['status'] = 'CANCEL';
        }
        kernel::single('wms_event_trigger_stockdump')->inStorage($wms_id, $data, true);
    }

}
