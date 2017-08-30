<?php
/*
 * 赊购单结算
 */

class purchase_mdl_credit_sheet extends dbeav_model{

    function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = "1";
        if(isset($filter['eo_bn'])){
            $eoObj = &$this->app->model("eo");
            $rows = $eoObj->getList('eo_id',array('eo_bn|has'=>$filter['eo_bn']));
            $eoId[] = 0;
            foreach($rows as $row){
                $eoId[] = $row['eo_id'];
            }
            $where .= '  AND eo_id IN ('.implode(',', $eoId).')';
            unset($filter['eo_bn']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".$where;
    }

    /*
     * 赊购单结算
     */
    public function statementDo($data, $csDetail, $statementType=''){ 
        $payd = $csDetail['paid'];#本次操作以前，已经支付的金额
        if(!$payd){
            $payd = 0;
        }

        //////////------------------------赊购单处理-------------------------------------
        $oPoid = &$this->app->model("po");
        $oDeposit = &$this->app->model("eo");

        $payable = $csDetail['payable'];#应付款

        //获取预付款初始值
        $eo_poid = $oDeposit->dump(array('eo_id'=>$csDetail['eo_id']),'po_id');
        $ini_deposit_balance = $oPoid->dump(array('po_id'=>$eo_poid['po_id']),'deposit_balance,eo_status');

        if (!$statementType){#非批量结算处理

            $deposit_balance = $ini_deposit_balance['deposit_balance'];#预付存款
            $temp_paid = $data['paid'];#临时存储输入的结算金额

        }else{#批量结算处理

            $deposit_balance = $data['deposit_balance'];
            $data['is_deduction'] = 'true';#默认为抵扣预付款
            $temp_paid = $payable;#临时存储输入的结算金额
        }
        //已经完成预付款支付
        if ($data['is_deposit'])
        {
            //if预付款>=结算金额  抵扣，然后将剩余金额返回给供应商
            if ($deposit_balance>=$temp_paid){
                $deposit_balanced = $deposit_balance - $temp_paid;#供应商预付存款余额
                $paid = $temp_paid + $payd;#结算金额= 本次结算金额+已支付结算金额
                $difference = $payable - $paid - $deposit_balance;#差额 = 应付 - 总结算金额 - 预付
                $balance = $difference;#结算余额 = 差额
                $cashmoney = $deposit_balance - $deposit_balanced;#预付款初始值 - 抵扣预付款
            }else{//预付款<结算金额，抵扣全部预付款，即供应商预付款清0
                $paid = $temp_paid  + $payd;#总结算金额  = 本次结算金额 +已付金额
                $difference = $payable - $temp_paid -  $deposit_balance - $payd;#差额 = 应付 - 本次结算金额 - 预付款 - 已付金额
                //结算余额 = 应付 - 本次结算金额 - 预付款 - 已付金额
                $balance = $difference;
                $deposit_balanced = 0;#预付款被扣完了，为0
                $cashmoney = $deposit_balance;#抵扣预付款
            }
            if ($cashmoney>'0') $memo = "本次结算抵扣预付款" . $cashmoney . "元";#备注
        }
        //结算单计算数据值
        $pay_add = $payable;#本期增加应付

        $localpaid = $temp_paid;

        ////付款单计算数据值=============================
        $statement_status = 2;#结算状态
        $statement_time = time();#结算时间
        $cs_id = $data['cs_id'];#赊购单ID
        $object_type = 1;#业务类型:赊购
        if ($memo){
            $memo = $memo."|".$data['memo'];
        }else{
            $memo = $data['memo'];
        }
        //$memo = $memo . "<br/>" . $data['memo'];
        $op_name = kernel::single('desktop_user')->get_name();
        $newmemo =  htmlspecialchars($memo);
        $memo1[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
        if (!$balance) {
            $balance = '0.000';
        }elseif($balance > 0){
            $statement_status = 4;#部分付款状态
        }
        $cureeBuy_array = array(
              'cs_id' => $cs_id,
              'paid' => $paid,
              'balance' => $balance,
              'memo' => serialize($memo1),
              'operator' => $data['operator'],
              'op_id' => kernel::single('desktop_user')->get_id(),
              'statement_status' => $statement_status,
              'statement_time' => $statement_time,
              'bank_no' => $data['bank_no']
        );
        $save_r1 = $this->save($cureeBuy_array);

        /*
         * 更新采购单的结算状态
         * if采购单的入库状态为部分入库，则每次结算时更新采购单的结算状态为部分结算
         * else采购单的状态为已入库，则每次结算时根据以下条件来判断{
         *    步骤一：获取此采购单的结算金额与余额总和
         *    步骤二：获取此采购单的采购总额
         *    步骤三：获取结算状态
         *    步骤四：更新结算状态
         *    部分结算状态 = 此采购单已结算金额+余额 < 采购单总额  or
         *    已结算状态  = 此采购单已结算金额+余额 = 采购单总额
         *    }
         */
        //采购单入库状态
        $po_eostatus = $ini_deposit_balance['eo_status'];
        if ($po_eostatus=='2'){
            $po_statement_status = '2';
        }else{
            //此采购单所有已结算金额与余额总和
            $po_filter = array('eo_id'=>$csDetail['eo_id'],'statement_status'=>array('2','4'));
            $po_statement_list = $this->getList('paid,balance', $po_filter, 0, -1);
            if ($po_statement_list)
            foreach ($po_statement_list as $k=>$v){
                $statement_total_money += $v['paid']+$v['balance'];
            }
            //采购单的总金额
            $po_detail = $oPoid->dump($eo_poid['po_id'], 'amount');
            $po_total_money = $po_detail['amount'];
            if ($statement_total_money<$po_total_money){
                $po_statement_status = '2';
            }else{
                $po_statement_status = '3';
            }
        }
        #结算完成
        if($statement_status == 2){
            $po_statement_status = '3';
        }
        //更新采购单的结算状态
        $filter = array("po_id"=>$eo_poid['po_id']);
        $oPoid->update(array('statement'=>$po_statement_status), $filter);

        //////////--------------------------写入结算清单------------------------------------
        //将应付金额、已付金额及差额等写入结算清单表中
        $statementArray = array(
              'supplier_id' => $csDetail['supplier_id'],
              'object_id' => $cs_id,
              'object_bn' => $csDetail['cs_bn'],
              'object_type' => $object_type,
              'pay_add' => $payable,
              'paid' => $localpaid,
              'difference' => $difference,
              'statement_time' => $statement_time
        );
        $oStatement = &$this->app->model("statement",$_GET['app']);
        $save_r2 = $oStatement->save($statementArray);

        //如果抵扣则更新预付款金额
        if ($data['is_deduction']=='true')
        {

            //更新预付款金额
            $Banlance_data = array("deposit_balance"=>$deposit_balanced);
            $oPoid->update($Banlance_data, $filter);
        }

        if ($save_r1 and $save_r2) return true;
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
     * 获取结算支付方式
     * @return array
     */
    function getPayment(){
        $sql = 'SELECT id,custom_name FROM `sdb_ome_payment_cfg`  ';
        $row = $this->db->select($sql);
        return $row;
    }

 /*
    * 编号
    */
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $cs_bn = date('YmdH').'21'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT cs_bn from sdb_purchase_credit_sheet where cs_bn =\''.$cs_bn.'\'');
        }while($row);
        return $cs_bn;
    }

   //添加日期格式化
   function modifier_add_time($row){
       $tmp = date('Y-m-d',$row);
       return $tmp;
    }

   //结算日期格式化(增加 没有结算日期显示‘1970-01-01’的判断)
   function modifier_statement_time($row){
   	   if(empty($row)){
   	   	    $tmp = '';
   	   }else{
   	     	$tmp = date('Y-m-d',$row);
   	   }
       return $tmp;
    }
    /*
    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'eo_bn'=>app::get('base')->_('入库单编号'),
        );
        return $Options = array_merge($parentOptions,$childOptions);
    }
    */
    function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['sheet'] = array(
                    'bn:赊账单编号' => 'cs_bn',
                    'col:入库单编号'=>'eo_id',
                    'col:制单日期' => 'add_time',
                    'col:供应商' => 'supplier_id',
                    'col:经办人' => 'operator',
                    'col:应付金额' => 'payable',
                    'col:商品总额' => 'product_cost',
                    'col:物流费用' => 'delivery_cost',
                    'col:结算金额' => 'paid',
                    'col:结算余额' => 'balance',
                    'col:备注' => 'memo',
                    'col:结算日期' => 'statement_time',
                    'col:结算状态' => 'statement_status',
                    'col:银行帐号' => 'bank_no',
                );
                break;
        }
        $this->ioTitle[$ioType]['sheet'] = array_keys( $this->oSchema[$ioType]['sheet'] );
        return $this->ioTitle[$ioType][$filter];
     }

     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        if( !$data['title']['sheet'] ){
            $title = array();
            foreach( $this->io_title('sheet') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['sheet'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;
        //if( $filter[''] )
        if( !$list=$this->getList('cs_id',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $aOrder = $this->dump($aFilter['cs_id'],'*');
            if( !$aOrder )continue;
            //处理供应商信息
            $supplier = app::get('purchase')->model('supplier')->dump($aOrder['supplier_id']);
            $aOrder['supplier_id'] = $supplier['name'];

            //处理操作员
            $aOrder['op_id'] = '';

            //处理时间
            $aOrder['add_time'] = date('Y-m-d H:i:s',$aOrder['add_time']);
            $aOrder['statement_time'] = $aOrder['statement_time']?date('Y-m-d H:i:s',$aOrder['statement_time']):'';
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

            //入库单编号
            $po = app::get('purchase')->model('eo')->dump($aOrder['eo_id']);
            $aOrder['eo_id'] = $po['eo_bn'];

            //处理备注
              $aOrder['memo'] = kernel::single('ome_func')->format_memo($aOrder['memo']);
              if(!empty($aOrder['memo'])){
	              foreach((array)$aOrder['memo'] as $k => $v){
	                     $arr[]= $v['op_content']." BY ".$v['op_name']." ".$v['op_time'];
	              }
	              $aOrder['memo'] = implode(',',$arr);
              }
            foreach( $this->oSchema['csv']['sheet'] as $k => $v ){
                $orderRow[$k] = $this->charset->utf2local(utils::apath( $aOrder,explode('/',$v) ));
            }
            $data['content']['sheet'][] = '"'.implode('","',$orderRow).'"';
        }
        $data['name'] = '采购赊购单'.date("Ymd");
        return true;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        echo implode("\n",$output);
    }

    function isCredit($po_id){
    	$row = $this->db->selectRow('select po_type from sdb_purchase_po where po_id='.$po_id);
    	if($row['po_type'] == 'cash'){
    		return false;
    	}else if($row['po_type'] == 'credit'){
    		return true;
    	}else{
    		return false;
    	}
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
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_credit_sheet') {
            if (is_string($params['statement_status']) && $params['statement_status'] == 2) {
                $type = 'bill_purchase_buyOnTally_export';
                $mark = false;
            }
            else {
                $type .= '_purchase_credit_sheet';
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
