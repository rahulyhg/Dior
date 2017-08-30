<?php
/**
 * 前端店铺支付业务处理接口
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_payment extends ome_rpc_response
{

    /**
     * 添加支付单
     * @access public
     * @param array $payment_sdf 付款单标准结构数据
     * @param object $responseObj 框架API接口实例化对象
     * @return array('payment_id'=>'付款单主键ID')
     */
    public function add($payment_sdf, &$responseObj) {

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'payment','add',$payment_sdf);
        
        $data = array('tid'=>$rs['data']['tid'],'payment_id'=>$rs['data']['payment_id'],'retry'=>$rs['data']['retry']);
        
        $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo']);

        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $this->send_user_error(app::get('base')->_($rs['msg']), $data);
            exit;
        }

        exit;
        /*
        $log = &app::get('ome')->model('api_log');
        $logTitle = '前端店铺支付业务处理接口[订单：]' . $payment_sdf['order_bn'];
        $logInfo = '前端店铺支付业务处理接口：<BR>';
        $logInfo .= '接收参数 $payment_sdf 信息：' . var_export($payment_sdf, true) . '<BR>';

        if ($responseObj) {
            $shop_id = $this->get_shop_id($responseObj);
        } else {
            $shop_id = $payment_sdf['shop_id'];
        }
        $logInfo .= '店铺ID：' . $shop_id . '<BR>';

        $status = $payment_sdf['status'];
        $payment_money = $payment_sdf['money'];
        $payment_bn = $payment_sdf['payment_bn'];
        $order_bn = $payment_sdf['order_bn'];

        //返回值
        $return_value = array('tid' => $order_bn, 'payment_id' => $payment_bn, 'retry' => 'false');

        $paymentObj = &app::get('ome')->model('payments');
        $orderObj = &app::get('ome')->model('orders');
        $shopObj = &app::get('ome')->model('shop');
        $oApi_log = &app::get('ome')->model('api_log');
        $order_detail = $orderObj->dump(array('shop_id' => $shop_id, 'order_bn' => $order_bn), 'pay_status,status,process_status,order_id,payed,total_amount');
        $shop_detail = $shopObj->dump($shop_id, 'name,node_type');
        $shop_name = $shop_detail['name'];
        $shop_type = $shop_detail['node_type'];

        $logInfo .= '订单信息：' . var_export($order_detail, true) . '<BR>';
        $logInfo .= '店铺信息：' . var_export($shop_detail, true) . '<BR>';

        //前端店铺发起新建支付单
        $c2c_shop = ome_shop_type::shop_list();
        if (!in_array($shop_type, $c2c_shop)) {
            //判断支付单号是否为空
            if (empty($payment_bn)) {
                $msg = 'payment_bn not allow empty ';
                $logInfo .= '信息：' . $msg . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                } else {
                    return $msg;
                }
            }
            //判断订单是否存在
            if (empty($order_detail['order_id'])) {
                $msg = 'order_bn incorrect ';
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($return_value, true) . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $return_value['retry'] = 'true';
                    $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                } else {
                    return $msg;
                }
            }
            //状态值判断
            if (empty($status)) {
                $msg = 'Status field value ' . $status . ' is not correct';
                $logInfo .= '信息：' . $msg . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $responseObj->send_user_error(app::get('base')->_($msg), '');
                } else {
                    return $msg;
                }
            }
            //判断支付单是否已经存在
            if ($paymentObj->dump(array('payment_bn' => $payment_sdf['payment_bn'], 'shop_id' => $shop_id))) {
                $logInfo .= '店铺ID为：' . $shop_id . ' 的支付单已存在<BR>';
                $logInfo .= '返回值为：' . var_export($return_value, true) . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                return $return_value;
            }

            //支付金额判断
            if ($payment_money <= 0) {
                $msg = 'Money field value is not correct';
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($return_value, true) . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                } else {
                    return $msg;
                }
            }
            //当前支付金额+已支付金额  > 订单总金额
            $payed = (float)($payment_money + $order_detail['payed']);
            if (bccomp($payed, $order_detail['total_amount'], 3)==1) {
                //日志记录
                $api_filter = array('marking_value' => $payment_sdf['payment_bn'], 'marking_type' => 'payment_money');
                $api_detail = $oApi_log->dump($api_filter, 'log_id');
                if (empty($api_detail['log_id'])) {
		        	$msg = '支付金额('.$payment_money.')+已支付金额('.$order_detail['payed'].')  > 订单总金额('.$order_detail['total_amount'].')';
		        	$log_title = '店铺('.$shop_name.')添加支付单:'.$payment_sdf['payment_bn'].',订单号:'.$order_bn;
                    $addon = $api_filter;

                    $log_id = $oApi_log->gen_id();
                    $oApi_log->write_log($log_id, $log_title, __CLASS__, __FUNCTION__, '', '', 'response', 'fail', $msg, $addon);
                }
                $msg = 'payment money abnormal';

                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($return_value, true) . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                } else {
                    return $msg;
                }
            }

        } else {
            //自身发起新建支付单
            $payment_bn = $paymentObj->gen_id();

            $logInfo .= '自身发起新建支付单<BR>';
        }
        if ($payment_bn != '' and $order_bn != '') {

            if ($responseObj) {
                $shop_id = $this->get_shop_id($responseObj);
            }

            //判断订单是否:部分退款\全部退款\全部支付
            if (in_array($order_detail['pay_status'], array('1', '5'))) {
                //日志记录
                $api_filter = array('marking_value' => $payment_bn . $order_detail['pay_status'], 'marking_type' => 'payment_pay_status');
                $api_detail = $oApi_log->dump($api_filter, 'log_id');

                if (empty($api_detail['log_id'])) {
                    $msg = $order_detail['order_bn'] . '订单已退款或已支付';
                    $log_title = '店铺(' . $shop_name . ')' . $msg . '[订单：]' . $order_bn;

                    $addon = $api_filter;

                    $log_id = $oApi_log->gen_id();
                    $oApi_log->write_log($log_id, $log_title, __CLASS__, __FUNCTION__, '', '', 'response', 'fail', $msg, $addon);
                }

                $msg = 'Order status: ' . $order_detail['pay_status'] . ',can not pay';
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($return_value, true) . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                } else {
                    return $msg;
                }
            }

            //判断订单状态是否为活动订单
            if ($order_detail['status'] != 'active') {
                $msg = 'Order status is not active,can not pay';
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($return_value, true) . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                } else {
                    return $msg;
                }
            }
            //判断订单确认状态
            if ($order_detail['process_status'] == 'cancel') {
                //日志记录
                $api_filter = array('marking_value' => $payment_bn . $order_detail['status'], 'marking_type' => 'payment_status');
                $api_detail = $oApi_log->dump($api_filter, 'log_id');

                if (empty($api_detail['log_id'])) {
                    $msg = $order_detail['order_bn'] . '订单已取消';
                    $log_title = '店铺(' . $shop_name . ')添加支付单' . $msg;
                    $log_id = $oApi_log->gen_id();

                    $addon = $api_filter;

                    $oApi_log->write_log($log_id, $log_title, __CLASS__, __FUNCTION__, '', '', 'response', 'fail', $msg, $addon);
                }
                $msg = 'Order is cancel，can not pay';
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($return_value, true) . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                } else {
                    return $msg;
                }
            }

            // 多张支付单合法性校验，求和，如果与不大于总共金额，则允许创建，否则返回不合法
            $paymentsByOrder = $paymentObj->getList('cur_money',array('order_id'=>$order_detail['order_id']));
            $hasPay = 0;
            if (is_array($paymentsByOrder)) {
                foreach ($paymentsByOrder as $p) {
                    $hasPay += floatval($p['cur_money']);
                }
            }
            $totalHasPay = (float)($hasPay + $payment_money);
            if (bccomp($totalHasPay, $order_detail['total_amount'], 3)==1) {
                $logInfo .= '$hasPay=' . var_export($hasPay, true) . '<BR>';

                $msg = '支付金额('.($totalHasPay).')大于订单金额('.($order_detail['total_amount']).')，支付失败';

                $logInfo .= $msg . '<BR>';
                $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

                if ($responseObj) {
                    $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                } else {
                    return $msg;
                }
            }

            $order_id = $order_detail['order_id'];
            $payment_sdf['t_begin'] = kernel::single('ome_func')->date2time($payment_sdf['t_begin']);
            $payment_sdf['t_end'] = kernel::single('ome_func')->date2time($payment_sdf['t_end']);
            $pay_bn = $payment_sdf['payment'];

            if ($pay_bn) {
                $payment_cfgObj = &app::get('ome')->model('payment_cfg');
                $payment_cfg = $payment_cfgObj->dump(array('pay_bn' => $pay_bn), 'id');
                $payment_sdf['payment'] = $payment_cfg['id'];
            }

            $sdf = array(
                'payment_bn' => $payment_bn,
                'shop_id' => $shop_id,
                'order_id' => $order_id,
                'account' => $payment_sdf['account'],
                'bank' => $payment_sdf['bank'],
                'pay_account' => $payment_sdf['pay_account'],
                'currency' => $payment_sdf['currency'] ? $payment_sdf['currency'] : 'CNY',
                'money' => $payment_money ? $payment_money : '0',
                'paycost' => $payment_sdf['paycost'],
                'cur_money' => $payment_sdf['cur_money'] ? $payment_sdf['cur_money'] : '0',
                'pay_type' => $payment_sdf['pay_type'] ? $payment_sdf['pay_type'] : 'online',
                'payment' => $payment_sdf['payment'],
                'pay_bn' => $pay_bn,
                'paymethod' => $payment_sdf['paymethod'],
                't_begin' => $payment_sdf['t_begin'] ? $payment_sdf['t_begin'] : time(),
                'download_time' => time(),
                't_end' => $payment_sdf['t_end'] ? $payment_sdf['t_end'] : time(),
                'status' => $status,
                'memo' => $payment_sdf['memo'],
                'is_orderupdate' => 'true',
                'trade_no' => $payment_sdf['trade_no']
            );
            $paymentObj->create_payments($sdf);

            // 更新订单的支付方式
            $orderObj -> update(array('payment' => $payment_sdf['paymethod']),array('order_id'=>$order_id));

            $logInfo .= '返回值：' . var_export($return_value, true) . '<BR>';
            $logInfo .= '$sdf 值信息：' . var_export($sdf, true) . '<BR>';
            $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo);

            if ($sdf['payment_id']) {
                return $return_value;
            } else {
                $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
            }

        } else {
            $msg = 'payment_bn and Order_bn can not be empty';
            $logInfo .= '信息：' . $msg . '<BR>';
            $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'fail', $logInfo);

            if ($responseObj) {
                $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
            } else {
                return $msg;
            }
        }

        $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo);
        */
    }

    /**
     * 更新付款单状态
     * @access public
     * @param array $status_sdf 付款单状态标准结构数据
     * @param object $responseObj 框架API接口实例化对象
     */
    public function status_update($status_sdf, &$responseObj) {

        $log = &app::get('ome')->model('api_log');

        $node_id = base_rpc_service::$node_id;
        $rs = kernel::single('ome_rpc_mapper')->response_router($node_id,'payment','status_update',$status_sdf);
        
        $data = array('tid'=>$rs['data']['tid'],'payment_id'=>$rs['data']['payment_id'],'retry'=>$rs['data']['retry']);
        
        $log->write_log($log->gen_id(), $rs['logTitle'], __CLASS__, __METHOD__, '', '', 'response', $rs['rsp'], $rs['logInfo']);

        if($rs['rsp'] == 'success'){
            return $data;
        }else{
            $this->send_user_error(app::get('base')->_($rs['msg']), $data);
            exit;
        }

        exit;
/*
        $log = &app::get('ome')->model('api_log');
        $logTitle = '更新付款单状态接口[订单：]' . $status_sdf['order_bn'];
        $logInfo = '更新付款单状态：<BR>';
        $logInfo .= '接收参数 $status_sdf 信息：' . var_export($status_sdf, true) . '<BR>';

        $status = $status_sdf['status'];
        $payment_bn = $status_sdf['payment_bn'];
        $order_bn = $status_sdf['order_bn'];

        //返回值
        $return_value = array('tid' => $order_bn, 'payment_id' => $payment_bn);

        //状态值判断
        if ($status == '') {
            $logInfo .= '状态值为空!<BR>';
            $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo);

            $responseObj->send_user_error(app::get('base')->_('Status field value is not correct'), $return_value);
        }
        if ($payment_bn != '' and $order_bn != '') {

            $shop_id = $this->get_shop_id($responseObj);
            $orderObj = &app::get('ome')->model('orders');
            $paymentObj = &app::get('ome')->model('payments');
            $order_detail = $orderObj->dump(array('shop_id' => $shop_id, 'order_bn' => $order_bn), 'order_id');
            $payment_detail = $paymentObj->dump(array('payment_bn' => $payment_bn, 'shop_id' => $shop_id));

            $logInfo .= '店铺ID：' . var_export($shop_id, true) . '<BR>';

            $order_id = $order_detail['order_id'];
            if ($status == "succ") {//已支付
                $logInfo .= '已支付<BR>';

                //更新支付单状态
                $filter = array('payment_bn' => $payment_bn, 'shop_id' => $shop_id);
                $data = array('status' => $status);
                $paymentObj->update($data, $filter);

                $logInfo .= '更新支付单成功<BR>';

                //更新订单状态
                $paymentObj->_updateOrder($order_id, $shop_id, $payment_detail['money']);

                $logInfo .= '更新订单成功<BR>';
            }

            $logInfo .= '返回值：' . var_export($return_value, true) . '<BR>';
            $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo);

            return $return_value;

        } else {
            $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo);

            $responseObj->send_user_error(app::get('base')->_('payment_bn and Order_bn can not be empty'), $return_value);
        }

        $log->write_log($log->gen_id(), $logTitle, __CLASS__, __METHOD__, '', '', 'response', 'success', $logInfo);
    */

    }

}