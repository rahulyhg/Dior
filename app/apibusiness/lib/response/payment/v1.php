<?php
/**
* 支付单 版本一
*
* @category apibusiness
* @package apibusiness/response/payment
* @author chenping<chenping@shopex.cn>
* @version $Id: v1.php 2013-3-12 17:23Z
*/
class apibusiness_response_payment_v1 extends apibusiness_response_payment_abstract
{
    public function add()
    {
        parent::add();

        $shop_id    = $this->_shop['shop_id'];
        $payment_bn = $this->_paymentsdf['payment_bn'];
        $order_bn   = $this->_paymentsdf['order_bn'];

        // 支付单号是否存在
        if (empty($this->_paymentsdf['payment_bn'])) {
            $this->_apiLog['info']['msg'] = '返回值：支付单号不能为空';
            $this->exception(__METHOD__);
        }

        // 支付单状态
        if ($this->_paymentsdf['status'] == '') {
            $this->_apiLog['info']['msg'] = '支付单状态值不能为空';
            $this->exception(__METHOD__);
        }

        // 支付单是否存在
        $paymentModel = app::get(self::_APP_NAME)->model('payments');
        $tgPayments = $paymentModel->dump(array('payment_bn'=>$payment_bn,'shop_id'=>$shop_id));
        if ($tgPayments) {
            $this->_apiLog['info']['msg'] = '返回值：支付单已存在';
            $this->exception(__METHOD__);
        }

        // 金额验证
        if ($this->_paymentsdf['money'] <= 0) {
            $this->_apiLog['info']['msg'] = '返回值：支付金额不正确';
            $this->exception(__METHOD__);
        }

        // 淘管中订单信息
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id));
        $this->_apiLog['info'][] = '订单信息：'.var_export($tgOrder,true);
        if (!$tgOrder) {
            $this->_apiLog['info']['msg'] = '返回值：订单号不存在';
            $this->exception(__METHOD__);
        }

        // 支付金额大于订单总额
        $payed = bcadd($tgOrder['payed'], (array)$this->_paymentsdf['money'],3);
        if (bccomp($payed, $tgOrder['total_amount'], 3) == 1) {
            $this->_apiLog['info']['msg'] = '返回值：支付金额(' . $this->_paymentsdf['money'] . ')+已支付金额(' . $tgOrder['payed'] . ')  > 订单总金额(' . $tgOrder['total_amount'] . ')';
            $this->exception(__METHOD__);
        }

        // 判断是否订单全额退款/已支付
        if ($tgOrder['pay_status'] == '1' || $tgOrder['pay_status'] == '5') {
            $this->_apiLog['info']['msg'] = '返回值：订单已退款或已支付';
            $this->exception(__METHOD__);
        }

        //  订单状态验证
        if ($tgOrder['status'] != 'active') {
            $this->_apiLog['info']['msg'] = '返回值：订单状态非活动,无法支付';
            $this->exception(__METHOD__);
        }

        // 订单取消
        if ($tgOrder['process_status'] == 'cancel') {
            $this->_apiLog['info']['msg'] = '返回值：订单已取消';
            $this->exception(__METHOD__);
        }

        // 多张支付单合法性校验，求和，如果与不大于总共金额，则允许创建，否则返回不合法
        $filter = array('order_id'=>$tgOrder['order_id']);
        $tgPayments = $paymentModel->getList('cur_money',$filter);
        $money = (float)$this->_paymentsdf['money'];
        if ($tgPayments) {
            foreach ($tgPayments as $key => $payment) {
                $money += $payment['cur_money'];
            }
        }

        if (bccomp($money, $tgOrder['total_amount'], 3) == 1) {
            $this->_apiLog['info']['msg'] = '返回值：支付金额(' . $money . ')大于订单金额(' . $tgOrder['total_amount'] . ')，支付失败';
            $this->exception(__METHOD__);
        }

        $pay_bn = $this->_paymentsdf['payment'];
        if ($pay_bn) {
            $payment_cfg = kernel::single('apibusiness_adapter_payment_cfg')->get_payment($pay_bn,$shop_id);
            $this->_paymentsdf['payment'] = $payment_cfg['id'];
        }

        $t_begin = $this->_paymentsdf['t_begin'] ?  kernel::single('ome_func')->date2time($this->_paymentsdf['t_begin']) : time();
        $t_end   = $this->_paymentsdf['t_end'] ?  kernel::single('ome_func')->date2time($this->_paymentsdf['t_end']) : time();

        $sdf = array(
            'payment_bn'     => $this->_paymentsdf['payment_bn'],
            'shop_id'        => $shop_id,
            'order_id'       => $tgOrder['order_id'],
            'account'        => $this->_paymentsdf['account'],
            'bank'           => $this->_paymentsdf['bank'],
            'pay_account'    => $this->_paymentsdf['pay_account'],
            'currency'       => $this->_paymentsdf['currency'] ? $this->_paymentsdf['currency'] : 'CNY',
            'money'          => (float)$this->_paymentsdf['money'],
            'paycost'        => $this->_paymentsdf['paycost'],
            'cur_money'      => (float)$this->_paymentsdf['cur_money'],
            'pay_type'       => $this->_paymentsdf['pay_type'] ? $this->_paymentsdf['pay_type'] : 'online',
            'payment'        => $this->_paymentsdf['payment'],
            'pay_bn'         => $pay_bn,
            'paymethod'      => $this->_paymentsdf['paymethod'],
            't_begin'        => $t_begin,
            'download_time'  => time(),
            't_end'          => $t_end,
            'status'         => $this->_paymentsdf['status'],
            'memo'           => $this->_paymentsdf['memo'],
            'is_orderupdate' => 'true',
            'trade_no'       => $this->_paymentsdf['trade_no'],
        );

        $rs = $paymentModel->create_payments($sdf);
        if ($rs) {
            $this->_apiLog['info'][] = '返回值：添加支付单成功';

            $filter = array('order_id'=>$tgOrder['order_id']);
            $orderModel->update(array('payment' => $this->_paymentsdf['paymethod']),$filter);
        } else {
            $this->_apiLog['info']['msg'] = '返回值：添加支付单失败';
            $this->exception(__METHOD__);
        }
    }

    public function status_update()
    {
        parent::status_update();

        $shop_id    = $this->_shop['shop_id'];
        $order_bn   = $this->_paymentsdf['order_bn'];
        $payment_bn = $this->_paymentsdf['payment_bn'];
        $status     = $this->_paymentsdf['status'];

        if ($status != 'succ') {
            $this->_apiLog['info']['msg'] = '返回值：状态值不正确';
            $this->exception(__METHOD__);
        }

        if (empty($order_bn) || empty($payment_bn)) {
            $this->_apiLog['info']['msg'] = '返回值：支付单号或订单号不能为空';
            $this->exception(__METHOD__);
        }

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id));
        if (empty($tgOrder)) {
            $this->_apiLog['info']['msg'] = '返回值：订单不存在';
            $this->exception(__METHOD__);
        }

        $paymentModel = app::get(self::_APP_NAME)->model('payments');
        $tgPayment = $paymentModel->dump(array('payment_bn'=>$payment_bn,'shop_id'=>$shop_id));
        if (empty($tgPayment)) {
            $this->_apiLog['info']['msg'] = '返回值：支付单不存在';
            $this->exception(__METHOD__);
        }

        // 更新支付单状态
        $rs = $paymentModel->update(array('status'=>$status),array('payment_bn'=>$payment_bn,'shop_id'=>$shop_id));
        if ($rs) {
            $this->_apiLog['info'][] = '返回值：更新支付单状态成功';
            // 更新订单状态
            $actual_payed = bcadd($tgOrder['payed'], $tgPayment['money'],3);
            $updateOrder['payed'] = (bccomp($actual_payed, $tgOrder['total_amount'],3) == 1) ? $tgOrder['payed'] :  $actual_payed;
            $updateOrder['paytime'] = $tgPayment['t_begin'];
            $rs = $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));
            if ($rs || $tgPayment['money'] == 0) {
                kernel::single('ome_order_func')->update_order_pay_status($tgOrder['order_id']);
            } else {

            }
        } else {
            $this->_apiLog['info'][] = '返回值：支付单更新状态失败';
            $this->exception(__METHOD__);
        }
        
    }
}