<?php
/**
 * 订单余单撤销金额计算商品类型pkg
 * 有关订单余单撤销金额计算商品类型pkg
 * @author Chris.Zhang
 * @package ome_service_order_object_pkg
 * @copyright www.shopex.cn 2011.04.06
 *
 */
class ome_service_order_object_pkg {
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function diff_money($obj){
        return kernel::single("ome_order_remain_pkg")->diff_money($obj);
    }
    
}