<?php
/**
 * 采购赊购单对象处理
 * @author ome team
 * @copyright www.shopex.cn 2010.4.27
 */
class purchase_ctl_admin_credit_sheet extends desktop_controller{

    var $name = "采购赊账单";
    var $workground = "purchase_manager";


    /**
     * 采购赊购单显示
     * @param number
     * @return string
     */
    function index($statement_status=NULL){

        //列表标题及过滤条件
        switch($statement_status)
        {
            case '1':
                $sub_title = " - 未结算";
                $this->workground = 'finance_center';
                $statement_status = array(1,4);//把部分付款加进去
                $batch_statement = array(
                    'label' => '批量结算',
                    'submit' => 'index.php?app=purchase&ctl=admin_credit_sheet&act=batch_statement',
                    'target' => '_blank'
                );
                #增加财务导出权限
                $is_export = kernel::single('desktop_user')->has_permission('finance_export');
                break;
            case '2':
                $sub_title = " - 已结算";
                $this->workground = 'invoice_center';
                #增加单据导出权限
                $is_export = kernel::single('desktop_user')->has_permission('bill_export');
                break;
            default:
                $sub_title = " - 全部";
        }
        $params = array(
          'title'=>$this->name.$sub_title,
          'use_buildin_new_dialog' => false,
          'use_buildin_set_tag'=>false,
          'use_buildin_recycle'=>false,
          'use_buildin_export'=>$is_export,
          'use_buildin_import'=>false,
          'use_buildin_filter'=>true,
          'orderBy' => 'add_time desc',
        );
        if($batch_statement){
            $params['actions'] = array($batch_statement);
        }
        if($statement_status){
            $params['base_filter']['statement_status'] = $statement_status;
        }
        $this->finder('purchase_mdl_credit_sheet', $params);
    }

    /**
     * 采购赊购单结算
     * @param number
     * @return string
     */
    function statement($cs_id){

        $oCs = &$this->app->model("credit_sheet");       

        //获取采购赊购单详情
        $filter = array("cs_id"=>$cs_id);
        $csDetail = $oCs->dump($filter,'*');

        if ($csDetail['statement_status']=="1" || $csDetail['statement_status'] =="4")#未结算、部分结算状态 ，进入结算处理
        {

            $inputArray = $_POST;


            if ($inputArray['statementSubmit']=="do"){
                #必须先完成预付款的支付
                if(!$inputArray['is_deposit']){
                    $this->end(false, app::get('base')->_('请先到付款单完成预付款结算'));
                }
                unset($inputArray['statementSubmit']);
                $gotourl = 'index.php?app=purchase&ctl=admin_credit_sheet&p[0]=1';
                $this->begin($gotourl);
                $inputArray['cs_id'] = $cs_id;
                //表单验证
                $oCs->validate($inputArray);
                $return = $oCs->statementDo($inputArray, $csDetail);
                $this->end(true, app::get('base')->_('结算成功'));

            }else{
                #如果存在部分支付，则计算还需支付金额
                if(!empty($csDetail['paid'])){
                    $csDetail['need'] = $csDetail['product_cost']+$csDetail['delivery_cost']-$csDetail['paid'];
                }else{
                    $csDetail['need'] = $csDetail['payable'];
                }
                //读取供应商预付款
                $oDeposit = &app::get('taoguaniostockorder')->model("iso");
                $eo_poid = $oDeposit->dump(array('iso_id'=>$csDetail['eo_id']),'original_id,iso_bn');

                $oPoid = &$this->app->model("po");
                $deposit_balance = $oPoid->dump(array('po_id'=>$eo_poid['original_id']),'deposit_balance');   
                $oPayments = &$this->app->model("purchase_payments");
                $deposit_info = $oPayments->getList('statement_status,deposit',array('po_id'=>$eo_poid['original_id']));
                if(empty($deposit_info[0])){
                    $this->pagedata['is_deposit'] = 1;#如果没有预付款，视作预付款已经结算完成
                }
                #预付款已经结算完成
                if($deposit_info[0]['statement_status'] == 2){
                    $this->pagedata['is_deposit'] = 1;#预付款已经结算完成
                    $csDetail['paid'] = $csDetail['paid']+ $deposit_info[0]['deposit'];//本次已付金额=已付金额+ 预付款
                    $csDetail['need'] = $csDetail['need'] - $deposit_info[0]['deposit'];//本次还需支付=还需支付- 预付款
                }
                $this->pagedata['eo_bn'] = $eo_poid['iso_bn'];

                //模板输出
                $this->pagedata['detail'] = $csDetail;
                //判断采购单状态
                //$this->pagedata['deposit_balance'] = $deposit_balance['status']=='4' ? 0 : $deposit_balance['deposit_balance'];
                $this->pagedata['deposit_balance'] = $deposit_balance['deposit_balance'];

                $oSupplier = &$this->app->model("supplier");
                //供应商名称
                $supplier_name = $oSupplier->supplier_detail($csDetail['supplier_id']);
                if (!$supplier_name['operator']) $supplier_name['operator'] = '未知';
                $this->pagedata['supplier'] = $supplier_name;
                $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();

                $this->pagedata['potype'] = $oCs->getPayment();#结算支付方式
                $this->display('admin/purchase/credit_sheet/statement.html');
            }

        }
        else
        {
            die('赊购单已被结算！');
        }

    }

    /**
     * 批量结算 batch_statement
     * @param int
     * @return boolean
     */
    function batch_statement()
    {

        //被选中的赊购单ID
        $cs_ids = $_POST['cs_id'];

        $oCs = &$this->app->model("credit_sheet");
        $oSupplier = &$this->app->model("supplier");
        $oEo = &$this->app->model("eo");
        $oDeposit = &$this->app->model("eo");
        $oPoid = &$this->app->model("po");

        /*
         * 批量结算程序处理
         */
        if ($_POST['statementSubmit']=="do"){

           $data = $_POST;

           unset($inputArray['statementSubmit']);
           $gotourl = 'index.php?app=purchase&ctl=admin_credit_sheet&p[0]=1';
           $this->begin($gotourl);

           foreach ($data['cs_id'] as $val){
            
              if ($val ==0) continue;

              //获取批量的采购赊购单详情
              $filter = array("cs_id"=>$val);
              $csDetail = $oCs->dump($filter, '*');

              //已结算状态或者部分入库 ，退出处理
              if ($csDetail['statement_status']=="2") continue;

              //读取供应商预付款
              $eo_poid = $oDeposit->dump(array('eo_id'=>$csDetail['eo_id']),'po_id');
              $deposit_balance = $oPoid->dump(array('po_id'=>$eo_poid['po_id']),'deposit_balance');
              $value['deposit_balance'] = $deposit_balance['deposit_balance'] ? $deposit_balance['deposit_balance'] : 0;

              $value['cs_id'] = $val;
              $value['bank_no'] = $data['bank_no'][$val];
              $value['operator'] = $data['operator'][$val];
              $value['memo'] = $data['memo'][$val];

              $return = $oCs->statementDo($value, $csDetail, true);

           }

           $this->end(true, app::get('base')->_('批量结算成功'));


        }else{

            //获取批量的采购赊购单详情
            $filter = array("cs_id"=>$cs_ids);
            $csDetail = $oCs->getList('*', $filter, 0, -1);

            $detail = array();
            foreach ($csDetail as $key=>$val){
                /*
                 * 供应商名称
                 */
                $supplier_name = $oSupplier->supplier_detail($val['supplier_id']);
                $val['supplier_name'] = $supplier_name['name'];
                /*
                 * 采购单编号
                 */
                $eo_bn = $oEo->dump($val['eo_id'],'eo_bn');
                $val['eo_bn'] = $eo_bn['eo_bn'];

                $detail[] = $val;
            }

            $this->pagedata['operator'] = kernel::single('desktop_user')->get_name();
            //模板输出
            $this->pagedata['detail'] = $detail;
            $this->pagedata['potype'] = $oCs->getPayment();#结算支付方式
            $this->singlepage('admin/purchase/credit_sheet/batch_statement.html');

        }

    }

    /*
     * 单据信息修改
     */
    function modify_detail()
    {
        if( $_POST['cs_id']){
           $oCs = &$this->app->model("credit_sheet");

           $gotourl = 'index.php?app=purchase&ctl=admin_credit_sheet&p[0]=2';
           $this->begin($gotourl);
           $return = $oCs->save($_POST);
           $msg = $return ? '成功' : '失败';
           $this->end($return, app::get('base')->_('修改'.$msg));
        }
    }

    /*
     * 结算时当结算余额不为0时的POP窗口提示信息
     */
    function stateConfirm($html=null)
    {
        $this->workground = 'finance_center';
        $this->pagedata['html'] = urldecode($html);
        $this->page('admin/purchase/credit_sheet/statement_confirm.html');
    }


}
?>