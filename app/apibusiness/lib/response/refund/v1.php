<?php
/**
* 退款单 版本一
*
* @category apibusiness
* @package apibusiness/response/refund
* @author chenping<chenping@shopex.cn>
* @version $Id: v1.php 2013-3-12 17:23Z
*/
class apibusiness_response_refund_v1 extends apibusiness_response_refund_abstract
{

    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        if ($this->_refundsdf['status'] == 'succ' || $this->_refundsdf['refund_type'] == 'refund') {
            if (bccomp($tgOrder['payed'], $this->_refundsdf['money'],3) < 0) {
                $this->_apiLog['info']['msg'] = '退款失败,支付金额('.$tgOrder['payed'].')小于退款金额('.$this->_refundsdf['money'].')';
                return false;        
            }
        }

        // 订单状态判断
        if ($tgOrder['process_status'] == 'cancel') {
            $this->_apiLog['info']['msg'] = '订单['.$this->_refundsdf['order_bn'].']已经取消，无法退款';
            return false;
        }

        return parent::canAccept($tgOrder);
    }


    /**
     * 添加退款单
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        parent::add();

        if ($this->_refundsdf['status']=="succ" || $this->_refundsdf['refund_type'] == 'refund') {

            $shop_id      = $this->_shop['shop_id'];
            $refund_money = $this->_refundsdf['money'];
            $order_bn     = $this->_refundsdf['order_bn'];

            $orderModel = app::get(self::_APP_NAME)->model('orders');
            $order = $orderModel->dump(array('order_bn' => $order_bn,'shop_id' => $shop_id),'order_id');

            $this->_updateOrder($order['order_id'],$refund_money);

            $this->_apiLog['info'][] = "更新订单[{$order_bn}]支付状态";       

        }
    }

    /**
     * 更新退款单状态
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        parent::status_update();

        $shop_id   = $this->_shop['shop_id'];
        $order_bn  = $this->_refundsdf['order_bn'];
        $refund_bn = $this->_refundsdf['refund_bn'];

        $refundModel = app::get(self::_APP_NAME)->model('refunds');

        $refund_detail = $refundModel->dump(array('refund_bn'=>$refund_bn,'shop_id'=>$shop_id));

        $order_id = $refund_detail['order_id'];
        
        $this->_updateOrder($order_id,$refund_detail['money']);
        
    }
}