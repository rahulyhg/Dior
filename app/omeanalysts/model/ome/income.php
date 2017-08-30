<?php
class omeanalysts_mdl_ome_income extends dbeav_model{

    var $has_export_cnf = true;

    var $export_name = '订单收入统计';

    public function get_payMoney($filter=null){
        if($filter['bill_type'] == 'refund') return 0;
        //收款额
        $sql = 'SELECT sum(money) as amount FROM sdb_ome_payments WHERE '.$this->_pFilter($filter);
        $row = $this->db->select($sql);
        return $row[0]['amount'];
    }

    public function get_refundMoney($filter=null){
        if($filter['bill_type'] == 'payment') return 0;
        //退款额
        $sql = 'SELECT sum(money) as amount FROM sdb_ome_refunds where '.$this->_rFilter($filter);
        $row = $this->db->select($sql);
        return $row[0]['amount'];
    }

    public function count($filter=null){
        if( $filter['bill_type'] == 'payment'){
            $sql = 'SELECT count(*) as _count FROM sdb_ome_payments WHERE '.$this->_pFilter($filter);
        }elseif( $filter['bill_type'] == 'refund'){
            $sql = 'SELECT count(*) as _count FROM sdb_ome_refunds where '.$this->_rFilter($filter);
        }else{
            $sql = 'SELECT count(*) as _count FROM (SELECT payment_bn as bill_id FROM sdb_ome_payments WHERE '.$this->_pFilter($filter).
                ' UNION ALL '.
                'SELECT refund_bn as bill_id FROM sdb_ome_refunds where '.$this->_rFilter($filter).') as tb';
        }

        $row = $this->db->select($sql);
        return intval($row[0]['_count']);
    }

    public function searchOptions(){
        $columns = array();
        foreach($this->_columns() as $k=>$v){
            if(isset($v['searchtype']) && $v['searchtype']){
                $columns[$k] = $v['label'];
            }
        }

        $ext_columns = array(
            'payment_bn'=>$this->app->_('支付单号'),
            'refund_bn'=>$this->app->_('退款单号'),
        );

        return array_merge($columns, $ext_columns);
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        if($filter['bill_type'] == 'payment'){
            $sql = 'SELECT order_id,1 as bill_type,payment_bn as bill_id,t_end as bill_time,money as bill_amount,paymethod,shop_id FROM sdb_ome_payments WHERE '.$this->_pFilter($filter);
        }elseif($filter['bill_type'] == 'refund'){
            $sql = 'SELECT order_id,2 as bill_type,refund_bn as bill_id,t_ready as bill_time,money as bill_amount,paymethod,shop_id FROM sdb_ome_refunds where '.$this->_rFilter($filter);
        }else{
            $sql = 'SELECT order_id,1 as bill_type,payment_bn as bill_id,t_end as bill_time,money as bill_amount,paymethod,shop_id FROM sdb_ome_payments WHERE '.$this->_pFilter($filter).
                ' UNION ALL '.
                'SELECT order_id,2 as bill_type,refund_bn as bill_id,t_ready as bill_time,money as bill_amount,paymethod,shop_id FROM sdb_ome_refunds where '.$this->_rFilter($filter);
        }

        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);

        $rows = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($rows, $cols);
        $obj_order = &app::get('ome')->model('orders');
        foreach($rows as $key=>$v){
            $_rs = $obj_order->dump($v['order_id'],'order_bn');
           $rows[$key]['order_id'] = $_rs['order_bn'];
           $rows[$key]['bill_id']= $v['bill_id']."\t";
        }
        return $rows;
    }

    public function _pFilter($filter,$tableAlias=null,$baseWhere=null){
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' t_end >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' t_end <'.(strtotime($filter['time_to'])+86400);
        }
        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' shop_id =\''.addslashes($filter['type_id']).'\'';
        }
        if(isset($filter['payment_bn']) && $filter['payment_bn']){
            $where[] = ' payment_bn LIKE \''.addslashes($filter['payment_bn']).'%\'';
        }
        if(isset($filter['refund_bn']) && $filter['refund_bn']){
            $where[] = ' 1=2 ';
        }

        if(isset($filter['paymethod']) && $filter['paymethod']){
            $where[] = ' paymethod = "'.addslashes($filter['paymethod']).'"';
        }

        if(isset($filter['order_id']) && $filter['order_id']){
            $Oorders = app::get('ome')->model('orders');
            $orders = $this->db->select('select p.order_id from sdb_ome_orders o left join sdb_ome_payments p on o.order_id = p.order_id where o.order_bn like \''.addslashes($filter['order_id']).'%\' and p.order_id is not null');
            if($orders){
                $orderids = array();
                foreach ($orders as $v) {
                   $orderids[] = $v['order_id'];
                }
                $where[] = ' order_id in ('.implode(',', $orderids).')';
            }else{
                $where[] = ' 1=2 ';
            }


        }

        return implode($where,' AND ');
    }

    public function _rFilter($filter,$tableAlias=null,$baseWhere=null){
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' t_ready >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' t_ready <'.(strtotime($filter['time_to'])+86400);
        }
        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' shop_id =\''.addslashes($filter['type_id']).'\'';
        }
        if(isset($filter['refund_bn']) && $filter['refund_bn']){
            $where[] = ' refund_bn LIKE \''.addslashes($filter['refund_bn']).'%\'';
        }
        if(isset($filter['payment_bn']) && $filter['payment_bn']){
            $where[] = ' 1=2 ';
        }

        if(isset($filter['paymethod']) && $filter['paymethod']){
            $where[] = ' paymethod = "'.addslashes($filter['paymethod']).'"';
        }

        if(isset($filter['order_id']) && $filter['order_id']){
            $ssql = 'select r.order_id from sdb_ome_orders o left join sdb_ome_refunds r on o.order_id = r.order_id where o.order_bn like \''.addslashes($filter['order_id']).'%\' and r.order_id is not null';
            $orders = $this->db->select($ssql);
            if($orders){
                $orderids = array();
                foreach ($orders as $v) {
                   $orderids[] = $v['order_id'];
                }

                $where[] = ' order_id in ('.implode(',', $orderids).')';
            }else{
                $where[] = ' 1=2 ';
            }


        }

        return implode($where,' AND ');
    }

    public function get_billtype(){
        return array(
            'all'=>'全部',
            'payment'=>'付款单',
            'refund'=>'退款单'
        );
    }

    public function get_schema(){
        $schema = array (
            'columns' => array (
                'order_id' => array (
                    'type' => 'table:orders@ome',
                    'label' => '订单号',
                    'width' => 135,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'searchtype' => true,
                    'default_in_list' => true,
                ),
                'bill_type' => array (
                    'type' =>
                    array (
                        1 => '付款单',
                        2 => '退款单',
                    ),
                    'required' => true,
                    'label' => app::get('b2c')->_( '单据类型'),
                    'width' => 75,
                    'editable' => false,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'in_list' => true,
                ),
                'bill_id' => array (
                    'type' => 'varchar(20)',
                    'pkey' => true,
                    'required' => true,
                    'label' =>  app::get('b2c')->_('支付/退款单号'),
                    'width' => 135,
                    'editable' => false,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'bill_time' => array (
                    'type' => 'time',
                    'label' => app::get('b2c')->_('付款时间'),
                    'width' => 130,
                    'editable' => false,
                    'in_list' => true,
                ),
                'bill_amount' => array (
                    'type' => 'money',
                    'default' => '0',
                    'required' => true,
                    'label' =>app::get('b2c')->_('金额'),
                    'width' => 75,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'paymethod' => array (
                    'type' => 'varchar(100)',
                    'label' => '支付方式',
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                ),
                'shop_id' => array (
                    'type' => 'table:shop@ome',
                    'label' => '店铺',
                    'width' => 120,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            ),
            'idColumn' => 'order_id',
            'in_list' => array (
                0 => 'order_id',
                1 => 'bill_type',
                2 => 'bill_id',
                3 => 'bill_time',
                4 => 'bill_amount',
                5 => 'paymethod',
                6 => 'shop_id',
            ),
            'default_in_list' => array (
                0 => 'order_id',
                1 => 'bill_type',
                2 => 'bill_id',
                3 => 'bill_time',
                4 => 'bill_amount',
                5 => 'paymethod',
                6 => 'shop_id',
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
            $type .= '_purchaseReport_orderIncomeAnalysis';
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
            $type .= '_purchaseReport_orderIncomeAnalysis';
        }
        $type .= '_import';
        return $type;
    }
    public function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        @ini_set('memory_limit','1024M');
        if( !$data['title'] ){
            $title = array();
            foreach( $this->io_title() as $k => $v ){
                $title[] = $v;
            }
            $data['title']['income'] = mb_convert_encoding('"'.implode('","',$title).'"', 'GBK', 'UTF-8');
        }
        $oShop = &app::get('ome')->model('shop');
        $rs = $oShop->getList('shop_id,name');
        foreach($rs as $v) {
            $shops[$v['shop_id']] = $v;
        }
        $limit = 100;
        if(!$goodssale = $this->getList('*',$filter,$offset*$limit,$limit)) return false;
        foreach ($goodssale as $aFilter) {
            $goodsaleRow['*:订单号'] = "=\"\"".$aFilter['order_id']."\"\"";
            if($aFilter['bill_type'] == '1'){
                $aFilter['bill_type'] = '付款单';
            }else{
                $aFilter['bill_type'] = '退款单';
            }
            $goodsaleRow['*:单据类型'] = $aFilter['bill_type'];
            $goodsaleRow['*:支付/退款单号'] = $aFilter['bill_id'];
            $goodsaleRow['*:付款时间'] = date('Y-m-d H:i:s',$aFilter['bill_time']);
            $goodsaleRow['*:金额'] = $aFilter['bill_amount'];
            $goodsaleRow['*:支付方式'] = $aFilter['paymethod'];
            $goodsaleRow['*:店铺名称'] = $shops[$aFilter['shop_id']]['name'];
            $data['content']['income'][] = mb_convert_encoding('"'.implode('","',$goodsaleRow).'"', 'GBK', 'UTF-8');
        }
        return true;
    }
    public function io_title($filter=null,$ioType='csv'){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['main'] = array(
                '*:订单号'=>'order_id',
                '*:单据类型'=>'bill_type',
                '*:支付/退款单号'=>'bill_id',
                '*:付款时间'=>'bill_time',
                '*:金额'=>'bill_amount',
                '*:支付方式'=>'paymethod',
                '*:店铺'=>'shop_id'
                );
        }
        $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType]['main'] );
        return $this->ioTitle[$ioType][$filter];
    }
    function export_csv($data,$exportType = 1 ){
        $output = array();
        $output[] = $data['title']['income']."\n".implode("\n",(array)$data['content']['income']);
        echo implode("\n",$output);
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){
        
        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        $oShop = &app::get('ome')->model('shop');
        $rs = $oShop->getList('shop_id,name');
        foreach($rs as $v) {
            $shops[$v['shop_id']] = $v;
        }

        if(!$income = $this->getList('*',$filter,$start,$end)) return false;
        foreach ($income as $aFilter) {
            $incomeRow['order_id'] = $aFilter['order_id'];
            if($aFilter['bill_type'] == '1'){
                $aFilter['bill_type'] = '付款单';
            }else{
                $aFilter['bill_type'] = '退款单';
            }
            $incomeRow['bill_type'] = $aFilter['bill_type'];
            $incomeRow['bill_id'] = $aFilter['bill_id'];
            $incomeRow['bill_time'] = date('Y-m-d H:i:s',$aFilter['bill_time']);
            $incomeRow['bill_amount'] = $aFilter['bill_amount'];
            $incomeRow['paymethod'] = $aFilter['paymethod'] ? $aFilter['paymethod'] : '-';
            $incomeRow['shop_id'] = $shops[$aFilter['shop_id']]['name'];
            
            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($incomeRow[$col])){
                    $incomeRow[$col] = mb_convert_encoding($incomeRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $incomeRow[$col];
                }
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }
}
