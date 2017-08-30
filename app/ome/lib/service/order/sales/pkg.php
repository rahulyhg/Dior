<?php
/**
 * 捆绑商品销售单据相关计算
 * @package ome_service_order_sales_pkg
 * @author ome 2011.4.1
 * @copyright shopex.cn
 */
class ome_service_order_sales_pkg{
    
    /**
     * 计算捆绑差额
     * @access public
     * @param $order_objects objects_sdf 结构
     * @return Number 差额
     */
    public function get_difference($order_objects=array()){
        if (empty($order_objects)) return 0;
        
        $difference = kernel::single('ome_order_order')->obj_difference($order_objects);
        return $difference;
    }
    
}