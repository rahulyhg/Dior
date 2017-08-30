<?php
/**
 * 前端店铺订单数据业务处理
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response_version_2_order extends ome_rpc_response_version_base_order
{

   /**
     * 更新订单状态
     * @access public
     * @param Array $order_sdf 待更新订单状态标准结构数据
     */
    public function status_update($order_sdf){
        return array('rsp'=>'success','msg'=>'','data'=>$order_sdf['order_bn']);
    }
    
    /**
     * 更新订单支付状态
     * @access public
     * @param Array $order_sdf  待更新订单支付状态标准结构数据
     */
    public function pay_status_update($order_sdf){
        return array('rsp'=>'success','msg'=>'','data'=>$order_sdf['order_bn']);
    }
    
    /**
     * 更新订单发货状态
     * @access public
     * @param Array $order_sdf 待更新订单发货状态标准结构数据
     */
    public function ship_status_update($order_sdf){
        return array('rsp'=>'success','msg'=>'','data'=>$order_sdf['order_bn']);
    }
    
    /**
     * 添加买家留言
     * @access public
     * @param Array $order_sdf 买家留言标准结构数据
     */
    public function custom_mark_add($order_sdf){
        return array('rsp'=>'success','msg'=>'','data'=>$order_sdf['order_bn']);
    }
    
    /**
     * 更新买家留言
     * @access public
     * @param Array $order_sdf 买家留言标准结构数据
     */
    public function custom_mark_update($order_sdf){
        return array('rsp'=>'success','msg'=>'','data'=>$order_sdf['order_bn']);
    }
    
    /**
     * 添加订单备注
     * @access public
     * @param Array $order_sdf 订单备注标准结构数据
     */
    public function memo_add($order_sdf){
        return array('rsp'=>'success','msg'=>'','data'=>$order_sdf['order_bn']);
    }
    
    
    /**
     * 更新订单备注
     * @access public
     * @param Array $order_sdf 订单备注注标准结构数据
     */
    public function memo_update($order_sdf){
        return array('rsp'=>'success','msg'=>'','data'=>$order_sdf['order_bn']);
    }

    /**
     * 更新订单支付方式
     * @access public
     * @param Array $order_sdf 订单备注注标准结构数据
     */
    public function payment_update($order_sdf){
        return array('rsp'=>'success','msg'=>'','data'=>$order_sdf['order_bn']);
    }

}
?>