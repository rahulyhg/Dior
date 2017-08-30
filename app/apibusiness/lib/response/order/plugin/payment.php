<?php
/**
* 支付单插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: payment.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_payment extends apibusiness_response_order_plugin_abstract
{
    const _APP_NAME = 'ome';

    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        # 恢复支付单
        if($instance = kernel::service('ome_apibusiness_data_restore.payment')){
            if(method_exists($instance, 'add')){
                $instance->add($this->_platform->_newOrder['order_bn']);
            }
        }

        # 支付单结构
        $payment_list = isset($this->_platform->_ordersdf['payments']) ? $this->_platform->_ordersdf['payments'] : array($this->_platform->_ordersdf['payment_detail']);
        # 支付单存在且订单是已支付创建支付单(或者一号店订单，货到付款，部分支付的,创建支付单)
        if (($payment_list && $this->_platform->_ordersdf['pay_status'] == '1') || ('true' == $this->_platform->_ordersdf['shipping']['is_cod'] && $this->_platform->_ordersdf['shop_type'] =='yihaodian' && $this->_platform->_ordersdf['pay_status'] == '3')) {
            //$paymentObj = kernel::single('apibusiness_adapter_payments');
            $paymentObj = app::get(self::_APP_NAME)->model('payments');

            $payment_cfg = kernel::single('apibusiness_adapter_payment_cfg')->get_payment($this->_platform->_ordersdf['pay_bn'],$this->_platform->_shop['node_type']);
            foreach ($payment_list as $payment) {
                $payment['op_name'] = trim($payment['op_name']);
                if($payment['op_name']){
                    #查询支付单上的操作人,是否能在系统找到
                    $obj_pam = &app::get('pam')->model('account');
                    $account_info = $obj_pam->getList('account_id',array('login_name' => $payment['op_name']));
                    if(!empty( $account_info[0])){  
                         $payment['op_id'] =  $account_info[0]['account_id'];
                    }
                }
                if(!$payment['pay_time']) $payment['pay_time'] = time();

                $t_begin = $t_end = kernel::single('ome_func')->date2time($payment['pay_time']);
                
                if ($payment['trade_no'] === 'null') unset($payment['trade_no']);

                $paymentsdf = array(
                    'payment_bn'    => $payment['trade_no'] ? $payment['trade_no'] : $paymentObj->gen_id(),
                    'shop_id'       => $this->_platform->_newOrder['shop_id'],
                    'order_id'      => $this->_platform->_newOrder['order_id'],
                    'account'       => $payment['account'],
                    'bank'          => $payment['bank'],
                    'pay_account'   => $payment['pay_account'],
                    'current'       => 'CNY',
                    'money'         => (float)$payment['money'],
                    'paycost'       => $payment['paycost'],
                    'cur_money'     => (float)$payment['money'],
                    'pay_type'      => $payment_cfg['pay_type'],
                    'payment'       => $payment_cfg['id'],
                    'pay_bn'        => $payment_cfg['pay_bn'],
                    'paymethod'     => $payment['paymethod'],
                    't_begin'       => $t_begin ? $t_begin : time(),
                    't_end'         => $t_end ? $t_end : time(),
                    'download_time' => time(),
                    'status'        => 'succ',
                    'trade_no'      => $payment['trade_no'],
                    'memo'          => $payment['memo'],
                    'op_id'         => $payment['op_id'] ? $payment['op_id'] : '',
                    'op_name'       => $payment['op_name'] ? $payment['op_name'] : $this->_platform->_shop['node_type'],
                );

                $paymentObj->insert($paymentsdf);

                $this->_platform->_apiLog['info'][] = '支付单标准$sdf结构：'.var_export($paymentsdf,true);
            }
        }
    }

    /**
     * 更新完成后操作
     *
     * @return void
     * @author 
     **/
    public function postUpdate()
    {
        if (!$this->_platform->_ordersdf['payments']) return false;
        $paymentModel = app::get(self::_APP_NAME)->model('payments');
        $tgPayments = $paymentModel->getList('*',array('order_id'=>$this->_platform->_tgOrder['order_id']));

        $tg_payments_bn = array();
        if ($tgPayments) {
            foreach ($tgPayments as $key => $value) {
                $tg_payments_bn[] = $value['payment_bn'];
            }
        }
        $this->_platform->_tgOrder['payments'] = $tgPayments;
        unset($tgPayments);

        $add_payments = false;
        foreach ($this->_platform->_ordersdf['payments'] as $payment) {
            if(in_array($payment['trade_no'], $tg_payments_bn)) continue;

            $pay_time = $payment['pay_time'] ? kernel::single('ome_func')->date2time($payment['pay_time']) : time();

            $payment['op_name'] = trim($payment['op_name']);
            if($payment['op_name']){
                #查询支付单上的操作人,是否能在系统找到
                $rs = &app::get('desktop')->model('users')->check_name($payment['op_name']);
                if(!$rs){
                    #如果系统没找到，把数据清掉
                    unset($payment['op_name'],$payment['id']);
                }
            }
            $paymentsdf = array(
                'payment_bn'    => $payment['trade_no'],
                'shop_id'       => $this->_platform->_shop['shop_id'],
                'order_id'      => $this->_platform->_tgOrder['order_id'],
                'account'       => $payment['account'],
                'bank'          => $payment['bank'],
                'pay_account'   => $payment['pay_account'],
                'money'         => (float)$payment['money'],
                'paycost'       => $payment['paycost'],
                'payment'       => $payment['payment'],
                'pay_bn'        => $payment['pay_bn'],
                'pay_type'      => $payment['pay_type'],
                'paymethod'     => $payment['paymethod'],
                't_begin'       => $pay_time,
                't_end'         => $pay_time,
                'download_time' => time(),
                'status'        => 'succ',
                'memo'          => $payment['memo'],
                'trade_no'      => $payment['trade_no'],
                'op_id'         => $payment['op_id'] ? $payment['op_id'] : '',
                'op_name'       => $payment['op_name'] ? $payment['op_name'] : $this->_platform->_shop['node_type'],
            );

            // 支付方式
            if ($payment['pay_bn']) {
                $paymentCfg = kernel::single('apibusiness_adapter_payment_cfg')->get_payment($payment['pay_bn'],$this->_platform->_shop['node_type']);
                $paymentsdf['payment'] = $paymentCfg['id'];
                $paymentsdf['pay_type'] = $paymentCfg['pay_type'];
            }

            $paymentModel->insert($paymentsdf);

            $this->_platform->_apiLog['info'][] = '支付单标准SDF结构：'.var_export($paymentsdf,true);

            $add_payments = true;
        }
        if ($add_payments) {
            $logModel = app::get(self::_APP_NAME)->model('operation_log');
            $logModel->write_log('order_pay@ome',$this->_platform->_tgOrder['order_id'],'支付单添加');            
        }
    }
}