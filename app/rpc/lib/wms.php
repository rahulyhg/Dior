<?php
/**
* wms适配器接口参数转换
* @copytight shopex.cn 2013.04.25
*/
class rpc_wms{

    /**
    * 适配器内部接口参数转换
    * @access public 
    * @param String $method 接口方法
    * @param Array $params 接口参数
    * @return Array 标准返回格式
    */
    public function convert($method,&$params){
        $_convert_method = str_replace('.','_',$method);
        if(method_exists($this,$_convert_method)){
            $adapter_params = $this->$_convert_method($params,$adapter_method,$write_log);
            return array($adapter_method,$adapter_params,$write_log);
        }else{
            return NULL;
        }
    }

    /**
    * 入库单
    */
    private function wms_stockin_status_update(&$params,&$adapter_method='',&$write_log=array()){
        $stockin_bn = $params['stockin_bn'];
        //区分退货入库
        if (substr($stockin_bn,0,2) == 'MS') {
            
            $data = $this->wms_reship_status_update($params,$adapter_method,$write_log);
            return $data;
        }
        #日志信息
        $write_log = array(
            'log_title' => '入库结果回传:'.$params['status'],
            'original_bn' => $params['stockin_bn'],
            'log_type' => 'store.trade.stockin',
            'api_method' => 'wms.stockin.status_update',
            'return_value' => array('stockin_id'=>$stockin_bn),
        );

        #适配器接收方法
        $adapter_method = 'stockin_result';
        
        #参数转换
        $_iotype_prefix = array(
            'I' => 'PURCHASE',#采购入库
            'T' => 'ALLCOATE',#调拨入库
            'D' => 'DEFECTIVE',#残损入库
            //'E' => 'DIRECT',#直接入库
            'O' => 'OTHER',#其它入库
        );
        
        $io_type = $_iotype_prefix[substr($stockin_bn,0,1)] ? $_iotype_prefix[substr($stockin_bn,0,1)] : 'OTHER';
        $data = array(
            'io_type' =>  $io_type,
            'io_bn' => $stockin_bn,
            'branch_bn' => $params['warehouse'],
            'io_status' => $params['status'],
            'memo' => $params['remark'],
            'operate_time' =>$params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s',time()),
        );
        
        $stockin_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],1) : '';
        if ($items){
            $bns = array();
            foreach ($items as $key=>$val){
                if (!isset($val['product_bn']) || !$val['product_bn']) continue;
                if (in_array($val['product_bn'], $bns)){
                    $stockin_items[$val['product_bn']]['normal_num'] += $val['normal_num'];
                    $stockin_items[$val['product_bn']]['defective_num'] += $val['defective_num'];
                }else{
                    $stockin_items[$val['product_bn']] = array(
                        'bn' => $val['product_bn'],
                        'normal_num' => $val['normal_num'],
                        'defective_num' => $val['defective_num'],
                    );
                    $bns[] = $val['product_bn'];
                }
            }
        }
        $data['items'] = $stockin_items;

        return $data;
    }

    /**
    * 出库单
    */
    private function wms_stockout_status_update(&$params,&$adapter_method='',&$write_log=array()){
        $stockout_bn = $params['stockout_bn'];
        //发货出库
        if (substr($stockout_bn,0,2) == 'OS') {
            
            $data = $this->wms_delivery_status_update($params,$adapter_method,$write_log);
            return $data;
        }
        #日志信息
        $write_log = array(
            'log_title' => '出库结果回传:'.$params['status'],
            'original_bn' => $params['stockout_bn'],
            'log_type' => 'store.trade.stockout',
            'api_method' => 'wms.stockout.status_update',
            'return_value' => array('stockout_id'=>$stockout_bn),
        );

        #适配器接收方法
        $adapter_method = 'stockout_result';
        //
        
        #参数转换
        $_iotype_prefix = array(
            'H' => 'PURCHASE_RETURN',#采购退货
            'R' => 'ALLCOATE',#调拨出库
            'B' => 'DEFECTIVE',#残损出库
            //'A' => 'DIRECT',#直接出库
            'U' => 'OTHER',#其它出库
        );
        
        $io_type = $_iotype_prefix[substr($stockout_bn,0,1)] ? $_iotype_prefix[substr($stockout_bn,0,1)] : 'OTHER';
        $data = array(
            'io_type' =>  $io_type,
            'io_bn' => $stockout_bn,
            'branch_bn' => $params['warehouse'],
            'io_status' => $params['status'],
            'memo' => $params['remark'],
            'operate_time' =>$params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s',time()),
        );
        
        $stockout_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],1) : '';
        if ($items){
            foreach ($items as $key=>$val){
                if (!isset($val['product_bn']) || !$val['product_bn']) continue;
                $stockout_items[] = array(
                    'bn' => $val['product_bn'],
                    'num' => $val['num'],
                );
            }
        }
        $data['items'] = $stockout_items;
        return $data;
    }
    
    /**
    * 转储单
    */
    private function wms_stockdump_status_update(&$params,&$adapter_method='',&$write_log=array()){
        $stockdump_bn = $params['stockdump_bn'];
        #日志信息
        $write_log = array(
            'log_title' => '转储结果回传:'.$params['status'],
            'original_bn' => $params['stockdump_bn'],
            'log_type' => 'store.trade.stockdump',
            'api_method' => 'wms.stockdump.status_update',
            'return_value' => array('stockdump_id'=>$stockdump_bn),
        );

        #适配器接收方法
        $adapter_method = 'stockdump_result';

        $data = array(
            'stockdump_bn' => $stockdump_bn,
            'branch_bn' => $params['warehouse'],
            'status' => $params['status'],
            'memo' => $params['remark'],
            'operate_time' =>$params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s',time()),
        );
        
        $stockdump_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],1) : '';
        if ($items){
            foreach ($items as $key=>$val){
                if (!isset($val['product_bn']) || !$val['product_bn']) continue;
                $stockdump_items[] = array(
                    'bn' => $val['product_bn'],
                    'num' => $val['num'],
                );
            }
        }
        $data['items'] = $stockdump_items;
        return $data;
    }

    /**
    * 发货单
    */
    private function wms_delivery_status_update(&$params,&$adapter_method='',&$write_log=array()){
        if (substr($params['stockout_bn'],0,2) == 'OS') {
            $params = $this->format_delivery_status_data($params);
        }
        
        $delivery_bn = $params['delivery_bn'];
        #日志信息
        $write_log = array(
            'log_title' => '发货单结果回传:'.$params['status'],
            'original_bn' => $params['delivery_bn'],
            'log_type' => 'store.trade.delivery',
            'api_method' => 'wms.delivery.status_update',
            'return_value' => array('delivery_id'=>$delivery_bn),
        );

        #适配器接收方法
        $adapter_method = 'delivery_result';

        // 分批物流单号处理
        $tmp_logi_no = trim($params['logi_no']);
        $tmp_logi_no = preg_replace('/\s/', '', $tmp_logi_no);
        $tmp_logi_no = explode(';', $tmp_logi_no);
        $logi_no = $tmp_logi_no[count($tmp_logi_no)-1];
        if (!$logi_no){
            $logi_no = $tmp_logi_no[count($tmp_logi_no)-2];
        }

        $_sdf_status = array(
            'CLOSE' => 'cancel',//取消
            'FAILED' => 'cancel',//取消
            'ACCEPT' => 'accept',
            'PRINT' => 'print',//打印
            'PICK' => 'pick',//拣货
            'CHECK' => 'check',
            'PACKAGE' => 'package',
            'DELIVERY' => 'delivery',//发货
            'UPDATE' => 'update',
            'PARTIN' => 'partin',
        );
        
        $data = array(
            'delivery_bn' => trim($delivery_bn),
            'logi_no' => $logi_no,
            'logi_id' => $params['logistics'],
            'status' => $_sdf_status[$params['status']],
            'weight' => $params['weight'],
            'branch_bn' => $params['warehouse'],
            'volume' => $params['volume'],
            'memo' => $params['remark'],
            'operate_time' =>$params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s',time()),
        );
        if ($params['out_delivery_bn']) {
            $data['out_delivery_bn'] = $params['out_delivery_bn'];
        }
        $delivery_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],1) : '';
        if ($items){
            foreach ($items as $key=>$val){
                if (!isset($val['product_bn']) || !$val['product_bn']) continue;
                $delivery_items[] = array(
                    'bn' => $val['product_bn'],
                    'num' => $val['num'],
                );
            }
        }
        $data['items'] = $delivery_items;

        return $data;
    }

    /**
    * 退货单
    */
    private function wms_reship_status_update(&$params,&$adapter_method='',&$write_log=array()){
        if (substr($params['stockin_bn'],0,2) == 'MS') {
            $params = $this->format_reship_status_data($params);
        }
        $reship_bn = $params['reship_bn'];
        #日志信息
        $write_log = array(
            'log_title' => '退货结果回传:'.$params['status'],
            'original_bn' => $params['reship_bn'],
            'log_type' => 'store.trade.reship',
            'api_method' => 'wms.reship.status_update',
            'return_value' => array('reship_id'=>$reship_bn),
        );
         $_sdf_status = array(
            'CLOSE' => 'CLOSE',//取消
            'FAILED' => 'CLOSE',//取消
            'DENY'=>'CLOSE',
            'FINISH'=>'FINISH',
            'PARTIN'=>'PARTIN'
        );
        #适配器接收方法
        $adapter_method = 'reship_result';

        $data = array(
            'reship_bn' => trim($reship_bn),
            'logi_code' => $params['logistics'],
            'logi_no' => $params['logi_no'],
            'branch_bn' => $params['warehouse'],
            'status' => $_sdf_status[$params['status']],
            'memo' => $params['remark'],
            'operate_time' =>$params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s',time()),
        );
        
        $reship_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],1) : '';
        if ($items){
            $bns = array();
            foreach ($items as $key=>$val){
                if (!isset($val['product_bn']) || !$val['product_bn']) continue;
                if (in_array($val['product_bn'], $bns)){
                    $reship_items[$val['product_bn']]['normal_num'] += $val['normal_num'];
                    $reship_items[$val['product_bn']]['defective_num'] += $val['defective_num'];
                }else{
                    $reship_items[$val['product_bn']] = array(
                        'bn' => $val['product_bn'],
                        'normal_num' => $val['normal_num'],
                        'defective_num' => $val['defective_num'],
                    );
                    $bns[] = $val['product_bn'];
                }
            }
        }
        $data['items'] = $reship_items;
        
        return$data;
    }

    /**
    * 盘点
    */
    private function wms_inventory_add(&$params,&$adapter_method='',&$write_log=array()){
        $inventory_bn = $params['inventory_bn'];
        #日志信息
        $write_log = array(
            'log_title' => '盘点结果回传',
            'original_bn' => $params['inventory_bn'],
            'log_type' => 'store.trade.inventory',
            'api_method' => 'wms.inventory.status_update',
            'return_value' => array('inventory_id'=>$inventory_bn),
        );

        #适配器接收方法
        $adapter_method = 'inventory_result';
        //根据传入的外部仓库code找到对应的内部仓库编码
        $tmp_branch_bn = kernel::single('console_iostockdata')->getBranchByStorageCode($params['storage_code']);
        $operate_time = rpc_func::date2time($params['operate_time']);
        $data = array(
            'inventory_bn' => $inventory_bn,
            'branch_bn' => $tmp_branch_bn ? $tmp_branch_bn['branch_bn'] : $params['warehouse'],
            'memo' => $params['remark'],
            'operate_time' =>$params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s',time()),
        );

        $inventory_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],1) : '';
        if ($items){
            $bns = array();
            foreach ($items as $key=>$val){
                if (!isset($val['product_bn']) || !$val['product_bn']) continue;

                if (in_array($val['product_bn'], $bns)){
                    $inventory_items[$val['product_bn']]['normal_num'] += $val['normal_num'];
                    $inventory_items[$val['product_bn']]['defective_num'] += $val['defective_num'];
                }else{
                    $inventory_items[$val['product_bn']] = array(
                        'bn' => $val['product_bn'],
                        'normal_num' => $val['normal_num'],
                        'defective_num' => $val['defective_num'],
                    );
                    $bns[] = $val['product_bn'];
                }
            }
        }
        $data['items'] = $inventory_items;

        return $data;
    }

    /**
    * 库存对账
    */
    private function wms_stock_quantity(&$params,&$adapter_method='',&$write_log=array()){
        $stock_bn = $params['stock_bn'];
        #日志信息
        $write_log = array(
            'log_title' => '库存对账结果回传',
            'log_type' => 'store.trade.stock',
            'api_method' => 'wms.stock.status_update',
        );

        #适配器接收方法
        $adapter_method = 'stock_result';

        $data = array(
            'operate_time' =>$params['operate_time'] ? $params['operate_time'] : date('Y-m-d H:i:s',time()),

        );
        $stock_items = array();
        $items = isset($params['item']) ? json_decode($params['item'],1) : '';
        if ($items){
            $data['batch'] = $items[0]['batch'];
            $bns = array();
            foreach ($items as $key=>$val){
                $stock_items[] = array(
                    'branch_bn' => $val['warehouse'],
                    'logi_code' => $val['logistics'],
                    'product_bn' => $val['product_bn'],
                    'normal_num' => $val['normal_num'],
                    'defective_num' => $val['defective_num'],
                    
                );
            }
        }
        $data['items'] = $stock_items;

        return $data;
    }

     
    /**
     * 转换入库单格式为退货单
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    private function format_reship_status_data($params)
    {
        
        $reship_bn = substr($params['stockin_bn'],2,strlen($params['stockin_bn']));
        $sdf = array(
           'logistics'=>'',
            'status'=>'FINISH',
            'remark'=>'',
            'task'=>'',
            'logi_no'=>'',
            'node_version'=>'1.0',
            'app_id'=>'ecos.ome',
            'item'=>$params['item'],
            'reship_bn'=>$reship_bn,
            'warehouse'=>$params['warehouse'],
        );
        return $sdf;
    }

    /**
     * 转换出库单格式为发货单
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    private function format_delivery_status_data($sdf)
    {
        $delivery_bn = substr($sdf['stockout_bn'],2,strlen($sdf['stockout_bn']));
        $_sdf_status = array(
            
            'FINISH' => 'DELIVERY',
           
        );
        $params = array
            (
                'delivery_bn'=>$delivery_bn,
                'logistics'=>$sdf['logistics']=='顺丰速运' ? 'SF' : $sdf['logistics'],
                'logi_no'=>$sdf['logi_no'],
                'warehouse'=>$sdf['warehouse'],
                'status'=>$_sdf_status[$sdf['status']] ? $_sdf_status[$sdf['status']] : $sdf['status'],
                'volume'=>'',
                'weight'=>'',
                'remark'=>'',
                'operate_time'=>$sdf['operate_time'],
                'item'=>$sdf['item'],
        );
        if ($sdf['out_delivery_bn']) {
            $params['out_delivery_bn'] = $sdf['out_delivery_bn'];
        }
        return $params;
    }
}