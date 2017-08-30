<?php

class wms_mdl_delivery_outerlogi extends dbeav_model{

    public function table_name($real=false)
    {
        $table_name = 'delivery';
        if($real){
            return DB_PREFIX.'wms_'.$table_name;
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
                    '*:物流公司' => 'logi_name',
                    '*:收件人' => 'ship_name'
                );
        }
       $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
    }

    public function fgetlist_csv(&$data,$filter,$offset,$exportType = 1)
    {
        if($filter['isSelectedAll']){
            
            $new_filter = Array
            (
                'type' => 'normal',
                'pause' => "FALSE",
                'parent_id' => 0,
                'disabled' => "false",
                'status' => array('ready','progress'),
                'ext_branch_id' => $filter['ext_branch_id']
            );
        }else{
            $filter['process'] = 'false';
            $new_filter = $filter;
        }
        
        $title = array();
        if ( !$data['title'] ) {
            foreach( $this->io_title() as $k => $v ){
                $title[] = $v;
            }
            $data['title'] = '"'.implode('","',$title).'"';

            return true;
        }

 

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
                $data['contents'][] = implode(',', $contents);
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
        $branchList = app::get('ome')->model('branch')->getList('branch_id',array('owner'=>'2'));
        $branchIds = array();
        foreach ($branchList as $key => $value) {
            $branchIds[] = $value['branch_id'];
        }
        if (empty($branchIds)) {
            $msg['error'] = '第三方仓不存在，请新建！！！';
            return true;
        }

        # 判断发货单是否存在
        $deliModel = app::get('wms')->model('delivery');
        $delivery = $deliModel->dump(array('delivery_bn'=>$delivery_bn),'delivery_id,status,logi_id,branch_id,net_weight,ship_area');
        if (!$delivery) {
            $msg['warning'][] = 'Line '.$this->nums.'：发货单号不存在！';
            return false;
        }

        # 验证运单号是否被使用过
        $dlyCheckLib = kernel::single('wms_delivery_check');
        $logi_no_exist = $dlyCheckLib->existExpressNoBill($logi_no,$delivery['delivery_id']);
        if ($logi_no_exist) {
            $msg['warning'][] = 'Line '.$this->nums.'：运单号已被使用！';
            return false;
        }

        if ($delivery['status'] == 3) {
            $msg['warning'][] = 'Line '.$this->nums.'：发货单号【'.$delivery_bn.'】已发货！';
            return false;
        }

        if (!in_array($delivery['branch_id'], $branchIds)) {
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
                $corp_info = $obj_dly_corp->dump(array('name'=>$logi_name),'corp_id,name');
                if(empty($corp_info)){
                    $msg['warning'][] = 'Line '.$this->nums.'：物流公司不存在！';
                    return false;
                }
            }
        }
        // 库存验证
        $branch_pObj = app::get('ome')->model("branch_product");
        $delivery_items = $this->app->model('delivery_items')->getList('*',array('delivery_id'=>$delivery[0]['delivery_id']));
        foreach ($delivery_items as $key=>$value) {
            $bp = $branch_pObj->dump(array('branch_id'=>$delivery['branch_id'],'product_id'=>$value['product_id']),'store');
            if ($bp['store'] < $value['number']) {
                $msg['warning'][] = 'Line '.$this->nums.'：【'.$value['product_name'].'】商品库存不足';
                return false;
            }
        }

        $minWeight = app::get('wms')->getConf('wms.delivery.minWeight');
        if (empty($weight)) {
            $weight = $delivery['net_weight'] ? $delivery['net_weight'] : $minWeight;
        }

        $weightSet = &app::get('wms')->getConf('wms.delivery.weight');
        if (empty($weight) && $weightSet=='on'){
            $msg['warning'][] = 'Line '.$this->nums.'：请输入重量信息！';
            return false;
        }

        $maxWeight = app::get('wms')->getConf('wms.delivery.maxWeight');
        if($weight < $minWeight || $weight > $maxWeight){
            $msg['warning'][] = 'Line '.$this->nums.'：包裹重量超出系统设置范围！';
            return false;
        }

        //获取物流费用
        $wmsCommonLib = kernel::single('wms_common');

        $area = $delivery['consignee']['area'];
        $arrArea = explode(':', $area);
        $area_id = $arrArea[2];
        $delivery_cost_actual = $wmsCommonLib->getDeliveryFreight($area_id,$delivery['logi_id'],$weight);

        $opInfo = kernel::single('ome_func')->getDesktopUser();

        $sdf = array(
            'delivery_id' => $delivery['delivery_id'],
            //'outer_delivery_bn' => $outer_delivery_bn,
            //'outer_supplier' => $outer_supplier,
            'logi_no' => $logi_no,
            'delivery_cost_actual' => $delivery_cost_actual ? $delivery_cost_actual : 0,
            'weight' => $weight ? $weight : 0,
            'opInfo' => $opInfo,
            'is_super' => kernel::single('desktop_user')->is_super(),
            'user_data' => kernel::single('desktop_user')->user_data,
            //'verify' => $delivery[0]['verify'],
            //'stock_status' => $delivery[0]['stock_status'],
            //'deliv_status' => $delivery[0]['deliv_status'],
            //'expre_status' => $delivery[0]['expre_status'],
        );
        if ($corp_info) {
            $sdf['logi_id'] = $corp_info['corp_id'];
            $sdf['logi_name'] = $corp_info['name'];
        }
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
                'app' => 'wms',
                'mdl' => 'delivery_outerlogi',
            ),
            'worker'=>'wms_mdl_delivery_outerlogi.import_run',
        );
        $oQueue->save($queueData);

        $oQueue->flush();
    }

    public function import_run($cursor_id,$params,$errormsg)
    {
        $now = time();
        $opObj = app::get('ome')->model('operation_log');

        $dlyCheckLib = kernel::single('wms_delivery_check');
        $dlyProcessLib = kernel::single('wms_delivery_process');

        $deliveryObj = &app::get('wms')->model('delivery');
        $deliveryBillObj = &app::get('wms')->model('delivery_bill');

        foreach ($params['sdfdata'] as $key=>$value) {
            //$transaction = $this->db->beginTransaction();

            $opInfo = $value['opInfo']; unset($value['opInfo']);
            $is_super = $value['is_super']; unset($value['is_super']);
            kernel::single('desktop_user')->user_data = $value['user_data']; unset($value['user_data']);
            kernel::single('desktop_user')->user_id = $opInfo['op_id'];
            
            $dlyInfo = $deliveryObj->dump($value['delivery_id'],'print_status,process_status,branch_id,outer_delivery_bn,logi_id,logi_name,memo');
            if($value['logi_id']){
                $deliveryObj->update(array('logi_name'=>$value['logi_name'],'logi_id'=>$value['logi_id']),array('delivery_bn'=>$dlyInfo['delivery_bn']));
                $opObj->write_log('delivery_modify@wms', $value['delivery_id'], '第三方发货，更改快递公司',$now,$opInfo);
            }else{
                unset($value['logi_id'],$value['logi_name']);
            }
            if($dlyInfo['print_status'] > 0){
                if (($dlyInfo['print_status'] & 1) != 1) {
                    $opObj->write_log('delivery_stock@wms', $value['delivery_id'], '备货单打印(系统模拟打印)',$now,$opInfo);
                }
                if (($dlyInfo['print_status'] & 2) != 2) {
                    $opObj->write_log('delivery_deliv@wms', $value['delivery_id'], '发货单打印(系统模拟打印)',$now,$opInfo);
                }
                if (($dlyInfo['print_status'] & 4) != 4) {
                    $opObj->write_log('delivery_expre@wms', $value['delivery_id'], '快递单打印(系统模拟打印)',$now,$opInfo);
                }
            }

            $value['print_status'] = 7;
            $value['process_status'] = (($dlyInfo['process_status'] == 3) ? 3 : 1);

            //更新打印及物流重量等信息
            $result = $deliveryObj->save($value);
            if ($result) {
                //同步打印状态到oms
                $wms_id = kernel::single('ome_branch')->getWmsIdById($dlyInfo['branch_id']);
                $data = array(
                	'delivery_bn' => $dlyInfo['outer_delivery_bn'],
                );
                $res = kernel::single('wms_event_trigger_delivery')->doPrint($wms_id, $data, true);

                //保存物流单号
                $deliveryBillObj->update(array('logi_no'=>$value['logi_no']),array('delivery_id' => $value['delivery_id'],'type'=>1));

                $opObj->write_log('delivery_modify@wms', $value['delivery_id'], '修改发货单详情');

                //信息变更更新到oms
                $data = array(
                	'delivery_bn' => $dlyInfo['outer_delivery_bn'],
                    'weight' => $value['weight'],
                    'delivery_cost_actual' => $value['delivery_cost_actual'],
                    'logi_id' => $dlyInfo['logi_id'],
                    'logi_no' => $value['logi_no'],
                    'logi_name' => $dlyInfo['logi_name'],
                    'memo' => $dlyInfo['memo'],
                    'action' => 'updateDetail',
                );
                $res = kernel::single('wms_event_trigger_delivery')->doUpdate($wms_id, $data, true);

                if ($is_super) {
                    $branches = array('_ALL_');
                } else {
                    $branches = $this->getBranchByOp($opInfo['op_id']);
                }

                $process = false;

                # 校验
                if (($value['process_status'] &  2) != 2) {
                    $delivery = $dlyCheckLib->checkAllow($value['logi_no'], $msg);
                    if ($delivery === false) {
                        //$this->db->rollback();
                        continue;
                    }

                    $verify = $dlyProcessLib->verifyDelivery($value['delivery_id']);
                    if (!$verify) {
                        //$this->db->rollback();
                        continue;
                    }
                }

                # 发货
                $return_error = $dlyCheckLib->consignAllow('', $value['logi_no'], $value['weight']);
                if ($return_error) {
                    //$this->db->rollback();
                    continue;
                }

                $data = array(
    				'status'=> 1,
    				'weight'=> $value['weight'],
    				'delivery_cost_actual'=> $value['delivery_cost_actual'],
    				'delivery_time'=>time(),
                );
                $filter = array('delivery_id'=>$value['delivery_id'],'status'=> 0, 'type'=>1);
                $deliveryBillObj->update($data,$filter);

                $numdata = array('delivery_logi_number'=>1);
                $numfilter = array('delivery_id'=>$value['delivery_id']);
                $deliveryObj->update($numdata,$numfilter);

                $result = $dlyProcessLib->consignDelivery($value['delivery_id']);
                if (!$result) {
                    //$this->db->rollback();
                    continue;
                }

                //$this->db->commit($transaction);
            } else {
                //$this->db->rollback();
            }
        }

        return false;
    }

    public function getBranchByOp($op_id)
    {
        $bps = array();
        $oBops = app::get('ome')->model('branch_ops');
        $bops_list = $oBops->getList('branch_id', array('op_id' => $op_id), 0, -1);
        if ($bops_list){
            $bps = array_map('current',$bops_list);
        }

        return $bps;
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        $deliveryObj = &app::get('ome')->model("delivery");
        $wmsObj = &app::get('wms')->model("delivery");
        if(isset($filter['extend_delivery_id'])){
            $where .= ' OR delivery_id IN ('.implode(',', $filter['extend_delivery_id']).')';
            unset($filter['extend_delivery_id']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = &app::get('ome')->model("members");
            $rows = $memberObj->getList('member_id',array('uname|has'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }
            $where .= '  AND member_id IN ('.implode(',', $memberId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['order_bn'])){
            $orderObj = &app::get('ome')->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }

            $deliOrderObj = &app::get('ome')->model("delivery_order");
            $rows = $deliOrderObj->getList('delivery_id',array('order_id'=>$orderId));
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            //
            
            $_delivery_bn = $wmsObj->_getdelivery_bn($deliveryId);

            $where .= '  AND outer_delivery_bn IN (\''.implode('\',\'', $_delivery_bn).'\')';
            unset($filter['order_bn']);
        }

        if(isset($filter['no_logi_no']) && $filter['no_logi_no'] == true){
            $rows = $this->db->select("select delivery_id from sdb_wms_delivery_bill where logi_no = '' or logi_no is null");
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['no_logi_no']);
        }

        if(isset($filter['product_bn'])){
            $itemsObj = &$this->app->model("delivery_items");
            $rows = $itemsObj->getDeliveryIdByPbn($filter['product_bn']);
            
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            //$_delivery_bn = $this->_getdelivery_bn($deliveryId);
            $where .= '  AND delivery_id IN (\''.implode('\',\'', $deliveryId).'\')';
            unset($filter['product_bn'],$_delivery_bn);
        }
        if(isset($filter['product_barcode'])){
            $itemsObj = &$this->app->model("delivery_items");
            $rows = $itemsObj->getDeliveryIdByPbarcode($filter['product_barcode']);
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            //$_delivery_bn = $this->_getdelivery_bn($deliveryId);
            $where .= '  AND delivery_id IN (\''.implode('\',\'', $deliveryId).'\')';
            
            unset($filter['product_barcode'],$_delivery_bn);
        }
        if(isset($filter['logi_no_ext'])){
            $logObj = &$this->app->model("delivery_bill");
            $rows = $logObj->getlist('delivery_id',array('logi_no'=>$filter['logi_no_ext']));
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['logi_no_ext']);
        }
         if(isset($filter['addonSQL'])){
            $where .= ' AND '.$filter['addonSQL'];
            unset($filter['addonSQL']);
        }
        if(isset($filter['delivery_ident'])){
            $arr_delivery_ident = explode('_',$filter['delivery_ident']);
            $mdl_queue = app::get('ome')->model("print_queue");
            if(count($arr_delivery_ident) == 2){
                $ident_dly = array_pop($arr_delivery_ident);
                $ident = implode('-',$arr_delivery_ident);
                $queueItem = $mdl_queue->findQueueItem($ident,$ident_dly);
                if($queueItem){
                    $where .= '  AND delivery_id ='.$queueItem['delivery_id'].'';
                }else{
                    $where .= '  AND delivery_id IN (0)';
                }
            }else{
                $queue = $mdl_queue->findQueue($filter['delivery_ident'],'dly_bns');
                if($queue){
                    $where .= '  AND delivery_id IN ('.$queue['dly_bns'].')';
                }else{
                    $where .= '  AND delivery_id IN (0)';
                }
            }

            unset($filter['delivery_ident']);
        }
        if(isset($filter['ship_tel_mobile'])){
            $where .= ' AND (ship_tel=\''.$filter['ship_tel_mobile'].'\' or ship_mobile=\''.$filter['ship_tel_mobile'].'\')';
            unset($filter['ship_tel_mobile']);
        }
        if($filter['todo']==1){
            $where .= " AND ((print_status & 1) !=1 or (print_status & 2) !=2 or (print_status & 4) !=4)";
            unset($filter['todo']);
        }
        if($filter['todo']==2){
            $where .= " AND ((print_status & 1) !=1 or (print_status & 4) !=4)";
            unset($filter['todo']);
        }
        if($filter['todo']==3){
            $where .= " AND ((print_status & 2) !=2 or (print_status & 4) !=4)";
            unset($filter['todo']);
        }
        if($filter['todo']==4){
            $where .= " AND (print_status & 4) !=4";
            unset($filter['todo']);
        }

        if (isset($filter['print_finish'])) {
            $where_or = array();
            foreach((array)$filter['print_finish'] as $key=> $value){
                $or = "(deli_cfg='".$key."'";
                switch($value) {
                    case '1_1':
                        $or .= " AND (print_status & 1) =1 and (print_status & 2) =2 ";
                        break;
                    case '1_0':
                        $or .= " AND (print_status & 1) =1 ";
                        break;
                    case '0_1':
                        $or .= " AND (print_status & 2) =2 ";
                        break;
                    case '0_0':
                        break;
                }
                $or .= ')';
                $where_or[] = $or;
            }
            if($where_or){
                $where .= ' AND ('.implode(' OR ',$where_or).')';
            }
            unset($filter['print_finish']);
        }
        if (isset($filter['ext_branch_id'])) {
            if (isset($filter['branch_id'])){
                $filter['branch_id'] = array_intersect((array)$filter['branch_id'],(array)$filter['ext_branch_id']);
                $filter['branch_id'] = $filter['branch_id'] ? $filter['branch_id'] : 'false';
            }else{
                $filter['branch_id'] = $filter['ext_branch_id'];
            }
           
            unset($filter['ext_branch_id']);
        }
         #客服备注
        if(isset($filter['mark_text'])){
            $mark_text = $filter['mark_text'];
            $sql = "SELECT do.delivery_id FROM sdb_ome_delivery_order do JOIN sdb_ome_orders o ON do.order_id=o.order_id  and o.process_status='splited' and  o.mark_text like "."'%{$mark_text}%'";
            $_rows = $this->db->select($sql);
            if(!empty($_rows)){
                foreach($_rows as $_orders){
                    $_delivery[] = $_orders['delivery_id'];
                }
                $_delivery_bn = $wmsObj->_getdelivery_bn($_delivery);
                $where .= '  AND outer_delivery_bn IN (\''.implode('\',\'', $_delivery_bn).'\')';
                unset($filter['mark_text'],$_delivery,$_delivery_bn);
            }
        
        }
        #买家留言
        if(isset($filter['custom_mark'])){
            $custom_mark = $filter['custom_mark'];
            $sql = "SELECT do.delivery_id FROM sdb_ome_delivery_order do JOIN sdb_ome_orders o ON do.order_id=o.order_id  and o.process_status='splited' and  o.custom_mark like "."'%{$custom_mark}%'";
            $_rows = $this->db->select($sql);
            if(!empty($_rows)){
                foreach($_rows as $_orders){
                    $_delivery[] = $_orders['delivery_id'];
                }
                $_delivery_bn = $wmsObj->_getdelivery_bn($_delivery);

                $where .= '  AND outer_delivery_bn IN (\''.implode('\',\'', $_delivery_bn).'\')';
                unset($filter['custom_mark'],$_delivery,$_delivery_bn);
            }
        
        } 
        if (isset($filter['stock_status'])) {
            if ($filter['stock_status'] == 'true') {
                $where .= " AND (print_status & 1) =1";
            }else{
                $where .= " AND (print_status & 1) !=1";
            }
            unset($filter['stock_status']);
        }
        if (isset($filter['deliv_status'])) {
            if ($filter['deliv_status']=='true') {
                $where .= " AND (print_status & 2) =2";
            }else{
                $where .= " AND (print_status & 2) !=2";
            }
            unset($filter['deliv_status']);    
        }
        if (isset($filter['expre_status'])) {
            if ($filter['expre_status']=='true') {
                $where .= " AND (print_status & 4) =4";
            }else{
                $where .= " AND (print_status & 4) !=4";
            }
            unset($filter['expre_status']);    
        }

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'delivery_bn'=>app::get('base')->_('发货单号'),
            'order_bn'=>app::get('base')->_('订单号'),
            'member_uname'=>app::get('base')->_('用户名'),
            'ship_name'=>app::get('base')->_('收货人'),
            'ship_tel_mobile'=>app::get('base')->_('联系电话'),
            'product_bn'=>app::get('base')->_('货号'),
            'product_barcode'=>app::get('base')->_('条形码'),
            'delivery_ident'=>app::get('base')->_('打印批次号'),
            'outer_delivery_bn'=>app::get('base')->_('外部发货单号'),
            'logi_no_ext'=>app::get('base')->_('物流单号'),
        );

        return array_merge($childOptions,$parentOptions);
    }
}
