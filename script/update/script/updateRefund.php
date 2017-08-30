<?php
/**
 * 淘宝退款完后，对淘管订单还是审请退款中，对这部分数据进行修正
 * 
 * @author chenping@shopex.cn
 * @version 1.0
 */
error_reporting(E_ALL ^ E_NOTICE);

$domain = $argv[1];
$order_id = $argv[2];
$host_id = $argv[3];
$time = strtotime($argv[4]);

if (empty($domain) || empty($order_id) || empty($host_id) || empty($time)) {

	die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);
$affectRow = 0;

$logModel = app::get('ome')->model('operation_log');
$refundApplyModel = app::get('ome')->model('refund_apply');
$refundModel = app::get('ome')->model('refunds');
$orderModel = app::get('ome')->model('orders');
# 获取退款审请中的订单
$orderList = $orderModel->getList('*',array('pay_status'=>'6','createtime|lthan'=>$time,'abnormal'=>'false','is_fail'=>'false','status'=>'active','archive'=>'0'));  
if (count($orderList) > 40) {
    echo 'data too large!!!'.count($orderList);exit;
}
echo 'count:'.count($orderList)."\r\n";

foreach ($orderList as $order) {
    # 退款单
    $refundList = $refundModel->getList('cur_money',array('order_id'=>$order['order_id']));
    if ($refundList) {
        $refundMoney = 0;
        foreach ($refundList as $refund) {
            $refundMoney = bcadd($refundMoney,$refund['cur_money'],3);
        }

        if ($refundMoney == $order['total_amount']) {
            # 多余退款审请单拒绝
            $refundApplyList = $refundApplyModel->getList('apply_id',array('order_id'=>$order['order_id']));
            if (count($refundApplyList)>5) {
                echo 'data exception!!!';exit;
            }
            foreach ($refundApplyList as $refundApply) {
                $refundApplyModel->update(array('status'=>'3'),array('apply_id'=>$refundApply['apply_id']));
                $logModel->write_log('refund_refuse@ome',$refundApply['apply_id'],"退款申请拒绝：数据过期，批量操作！");
            }
            ilog('update order:payed '.$order['payed'].'、pay_status '.$order['order_bn']);
            # 支付金额置为0
            $orderModel->update(array('payed'=>'0','pause'=>'false'),array('order_id'=>$order['order_id']));
            # 状态更新
            kernel::single('ome_order_func')->update_order_pay_status($order['order_id']);
            #订单取消

            $affectRow++;
        } elseif($refundMoney < $order['total_amount']) {
            ilog('update order:payed '.$order['payed'].'、pay_status '.$order['order_bn']);
            
            $leftMoney = bcsub($order['total_amount'],$refundMoney,3);

            # 未审核的审请单
            $refundApplyList = $refundApplyModel->getList('apply_id,money,refund_apply_bn,order_id,pay_account,memo',array('order_id'=>$order['order_id'],'status'=>array('0','1','2')));
            if (count($refundApplyList)>5) {
                echo 'data exception!!!';exit;
            }
            foreach ($refundApplyList as $refundApply) {

                if (floatval($leftMoney) >= floatval($refundApply['money'])) {
                    echo $refundApply['money']."\r\n";
                    $leftMoney = bcsub($leftMoney,$refundApply['money'],3);
                    # 标记已退款
                    $refundApplyModel->update(array('status'=>'4','refunded'=>$refundApply['money']),array('apply_id'=>$refundApply['apply_id']));

                    # 生成退款单
                    $refunddata['refund_bn'] = $refundApply['refund_apply_bn'] ? $refundApply['refund_apply_bn'] : $refundModel->gen_id();
                    $refunddata['order_id'] = $order['order_id'];
                    $refunddata['shop_id'] = $order['shop_id'];
                    $refunddata['account'] = '';
                    $refunddata['bank'] = '';
                    $refunddata['pay_account'] = $refundApply['pay_account'];
                    $refunddata['currency'] = $order['currency'];
                    $refunddata['money'] = $refundApply['money'];
                    $refunddata['paycost'] = 0;//没有第三方费用
                    $refunddata['cur_money'] = $refundApply['money'];//汇率计算 TODO:应该为汇率后的金额，暂时是人民币金额
                    $refunddata['pay_type'] ='';
                    $refunddata['payment'] = '';
                    //$paymethods = ome_payment_type::pay_type();
                    $refunddata['paymethod'] = '';
                    //Todo ：确认paymethod
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    $refunddata['op_id'] = $opInfo['op_id'];

                    $refunddata['t_ready'] = time();
                    $refunddata['t_sent'] = time();
                    $refunddata['status'] = "succ";#支付状态
                    $refunddata['memo'] = $refundApply['memo'];
                    $refundModel->save($refunddata);

                    # 支付金额置为0
                    $updateData = array('payed'=>$leftMoney);
                    if ($leftMoney == 0) {
                        $updateData['pause'] = 'false';
                    }
                    $orderModel->update($updateData,array('order_id'=>$order['order_id']));

                    # 状态更新
                    kernel::single('ome_order_func')->update_order_pay_status($order['order_id']);
                }
            }

            $affectRow++;
        }
    } else {
            ilog('update order:payed '.$order['payed'].'、pay_status '.$order['order_bn']);

            $leftMoney = $order['total_amount'];
            # 未审核的审请单
            $refundApplyList = $refundApplyModel->getList('apply_id,money,refund_apply_bn,order_id,pay_account,memo',array('order_id'=>$order['order_id'],'status'=>array('0','1','2')));
            foreach ($refundApplyList as $refundApply) {
                if (floatval($leftMoney)>=floatval($refundApply['money'])) {
                    $leftMoney = bcsub($leftMoney,$refundApply['money'],3);
                    # 标记已退款
                    $refundApplyModel->update(array('status'=>'4','refunded'=>$refundApply['money']),array('apply_id'=>$refundApply['apply_id']));

                    # 生成退款单
                    $refunddata['refund_bn'] = $refundApply['refund_apply_bn'] ? $refundApply['refund_apply_bn'] : $refundModel->gen_id();
                    $refunddata['order_id'] = $order['order_id'];
                    $refunddata['shop_id'] = $order['shop_id'];
                    $refunddata['account'] = '';
                    $refunddata['bank'] = '';
                    $refunddata['pay_account'] = $refundApply['pay_account'];
                    $refunddata['currency'] = $order['currency'];
                    $refunddata['money'] = $refundApply['money'];
                    $refunddata['paycost'] = 0;//没有第三方费用
                    $refunddata['cur_money'] = $refundApply['money'];//汇率计算 TODO:应该为汇率后的金额，暂时是人民币金额
                    $refunddata['pay_type'] ='';
                    $refunddata['payment'] = '';
                    //$paymethods = ome_payment_type::pay_type();
                    $refunddata['paymethod'] = '';
                    //Todo ：确认paymethod
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    $refunddata['op_id'] = $opInfo['op_id'];

                    $refunddata['t_ready'] = time();
                    $refunddata['t_sent'] = time();
                    $refunddata['status'] = "succ";#支付状态
                    $refunddata['memo'] = $refundApply['memo'];
                    $refundModel->save($refunddata);

                    # 支付金额置为0
                    $updateData = array('payed'=>$leftMoney);
                    if ($leftMoney == 0) {
                        $updateData['pause'] = 'false';
                    }
                    $orderModel->update($updateData,array('order_id'=>$order['order_id']));

                    # 状态更新
                    kernel::single('ome_order_func')->update_order_pay_status($order['order_id']);
                }
            }

            $affectRow++;
    }

    echo $order['order_bn']."\r\n";
}
echo 'affect row:'.$affectRow;

/**
 * 日志
 */
function ilog($str) {	
    global $domain;
    $filename = dirname(__FILE__) . '/../logs/updateRefund_' . date('Y-m-d') . '.log';
    $fp = fopen($filename, 'a');
    fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
    fclose($fp);
}
