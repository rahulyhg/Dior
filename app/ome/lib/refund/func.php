<?php
/**
 * 退款(申请)单公用函数类
 * @package ome_refund_func
 * @copyright www.shopex.cn 2011.3.1
 * @author ome
 * @version $Revision: 1.0 $
 */
class ome_refund_func{
    
    /**
     * 退款申请单据状态列表
     * @access static public
     * @return ArrayIterator 状态数组对象
     */
    static public function refund_apply_status(){
        $status = array(
            0 => '未审核',
            1 => '审核中',
            2 => '已接受申请',
            3 => '已拒绝',
            4 => '已退款',
            5 => '退款中',
            6 => '退款失败',
        );
        return $status;
    }
    
    /**
     * 获取退款申请单据状态名称
     * @access static public
     * @param String $status
     * @return String 状态名称
     */
    static public function refund_apply_status_name($status=''){
        if (empty($status)) return NULL;
        $refund_apply_status = self::refund_apply_status();
        $status_name = $refund_apply_status[$status];
        if ($status_name){
            return $status_name;
        }else{
            return $status;
        }
    }
    
    /**
     * 退款单据状态列表
     * @access static public
     * @return ArrayIterator 状态数组对象
     */
    static public function refund_status(){
        $status = array (
            'succ' => '支付成功',
            'failed' => '支付失败',
            'cancel' => '未支付',
            'error' => '处理异常',
            'invalid' => '非法参数',
            'progress' => '处理中',
            'timeout' => '超时',
            'ready' => '准备中',
          );
        return $status;
    }
    
    /**
     * 获取申请单据状态名称
     * @access static public
     * @param String $status
     * @return String 状态名称
     */
    static public function refund_status_name($status=''){
        if (empty($status)) return NULL;
        $refund_status = self::refund_status();
        $status_name = $refund_status[$status];
        if ($status_name){
            return $status_name;
        }else{
            return $status;
        }
    }
    
}