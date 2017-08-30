<?php
/**
 * 订单退款同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_refund extends ome_rpc_request {

    //退款状态
    var $status = array(
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
     * 添加交易退款单
     * @access public
     * @param int $refund_id 退款单主键ID
     * @return boolean
     */
    public function add($refund_id){

        if(!empty($refund_id)){

            $params = $this->refund_request_params($refund_id,'');
            $shop_id = $params['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')添加[交易退款单(金额:'.$params['refund_fee'].')](订单号:'.$params['tid'].'退款单号:'.$params['refund_id'].')';
            }else{
                return false;
            }

            $callback = array(
                'class' => 'ome_rpc_request_refund',
                'method' => 'refund_add_callback',
            );

            $this->request('store.trade.refund.add',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }

    }

    function refund_add_callback($result){
        return $this->callback($result);
    }

    /**
     * 交易退款单请求
     * @access public
     * @param mixed $sdf 退款请求数据
     * @return boolean
     */
    public function refund_request($sdf=NULL){

        if(!empty($sdf)){

            $params = $this->refund_request_params('',$sdf);
            $shop_id = $params['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')发起交易退款请求(金额:'.$params['refund_fee'].',支付方式:'.$params['payment_type'].')]订单号:'.$params['tid'];
            }else{
                return false;
            }

            $callback = array(
                'class' => 'ome_rpc_request_refund',
                'method' => 'refund_request_callback',
            );
            $this->request('store.trade.refund.add',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function refund_request_callback($result){

        $status = $result->get_status();
        if ($status != 'succ'){
            $oOrder = &app::get('ome')->model('orders');
            //$oApi_log = &app::get('ome')->model('api_log');
            $oOperation_log = &app::get('ome')->model('operation_log');
            $refund_applyObj = &app::get('ome')->model('refund_apply');
            $refundsObj = &app::get('ome')->model('refunds');

            $callback_params = $result->get_callback_params();
            $request_params = $result->get_request_params();
            //$log_id = $callback_params['log_id'];
            //$apilog_detail = $oApi_log->dump(array('log_id'=>$log_id), 'params');
            //$apilog_detail = unserialize($apilog_detail['params']);
            //$apilog_detail = $request_params;

            $order_bn = $request_params['tid'];
            $shop_id = $callback_params['shop_id'];
            $refund_apply_id = $request_params['refund_apply_id'];

            $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
            $order_id = $order_detail['order_id'];
            //状态回滚，变成已支付/部分付款/部分退款
            kernel::single('ome_order_func')->update_order_pay_status($order_id);

            #bugfix:解决如果退款单请求先到并生成单据于此同时由于网络超时造成退款申请失败，从而造成退款申请单状态错误问题。
            $refundapply_detail = $refund_applyObj->getList('refund_apply_bn',array('apply_id'=>$refund_apply_id));
            $refunds_detail = $refundsObj->getList('refund_id',array('refund_bn'=>$refundapply_detail[0]['refund_apply_bn'],'status'=>'succ'));

            if(!$refunds_detail){

                $refund_applyObj->update(array('status'=>'6'), array('apply_id'=>$refund_apply_id));
                //操作日志
                $oOperation_log->write_log('order_refund@ome',$order_id,'订单:'.$order_bn.'发起退款请求,前端拒绝退款，退款失败');

            }

        }
        return $this->callback($result);
    }

    /**
     * 更新交易退款状态
     * @access public
     * @param int $refund_id 退款单主键ID
     * @return boolean
     */
    public function status_update($refund_id){

        if(!empty($refund_id)){
            $refundObj = &app::get('ome')->model('refunds');
            $orderObj = &app::get('ome')->model('orders');
            $refund_detail = $refundObj->dump(array('refund_id'=>$refund_id), 'order_id,shop_id,refund_bn,status');
            $order = $orderObj->dump($refund_detail['order_id'], 'order_bn');
            $params['tid'] = $order['order_bn'];
            $params['refund_id '] = $refund_detail['refund_bn'];
            $params['oid '] = '';#子订单id
            $params['status'] = $this->status[$refund_detail['status']];

            $callback = array(
                'class' => 'ome_rpc_request_refund',
                'method' => 'refund_status_update_callback',
            );

            $shop_id = $refund_detail['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更新[交易退款状态]:'.$params['status'].'(订单号:'.$order['order_bn'].'退款单号:'.$refund_detail['refund_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.refund.status.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function refund_status_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 组织退款单发起参数
     * @access private
     * @param number $refund_id 退款单ID
     * @param mixed $sdf 原始请求数据
     * @return ArrayObject 标准请求参数数据
     */
    private function refund_request_params($refund_id='', $sdf=''){

        $paymentCfgObj = &app::get('ome')->model('payment_cfg');
        $refundObj = &app::get('ome')->model('refunds');
        $orderObj = &app::get('ome')->model('orders');
        $memberObj = &app::get('ome')->model('members');

        if (!empty($refund_id)){
            $refund_detail = $refundObj->dump(array('refund_id'=>$refund_id), '*');
            $order_id = $refund_detail['order_id'];
        }elseif($sdf){
            $refund_detail = $sdf;
            $order_id = $refund_detail['order_id'];
            $curr_time = time();
            $refund_detail['t_begin'] = $curr_time;
            $refund_detail['t_end'] = $curr_time;
        }

        //支付方式信息
        $cfg = $paymentCfgObj->dump(array('id'=>$refund_detail['payment']), 'pay_bn,custom_name');
        $refund_detail['pay_bn'] = $cfg['pay_bn'];
        $refund_detail['paymethod'] = $cfg['custom_name'];
        //订单/会员信息
        $order = $orderObj->dump($order_id, 'order_bn,member_id,shop_id');
        $member_info = $memberObj->dump($order['member_id'],'uname,name');

        $params = array();
        $params['shop_id'] = $order['shop_id'];
        $params['tid'] = $order['order_bn'];
        $params['refund_id'] = $refund_detail['refund_bn'];
        $params['refund_apply_id'] = $refund_detail['apply_id'];
        $params['buyer_account'] = $refund_detail['account']?$refund_detail['account']:'';
        $params['buyer_bank'] = $refund_detail['bank']?$refund_detail['bank']:'';
        $params['seller_account'] = $refund_detail['pay_account']?$refund_detail['pay_account']:'';
        $params['buyer_name'] = $member_info['contact']['name'];#买家姓名
        $params['buyer_id'] = $member_info['account']['uname'];#买家会员帐号
        $params['currency'] = $refund_detail['currency']?$refund_detail['currency']:'CNY';
        $params['refund_fee'] = $refund_detail['money'];
        $params['paycost'] = $refund_detail['paycost']?$refund_detail['paycost']:'';
        $params['currency_fee'] = $refund_detail['cur_money'] ? $refund_detail['cur_money'] : $refund_detail['money'];
        $params['pay_type'] = $refund_detail['pay_type'];
        $params['payment_tid'] = $refund_detail['pay_bn'];
        $params['payment_type'] = $refund_detail['paymethod']?$refund_detail['paymethod']:'';
        $refund_detail['t_ready'] = $refund_detail['t_ready'] ? $refund_detail['t_ready'] : time();
        $params['t_begin'] = date("Y-m-d H:i:s",$refund_detail['t_ready']);
        $params['t_sent'] = $refund_detail['t_sent'] ? date("Y-m-d H:i:s",$refund_detail['t_sent']) : '';
        $params['t_received'] = $refund_detail['t_received'] ? date("Y-m-d H:i:s",$refund_detail['t_received']) : date("Y-m-d H:i:s",time());
        $params['status'] = $this->status[$refund_detail['status']];
        $params['memo'] = $refund_detail['memo']?$refund_detail['memo']:'';
        $params['outer_no'] = $refund_detail['trade_no']?$refund_detail['trade_no']:'';
        $params['modify'] = date("Y-m-d H:i:s", time());
        return $params;
    }

}