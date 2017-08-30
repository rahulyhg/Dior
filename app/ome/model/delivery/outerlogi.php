<?php

class ome_mdl_delivery_outerlogi extends ome_mdl_delivery{
    var $export_flag = null;
    
    public function table_name($real=false) 
    {
        $table_name = 'delivery';
        if($real){
            return DB_PREFIX.'ome_'.$table_name;
        }else{
            return $table_name;
        }
    }

    public function exportName(&$data) 
    {
        $data['name'] = '快速发货模板'.date('Ymd');
    }

    public function io_title( $filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
                    '*:发货单号' => 'delivery_bn',
                    '*:物流单号' => 'logi_no',
                    '*:重量' => 'weight',
                    //'*:外部发货单号' => 'outer_delivery_bn',
                    //'*:承接商' => 'outer_supplier',
                    //'*:实际物流费用' => 'delivery_cost_actual',
                );
        }
        #第三方发货新增导出
        if($this->export_flag){
            $title = array(
                    '*:物流公司' => 'logi_name',
                    '*:收件人' => 'ship_name'
                    );
            $this->oSchema[$ioType]['main'] = array_merge($this->oSchema[$ioType]['main'],$title);
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
    }

    public function fgetlist_csv(&$data,$filter,$offset,$exportType = 1) 
    {
        #第三方发货,选择全部时，只导出未发货的订单
        if($filter['isSelectedAll']){
            
            $new_filter = Array
            (
                    'type' => 'normal',
                    'pause' => "FALSE",
                    'parent_id' => 0,
                    'disabled' => "false",
                    'status' => Array('ready','progress'),
                    'ext_branch_id' => $filter['ext_branch_id']
            );
        }else{
            $filter['process'] = 'false';
            $new_filter = $filter;
        }
        $this->export_flag = true;
        $title = array();
        if ( !$data['title'] ) {
            foreach( $this->io_title() as $k => $v ){
                $title[] = $v;
            }
            $data['title']['delivery'] = '"'.implode('","',$title).'"';

            return true;
        }

        //$filter['process'] = 'false';

        $list = $this->getList('delivery_bn,logi_name,ship_name',$new_filter,0,-1);
        if ($list) {
            $contents = array();
            foreach ($list as $v) {
                $contents = array(
                    'delivery_bn' => $v['delivery_bn']."\t",
                    'logi_no'     => '',
                    'weight'      => '',
                    'logi_name' => $v['logi_name'],
                    'ship_name' => $v['ship_name'],
                );
                $data['content']['delivery'][] = implode(',', $contents);
            }
        }
        return false;
    }
    

    public function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }
    
    public function prepared_import_csv_row($row,&$title,&$tmpl,&$mark,&$newObjFlag,&$msg) 
    {
        if(empty($row)) return false;
        
        if( substr($row[0],0,1) == '*' ){
            $this->nums = 1;
            $title = array_flip($row);

            # $mark = 'title';
            return false;
        }
        
        if (empty($title)) {
            $msg['error'] = "请使用正确的模板格式！";
            return false;
        }

        $delivery_bn = trim($row[$title['*:发货单号']]);
        //$outer_delivery_bn = $row[$title['*:外部发货单号']];
        //$outer_supplier = $row[$title['*:承接商']];
        $logi_no = trim($row[$title['*:物流单号']]);
        //$delivery_cost_actual = $row[$title['*:实际物流费用']];
        $weight = trim($row[$title['*:重量']]);
        if(isset($this->nums)){
            $this->nums++;
            if($this->nums > 5000){
                $msg['error'] = "导入的数据量过大，请减少到5000条以下！";
                return false;
            }
        }

        #$mark = 'contents';
        if (empty($logi_no)) {
            $msg['warning'][] = 'Line '.$this->nums.'：运单号不能都为空！';
            return false;
        }
        
        if (empty($delivery_bn)) {
            $msg['warning'][] = 'Line '.$this->nums.'：发货单号不能都为空！';
            return false;
        }
        
        # 获取第三方发货仓
        $branchList = $this->app->model('branch')->getList('branch_id',array('owner'=>'2'));
        $branchIds = array();
        foreach ($branchList as $key => $value) {
            $branchIds[] = $value['branch_id'];
        }
        if (empty($branchIds)) {
            $msg['error'] = '第三方仓不存在，请新建！！！';
            return true;
        }

        # 判断发货单是否存在
        $deliModel = $this->app->model('delivery');
        $delivery = $deliModel->getList('delivery_id,ship_area,process,logi_id,verify,stock_status,deliv_status,expre_status,branch_id',array('delivery_bn'=>$delivery_bn),0,1);
        if (!$delivery) {
            $msg['warning'][] = 'Line '.$this->nums.'：发货单号不存在！';
            return false;
        }
        
        # 验证运单号是否被使用过
        $logi_no_exist = $deliModel->getList('delivery_id',array('logi_no'=>$logi_no,'delivery_bn|noequal'=>$delivery_bn),0,1);
        if ($logi_no_exist) {
            $msg['warning'][] = 'Line '.$this->nums.'：运单号已被使用！';
            return false;
        }

        if ($delivery[0]['process'] == 'true') {
            $msg['warning'][] = 'Line '.$this->nums.'：发货单号【'.$delivery_bn.'】已发货！';
            return false;
        }

        if (!in_array($delivery[0]['branch_id'], $branchIds)) {
            $msg['warning'][] = 'Line '.$this->nums.'：不是第三方仓不能发货！';
            return false;    
        }
        $logi_name = trim($row[$title['*:物流公司']]);
        if(!empty($logi_name)){
            #检测物流公司是否改变
            $filter['delivery_bn']  = $delivery_bn;
            $filter['logi_name'] = $logi_name;
            $rs = $deliModel->getList('delivery_id',$filter);
            #当改变了物流公司,检测物流公司是否存在
            if(empty($rs)){
                #检测物流公司是否存在
                $obj_dly_corp = &app::get('ome')->model('dly_corp');
                $corp_info = $obj_dly_corp->getList('corp_id,name',array('name'=>$logi_name));
                if(empty($corp_info)){
                    $msg['warning'][] = 'Line '.$this->nums.'：物流公司不存在！';
                    return false;
                }
            }
        }
        // 库存验证
        $branch_pObj = $this->app->model("branch_product");
        $delivery_items = $this->app->model('delivery_items')->getList('*',array('delivery_id'=>$delivery[0]['delivery_id']));
        foreach ($delivery_items as $key=>$value) {
            $bp = $branch_pObj->dump(array('branch_id'=>$delivery[0]['branch_id'],'product_id'=>$value['product_id']),'store');
            if ($bp['store'] < $value['number']) {
                $msg['warning'][] = 'Line '.$this->nums.'：【'.$value['product_name'].'】商品库存不足';
                return false;
            }
        }

        if (empty($weight)) {
            $deliveryOrderObj = $this->app->model('delivery_order');
            $delivery_order = $deliveryOrderObj->getList('order_id',array('delivery_id'=>$delivery[0]['delivery_id']));
            
            $orderObj = $this->app->model('orders');
            $weight = 0;
            foreach($delivery_order as $item){
                $orderWeight = $orderObj->getOrderWeight($item['order_id']);
                if($orderWeight==0){
                    break;
                }else{
                    $weight += $orderWeight;
                }
            }

            #商品重量有取商品重量
            if($weight == 0){
               $minWeight = $this->app->getConf('ome.delivery.minWeight');
               $weight = $minWeight ? $minWeight : 0;
            }
        }
        
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        
        $sdf = array(
            'delivery_id' => $delivery[0]['delivery_id'],
            //'outer_delivery_bn' => $outer_delivery_bn,
            //'outer_supplier' => $outer_supplier,
            'logi_no' => $logi_no,
            'logi_id'=>$corp_info[0]['corp_id'],
            'logi_name'=>$corp_info[0]['name'],
            //'delivery_cost_actual' => $delivery_cost_actual ? $delivery_cost_actual : 0,
            'weight' => $weight ? $weight : 0,
            'opInfo' => $opInfo,
            'is_super' => kernel::single('desktop_user')->is_super(),
            'user_data' => kernel::single('desktop_user')->user_data,
            'verify' => $delivery[0]['verify'],
            'stock_status' => $delivery[0]['stock_status'],
            'deliv_status' => $delivery[0]['deliv_status'],
            'expre_status' => $delivery[0]['expre_status'],
        );

        $this->import_data[] = $sdf;
        return true;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    public function finish_import_csv(){
        
        $oQueue = app::get('base')->model('queue');

        $queueData = array(
            'queue_title'=>'外部运单号导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$this->import_data,
                'app' => 'ome',
                'mdl' => 'delivery_outerlogi',
            ),
            'worker'=>'ome_mdl_delivery_outerlogi.import_run',
        );
        $oQueue->save($queueData);

        $oQueue->flush();
    }

    public function import_run($cursor_id,$params,$errormsg) 
    {
        $now = time();
        $opObj = $this->app->model('operation_log');
        $deliModel = $this->app->model('delivery');
        foreach ($params['sdfdata'] as $key=>$value) {
            $transaction = $this->db->beginTransaction();

            $opInfo = $value['opInfo']; unset($value['opInfo']);
            $is_super = $value['is_super']; unset($value['is_super']);
            kernel::single('desktop_user')->user_data = $value['user_data']; unset($value['user_data']);
            kernel::single('desktop_user')->user_id = $opInfo['op_id'];
            #第三方发货，更该了快递公司
            if($value['logi_id']){
                $deliModel->update(array('logi_name'=>$value['logi_name'],'logi_id'=>$value['logi_id']),array('delivery_bn'=>$value['delivery_bn']));
                $opObj->write_log('delivery_modify@ome', $value['delivery_id'], '第三方发货，更改快递公司',$now,$opInfo);
            }else{
                unset($value['logi_id'],$value['logi_name']);
            }

            if ($value['stock_status'] != 'true') {
                $opObj->write_log('delivery_stock@ome', $value['delivery_id'], '备货单打印(系统模拟打印)',$now,$opInfo);
            }
            if ($value['deliv_status'] != 'true') {
                $opObj->write_log('delivery_deliv@ome', $value['delivery_id'], '发货单打印(系统模拟打印)',$now,$opInfo);
            }
            if ($value['expre_status'] != 'true') {
                $opObj->write_log('delivery_expre@ome', $value['delivery_id'], '快递单打印(系统模拟打印)',$now,$opInfo);
            }

            $value['stock_status'] = 'true';
            $value['deliv_status'] = 'true';
            $value['expre_status'] = 'true';
            $value['status'] = 'progress';

            $result = $deliModel->save($value);
            if ($result) {
                if ($is_super) {
                    $branches = array('_ALL_');
                } else {
                    $branches = $this->getBranchByOp($opInfo['op_id']);
                }
                
                $process = false;

                # 校验
                if ($value['verify'] != 'true') {
                    $delivery = kernel::single('ome_delivery_check')->checkAllow($value['logi_no'],$branches,$msg);
                    if ($delivery === false) {
                        $this->db->rollback();
                        continue;
                    }

                    $verify = $deliModel->verifyDelivery($delivery);
                    if (!$verify) {
                        $this->db->rollback();
                        continue;
                    }
                }
                
                # 发货
                $delivery = kernel::single('ome_delivery_consign')->deliAllow($value['logi_no'],$branches,$msg,$patch);
                if (!$delivery) {
                    $this->db->rollback();
                    continue;
                }
                
                $result = $deliModel->consignDelivery($value['delivery_id'],$value['weight'],$msg);
                if (!$result) {
                    $this->db->rollback();
                    continue;
                }
                
                $this->db->commit($transaction);
            } else {
                $this->db->rollback();
            }
        }

        return false;
    }

    public function getBranchByOp($op_id) 
    {
        $bps = array();
        $oBops = $this->app->model('branch_ops');
        $bops_list = $oBops->getList('branch_id', array('op_id' => $op_id), 0, -1);
        if ($bops_list){
            $bps = array_map('current',$bops_list);
        }

        return $bps;
    }
    
    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'delivery';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_goods') {
            if (isset($params['acti']) && $params['acti'] == 'cost') {
                $type .= '_goodsMananger_allList_template';
            }
            elseif (isset($params['_gType'])) {
                $type .= '_goodsBatProcess_batUpload';
            }
            else {
                $type .= '_goodsMananger_allList';
            }
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'delivery';
        if ($logParams['app'] == 'ome' && $logParams['ctl'] == 'admin_receipts_outer') {
            $type .= '_third_quick';
        }
        $type .= '_import';
        return $type;
    }
}
