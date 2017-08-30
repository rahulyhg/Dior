<?php
/**
* 退款请求插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: refundapply.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_refundapply extends apibusiness_response_order_plugin_abstract
{
    const _APP_NAME = 'ome';
    /**
     * 更新完成后操作
     *
     * @return void
     * @author 
     **/
    public function preUpdate()
    {
        $refund_money = 0;
        if ($this->_platform->_ordersdf['payed'] > $this->_platform->_ordersdf['total_amount']) {
            $refund_money = bcsub($this->_platform->_ordersdf['payed'], $this->_platform->_ordersdf['total_amount'],3);
        }

        if($refund_money <= 0) return false;
        $refundApplyModel = app::get(self::_APP_NAME)->model('refund_apply');

        $create_time = $this->_platform->_ordersdf['lastmodify'] ? kernel::single('ome_func')->date2time($this->_platform->_ordersdf['lastmodify']) : time();
        $refundApplySdf = array(
            'order_id' => $this->_platform->_tgOrder['order_id'],
            'refund_apply_bn' => $refundApplyModel->gen_id(),
            'pay_type' => 'online',
            'money' => $refund_money,
            'refunded' => '0',
            'memo' => '订单编辑产生的退款申请',
            'create_time' => $create_time,
            'status' => '2',
            'shop_id' => $this->_platform->_shop['shop_id'],
        );

        $refundApplyModel->create_refund_apply($refundApplySdf);

        $logModel = app::get(self::_APP_NAME)->model('operation_log');
        $logModel->write_log('order_edit@ome',$this->_platform->_tgOrder['order_id'],'退款申请');

        $this->_platform->_newOrder['pay_status'] = '6';
        $this->_platform->_newOrder['pause'] = 'true';
    }
}