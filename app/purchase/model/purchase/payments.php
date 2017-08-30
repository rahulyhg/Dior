<?php

class purchase_mdl_purchase_payments extends dbeav_model{

    function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = "1";
        if(isset($filter['po_bn'])){
            $poObj = &$this->app->model("po");
            $rows = $poObj->getList('po_id',array('po_bn|head'=>$filter['po_bn']));
            $poId[] = 0;
            foreach($rows as $row){
                $poId[] = $row['po_id'];
            }
            $where .= '  AND po_id IN ('.implode(',', $poId).')';
            unset($filter['po_bn']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".$where;
    }

   /*
    * 付款单编号
    */
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $payment_bn = date('YmdH').'20'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT payment_bn from sdb_purchase_purchase_payments where payment_bn =\''.$payment_bn.'\'');
        }while($row);
        return $payment_bn;
    }

    /*
     * 付款单结算
     */
    public function statementDo($data,$paymentDetail){
        $payd = $paymentDetail['paid'];#本次操作以前，已经支付的金额
        if(!$payd){
            $payd = 0;
        }

        //////////-------------------------付款单处理-------------------------------------
        //付款单计算数据值
        $paid = $payd + $data['paid'];#结算金额=以前的结算金额+本次结算金额
        $balance = $paymentDetail['payable'] - $payd - $data['paid'];#结算余额=应付金额-已付金额-本次结算金额
        $payable = $paymentDetail['payable'];#应付金额;

        //结算单计算数据值
        $pay_add = $paymentDetail['payable'];#本期增加应付
        $difference = $payable - $payd - $data['paid'];#差额 = 应付金额  - 已经支付 -本次结算金额
        $localpaid = $data['paid'];#本期已付
        $data['payment'] = (int)$data['payment'];
        $statement_status = 2;#结算状态
        $statement_time = time();#结算时间
        $payment_id = $data['payment_id'];#付款单ID
        $object_type = 2;#业务类型:现购
        $op_name = kernel::single('desktop_user')->get_name();
        $newmemo =  htmlspecialchars($data['memo']);
        $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
        if ($balance=='0') {
            $balance = '0.00';
            $statement = 3;
        }elseif($balance > 0){
            $statement_status = 4;
            $statement = 2;
        }
        $cureeBuy_array = array(
              'payment_id' => $payment_id,
              'paid' => abs($paid),
              'balance' => $balance,
              'memo' => serialize($memo),
              'payment' => $data['payment'],
              'operator' => $data['operator'],
              'op_id' => kernel::single('desktop_user')->get_id(),
              'statement_status' => $statement_status,
              'statement_time' => $statement_time,
              'logi_no' => $data['logi_no'],
              'tax_no' => $data['tax_no'],
              'bank_no' => $data['bank_no']
        );
        $oPayments = &$this->app->model("purchase_payments",$_GET['app']);
        $save_r1 = $oPayments->save($cureeBuy_array);

        //更新采购单的statement状态
        if ($paymentDetail['po_type']=='cash'){
            //$statement = '3';
            $oPo = &$this->app->model("po");
            $update_fields = array("statement"=>$statement);
            $filter = array("po_id"=>$paymentDetail['po_id']);
            $oPo->update($update_fields, $filter);
        }
        //$statement = $paymentDetail['po_type']=='cash' ? '3' : '2';

        //////////--------------------------现购写入结算清单------------------------------------
         if ($paymentDetail['po_type']=='cash'){
            //将应付金额、已付金额及差额等写入结算清单表中
            $statementArray = array(
                'supplier_id' => $paymentDetail['supplier_id'],
                'object_id' => $payment_id,
                'object_bn' => $paymentDetail['payment_bn'],
                'object_type' => $object_type,
                'pay_add' => abs($payable),
                'paid' => abs($localpaid),
                'difference' => abs($difference),
                'statement_time' => $statement_time
            );
            $oStatement = &$this->app->model("statement",$_GET['app']);
            $save_r2 = $oStatement->save($statementArray);
         }

        //如果为预付款类型，且预付款不为0，更新预付款金额
        if ($paymentDetail['po_type']=='credit')
        {
            $oBalance = &$this->app->model("po",$_GET['app']);
            $filter = array("po_id"=>$paymentDetail['po_id']);

            //获取预付款初始值
            $ini_deposit_balance = $oBalance->dump($filter,'deposit_balance');

            //更新预付款金额
            //$deposit_balance = $paymentDetail['deposit']+$ini_deposit_balance['deposit_balance'];
            $deposit_balance = $paid;
            $Banlance_data = array("deposit_balance"=>$deposit_balance);
            $oBalance->update($Banlance_data,$filter);
        }

        if ($save_r1) return true;
        else return false;
    }

    /*
     * 结算表单提交验证 validate
     */
    function validate($data){

        $paid = $data['paid'];#结算金额
        $payable = $data['payable'];#应付金额

        if($paid===null){
            trigger_error(app::get('base')->_('请输入结算金额'),E_USER_ERROR);
        }
        if($paid > $payable){
            trigger_error(app::get('base')->_('结算金额不能大于应付金额！'),E_USER_ERROR);
        }

    }

    /*
     * 获取结算支付方式 getPayment
     * @return array
     */
    function getPayment(){
        $sql = 'SELECT id,custom_name FROM `sdb_ome_payment_cfg`  ';
        $row = $this->db->select($sql);
        return $row;
    }

   //结算日期格式化
   function modifier_statement_time($row){
   	   if(empty($row)){
   	   	   $tmp = '';
   	   }else{
   	   	   $tmp = date('Y-m-d',$row);
   	   }
       return $tmp;
    }

    //添加日期格式化
   function modifier_add_time($row){
   	if(empty($row)){
   		$tmp = '';
   	}else{
   		$tmp = date('Y-m-d',$row);
   	}
       return $tmp;
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'po_bn'=>app::get('base')->_('采购单编号'),
        );
        return $Options = array_merge($parentOptions,$childOptions);
    }

    function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['payments'] = array(
                    'bn:付款单编号' => 'payment_bn',
                    'col:采购单编号'=>'po_id',
                    'col:制单日期' => 'add_time',
                    'col:供应商' => 'supplier_id',
                    'col:采购类型' => 'po_type',
                    'col:应付金额' => 'payable',
                    'col:经办人' => 'operator',
                    'col:商品费用' => 'product_cost',
                    'col:物流费用' => 'delivery_cost',
                    'col:预付金额' => 'deposit',
                    'col:结算金额' => 'paid',
                    'col:结算日期' => 'statement_time',
                    'col:结算余额' => 'balance',
                    'col:备注' => 'memo',
                    'col:支付方式' => 'paymethod',
                    'col:结算状态' => 'statement_status',
                    'col:发票号' => 'tax_no',
                    'col:银行账号' => 'bank_no',
                    'col:物流运单号' => 'logi_no',
                );
                break;
        }
        $this->ioTitle[$ioType]['payments'] = array_keys( $this->oSchema[$ioType]['payments'] );
        return $this->ioTitle[$ioType][$filter];
     }

     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        if( !$data['title']['payments'] ){
            $title = array();
            foreach( $this->io_title('payments') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['payments'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;
        //if( $filter[''] )
        if( !$list=$this->getList('payment_id',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $aOrder = $this->dump($aFilter['payment_id'],'*');
            if( !$aOrder )continue;
            //处理供应商信息
            $supplier = app::get('purchase')->model('supplier')->dump($aOrder['supplier_id']);
            $aOrder['supplier_id'] = $supplier['name'];

            //处理采购类型
            $aOrder['po_type'] = $aOrder['po_type'] =='cash'?'现款':'预付款';

            //处理结算状态statement_status

            switch ($aOrder['statement_status']){
                case '1':
                    $aOrder['statement_status'] = '未结算';
                    break;
                case '2':
                    $aOrder['statement_status'] = '已结算';
                    break;
                case '3':
                    $aOrder['statement_status'] = '拒绝结算';
                    break;
            }

            //采购单编号
            $po = app::get('purchase')->model('po')->dump($aOrder['po_id']);
            $aOrder['po_id'] = $po['po_bn'];

            //处理备注
              $aOrder['memo'] = kernel::single('ome_func')->format_memo($aOrder['memo']);
              if(!empty($aOrder['memo'])){
              	foreach($aOrder['memo'] as $k => $v){
                    $arr[]= $v['op_content']." BY ".$v['op_name']." ".$v['op_time'];
	              }
	              $aOrder['memo'] = implode(',',$arr);
              }

              //处理时间
             $aOrder['add_time'] = $aOrder['add_time']?date('Y-m-d H:i:s',$aOrder['add_time']):'';
             $aOrder['statement_time'] = $aOrder['statement_time']?date('Y-m-d H:i:s',$aOrder['statement_time']):'';

            foreach( $this->oSchema['csv']['payments'] as $k => $v ){
                $orderRow[$k] = $this->charset->utf2local(utils::apath( $aOrder,explode('/',$v) ));
            }
            $data['content']['payments'][] = '"'.implode('","',$orderRow).'"';
        }
        $data['name'] = '采购付款单'.date("Ymd");
        return true;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        echo implode("\n",$output);
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
        $mark = true;
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_purchase_payments') {
            if (is_string($params['statement_status']) && $params['statement_status'] == 2) {
                $type = 'bill_purchase_cash_export';
                $mark = false;
            }
            else {
                $type .= '_purchase_payments';
            }
        }
        if ($mark) {
            $type .= '_export';
        }
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