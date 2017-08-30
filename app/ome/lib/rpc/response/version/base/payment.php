<?php
/**
 * 前端店铺支付业务处理接口
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_version_base_payment extends ome_rpc_response
{

    /**
     * 添加支付单
     * @access public
     * @param array $payment_sdf 付款单标准结构数据
     * @return array('payment_id'=>'付款单主键ID')
     */
    public function add($payment_sdf){
        $shop_id = $payment_sdf['shop_id'];
        $status = $payment_sdf['status'];
        $payment_money = $payment_sdf['money'];
        $payment_bn = $payment_sdf['payment_bn'];
        $order_bn = $payment_sdf['order_bn'];

        /*Log info*/
        $log = &app::get('ome')->model('api_log');
        $logTitle = '前端店铺支付业务处理接口[订单：]' . $payment_sdf['order_bn'];
        $logInfo = '前端店铺支付业务处理接口：<BR>';
        $logInfo .= '接收参数 $payment_sdf 信息：' . var_export($payment_sdf, true) . '<BR>';
        $logInfo .= '店铺ID：' . $shop_id . '<BR>';
        /*Log info*/

        //返回值
        $rs_data = array('tid'=>$order_bn,'payment_id'=>$payment_bn,'retry'=>'false');
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logTitle'=>$logTitle,'logInfo'=>'');

        $paymentObj = &app::get('ome')->model('payments');
        $orderObj = &app::get('ome')->model('orders');
        //$temporaryObj = &app::get('ome')->model('rpc_temporary');
        $order_detail = $orderObj->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn), 'pay_status,status,process_status,order_id,payed,total_amount');
        $shop_name = $payment_sdf['shop_name'];
        $shop_type = $payment_sdf['shop_type'];
        $shop_detail = array('name'=>$shop_name,'node_type'=>$shop_type);

        $logInfo .= '订单信息：' . var_export($order_detail, true) . '<BR>';
        $logInfo .= '店铺信息：' . var_export($shop_detail, true) . '<BR>';

/*
        $writelog = array(
            'method' => 'ome.payment.add',
            'log_type' => 'store.trade.payment',
            'title' => '支付单添加('.$shop_name.')'
        );
        $result['rsp'] = 'fail';
*/

        //前端店铺发起新建支付单
        $c2c_shop = ome_shop_type::shop_list();
        if (!in_array($shop_type, $c2c_shop)){
            //判断支付单号是否为空
            if(empty($payment_bn)){
                $msg = '支付单号不能为空';
                /*
                $result['msg'] = $msg;
                $log_addon = array(
                    'bn' => $payment_bn,
                    'unique' => $msg,
                );
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
                */
                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                return $rs;
            }
            //判断订单是否存在
            if(empty($order_detail['order_id'])){
                $msg = '订单号不存在';

                /*
                $result['msg'] = $msg;
                $log_addon = array(
                    'bn' => $payment_bn,
                    'unique' => $msg,
                );
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);

                // 存储到临时表
                $temporaryObj = &app::get('ome')->model('rpc_temporary');
                if ( !$temporaryObj->dump(array('order_bn'=>$order_bn,'original_bn'=>$payment_bn)) ){
                    $payment_sdf['shop_id'] = $shop_id;
                    $tmp_params = $payment_sdf;
                    $rpc_data = array(
                        'order_bn' => $order_bn,
                        'type' => 'payment',
                        'original_bn'=> $payment_bn,
                        'params' => $tmp_params,
                    );
                    $temporaryObj->save($rpc_data);
                }
                */
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($rs_data, true) . '<BR>';
                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                $rs['retry'] = 'true';
                $rs['rsp'] = 'success';
                return $rs;
            }
            //状态值判断
            if (empty($status)){
                $msg = '支付单状态值:'.$status.'不正确';
                $logInfo .= '信息：' . $msg . '<BR>';
                /*
                $log_addon = array(
                    'bn' => $payment_bn,
                    'unique' => $msg,
                );
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
                */
                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                return $rs;
            }
            //判断支付单是否已经存在
            if($paymentObj->dump(array('payment_bn'=>$payment_sdf['payment_bn'],'shop_id'=>$shop_id))){
                $logInfo .= '店铺ID为：' . $shop_id . ' 的支付单已存在<BR>';
                $logInfo .= '返回值为：' . var_export($rs_data, true) . '<BR>';
                $rs['logInfo'] = $logInfo;
                $rs['rsp'] = 'success';
                return $rs;
            }

            //支付金额判断
            if ($payment_money <= 0) {
                $msg = '支付金额不正确';
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($return_value, true) . '<BR>';

                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                return $rs;
            }

            //当前支付金额+已支付金额  > 订单总金额
            $payed = (float)($payment_money + $order_detail['payed']);
            if (bccomp($payed, $order_detail['total_amount'], 3)==1){

                $msg = '支付金额('.$payment_money.')+已支付金额('.$order_detail['payed'].')  > 订单总金额('.$order_detail['total_amount'].')';
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($rs_data, true) . '<BR>';

                /*
                $api_filter = array('marking_value' => $payment_sdf['payment_bn'], 'marking_type' => 'payment_money');
                $api_detail = $log->dump($api_filter, 'log_id');
                if (empty($api_detail['log_id'])) {
                    $msg = '支付金额('.$payment_money.')+已支付金额('.$order_detail['payed'].')  > 订单总金额('.$order_detail['total_amount'].')';
                    $log_title = '店铺('.$shop_name.')添加支付单:'.$payment_sdf['payment_bn'].',订单号:'.$order_bn;
                    $addon = $api_filter;

                    $log_id = $log->gen_id();
                    $log->write_log($log_id, $log_title, 'ome_rpc_response_payment', 'add', '', '', 'response', 'fail', $msg, $addon,'api.store.trade.payment',$order_bn);
                }
                */
                //$msg = 'payment money abnormal';

                //$logInfo .= '信息：' . $msg . '<BR>';
                //$logInfo .= '返回值信息：' . var_export($rs_data, true) . '<BR>';

                /*
                $result['msg'] = $msg;
                $log_addon = array(
                    'bn' => $payment_bn,
                    'unique' => $msg,
                );
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
                */
                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                return $rs;
            }

        }else{
            //自身发起新建支付单
            $payment_bn = $paymentObj->gen_id();

            $logInfo .= '自身发起新建支付单<BR>';

        }
        if ($payment_bn!='' and $order_bn!=''){

            //判断订单是否:部分退款\全部退款\全部支付
            if (in_array($order_detail['pay_status'],array('1','5'))){

                $msg = $order_detail['order_bn'].'订单已退款或已支付';

                //日志记录
                /*
                $api_filter = array('marking_value' => $payment_bn . $order_detail['pay_status'], 'marking_type' => 'payment_pay_status');
                $api_detail = $log->dump($api_filter, 'log_id');

                if (empty($api_detail['log_id'])) {
                    $log_title = '店铺(' . $shop_name . ')' . $msg . '[订单：]' . $order_bn;

                    $addon = $api_filter;

                    $log_id = $log->gen_id();
                    $log->write_log($log_id, $log_title, 'ome_rpc_response_payment', 'add', '', '', 'response', 'fail', $msg, $addon,'api.store.trade.payment',$order_bn);
                }
                */
                /*$result['msg'] = $msg;
                $log_addon = array(
                    'bn' => $payment_bn,
                    'unique' => $msg,
                );
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
                */

                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($rs_data, true) . '<BR>';
                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                return $rs;
            }
            //判断订单状态是否为活动订单
            if ($order_detail['status']!='active'){
                $msg = '订单状态非活动,无法支付';
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($rs_data, true) . '<BR>';
                /*
                $log_addon = array(
                    'bn' => $payment_bn,
                    'unique' => $msg,
                );
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
                */
                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                return $rs;
            }
            //判断订单确认状态
            if ($order_detail['process_status']=='cancel'){

                $msg = $order_detail['order_bn'].'订单已取消';
                //日志记录
                /*
                $api_filter = array('marking_value' => $payment_bn . $order_detail['status'], 'marking_type' => 'payment_status');
                $api_detail = $log->dump($api_filter, 'log_id');

                if (empty($api_detail['log_id'])) {
                    $log_title = '店铺(' . $shop_name . ')添加支付单' . $msg;
                    $log_id = $log->gen_id();

                    $addon = $api_filter;

                    $log->write_log($log_id, $log_title, 'ome_rpc_response_payment', 'add', '', '', 'response', 'fail', $msg, $addon,'api.store.trade.payment',$order_detail['order_bn']);
                }
                */
                /*
                $result['msg'] = $msg;
                $log_addon = array(
                    'bn' => $payment_bn,
                    'unique' => $msg,
                );
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
                */
                $logInfo .= '信息：' . $msg . '<BR>';
                $logInfo .= '返回值信息：' . var_export($rs_data, true) . '<BR>';
                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                return $rs;
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

                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;

                return $rs;
            }


            $order_id = $order_detail['order_id'];
            $payment_sdf['t_begin'] = kernel::single('ome_func')->date2time($payment_sdf['t_begin']);
            $payment_sdf['t_end'] = kernel::single('ome_func')->date2time($payment_sdf['t_end']);
            $pay_bn = $payment_sdf['payment'];
            if ($pay_bn){
                $payment_cfgObj = &app::get('ome')->model('payment_cfg');
                $payment_cfg = $payment_cfgObj->dump(array('pay_bn'=>$pay_bn), 'id');
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

            if($sdf['payment_id']){
                // 行为日志
                /*
                $log_addon = array(
                    'bn' => $payment_bn,
                );
                $result['rsp'] = 'success';
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
                */
                $rs['rsp'] = 'success';
                return $rs;
            }else{
                $msg = '插入支付单失败!';
                $logInfo .= '信息：' . $msg . '<BR>';
                /*
                $result['msg'] = $msg;
                $log_addon = array(
                    'bn' => $payment_bn,
                    'unique' => $msg,
                );
                $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
                */

                $rs['logInfo'] = $logInfo;
                $rs['msg'] = $msg;
                return $rs;
            }

        }else{
            $msg = '支付单号和订单号不能为空';
            $logInfo .= '信息：' . $msg . '<BR>';
            /*
            $result['msg'] = $msg;
            $log_addon = array(
                'bn' => $payment_bn,
                'unique' => $msg,
            );
            $this->action_log($writelog['method'],$payment_sdf,$writelog['title'],$writelog['log_type'],$result,$log_addon);
            */
            $rs['logInfo'] = $logInfo;
            $rs['msg'] = $msg;
            return $rs;
        }

        $rs['rsp'] = 'success';
        return $rs;

    }

    /**
     * 更新付款单状态
     * @access public
     * @param array $status_sdf 付款单状态标准结构数据
     */
    public function status_update($status_sdf){
        $status = $status_sdf['status'];
        $payment_bn = $status_sdf['payment_bn'];
        $order_bn = $status_sdf['order_bn'];

        /* Log info*/
        $log = &app::get('ome')->model('api_log');
        $logTitle = '更新付款单状态接口[订单：]' . $status_sdf['order_bn'];
        $logInfo = '更新付款单状态：<BR>';
        $logInfo .= '接收参数 $status_sdf 信息：' . var_export($status_sdf, true) . '<BR>';
        /* Log info*/

        //返回值
        $rs_data = array('tid'=>$order_bn,'payment_id'=>$payment_bn);
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$rs_data,'logTitle'=>$logTitle,'logInfo'=>'');


        //状态值判断
        if ($status==''){
            // 行为日志
            $msg = '状态值不正确';
            $logInfo .= $msg;
            $rs['logInfo'] = $logInfo;
            $rs['msg'] = $msg;
            return $rs;
        }
        if ($payment_bn!='' and $order_bn!=''){

            $shop_id = $status_sdf['shop_id'];
            $orderObj = &app::get('ome')->model('orders');
            $paymentObj = &app::get('ome')->model('payments');
            $order_detail = $orderObj->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn), 'order_id');
            $payment_detail = $paymentObj->dump(array('payment_bn'=>$payment_bn,'shop_id'=>$shop_id));

            $logInfo .= '店铺ID：' . var_export($shop_id, true) . '<BR>';

            $order_id = $order_detail['order_id'];
            if ($status=="succ"){//已支付
                $logInfo .= '已支付<BR>';
                //更新支付单状态
                $filter = array('payment_bn'=>$payment_bn,'shop_id'=>$shop_id);
                $data = array('status'=>$status);
                $paymentObj->update($data, $filter);
                $logInfo .= '更新支付单成功<BR>';

                //更新订单状态
                $paymentObj->_updateOrder($order_id,$shop_id,$payment_detail['money']);
                $logInfo .= '更新订单成功<BR>';
            }


            $rs['logInfo'] = $logInfo;

            $rs['rsp'] = 'success';
            return $rs;
        }else{
            // 行为日志
            $msg = '支付单号或订单号不能为空';
            $logInfo .=$msg;
            $rs['logInfo'] = $logInfo;
            $rs['msg'] = $msg;
            return $rs;
        }
    }

}