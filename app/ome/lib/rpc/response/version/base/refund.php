<?php
/**
 * 前端店铺退款业务处理
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_version_base_refund extends ome_rpc_response
{

    /**
     * 添加退款单
     * @access public
     * @param array $refund_sdf 退款单数据
     * @return array 退款单主键ID array('refund_id'=>'退款单主键ID')
     */
    function add($refund_sdf){

        $logTitle = '前端店铺退款业务处理[order_bn:'. $refund_sdf['order_bn'] . ']';
        $logInfo = '前端店铺退款业务处理接口：<BR>';
        $logInfo .= '接收参数 $refund_sdf 信息：' . var_export($refund_sdf, true) . '<BR>';

        $shop_id = $refund_sdf['shop_id'];
        $status = $refund_sdf['status'];
        $refund_money = $refund_sdf['money'];
        $refund_bn = $refund_sdf['refund_bn'];
        $refund_type = $refund_sdf['refund_type'];
        $order_bn = $refund_sdf['order_bn'];
        $refundObj = &app::get('ome')->model('refunds');
        $refund_applyObj = &app::get('ome')->model('refund_apply');
        $shop_name = $refund_sdf['shop_name'];
        $shop_type = $refund_sdf['shop_type'];

        //返回值
        $rs_data = array('tid'=>$order_bn,'refund_id'=>$refund_bn,'retry'=>'false');
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logTitle'=>$logTitle,'logInfo'=>'','update_order'=>'false');
        //状态值判断
        if ($status==''){
            $msg = 'Status field value is not correct';
            $rs['logInfo'] .= $logInfo;
            $rs['msg'] = $msg;
            return $rs;
        }
        //退款金额判断
        if ($refund_money<=0){
            $msg = 'Money field value is not correct';
            $rs['logInfo'] .= $logInfo;
            $rs['msg'] = $msg;
            return $rs;
        }
        //判断退款单是否已经存在
        if($refundObj->dump(array('refund_bn'=>$refund_sdf['refund_bn'],'shop_id'=>$shop_id))){

           $filter = array('refund_apply_bn'=>$refund_bn,'shop_id'=>$shop_id);
           $refund_applyObj->update(array('status'=>'4'),$filter);

           $logInfo .= '判断退款单是否已经存在，返回值为:<BR>' . var_export($rs_data, true) . '<BR>';
           $rs['logInfo'] = $logInfo;
           $rs['rsp'] = 'success';
           return $rs;
        }
        if ($refund_bn!='' and $order_bn!=''){

            $orderObj = &app::get('ome')->model('orders');
            $objMath = kernel::single('eccommon_math');
            $oApi_log = &app::get('ome')->model('api_log');
            $order_detail = $orderObj->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn), 'pay_status,status,process_status,order_id,payed,cost_payment');
            $refund_apply = $refund_applyObj->dump(array('refund_apply_bn'=>$refund_bn,'shop_id'=>$shop_id));

            //判断订单是否存在
            if(empty($order_detail['order_id'])){
                $order_is_exists = false;
                $rpc_order = kernel::single("ome_rpc_request_order");

                $rsp_data = $rpc_order->get_order_detial($order_bn,$shop_id,'direct');

                if($rsp_data['rsp'] == 'fail' && $shop_type == 'taobao'){
                    $rsp_data = $rpc_order->get_order_detial($order_bn,$shop_id,'agent');
                }

                if( $rsp_data['rsp'] == 'succ' ){
                    $obj_syncorder = kernel::single("ome_syncorder");
                    $sdf_orders = $rsp_data['data']['trade'];
                    if($obj_syncorder->get_order_log( $sdf_orders,$shop_id,$msg )){
                        $order_detail = $orderObj->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn), 'pay_status,status,process_status,order_id,payed,cost_payment');
                        $order_is_exists = true;
                    }

                }

                if(!$order_is_exists){
                    $msg = 'Order NO:'.$order_bn.' not exists ';
                    $rs_data['retry'] = 'true';
                    $logInfo .= '订单不存在: ' . $msg . '<BR>';
                    $rs['logInfo'] = $logInfo;
                    $rs['msg'] = $msg;
                    return $rs;
                }
            }

            //判断订单状态是否为活动订单并且退款申请单状态是已退款。
            if (($order_detail['status']!='active' && ($refund_apply['status'] == '4')) || ($refund_apply['status'] == '3') ){
                //日志记录
                /*
                $api_filter = array('marking_value'=>$refund_bn,'marking_type'=>'refund_status');
                $api_detail = $oApi_log->dump($api_filter, 'log_id');
                if (empty($api_detail['log_id'])){
                    $msg = '订单号:'.$order_detail['order_bn'].'不是活动订单,无法退款';
                    $log_title = '店铺('.$shop_name.')添加退款单['.$refund_bn.'],'.$msg;
                    $addon = $api_filter;
                    $log_id = $oApi_log->gen_id();
                    $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_refund','add','','','response','fail',$msg,$addon,'api.store.trade.refund',$refund_bn);
                }
                */
                $msg = '订单号:'.$order_detail['order_bn'].'不是活动订单,无法退款';
                $rs['msg'] = $msg;
                $logInfo .= '订单号:'.$order_detail['order_bn'].'不是活动订单,无法退款';
                $rs['logInfo'] = $logInfo;
                return $rs;
            }

            //判断订单确认状态
            if ($order_detail['process_status']=='cancel' && ($refund_apply['status'] == '4') ){
                //日志记录
                /*
                $api_filter = array('marking_value'=>$refund_bn,'marking_type'=>'refund_process_status');
                $api_detail = $oApi_log->dump($api_filter, 'log_id');
                if (empty($api_detail['log_id'])){
                    $msg = '订单:'.$order_detail['order_bn'].'确认状态取消,无法退款';
                    $log_title = '店铺('.$shop_name.')添加退款单['.$refund_bn.'],'.$msg;
                    $addon = $api_filter;
                    $log_id = $oApi_log->gen_id();
                    $oApi_log->write_log($log_id,$log_title,'ome_rpc_response_refund','add','','','response','fail',$msg,$addon,'api.store.trade.refund',$refund_bn);
                }
                */
                $msg = '订单:'.$order_detail['order_bn'].'确认状态取消,无法退款';
                $rs['msg'] = $msg;
                $logInfo .= '订单:'.$order_detail['order_bn'].'确认状态取消,无法退款';
                $rs['logInfo'] = $logInfo;
                return $rs;
            }

            $order_id = $order_detail['order_id'];
            $refund_sdf['t_ready'] = kernel::single('ome_func')->date2time($refund_sdf['t_ready']);
            $refund_sdf['t_sent'] = kernel::single('ome_func')->date2time($refund_sdf['t_sent']);
            $refund_sdf['t_received'] = kernel::single('ome_func')->date2time($refund_sdf['t_received']);
            if ($status=="succ" || $refund_type == 'refund'){//退款成功

                //取出退款申请单据编号
                if ($refund_sdf['memo']){
                    $refund_apply_bn = '';
                    preg_match("/#(\d+)#/", $refund_sdf['memo'], $refund_apply_bn);
                    $refund_apply_bn = $refund_apply_bn[1];
                    $pattrn = '#'.$refund_apply_bn.'#';
                    $refund_sdf['memo'] = str_replace($pattrn, '', $refund_sdf['memo']);
                }

                $pay_bn = $refund_sdf['payment'];
                if ($pay_bn){
                    $payment_cfgObj = &app::get('ome')->model('payment_cfg');
                    $payment_cfg = $payment_cfgObj->dump(array('pay_bn'=>$pay_bn), 'id');
                    $refund_sdf['payment'] = $payment_cfg['id'];
                }

                $sdf = array(
                    'refund_bn' => $refund_bn,
                    'shop_id' => $shop_id,
                    'order_id' => $order_id,
                    'account' => $refund_sdf['account'],
                    'bank' => $refund_sdf['bank'],
                    'pay_account' => $refund_sdf['pay_account'],
                    'currency' => $refund_sdf['currency'],
                    'money' => $refund_money?$refund_money:'0',
                    'paycost' => $refund_sdf['paycost'],
                    'cur_money' => $refund_sdf['cur_money']?$refund_sdf['cur_money']:$refund_sdf['money'],
                    'pay_type' => $refund_sdf['pay_type']?$refund_sdf['pay_type']:'online',
                    'payment' => $refund_sdf['payment'],
                    'paymethod' => $refund_sdf['paymethod'],
                    'download_time' => time(),
                    'status' => $status,
                    'memo' => $refund_sdf['memo'],
                    'trade_no' => $refund_sdf['trade_no']
                );

                $c2c_shop_list = ome_shop_type::shop_list();

                if(in_array($shop_type, $c2c_shop_list)){
                    $rs['update_order'] = 'true';
                    $sdf['t_ready'] = $refund_sdf['t_ready'];
                    $sdf['t_sent'] = $refund_sdf['modified'];
                    $sdf['t_received'] = '';//如果是c2c订单不设用户收款时间
                }else{
                    $shopObj = &app::get('ome')->model('shop');
                    $shop_info = $shopObj->getRow(array('node_id'=>$refund_sdf['node_id']),'api_version');
                    if($shop_info['api_version'] == '' || $shop_info['api_version'] == '1.0'){
                        $rs['update_order'] = 'true';
                    }else{
                        $rs['update_order'] = 'false';
                    }

                    $sdf['t_ready'] = $refund_sdf['t_ready'] ? $refund_sdf['t_ready'] : $refund_sdf['t_sent'];
                    $sdf['t_sent'] = $refund_sdf['t_sent'] ? $refund_sdf['t_sent'] : $refund_sdf['t_ready'];
                    $sdf['t_received'] = $refund_sdf['t_received'];
                }

                $refundObj->create_refunds($sdf);

                if(!isset($sdf['refund_id'])){
                    $rs['update_order'] = 'false';
                }

                $logInfo .= '创建退款单, 参数值为:<BR>' . var_export($sdf, true) . '<BR>';

                if ($refund_apply['apply_id'] || $refund_apply_bn){
                    //将退款申请单状态变成已退款
                    $refund_apply_update = array(
                       'status' => '4',
                       'refunded' => $refund_money,
                    );
                    $refund_bn = $refund_apply_bn ? $refund_apply_bn : $refund_bn;
                    $refund_apply_filter = array(
                       'shop_id' => $shop_id,
                       'refund_apply_bn' => $refund_bn,
                    );
                    $refund_applyObj->update($refund_apply_update, $refund_apply_filter);
                    $logInfo .= '将退款单状态变成已退款，参数为为:<BR>' . var_export($refund_apply_update, true) . '<BR>' . var_export($refund_apply_filter, true) . '<BR>';

                    //更新售后申请单的退款金额
                    $refund_apply_detail = $refund_applyObj->dump($refund_apply_filter, 'addon');
                    $addon = $refund_apply_detail['addon'];
                    if (!empty($addon)){
                        $addon = unserialize($addon);
                        $return_id = $addon['return_id'];
                        $sql = "UPDATE `sdb_ome_return_product` SET `refundmoney`=IFNULL(`refundmoney`,0)+{$refund_money} WHERE `return_id`='".$return_id."'";
                        kernel::database()->exec($sql);

                        $logInfo .= '更新售后申请单的退款金额，返回值为:<BR>' . $sql . '<BR>';

                    }
                }

                $logInfo .= '更新订单 ' . $order_id . ' 状态及金额 ' . $refund_money . '<BR>';

            }elseif ($refund_type == 'apply'){

                //判断申请退款单是否已经存在
                if ($refund_apply['apply_id'] && $status == '0' && ($refund_apply['memo'] == $refund_sdf['memo']) && ($refund_apply['money'] == $refund_money) ){
                    $logInfo .= '申请退款单是否已经存在 且status等于0，返回值为:<BR>' . var_export($rs_data, true) . '<BR>';
                    $rs['logInfo'] = $logInfo;
                    $rs['rsp'] = 'success';
                    return $rs;
                }


                #bugfix:修复退款申请更新时，因为之前一次status为0没收到造成退款申请没有创建。

                if ($status == '0' || empty($refund_apply['apply_id']) ){
                    $addon = serialize(array('refund_bn'=>$refund_bn));
                    $sdf = array(
                        'order_id' => $order_id,
                        'refund_apply_bn' => $refund_bn,
                        'pay_type' => $refund_sdf['pay_type']?$refund_sdf['pay_type']:'online',
                        'account' => $refund_sdf['account'],
                        'bank' => $refund_sdf['bank'],
                        'pay_account' => $refund_sdf['pay_account'],
                        'money' => $refund_money?$refund_money:'0',
                        'refunded' => '0',
                        'memo' => $refund_sdf['memo'],
                        'create_time' => $refund_sdf['t_ready'],
                        'status' => $status,
                        'shop_id' => $shop_id,
                        'addon' => $addon,
                    );


                    $refund_applyObj->create_refund_apply($sdf);

                    $logInfo .= '创建退款申请单，参数值为:<BR>' . var_export($sdf, true) . '<BR>';

                }else{
                    $refund_apply_update = array(
                       'status' => $status,
                    );
                    $refund_apply_filter = array(
                       'shop_id' => $shop_id,
                       'memo'    => $refund_sdf['memo'],
                       'money'    => $refund_sdf['money'],
                       'refund_apply_bn' => $refund_bn,
                    );
					$refund_applyObj->update($refund_apply_update, $refund_apply_filter);
                    $logInfo .= '更新退款申请单状态，参数值为:<BR>' . var_export($refund_apply_update, true) . '<BR>' . var_export($refund_apply_filter, true) . '<BR>';
                }

                //更新订单支付状态
                kernel::single('ome_order_func')->update_order_pay_status($order_id);

                $logInfo .= '更新订单：' . $order_id . '支付状态<BR>';

            }

            $logInfo .= '返回值为:<BR>' . var_export($rs_data, true) . '<BR>';
            $rs['logInfo'] = $logInfo;
            $rs['rsp'] = 'success';
            return $rs;
        }else{
            $msg = '退款单号或订单号不能为空';
            $rs['msg'] = $msg;
            $rs['logInfo'] = $logInfo;
            return $rs;
        }

    }

    /**
     * 更新退款单状态
     * @access public
     * @param array $status_sdf 退款单状态数据
     */
    function status_update($status_sdf){

        $status = $status_sdf['status'];
        $refund_bn = $status_sdf['refund_bn'];
        $order_bn = $status_sdf['order_bn'];

        $logTitle = '前端店铺更新退款单状态[order_bn:'. $order_bn . ']';
        $logInfo = '前端店铺更新退款单状态：<BR>';
        $logInfo .= '接收参数 $status_sdf 信息：' . var_export($status_sdf, true) . '<BR>';

        //返回值
        $rs_data = array('tid'=>$order_bn,'refund_id'=>$refund_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logTitle'=>$logTitle,'logInfo'=>'');

        //状态值判断
        if ($status==''){
            // 行为日志
            $msg = '状态值不正确';
            $logInfo .=$msg;
            $rs['logInfo'] = $logInfo;
            $rs['msg'] = $msg;
            return $rs;
        }
        if ($refund_bn!='' and $order_bn!=''){

            $shop_id = $status_sdf['shop_id'];
            $orderObj = &app::get('ome')->model('orders');
            $refundObj = &app::get('ome')->model('refunds');
            $order_detail = $orderObj->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn), 'order_id');
            $refund_detail = $refundObj->dump(array('refund_bn'=>$refund_bn,'shop_id'=>$shop_id));

            $order_id = $order_detail['order_id'];
            if ($status=="succ"){//已支付

                //更新退款单状态
                $filter = array('refund_bn'=>$refund_bn,'shop_id'=>$shop_id);
                $data = array('status'=>$status);
                $refundObj->update($data, $filter);
            }

            $result['rsp'] = 'success';

            $rs['rsp'] = 'success';
            return $rs;
        }else{
            // 行为日志
            $msg = '退款单号或订单号不能为空';
            $logInfo .= $msg;
            $rs['logInfo'] = $logInfo;
            $rs['msg'] = $msg;
            return $rs;
        }
    }

    /**
     * 更新订单状态及金额
     * @access private
     * @param string order_id
     * @param string shop_id
     * @param money refund_money
     * @return boolean
     */
    protected function _updateOrder($order_id, $refund_money){
        if (empty($order_id)) return false;

        //更新订单支付金额
        if ($refund_money){
            $sql ="update sdb_ome_orders set payed=IF((CAST(payed AS char)-IFNULL(0,cost_payment)-".$refund_money.")>=0,payed-IFNULL(0,cost_payment)-".$refund_money.",0)  where order_id=".$order_id;
            kernel::database()->exec($sql);
            //kernel::single('ome_order')->_update_status($order_id);

            //更新订单支付状态
            if (kernel::single('ome_order_func')->update_order_pay_status($order_id)){
                return true;
            }else{
                return false;
            }
        }
    }


}