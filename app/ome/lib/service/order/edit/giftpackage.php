<?php
/**
 * 订单编辑giftpackage类型的service
 * 有关订单编辑giftpackage类型的扩展功能都可以使用此服务
 * 此service请使用kerner::service()方法
 * @author Chris.Zhang
 * @package ome_service_order_edit_giftpackage
 * @copyright www.shopex.cn 2011.03.15
 *
 */
class ome_service_order_edit_giftpackage{
    /**
     * 获取goods的显示定义
     * @access public
     */
    public function get_config(){
        return kernel::single("ome_order_edit_giftpackage")->get_config();
    }
    
    /**
     * 处理订单编辑时提交的数据
     * @access public
     * @param array $data 订单编辑的数据
     */
    public function process($data){
        return kernel::single("ome_order_edit_giftpackage")->process($data);
    }
    
    /**
     * 判断这次提交的数据在处理完成后，是否还存在有正常的数据。
     * @param array $data 订单编辑的数据  //POST
     */
    public function is_null($data){
        return kernel::single("ome_order_edit_giftpackage")->is_null($data);
    }
    
    /**
     * 校验订单编辑时提交的数据
     * @param array $data 订单编辑的数据  //POST
     */
    public function valid($data){
        return kernel::single("ome_order_edit_giftpackage")->valid($data);
    }
}