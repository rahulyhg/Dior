<?php
/**
 * ome模板定义服务
 * 此服务可适用于ome的模板相关功能
 * @author chris.zhang
 * @name ome.service.template
 * @package ome_service_template
 * @copyright www.shopex.cn 2010.11.24
 *
 */
class wms_service_template{
    /**
     * get print template list
     * 获取定义的快递单打印项的配置列表
     * @return array();
     */
    public function getElements(){
       return kernel::single('wms_delivery_template')->defaultElements();
    }


    /**
     * get default print content
     * 获取快递单打印项的对应内容
     * @param unknown_type $value_list
     * @return string
     */
    public function getElementContent($value_list){
        return kernel::single('wms_delivery_template')->processElementContent($value_list);
    }
}