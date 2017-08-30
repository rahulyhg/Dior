<?php
/**
 * 订单余单撤销金额计算商品类型goods
 * 有关订单余单撤销金额计算商品类型goods
 * @author Chris.Zhang
 * @package ome_service_order_object_goods
 * @copyright www.shopex.cn 2011.04.06
 *
 */
class ome_service_order_object_goods {
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function diff_money($obj){
        return kernel::single("ome_order_remain_goods")->diff_money($obj);
    }
    
}