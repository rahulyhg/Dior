<?php
/**
 * 订单确认的页面处理
 * 对订单编辑的显示页面与提交操作的实现
 * @author chris.zhang
 * @package ome_order_products
 * @copyright www.shopex.cn 2011.02.25
 *
 */
class ome_order_products{
    /**
     * 获取订单编辑时每种objtype的显示内容定义
     * @access public
     * @param int $reship_id 退货单ID
     */
    public function get_view_list(){
        $conf_list = array(
            'pkg'       => $this->view_pkg(),
            'gift'      => $this->view_gift(),
            'goods'     => $this->view_goods(),
            'giftpackage'   => $this->view_giftpackage(),
        );
        return $conf_list;
    }

    public function view_pkg(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/pkg_view.html',
        );
        return $config;
    }
    public function view_gift(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/gift_view.html',
        );
        return $config;
    }
    public function view_goods(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/goods_view.html',
        );
        return $config;
    }
    public function view_giftpackage(){
        $config = array(
           'app' => 'ome',
           'html' => 'admin/order/products/giftpackage_view.html',
        );
        return $config;
    }
}