<?php
/*
 * 退款单结算
 */

class purchase_mdl_purchase_refunds extends dbeav_model{

    function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = "1";
        if(isset($filter['eo_bn'])){
            $purchaseObj = $this->app->model("po");
            $rows =$purchaseObj->getList('po_id',array('po_bn'=>$filter['eo_bn']));
            $eoId[] = 0;
            foreach($rows as $row){
                $eoId[] = $row['po_id'];
            }

            $rpObj = &$this->app->model("returned_purchase");
            $rows = $rpObj->getList('rp_id',array('object_id'=>$eoId));
            $rpId[] = 0;
            foreach($rows as $row){
                $rpId[] = $row['rp_id'];
            }
           
            $where .= '  AND rp_id IN ('.implode(',', $rpId).')';
            unset($filter['eo_bn']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere)." AND ".$where;
    }

    /*
     * 退款单结算
     */
    public function statementDo($data,$refundDetail){

        //////////------------------------退款单处理-------------------------------------

        $ini_paid = $refundDetail['refund'];//应收金额，即本期增加应收
        $paid = $data['paid'];#结算金额  = 用户输入的结算金额
        $difference = $ini_paid - $paid;#差额 = 应收 - 结算金额，此处的差额为负数
        unset($type);
        //如果退款类型为入库退款的赊购方式
        if ($refundDetail['type']=='po' and $refundDetail['po_type']=='credit')
        {
            $type = 'po-credit';
            //备注
            if (!$difference){
                $memo = "预付款" . $refundDetail['refund'] . "元，已退款，退款单号：" . $refundDetail['refund_bn'];
            }else{
                $memo = "预付款抵扣余额计：".$data['deposit_balance'] . "元，供应商实退" . $data['paid'] . "元，退款单号：" . $refundDetail['refund_bn'];
            }
        }
        //结算单计算数据值
        $receive_add = $ini_paid;#本期增加应收
        $received = $paid;#本期已收

        ////退款单计算数据值=============================
        $statement_status = 2;#结算状态
        $statement_time = time();#结算时间
        $refund_id = $refundDetail['refund_id'];#退款单ID
        //业务类型
        $object_type = 3;#采购退货
        if ($memo){
            $memo = $memo."<br/>".$data['memo'];
        }else{
            $memo = $data['memo'];
        }
        $data['payment'] = (int)$data['payment'];

        $op_name = kernel::single('desktop_user')->get_name();
        $newmemo =  htmlspecialchars($memo);
        $memo1[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);

        if (!$paid) $paid = '0.000';
        $cureeBuy_array = array(
              'refund_id' => $refund_id,
              'memo' => serialize($memo1),
              'refund' => $paid,
              'operator' => $data['operator'],
              'op_id' => kernel::single('desktop_user')->get_id(),
              'statement_status' => $statement_status,
              'statement_time' => $statement_time,
              'payment' => $data['payment'],
              'bank_no' => $data['bank_no']
        );
        $save_r1 = $this->save($cureeBuy_array);

        if ($type<>'po-credit'){
            //////////--------------------------写入结算清单------------------------------------
            //将应收金额、结算金额及差额等写入结算清单表中
            $statementArray = array(
                  'supplier_id' => $refundDetail['supplier_id'],
                  'object_id' => $refund_id,
                  'object_bn' => $refundDetail['refund_bn'],
                  'object_type' => $object_type,
                  'receive_add' => $receive_add,
                  'received' => $received,
                  'difference' => $difference,
                  'statement_time' => $statement_time
            );
            $oStatement = &$this->app->model("statement");
            $save_r2 = $oStatement->save($statementArray);
        }else{
            /*
            $oBalance = &$this->app->model("po",$_GET['app']);
            //供应商预付款清0
            $oReturn = &$this->app->model("returned_purchase";
            $poid = $oDeposit->dump(array('rp_id'=>$refundDetail['rp_id']),'object_id');

            $Banlance_data = array("deposit_balance"=>'0');
            $filter = array("po_id"=>$poid['object_id']);
            $oBalance->update($Banlance_data,$filter);
            */
        }

        if ($save_r1) return true;
        else return false;
    }

    /*
     * 结算表单提交验证 validate
     */
    function validate($data, $refund_money){

        $paid = $data['paid'];#结算金额

        if($paid===null){
            trigger_error(app::get('base')->_('请输入结算金额'),E_USER_ERROR);
        }
        if($paid > $refund_money){
            trigger_error(app::get('base')->_('结算金额不能大于应收金额！'),E_USER_ERROR);
        }

    }

    /*
     * 获取结算支付方式getPayment
     * @return array
     */
    function getPayment($pid=''){

        if (!$pid){
            $sql = 'SELECT id,custom_name FROM `sdb_ome_payment_cfg`  ';
            $row = $this->db->select($sql);
            return $row;
        }
        else
        {
            $sql = "SELECT custom_name FROM `sdb_ome_payment_cfg` where id='".$pid."'";
            $row = $this->db->selectrow($sql);
            return $row['custom_name'];
        }
    }

    /*
     * 获取退款类型 getReturnType
     * @return array
     */
    function getReturnType($type='')
    {
        $returnArr = array(
          'po'=> '入库取消',
          'eo'=> '采购退货'
        );
        if ($type) return $returnArr[$type];
        else return $returnArr;
    }

    /*
     * 获取付款类型 getReturnType
     * @return array
     */
    function getPaymentType($po_type='')
    {
        $returnArr = array(
          'cash'=> '现购',
          'credit'=> '赊购',
        );
        if ($po_type) return $returnArr[$po_type];
        else return $returnArr;
    }

    /*
    * 编号
    * 退款单编号
    */
    function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $refund_bn = date('YmdH').'19'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT refund_bn from sdb_purchase_purchase_refunds where refund_bn =\''.$refund_bn.'\'');
        }while($row);
        return $refund_bn;
    }

    function createRefund($sdf){
        $sdf['refund_bn'] = $this->gen_id();
        $this->save($sdf);

        return $sdf['refund_id'];
    }

   //添加日期格式化
   function modifier_add_time($row){
       $tmp = date('Y-m-d',$row);
       return $tmp;
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

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'eo_bn'=>app::get('base')->_('入库单编号'),
        );
        return $Options = array_merge($parentOptions,$childOptions);
    }

    function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['refunds'] = array(
                    'bn:退款单编号' => 'refund_bn',
                    'col:退货单ID'=>'rp_id',
                    'col:制单日期' => 'add_time',
                    'col:供应商' => 'supplier_id',
                    'col:采购方式' => 'po_type',
                    'col:退货方式' => 'type',
                    'col:结算金额' => 'refund',
                    'col:商品总金额' => 'refund',
                    'col:商品总金额' => 'product_cost',
                    'col:经办人' => 'operator',
                    'col:物流费用' => 'delivery_cost',
                    'col:结算日期' => 'statement_time',
                    'col:银行账号' => 'bank_no',
                    'col:结算状态' => 'statement_status',
                    'col:备注' => 'memo',
                );
                break;
        }
        $this->ioTitle[$ioType]['refunds'] = array_keys( $this->oSchema[$ioType]['refunds'] );
        return $this->ioTitle[$ioType][$filter];
     }

     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        if( !$data['title']['refunds'] ){
            $title = array();
            foreach( $this->io_title('refunds') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['refunds'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;
        //if( $filter[''] )
        if( !$list=$this->getList('refund_id',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $aOrder = $this->dump($aFilter['refund_id'],'*');
            if( !$aOrder )continue;
            //处理供应商信息
            $supplier = app::get('purchase')->model('supplier')->dump($aOrder['supplier_id']);
            $aOrder['supplier_id'] = $supplier['name'];

            //处理采购类型
            $aOrder['po_type'] = $aOrder['po_type'] =='cash'?'现款':'预付款';

            //处理时间
            $aOrder['add_time'] = date('Y-m-d',$aOrder['add_time']);
//            $aOrder['statement_time'] = $aOrder['statement_time']?date('Y-m-d H:i:s',$aOrder['statement_time']):'';

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

            //退货单ID处理
            $po = app::get('purchase')->model('returned_purchase')->dump($aOrder['rp_id']);
            $aOrder['rp_id'] = $po['rp_bn'];

            //处理备注
              $aOrder['memo'] = kernel::single('ome_func')->format_memo($aOrder['memo']);
              if(!empty($aOrder['memo'])){
                  foreach((array)$aOrder['memo'] as $k => $v){
                         $arr[]= $v['op_content']." BY ".$v['op_name']." ".$v['op_time'];
                  }
                  $aOrder['memo'] = implode(',',$arr);
              }

              //处理时间
             $aOrder['statement_time'] = $aOrder['statement_time']?date('Y-m-d H:i:s',$aOrder['statement_time']):'';

             //处理退货方式
             switch ($aOrder['type']){
                case 'po':
                    $aOrder['type'] = '入库取消';
                    break;
                case 'eo':
                    $aOrder['type'] = '采购退货';
                    break;
            }

            foreach( $this->oSchema['csv']['refunds'] as $k => $v ){
                $orderRow[$k] = $this->charset->utf2local(utils::apath( $aOrder,explode('/',$v) ));
            }
            $data['content']['refunds'][] = '"'.implode('","',$orderRow).'"';
        }
        $data['name'] = '采购退款单'.date("Ymd");
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
        if ($logParams['app'] == 'purchase' && $logParams['ctl'] == 'admin_purchase_refunds') {
            if (is_string($params['statement_status']) && $params['statement_status'] == 2) {
                $type = 'bill_purchase_purchaseRefund_export';
                $mark = false;
            }
            else {
                $type .= '_purchase_purchaseRefunds';;
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
