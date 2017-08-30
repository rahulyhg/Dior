<?php
/**
 * 前端店铺退款业务处理
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_version_1_refund extends ome_rpc_response_version_base_refund
{

    /**
     * 添加退款单
     * @access public
     * @param array $refund_sdf 退款单数据
     * @return array 退款单主键ID array('refund_id'=>'退款单主键ID')
     */
    function add($refund_sdf){
        
        $rs = parent::add($refund_sdf);
        
        if ($rs['rsp'] == 'success' && $rs['update_order'] == 'true'){
            $shop_id = $refund_sdf['shop_id'];
            $refund_money = $refund_sdf['money'];
            $order_bn = $refund_sdf['order_bn'];
            $orderObj = &app::get('ome')->model('orders');
            $order_detail = $orderObj->getRow(array('shop_id'=>$shop_id,'order_bn'=>$order_bn), 'order_id');
            $this->_updateOrder($order_detail['order_id'],$refund_money);

            $logInfo .= '更新订单：' . $order_bn . '支付状态<BR>';
            $rs['logInfo'] .= $logInfo;
        }
        return $rs;
    }

    /**
     * 更新退款单状态
     * @access public
     * @param array $status_sdf 退款单状态数据
     */
    function status_update($status_sdf){

        $rs = parent::status_update($status_sdf);

        if ($rs['rsp'] == 'success'){
            $shop_id = $status_sdf['shop_id'];
            $order_bn = $status_sdf['order_bn'];
            $refund_bn = $status_sdf['refund_bn'];
            $refundObj = &app::get('ome')->model('refunds');
            $refund_detail = $refundObj->dump(array('refund_bn'=>$refund_bn,'shop_id'=>$shop_id));
            $order_id = $refund_detail['order_id'];
            $this->_updateOrder($order_id,$refund_detail['money']);
        }
        return $rs;
    }

}