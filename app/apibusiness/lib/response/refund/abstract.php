<?php
/**
* 退款单 抽象类
*
* @category apibusiness
* @package apibusiness/response/refund
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_refund_abstract
{
    protected $_respservice = null;

    protected $_tgver = '';

    public $_apiLog = array();

    public $_refundsdf = array();

    const _APP_NAME = 'ome';

    protected $_shop = array();

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
     * 添加退款单
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        // 日志
        $this->_apiLog['title']  = '前端店铺退款业务处理[订单：' . $this->_refundsdf['order_bn'].']';
        $this->_apiLog['info'][] = '接收参数：' . var_export($this->_refundsdf, true);
        $this->_apiLog['info'][] = '前端店铺信息：'.var_export($this->_shop,true);
        $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;

        // 店铺
        $shop_type   = $this->_shop['shop_type'];
        $shop_name   = $this->_shop['name'];
        $shop_id     = $this->_shop['shop_id'];

        $status      = $this->_refundsdf['status'];
        $refundMoney = (float)$this->_refundsdf['money'];
        $refund_bn   = $this->_refundsdf['refund_bn'];
        $refund_type = $this->_refundsdf['refund_type'];
        $order_bn    = $this->_refundsdf['order_bn'];

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
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'pay_status,status,process_status,order_id,payed,cost_payment');
        if (!$tgOrder) {
            //根据店铺判断要获取订单的类型
            $order_type = ($this->_shop['business_type']=='fx') ? 'agent' : 'direct';
            // 淘管中无些单号订单，重新获取
            $orderRsp = kernel::single('apibusiness_router_request')->setShopId($shop_id)->get_order_detial($order_bn,$order_type);

            if ($orderRsp['rsp'] == 'succ') {
                $rs = kernel::single('ome_syncorder')->get_order_log($orderRsp['data']['trade'],$shop_id,$msg);
                if ($rs) {
                    $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'pay_status,status,process_status,order_id,payed,cost_payment');
                }
            }
        }

        if (!$tgOrder) {
            $this->_apiLog['info']['msg'] = 'no order in TAOGUAN';
            $this->exception(__METHOD__ , 'true');
        }

        // 状态值验证
        if ($status == '') {
            $this->_apiLog['info']['msg'] = 'status is empty';
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

        if (in_array($refundApply['status'], array('3','4'))) {
            $this->_apiLog['info']['msg'] = "退款申请单[{$refund_apply_bn}]已退款或已拒绝，无法退款！";
            $this->exception(__METHOD__);
        }

        // 判断是否允许接收
        $canAccept = $this->canAccept($tgOrder);
        if ($canAccept === false) {
            $this->exception(__METHOD__);
        }

        // 退款单
        $order_id = $tgOrder['order_id'];
        $this->_refundsdf['t_ready']    = kernel::single('ome_func')->date2time($this->_refundsdf['t_ready']);
        $this->_refundsdf['t_sent']     = kernel::single('ome_func')->date2time($this->_refundsdf['t_sent']);
        $this->_refundsdf['t_received'] = kernel::single('ome_func')->date2time($this->_refundsdf['t_received']);
        if ($status == 'succ' || $refund_type == 'refund') {

            // 取出退款申请单据编号
            if ($this->_refundsdf['memo']){
                $this->_refundsdf['memo'] = preg_replace('/#(\d+)#/', '', $this->_refundsdf['memo']);
            }

            $sdf = array(
                'refund_bn'     => $refund_bn,
                'shop_id'       => $shop_id,
                'order_id'      => $tgOrder['order_id'],
                'account'       => $this->_refundsdf['account'],
                'bank'          => $this->_refundsdf['bank'],
                'pay_account'   => $this->_refundsdf['pay_account'],
                'currency'      => 'CNY',
                'money'         => $refundMoney,
                'paycost'       => $this->_refundsdf['paycost'],
                'cur_money'     => $this->_refundsdf['cur_money'] ? $this->_refundsdf['cur_money'] : $refundMoney,
                'pay_type'      => $this->_refundsdf['pay_type'] ? $this->_refundsdf['pay_type'] : 'online',
                'payment'       => $this->_refundsdf['payment'] ? $this->_refundsdf['payment'] : $refundApply['payment'],
                'paymethod'     => $this->_refundsdf['paymethod'],
                'download_time' => time(),
                'status'        => $status,
                'memo'          => $this->_refundsdf['memo'],
                'trade_no'      => $this->_refundsdf['trade_no'],
                'return_id' => $refundApply['return_id'],
                'refund_refer' => $refundApply['refund_refer'],
				'oid'           => $this->_refundsdf['oid'],
            );

            $pay_bn = $this->_refundsdf['payment'];
            if ($pay_bn) {
                $payment_cfg = kernel::single('apibusiness_adapter_payment_cfg')->get_payment($pay_bn,$shop_type);
                $sdf['payment'] = $payment_cfg['id'];
            }

            //$c2c_shop_list = apibusiness_shop_type::shop_list();

            if(!apibusiness_router_mapping::$shopex[$shop_type]){
                $sdf['t_ready']    = $this->_refundsdf['t_ready'];
                $sdf['t_sent']     = $this->_refundsdf['modified'];
                $sdf['t_received'] = '';    // 如果是c2c订单不设用户收款时间
            }else{
                $sdf['t_ready']    = $this->_refundsdf['t_ready'] ? $this->_refundsdf['t_ready'] : $this->_refundsdf['t_sent'];
                $sdf['t_sent']     = $this->_refundsdf['t_sent'] ? $this->_refundsdf['t_sent'] : $this->_refundsdf['t_ready'];
                $sdf['t_received'] = $this->_refundsdf['t_received'];
            }

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
                    $reship_id = $addon['reship_id'];
                    if ($return_id) {
                        $pReturnModel = app::get(self::_APP_NAME)->model('return_product');
                        $pReturn = $pReturnModel->dump(array('return_id' => $return_id),'refundmoney,return_bn');
                        $refundMoney = bcadd((float)$refundMoney, (float)$pReturn['refundmoney'],3);
                        $pReturnModel->update(array('refundmoney'=>$refundMoney),array('return_id'=>$return_id));
                        $return_bn = $pReturn['return_bn'];
                        $this->_apiLog['info'][] = "更新售后申请单[{$return_bn}]金额：".$refundMoney;
                        
                    }
                    if ($return_id || $reship_id) {
                        //生成售后单
                        kernel::single('sales_aftersale')->generate_aftersale($refundApply['apply_id'],'refund');
                    }
                }
            }

        } elseif ($refund_type == 'apply') {
            
            // 判断申请退款单是否已经存在
            if ($refundApply['apply_id'] && $status == '0' && ($refundApply['memo'] == $this->_refundsdf['memo']) && ($refundApply['money'] == $refundMoney) ){
                $this->_apiLog['info']['msg'] = "退款申请单[{$refundApply['refund_apply_bn']}]已经存在 且status等于{$refundApply['status']}";
                $this->exception(__METHOD__);
            }

            // 如果申请单存在，做更新(只更新备注和状态)
            if ($refundApply['apply_id']) {
                if ($status == '0' && $refundApply['status'] != '0') {
                    $this->_apiLog['info']['msg'] = '退款申请单处理中，不允许更新状态';
                    $this->exception(__METHOD__);
                }

                $updateData = array(
                    'status' => $status,
                );
                if ($this->_refundsdf['memo']) {
                    if ($refundApply['memo'] && false === strpos($this->_refundsdf['memo'], $refundApply['memo'])) {
                        $updateData['memo'] = $refundApply['memo'] . ',' . $this->_refundsdf['memo'];
                    } elseif (!$refundApply['memo']) {
                        $updateData['memo'] = $this->_refundsdf['memo'];
                    }
                }

                $filter = array('apply_id' => $refundApply['apply_id'],'money'=>$this->_refundsdf['money'] );

                $affect_row = $refundApplyModel->update($updateData,$filter);
                if ( is_numeric($affect_row) && $affect_row > 0) {
                    $this->_apiLog['info'][] = "更新退款申请单[{$refund_bn}]状态成功：{$status},影响行数：".$affect_row;
                } else {
                    $this->_apiLog['info'][] = "更新退款申请单[{$refund_bn}]状态失败：可能是金额不一致";
                }
                
            } else {
                // 创建退款申请.
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
                    'memo'            => $this->_refundsdf['memo'],
                    'create_time'     => $this->_refundsdf['t_ready'],
                    'status'          => $status,
                    'shop_id'         => $shop_id,
                    'addon'           => $addon,
                    'source'        =>'matrix',
                    'shop_type'         =>$shop_type,
                );
                $refundApplyModel->create_refund_apply($sdf);
                
                $this->_apiLog['info'][] = '创建退款申请单SDF结构：'.var_export($sdf,true);



            }

            // if ($status == '0' || !$refundApply['apply_id']) {
            //     $addon = serialize(array('refund_bn'=>$refund_bn));
            //     $sdf = array(
            //         'order_id'        => $order_id,
            //         'refund_apply_bn' => $refund_bn,
            //         'pay_type'        => $this->_refundsdf['pay_type'] ? $this->_refundsdf['pay_type'] : 'online',
            //         'account'         => $this->_refundsdf['account'],
            //         'bank'            => $this->_refundsdf['bank'],
            //         'pay_account'     => $this->_refundsdf['pay_account'],
            //         'money'           => $refundMoney ? $refundMoney : '0',
            //         'refunded'        => '0',
            //         'memo'            => $this->_refundsdf['memo'],
            //         'create_time'     => $this->_refundsdf['t_ready'],
            //         'status'          => $status,
            //         'shop_id'         => $shop_id,
            //         'addon'           => $addon,
            //     );

            //     $refundApplyModel->create_refund_apply($sdf);

            //     $this->_apiLog['info'][] = '创建退款申请单SDF结构：'.var_export($sdf,true);
            // } else {
            //     $updateData = array(
            //         'status' => $status,
            //     );
            //     $filter = array(
            //         'shop_id'         => $shop_id,
            //         'memo'            => $this->_refundsdf['memo'],
            //         'money'           => $this->_refundsdf['money'],
            //         'refund_apply_bn' => $refund_bn,
            //     );
            //     $refundApplyModel->update($updateData,$filter);
            //     $this->_apiLog['info'][] = "更新退款申请单[{$refund_bn}]状态：{$status}";
            // }

            kernel::single('ome_order_func')->update_order_pay_status($order_id);

            $this->_apiLog['info'][] = "更新订单[{$order_id}]支付状态";
        }
        $this->_apiLog['info'][] = 'O.K';
    }

    /**
     * 更新退款单状态
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        $status    = $this->_refundsdf['status'];
        $refund_bn = $this->_refundsdf['refund_bn'];
        $order_bn  = $this->_refundsdf['order_bn'];
        $shop_id   = $this->_shop['shop_id'];

        // 日志
        $this->_apiLog['title']  = '前端店铺更新退款单状态[order_bn:'. $order_bn . ']';
        $this->_apiLog['info'][] = '接收参数：'.var_export($this->_refundsdf,true);
        $this->_apiLog['info'][] = '前端店铺信息：'.var_export($this->_shop,true);
        $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;

        if (!$status) {
            $this->_apiLog['info']['msg'] = 'status is empty';
            $this->exception(__METHOD__);
        }

        if (!$order_bn) {
            $this->_apiLog['info']['msg'] = 'no order bn';
            $this->exception(__METHOD__);
        }

        if (!$refund_bn) {
            $this->_apiLog['info']['msg'] = 'no refund bn';
            $this->exception(__METHOD__);
        }

        // 订单
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump(array('order_bn' => $order_bn,'shop_id' => $shop_id),'order_id');
        if (!$order) {
            $this->_apiLog['info']['msg'] = 'no order in TAOGUAN';
            $this->exception(__METHOD__);
        }

        // 退货单
        $refundModel = app::get(self::_APP_NAME)->model('refunds');
        $refund = $refundModel->dump(array('refund_bn'=>$refund_bn,'shop_id'=>$shop_id));
        if (!$refund) {
            $this->_apiLog['info']['msg'] = 'no refund in TAOGUAN';
            $this->exception(__METHOD__);
        }

        if ($status == 'succ') {
            $filter = array('refund_bn' => $refund_bn,'shop_id' => $shop_id );
            $updateData = array('status' => $status);
            $refundModel->update($updateData,$filter);

            $this->_apiLog['info'][] = "更新退款单[{$return_bn}]状态：{$status}";
        } else {
            $this->_apiLog['info'][] = "不更新退款单[{$refund_bn}]状态";
        }

        $this->_apiLog['info'][] = 'O.K';
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
    public function aftersale_add(){}

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
                             $this->_refundsdf['order_bn']);

        $data = array('tid'=>$this->_refundsdf['order_bn'],'refund_id'=>$this->_refundsdf['refund_bn'],'retry'=>$retry);

        $this->_respservice->send_user_error($this->_apiLog['info']['msg'],$data);
        exit;
    }

    
}