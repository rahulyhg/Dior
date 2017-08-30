<?php
/**
* SHOPEX 请求抽象类
*
* @category apibusiness
* @package apibusiness/lib/request/
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-13-12 14:44Z
*/
abstract class apibusiness_request_shopexabstract extends apibusiness_request_abstract
{

    static public $order_mark_type = array(
        'b0' => '0',
        'b1' => '1',
        'b2' => '2',
        'b3' => '3',
        'b4' => '4',
        'b5' => '5',
    );

    static public $aftersale_status = array (
            '1' => '1',#申请中',
            '2' => '2',#审核中',
            '3' => '3',#接受申请',
            '4' => '4',#完成',
            '5' => '5',#拒绝',
            '6' => '6',#已收货',
            '7' => '7',#已质检',
            '8' => '8',#补差价',
            '9' => '9',#已拒绝退款',
    );

    /**
     * 添加售后申请
     *
     * @param Array $returninfo 售后申请
     * @return void
     * @author 
     **/
    public function add_aftersale($returninfo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if(!$returninfo) {
            $rs['msg'] = 'no return';
            return $rs;
        }

        // 售后明细
        $returnItemModel = app::get(self::_APP_NAME)->model('return_product_items');
        $return_items = $returnItemModel->getList('name as sku_name,bn as sku_bn,num as number',array('return_id'=>$returninfo['return_id']));

        // 订单信息
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump($returninfo['order_id'],'member_id,order_id,order_bn');

        // 会员信息
        $memberModel = app::get(self::_APP_NAME)->model('members');
        $member = $memberModel->dump($order['member_id'],'uname,member_id');
        
        //退货附件
        $attachment = $returninfo['attachment'];
        if (is_numeric($attachment)){
            $attachment = kernel::single('base_storager')->getUrl($attachment);
        }

        $params['aftersale_items'] = json_encode($return_items);
        $params['attachment']      = $attachment;
        $params['tid']             = $order['order_bn'];
        $params['aftersale_id']    = $returninfo['return_bn'];
        $params['title']           = $returninfo['title'] ? $returninfo['title'] : '';
        $params['content']         = $returninfo['content'] ? $returninfo['content'] : '';
        $params['messager']        = $returninfo['comment'] ? $returninfo['comment'] : '';
        $params['memo']            = $returninfo['memo'] ? $returninfo['memo'] : '';
        $params['status']          = self::$aftersale_status[$returninfo['status']];
        $params['buyer_id']        = $member['member_id'];
        $params['buyer_name']      = $member['account']['uname'];
        $params['modify']          = $returninfo['last_modified'] ?  date("Y-m-d H:i:s",$returninfo['last_modified']) : date("Y-m-d H:i:s");
        $params['created']         = $returninfo['add_time'] ? date("Y-m-d H:i:s",$returninfo['add_time']) : date("Y-m-d H:i:s");

        $callback = array(
            'class' => get_class($this),
            'method' => 'add_aftersale_callback',
        );
        
        $title = '店铺('.$this->_shop['name'].')售后申请(订单号:'.$order['order_bn'].',申请单号:'.$returninfo['return_bn'].')';

        $addon['bn'] = $order['order_bn'];
        $this->_caller->request(ADD_AFTERSALE_RPC,$params,$callback,$title,$this->_shop['shop_id'],10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }// TODO TEST

    public function add_aftersale_callback($result)
    {
        return $this->_caller->callback($result);
    }

   /**
     * 更新售后申请状态
     *
     * @param Array $returninfo 售后申请
     * @return void
     * @author 
     **/
    public function update_aftersale_status($returninfo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if(!$returninfo) {
            $rs['msg'] = 'no return';
            return $rs;
        }

        $returnModel = app::get(self::_APP_NAME)->model('return_product');
        if ($returninfo['status'] == '4'){
            $product_detail = $returnModel->product_detail($returninfo['return_id']);
            if ($product_detail['check_data']){
                foreach ($product_detail['check_data'] as $item){
                    $tmp = array(
                        'bn'          => $item['bn'],//货品货号
                        'name'        => $item['name'],//货品名称
                        'memo'        => $item['memo'],//备注
                        'need_money'  => $item['need_money'],//应退金额
                        'other_money' => $item['other'],//折旧（其他金额）
                        'status'      => $item['status'],//1：退货、2：换货、3：拒绝
                    );
                    $addon[] = $tmp;
                }
            }
        }

        // 订单信息
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump($returninfo['order_id'], 'order_bn,shop_id');

        $params['tid']          = $order['order_bn'];
        $params['aftersale_id'] = $returninfo['return_bn'];
        $params['status']       = self::$aftersale_status[$returninfo['status']];
        $params['modify']       = date('Y-m-d H:i:s');
        $params['addon']        = json_encode($addon);

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_aftersale_status_callback',
        );
        
        $title = '店铺('.$this->_shop['name'].')更新售后申请状态['.$params['status'].'](订单号:'.$order['order_bn'].',申请单号:'.$returninfo['return_bn'].')';

        $addon['bn'] = $order['order_bn'];
        $this->_caller->request(UPDATE_AFTERSALE_STATUS_RPC,$params,$callback,$title,$this->_shop['shop_id'],10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }

    public function update_aftersale_status_callback($result)
    {
        return $this->_caller->callback($result);
    }

    //支付状态
    static public $pay_status = array(
          'succ'     =>'SUCC',
          'failed'   =>'FAILED',
          'cancel'   =>'CANCEL',
          'error'    =>'ERROR',
          'invalid'  =>'INVALID',
          'progress' =>'PROGRESS',
          'timeout'  =>'TIMEOUT',
          'ready'    =>'READY',
    );
    /**
     * 添加付款单
     * @access public
     * @param  $payment 
     */
    public function add_payment($payment)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if(!$payment) {
            $rs['msg'] = 'no payment';
            return $rs;
        }

        $payment['t_begin']   = $payment['t_begin'] ? $payment['t_begin'] : time();
        $payment['t_end']     = $payment['t_end'] ? $payment['t_end'] : time();
        $payment['cur_money'] = $payment['cur_money'] ? $payment['cur_money'] : $payment['money'];

        //支付信息
        $paymentCfgModel = app::get(self::_APP_NAME)->model('payment_cfg');
        $cfg = $paymentCfgModel->dump(array('id'=>$payment['payment']), 'pay_bn,custom_name');
        $payment['pay_bn']    = $cfg['pay_bn'];
        $payment['paymethod'] = $cfg['custom_name'];
        
        // 订单
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump($payment['order_id'], 'order_id,order_bn,member_id,shop_id');
        
        // 会员信息
        $memberModel = app::get(self::_APP_NAME)->model('members');
        $memberinfo = $memberModel->dump($order['member_id'],'uname,name,member_id');
        
        $params = array();
        $params['shop_id']          = $order['shop_id'];
        $params['tid']              = $order['order_bn'];
        $params['payment_id']       = $payment['payment_bn'];
        $params['buyer_id']         = $memberinfo['account']['uname'];
        $params['seller_account']   = $payment['account']?$payment['account']:'';
        $params['seller_bank']      = $payment['bank']?$payment['bank']:'';
        $params['buyer_account']    = $payment['pay_account']?$payment['pay_account']:'';
        $params['currency']         = $payment['currency']?$payment['currency']:'CNY';
        $params['pay_fee']          = $payment['money'];
        $params['paycost']          = $payment['paycost']?$payment['paycost']:'';
        $params['currency_fee']     = $payment['cur_money']?$payment['cur_money']:'';
        $params['pay_type']         = $payment['pay_type'];
        $params['payment_tid']      = $payment['pay_bn'];
        $params['payment_type']     = $payment['paymethod']?$payment['paymethod']:'';
        $params['t_begin']          = date("Y-m-d H:i:s",$payment['t_begin']);
        $params['t_end']            = date("Y-m-d H:i:s",$payment['t_end']);
        $params['memo']             = $payment['memo']?$payment['memo']:'';
        $params['status']           = self::$pay_status[$payment['status']];
        $params['payment_operator'] = kernel::single('desktop_user')->get_login_name();
        $params['op_name'] = kernel::single('desktop_user')->get_login_name();
        $params['outer_no']         = $payment['trade_no']?$payment['trade_no']:'';#支付网关的内部交易单号
        $params['modify']           = date("Y-m-d H:i:s", time());
        
        $callback = array(
            'class' => get_class($this),
            'method' => 'add_payment_callback',
        );

        $title = '店铺('.$this->_shop['name'].')发起交易支付请求(金额:'.$params['pay_fee'].',支付方式:'.$params['payment_type'].')]订单号:'.$params['tid'];
        
        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(ADD_PAYMENT_RPC,$params,$callback,$title,$this->_shop['shop_id'], 60,false,$addon);
        
        $rs['rsp'] = 'success';

        return $rs;
    }

    public function add_payment_callback($result)
    {
        //请求失败，还原订单支付状态，
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();

        // 记录操作日志
        $oApi_log = app::get(self::_APP_NAME)->model('api_log'); 
        //$log_id = $callback_params['log_id'];
        //$apilog_detail = $oApi_log->dump(array('log_id'=>$log_id), 'params');
        //$apilog_detail = unserialize($apilog_detail['params']);
        //$apilog_detail = $request_params;

        $order_bn = $request_params['tid'];
        $shop_id = $callback_params['shop_id'];

        // 订单
        $oOrder = app::get(self::_APP_NAME)->model('orders');
        $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
        $order_id = $order_detail['order_id'];
        $data = array(
            'order_id' => $order_id,
            'type' => 'payment'
        );

        $api_failObj = app::get(self::_APP_NAME)->model('api_fail');
        $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');
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

        return $this->_caller->callback($result);
    }

    /**
     * 付款单状态更新
     * @access public
     * @param  $payment 
     */
    public function update_payment_status($payment)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if(!$payment) {
            $rs['msg'] = 'no payment';
            return $rs;
        }
       
        // 订单
        $orderObj = app::get(self::_APP_NAME)->model('orders');
        $order = $orderObj->dump($payment['order_id'], 'order_bn');

        $params['tid']         = $order['order_bn'];
        $params['payment_id '] = $payment['payment_bn'];
        $params['oid ']        = '';#子订单id
        $params['status']      = self::$pay_status($payment['status']);
        
        $callback = array(
            'class' => get_class($this),
            'method' => 'update_payment_status_callback',
        );
        
        $title = '店铺('.$this->_shop['name'].')更新[交易支付单状态'.$params['status'].'](订单号:'.$order['order_bn'].'付款单号:'.$payment['payment_bn'].')';

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_PAYMENT_STATUS_RPC,$params,$callback,$title,$this->_shop['shop_id'],10,false,$addon);

        $rs['rsp'] = 'success';
        return $rs;
    }

    public function update_payment_status_callback($result)
    {
        return $this->_caller->callback($result);
    }

    static public $refund_status = array(
          'succ'     =>'SUCC',
          'failed'   =>'FAILED',
          'cancel'   =>'CANCEL',
          'error'    =>'ERROR',
          'invalid'  =>'INVALID',
          'progress' =>'PROGRESS',
          'timeout'  =>'TIMEOUT',
          'ready'    =>'READY',
    );
    /**
     * 添加退款单
     *
     * @param Array $refund 退款单信息
     * @return void
     * @author 
     **/
    public function add_refund($refund)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$refund) {
            $rs['msg'] = 'no refund';
            return $rs;
        }
        //支付方式信息
        $paymentCfgModel = app::get(self::_APP_NAME)->model('payment_cfg');
        $payment_cfg = $paymentCfgModel->dump(array('id'=>$refund['payment']), 'pay_bn,custom_name');
        $refund['pay_bn'] = $payment_cfg['pay_bn'];
        $refund['paymethod'] = $payment_cfg['custom_name'];

        // 订单信息
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        if ($refund['is_archive'] == '1') {
            $orderModel = app::get('archive')->model('orders');
        }
        $order = $orderModel->dump($refund['order_id'], 'order_bn,member_id,shop_id');
        
        // 会员
        $memberModel = app::get(self::_APP_NAME)->model('members');
        $member = $memberModel->dump(array('member_id'=>$order['member_id']),'uname,name,member_id');

        $params = array();
        $params['shop_id']         = $order['shop_id'];
        $params['tid']             = $order['order_bn'];
        $params['refund_id']       = $refund['refund_bn'];
        $params['refund_apply_id'] = $refund['apply_id'];
        $params['buyer_account']   = $refund['account']?$refund['account']:'';
        $params['buyer_bank']      = $refund['bank']?$refund['bank']:'';
        $params['seller_account']  = $refund['pay_account']?$refund['pay_account']:'';
        $params['buyer_name']      = $member['contact']['name'];#买家姓名
        $params['buyer_id']        = $member['account']['uname'];#买家会员帐号
        $params['currency']        = $refund['currency']?$refund['currency']:'CNY';
        $params['refund_fee']      = $refund['money'];
        $params['paycost']         = $refund['paycost']?$refund['paycost']:'';
        $params['currency_fee']    = $refund['cur_money'] ? $refund['cur_money'] : $refund['money'];
        $params['pay_type']        = $refund['pay_type'];
        $params['payment_tid']     = $refund['pay_bn'];
        $params['payment_type']    = $refund['paymethod']?$refund['paymethod']:'';
        $params['t_begin']         = $refund['t_ready'] ? date("Y-m-d H:i:s",$refund['t_ready']) : date("Y-m-d H:i:s");
        $params['t_sent']          = $refund['t_sent'] ? date("Y-m-d H:i:s",$refund['t_sent']) : '';
        $params['t_received']      = $refund['t_received'] ? date("Y-m-d H:i:s",$refund_detail['t_received']) : date("Y-m-d H:i:s");
        $params['status']          = self::$refund_status[$refund['status']];
        $params['memo']            = $refund['memo']?$refund['memo']:'';
        $params['outer_no']        = $refund['trade_no']?$refund['trade_no']:'';
        $params['modify']          = date("Y-m-d H:i:s");

        $callback = array(
            'class' => get_class($this),
            'method' => 'add_refund_callback',
        );

        $title = '店铺('.$this->_shop['name'].')添加[交易退款单(金额:'.$params['refund_fee'].')](订单号:'.$params['tid'].'退款单号:'.$params['refund_id'].')';

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(ADD_REFUND_RPC,$params,$callback,$title,$this->_shop['shop_id'],10,false,$addon);

        $rs['rsp'] = 'success';
        return $rs;
    }

    public function add_refund_callback($result)
    {
        $status = $result->get_status();
        if ($status != 'succ'){
            $request_params = $result->get_request_params();
            $callback_params = $result->get_callback_params();
            $log_id = $callback_params['log_id'];
            $shop_id = $callback_params['shop_id'];

            $oApi_log = app::get(self::_APP_NAME)->model('api_log');
            //$apilog_detail = $oApi_log->dump(array('log_id'=>$log_id), 'params');
            //$apilog_detail = unserialize($apilog_detail['params']);
            //$apilog_detail = $request_params;

            $order_bn = $request_params['tid'];
            $refund_apply_id = $request_params['refund_apply_id'];

            $oOrder = app::get(self::_APP_NAME)->model('orders');
            $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
            if (!$order_detail) {
                $oOrder = app::get('archive')->model('orders');
                $order_detail = $oOrder->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,pay_status');
            }
            $order_id = $order_detail['order_id'];

            //状态回滚，变成已支付/部分付款/部分退款
            kernel::single('ome_order_func')->update_order_pay_status($order_id);

            #bugfix:解决如果退款单请求先到并生成单据于此同时由于网络超时造成退款申请失败，从而造成退款申请单状态错误问题。
            $refund_applyObj = app::get(self::_APP_NAME)->model('refund_apply');
            $refundapply_detail = $refund_applyObj->getList('refund_apply_bn',array('apply_id'=>$refund_apply_id));
            
            $refundsObj = app::get(self::_APP_NAME)->model('refunds');
            $refunds_detail = $refundsObj->getList('refund_id',array('refund_bn'=>$refundapply_detail[0]['refund_apply_bn'],'status'=>'succ'));

            if(!$refunds_detail){
                
                $refund_applyObj->update(array('status'=>'6'), array('apply_id'=>$refund_apply_id));
                //操作日志
                $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');
                $oOperation_log->write_log('order_refund@ome',$order_id,'订单:'.$order_bn.'发起退款请求,前端拒绝退款，退款失败');
            }
        }
        return $this->_caller->callback($result);
    }

    /**
     * 更新退款单状态
     *
     * @param Array $refundinfo 退款单
     * @return Array
     * @author 
     **/
    public function update_refund_status($refundinfo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$refundinfo) {
            $rs['msg'] = 'no refund';
            return $rs;
        }

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump($refundinfo['order_id'], 'order_bn');
        
        $params['tid']        = $order['order_bn'];
        $params['refund_id '] = $refundinfo['refund_bn'];
        $params['oid ']       = '';#子订单id
        $params['status']     = self::$refund_status[$refundinfo['status']];
        
        $callback = array(
            'class' => get_class($this),
            'method' => 'update_refund_status_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新[交易退款状态]:'.$params['status'].'(订单号:'.$order['order_bn'].'退款单号:'.$refundinfo['refund_bn'].')';

        $shop_id = $this->_shop['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_REFUND_STATUS_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }

    public function update_refund_status_callback($result)
    {
        return $this->_caller->callback($result);
    }

    static public $reship_status = array(
          'succ'     =>'SUCC',
          'failed'   =>'FAILED',
          'cancel'   =>'CANCEL',
          'progress' =>'PROGRESS',
          'timeout'  =>'TIMEOUT',
          'ready'    =>'READY',
          'stop'     =>'STOP',
          'back'     =>'BACK'
    );
    /**
     * 添加交易退货单
     *
     * @param Array $reship 退货单信息
     * @return void
     * @author 
     **/
    public function add_reship($reship)
    {   
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$reship) {
            $rs['msg'] = 'no reship';
            return $rs;
        }

        // 订单
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump($reship['order_id'], 'order_bn,member_id');

        // 会员
        $memberModel = app::get(self::_APP_NAME)->model('members');
        $member = $memberModel->dump($order['member_id'],'uname,name,member_id');

        //发货品信息
        $reshipItemModel = app::get(self::_APP_NAME)->model('reship_items');
        $reship_items = $reshipItemModel->getList('product_name,bn,num',array('reship_id'=>$reship['reship_id'],'return_type'=>array('return','refuse')));
        $reshipitems = array();
        if ($reship_items){
            foreach ($reship_items as $k=>$v){
                $v['sku_type'] = 'goods';
                $v['name'] = $v['product_name'];
                $v['number'] = $v['num'];
                unset($v['product_name']);
                unset($v['num']);
                $reshipitems[] = $v;
            }
        }


        $area = $reship['ship_area'];
        if (strpos($area, ":")){
            $area = explode(":", $area);
            $area = explode("/", $area[1]);
        }

        $params['tid']               = $order['order_bn'];
        $params['reship_fee']        = $reship['money'];
        $params['reship_id']         = $reship['reship_bn'];
        $params['buyer_id']          = $member['account']['uname'];
        $params['buyer_uname']       = $member['account']['uname'];
        $params['create_time']       = $reship['t_begin'] ? date("Y-m-d H:i:s",$reship['t_begin']) : date("Y-m-d H:i:s");
        $params['is_protect']        = $reship['is_protect'];
        $params['status']            = self::$reship_status[$reship['status']];

        $params['reship_type']       = $reship['delivery']?$reship['delivery']:'';
        $params['logistics_id']      = $reship['logi_id']?$reship['logi_id']:'';
        $params['logistics_company'] = $reship['logi_name']?$reship['logi_name']:'';
        $params['logistics_no']      = $reship['logi_no']?$reship['logi_no']:'';
        $params['receiver_name']     = $reship['ship_name']?$reship['ship_name']:'';
        $params['receiver_state']    = $area[0]?$area[0]:'';#省
        $params['receiver_city']     = $area[1]?$area[1]:'';#市
        $params['receiver_district'] = $area[2]?$area[2]:'';#县
        $params['receiver_address']  = $reship['ship_addr']?$reship['ship_addr']:'';
        $params['receiver_zip']      = $reship['ship_zip']?$reship['ship_zip']:'';
        $params['receiver_mobile']   = $reship['ship_mobile']?$reship['ship_mobile']:'';
        $params['receiver_email']    = $reship['ship_email']?$reship['ship_email']:'';
        $params['receiver_phone']    = $reship['ship_tel']?$reship['ship_tel']:'';
        $params['memo']              = $reship['memo']?$reship['memo']:'';
        $params['t_begin']           = $reship['t_begin'] ? date("Y-m-d H:i:s",$reship['t_begin']) : date("Y-m-d H:i:s");
        $params['t_end']             = $reship['t_end'] ? date("Y-m-d H:i:s",$reship['t_end']) : date("Y-m-d H:i:s");
        $params['reship_operator']   = kernel::single('desktop_user')->get_login_name();
        $params['reship_items']      = json_encode($reshipitems);
        $params['ship_type']         = 'return';
        $params['modify']            = $reship['t_end'] ? date("Y-m-d H:i:s",$reship['t_end']) : date("Y-m-d H:i:s");

        
        $callback = array(
            'class' => get_class($this),
            'method' => 'add_reship_callback',
        );

        $title = '店铺('.$this->_shop['name'].')添加[交易退货单](订单号:'.$order['order_bn'].'退货单号:'.$reship['reship_bn'].')';

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(ADD_RESHIP_RPC,$params,$callback,$title,$this->_shop['shop_id'],10,false,$addon);

        $rs['rsp'] = 'success';
        return $rs;
    }    

    public function add_reship_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 更改退货单状态
     *
     * @param Array $reship 退货单信息
     * @return void
     * @author 
     **/
    public function update_reship_status($reship)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$reship) {
            $rs['msg'] = 'no reship';
            return $rs;
        }

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump(array('order_id'=>$reship['order_id']), 'order_bn,shop_id');

        $params['tid']       = $order['order_bn'];
        $params['reship_id'] = $reship['reship_bn'];
        $params['oid ']      = '';#子订单id
        $params['status']    = self::$reship_status[$reship['status']];

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_reship_status_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新[交易退货状态]:'.$params['status'].'(订单号:'.$order['order_bn'].'退货单号:'.$reship['reship_bn'].')';

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_RESHIP_STATUS_RPC,$params,$callback,$title,$this->_shop['shop_id'],10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }

    public function update_reship_status_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 获取店铺支付方式
     *
     * @return void
     * @author 
     **/
    public function get_paymethod()
    {
        $params = array();
        $callback = array(
            'class' => get_class($this),
            'method' => 'get_paymethod_callback',
        );

        $title = '同步店铺('.$this->_shop['name'].')的支付方式';

        $this->_caller->request(GET_PAYMETHOD_RPC,$params,$callback,$title,$this->_shop['shop_id']);
    }

    public function get_paymethod_callback($result){

        $status = $result->get_status();
        if($status == 'succ'){
            $cfgObj = app::get(self::_APP_NAME)->model('payment_cfg');
            $payShopObj = app::get(self::_APP_NAME)->model('payment_shop');

            $msg_id = $result->get_msg_id();
            $callback_params = $result->get_callback_params();
            $log_id = $callback_params['log_id'];
            $shop_id = $callback_params['shop_id'];

            $request_params = $result->get_request_params();

            $apiLogObj = app::get(self::_APP_NAME)->model('api_log');
            //$apilog_detail = $apiLogObj->dump(array('log_id'=>$log_id), 'params');
            //$apilog_detail = unserialize($apilog_detail['params']);
            //$apilog_detail = $request_params;
            //$msg_id = $apilog_detail[3]['msg_id'];
            //$node_id = $apilog_detail[1]['to_node_id'];
            
            //$node_id = $request_params['to_node_id'];
            $shopObj = app::get(self::_APP_NAME)->model('shop');
            //$shop = $shopObj->dump(array('node_id'=>$node_id));
            //$shop_id = $shop['shop_id'];

            $data     = $result->get_data();
            $rsp      = $result->get_status();
            $payments = $data;

            if( (is_array($payments) && count($payments)>0 && $shop_id) || ($rsp == 'succ') ) {
                $pay_bn = '';
                foreach((array)$payments as $payment){
                    $pay_bn = $payment['pay_bn'];
                    if(isset($pay_bn) && $pay_bn){
                        $pay_bns[] = $payment['pay_bn'];
                        $pay_type = $payment['pay_type'];

                        $payShopObj->delete(array('pay_bn'=>$pay_bn,'shop_id'=>$shop_id));
                        $payShop = $payShopObj->dump(array('pay_bn'=>$pay_bn), 'pay_bn,shop_id');
                        if(!isset($payShop['shop_id']) && !$payment['shop_id']){
                            $cfgObj->delete(array('pay_bn'=>$pay_bn));
                        }

                        $cfgSdf = array(
                            'custom_name' => $payment['custom_name'],
                            'pay_bn' => $pay_bn,
                            'pay_type' => $pay_type,
                        );
                        $payShopSdf = array(
                            'pay_bn' => $pay_bn,
                            'shop_id' => $shop_id,
                        );
                        $cfgObj->insert($cfgSdf);
                        $payShopObj->insert($payShopSdf);
                    }
                }

                $payShops = $payShopObj->getList('*',array('shop_id'=>$shop_id));
                $pay_bn = '';
                foreach($payShops as $payShop){
                    $pay_bn = $payShop['pay_bn'];
                    if($pay_bn && !in_array($pay_bn,$pay_bns)){
                        $payShopObj->delete(array('pay_bn'=>$pay_bn,'shop_id'=>$shop_id));
                        $payShop = $payShopObj->dump(array('pay_bn'=>$pay_bn), 'pay_bn,shop_id');
                        if(!isset($payShop['shop_id']) && !$payment['shop_id']){
                            $cfgObj->delete(array('pay_bn'=>$pay_bn));
                        }
                    }
                }
                return $this->_caller->callback($result);
            }else{
                $msg = 'fail' . ome_api_func::api_code2msg('re001', '', 'public');
                $apiLogObj->update_log($log_id, $msg, 'fail');
                return array('rsp'=>'fail', 'res'=>$msg, 'msg_id'=>$msg_id);
            }
        }
        return $this->_caller->callback($result);
    }

    /**
     * 获取必要的发货数据
     *
     * @param Array $delivery 发货单信息
     * @return MIX
     * @author 
     **/
    protected function format_delivery($delivery)
    {
        // SHOPEX体系不对售后发货单打接口
        if($delivery['type'] == 'reject') return false;

        $delivery = parent::format_delivery($delivery);

        // 如果是捆绑，取OBJECT上明细
        $orderObjModel = app::get(self::_APP_NAME)->model('order_objects');
        $objCount = $orderObjModel->count(array('order_id'=>$delivery['order']['order_id'],'obj_type'=>'pkg'));
        if ($objCount > 0) {
            $orderObj = $orderObjModel->getList('*',array('order_id'=>$delivery['order']['order_id']));

            // 订单明细
            $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
            $orderItems = $orderItemModel->getList('*',array('order_id'=>$delivery['order']['order_id'],'delete'=>'false'));
            $order_items = array();
            foreach ($orderItems as $key => $item) {
                $order_items[$item['obj_id']][] = $item;
            }
            unset($orderItems);

            $delivery_items = array();
            foreach ($orderObj as $obj) {
                if ($order_items[$obj['obj_id']]) {
                    if ($obj['obj_type'] == 'pkg') {
                        $delivery_items[] = array(
                            'number' => $obj['quantity'],
                            'name' => trim($obj['name']),
                            'bn' => trim($obj['bn']),
                        );    
                    } else {
                        foreach ($order_items[$obj['obj_id']] as $item) {
                            $delivery_items[] = array(
                                'number' => $item['nums'],
                                'name' => trim($item['name']),
                                'bn' => trim($item['bn']),
                            );
                        }
                    }     
                }
            }
            
            $delivery['delivery_items'] = $delivery_items;
        } else {
            /*------------------------------------------------------ */
            //-- 判断订单是否进行拆单
            /*------------------------------------------------------ */
            $chk_split  = parent::check_order_is_split($delivery, true);
            if($chk_split == true)
            {
                #获取发货单关联的订单信息[所属店铺]
                $orderObj      = &app::get('ome')->model('orders');
                
                $order_id      = $delivery['order']['order_id'];
                $order_info    = $orderObj->dump(array('order_id'=>$order_id), 'shop_id, shop_type');
                $shop_type     = $order_info['shop_type'];
                
                #[发货配置]回写发货单方式，按sku方式进行拆单[回写一次] ExBOY
                $split_model   = parent::getDeliverySeting();
                if($split_model == 2 && in_array($shop_type, array('shopex_b2b')))
                {
                    //[订单明细]回写一次，拼接整个订单下的购买商品列表
                    $orderItemModel     = app::get(self::_APP_NAME)->model('order_items');
                    $develiy_items      = $orderItemModel->getList('name, bn, nums as number', array('order_id'=>$delivery['order']['order_id'], 'delete'=>'false'));
                }
                else 
                {
                    //发货单明细
                    $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
                    $develiy_items = $deliItemModel->getList('product_name as name,bn,number',array('delivery_id'=>$delivery['delivery_id']));
                }
            }
            else 
            {
                // 发货单明细
                $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
                $develiy_items = $deliItemModel->getList('product_name as name,bn,number',array('delivery_id'=>$delivery['delivery_id']));
            }

            // 过滤发货单明细中的空格
            foreach((array)$develiy_items as $key=>$item){
                $delivery_items[$key] = array_map('trim', $item);
            }

            $delivery['delivery_items'] = $develiy_items;
        }

        // 会员信息
        $memberModel = app::get(self::_APP_NAME)->model('members');
        $delivery['member'] = $memberModel->dump(array('member_id'=>$delivery['member_id']),'uname,name');

        return $delivery;
    }// TODO TEST

    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {
        $params = array(
            'tid'               => $delivery['order']['order_bn'],
            'shop_id'           => $this->_shop['shop_id'],
            'shipping_fee'      => $delivery['delivery_cost_actual'] ? $delivery['delivery_cost_actual'] :'',
            'shipping_id'       => $delivery['delivery_bn'],
            'create_time'       => date('Y-m-d H:i:s',$delivery['create_time']),
            'is_protect'        => $delivery['is_protect'],
            'is_cod'            => $delivery['is_cod'],
            'buyer_id'          => $delivery['member']['account']['uname'],
            'status'            => self::$ship_status[$delivery['status']],
            'shipping_type'     => $delivery['delivery'] ? $delivery['delivery'] : '',
            'logistics_id'      => $delivery['logi_id'] ? $delivery['logi_id'] : '',
            'logistics_company' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no'      => $delivery['logi_no'] ? $delivery['logi_no'] : '',
            'logistics_code'    => $delivery['dly_corp']['type'],
            'receiver_name'     => $delivery['consignee']['name'] ? $delivery['consignee']['name'] : '',
            'receiver_state'    => $delivery['receiver']['receiver_state'] ? $delivery['receiver']['receiver_state'] : '',
            'receiver_city'     => $delivery['receiver']['receiver_city'] ? $delivery['receiver']['receiver_city'] : '',
            'receiver_district' => $delivery['receiver']['receiver_district'] ? $delivery['receiver']['receiver_district'] : '',
            'receiver_address'  => $delivery['consignee']['addr'] ? $delivery['consignee']['addr'] :'',
            'receiver_zip'      => $delivery['consignee']['zip']?$delivery['consignee']['zip']:'',
            'receiver_email'    => $delivery['consignee']['email']?$delivery['consignee']['email']:'',
            'receiver_mobile'   => $delivery['consignee']['mobile']?$delivery['consignee']['mobile']:'',
            'receiver_phone'    => $delivery['consignee']['telephone']?$delivery['consignee']['telephone']:'',
            'memo'              => $delivery['memo']?$delivery['memo']:'',
            't_begin'           => date('Y-m-d H:i:s',$delivery['create_time']),
            'refund_operator'   => kernel::single('desktop_user')->get_login_name(),
            'shipping_items'    => json_encode($delivery['delivery_items']),
            'ship_type'         => 'delivery',
            'modify'            => date('Y-m-d H:i:s',$delivery['last_modified']),
        );

        return $params;
    }// TODO TEST

    /**
     * 自有平台三步发货添加发货单请求
     *
     * @return void
     * @author 
     **/
    protected function delivery_request($delivery)
    {
        $delivery = $this->format_delivery($delivery);
        if ($delivery === false) return false;

        $param = $this->getDeliveryParam($delivery);
        $callback = array(
           'class' => get_class($this),
           'method' => 'add_delivery_callback',
        );
        $shop_id = $delivery['shop_id'];
        $title = '店铺('.$this->_shop['name'].')添加[交易发货单](订单号:'.$param['tid'].',发货单号:'.$delivery['delivery_bn'].')';
        $addon['bn'] = $delivery['order']['order_bn'];

        $log_id = $this->_caller->request(ADD_SHIPPING_RPC,$param,$callback,$title,$shop_id,10,false,$addon);

        return true;
    }// TODO TEST

    /**
     * 添加发货单回调
     *
     * @return void
     * @author
     **/
    public function add_delivery_callback($result)
    {
        #[发货配置]是否启动拆单 ExBOY
        $split_model   = parent::getDeliverySeting();
        
        $request_params    = $result->get_request_params();
        $status            = $result->get_status();
        
        //[回写]更新发货单状态 ExBOY
        $delivery_bn    = $request_params['shipping_id'];
        if(!empty($delivery_bn) && !empty($split_model))
        {
            $sync_status   = (strtolower($status) == 'succ' ? 'succ' : 'fail');
            $dlysyncModel  = app::get(self::_APP_NAME)->model('delivery_sync');
            $dlysyncModel->update(array('sync'=>$sync_status, 'dateline'=>time()), array('delivery_bn'=>$delivery_bn));
        }
        
        $ret = $this->_caller->callback($result);
        return $ret;
    }

    /**
     * 更新物流信息
     *
     * @param Int $delivery_id 发货单ID
     * @return void
     * @author 
     **/
    public function update_logistics($delivery,$queue = false)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$delivery) {
            $rs['msg'] = 'no delivery';
            return $rs;
        }
        
        //如果发现当前请求是合并后的发货单编辑详情后触发的，直接返回，因为前端没有合并后的发货单。
        if($delivery['is_bind'] == 'true') return false;

        $deliOrderModel = app::get(self::_APP_NAME)->model('delivery_order');
        $deliOrder = $deliOrderModel->dump(array('delivery_id'=>$delivery['delivery_id']));

        // 订单信息
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump(array('order_id'=>$deliOrder['order_id']), 'order_bn');

        $params['tid']         = $order['order_bn'];
        $params['shipping_id'] = $delivery['delivery_bn'];

        // 如果是合并后的发货单，取父发货单物流信息
        $parent_id = $delivery['parent_id'];
        if($parent_id>0) {
            $deliveryModel = app::get(self::_APP_NAME)->model('delivery');
            $pDelivery = $deliveryModel->dump(array('delivery_id'=>$parent_id), 'logi_name,logi_no,logi_id');
            $delivery['logi_name'] = $pDelivery['logi_name'];
            $delivery['logi_no'] = $pDelivery['logi_no'];
            $delivery['logi_id'] = $pDelivery['logi_id'];
        }


        // 物流公司信息
        $dlyCorpModel = app::get(self::_APP_NAME)->model('dly_corp');
        $dlyCorp = $dlyCorpModel->dump(array('corp_id'=>$delivery['logi_id']),'type,name');

        $params['logistics_code']    = $dlyCorp['type'] ? $dlyCorp['type'] : '';
        $params['logistics_company'] = $delivery['logi_name'] ? $delivery['logi_name'] : '';
        $params['logistics_no']      = $delivery['logi_no'] ? $delivery['logi_no'] : '';

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_logistics_callback',
        );


        $shop_id = $delivery['shop_id'];

        $title = '店铺('.$this->_shop['name'].')更改[发货物流信息](订单号:'.$order['order_bn'].',物流单号:'.$params['logistics_no'].',发货单号:'.$delivery['delivery_bn'].')';

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_LOGISTICS_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }// TODO TEST

    /**
     * 更新物流信息回调
     *
     **/
    public function update_logistics_callback($result)
    {
        // 更新运单号
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();

        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];
        $order_bn = $request_params['tid'];

        //$apilog_detail = $oApi_log->dump(array('log_id'=>$log_id), 'params');
        //$apilog_detail = unserialize($apilog_detail['params']);
        //$apilog_detail = $request_params;

        //$order_bn = $apilog_detail[1]['tid'];
        //$shop_id = $apilog_detail[2][2]['shop_id'];

        $shipment_log = app::get(self::_APP_NAME)->model('shipment_log');
        $filter = array(
            'shopId' => $shop_id,
            'orderBn' => $order_bn,
        );
        $data = array(
            'deliveryCode' => $request_params['logistics_no'],
            'deliveryCropName' => $request_params['logistics_company'],
            'deliveryCropCode' => $request_params['logistics_code'],
        );
        $shipment_log->update($data,$filter);

        return $this->_caller->callback($result);
    } // TODO TEST

    /**
     * 更新发货单状态
     *
     * @param Array $delivery 发货单主表信息 
     * @param String $status 发货单状态
     * @param Boolean $queue 是否进队列
     * @return MIX
     * @author 
     **/
    public function update_delivery_status($delivery , $status = '' , $queue = false)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$delivery) {
            $rs['msg'] = 'no delivery';
            return $rs;
        }

        $deliveryModel = app::get(self::_APP_NAME)->model('delivery');
        if ($delivery['is_bind'] == 'true') { // 合单
            // 获取子单
            $delivery_ids = $deliveryModel->getItemsByParentId($delivery['delivery_id'],'array','*');
            if ($delivery_ids){
                foreach($delivery_ids as $v){
                    $this->update_delivery_status($v);
                }
            }
        } else {
            $deliOrderModel = app::get(self::_APP_NAME)->model('delivery_order');
            $deliOrder = $deliOrderModel->dump(array('delivery_id' => $delivery['delivery_id']),'*');

            // 订单主表信息
            $orderObj = app::get(self::_APP_NAME)->model('orders');
            $order = $orderObj->dump(array('order_id'=>$deliOrder['order_id']),'order_id,order_bn');

            if ($delivery['parent_id'] > 0) {
                $pDelivery = $deliveryModel->dump(array('delivery_id'=>$delivery['parent_id']),'logi_name,logi_no,status,logi_id');
                $delivery['logi_name'] = $pDelivery['logi_name'];
                $delivery['logi_no'] = $pDelivery['logi_no'];
                $delivery['logi_id'] = $pDelivery['logi_id'];
                $delivery['status'] = $pDelivery['status'];
            }
            if ($status) {
                $delivery['status'] = $status;
            }
            // 物流公司信息
            $dlyCorpModel = app::get(self::_APP_NAME)->model('dly_corp');
            $dlyCorp = $dlyCorpModel->dump(array('corp_id'=>$delivery['logi_id']),'type,name');

            $param = array(
                'tid'         => $order['order_bn'],
                'shipping_id' => $delivery['delivery_bn'],
                'status'      => self::$ship_status[$delivery['status']],
            );
            $callback = array(
                'class'  => get_class($this),
                'method' =>  'update_delivery_status_callback',
            );
            $shop_id = $delivery['shop_id'];
            $title = '店铺('.$this->_shop['name'].')更新交易发货单状态['.$param['status'].'],订单号:'.$order['order_bn'].'发货单号:'.$delivery['delivery_bn'].')';

            if ($param['status']) {
                $addon['bn'] = $order['order_bn'];
                $log_id = $this->_caller->request(UPDATE_DELIVERY_STATUS_RPC,$param,$callback,$title,$shop_id,10,$queue,$addon);
                
                if($param['status'] == 'SUCC'){
                    /* 自有前端在更新状态时修改状态回写信息 */
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    //增加发货状态日志
                    $log = array(
                        'shopId'           => $shop_id,
                        'ownerId'          => $opInfo['op_id'],
                        'orderBn'          => $order['order_bn'],
                        'deliveryCode'     => $delivery['logi_no'],
                        'deliveryCropCode' => $dlyCorp['type'],
                        'deliveryCropName' => $delivery['logi_name'],
                        'receiveTime'      => time(),
                        'status'           => 'send',
                        'updateTime'       => '0',
                        'message'          => '',
                        'log_id'           => $log_id,
                    );
                    $shipmentLogModel = app::get(self::_APP_NAME)->model('shipment_log');
                    $shipmentLogModel->save($log);
                    //更新订单同步状态
                    $orderObj->update(array('sync'=>'run'),array('order_id'=>$order['order_id']));
                }

                $rs['rsp'] = 'success';
                return $rs;
            }

            return $rs;
        }
    }// TODO TEST

    /**
     * 发货单状态回调
     *
     * @param Object $result 结果对象
     * @return MIX
     * @author 
     **/
    public function update_delivery_status_callback($result)
    {
        #[发货配置]是否启动拆单 ExBOY
        $split_model   = parent::getDeliverySeting();
        
        /* 自有前端在更新状态回调时修改状态回写信息 */
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];
        $orderObj = app::get(self::_APP_NAME)->model('orders');

        //获取返回信息
        $msg = json_decode($result->get_result(), true);
        if($msg){
            $msg = serialize($msg);
        }else{
            $msg = $result->get_result();
        }
        $err_msg = $result->get_err_msg();
        if ($err_msg) {
            $msg .= '：'.$err_msg;
        }
        
        //[回写]更新发货单状态 ExBOY
        $request_params = $result->get_request_params();
        $delivery_bn    = $request_params['shipping_id'];
        if(!empty($delivery_bn) && !empty($split_model))
        {
            $sync_status   = (strtolower($status) == 'succ' ? 'succ' : 'fail');
            $dlysyncModel  = app::get(self::_APP_NAME)->model('delivery_sync');
            $dlysyncModel->update(array('sync'=>$sync_status, 'dateline'=>time()), array('delivery_bn'=>$delivery_bn));
        }

        //更新发货日志并获取日志信息
        $log = array('status' => $status, 'updateTime' => time(), 'message' => $msg);
        $logFilter = array('log_id' => $log_id);
        $shipment_log = app::get(self::_APP_NAME)->model('shipment_log');
        $shipment_log->update($log,$logFilter);
        $res = $shipment_log->dump(array('log_id' => $log_id), '*');

        if ($res) {
            // 订单信息
            $order = $orderObj->dump(array('order_bn' => $res['orderBn'], 'shop_id' => $res['shopId']), '*');
            if ($order) {
                $order_id = $order['order_id'];
                if (trim($order['sync']) <> 'succ') {
                    $status = $status;
                } else {
                    $status = 'succ';
                }
                $sdf = array('order_id' => $order_id, 'sync' => $status, 'up_time' => time());

                //增加同步失败类型
                if($status != 'succ') {
                    $sync_code = $result->get_result();
                    $sync_code = trim($sync_code);
                    switch ($sync_code) {
                        case 'W90010':
                        case 'W90012':
                            $sdf['sync_fail_type'] = 'shipped';
                            break;
                        case 'W90011':
                        case 'W90013':
                        case 'W90014':
                            $sdf['sync_fail_type'] = 'params';
                            break;
                        default:
                            $sdf['sync_fail_type'] = 'none';
                            break;
                    }
                }

                // 更新回写状态
                $orderObj->save($sdf);
            }
        }

        return $this->_caller->callback($result);
    }// TODO TEST

    static public $order_status = array(
        'active' => 'TRADE_ACTIVE',
        'dead'   => 'TRADE_CLOSED',
        'finish' => 'TRADE_FINISHED',
    );
    static public $order_object_type = array(
        'goods' => 'goods',
        'gift'  => 'gift',
    );
    static public $order_pay_status = array(
        '0' => 'PAY_NO',
        '1' => 'PAY_FINISH',
        '2' => 'PAY_TO_MEDIUM',
        '3' => 'PAY_PART',
        '4' => 'REFUND_PART',
        '5' => 'REFUND_ALL',
    );
    static public $order_ship_status = array(
        '0' => 'SHIP_NO',
        '1' => 'SHIP_FINISH',
        '2' => 'SHIP_PART',
        '3' => 'RESHIP_PART',
        '4' => 'RESHIP_ALL',
    );
    /**
     * 更新订单
     *  
     * @param Array $order 订单主表信息
     * @return MIX
     * @author 
     **/
    public function update_order($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');

        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        //店铺信息
        $shop_info = $this->_shop;

        // 订单明细
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump(array('order_id' => $order['order_id']), '*', array('order_objects'=>array('*',array('order_items'=>array('*')))));

        if($order['source'] == 'local') {
            $rs['msg'] = '本地新建订单不同步到前台';
            return $rs;
        }

        $productModel = app::get(self::_APP_NAME)->model('products');
        $specModel    = app::get(self::_APP_NAME)->model('specification');

        $gzip = false;
        $max_orderitems = ome_order_func::get_max_orderitems();
        $object_key = $order_items_num = 0;
        if ($order['order_objects']){
            foreach ($order['order_objects'] as $objects){
                $order_objects['order'][$object_key] = array(
                    'oid'             => $objects['shop_goods_id'],
                    'type'            => self::$order_object_type[$objects['obj_type']] ? self::$order_object_type[$objects['obj_type']] : 'goods',
                    'type_alias'      => $objects['obj_alias'],
                    'iid'             => $objects['shop_goods_id'],
                    'title'           => $objects['name'],
                    'orders_bn'       => $objects['bn'],
                    'items_num'       => $objects['quantity'],
                    'total_order_fee' => $objects['amount'],
                    'weight'          => $objects['weight'],
                    'discount_fee'    => $objects['pmt_price'],
                    'sale_price'      => $objects['sale_price'],
                );
                if ($objects['order_items']){
                    foreach ($objects['order_items'] as $items){
                       $product_id = $items['product_id'];
                       $products_info = $productModel->dump($product_id, 'spec_desc');

                       $product_attr = array();
                       if ($products_info['spec_value']){
                           foreach ($products_info['spec_value'] as $spec_key=>$spec_val){
                               $specification_info = $specModel->dump(array('spec_id'=>$spec_key), 'spec_name');

                               $product_attr[] = $specification_info['spec_name'].":".$spec_val;
                           }
                       }

                       $product_attr = implode(';',$product_attr);
                       $order_objects['order'][$object_key]['order_items']['item'][] = array(
                           'sku_id'         => $product_id,
                           'bn'             => $items['bn'],
                           'name'           => $items['name'],
                           'sku_properties' => $product_attr,
                           'weight'         => $items['weight'],
                           'score'          => $items['score'],
                           'price'          => $items['price'],
                           'total_item_fee' => $items['amount'],
                           'discount_fee'   => $items['pmt_price'],
                           'sale_price'     => $items['sale_price'],
                           'num'            => $items['quantity'],
                           'sendnum'        => $items['sendnum'],
                           'item_type'      => $items['item_type'] ? $items['item_type']:'product',
                           'item_status'    => $items['delete'] == 'false' ? 'normal' : 'cancel',
                       );
                    }
                }

                $order_items_num += count($objects['order_items']);
                $object_key++;
            }
        }


        $pmtModel = app::get(self::_APP_NAME)->model('order_pmt');
        //优惠方案
        $pmt_detail = $pmtModel->getList('pmt_amount as promotion_fee,pmt_describe as promotion_name', array('order_id'=>$order['order_id']));
        $regionLib = kernel::single('ome_func');
        //收货人地区信息
        $area = $order['consignee']['area'];
        $regionLib->split_area($area);

        //买家会员信息
        $memberModel = app::get(self::_APP_NAME)->model('members');
        $members_info = $memberModel->dump(array('member_id'=>$order['member_id']), '*');
        $member_area = $members_info['contact']['area'];
        $regionLib->split_area($member_area);
        //卖家信息
        $shop_id = $order['shop_id'];

        //交易备注
        $oldmemo = unserialize($order['mark_text']);
        $memo    = $oldmemo[count($oldmemo)-1]['op_content'];

        $params = array(
            'tid'                    => $order['order_bn'],
            'created'                => date('Y-m-d H:i:s',$order['createtime']),
            'modified'               => date('Y-m-d H:i:s',$order['last_modified']),
            'status'                 => self::$order_status[$order['status']],
            'pay_status'             => self::$order_pay_status[$order['pay_status']],
            'ship_status'            => self::$order_ship_status[$order['ship_status']],
            'is_delivery'            => $order['is_delivery']=='Y' ? 'true' : 'false',
            'is_cod'                 => $order['shipping']['is_cod'],
            'has_invoice'            => $order['is_tax'],
            'invoice_title'          => $order['tax_title'],
            'invoice_fee'            => $order['cost_tax'],
            'total_goods_fee'        => $order['cost_item'],
            'total_trade_fee'        => $order['total_amount'],
            'total_currency_fee'     => $order['total_amount'],
            'discount_fee'           => $order['discount'],
            'goods_discount_fee'     => $order['pmt_goods'],
            'orders_discount_fee'    => $order['pmt_order'],
            'promotion_details'      => $pmt_detail ? json_encode($pmt_detail) : '',
            'payed_fee'              => $order['payed'],
            'currency'               => $order['currency'] ? $order['currency'] : 'CNY',
            'currency_rate'          => $order['cur_rate'],
            'pay_cost'               => $order['payinfo']['cost_payment'],
            'buyer_obtain_point_fee' => $order['score_g'],
            'point_fee'              => $order['score_u'],
            'shipping_type'          => $order['shipping']['shipping_name'],
            'shipping_fee'           => $order['shipping']['cost_shipping'],
            'is_protect'             => $order['shipping']['is_protect'],
            'protect_fee'            => $order['shipping']['cost_protect'],
            'payment_type'           => $order['payinfo']['pay_name'],
            'receiver_name'          => $order['consignee']['name'],
            'receiver_email'         => $order['consignee']['email'],
            'receiver_state'         => $area[0],
            'receiver_city'          => $area[1],
            'receiver_district'      => $area[2],
            'receiver_address'       => $order['consignee']['addr'],
            'receiver_zip'           => $order['consignee']['zip'],
            'receiver_mobile'        => $order['consignee']['mobile'],
            'receiver_phone'         => $order['consignee']['telephone'],
            'receiver_time'          => $order['consignee']['r_time'],
            'buyer_uname'            => $members_info['account']['uname'],
            'buyer_name'             => $members_info['contact']['name'],
            'buyer_mobile'           => $members_info['contact']['phone']['mobile'],
            'buyer_phone'            => $members_info['contact']['phone']['telephone'],
            'buyer_email'            => $members_info['contact']['email'],
            'buyer_state'            => $member_area[0],
            'buyer_city'             => $member_area[1],
            'buyer_district'         => $member_area[2],
            'buyer_address'          => $members_info['contact']['addr'],
            'buyer_zip'              => $members_info['contact']['zipcode'],
            'seller_mobile'          => $shop_info['mobile'],
            'seller_phone'           => $shop_info['tel'],
            'seller_name'            => $shop_info['default_sender'],
            'total_weight'           => $order['weight'],
            'orders'                 => $order_objects ? json_encode($order_objects) : '',
        );

        $title = '店铺('.$shop_info['name'].')订单编辑(订单号:'.$order['order_bn'].')';

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_callback',
        );

        if ($order_items_num > $max_orderitems){
            $gzip = true;
        }
        //$params['gzip'] = $gzip;TODO:暂时不走GZIP
        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }// TODO TEST

    /**
     * 更新订单回调
     *
     * @return void
     * @author 
     **/
    public function update_order_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 更新订单状态
     *
     * @param int $order_id 订单主键ID
     * @param string $status 状态
     * @param string $memo 备注
     * @param string $mode 请求类型:sync同步  async异步
     * @return void
     * @author 
     **/
    public function update_order_status($order , $status='' , $memo='' , $mode='sync')
    {
        $rs = array('rsp'=>'fail','msg'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $shop_id = $order['shop_id'];
        $shop    = $this->_shop;

        $order_status = $status ? $status : $order['status'];
        
        $params['tid']                    = $order['order_bn'];
        $params['status']                 = self::$order_status[$order_status];
        $params['type']                   = 'status';
        $params['modify']                 = date('Y-m-d H:i:s', time());
        $params['is_update_trade_status'] = 'true';

        if ($order_status == 'dead'){
            //订单取消理由
            $params['reason'] = $memo;
        }

        $title = '店铺('.$shop['name'].')更新[订单状态]:'.$params['status'].'(订单号:'.$order['order_bn'].')';

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_status_callback',
        );

        if($mode == 'sync'){
            $rsp = $this->_caller->call(UPDATE_TRADE_STATUS_RPC,$params,$shop_id,10);

            $oApi_log = app::get(self::_APP_NAME)->model('api_log');
            $log_id = $oApi_log->gen_id();

            $callback = array(
            'class'   => get_class($this),
            'method'  => __METHOD__,
            '2'       => array(
                'log_id'  => $log_id,
                'shop_id' => $shop_id,
            ),
            );
            $addon['bn'] = $order['order_bn'];

            $oApi_log->write_log($log_id,$title,'apibusiness_router_request','update_order_status',array(UPDATE_TRADE_STATUS_RPC, $params, $callback),'','request','running','','','api.store.trade',$addon['bn']);

            if($rsp->rsp == 'succ'){
                $api_status = 'success';
                $msg = '订单状态更新成功<BR>';
                $oApi_log->update(array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
            }else{
                $api_status = 'fail';
                $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                $msg = '订单状态更新失败('.$err_msg.')<BR>';
                $oApi_log->update(array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
            }

            $result['rsp']     = $rsp->rsp;
            $result['err_msg'] = $rsp->err_msg;
            $result['msg_id']  = $rsp->msg_id;
            $result['res']     = $rsp->res;
            $result['data']    = json_decode($rsp->data,1);
        }else{
            $addon['bn'] = $order['order_bn'];

            $result = $this->_caller->request(UPDATE_TRADE_STATUS_RPC,$params,$callback,$title,$shop_id,10,false,$addon);
        }

        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];
        $rs['data'] = $result['data'];

        return $rs;
    }// TODO TEST

    /**
     * 订单状态更新回调
     *
     * @return void
     * @author 
     **/
    public function update_order_status_callback($result)
    {
        return $this->_caller->callback($result);
    }// TODO TEST

    /**
     * 更新订单发票信息
     *
     * @param Array $order 订单信息
     * @return MIX
     * @author 
     **/
    public function update_order_tax($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $params['tid']    = $order['order_bn'];
        $params['tax_no'] = $order['tax_no'];

        $title = '店铺('.$this->_shop['name'].')更新[订单发票号]:'.$order['tax_no'].'(订单号:'.$order['order_bn'].')';

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_tax_callback',
        );

        $shop_id = $this->_shop['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_TAX_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }// TODO TEST

    /**
     * 更新订单发票回调
     *
     * @return void
     * @author 
     **/
    public function update_order_tax_callback($result)
    {
        return $this->_caller->callback($result);
    }// TODO TEST

    /**
     * 更新订单发货状态
     *
     * @param int $order_id 订单主键ID
     * @param boolean $queue 是否走队列
     * @return void
     * @author 
     **/
    public function update_order_ship_status($order,$queue = false)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params['tid']         = $order['order_bn'];
        $params['ship_status'] = self::$order_ship_status[$order['ship_status']];

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_ship_status_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新[订单发货状态]:'.$params['ship_status'].'(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_SHIP_STATUS_RPC,$params,$callback,$title,$shop_id,10,$queue,$addon);

        $rs['rsp'] = 'success';
        return $rs;
    }// TODO TEST

    /**
     * 更新订单发货状态回调
     *
     * @return void
     * @author 
     **/
    public function update_order_ship_status_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 更新订单支付状态
     *
     * @param array $order 订单主表信息
     * @return MIX
     * @author 
     **/
    public function update_order_pay_status($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }
        $params['tid'] = $order['order_bn'];

        $params['pay_status'] = self::$order_pay_status[$order['pay_status']];
       
        $title = '店铺('.$this->_shop['name'].')更新[订单支付状态]:'.$params['pay_status'].'(订单号:'.$order['order_bn'].')';

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_pay_status_callback',
        );

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_PAY_STATUS_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';
        return $rs;
    }// TODO TEST

    /**
     * 更新支付状态回调
     *
     * @return void
     * @author 
     **/
    public function update_order_pay_status_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 更新订单交易备注
     *
     * @param int $order 订单主表信息
     * @param array $memo 备注内容
     * @return MIX
     * @author 
     **/
    public function update_order_memo($order,$memo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $params['tid']      = $order['order_bn'];
        $params['memo']     = $memo['op_content'];
        $params['flag']     = self::$order_mark_type[$order['mark_type']] ? self::$order_mark_type[$order['mark_type']] : '';
        $params['sender']   = $memo['op_name'];
        $params['add_time'] = $memo['op_time'];

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_memo_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新订单备注(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_MEMO_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }// TODO TEST

    public function update_order_memo_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 添加订单交易备注
     *
     * @param int $order_id 订单主键ID
     * @param array $memo 备注内容
     * @return void
     * @author 
     **/
    public function add_order_memo($order,$memo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $params['tid']      = $order['order_bn'];
        $params['memo']     = $memo['op_content'];
        $params['flag']     = self::$order_mark_type[$order['mark_type']] ? self::$order_mark_type[$order['mark_type']] : '';
        $params['sender']   = $memo['op_name'];
        $params['add_time'] = $memo['op_time'];

        $callback = array(
            'class' => get_class($this),
            'method' => 'add_order_memo_callback',
        );

        $title = '店铺('.$this->_shop['name'].')添加订单备注(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];
 
        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(ADD_TRADE_MEMO_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }

    public function add_order_memo_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 添加买家留言
     *
     * @param array $order 订单主表信息
     * @param array $memo 留言
     * @return void
     * @author 
     **/
    public function add_order_custom_mark($order,$memo)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $params['tid']      = $order['order_bn'];
        $params['message']  = $memo['op_content'];
        $params['sender']   = $memo['op_name'];
        $params['add_time'] = $memo['op_time'];

        $callback = array(
            'class' => get_class($this),
            'method' => 'add_order_custom_mark_callback',
        );

        $title = '店铺('.$this->_shop['name'].')添加订单附言(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(ADD_TRADE_BUYER_MESSAGE_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';

        return $rs;
    }

    public function add_order_custom_mark_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 更新交易收货人信息
     *
     * @param Array $order 订单信息
     * @return void
     * @author 
     **/
    public function update_order_shippinginfo($order)
    { 
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $consignee_area = $order['consignee']['area'];
        if(strpos($consignee_area,":")){
            $t_area            = explode(":",$consignee_area);
            $t_area_1          = explode("/",$t_area[1]);
            $receiver_state    = $t_area_1[0];
            $receiver_city     = $t_area_1[1];
            $receiver_district = $t_area_1[2];
        }

        $params['tid']               = $order['order_bn'];
        $params['receiver_name']     = $order['consignee']['name']?$order['consignee']['name']:'';
        $params['receiver_state']    = $receiver_state?$receiver_state:'';
        $params['receiver_city']     = $receiver_city?$receiver_city:'';
        $params['receiver_district'] = $receiver_district?$receiver_district:'';
        $params['receiver_address']  = $order['consignee']['addr']?$order['consignee']['addr']:'';
        $params['receiver_zip']      = $order['consignee']['zip']?$order['consignee']['zip']:'';
        $params['receiver_email']    = $order['consignee']['email']?$order['consignee']['email']:'';
        $params['receiver_mobile']   = $order['consignee']['mobile']?$order['consignee']['mobile']:'';
        $params['receiver_phone']    = $order['consignee']['telephone']?$order['consignee']['telephone']:'';
        $params['receiver_time']     = $order['consignee']['r_time']?$order['consignee']['r_time']:'';

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_shippinginfo_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新[交易收货人信息]:'.$params['receiver_name'].'(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_SHIPPING_ADDRESS_RPC,$params,$callback,$title,$shop_id,10,false,$addon);
        
        $rs['rsp'] = 'success';

        return $rs;
    }// TODO TEST

    public function update_order_shippinginfo_callback($result)
    {
        return $this->_caller->callback($result);
    }// TODO TEST

    /**
     * 更新交易发货人信息
     *
     * @param Array $order 订单信息
     * @return MIX
     * @author 
     **/
    public function update_order_consignerinfo($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $consigner_area = $order['consigner']['area'];
        kernel::single('ome_func')->split_area($consigner_area);

        $params['tid']              = $order['order_bn'];
        $params['shipper_name']     = $order['consigner']['name'];
        $params['shipper_state']    = $consigner_area[0];
        $params['shipper_city']     = $consigner_area[1];
        $params['shipper_district'] = $consigner_area[2];
        $params['shipper_address']  = $order['consigner']['addr'];
        $params['shipper_zip']      = $order['consigner']['zip'];
        $params['shipper_email']    = $order['consigner']['email'];
        $params['shipper_mobile']   = $order['consigner']['mobile'];
        $params['shipper_phone']    = $order['consigner']['tel'];

        $callback = array(
            'class'  => get_class($this),
            'method' => 'update_order_consignerinfo_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新[交易发货人信息]:'.$params['consigner_name'].'(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_SHIPPER_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';
        return $rs;
    }

    public function update_order_consignerinfo_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 更新代销人信息
     *
     * @return void
     * @author 
     **/
    public function update_order_sellagentinfo($order)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $sellagentObj = app::get(self::_APP_NAME)->model('order_selling_agent');
        $sellagent_detail = $sellagentObj->dump(array('order_id' => $order['order_id']), '*');

        $sellagent_area = $sellagent_detail['member_info']['area'];
        kernel::single('ome_func')->split_area($sellagent_area);

        $params = array(
            'tid'             => $order['order_bn'],
            '_uname'          => $sellagent_detail['member_info']['uname'],
            '_name'           => $sellagent_detail['member_info']['name'],
            '_birthday'       => $sellagent_detail['member_info']['birthday'],
            '_sex'            => $sellagent_detail['member_info']['sex'],
            '_state'          => $sellagent_area[0],
            '_city'           => $sellagent_area[1],
            '_district'       => $sellagent_area[2],
            '_address'        => $sellagent_detail['member_info']['addr'],
            '_zip'            => $sellagent_detail['member_info']['zip'],
            '_email'          => $sellagent_detail['member_info']['email'],
            '_mobile'         => $sellagent_detail['member_info']['mobile'],
            '_phone'          => $sellagent_detail['member_info']['tel'],
            '_website_name'   => $sellagent_detail['website']['name'],
            '_website_domain' => $sellagent_detail['website']['domain'],
        );

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_sellagentinfo_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新[交易代销人信息]:(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_SELLING_AGENT_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';
        return $rs;
    }

    public function update_order_sellagentinfo_callback($result)
    {
        return $this->_caller->callback($result);
    }

    /**
     * 更新订单失效时间
     *
     * @param array $order 订单
     * @param string $order_limit_time 订单失效时间
     * @return void
     * @author 
     **/
    public function update_order_limittime($order,$order_limit_time)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $params['tid']              = $order['order_bn'];
        $params['order_limit_time'] = $order_limit_time;

        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_limittime_callback',
        );

        $title = '更新店铺('.$this->_shop['name'].')订单失效时间(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_ORDER_LIMITTIME_RPC,$params,$callback,$title,$shop_id,10,false,$addon);

        $rs['rsp'] = 'success';
        return $rs;
    }

    public function update_order_limittime_callback($result)
    {
        return $this->_caller->callback($result);
    }
}