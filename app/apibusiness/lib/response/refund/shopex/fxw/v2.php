<?php
/**
* 退款单 版本一
*
* @category apibusiness
* @package apibusiness/response/refund
* @author chenping<chenping@shopex.cn>
* @version $Id: v1.php 2013-3-12 17:23Z
*/
class apibusiness_response_refund_shopex_fxw_v2 extends apibusiness_response_refund_v2
{

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
}