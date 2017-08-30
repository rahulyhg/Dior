<?php
/**
* 换货类
*
* @author chenping<chenping@shopex.cn>
*/
class ome_reship
{

    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 取消退款申请
     *
     * @param String $apply_id 退款申请ID
     * @param String $memo 取消理由
     * @return void
     * @author
     **/
    public function cancelRefundApply($apply_id,$memo='')
    {
        $refundApplyModel = $this->app->model('refund_apply');
        $applyUpdate = array(
            'status' => '3',
            'memo'=>$memo
        );
        $refundApplyModel->update($applyUpdate,array('apply_id'=>$apply_id));
    }

    /**
     * @description 退换货申请退款生成退款单据
     * @access public
     * @param void
     * @return void
     */
    public function createRefund($refundApply,$order)
    {
        # 更新退款金额
        $orderModel = $this->app->model('orders');
        $payed = $order['payed'] - $refundApply['money'];
        $payed = ( $payed > 0 ) ? $payed : 0;
        $orderModel->update(array('payed'=>$payed),array('order_id'=>$order['order_id']));

        $opLogModel = $this->app->model('operation_log');
        $opLogModel->write_log('order_modify@ome',$order['order_id'],"售后退款成功，更新订单退款金额。系统自动操作，退款金额用于支付新订单。");

        # 退款申请单处理
        $refundApplyUpdate = array(
            'status' => '4',
            'refunded' => $refundApply['money'],
            'last_modified' => time(),
            'account' => $refundApply['account'],
            'pay_account' => $refundApply['pay_account'],
        );
        $refundApplyModel = $this->app->model('refund_apply');
        $refundApplyModel->update($refundApplyUpdate,array('apply_id'=>$refundApply['apply_id']));

        $opLogModel->write_log('refund_apply@ome',$refundApply['apply_id'],"售后退款成功，更新退款申请状态。系统自动操作，退款金额用于支付新订单。");

        # 退款单处理
        $paymethods = ome_payment_type::pay_type();
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $refunddata = array(
            'refund_bn' => $refundApply['refund_apply_bn'],
            'order_id' => $order['order_id'],
            'shop_id' => $order['shop_id'],
            'account' => $refundApply['account'],
            'bank' => $refundApply['bank'],
            'pay_account' => $refundApply['pay_account'],
            'currency' => $order['currency'],
            'money' => $refundApply['money'],
            'paycost' => 0,
            'cur_money' => $refundApply['money'],
            'pay_type' => $refundApply['pay_type'],
            'payment' => $refundApply['payment'],
            'paymethod' => $paymethods[$refundApply['pay_type']],
            'op_id' => $opInfo['op_id'],
            't_ready' => time(),
            't_sent' => time(),
            'memo' => $refundApply['memo'],
            'status' => 'succ',
            'refund_refer' => '1',
            'return_id' => $refundApply['return_id'],
        );
        if ($refundApply['archive'] && $refundApply['archive']=='1') {
            $refunddata['archive'] = '1';
        }
        $oRefund = $this->app->model('refunds');
        $oRefund->save($refunddata);

        // 更新订单支付状态
        kernel::single('ome_order_func')->update_order_pay_status($order['order_id']);
        $opLogModel->write_log('refund_accept@ome',$refunddata['refund_id'],"售后退款成功，生成退款单".$refunddata['refund_bn']."，退款金额用于支付新订单。");
    }

    /**
     * 取消补差价订单
     *
     * @param Int $order_id 订单ID
     * @param String $shop_id 店铺ID
     * @return void
     * @author
     **/
    public function cancelDiffOrder($order_id,$shop_id,$memo='')
    {
        define('FRST_TRIGGER_OBJECT_TYPE','订单：订单作为补差价订单取消');
        define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：do_cancel');


        $c2c_shop_list = ome_shop_type::shop_list();

        $node_type = $this->app->model('shop')
                        ->select()->columns('node_type')
                        ->where('shop_id=?',$shop_id)
                        ->instance()->fetch_one();

        $mod = in_array($node_type,$c2c_shop_list) ? 'async' : 'sync';

        return $this->app->model('orders')->cancel($order_id,$memo,true,$mod);
    }

    /**
     * 对换货订单进行支付操作
     *
     * @param Array $order 订单信息
     * @return void
     * @author
     **/
    public function payChangeOrder($order)
    {
        $mathLib      = kernel::single('eccommon_math');
        $orderModel   = $this->app->model('orders');
        $paymentModel = $this->app->model('payments');

        $orderdata = array(
            'order_id' => $order['order_id'],
            'pay_status' => $order['pay_status'],
            'paytime' => time(),
        );

        # 支付配置
        //$cfg = $this->app->model('payment_cfg')->dump();
        $cfg = array();

        $orderdata['pay_bn'] = $cfg['pay_bn'];

        $orderdata['payed'] = $mathLib->getOperationNumber($order['pay_money']);

        $orderdata['payment'] = '线下支付';

        $orderModel->update($orderdata,array('order_id'=>$order['order_id']));

        //日志
        $memo = '做质检时连带操作;订单付款操作,用订单('.$order['reship_order_bn'].')的退款金额作支付金额';
        $oOperation_log = &$this->app->model('operation_log');
        $oOperation_log->write_log('order_modify@ome',$order['order_id'],$memo);

        //生成支付单
        $payment_bn = $paymentModel->gen_id();
        $paymentdata = array();
        $paymentdata['payment_bn']  = $payment_bn;
        $paymentdata['order_id']    = $order['order_id'];
        $paymentdata['shop_id']     = $order['shop_id'];
        $paymentdata['account']     = '';
        $paymentdata['bank']        = '';
        $paymentdata['pay_account'] = '';
        $paymentdata['currency']    = $order['currency'];
        $paymentdata['money']       = $order['pay_money'];
        $paymentdata['paycost']     = 0;
        $curr_time                  = time();
        $paymentdata['t_begin']     = $curr_time;//支付开始时间
        $paymentdata['t_end']       = $curr_time;//支付结束时间
        $paymentdata['trade_no']    = '';//支付网关的内部交易单号，默认为空
        $paymentdata['cur_money']   = $paymentdata['money'];
        $paymentdata['pay_type']    = 'offline';
        $paymentdata['payment']     = '';
        $paymentdata['paymethod']   = '线下支付';
        $paymentdata['payment_refer'] = '1';

        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $paymentdata['op_id'] = $opInfo['op_id'];
        if ($order['archive'] && $order['archive'] == '1') {
            //$paymentdata['archive'] = '1';
        }
        $paymentdata['ip'] = kernel::single("base_request")->get_remote_addr();
        $paymentdata['status'] = 'succ';
        $paymentdata['memo'] = '做质检时连带操作;系统生成换货订单支付单据;通过退款金额进行支付;补换货的订单:'.$order['reship_order_bn'];
        $paymentdata['is_orderupdate'] = 'false';
        $paymentModel->create_payments($paymentdata);

        //日志
        $oOperation_log->write_log('payment_create@ome',$paymentdata['payment_id'],'生成支付单');
    }

    /**
     * @description 判断是否为反审单据
     * @access public
     * @param void
     * @return void
     */
    public function is_precheck_reship($is_check,$need_sv='true')
    {
        return ($is_check=='0' && $need_sv == 'false') ? true : false;
    }

    /**
     * @description 更新订单的状态,并把发货状态同步到前端
     *
     * @return void
     * @author
     **/
    function updatediffOrder($order_bn)
    {
        $orderModel = $this->app->model('orders');
        $shopObj = &app::get('ome')->model('shop');
        $order_detail = $orderModel->getList('order_id,shop_id',array('order_bn'=>$order_bn));
        $shop_detail = $shopObj->dump($order_detail[0]['shop_id'], 'node_type');

        /*
            logistics_code company_code => 'OTHER'  (type)
            logistics_no                => 00000000000(logi_no)
            logistics_company  company_name  logistics_company  => 其他物流公司(logi_name)
        */
        if( in_array( $shop_detail['node_type'] , ome_shop_type::shop_list() ) ){

            $filter = array(
              'ship_status' => '1',
              'status' => 'finish',
              'archive' => '1',
              'process_status' => 'confirmed',
              'confirm' => 'Y', 
              'is_delivery' => 'Y',
            );

            $orderModel->update($filter,array('order_bn'=>$order_bn));

            # 标记发货数
            $sql = 'UPDATE `sdb_ome_order_items` SET sendnum=nums WHERE `delete`="false" AND order_id='.$order_detail[0]['order_id'];
            $orderModel->db->exec($sql);

            $delivery['order']['order_id'] = $order_detail[0]['order_id'];
            $delivery['type'] = 'reject';
            $delivery['shop_id'] = $order_detail[0]['shop_id'];
            
            $router = kernel::single('apibusiness_router_request');

            $router->setShopId($order_detail[0]['shop_id'])->add_delivery($delivery);
            
            #kernel::single('ome_rpc_request_shipping')->_get_shipping_params('', $order_detail[0]['order_id'], '', $shop_detail['node_type'],'','aftersale');
        }

    }
}