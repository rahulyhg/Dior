<?php
/**
 * 订单余单撤销处理商品类型giftpackage
 * 有关订单余单撤销处理商品类型giftpackage
 * @author Chris.Zhang
 * @package ome_service_order_remain_giftpackage
 * @copyright www.shopex.cn 2011.04.06
 *
 */
class ome_service_order_remain_giftpackage {
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function diff_money($obj){
        $amount = 0;
        if ($service = kernel::service("ome.service.order.object.diff.giftpackage")){
            if (method_exists($service, 'diff_money')) $amount = $service->diff_money($obj);
        }
        return $amount;
        //return kernel::single("ome_order_remain_giftpackage")->diff_money($obj);
    }

    /*
     * 余单撤销处理
     */
    public function remain_cancel($obj){
        return kernel::single("ome_order_remain_giftpackage")->remain_cancel($obj);
    }
}