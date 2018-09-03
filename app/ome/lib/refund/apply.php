<?php
/**
 * 退款申请类
 * @access public
 * @copyright www.shopex.cn 2010.12.1
 * @author ome
 */
class ome_refund_apply
{
    
    /**
     * 显示退款申请页面
     * @access public
     * @param int $order_id 订单ID
     * @param decimal 退款金额
     * @param $addon 附加属性，用于特殊情况处理
     * @param int $return_id 退款ID
     * @return html
     */
    public function show_refund_html($order_id,$return_id='0',$refund_money='0',$addon=null){
        $render = app::get('ome')->render();
        $msg = array('result'=>true, 'msg'=>'');
        if(!$order_id){
            $msg['result'] = false;
            $msg['msg'] = '订单号传递出错';
            return $msg;
        }
        $finder_id = $_GET['finder_id'];
        $orefapply = &app::get('ome')->model('orders');
        $oRefund = &app::get('ome')->model('refund_apply');
        $refunddata = $oRefund->getList('*',array('order_id'=>$order_id),0,-1);
        $amount = 0;
        foreach ($refunddata as $row){
            if ($row['status'] != 3 && $row['status'] != 4){
                $msg['result'] = false;
                $msg['msg'] = '上次申请未处理完成，请完成上次处理';
                return $msg;
            }
        }
       $render->pagedata['order'] = $orefapply->order_detail($order_id);
       if ($render->pagedata['order']['pay_status'] == '5'){
           $msg['result'] = false;
           $msg['msg'] = '订单已全额退款，无需再处理';
           return $msg;
       }
       $payment_cfgObj = &app::get('ome')->model('payment_cfg');
       $oPayment = &app::get('ome')->model('payments');
       $oShop = &app::get('ome')->model('shop');
       $shop_id = $render->pagedata['order']['shop_id'];
       $shop_detail = $oShop->dump($shop_id,'node_type,node_id');
       if ($shop_id){
           $payment = kernel::single('ome_payment_type')->paymethod($shop_id);
       }else{
           $payment = $oPayment->getMethods();
       }
       $payment_cfg = $payment_cfgObj->dump(array('pay_bn'=>$render->pagedata['order']['pay_bn']), 'id,pay_type');
       $render->pagedata['shop_id'] = $shop_id;
       $render->pagedata['node_id'] = $shop_detail['node_id'];
       $render->pagedata['payment'] = $payment;
       $render->pagedata['payment_id'] = $payment_cfg['id'];
       $render->pagedata['pay_type'] = $payment_cfg['pay_type'];
       if ($payment_cfg['id']){
           $order_paymentcfg = kernel::single('ome_payment_type')->paymethod($shop_id,$payment_cfg['pay_type']);
       }
       $render->pagedata['order_paymentcfg'] = $order_paymentcfg;
       $render->pagedata['typeList'] = ome_payment_type::pay_type();
       $aRet = $oPayment->getAccount();
       $aAccount = array('--使用已存在帐户--');
        foreach ($aRet as $v){
            $aAccount[$v['bank']."-".$v['account']] = $v['bank']."-".$v['account'];
        }
       $render->pagedata['pay_account'] = $aAccount;
       
       $paymentInfo = $oPayment->dump(array('order_id'=>$order_id));
       $render->pagedata['account'] = $paymentInfo['account'];
       $render->pagedata['payment_bank'] = $paymentInfo['bank'];
       $render->pagedata['payment_type'] = $paymentInfo['pay_type'];
       $render->pagedata['payment_account'] = $paymentInfo['pay_account'];
       
       $render->pagedata['is_c2cshop'] = in_array($shop_detail['node_type'],ome_shop_type::shop_list()) ?true:false;
       $render->pagedata['shop_name'] = ome_shop_type::shop_name($shop_detail['node_type']);

       if (!$refund_money){
           if ($return_id)
            {
                //处理售后的退款
                $render->pagedata['return_id'] = $return_id;
                $oReturn = &app::get('ome')->model('return_product');
                $return_detail = $oReturn->product_detail($return_id);
                if ($return_detail['tmoney'] > 0)
                {
                    $render->pagedata['refund_money'] = $return_detail['tmoney'];
                }
                else
                {
                    $render->pagedata['refund_money'] = 0;
                }
            }
            else
            {
                $render->pagedata['refund_money'] = $render->pagedata['order']['payed'];
            }
       }else{
           $render->pagedata['refund_money'] = $refund_money;
       }
       $render->pagedata['finder_id'] = $finder_id;
       $memberid = $render->pagedata['order']['member_id'];
       $oMember = &$render->app->model('members');
       $render->pagedata['member'] = $oMember->member_detail($memberid);
       $render->pagedata['aItems'] = $orefapply->getItemList($order_id);
       switch ($addon['from']){
           case 'order_edit'://订单编辑
               $render->pagedata['ctl'] = 'admin_order';
               $render->pagedata['act'] = 'do_refund';
               $render->pagedata['addon'] = $addon;
               break;
           case 'remain_order_cancel'://余单撤消
               $render->pagedata['ctl'] = 'admin_order';
               $render->pagedata['act'] = 'remain_order_cancel_refund';
               $render->pagedata['addon'] = $addon;
               $diff_price = kernel::single('ome_order_func')->order_items_diff_money($order_id);
               $render->pagedata['diff_price'] = $diff_price;
               $render->pagedata['remain_cancel_flag'] = 'true';
               break;
           default:
               $render->pagedata['ctl'] = 'admin_refund_apply';
               $render->pagedata['act'] = 'showRefund';
       }
       $render->pagedata['pay_status'] = kernel::single('ome_order_status')->pay_status();
       return $render->display('admin/refund/refund_apply.html');
    }
    
    /**
     * 添加退款申请
     * @access public
     * @param $data $data POST提交数据  $refund_refer 退款申请来源 0:普通流程产生的退款申请 1:通过售后流程产生的退款申请
     * @return 退款申请成功与失败状态及消息
     */
    public function refund_apply_add($data,$refund_refer='0'){
        $mathLib = kernel::single('eccommon_math');
        if($data){
            
            if(empty($data['pay_type']))
            {
                $msg['result'] = false;
                $msg['msg'] = '请选择退款类型';
                return $msg;
            }
            /*if(empty($data['payment']))
            {
                $msg['result'] = false;
                $msg['msg'] = '请选择退款支付方式';
                return $msg;
            }*/
            
            if( $data['refund_money'] <= 0)
            {
                $msg['result'] = false;
                $msg['msg'] = '退款金额必须大于0';
                return $msg;
            }
            
            $objOrder = &app::get('ome')->model('orders');
            $refundapp = &app::get('ome')->model('refund_apply');
            $oOrderItems = &app::get('ome')->model('order_items');
            $oLoger = &app::get('ome')->model('operation_log');
            $oShop = &app::get('ome')->model ( 'shop' );
			$order_id=$data['order_id'];
			$arrOrderBn=$objOrder->db->select("SELECT o.pay_bn,p.trade_no,o.order_bn,o.wx_order_bn,o.shop_id FROM sdb_ome_orders o LEFT JOIN sdb_ome_payments p ON o.order_id=p.order_id WHERE o.order_id='$order_id'");
            $bcmoney = $mathLib->getOperationNumber($data['bcmoney']);//补偿费用
            $countPrice=0;
            $countPrice=$data['refund_money'];
            $totalPrice=0;
            $totalPrice=$countPrice+$bcmoney;
			if($arrOrderBn['0']['pay_bn']=='cod'){
			    //$refund_apply_bn = $refundapp->gen_id();
				$refund_apply_bn = $arrOrderBn['0']['trade_no'];
			}else{
				if(empty($arrOrderBn['0']['trade_no'])){
					$msg['result'] = false;
					$msg['msg'] = '不存在的交易流水号';
					return $msg;
				}
				$refund_apply_bn = $arrOrderBn['0']['trade_no'];
			}
			$refund_apply_bn=$refundapp->checkRefundApplyBn($refund_apply_bn);
           //echo "<pre>";print_r($refund_apply_bn);exit();
            if ($data['source'] &&  in_array($data['source'],array('archive'))) {
                $objOrder = &app::get('archive')->model('orders');
                $source = $data['source'];
            }else{
                $objOrder = &app::get('ome')->model('orders');
            }
            $orderdata = $objOrder->order_detail($data['order_id']);
			if($data['payment']=="3"){
				$data['payment']=4;
			}
            $data=array(
                 'return_id'=>$data['return_id'],
                 'refund_apply_bn'=>$refund_apply_bn,
                 'order_id'=>$data['order_id'],
				 'wx_order_bn'=>$arrOrderBn['0']['wx_order_bn'],
                 'shop_id'=>$orderdata['shop_id'],
                 'pay_type'=>$data['pay_type'],
                 'bank'=>$data['bank'],
                 'account'=>$data['account'],
                 'pay_account'=>$data['pay_account'],
                 'money'=>$totalPrice,
                'bcmoney'=>$bcmoney,
                 'apply_op_id'=>kernel::single('desktop_user')->get_id(),
                 'payment'=>is_numeric($data['payment'])?$data['payment']:4,
                 'memo'=>$data['memo'],
                 'verify_op_id' =>kernel::single('desktop_user')->get_id(),
                 'addon' => serialize(array('return_id'=>$data['return_id'])),
                 'refund_refer' => $refund_refer,
				 'BeneficiaryName'=>$data['BeneficiaryName'],//收款人姓名
				 'BeneficiaryBankName'=>$data['BeneficiaryBankName'],//收款人账号
				 'isk'=>$data['isk'],
				 'iss'=>$data['iss'],
            );
            if ($source && in_array($source,array('archive'))) {
                $data['source'] = 'archive';
                $data['archive'] = 1;
            }
            $shop_type = $oShop->getShoptype($orderdata['shop_id']);
            $data['shop_type'] = $shop_type;
            $msg = array('result'=>true, 'msg'=>'申请退款成功,单据号为:'.$refund_apply_bn);
            if(round($countPrice,3)>round(($orderdata['payed']),3))
            {
                $msg['result'] = false;
                $msg['msg'] = '退款申请金额大于订单上的余额';
                return $msg;
            }
            //余单撤销退款金额判断
            if ($data['remain_cancel_flag'] == 'true' && $countPrice > $data['diff_price']){
                $msg['result'] = false;
                $msg['msg'] = '退款申请金额大于余单撤销金额';
                return $msg;
            }
            
            $data['create_time'] = time();
            // echo "<pre>";print_r($data);exit();
            if($refundapp->save($data))
            {   //将订单更改为退款申请中
				$z_order_bn=$arrOrderBn['0']['order_bn'];
				
				//$sql="SELECT d.delivery_id FROM sdb_ome_orders o LEFT JOIN sdb_ome_delivery_order od ON od.order_id=o.order_id LEFT JOIN sdb_ome_delivery d ON d.delivery_id=od.delivery_id WHERE o.order_id='$order_id' AND d.status='succ'";
				//$arrIsDelivery=$objOrder->db->select($sql);
				//if(!empty($arrIsDelivery[0]['delivery_id'])){
					$z_refund_id=$data['apply_id'];
					
					
				//}
				
				if($data['payment']=="4"){
					kernel::single('omemagento_service_order')->update_status($z_order_bn,'refund_required');
					
				}else{
					kernel::single('omemagento_service_order')->update_status($z_order_bn,'refunding');
				}
				app::get('ome')->model('refund_apply')->sendRefundToM($z_refund_id,$z_order_bn,$totalPrice);
                
			    kernel::single('ome_order_func')->update_order_pay_status($data['order_id']);

                /*if ($data['return_id']){
                     //插入return_refund_apply
                     $oreturn_refund_apply = &app::get('ome')->model('return_refund_apply');
                     $return_ref_data = array('refund_apply_id'=>$data['apply_id'],'return_id'=>$data['return_id']);
                     $oreturn_refund_apply->save($return_ref_data);
                }*/

                ### 订单状态回传kafka august.yao 退款申请中 start ###
                $kafkaQueue  = app::get('ome')->model('kafka_queue');
                $queueData = array(
                    'queue_title' => '订单退款申请中状态推送',
                    'worker'      => 'ome_kafka_api.sendOrderStatus',
                    'start_time'  => time(),
                    'params'      => array(
                        'status'   => 'refunding',
                        'order_bn' => $z_order_bn,
                        'logi_bn'  => '',
                        'shop_id'  => $arrOrderBn['0']['shop_id'],
                        'item_info'=> array(),
                        'bill_info'=> array(),
                    ),
                );
                $kafkaQueue->save($queueData);
                ### 订单状态回传kafka august.yao 退款申请中 end ###
                
                $oLoger->write_log('refund_apply@ome',$data['apply_id'],'申请退款成功');
                return $msg;
            }
        }
    }
    

    
    /**
     * 批量处理售后申请单
     * @param   array apply_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function batch_update($status_type,$apply_id){
        set_time_limit(0);
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $oLoger = &app::get('ome')->model('operation_log');
        $oReturn_batch = &app::get('ome')->model('return_batch');
        
        $error_msg = array();
        $need_apply_id = array();

        foreach ($apply_id as $apply_id ) {
            $apply_id = explode('||',$apply_id);
            $need_apply_id[] = $apply_id[1];
        }
        $apply_list = $oRefund_apply->db->select('SELECT status,source,shop_type,apply_id,refund_apply_bn,shop_id,order_id,return_id FROM sdb_ome_refund_apply WHERE apply_id in('.implode(',',$need_apply_id).')');
        
        $fail = 0;$succ=0;
        if ($status_type == 'agree') {
            $batchList = $this->return_batch('accept_refund');
            foreach ( $apply_list as $apply ) {#同意退款目前只有天猫需要回写
                $apply_id = $apply['apply_id'];
                $status = $apply['status'];
                if (in_array( $status,array('0','1'))){
                    if (in_array($apply['shop_type'],array('tmall','meilishuo')) && $apply['source'] == 'matrix') {
                        $return_batch = $batchList[$apply['shop_id']];
                        $refund = array(
                            'apply_id'=>$apply['apply_id'],
                            'refuse_message'=>$return_batch['memo'],
                        );
                        $rs = kernel::single('ome_service_refund_apply')->update_status($refund,2,'sync');
                    }
                    
                    if ($rs && $rs['rsp'] == 'fail') {
                        $fail++;
                        $error_msg[] = '单号:'.$apply['refund_apply_bn'].",".$rs['msg'];
                        
                    }else{
                        #更新退款单接受状态
                        $this->update_refund_applyStatus('2',$apply);
                    }
                }
            }
            
        }elseif($status_type == 'refuse') {
            $batchList = $this->return_batch('refuse');
            #淘宝拒绝天猫需上传凭证
            
            foreach ( $apply_list as $apply ) {
                $status = $apply['status'];
                $apply_id = $apply['apply_id'];
                if ( in_array( $status , array( '0','1','2' ) )) {
                    $rs = array();
                    if (in_array($apply['shop_type'],array('taobao','tmall')) && $apply['source'] == 'matrix'){
                        
                        $return_batch = $batchList[$apply['shop_id']];
                        $refund = array(
                            'apply_id'=>$apply_id,
                            'refuse_message'=>$return_batch['memo'],
                        );
                        $picurl = $return_batch['picurl'];
                        if ($apply['shop_type'] == 'tmall') {
                            $picurl = file_get_contents($picurl);
                            $picurl = base64_encode($picurl);
                            
                        }
                        $refund['refuse_proof'] = $picurl;
                        $rs = kernel::single('ome_service_refund_apply')->update_status($refund,3,'sync');

                    }
                    
                    if ( ($rs && $rs['rsp'] == 'fail') ) {
                        $fail++;
                        $error_msg[] = '单号:'.$apply['refund_apply_bn'].",".$rs['msg'];
                        
                    }else{
                        $this->update_refund_applyStatus('3',$apply);                       
                    }
                }
            }
        }
        $result = array('error_msg'=>$error_msg,'fail'=>$fail);
        return $result;
    }

    
    /**
     * 退款单列表.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function refund_list($status_type,$apply_id)
    {
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        if ($status_type == 'agree') {
            $apply_list = $oRefund_apply->db->select('SELECT apply_id FROM sdb_ome_refund_apply WHERE apply_id in('.implode(',',$apply_id).') AND `status` in (\'0\',\'1\')');
        }else{
            $apply_list = $oRefund_apply->db->select('SELECT apply_id FROM sdb_ome_refund_apply WHERE apply_id in('.implode(',',$apply_id).') AND `status` in (\'0\',\'1\',\'2\')');
        }
        
        $apply_id_list = array();
        foreach ($apply_list as $apply ) {
            $apply_id_list[] = $apply['apply_id'];
        }
        return $apply_id_list;
    }
    
    /**
    * 售后默认设置
    */
    public function return_batch($batch_type){
        $oReturn_batch = &app::get('ome')->model('return_batch');
        $batch = $oReturn_batch->getlist('*',array('is_default'=>'true','batch_type'=>$batch_type));
        $batchList = array();
        foreach ($batch as $item ) {
            $batchList[$item['shop_id']] = $item;
        }
        return $batchList;
    }

    
    /**
     * 更新退款申请单状态.
     * @param   status
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function update_refund_applyStatus($status,$apply)
    {
        $oLoger = &app::get('ome')->model('operation_log');
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $Oreturn_product = app::get('ome')->model('return_product');
        $apply_id = $apply['apply_id'];
        $order_id = $apply['order_id'];
        switch ($status) {
            case '2':#接受
                $refund_op = 'refund_pass@ome';
                $refund_op_name = '接受';
                break;
            case '3':#拒绝
                $refund_op = 'refund_refuse@ome';
                $refund_op_name = '拒绝';
                break;
            default:
                return true;
                break;
        }
        $data['apply_id'] = $apply_id;
        $data['status'] = $status;
        $data['last_modified'] = time();

        $oRefund_apply->save($data,true);
        $memo = "退款申请 $refund_op_name";
        if ($apply['oper_memo']) {
            $memo = $apply['oper_memo'];
        }
        $oLoger->write_log($refund_op,$apply_id,$memo);
        if ( $status == '3' ) {
            $return_id = $apply['return_id'];
            if ($return_id) {
                $return_data = array ('return_id' => $return_id, 'status' => '9', 'last_modified' => time () );
                $Oreturn_product->save ( $return_data );

                $oLoger->write_log('return@ome',$return_id,$apply['refund_apply_bn'].$memo);    
            }
            
            kernel::single('ome_order_func')->update_order_pay_status($order_id);
            //生成售后单
            kernel::single('sales_aftersale')->generate_aftersale($apply_id,'refund');
        }
    }
}