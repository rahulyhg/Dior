<?php
/**
 * 采购退款单对象处理
 * @author ome team
 * @copyright www.shopex.cn 2010.4.27
 */
class purchase_ctl_admin_purchase_refunds extends desktop_controller{

    var $name = "采购退款单";
    var $workground = "purchase_manager";


    /**
     * 采购退款单显示
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
        if($statement_status){
            $params['base_filter']['statement_status'] = $statement_status;
        }
        $this->finder('purchase_mdl_purchase_refunds', $params);
    }

    /**
     * 采购退款单结算
     * @param number
     * @return string
     */
    function statement($refund_id){

        $oRefund = &$this->app->model("purchase_refunds");

        //获取采购退款单详情
        $filter = array("refund_id"=>$refund_id);
        $refundDetail = $oRefund->dump($filter,'*');

        if ($refundDetail['statement_status']=="1")#未结算状态 ，进入结算处理
        {

            $inputArray = $_POST;

            if ($inputArray['statementSubmit']=="do"){

                unset($inputArray['statementSubmit']);
                $gotourl = 'index.php?app=purchase&ctl=admin_purchase_refunds&p[0]=1';
                $this->begin($gotourl);
                $inputArray['refund_id'] = $refund_id;
                //表单验证
                $refund_money = $refundDetail['refund'] ? $refundDetail['refund'] : 0;
                $oRefund->validate($inputArray, $refund_money);
                $return = $oRefund->statementDo($inputArray,$refundDetail);
                $this->end(true, app::get('base')->_('结算成功'));

            }else{

                //读取供应商预付款
                $oDeposit = &$this->app->model("po");
                $supplier_id = $refundDetail['supplier_id'];
                $deposit_balance = $oDeposit->dump(array('supplier_id'=>$supplier_id),'deposit_balance');

                //获取退货单编号
                $oRp = &$this->app->model("returned_purchase")->dump($refundDetail['rp_id'],'rp_bn');
                $this->pagedata['rp_bn'] = $oRp['rp_bn'];

                $this->pagedata['deposit_balance'] = $deposit_balance['deposit_balance'];
                //入库取消-赊购 不读取预付款
                if ($refundDetail['type']=='po' and $refundDetail['po_type']=="credit"){
                    $this->pagedata['deposit_balance'] = 0;
                }

                //供应商名称
                $oSupplier = &$this->app->model("supplier");
                $supplier_name = $oSupplier->supplier_detail($refundDetail['supplier_id']);
                if (!$supplier_name['operator']) $supplier_name['operator'] = '未知';
                $this->pagedata['supplier'] = $supplier_name;

                $refundDetail['refund'] = $refundDetail['refund'];
                $this->pagedata['detail'] = $refundDetail;
                $this->pagedata['payment'] = $oRefund->getPayment();#结算支付方式
                $this->pagedata['returntype'] = $oRefund->getReturnType();#退款 类型
                $this->pagedata['returnpaytype'] = $oRefund->getPaymentType();#付款类型
                $this->display('admin/purchase/refunds/statement.html');
            }

        }
        else
        {
            die('退款单已被结算');
        }


    }

    /*
     * 单据信息修改
     */
    function modify_detail()
    {
        if( $_POST['refund_id']){
           $oRefund = &$this->app->model("purchase_refunds");

           $gotourl = 'index.php?app=purchase&ctl=admin_purchase_refunds&p[0]=2';
           $this->begin($gotourl);
           $return = $oRefund->save($_POST);
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
        $this->page('admin/purchase/refunds/statement_confirm.html');
    }


}
?>