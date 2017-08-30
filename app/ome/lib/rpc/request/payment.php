<?php
/**
 * 订单支付同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_payment extends ome_rpc_request {

    //支付状态
    var $pay_status = array(
          'succ'=>'SUCC',
          'failed'=>'FAILED',
          'cancel'=>'CANCEL',
          'error'=>'ERROR',
          'invalid'=>'INVALID',
          'progress'=>'PROGRESS',
          'timeout'=>'TIMEOUT',
          'ready'=>'READY',
    );

    /**
     * 添加交易收款单
     * @access public
     * @param int $payment_id 支付单主键ID
     * @return boolean
     */
    public function add($payment_id){

        if(!empty($payment_id)){

            $params = $this->payment_request_params($payment_id,'');
            $shop_id = $params['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')添加[交易付款单(金额:'.$params['pay_fee'].')]订单号:'.$params['tid'].'付款单号:'.$params['payment_id'].')';
            }else{
                return false;
            }

            $callback = array(
                'class' => 'ome_rpc_request_payment',
                'method' => 'payment_add_callback',
            );

            $this->request('store.trade.payment.add',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function payment_add_callback($result){
        return $this->callback($result);
    }

    /**
     * 交易支付单请求
     * @access public
     * @param mixed $sdf 支付请求数据
     * @return boolean
     */
    public function payment_request($sdf=NULL){

        if(!empty($sdf)){

            $params = $this->payment_request_params('',$sdf);
            $shop_id = $params['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')发起交易支付请求(金额:'.$params['pay_fee'].',支付方式:'.$params['payment_type'].')]订单号:'.$params['tid'];
            }else{
                return false;
            }

            $callback = array(
                'class' => 'ome_rpc_request_payment',
                'method' => 'payment_request_callback',
            );
            $this->request('store.trade.payment.add',$params,$callback,$title,$shop_id, 60);
        }else{
            return false;
        }
    }

    function payment_request_callback($result){

        //请求失败，还原订单支付状态，记录操作日志
        $status = $result->get_status();
        $oOrder = &app::get('ome')->model('orders');
        //$oApi_log = &app::get('ome')->model('api_log');
        $api_failObj = &app::get('ome')->model('api_fail');
        $oOperation_log = &app::get('ome')->model('operation_log');
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();
        //$log_id = $callback_params['log_id'];
        //$apilog_detail = $oApi_log->dump(array('log_id'=>$log_id), 'params');
        //$apilog_detail = unserialize($apilog_detail['params']);
        //$apilog_detail = $request_params;

        $order_bn = $request_params['tid'];
        $shop_id = $callback_params['shop_id'];


        $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
        $order_id = $order_detail['order_id'];
        $data = array(
            'order_id' => $order_id,
            'type' => 'payment'
        );
        if ($status != 'succ'){
            if ($order_detail['pay_status'] == '8'){
                //状态回滚，变成未付款/部分付款
                kernel::single('ome_order_func')->update_order_pay_status($order_id);
                //此订单出现在付款确认的“付款失败”标签页里,并在操作日志中记录“前端拒绝支付，付款失败”
                $api_failObj->insert($data);
            }elseif(in_array($order_detail['pay_status'],array('1','3'))){
                $api_failObj->delete($data);
            }
            //操作日志
            $oOperation_log->write_log('order_payment@ome',$order_id,'订单号:'.$order_bn.'发起支付请求,前端拒绝支付,付款失败');
        }else{
            $api_failObj->delete($data);
        }
        return $this->callback($result);
    }

    /**
     * 更新支付单状态
     * @access public
     * @param int $payment_id 支付单主键ID
     * @return boolean
     */
    public function status_update($payment_id){

        if(!empty($payment_id)){
            $paymentObj = &app::get('ome')->model('payments');
            $orderObj = &app::get('ome')->model('orders');
            //支付单详情
            $payment_detail = $paymentObj->dump(array('payment_id'=>$payment_id), 'order_id,shop_id,payment_bn,status');
            $order = $orderObj->dump($payment_detail['order_id'], 'order_bn');
            $params['tid'] = $order['order_bn'];
            $params['payment_id '] = $payment_detail['payment_bn'];
            $params['oid '] = '';#子订单id
            $params['status'] = $this->pay_status($payment_detail['status']);

            $callback = array(
                'class' => 'ome_rpc_request_payment',
                'method' => 'payment_status_update_callback',
            );

            $shop_id = $payment_detail['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更新[交易支付单状态'.$params['status'].'](订单号:'.$order['order_bn'].'付款单号:'.$payment_detail['payment_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.payment.status.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function payment_status_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 组织支付单发起参数
     * @access private
     * @param number $payment_id 支付单ID
     * @param mixed $sdf 原始请求数据
     * @return ArrayObject 标准请求参数数据
     */
    private function payment_request_params($payment_id='', $sdf=''){

        $paymentObj = &app::get('ome')->model('payments');
        $paymentCfgObj = &app::get('ome')->model('payment_cfg');
        $orderObj = &app::get('ome')->model('orders');
        $memberObj = &app::get('ome')->model('members');
        if (!empty($payment_id)){
            $payment_detail = $paymentObj->dump(array('payment_id'=>$payment_id), '*');
            $order_id = $payment_detail['order_id'];
        }elseif($sdf){
            $payment_detail = $sdf;
            $order_id = $payment_detail['order_id'];
            $curr_time = time();
            $payment_detail['t_begin'] = $curr_time;
            $payment_detail['t_end'] = $curr_time;
            $payment_detail['cur_money'] = $payment_detail['money'];
        }

        //支付信息
        $cfg = $paymentCfgObj->dump(array('id'=>$payment_detail['payment']), 'pay_bn,custom_name');
        $payment_detail['pay_bn'] = $cfg['pay_bn'];
        $payment_detail['paymethod'] = $cfg['custom_name'];
        //订单/会员信息
        $order = $orderObj->dump($order_id, 'order_bn,member_id,shop_id');
        $memberinfo = $memberObj->dump($order['member_id'],'uname,name');

        $params = array();
        $params['shop_id'] = $order['shop_id'];
        $params['tid'] = $order['order_bn'];
        $params['payment_id'] = $payment_detail['payment_bn'];
        $params['buyer_id'] = $memberinfo['account']['uname'];
        $params['seller_account'] = $payment_detail['account']?$payment_detail['account']:'';
        $params['seller_bank'] = $payment_detail['bank']?$payment_detail['bank']:'';
        $params['buyer_account'] = $payment_detail['pay_account']?$payment_detail['pay_account']:'';
        $params['currency'] = $payment_detail['currency']?$payment_detail['currency']:'CNY';
        $params['pay_fee'] = $payment_detail['money'];
        $params['paycost'] = $payment_detail['paycost']?$payment_detail['paycost']:'';
        $params['currency_fee'] = $payment_detail['cur_money']?$payment_detail['cur_money']:'';
        $params['pay_type'] = $payment_detail['pay_type'];
        $params['payment_tid'] = $payment_detail['pay_bn'];
        $params['payment_type'] = $payment_detail['paymethod']?$payment_detail['paymethod']:'';
        $params['t_begin'] = date("Y-m-d H:i:s",$payment_detail['t_begin']);
        $params['t_end'] = $payment_detail['t_end'] ? date("Y-m-d H:i:s",$payment_detail['t_end']) : '';
        $params['memo'] = $payment_detail['memo']?$payment_detail['memo']:'';
        $params['status'] = $this->pay_status[$payment_detail['status']];
        $params['payment_operator'] = kernel::single('desktop_user')->get_login_name();
        $params['outer_no'] = $payment_detail['trade_no']?$payment_detail['trade_no']:'';#支付网关的内部交易单号
        $params['modify'] = date("Y-m-d H:i:s", time());
        return $params;
    }

}