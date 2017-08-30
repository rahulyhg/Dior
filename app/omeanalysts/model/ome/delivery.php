<?php
class omeanalysts_mdl_ome_delivery extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '快递费结算表';

    public function table_name($real=false){
        $table_name = 'ome_delivery';
        if($real){
            return kernel::database()->prefix.$table_name;
        }else{
            return $table_name;
        }
    }

    public function get_delivery($filter=null){

        $nums = 0;
        $cost = 0;
        //包裹数量、快递成本
        $sql = 'SELECT sum(logi_number) as nums,sum(delivery_cost_actual) as sum_cost_actual FROM sdb_ome_delivery where parent_id=0 and is_cod=\'false\' and process=\'true\' and '.$this->_filter($filter);
        $rows = $this->db->select($sql); 
        $cost += $rows[0]['sum_cost_actual']; 
        $nums += $rows[0]['nums'];

        $sql = 'SELECT delivery_id FROM sdb_ome_delivery where logi_number>1 and parent_id=0 and is_cod=\'false\' and process=\'true\' and '.$this->_filter($filter);
        $rows = $this->db->select($sql);  
        
        $dlyBillObj = &app::get('ome')->model("delivery_bill");

        foreach($rows as $k=>$v){
            $deliveryids[] = $v['delivery_id'];          
        }
        
        unset($rows);

        if($deliveryids){

            $bills = $this->db->select('select sum(delivery_cost_actual) as sum_cost_actual from sdb_ome_delivery_bill where status=\'1\' and delivery_id in('.implode(',',$deliveryids).') limit 1');
            
            $cost += $bills[0]['sum_cost_actual'];
        }

        //买家支付运费
        //$sql_costfreight = 'SELECT sum(sdb_ome_orders.cost_freight) as sum_freight FROM sdb_ome_orders,sdb_ome_delivery,sdb_ome_delivery_order where sdb_ome_delivery.delivery_id=sdb_ome_delivery_order.delivery_id and sdb_ome_orders.order_id=sdb_ome_delivery_order.order_id and sdb_ome_delivery.parent_id=0 and sdb_ome_delivery.is_cod=\'false\' and sdb_ome_delivery.process=\'true\' and '.$this->_filter($filter);
        $sql_costfreight = 'SELECT sum(sdb_ome_orders.cost_freight) as sum_freight FROM sdb_ome_delivery LEFT JOIN sdb_ome_delivery_order ON sdb_ome_delivery.delivery_id=sdb_ome_delivery_order.delivery_id LEFT JOIN sdb_ome_orders ON sdb_ome_orders.order_id=sdb_ome_delivery_order.order_id WHERE sdb_ome_delivery.parent_id=0 and sdb_ome_delivery.is_cod=\'false\' and sdb_ome_delivery.process=\'true\' and '.$this->_filter($filter);

        $costfreights = $this->db->select($sql_costfreight);

        return array('num'=>$nums,'cost'=>$cost,'costfreight'=>$costfreights[0]['sum_freight']);
    }

    public function count($filter=null){
        $sql = 'SELECT count(*) as _count FROM sdb_ome_delivery WHERE parent_id=0 and '.
            'is_cod=\'false\' and process=\'true\' and '.$this->_filter($filter);

        $row = $this->db->select($sql);
        return intval($row[0]['_count']);
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(isset($filter['logi_no'])){
            $obj_delivery_bill = $deliOrderObj = &app::get('ome')->model("delivery_bill");
            #获取子表物流单号
            $delivery_id = $obj_delivery_bill->dump(array('logi_no'=>$filter['logi_no']),'delivery_id');
            if(!empty($delivery_id['delivery_id'])){
                unset($filter['logi_no']);
                $filter['delivery_id'] = $delivery_id['delivery_id'];
            }
        }
        $sql = 'SELECT delivery_bn,logi_name,logi_no,delivery_time,weight,ship_area,ship_name,delivery_cost_actual,cost_protect,(delivery_cost_actual+cost_protect) as balance,shop_id,delivery_id,branch_id 
            FROM sdb_ome_delivery WHERE 
            parent_id=0 AND is_cod=\'false\' and process=\'true\' and '.$this->_filter($filter);

        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);

        $rows = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($rows, $cols);
        if ($rows){
            $deliOrderObj = &app::get('ome')->model("delivery_order");
            $orderObj = &app::get('ome')->model("orders");
            $dlyBillObj = &app::get('ome')->model("delivery_bill");

            $deliveryids = array();
            foreach ($rows as $key=>$val){
                $deliveryids[] = $val['delivery_id'];
                $tmp_area = array();
                if ( preg_match("/:(.*):/", $val['ship_area'], $tmp_area) ){
                    $rows[$key]['ship_area'] = $tmp_area[1];
                }

                $rows[$key]['logi_no'] .= "\t";

                // begin 获取订单相关信息
                $orders = $deliOrderObj->getList('order_id',array('delivery_id'=>$val['delivery_id']));
                $orderId[] = 0;
                foreach($orders as $vv){
                    $orderId[] = $vv['order_id'];
                }
                $orderBn = $orderObj->getList('cost_freight,order_bn,is_cod,ship_tel,ship_mobile,ship_addr,total_amount',array('order_id'=>$orderId));
                $cost_freight =0;
                foreach($orderBn as $vv){
                    
                    if ($rows[$key]['order_bn']) {
                        $rows[$key]['order_bn'] .= ','.$vv['order_bn'];
                        $cost_freight+=$vv['cost_freight'];
                    }else{
                        $rows[$key]['order_bn'] = $vv['order_bn']."\t";
                        $cost_freight = $vv['cost_freight'];
                    }
                    $rows[$key]['is_cod'] = $vv['is_cod'] == 'true' ? '是' :'否';
                    $rows[$key]['ship_tel'] = $vv['ship_tel'];
                    $rows[$key]['ship_mobile'] = $vv['ship_mobile']."\t";
                    $rows[$key]['ship_addr'] = $vv['ship_addr'];
                    $rows[$key]['cost_freight'] = $cost_freight;
                    $rows[$key]['total_amount'] += $vv['total_amount'];
                }
                unset($order_bn,$orderId);
                // end 获取订单相关信息
            }

            if($deliveryids){
                $dlyBillList = $rowAll = $billInfo = array();
                $dlyBillInfo = $dlyBillObj->getList('delivery_id,logi_no,weight,delivery_time,delivery_cost_actual',array('delivery_id|in'=>$deliveryids,'status'=>1));
                foreach($dlyBillInfo as $k=>$v){
                    $dlyBillList[$v['delivery_id']][] = $v;
                }
                unset($dlyBillInfo);
                foreach($rows as $rk=>$rv){
                    $rowAll[] = $rv;
                    if(count($dlyBillList[$rv['delivery_id']])>0){
                        foreach($dlyBillList[$rv['delivery_id']] as $dk=>$dv){
                            $rv['logi_no'] = $dv['logi_no']."\t";
                            $rv['weight'] = $dv['weight'];
                            $rv['delivery_time'] = $dv['delivery_time'];
                            $rv['delivery_cost_actual'] = $dv['delivery_cost_actual'];
                            $rv['balance'] = $dv['delivery_cost_actual'];
                            $rowAll[] = $rv;
                        }
                    }
                }
            }
        }
        unset($rows,$dlyBillList);
        return $rowAll;
    }
    
    public function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'order_bn'=>app::get('base')->_('订单号'),
        );
        return $Options = array_merge($parentOptions,$childOptions);
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' sdb_ome_delivery.delivery_time >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' sdb_ome_delivery.delivery_time <'.(strtotime($filter['time_to'])+86400);
        }
        
        if(isset($filter['own_branches']) && $filter['own_branches']){
            $where[]= ' sdb_ome_delivery.branch_id in ('.implode(',',$filter['own_branches']).')';
        }
        unset($filter['own_branches']);

        if(isset($filter['branch_id']) && $filter['branch_id']){
            $where[] = ' sdb_ome_delivery.branch_id =\''.addslashes($filter['branch_id']).'\'';
            
        }
        unset($filter['branch_id']);
        if(isset($filter['logi_id']) && $filter['logi_id']){
            $where[] = ' sdb_ome_delivery.logi_id ='.addslashes($filter['logi_id']);
            
        }
        unset($filter['logi_id']);
        if(isset($filter['shop_id']) && $filter['shop_id']){
            $where[] = ' sdb_ome_delivery.shop_id =\''.addslashes($filter['shop_id']).'\'';
        }
        unset($filter['shop_id']);
        if(isset($filter['order_bn']) && $filter['order_bn']){
            $orderObj = &app::get('ome')->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn|has'=>$filter['order_bn']));
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

            $where[] = ' delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['order_bn']);
        }
        if(isset($filter['delivery_id'])){
            $where[] = ' sdb_ome_delivery.delivery_id ='.$filter['delivery_id'];
            unset($filter['delivery_id']);
        }
        return parent::_filter($filter,'sdb_ome_delivery',$baseWhere)." AND ".implode($where,' AND ');
    }

    public function get_schema(){
        $schema = array (
            'columns' => array (
                'delivery_bn' => array (
                    'type' => 'varchar(32)',
                    'required' => true,
                    'label' => '发货单号',
                    'comment' => '配送流水号',
                    'editable' => false,
                    'width' =>140,
                    'searchtype' => 'has',
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'is_title' => true,
                ),
                'logi_id' => array (
                    'type' => 'table:dly_corp@ome',
                    'comment' => '物流公司ID',
                    'editable' => false,
                    'label' => '物流公司',
                    'deny_export' => true,
                ),
                'logi_name' => array (
                    'type' => 'varchar(100)',
                    'label' => '物流公司',
                    'comment' => '物流公司名称',
                    'editable' => false,
                    'width' =>75,
                ),
                'logi_no' => array (
                    'type' => 'varchar(50)',
                    'label' => '快递单号',
                    'comment' => '物流单号',
                    'editable' => false,
                    'searchtype' => 'tequal',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'width' =>110,
                ),
                'delivery_time' => array (
                    'type' => 'time',
                    'label' => '发货时间',
                    'comment' => '单据生成时间',
                    'width' =>130,
                    'editable' => false,
                ),
                'weight' => array (
                    'type' => 'number',
                    'editable' => false,
                    'label' => '包裹重量',
                    'comment' => '包裹重量',
                    'width' =>85,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'ship_area' => array (
                    'type' => 'region',
                    'label' => '收货地区',
                    'comment' => '收货人地区',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'width' =>130,
                ),
                'ship_name' => array (
                    'type' => 'varchar(50)',
                    'label' => '收货人',
                    'comment' => '收货人姓名',
                    'editable' => false,
                    'searchtype' => 'tequal',
                    'width' =>75,
                ),
                'delivery_cost_actual' => array (
                    'type' => 'money',
                    'editable' => false,
                    'label' => '配送费用',
                    'comment' => '物流费用(包裹重量计算的费用)',
                    'width' =>85,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'cost_protect' => array (
                    'type' => 'money',
                    'default' => '0',
                    'label' => '保价费用',
                    'width' =>85,
                    'required' => false,
                    'editable' => false,
                ),
                'balance' => array (
                    'type' => 'money',
                    'editable' => false,
                    'label' => '结算金额',
                    'comment' => '结算金额',
                    'width' =>110,
                ),
                'shop_id' => array (
                    'type' => 'table:shop@ome',
                    'label' => '来源店铺',
                    'width' => 75,
                    'editable' => false,
                ),
                'branch_id' => array (
                    'type' => 'table:branch@ome',
                    'editable' => false,
                    'label' => '发货仓库',
                    'width' => 110,
                ),
                'order_bn' => array (
                    'type' => 'varchar(32)',
                    'required' => true,
                    'label' => '订单号',
                    'comment' => '订单号',
                    'editable' => false,
                    'width' =>200,
                    'searchtype' => 'has',
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'is_title' => true,
                ),
                'is_cod' => array (
                    'type' => 'bool',
                    'required' => true,
                    'editable' => false,
                    'label' => '货到付款',
                    'in_list' => true,
                    'default_in_list' => true,
                    'width' => 60,
                    'is_title' => true,
                ),
                'ship_tel' => array (
                    'type' => 'varchar(30)',
                    'label' => '收货人电话',
                    'width' => 75,
                    'editable' => false,
                    'in_list' => true,
                    'is_title' => true,
                ),
                'ship_mobile' => array (
                    'label' => '收货人手机',
                    'hidden' => true,
                    'type' => 'varchar(50)',
                    'editable' => false,
                    'width' => 85,
                    'in_list' => true,
                    'is_title' => true,
                ),
                'ship_addr' => array (
                    'type' => 'varchar(100)',
                    'label' => '收货地址',
                    'width' => 180,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'in_list' => true,
                    'is_title' => true,
                ),
                'cost_freight' =>
                array (
                  'type' => 'money',
                  'default' => '0',
                  'required' => true,
                  'label' => '收取运费',
                  'width' => 70,
                  'editable' => false,
                  'filtertype' => 'number',
                  'sdfpath' => 'shipping/cost_shipping',
                  'default_in_list' => true,
                  'in_list' => true,
                  'orderby' => false,
                ),                
                'total_amount' => array (
                    'type' => 'money',
                    'default' => '0',
                    'label' => '订单总额',
                    'width' => 70,
                    'editable' => false,
                    //'filtertype' => 'number',
                    //'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                    'is_title' => true,
                ),

            ),
            'idColumn' => 'delivery_bn',
            'in_list' => array (
                0 => 'delivery_bn',
                1 => 'logi_name',
                2 => 'logi_no',
                3 => 'delivery_time',
                4 => 'weight',
                5 => 'ship_area',
                6 => 'ship_name',
                7 => 'delivery_cost_actual',
                8 => 'cost_protect',
                9 => 'balance',
                10 => 'shop_id',
                11 => 'order_bn',
                12 => 'is_cod',
                13 => 'ship_tel',
                14 => 'ship_mobile',
                15 => 'ship_addr',
                16 => 'total_amount',
                17 => 'branch_id',
                18 => 'cost_freight',
            ),
            'default_in_list' => array (
                0 => 'delivery_bn',
                1 => 'logi_name',
                2 => 'logi_no',
                3 => 'delivery_time',
                4 => 'weight',
                5 => 'ship_area',
                6 => 'ship_name',
                7 => 'delivery_cost_actual',
                8 => 'cost_protect',
                9 => 'balance',
                10 => 'shop_id',
                11 => 'order_bn',
                12 => 'is_cod',
                13 => 'ship_tel',
                14 => 'ship_mobile',
                15 => 'ship_addr',
                16 => 'total_amount',
                17 => 'branch_id',
                18 => 'cost_freight',
            ),
        );
        return $schema;
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
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_purchaseReport_expressAnalysis';
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
        $type = 'report';
        if ($logParams['app'] == 'omeanalysts' && $logParams['ctl'] == 'ome_analysis') {
            $type .= '_purchaseReport_expressAnalysis';
        }
        $type .= '_import';
        return $type;
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){

        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        if(!$omedelivery = $this->getList('*',$filter,$start,$end)) return false;
        
        //error_log(var_export($new_rows,true),3,'/www/newr.txt');

        $new_omedelivery = array();
        foreach ($omedelivery as $k => $aFilter) {
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($aFilter[$col])){
                    $new_omedelivery[$k][$col] = $aFilter[$col];
                }
            }
        }

        $new_rows = $this->formatCsvExport($new_omedelivery);
        foreach ($new_rows as $k => $aFilter) {
            $exptmp_data = array();
            foreach ($aFilter as $col => $val) {
                $aFilter[$col] = mb_convert_encoding($aFilter[$col], 'GBK', 'UTF-8');
                $exptmp_data[] = $aFilter[$col];
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }
}
