<?php
/**
 * 订单状态
 * 返回订单支付状态、订单状态、订单发货状态类型值
 * @access public
 * @author ome 2010.11.24
 * @copyright www.shopex.cn
 *
 */
class ome_order_status{
    
    /**
     * 订单支付状态
     * @access public
     * @param int $status
     * @return ArrayIterator 订单支付状态类型
     */
    public function pay_status($status=NULL){
        $pay_status = array (
            0 => '未支付',
            1 => '已支付',
            2 => '处理中',
            3 => '部分付款',
            4 => '部分退款',
            5 => '全额退款',
            6 => '退款申请中',
            7 => '退款中',
            8 => '支付中',
        );
        if ($status == NULL){
            return $pay_status;
        }else{
            return $pay_status[$status];
        }
    }
    
    /**
     * 订单发货状态
     * @access public
     * @param int $status
     * @return ArrayIterator 订单发货状态类型
     */
    public function ship_status($status=NULL){
        $ship_status = array (
            0 => '未发货',
            1 => '已发货',
            2 => '部分发货',
            3 => '部分退货',
            4 => '已退货',
        );
        if ($status == NULL){
            return $ship_status;
        }else{
            return $ship_status[$status];
        }
    }
    
    /**
     * 订单状态
     * @access public
     * @param int $status
     * @return ArrayIterator 订单状态类型
     */
    public function order_status($status=NULL){
        $order_status = array (
            'active' => '活动订单',
            'dead' => '已作废',
            'finish' => '已完成',
        );
        if ($status == NULL){
            return $order_status;
        }else{
            return $order_status[$status];
        }
    }
    
}