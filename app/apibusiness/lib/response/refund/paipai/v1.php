<?php
/**
* 退款单 版本一
*
* @category apibusiness
* @package apibusiness/response/refund
* @author chenping<chenping@shopex.cn>
* @version $Id: v1.php 2013-3-12 17:23Z
*/
class apibusiness_response_refund_paipai_v1 extends apibusiness_response_refund__v1
{

    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        
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

            //if (!apibusiness_router_mapping::$shopex[$shop_type]) {

                $shop_id      = $this->_shop['shop_id'];
                $refund_money = $this->_refundsdf['money'];
                $order_bn     = $this->_refundsdf['order_bn'];

                $orderModel = app::get(self::_APP_NAME)->model('orders');
                $order = $orderModel->dump(array('order_bn' => $order_bn,'shop_id' => $shop_id),'order_id');

                $this->_updateOrder($order['order_id'],$refund_money);

                $this->_apiLog['info'][] = "更新订单[{$order_bn}]支付状态";       
            //}
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