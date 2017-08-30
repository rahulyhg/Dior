<?php
/**
 * 采购付款单对象处理
 * @author ome team
 * @copyright www.shopex.cn 2010.4.27
 */
class purchase_ctl_admin_purchase_payments extends desktop_controller{

    var $name = "采购付款单";
    var $workground = "purchase_manager";


    /**
     * 采购付款单显示
     * @param number
     * @return string
     */
    function index($statement_status=NULL){

        //列表标题及过滤条件
        switch($statement_status)
        {
            case '1':
                $sub_title = " - 待付款";
                $statement_status = array(1,4);//把部分付款加进去
                $this->workground = 'finance_center';
                break;
            case '2':
                $sub_title = " - 已付款";
                $this->workground = 'invoice_center';
                break;
            case '3':
                $sub_title = " - 拒绝付款";
                $this->workground = 'invoice_center';
                break;
            default:
                $sub_title = " - 全部";
        }
        //增加“操作”选项宣布中的判断
        if ( in_array($_GET['p'][0],array('3','2') )){
            #增加单据导出权限
            $is_export = kernel::single('desktop_user')->has_permission('bill_export');
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
        }else{
            #增加财务导出权限
            $is_export = kernel::single('desktop_user')->has_permission('finance_export');
        	$params = array(
            'title'=>$this->name.$sub_title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>$is_export,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'orderBy' => 'add_time desc',
        	'finder_aliasname'=>'purchase_payments',
            'finder_cols'=>'payment_bn,po_id,add_time,supplier_id,payable,paid,logi_no',
            );
        }
        if($statement_status){
            $params['base_filter']['statement_status'] = $statement_status;
        }
        $params['base_filter']['disabled'] = 'false';
        $this->finder('purchase_mdl_purchase_payments', $params);
    }

    /*
     * 拒绝付款单
     * @param number
     * @return string
     */
    function dend_payments($statement_status=NULL){

        //列表标题及过滤条件
        switch($statement_status)
        {
            case '3':
                $sub_title = " - 拒绝付款";
                $this->workground = 'invoice_center';
                break;
        }
        $params = array(
            'title'=>$this->name.$sub_title,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
        );
        if($statement_status){
            $params['base_filter']['statement_status'] = $statement_status;
        }
        $params['base_filter']['disabled'] = 'false';
        $this->finder('purchase_mdl_purchase_payments', $params);
    }

    /**
     * 采购付款单结算
     * @param number
     * @return string
     */
    function statement($payment_id){

        $oPayments = &$this->app->model("purchase_payments");

        //获取采购付款单详情
        $filter = array("payment_id"=>$payment_id);
        $paymentDetail = $oPayments->dump($filter,'*');

        $inputArray = $_POST;
        #未结算、部分结算，可以继续付款
        if ($paymentDetail['statement_status']=="1" || $paymentDetail['statement_status'] == 4)#未结算状态 ，进入结算处理
        {

            if ($inputArray['statementSubmit']=="do"){  

                unset($inputArray['statementSubmit']);
                $gotourl = 'index.php?app=purchase&ctl=admin_purchase_payments&p[0]=1';
                $this->begin($gotourl);
                //表单验证
                $oPayments->validate($inputArray);
                $inputArray['payment_id'] = $payment_id;
                $return = $oPayments->statementDo($inputArray,$paymentDetail);
                $this->end(true, app::get('base')->_('付款成功'));

            }else{
                #如果已经部分支付，则需要计算未支付金额
                if(!empty($paymentDetail['paid'])){
                    $paymentDetail['need'] = $paymentDetail['payable']- $paymentDetail['paid'];#应付金额-已付金额
                }else{
                    $paymentDetail['need'] = $paymentDetail['payable'];
                }

                //模板输出
                $this->pagedata['detail'] = $paymentDetail;
                //获取采购单编号
                $oPo = &$this->app->model("po")->dump($paymentDetail['po_id'],'po_bn');
                $this->pagedata['po_bn'] = $oPo['po_bn'];
                $this->pagedata['payment'] = $oPayments->getPayment();#结算支付方式
                $oSupplier = &$this->app->model("supplier");
                //供应商名称
                $supplier_name = $oSupplier->supplier_detail($paymentDetail['supplier_id']);
                if (!$supplier_name['operator']) $supplier_name['operator'] = '未知';
                $this->pagedata['supplier'] = $supplier_name;
                $this->display('admin/purchase/payments/statement.html');
            }

        }
        else
        {
            die('付款单已被结算，请刷新当前页！');
            exit;
        }

    }

    /*
     * 单据信息修改
     */
    function modify_detail()
    {
        if( $_POST['payment_id']){
           $oPayments = &$this->app->model("purchase_payments");

           $gotourl = 'index.php?app=purchase&ctl=admin_purchase_payments&p[0]=2';
           $this->begin($gotourl);
           $return = $oPayments->save($_POST);
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
        $this->page('admin/purchase/payments/statement_confirm.html');
    }


}
?>