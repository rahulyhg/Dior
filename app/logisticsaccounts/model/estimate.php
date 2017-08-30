<?php
class logisticsaccounts_mdl_estimate extends dbeav_model{
     var $defaultOrder = array('eid','DESC');
     function io_title( $filter, $ioType='csv' ){
      
            switch( $filter ){

                case 'estimate':
                    $this->oSchema['csv'][$filter] = array(
                       
                        '*:状态' => 'status',
                         '*:店铺' => 'shop_name',
                        '*:仓库' => 'branch_name',
                         '*:发货单号' => 'delivery_bn',
                        '*:发货时间' => 'delivery_time',
                        '*:物流公司' => 'logi_name',
                        '*:收货地区' => 'ship_area',
                        '*:收货地址' => 'ship_addr',
                         '*:收货人' => 'ship_name',
                         '*:物流单号' => 'logi_no',
                          '*:包裹重量' => 'weight',
                    
                         '*:预计物流费用' => 'delivery_cost_expect',
                    
                 );
                    break;


            }
            $this->ioTitle[$ioType][$filter] = array_keys( $this->oSchema[$ioType][$filter] );
            return $this->ioTitle[$ioType][$filter];
         }



      function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        set_time_limit(0);
        @ini_set('memory_limit','100M');
        if( !$data['title']['estimate']){
            $title = array();
            foreach( $this->io_title('estimate') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['estimate'] = '"'.implode('","',$title).'"';
        }
        $list=$this->getList('status,shop_name,branch_name,delivery_bn,delivery_time,logi_name,ship_area,ship_addr,ship_name,logi_no,weight,delivery_cost_expect',$filter,0,-1);

        if(!$list)return false;
        foreach( $list as $aFilter ){
            $_ship_addr = explode(':', $aFilter['ship_area']);
            $aFilter['ship_area'] = $_ship_addr[1];

            if($aFilter['status']=='0')
            {
                $aFilter['status'] = '未对账';
            }else if($aFilter['status']=='1'){
                $aFilter['status'] = '已对账';
            }else if($aFilter['status']=='2'){
                $aFilter['status'] = '已记账';
            }else if($aFilter['status']=='3'){
                $aFilter['status'] = '已审核';
            }else if($aFilter['status']=='4'){
                $aFilter['status'] = '已关账';
            }


            $aFilter['delivery_time'] = date('Y-m-d',$aFilter['delivery_time']);


            $aFilter['logi_no'] = $aFilter['logi_no']."\t";
            $aFilter['delivery_bn'] = $aFilter['delivery_bn']."\t";
            foreach( $this->oSchema['csv']['estimate'] as $k => $v ){
                $iostockRow[$k] = $this->charset->utf2local($aFilter[$v]);
            }
            $data['content']['estimate'][] = '"'.implode('","',$iostockRow).'"';
        }

    }

     function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){
            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        }

        echo implode("\n",$output);
    }


     function utf8togbk($s)
    {
        return iconv("UTF-8", "GBK//TRANSLIT", $s);
    }
    public function searchOptions(){
        $ext_columns = array(
            'delivery_bn'=>$this->app->_('发货单号'),
              'logi_no'=>$this->app->_('物流单号'),
                'order_bn'=>$this->app->_('订单号'),
                'logi_name'=>$this->app->_('物流公司'),
            'task_bn'=>$this->app->_('任务名称'),
        );
        return $ext_columns;
    }



    public function exportName(&$data){

        $data['name'] = '物流账单'.date('Ymd');
    }


    public function update_estimate_status($data){
       
        $estimate_sql='';
        if($data['confirm']=='1'){//记账
            $actual_name=kernel::single('desktop_user')->get_name();
            $estimate_sql[]="actual_name='$actual_name'";
            $estimate_sql[]="actual_time=".time();
            $estimate_sql[]="status='2'";

        }else if($data['confirm']=='2'){//审核
            $confirm_name=kernel::single('desktop_user')->get_name();
            $estimate_sql[]="confirm_name='$confirm_name'";
            $estimate_sql[]="status='3'";

        }
        if($data['actual_amount']){
                $estimate_sql[]='actual_amount='.$data['actual_amount'];
        }
       

        if($estimate_sql){
            $estimate_sql=implode(',',$estimate_sql);
            $sql = 'UPDATE sdb_logisticsaccounts_estimate SET '.$estimate_sql.' WHERE aid='.$data['aid'].' AND task_id='.$data['task_id'];

            $this->db->exec($sql);
        }

    }

    public function _filter($filter, $tableAlias=null, $baseWhere=null) {
       
        $is_super = kernel::single('desktop_user')->is_super();
          if (!$is_super) {
                $op_id = kernel::single('desktop_user')->get_id();
                if ($op_id) {
                    if (!$filter['op_id']) {
                        $filter['op_id'] = $op_id;
                    }else{
                        if($filter['op_id']=='all'){
                            unset($filter['op_id']);
                        }
                    }
                }
            }else{
                if($filter['op_id']=='all'){
                     unset($filter['op_id']);
                 }
            }

        return parent::_filter($filter, $tableAlias, $baseWhere) . $where;
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
        $type = 'finance';
        if ($logParams['app'] == 'logisticsaccounts' && $logParams['ctl'] == 'admin_estimate') {
            $type .= '_logisticsToAccount_logisticsBill';
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
        $type = 'finance';
        $type .= '_import';
        return $type;
    }
}

?>