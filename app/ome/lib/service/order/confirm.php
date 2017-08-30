<?php
/**
 * 订单编辑配置service
 * 有关订单编辑的配置商品类型处理
 * @author Chris.Zhang
 * @package ome_service_order_edit
 * @copyright www.shopex.cn 2011.02.25
 *
 */
class ome_service_order_confirm{
    
    /*
     * 获取订单编辑的商品类型配置列表
     * @return array conf
     */
    public function view_list(){
        return kernel::single("ome_order_confirm")->get_view_list();
    }
    
}