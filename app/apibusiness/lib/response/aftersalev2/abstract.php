<?php
/**
* 退款单 抽象类
*
* @category apibusiness
* @package apibusiness/response/refund
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_aftersalev2_abstract
{
    protected $_respservice = null;

    protected $_tgver = '';

    public $_apiLog = array();

    public $_refundsdf = array();

    public $_oldRefundsdf = array();

    const _APP_NAME = 'ome';

    protected $_shop = array();
    static public $refund_status = array(
        'WAIT_SELLER_AGREE'=>'0',
        'WAIT_BUYER_RETURN_GOODS'=>'2',//卖家已经同意退款
        'SELLER_REFUSE_BUYER'=>'3',//卖家拒绝seller_refuse
        'CLOSED'=>'3',//退款关闭
        'SUCCESS'=>'4',//退款成功
       // 'WAIT_SELLER_CONFIRM_GOODS'=>'6',//买家已经退货 对应何流程？不处理
    );

    static public  $return_status = array(
        'WAIT_SELLER_AGREE'=>'1',
        'WAIT_BUYER_RETURN_GOODS'=>'3',//卖家已经同意退款
        'SELLER_REFUSE_BUYER'=>'5',//卖家拒绝
        'CLOSED'=>'5',//退款关闭
        'SUCCESS'=>'4',//退款成功
        'WAIT_SELLER_CONFIRM_GOODS'=>'6',//买家已经退货
    );
    
    static public $reship_status = array(
        'confirm_failed'            =>  '5',
        'wait_buyer_return_goods'   =>  '0',
        'wait_seller_confirm_goods' =>  '1',
        'confirm_success'           =>  '7',
    );
    public function __construct($refundsdf)
    {
        $this->_refundsdf = $refundsdf;
    }

    /**
     * 响应对象设置
     *
     * @return Object
     * @author 
     **/
    public function setRespservice($respservice)
    {
        $this->_respservice = $respservice;

        return $this;
    }

    /**
     * 淘管中对应版本
     *
     * @return Object
     * @author 
     **/
    public function setTgVer($tgver)
    {
        $this->_tgver = $tgver;

        return $this;
    }

    /**
     * 店铺信息
     *
     * @return void
     * @author 
     **/
    public function setShop($shop)
    {
        $this->_shop = $shop;

        return $this;
    }

    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        

         return true;
    }

    /**
     * 售后请求
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        
    }

    /**
    * 退款单
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
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'pay_status,status,process_status,order_id,payed,cost_payment,ship_status,is_cod');
        if (!$tgOrder) {
            $this->_apiLog['info']['msg'] = 'no order in TAOGUAN';
            $this->exception(__METHOD__ , 'true');
        }

        // 状态值验证
        if ($status == '') {
            $this->_apiLog['info']['msg'] = 'status is empty 或为不接受状态值!';
            $this->exception(__METHOD__);
        }

        if ($refundMoney <= 0 && ($tgOrder['shipping']['is_cod'] == 'false' || ($tgOrder['shipping']['is_cod'] == 'true' && $tgOrder['ship_status']!='0'))) {
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
        $refund_diff = 0;
        if ($refundApply) {
            if ($this->_refundsdf['modified'] > $refundApply['outer_lastmodify']) {
                $refund_diff = 1;
            }
        }
        
        if (in_array($refundApply['status'], array('3'))) {
            //查看是否变化
            if ($refund_diff == 0) {
                $this->_apiLog['info']['msg'] = "退款申请单[{$refund_apply_bn}]已拒绝，无法退款！";
                $this->exception(__METHOD__);
            }
            
        }
        if (in_array($refundApply['status'], array('4'))) {
            $this->_apiLog['info']['msg'] = "退款申请单[{$refund_apply_bn}]已退款,无法退款！";
            $this->exception(__METHOD__);
        }
        // 判断是否允许接收
        $canAccept = $this->canAccept($tgOrder);
        if ($canAccept === false) {
            $this->exception(__METHOD__);
        }

        // 退款单
        $order_id = $tgOrder['order_id'];
        $this->_refundsdf['created']= kernel::single('ome_func')->date2time($this->_refundsdf['created']);
        $this->_refundsdf['t_ready']    = kernel::single('ome_func')->date2time($this->_refundsdf['t_begin']);
        $this->_refundsdf['t_sent']     = $this->_refundsdf['modified'];
        $this->_refundsdf['t_received'] ='';
        $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');//写日志
        if ($status == '4' || $refund_type == 'refund') {
            
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
            //如果是全额退款，发货单未发货需要将发货单置为失败
            
            //如果是部分退款，未发货发货单撤销失败订单置为异常

            $this->_apiLog['info'][] = "更新订单[{$order_bn}]支付状态";       

        } elseif ($refund_type == 'apply'){
            
            // 判断申请退款单是否已经存在
            if ($refundApply['apply_id'] && $status == '0' && ($refundApply['memo'] == $this->_refundsdf['reason']) && ($refundApply['money'] == $refundMoney) ){
                $this->_apiLog['info']['msg'] = "退款申请单[{$refundApply['refund_apply_bn']}]是否已经存在 且status等于{$refundApply['status']}";
                $this->exception(__METHOD__);
                
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
                    'outer_lastmodify'=>$this->_refundsdf['modified'],
                );
                if (in_array($tgOrder['ship_status'],array('1','3'))) {
                    $sdf['refund_refer'] = '1';
                }
                if ($refundApply['apply_id']) {
                    $sdf['apply_id'] = $refundApply['apply_id'];
                }
                $item_list = $this->_refundsdf['refund_item_list'];
               
                $product_data = array();
                foreach ($item_list as $item) {
                    $bn = $item['bn'];
                    $product = $productModel->dump(array('bn'=>$bn));
                    $productList[$product['bn']] = $product;

                    $order_items = $orderItemModel->getList('item_id,order_id,bn,sendnum',array('order_id'=>$tgOrder['order_id'],'bn'=>$bn,'delete'=>'false'));
                    if (!$order_items) {//暂时不判断
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
                if ($refund_diff == 1) {
                    $memo = '(由于退款金额或原因变化)更新退款申请单状态为:'.$status;
                }else{
                    $memo = '创建退款申请单';
                }
                $oOperation_log->write_log('refund_apply@ome',$sdf['apply_id'],$memo);
                kernel::single('ome_order_func')->update_order_pay_status($order_id);
            } else {
                #
                $outer_lastmodify = $refundApply['outer_lastmodify'];
                if ($this->_refundsdf['modified'] > $outer_lastmodify) {
                    $updateData = array(
                        'status'            => $status,
                        'outer_lastmodify'  => $this->_refundsdf['modified'],
                        'memo'              => $this->_refundsdf['reason'],
                        'money'             => $this->_refundsdf['refund_fee'],
                    );
                    $filter = array(
                        'shop_id'         => $shop_id,
                        'refund_apply_bn' => $refund_bn,
                    );
                    $refundApplyModel->update($updateData,$filter);
                    $oOperation_log->write_log('refund_apply@ome',$refundApply['apply_id'],$memo."更新退款申请单[{$refund_bn}]状态：{$status}");
                    $this->_apiLog['info'][] = "更新退款申请单[{$refund_bn}]状态：{$status}";
                    kernel::single('ome_order_func')->update_order_pay_status($order_id);

                    $this->_apiLog['info'][] = "更新订单[{$order_id}]支付状态";
                }else{
                    $this->_apiLog['info'][] = "退款申请单[{$refund_bn}]更新时间未变化,不更新";
                }
                
            }

            

        }
        $this->_apiLog['info'][] = 'O.K';
    }

    /**
    * 售后处理单
    *
    */
    protected function aftersale_add(){
        $this->_apiLog['title']  = '前端店铺售后申请接口[售后单号：'.$this->_refundsdf['refund_id'].' ]';
        $order_bn  = $this->_refundsdf['tid'];
        $return_bn = $this->_refundsdf['refund_id'];
        $shop_id   = $this->_shop['shop_id'];
        $shop_type = $this->_shop['shop_type'];

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
        $oReship_item = app::get(self::_APP_NAME)->model ( 'reship_items' );
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('shop_id'=>$shop_id,'order_bn'=>$order_bn));
        $return_diff = 0;
        $lastmodify = $tgReturn['outer_lastmodify'];
        if ($tgReturn) {
            if ($this->_refundsdf['modified'] > $lastmodify && ($tgReturn['content']!=$this->_refundsdf['reason']) || $tgReturn['money']!=$this->_refundsdf['refund_fee']) {
                $return_diff = 1;
            }
        }
        
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
        if ($tgReturn['status'] == '5') {
            if ($return_diff == 0) {
                $this->_apiLog['info']['msg'] = '返回值：此售后单已经拒绝';
                $this->exception(__METHOD__);
            }
        }
       	if ($tgReturn['status'] == '4') {
            
            $this->_apiLog['info']['msg'] = '返回值：此售后单已经完成';
            $this->exception(__METHOD__);
        }

        if ($status == '1' || !$tgReturn['return_id']) {
            
            // 判断申请退款单是否已经存在
            if ($tgReturn['return_id']) {
                if ($return_diff == 0) {
                    $this->_apiLog['info']['msg'] = '售后申请单已存在';
                    $this->exception(__METHOD__);
                }
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
                    $is_fail = true;
                    $this->_apiLog['info']['msg'] = "返回值：订单明细不存在，货号[{$item['bn']}]";
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
                if ($effective<=0 && !$is_fail) {
                    $this->_apiLog['info']['msg'] = "返回值：货号[{$item['bn']}]超出可申请退货数量";
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
                'status'     => '1',
                'op_id'      => $opinfo['op_id'],
                'refundmoney'=> (float)$this->_refundsdf['refund_fee'],
                'money'=> (float)$this->_refundsdf['refund_fee'],
                'shipping_type'=>$this->_refundsdf['shipping_type'],
                'source'    => 'matrix',
                'shop_type'  =>$shop_type,
                'outer_lastmodify'=>$this->_refundsdf['modified'],
                'is_fail' =>$is_fail,
            );
            if ($tgReturn['return_id']) {
                $sdf['return_id'] = $tgReturn['return_id'];
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
                $return_item = $returnItemModel->dump(array('return_id'=>$sdf['return_id'],'bn'=>$item['bn']));
                $rpi = array(
                    'return_id'  => $sdf['return_id'],
                    'product_id' => $productList[$item['bn']]['product_id'] ? $productList[$item['bn']]['product_id'] : 0,
                    'bn'         => $item['bn'],
                    'name'       => $item['title'] ? $item['title']:$productList[$item['bn']]['name'],
                    'num'        => $item['num'],
                    'price'      => $item['price'],
                    'branch_id'   =>$delivery['branch_id'],
                );
                if ($return_item) {
                    $rpi['item_id'] = $return_item['item_id'];
                }
                $returnItemModel->save($rpi);
               
            }
            $this->aftersale_additional($sdf);

            if ($return_diff == 1) {
                $memo = '(退款原因或金额发生变化)';
            }
            $memo.= '创建售后申请单,状态为:'.$status;
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
            $this->_apiLog['info'][] = '店铺('.$this->_shop['name'].')更新售后状态：'.$status.',订单:'.$order_bn.',售后单号:'.$return_bn;

        }
        $this->_apiLog['info'][] = 'O.K';
    }

    /**
    * 更新售后状态
    */
    protected function update_aftersalestatus($status,$tgReturn,$tgOrder){
        $returnModel = app::get(self::_APP_NAME)->model('return_product');
        $oReship = app::get(self::_APP_NAME)->model('reship');
        $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');//写日志
        $data = array(
            'status'    => $status,
            'return_id' => $tgReturn['return_id'],
            'outer_lastmodify'=> $this->_refundsdf['modified'],
        );
        $return_bn = $this->_refundsdf['refund_id'];
        $reship = $oReship->dump(array('order_id'=>$tgOrder['order_id'],'return_id' => $tgReturn['return_id']));

        if (in_array($status, array('3'))){
            if ($tgReturn['status']>='3' && $reship) {
                $this->_apiLog['info']['msg'] = "此单据已接受或退货单已生成不可以再接受!";
                $this->exception(__METHOD__);
            }
            if ($tgReturn['shop_type'] == 'tmall' || $this->_shop['tbbusiness_type'] == 'B') {
                $data['choose_type_flag'] = 0;
            }else{
                $data['choose_type_flag'] = 1;
            }
            $api = TRUE;//前端打的更新就不向后端发起更新状态了
            $returnModel->tosave($data,$api);

        } elseif ($status == '4') {
            #完成时操作
            #质检 收货完成
            
            if (!$reship) {
                $this->_apiLog['info']['msg'] = "退货单不存在不可以完成!";
                $this->exception(__METHOD__);
            }
            $reship_id = $reship['reship_id'];
            #查看当前状态
            $memo = '线上已完成,请进行收货/质检等操作';
            //暂时关闭自动完成质检等操作

//            $is_check = $reship['is_check'];
//            if ($is_check == '0') {
//                kernel::single('ome_return_rchange')->accept_returned($reship_id,'3',$error_msg);
//                $oReturn_process = app::get(self::_APP_NAME)->model('return_process');
//                $return_process = $oReturn_process->dump(array('reship_id'=>$reship_id,'return_id'=>$tgReturn['return_id']));
//                $oProblem = &app::get('ome')->model('return_product_problem');
//                $stockType_list=$oProblem->store_type();
//
//                $oBranch = &app::get('ome')->model('branch');
//                $isExistOfflineBranch = $oBranch->isExistOfflineBranch();
//                $onlineBranch = $oBranch->getOnlineBranchs('branch_id,name');
//                $offlineBranch = $oBranch->getOfflineBranchs('branch_id,name');
//                $store_type = 0;
//                $branch_id = $onlineBranch[0]['branch_id'];
//                $isExistOnlineBranch = $oBranch->isExistOnlineBranch();
//                # 如果没有线上仓 去除新仓
//                if (!$isExistOnlineBranch) {
//                    unset($stockType_list[0]);
//                    $store_type = 1;
//                    $branch_id = $offlineBranch[0]['branch_id'];
//                }
//                $por_id = $return_process['por_id'];
//                $SQL = "UPDATE sdb_ome_return_process_items SET 
//                           store_type='".$store_type."',branch_id=".$branch_id.",
//                            acttime=".time().",is_check='true' where reship_id=".$reship_id;
//
//               $oReturn_process->db->exec($SQL);
//                # 写日志
//                $local_memo = '质检成功,进入'.$oProblem->get_store_type($store_type).':'.$oBranch->Get_name($branch_id);
//                if($tgReturn['return_id']){
//                   $oOperation_log->write_log('return@ome',$tgReturn['return_id'],$local_memo); 
//                }
//                $oOperation_log->write_log('reship@ome',$reship_id,$local_memo); 
//                $oReturn_process->changeverify($por_id,$reship_id,$return_id,'');
//
//                $return_product_detail = $oReturn_process->dump(array('por_id'=>$por_id), 'verify');
//                
//                if ($return_product_detail['verify'] == 'true'){
//                    kernel::single('ome_return_process')->do_iostock($por_id,1,$msg);
//                    $oReship->finish_aftersale($reship_id);
//                }
//
//                $memo = '单据状态符合自动完成售后状态';
//            }else{
//                $memo ='由于退货单状态异常，请人工介入处理';
//            }
            
        }else if($status == '6'){#
            #暂不处理
            $this->_updateLogistic($reship['reship_id'],$this->_refundsdf['logistics_company'],$this->_refundsdf['logistics_no']);
        }else {
            if ($status == '5') {
                if ($reship['is_check']>0) {
                    $memo = '线上拒绝操作，因退货单非未审核状态不处理';
                }else{
                    $api = TRUE;//前端打的更新就不向后端发起更新状态了
                    $returnModel->tosave($data,$api);
                    
                    if($reship['reship_id']){
                        // 同步拒绝退货单
                        $oReship->update(array('is_check'=>'5','t_end'=>time()),array('reship_id'=>$reship['reship_id']));

                        $oOperation_log->write_log('reship@ome',$reship['reship_id'],'前端拒绝');
                    }

                }
            }
        }
        if ($memo) {
            $oOperation_log->write_log('return@ome',$tgReturn['return_id'],$memo); 
        }
    }
    
    /**
    * 退货单添加
    */
    public function reship_add(){
        
        // 日志
        $this->_apiLog['title']  = '前端店铺退货业务处理[订单：' . $this->_refundsdf['tid'].']';
        $order_bn    = $this->_refundsdf['tid'];
        $reship_bn = $this->_refundsdf['refund_id'];
        // 店铺
        $shop_type   = $this->_shop['shop_type'];
        $shop_name   = $this->_shop['name'];
        $shop_id     = $this->_shop['shop_id'];
        
        $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');
        $reship_status = self::$reship_status[$this->_refundsdf['status']];
        
        // 退款单号验证
        if (!$reship_bn) {
            $this->_apiLog['info']['msg'] = 'no reship bn';
            $this->exception(__METHOD__);
        }
        // 订单号验证
        if (!$order_bn) {
            $this->_apiLog['info']['msg'] = 'no order bn';
            $this->exception(__METHOD__);
        }
        $productModel = app::get(self::_APP_NAME)->model('products');
        $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'*');
         // 售后单
        $returnModel = app::get(self::_APP_NAME)->model('return_product');
        $tgReturn = $returnModel->dump(array('shop_id'=>$shop_id,'return_bn'=>$reship_bn,'source'=>'matrix'));
        if (!$tgReturn) {
            //单拉售后单

            $returnRsp = kernel::single('apibusiness_router_request')->setShopId($shop_id)->get_refund_detial($this->_refundsdf['refund_id'] ,$this->_refundsdf['refund_phase'],$order_bn);
            
            if ($returnRsp['rsp'] == 'succ') {
                $rs = kernel::single('ome_return')->get_return_log($returnRsp['data'],$shop_id,$msg);
                if ($rs) {
                    $tgReturn = $returnModel->dump(array('shop_id'=>$shop_id,'return_bn'=>$reship_bn,'source'=>'matrix'));
                }
            }
        }
        if (!$tgReturn) {
            //单拉售后单
            $this->_apiLog['info']['msg'] = "售后申请单不存在,不可以创建退货单!";
            $this->exception(__METHOD__);
        }
        
        $oDc = app::get(self::_APP_NAME)->model('dly_corp');
        $dc_data = $oDc->dump($tgOrder['logi_id']);
        $Odelivery = app::get(self::_APP_NAME)->model('delivery');
        $deliveryinfo = $Odelivery->dump(array('delivery_id'=>$tgReturn['delivery_id']));
        $Oreship = app::get(self::_APP_NAME)->model('reship');
        $reship = $Oreship->dump(array('reship_bn'=>$this->_refundsdf['refund_id']));
        $modified = $this->_refundsdf['modified'];
        $oOrder_items = app::get(self::_APP_NAME)->model('order_items');
        $oReship_item = app::get(self::_APP_NAME)->model ( 'reship_items' );
        if ($reship) {
            
            if ( $reship['is_check'] == '5') {
                $this->_apiLog['info']['msg'] = "退货单已拒绝";
                $this->exception(__METHOD__);
            }
        }
        if (!$reship && $tgReturn['status']<3) {
            if ($shop_type == 'taobao') {
                if (strtoupper($this->_shop['tbbusiness_type']) == 'B') {
                    #天猫类型，更新售后申请为已接受
                    $returnModel->update(array('status'=>'3'),array('return_id'=>$tgReturn['return_id']));
                    $oOperation_log->write_log('return@ome',$tgReturn['return_id'],'由于退货单下载,售后单不为已接受更新为已接受');
                }
            }
        }
        if (!$reship || $reship_status == '0') {
            $order_id = $tgOrder['order_id'];
            $reship_data = array(
                'reship_bn'     =>$this->_refundsdf['refund_id'],
                'shop_id'       => $shop_id,
                'order_id'      => $tgOrder['order_id'],
                'delivery_id'   => $tgReturn['delivery_id'],
                'member_id'     => $tgOrder['member_id'],
                'logi_name'     => $dc_data['name'],
                'logi_no'       => $tgOrder['logi_no'],
                'logi_id'       => $tgOrder['logi_id'],
                'ship_name'     => $tgOrder['consignee']['name'],
                'ship_area'     => $tgOrder['consignee']['area'],
                'delivery'      => $tgOrder['shipping']['shipping_name'],
                'ship_addr'     => $tgOrder['consignee']['addr'],
                'ship_zip'      => $tgOrder['consignee']['zip'],
                'ship_tel'      => $tgOrder['consignee']['telephone'],
                'ship_email'    => $tgOrder['consignee']['email'],
                'ship_mobile'   => $tgOrder['consignee']['mobile'],
                'is_protect'    => $tgOrder['shipping']['is_protect'],
                'return_id'     => $tgReturn['return_id'],
                'return_logi_name'=>$this->_refundsdf['company_name'],
                'return_logi_no'=>$this->_refundsdf['sid'],
                'outer_lastmodify'=>$modified,
                'source'=>'matrix',
                't_begin'=>$this->_refundsdf['created'] ? strtotime($this->_refundsdf['created']) : time(),
                'op_id'=>16777215,
                'branch_id'=>$deliveryinfo['branch_id'],
            );
            if ( in_array($reship_status,array('0','5'))) {
                $reship_data['is_check'] = $reship_status;
            }
            //$tmoney = 0;
            $refund_item_list = $this->_refundsdf['refund_item_list'];
            
            $reship_items = array();
            if ($refund_item_list) {
                $refund_fee = $tgReturn['money'];
                #获取数量
                $return_item = $returnModel->db->selectrow("SELECT sum(num) as total_num FROM sdb_ome_return_product_items WHERE return_id=".$tgReturn['return_id']);
                $total_num = $return_item['total_num'];
                $price = sprintf('%.3f',$refund_fee/$total_num);
                
                foreach ($refund_item_list as $item ) {
                    $bn = $item['bn'];
                    $effective = $oReship_item->Get_refund_count ( $order_id, $bn );
                    if ($effective<=0) {
                        $this->_apiLog['info']['msg'] = '货号数量'.$bn."已退完";
                        $this->exception(__METHOD__);
                    }
                    $product = $productModel->dump(array('bn'=>$item['bn']));
                    $reship_items[] = array(
                       'op_id'=>16777215,
                       'bn'     =>$item['bn'],
                       'num'    =>$item['num'],
                       'price'  =>$price,
                       'branch_id'=>$deliveryinfo['branch_id'],
                       'product_name'=>$product['name'],
                       'product_id'=>$product['product_id'],
                    );

                }
                
                $reship_data['tmoney'] = $refund_fee;
                $reship_data['totalmoney'] = $refund_fee;//总计应退金额
                $reship_data['reship_items'] = $reship_items;
            }
            $Oreship->save($reship_data);
            $memo ='新建退换货单,单号为:'.$reship_data['reship_bn'];
            $oOperation_log->write_log('reship@ome',$reship_data['reship_id'],$memo);
            $this->_apiLog['info'][] = '创建退货单SDF结构：'.var_export($reship_data,true);
            $this->_updateLogistic($reship_data['reship_id'],$this->_refundsdf['company_name'],$this->_refundsdf['sid']);
        }else{
            if ($modified>$reship['outer_lastmodify']) {
                $company_name = $this->_refundsdf['company_name'];
                $sid = $this->_refundsdf['sid'];
                $this->_updateLogistic($reship['reship_id'],$company_name,$sid);
                
                if ($reship_status == '5') {#拒绝
                    if ($reship['is_check']>0) {
                        $this->_apiLog['info']['msg'] = "本地退货单当前状态[{$reship['status']}]不接收!";
                        $this->exception(__METHOD__);
                    }else{
                        $Oreship->update(array('is_check'=>'5'),array('reship_id'=>$reship['reship_id']));
                        $memo = '状态:拒绝';
                        if($reship['return_id']){
                            $oOperation_log->write_log('return@ome',$reship['return_id'],$memo);
                            $data = array ('return_id' => $reship['return_id'], 'status' => '5', 'last_modified' => time () );
                            
                            $returnModel->save ( $data );
                        }
                        $oOperation_log->write_log('reship@ome',$reship['reship_id'],$memo);
                    }
                }
            }else{
                $this->_apiLog['info']['msg'] = "退货单已存在且未变化不接收!";
                $this->exception(__METHOD__);
            }
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

            //更新订单支付状态
            if (kernel::single('ome_order_func')->update_order_pay_status($order_id)){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * 检查是否部分退款异常
     * @access protected
     * @param string order_id
     * @return boolean
     */
    protected function _checkAbnormal($order_id){
        if (empty($order_id)) return false;

        $orderObj = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderObj->dump(array('order_id'=>$order_id),'pay_status,ship_status');
        if($tgOrder['pay_status'] == '4' && $tgOrder['ship_status'] == '0'){
            //如果是部分退款订单,添加部分退款异常并暂停订单
            $abnormalObj = app::get(self::_APP_NAME)->model('abnormal');
            $abnormalTypeObj = app::get(self::_APP_NAME)->model('abnormal_type');
            $abnormalTypeInfo = $abnormalTypeObj->dump(array('type_name'=>'订单未发货部分退款'),'type_id,type_name');
            if($abnormalTypeInfo){
                $tmp['abnormal_type_id'] = $abnormalTypeInfo['type_id'];
            }else{
                $add_arr['type_name'] = '订单未发货部分退款';
                $abnormalTypeObj->save($add_arr);
                $tmp['abnormal_type_id'] = $add_arr['type_id'];
            }

            $abnormalInfo = $abnormalObj->dump(array('order_id'=>$order_id),'abnormal_id,abnormal_memo');
            $memo = '';
            if($abnormalInfo){
                $tmp['abnormal_id'] = $abnormalInfo['abnormal_id'];
                $oldmemo= unserialize($abnormalInfo['abnormal_memo']);
                if ($oldmemo){
                    foreach($oldmemo as $k=>$v){
                        $memo[] = $v;
                    }
                }
            }

            $op_name = 'system';
            $newmemo =  '订单未发货部分退款，系统自动设置为异常并暂停。';
            $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
            $tmp['abnormal_memo'] = serialize($memo);

            $tmp['abnormal_type_name'] ='订单未发货部分退款';
            $tmp['is_done'] = 'false';
            $tmp['order_id'] = $order_id;

            $abnormalObj->save($tmp);

            //订单暂停并设置为异常
            $order_data = array('order_id'=>$order_id,'abnormal'=>'true','pause'=>'true');
            $orderObj->save($order_data);
        }else if ($tgOrder['pay_status'] == '4' && ($tgOrder['ship_status'] == '2' || $tgOrder['ship_status'] == '1')) {
            //部分退款。部分发货时有发货单为撤销失败订单置为异常
            $deliveryObj = app::get('console')->model('delivery');
            $sync_delivery =$deliveryObj->getDeliveryByOrderId($order_id);
            if ($sync_delivery) {
                $this->_saveAbnormal($order_id,'订单部分发货部分退款','订单部分发货部分退款,系统自动设置为异常并暂停') ;
                //订单暂停并设置为异常
                $order_data = array('order_id'=>$order_id,'abnormal'=>'true','pause'=>'true');
                $orderObj->save($order_data);
            }
        }else{
            return true;
        }
    }

    protected function _updateLogistic($reship_id,$company_name,$sid){
        $returnModel = app::get(self::_APP_NAME)->model('return_product');
        $Oreship = app::get(self::_APP_NAME)->model('reship');
        $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');
        $reship = $Oreship->dump($reship_id);
        if ($company_name && $sid) {
            $memo ='更新物流公司:'.$company_name.',物流单号:'.$sid;
            $logistics_info = array(
                'shipcompany'=> $company_name,
                'logino'=>$sid,
            );
            if ($reship) {
                $updata = array(
                        'return_logi_name'=>$company_name,
                        'return_logi_no'=>$sid,
                        'outer_lastmodify'=>$this->_refundsdf['modified'],
                );
                $Oreship->update($updata,array('reship_id'=>$reship_id));
                $oOperation_log->write_log('reship@ome',$reship_id,$memo);
                $logistics_info = serialize($logistics_info);
                $return_id = $reship['return_id'];
                $returnModel->update(array('process_data'=>$logistics_info),array('return_id'=>$return_id));
                $oOperation_log->write_log('return@ome',$return_id,$memo); 
            }
        }
        
    }
    /**
     * 售后附加表
     * @access protected
     * @param array returninfo
     * 
     */
    protected function aftersale_additional($returninfo){}

    /**
     * 退款申请单附加表
     * @access protected
     * @param array returninfo
     * 
     */
    protected function refund_additional(){}
    /**
     * 异常处理
     *
     * @return void
     * @author 
     **/
    protected function exception($fun,$retry='false')
    {
        $logModel = app::get('ome')->model('api_log');
        $log_id = $logModel->gen_id();
        $logModel->write_log($log_id,
                             $this->_apiLog['title'],
                             get_class($this), 
                             $fun, 
                             '', 
                             '', 
                             'response', 
                             'fail', 
                             implode('<hr/>',$this->_apiLog['info']),
                             '',
                             '',
                             $this->_refundsdf['tid']);

        $data = array('tid'=>$this->_refundsdf['tid'],'refund_id'=>$this->_refundsdf['refund_id'],'retry'=>$retry);
        //echo $this->_apiLog['info']['msg'];
        $this->_respservice->send_user_error($this->_apiLog['info']['msg'],$data);
        exit;
    }

    /**
    *根据类型名称设置备注
    */
    protected function _saveAbnormal($order_id,$type_name,$newmemo){
        $abnormalObj = app::get(self::_APP_NAME)->model('abnormal');
        $abnormalTypeObj = app::get(self::_APP_NAME)->model('abnormal_type');
        $abnormalTypeInfo = $abnormalTypeObj->dump(array('type_name'=>$type_name),'type_id,type_name');
        if($abnormalTypeInfo){
            $tmp['abnormal_type_id'] = $abnormalTypeInfo['type_id'];
        }else{
            $add_arr['type_name'] = '订单未发货部分退款';
            $abnormalTypeObj->save($add_arr);
            $tmp['abnormal_type_id'] = $add_arr['type_id'];
        }
        $abnormalInfo = $abnormalObj->dump(array('order_id'=>$order_id),'abnormal_id,abnormal_memo');
        $memo = '';
        if($abnormalInfo){
            $tmp['abnormal_id'] = $abnormalInfo['abnormal_id'];
            $oldmemo= unserialize($abnormalInfo['abnormal_memo']);
            if ($oldmemo){
                foreach($oldmemo as $k=>$v){
                    $memo[] = $v;
                }
            }
        }

        $op_name = 'system';
        
        $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
        $tmp['abnormal_memo'] = serialize($memo);

        $tmp['abnormal_type_name'] =$type_name;
        $tmp['is_done'] = 'false';
        $tmp['order_id'] = $order_id;

        $abnormalObj->save($tmp);
    
    }
}