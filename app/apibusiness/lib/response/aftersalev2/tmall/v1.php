<?php
class apibusiness_response_aftersalev2_tmall_v1 extends apibusiness_response_aftersalev2_v1{
     
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        if (($this->_refundsdf['spider_type'] == 'tm_refund') || ($this->_refundsdf['spider_type'] != 'tm_refund_i')) {
            $this->_apiLog['info']['msg'] = '天猫售后老接口数据，不接受';
            return false;
        }
        if ($this->_refundsdf['status'] == 'success') {
            if (bccomp($tgOrder['payed'], $this->_refundsdf['refund_fee'],3) < 0) {
                $this->_apiLog['info']['msg'] = '退款失败,支付金额('.$tgOrder['payed'].')小于退款金额('.$this->_refundsdf['refund_fee'].')';
                return false;        
            }
        }

        // 订单状态判断
        if ($tgOrder['process_status'] == 'cancel') {
            $this->_apiLog['info']['msg'] = '订单['.$this->_refundsdf['tid'].']已经取消，无法退款';
            return false;
        }
        return true;
    }

    
    /**
     * 添加退款单
     *
     * @return void
     * @author 
     **/
    public function add(){
        if (($this->_refundsdf['spider_type'] == 'tm_refund') || ($this->_refundsdf['spider_type'] != 'tm_refund_i')) {
            $this->_apiLog['info']['msg'] = '天猫售后老接口数据，不接受';
            return false;
        }
        parent::add();
        $refund_type = $this->_refundsdf['refund_type'];
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order_bn  = $this->_refundsdf['tid'];
        $shop_id     = $this->_shop['shop_id'];
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'pay_status,status,process_status,order_id,payed,cost_payment,ship_status');
       
        if ($refund_type == 'return') {
            //判断是否已发货
            if (in_array($tgOrder['ship_status'],array('0'))) {
                $this->refund_add();
                //成功后的操作
                
            }else{
                $this->aftersale_add();
            }
        }else if($refund_type == 'reship'){
            $this->reship_add();
        }else{
            #退款单
            $this->refund_add();
        }
    }
    

    
    /**
     * 退款单添加
     * @param   
     * @return 
     * @access  public
     * @author cyyr24@sina.cn
     */
    protected function refund_add(){
        // 日志
        $this->_apiLog['title']  = '前端店铺退款业务处理[订单：' . $this->_refundsdf['tid'].']';
        // 店铺
        $shop_type   = $this->_shop['shop_type'];
        $shop_name   = $this->_shop['name'];
        $shop_id     = $this->_shop['shop_id'];
        if ($shop_type == 'taobao') {
            if (strtoupper($this->_shop['tbbusiness_type']) == 'B') {
                $shop_type = 'tmall';
            }
        }
        
        $status      = self::$refund_status[strtoupper($this->_refundsdf['status'])];
        $oRefund_tmall = app::get(self::_APP_NAME)->model('refund_apply_tmall');
        $refundMoney = (float)$this->_refundsdf['refund_fee'];
        $refund_bn   = $this->_refundsdf['refund_id'];
        $refund_type = $this->_refundsdf['refund_type'];
        $order_bn    = $this->_refundsdf['tid'];
        $productModel = app::get(self::_APP_NAME)->model('products');
        $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
        // 退款单号验证
        if (!$refund_bn) {
            $this->_apiLog['info']['msg'] = 'no refund bn';
            $this->exception(__METHOD__);
        }
        // 订单号验证
        if (!$order_bn) {
            $this->_apiLog['info']['msg'] = 'no order bn';
            $this->exception(__METHOD__);
        }
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'pay_status,status,process_status,order_id,payed,cost_payment,ship_status');
        if (!$tgOrder) {
            $this->_apiLog['info']['msg'] = 'no order in TAOGUAN';
            $this->exception(__METHOD__ , 'true');
        }

        // 状态值验证
        if ($status == '') {
            $this->_apiLog['info']['msg'] = 'status is empty 或为不接受状态值!';
            $this->exception(__METHOD__);
        }

        // 退款金额验证
        if ($refundMoney <= 0) {
            $this->_apiLog['info']['msg'] = 'error refund money';
            $this->exception(__METHOD__);
        }

        $refundApplyModel = app::get(self::_APP_NAME)->model('refund_apply');
        // memo里是否带了退款申请单
        $refund_apply_bn = $refund_bn;
        if (preg_match('/#(\d+)#/', $this->_refundsdf['memo'],$matches)) {
            $refund_apply_bn = $matches[1];
        }

        // 退款单是否存在验证
        $refundModel = app::get(self::_APP_NAME)->model('refunds');
        $refund = $refundModel->dump(array('refund_bn'=>$refund_bn,'shop_id'=>$shop_id));
        if ($refund) {
            // 退款单存在，更新退款申请单状态(已退款)
            $refundApplyModel->update(array('status' => '4'),array('refund_apply_bn' => $refund_apply_bn,'shop_id' => $shop_id));

            $this->_apiLog['info']['msg'] = "退款单[{$refund_bn}]已经存在，更新退款申请单[{$refund_apply_bn}]状态：已退款！";
            $this->exception(__METHOD__);
        }

        // 退款申请单
        $refundApply = $refundApplyModel->dump(array('refund_apply_bn'=>$refund_apply_bn,'shop_id'=>$shop_id));
        if ($tgOrder['status'] != 'active' && $refundApply['status'] == '4') {
            $this->_apiLog['info']['msg'] = "订单[{$order_bn}]不是活动订单且退款申请单[{$refund_apply_bn}]已退款，无法退款！";
            $this->exception(__METHOD__);
        }
        $apiversion_diff = 0;
        if ($refundApply) {
            $refund_tmall = $oRefund_tmall->dump(array('apply_id'=>$refundApply['apply_id'],'shop_id'=>$shop_id));
            if ($this->_refundsdf['refund_version']>$refund_tmall['refund_version']) {
                $apiversion_diff = 1;
            }
        }
        if (in_array($refundApply['status'], array('4'))) {
            $this->_apiLog['info']['msg'] = "退款申请单[{$refund_apply_bn}]已退款，无法退款！";
            $this->exception(__METHOD__);
            
        }
        if (in_array($refundApply['status'], array('3'))) {
            #查看版本号是否变化
            if ($apiversion_diff == 0) {
                $this->_apiLog['info']['msg'] = "退款申请单[{$refund_apply_bn}]已拒绝，无法退款！";
                $this->exception(__METHOD__);
            }
        }
        
        // 判断是否允许接收
        $canAccept = $this->canAccept($tgOrder);
        if ($canAccept === false) {
            $this->exception(__METHOD__);
        }
        // 退款单
        $order_id = $tgOrder['order_id'];
        $this->_refundsdf['created']= kernel::single('ome_func')->date2time($this->_refundsdf['created']);
        $this->_refundsdf['t_ready']    = $this->_refundsdf['created'];
        $this->_refundsdf['t_sent']     = $this->_refundsdf['modified'];
        $this->_refundsdf['t_received'] = '';
        $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');//写日志
        if ($status == '4') {

            // 取出退款申请单据编号
            if ($this->_refundsdf['reason']){
                $this->_refundsdf['memo'] = preg_replace('/#(\d+)#/', '', $this->_refundsdf['reason']);
            }
            $sdf = array(
                'refund_bn'     => $refund_bn,
                'shop_id'       => $shop_id,
                'order_id'      => $tgOrder['order_id'],
                'currency'      => 'CNY',
                'money'         => $refundMoney,
                'cur_money'     => $this->_refundsdf['cur_money'] ? $this->_refundsdf['cur_money'] : $refundMoney,
                'pay_type'      => $this->_refundsdf['pay_type'] ? $this->_refundsdf['pay_type'] : 'online',
               'download_time' => time(),
                'status'        => 'succ',
                'memo'          => $this->_refundsdf['memo'],
                'trade_no'      => $this->_refundsdf['alipay_no'],
                'modifiey'      => $this->_refundsdf['modified'],

            );
            $pay_bn = $this->_refundsdf['payment'];
            if ($pay_bn) {
                $payment_cfg = kernel::single('apibusiness_adapter_payment_cfg')->get_payment($pay_bn,$shop_type);
                $sdf['payment'] = $payment_cfg['id'];
            }
            $sdf['t_ready']    = $this->_refundsdf['t_ready'] ? $this->_refundsdf['t_ready'] : $this->_refundsdf['t_sent'];
            $sdf['t_sent']     = $this->_refundsdf['t_sent'] ? $this->_refundsdf['t_sent'] : $this->_refundsdf['t_ready'];
            $sdf['t_received'] = $this->_refundsdf['t_received'];
            $rs = $refundModel->save($sdf);
            if (!$rs) {
                $this->_apiLog['info']['msg'] = "创建退款单失败!";
                $this->exception(__METHOD__);
            }

            $this->_apiLog['info'][] = "创建退款单SDF结构：".var_export($sdf,true);

            if ($refundApply['apply_id']) {
                $filter = array(
                    'apply_id' => $refundApply['apply_id'],
                );
                $updateData = array('status' => '4','refunded' => $refundMoney);
                $refundApplyModel->update($updateData,$filter);
                $this->_apiLog['info'][] = "更新退款申请单[{$refund_apply_bn}]状态：".var_export($updateData,true);

                // 更新售后申请单的退款金额
                //$refundApply = $refundApplyModel->dump($filter,'addon');
                if ($refundApply['addon']) {
                    $addon = unserialize($refundApply['addon']);
                    $return_id = $addon['return_id'];

                    if ($return_id) {
                        $pReturnModel = app::get(self::_APP_NAME)->model('return_product');
                        $pReturn = $pReturnModel->dump(array('return_id' => $return_id),'refundmoney,return_bn');
                        $refundMoney = bcadd((float)$refundMoney, (float)$pReturn['refundmoney'],3);
                        $pReturnModel->update(array('refundmoney'=>$refundMoney),array('return_id'=>$return_id));

                        $this->_apiLog['info'][] = "更新售后申请单[{$return_bn}]金额：".$refundMoney;
                    }
                }
            }
            
            #更新订单状态
            $this->_updateOrder($tgOrder['order_id'],$refundMoney);

            //订单是否当然状态为部分退款，如果是暂停订单打上部分退款放入异常订单中
            $this->_checkAbnormal($tgOrder['order_id']);

            $this->_apiLog['info'][] = "更新订单[{$order_bn}]支付状态";       

        } else{
            
            // 判断申请退款单是否已经存在
            if ($refundApply['apply_id'] && $status == '0' && ($refundApply['memo'] == $this->_refundsdf['reason']) && ($refundApply['money'] == $refundMoney) ){
               
                if ($apiversion_diff == 0) {
                    $this->_apiLog['info']['msg'] = "退款申请单[{$refundApply['refund_apply_bn']}]已经存在 版本无变化,且status等于{$refundApply['status']}";
                    $this->exception(__METHOD__);
                }
            }
            
            if ($status == '0' || !$refundApply['apply_id']) {
                $addon = serialize(array('refund_bn'=>$refund_bn));
                $sdf = array(
                    'order_id'        => $order_id,
                    'refund_apply_bn' => $refund_bn,
                    'pay_type'        => $this->_refundsdf['pay_type'] ? $this->_refundsdf['pay_type'] : 'online',
                    'account'         => $this->_refundsdf['account'],
                    'bank'            => $this->_refundsdf['bank'],
                    'pay_account'     => $this->_refundsdf['pay_account'],
                    'money'           => $refundMoney ? $refundMoney : '0',
                    'refunded'        => '0',
                    'memo'            => $this->_refundsdf['reason'],
                    'create_time'     => $this->_refundsdf['created'],
                    'status'          => $status,
                    'shop_id'         => $shop_id,
                    'addon'           => $addon,
                    'source'          => 'matrix',
                    'shop_type'         =>$shop_type,
                    'outer_lastmodify'=> $this->_refundsdf['modified'],
                );
                if ($refundApply['apply_id'] && $apiversion_diff == 1) {
                    $sdf['apply_id'] = $refundApply['apply_id'];
                }
                if (in_array($tgOrder['ship_status'],array('1','3'))) {
                    $sdf['refund_refer'] = '1';
                }
                $item_list = $this->_refundsdf['refund_item_list'];
               
                $product_data = array();
                foreach ($item_list as $item) {
                    $bn = $item['bn'];
                    $product = $productModel->dump(array('bn'=>$bn));
                    $productList[$product['bn']] = $product;

                    $order_items = $orderItemModel->getList('item_id,order_id,bn,sendnum',array('order_id'=>$tgOrder['order_id'],'bn'=>$bn,'delete'=>'false'));
                    if (!$order_items) {
                        //$this->_apiLog['info']['msg'] = "返回值：订单明细不存在，货号[{$bn}]";
                        //$this->exception(__METHOD__);
                    }
                    $product_data[] = array(
                        'product_id'    => $productList[$item['bn']]['product_id'],
                        'bn'            => $bn,
                        'name'          => $item['title'] ? $item['title'] :$productList[$bn]['name'],
                        'num'           => $item['num'],
                        'price'         =>  $item['price'],
                        'oid'           =>  $item['oid'],
                        'refund_phase'  => $item['refund_phase'],
                        'refund_memo'   =>$item['refund_memo'],
                        'modified'      => kernel::single('ome_func')->date2time($item['modified']),
                    );
                    $oid = $item['oid'];
                }
                if ($product_data) {
                    $sdf['product_data'] = serialize($product_data);
                }
               
                $refundApplyModel->create_refund_apply($sdf);
                $this->refund_additional($sdf);
                $this->_apiLog['info'][] = '创建退款申请单SDF结构：'.var_export($sdf,true);
                $memo = '';
                if ($apiversion_diff == 1) {
                    $memo.='由于退款版本发生变化';
                }
                
                $oOperation_log->write_log('refund_apply@ome',$sdf['apply_id'],$memo."创建退款申请单");
            } else {
                $memo = '';
                $updateData = array(
                    'status' => $status,
                    'outer_lastmodify'=> $this->_refundsdf['modified'],
                );
                $filter = array(
                    'shop_id'         => $shop_id,
                    'refund_apply_bn' => $refund_bn,
                );
                if ( $apiversion_diff == 1 ) {
                    $updateData['memo']  = $this->_refundsdf['reason'];
                    $updateData['money'] = $this->_refundsdf['refund_fee'];

                    $uptmData = array(
                        'refund_version'=>$this->_refundsdf['refund_version'],
                        'good_status'=>$this->_refundsdf['good_status'],
                    );
                    $tmfilter = array('shop_id'=>$shop_id,'refund_apply_bn'=>$refund_bn);
                    $oRefund_tmall->update($uptmData,$tmfilter);
                    
                    $refundApplyModel->update($updateData,$filter);
                    $memo.='由于退款版本发生变化,更新退款申请单[{$refund_bn}]状态';
                }else{
                    if ( $this->_refundsdf['modified'] > $refundApply['outer_lastmodify']) {
                        if (($refundApply['memo'] == $this->_refundsdf['reason']) && ($refundApply['money'] == $refundMoney)) {
                            $refundApplyModel->update($updateData,$filter);
                            $memo.="更新退款申请单[{$refund_bn}]状态：{$status}";

                        }else{
                            $memo.='由于退款金额或备注不一致更新退款申请单[{$refund_bn}]状态：{$status}失败';
                        }
                    }
                }
                if ($memo) {
                    $oOperation_log->write_log('refund_apply@ome',$refundApply['apply_id'],$memo);
                    $this->_apiLog['info'][] = "更新退款申请单[{$refund_bn}]状态：{$status}";
                }
                
            }

            kernel::single('ome_order_func')->update_order_pay_status($order_id);

            $this->_apiLog['info'][] = "更新订单[{$order_id}]支付状态";

        }
        $this->_apiLog['info'][] = 'O.K';
    }

    protected function aftersale_add(){
        
        $this->_apiLog['title']  = '前端店铺售后申请接口[售后单号：'.$this->_refundsdf['refund_id'].' ]';
        $order_bn  = $this->_refundsdf['tid'];
        $return_bn = $this->_refundsdf['refund_id'];
        $shop_id   = $this->_shop['shop_id'];
        $shop_type = $this->_shop['shop_type'];
        if ($shop_type == 'taobao') {
            if (strtoupper($this->_shop['tbbusiness_type']) == 'B') {
                $shop_type = 'tmall';
            }
        }
        $status    = self::$return_status[strtoupper($this->_refundsdf['status'])];
        if ($status == '') {
            $this->_apiLog['info']['msg'] = '返回值：售后状态不能为空或为不接收状态值';
            $this->exception(__METHOD__);
        }
        $oReturn_tmall = app::get(self::_APP_NAME)->model('return_product_tmall');
        // 售后单
        $returnModel = app::get(self::_APP_NAME)->model('return_product');
        
        $tgReturn = $returnModel->dump(array('shop_id'=>$shop_id,'return_bn'=>$return_bn));
        $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');//写日志
        // 订单
        
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn));
        $oReship_item = app::get(self::_APP_NAME)->model ( 'reship_items' );
        if (empty($tgOrder)) {
            $this->_apiLog['info']['msg'] = '返回值：订单不存在';
            $this->exception(__METHOD__);
        }

        if ($tgOrder['ship_status'] == '0') {
            $this->_apiLog['info']['msg'] = '返回值：订单未发货不能申请售后';
            $this->exception(__METHOD__);
        }

        if ($tgOrder['ship_status'] == '4') {
            $this->_apiLog['info']['msg'] = '返回值：订单已经退货不能申请售后';
            $this->exception(__METHOD__);
        }
        #判断版本变化逻辑
        $apiversion_diff = 0;
        if ($tgReturn) {
            $return_tmall = $oReturn_tmall->dump(array('shop_id'=>$shop_id,'return_bn'=>$return_bn),'refund_version');
            if ($return_tmall && ($this->_refundsdf['refund_version']>$return_tmall['refund_version'])) {
                $apiversion_diff = 1;
            }
        }
        
        if ($tgReturn['status'] == '4' || $tgReturn['status'] == '9') {
            $this->_apiLog['info']['msg'] = '返回值：此售后单已经完成';
            $this->exception(__METHOD__);
        }
        #确认如果版本变化，清空现有单据
        if ($apiversion_diff == 1) {
            $this->cleanReturnstatus($tgReturn);
        }
      
        if ($tgReturn['status'] == '5') {
            if ($apiversion_diff == 0) {
                $this->_apiLog['info']['msg'] = '返回值：此售后单已经拒绝';
                $this->exception(__METHOD__);
            }
            
        }
        if ($status == '1' || !$tgReturn['return_id']) {
            
            // 判断申请退款单是否已经存在
            if ($tgReturn['return_id'] && $apiversion_diff == 0) {
                $this->_apiLog['info']['msg'] = '售后申请单已存在，且版本无变化不接收!';
                $this->exception(__METHOD__);
            }
            $return_product_items = $this->_refundsdf['refund_item_list'];
            
            if (!$return_product_items || !is_array($return_product_items)) {
                $this->_apiLog['info']['msg'] = '返回值：售后商品格式不正确';
                $this->exception(__METHOD__);
            }
            $productModel = app::get(self::_APP_NAME)->model('products');
            $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
            $return_num = array(); $productList = array();
            $is_fail = false;
            
            foreach ($return_product_items as $item) {
                $item['bn'] = $item['bn'];
                $product = $productModel->dump(array('bn'=>$item['bn']));
                if (!$product) {
                    $this->_apiLog['info']['msg'] = "返回值：货号[{$item['bn']}]不存在";
                    $is_fail = true;
                    //$this->exception(__METHOD__);
                }
                $productList[$product['bn']] = $product;

                $order_items = $orderItemModel->getList('item_id,order_id,bn,sendnum',array('order_id'=>$tgOrder['order_id'],'bn'=>$item['bn'],'delete'=>'false'));
             
                if (!$order_items) {
                    $this->_apiLog['info']['msg'] = "返回值：订单明细不存在，货号[{$item['bn']}]";
                    $is_fail = true;
                    //$this->exception(__METHOD__);
                }

                $return_num[$item['bn']] += $item['num'];

                $sendnum = 0;
                foreach ($order_items as $value) {
                    $sendnum += $value['sendnum'];
                }

                if (($return_num[$item['bn']] > $sendnum) && !$is_fail) {
                    $this->_apiLog['info']['msg'] = "返回值：货号[{$item['bn']}]超出订单发货数";
                    $this->exception(__METHOD__);
                }
                $effective = $oReship_item->Get_refund_count ( $tgOrder['order_id'], $item['bn'] );
                if (($effective<=0) && !$is_fail ) {
                    $this->_apiLog['info']['msg'] = "返回值：货号[{$item['bn']}]申请数超出订单发货数";
                    $this->exception(__METHOD__);
                }
            }
            // 如果前端传了会员名
            if ($this->_refundsdf['buyer_nick']) {
                $shopMemberModel = app::get(self::_APP_NAME)->model('shop_members');
                $member = $shopMemberModel->dump(array('shop_member_id'=>$this->_refundsdf['buyer_nick'],'shop_id'=>$shop_id));
                $member_id = $member['member_id'];
            } else {
                $member_id = $tgOrder['member_id'];
            }

            $opinfo = kernel::single('ome_func')->get_system();
            $sdf = array(
                'return_bn'  => $return_bn,
                'shop_id'    => $shop_id,
                'member_id'  => $member_id,
                'order_id'   => $tgOrder['order_id'],
                'title'      => $order_bn.'售后申请单',
                'content'    => $this->_refundsdf['reason'], 
                'comment'       => $this->_refundsdf['desc'],
                'add_time'   => $this->_refundsdf['created'] ? strtotime($this->_refundsdf['created']) : time(),
                'status'     => $status,
                'op_id'      => $opinfo['op_id'],
                'refundmoney'=> (float)$this->_refundsdf['refund_fee'],
                'money'=> (float)$this->_refundsdf['refund_fee'],
                'shipping_type'=>$this->_refundsdf['shipping_type'],
                'source'    => 'matrix',
                'shop_type'  =>$shop_type,
                'outer_lastmodify'=> $this->_refundsdf['modified'],
                'is_fail' =>$is_fail,
            );
            if ($apiversion_diff == 1){
                $sdf['return_id']  = $tgReturn['return_id'];
            }
            
            $deliOrderModel = app::get(self::_APP_NAME)->model('delivery_order');
            $deliOrder = $deliOrderModel->dump(array('order_id'=>$tgOrder['order_id']));
            
            $oDelivery = app::get(self::_APP_NAME)->model('delivery');
            
            /*------------------------------------------------------ */
            //-- [拆单]订单对应多个发货单_根据货品获取对应的仓库 ExBOY
            /*------------------------------------------------------ */
            #[发货配置]是否启动拆单 ExBOY
            $split_seting   = $oDelivery->get_delivery_seting();
            
            if(!empty($split_seting))
            {
                #获取订单关联的所有已发货的发货单delivery_id
                $sql    = "SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d
                            ON(dord.delivery_id=d.delivery_id) WHERE dord.order_id='".$tgOrder['order_id']."' AND (d.parent_id=0 OR d.is_bind='true')
                            AND d.disabled='false' AND d.status='succ'";
                $result = kernel::database()->select($sql);
                
                #暂只支持单次退货中的商品对应单个仓库[不支持退货的商品对应多个仓库]
                $get_order_items    = $return_product_items[0];
                
                if(count($result) > 1 && !empty($get_order_items))
                {
                    $delivery_ids    = array();
                    foreach ($result as $key => $val)
                    {
                        $delivery_ids[]    = $val['delivery_id'];
                    }
                    
                    $deliItemModel = &app::get(self::_APP_NAME)->model('delivery_items');
                    $result        = $deliItemModel->dump(array('delivery_id'=>$delivery_ids, 'bn'=>$get_order_items['bn']), 'delivery_id');
                    
                    if(!empty($result))
                    {
                        $deliOrder['delivery_id']    = $result['delivery_id'];
                    }
                }
            }
            
            if ($deliOrder) {
                $sdf['delivery_id'] = $deliOrder['delivery_id'];
            }
            
            $delivery = $oDelivery->dump(array('delivery_id'=>$sdf['delivery_id']),'branch_id');
            $rs = $returnModel->create_return_product($sdf);
            
            // 售后单明细
            $returnItemModel = app::get(self::_APP_NAME)->model('return_product_items');
            foreach ($return_product_items as $item) {
                $product_items = $returnItemModel->dump(array('return_id'=>$sdf['return_id'],'bn'=>$item['bn']));
                $rpi = array(
                    'return_id'  => $sdf['return_id'],
                    'product_id' => $productList[$item['bn']]['product_id'],
                    'bn'         => $item['bn'],
                    'name'       => $item['title'] ? $item['title']:$productList[$item['bn']]['name'],
                    'num'        => $item['num'],
                    'price'      => $item['price'],
                    'branch_id'   =>$delivery['branch_id'],
                );

                if ($product_items) {
                    $rpi['item_id'] = $product_items['item_id'];
                }
                $returnItemModel->save($rpi);
               
            }
            $this->aftersale_additional($sdf);
            $memo = '创建售后申请单,状态为:'.$status;
            if ($apiversion_diff==1) {
                $memo.= '(由于版本发生变化)';
            }
            $oOperation_log->write_log('return@ome',$sdf['return_id'],$memo);    
            $this->_apiLog['info'][] = "创建售后申请单SDF结构：".var_export($sdf,true);
            
            if ($sdf['return_id']) {
                if (in_array($status,array('3','4','5','6'))) {
                    $this->update_aftersalestatus($status,$sdf,$tgOrder);
                }
            }
         }else{
            $returnItemModel = app::get(self::_APP_NAME)->model('return_product_items');
            $tgReturnItems = $returnItemModel->getList('*',array('return_id'=>$tgReturn['return_id']));
            if (!$tgReturnItems) {
                $this->_apiLog['info']['msg'] = 'no after-sales detail';
                $this->exception(__METHOD__);
            }
            $this->update_aftersalestatus($status,$tgReturn,$tgOrder);

            #更新最新版本号
            $this->aftersale_additional($tgReturn);
            $this->_apiLog['info'][] = '店铺('.$this->_shop['name'].')更新售后状态：'.$status.',订单:'.$order_bn.',售后单号:'.$return_bn;
        }
        $this->_apiLog['info'][] = 'O.K';
    }

    protected function format_data($sdf){
       
        $sdf['modified']         = strtotime($sdf['modified']);
        $sdf['good_return_time'] = strtotime($sdf['good_return_time']);
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $oOrder_objects = app::get(self::_APP_NAME)->model('order_objects');
        $oOrder_items = app::get(self::_APP_NAME)->model('order_items');
        $order_bn    = $sdf['tid'];
        $shop_id     = $this->_shop['shop_id'];
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'order_id');
        $order_id = $tgOrder['order_id'];
        $reship_flag = false;
        $refund_type = $sdf['refund_type'];
        if ($sdf['refund_item_list']) {
            $refund_item_list = json_decode($sdf['refund_item_list'],true);
            $refund_item_list = $refund_item_list['return_item'];
            $item_list = array();
            if ($sdf['refund_type'] == 'reship') {
                $reship_flag = true;
                $returnitem_list = $this->getReturn_item($sdf);
            }
            
            foreach ($refund_item_list as $k=>$item) {
                $oid = $item['oid'];
                if ($sdf['refund_type'] == 'reship') {
                    
                    $oid = $sdf['oid'];
                }
                $order_objects = $oOrder_objects->dump(array('order_id'=>$order_id,'oid'=>$oid),'obj_type,obj_id'); 
                if ($order_objects) {
                    $obj_type = $order_objects['obj_type'];
                    $obj_id = $order_objects['obj_id'];
                    $order_items = $oOrder_items->getlist('bn,price,nums',array('obj_id'=>$obj_id));
                    foreach ($order_items as $ok=>$ov) {
                        $item_list[$ok]=array(
                            'num'=>   $ov['nums'],
                            'bn'=>   $ov['bn'],
                            'price'=>$ov['price'],
                            
                        );
                        
                    }
                }else{
                    
                    $item_list[$k]=array(
                            'num'=>   $item['num'],
                            'bn'=>   $item['outer_id'],
                            'price'=>$item['price'],
                            
                        );
                }
            }
            
            //
            if ($reship_flag && $returnitem_list) {
                $item_list = $returnitem_list;
                
            }
            $sdf['refund_item_list'] = $item_list;
        }

        if (!isset($sdf['refund_phase'])) {
            $sdf['refund_phase'] = $refund_item_list[0]['refund_phase'];
        }
        if (!isset($sdf['oid'])) {
            $sdf['oid'] = $refund_item_list[0]['oid'];
        }

       unset($order_objects);
        return $sdf;
    }

    protected function aftersale_additional($returninfo){
        $oReturn_tmall = app::get(self::_APP_NAME)->model('return_product_tmall');
        $return_bn = $returninfo['return_bn'];
        $shop_id = $returninfo['shop_id'];
        $status      = self::$refund_status[strtoupper($this->_refundsdf['status'])];
        //将信息新增至关联表
        $return_tm_data = array(
            'return_id'       => $returninfo['return_id'],
            'shop_id'         => $shop_id,
            'return_bn'       => $return_bn,
            'shipping_type'   => $this->_refundsdf['shipping_type'],
            'cs_status'       => $this->_refundsdf['cs_status'],
            'advance_status'  => $this->_refundsdf['advance_status'],
            'split_taobao_fee'=> $this->_refundsdf['split_taobao_fee'],
            'split_seller_fee'=> $this->_refundsdf['split_seller_fee'],
            'total_fee'       => $this->_refundsdf['total_fee'],
            'buyer_nick'      => $this->_refundsdf['buyer_nick'],
            'seller_nick'     => $this->_refundsdf['seller_nick'],
            'good_status'     => $this->_refundsdf['good_status'],
            'has_good_return' => $this->_refundsdf['has_good_return'],
            'good_return_time'=> $this->_refundsdf['good_return_time'],
            'refund_type'     => $this->_refundsdf['refund_type'],
            'refund_phase'    => $this->_refundsdf['refund_phase'],
            'refund_version'  => $this->_refundsdf['refund_version'],
            'alipay_no'       => $this->_refundsdf['payment_id'],
            'trade_status'      =>$this->_refundsdf['trade_status'],
            'oid'             => $this->_refundsdf['oid'],
            'bill_type'       => $this->_refundsdf['bill_type'],
            'current_phase_timeout'=>strtotime($this->_refundsdf['current_phase_timeout']),
        );
        if ($this->_refundsdf['tag_list']) {
            $tag_list = json_decode($this->_refundsdf['tag_list'],true);
            $return_tm_data['tag_list'] = serialize($tag_list);
        }
        if ($this->_refundsdf['address']) {
            $return_tm_data['address'] = $this->_refundsdf['address'];
        }
        $oReturn_tmall->save( $return_tm_data );
    }

    protected function refund_additional($refundinfo){
        $oRefund_tmall = app::get(self::_APP_NAME)->model('refund_apply_tmall');
        $status      = self::$refund_status[strtoupper($this->_refundsdf['status'])];
        $refund_tm_data = 
            array(
                'apply_id'       => $refundinfo['apply_id'],
                'shop_id'         => $refundinfo['shop_id'],
                'refund_apply_bn' => $refundinfo['refund_apply_bn'],
                'shipping_type'   => $this->_refundsdf['shipping_type'],
                'cs_status'       => $this->_refundsdf['cs_status'],
                'advance_status'  => $this->_refundsdf['advance_status'],
                'split_taobao_fee'=> $this->_refundsdf['split_taobao_fee'],
                'split_seller_fee'=> $this->_refundsdf['split_seller_fee'],
                'total_fee'       => $this->_refundsdf['total_fee'],
                'buyer_nick'      => $this->_refundsdf['buyer_nick'],
                'seller_nick'     => $this->_refundsdf['seller_nick'],
                'good_status'     => $this->_refundsdf['good_status'],
                'has_good_return' => $this->_refundsdf['has_good_return'],
                'good_return_time'=> $this->_refundsdf['good_return_time'],
                'oid'               =>$this->_refundsdf['oid'],
                'refund_version'    =>$this->_refundsdf['refund_version'],
                'bill_type'       => $this->_refundsdf['bill_type'],
                'outer_lastmodify'=>$this->_refundsdf['modified'],
                'alipay_no'=>$this->_refundsdf['payment_id'],
                'current_phase_timeout'=>strtotime($this->_refundsdf['current_phase_timeout']),
                );
                
        if ($this->_refundsdf['refund_type']) {
            $refund_tm_data['refund_type'] = $this->_refundsdf['refund_type'];
            
        }
        if ($this->_refundsdf['refund_phase']) {
            $refund_tm_data['refund_phase'] = $this->_refundsdf['refund_phase'];
        }
        if ($this->_refundsdf['tag_list']) {
            $tag_list = json_decode($this->_refundsdf['tag_list'],true);
            $refund_tm_data['tag_list'] = serialize($tag_list);
        }
        $rs = $oRefund_tmall->save( $refund_tm_data );

    }
    /**
    * 清空本地状态和已生成单据
    *
    */
    private function cleanReturnstatus($returninfo){
        $oReship = app::get(self::_APP_NAME)->model('reship');
        $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');//写日志
        $status = $returninfo['status'];
        $return_id = $returninfo['return_id'];
        $reship_bn = $returninfo['return_bn'];
        $reship = $oReship->dump(array('reship_bn'=>$reship_bn));
        $reship_id = $reship['reship_id'];
        if ( $reship ) {
            #
            if ($reship['is_check']=='0') {
                
           
                $oReship->db->exec('DELETE FROM sdb_ome_reship WHERE reship_id='.$reship_id.'');
                $oReship->db->exec('DELETE FROM sdb_ome_reship_items WHERE reship_id='.$reship_id.'');
                $oReship->db->exec('DELETE FROM sdb_ome_return_process WHERE reship_id='.$reship_id.'');
                $oReship->db->exec('DELETE FROM sdb_ome_return_process_items WHERE reship_id='.$reship_id.'');
                $memo = '由于版本发生变化,已生成退货单据等删除';
                $oOperation_log->write_log('return@ome',$return_id,$memo);
             }
        }

    }

    /**
     * 获取售后列表.
     * @param   
     * @return  售后申请单上明细
     * @access  private
     * @author sunjing@shopex.cn
     */
    private function getReturn_item($sdf)
    {
        $returnObj = app::get('ome')->model('return_product');
        $itemsObj = app::get('ome')->model('return_product_items');
        $return_detail = $returnObj->dump(array('return_bn'=>$sdf['refund_id']),'return_id');
        $return_id =$return_detail['return_id'];
        $items = $itemsObj->getList('bn,num,price',array('return_id'=>$return_id,'disabled'=>'false'));
        //print_r($items);
        return $items;
    }
}

?>