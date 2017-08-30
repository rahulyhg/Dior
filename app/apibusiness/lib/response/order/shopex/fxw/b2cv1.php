<?php
/**
* fxw(分销王系统)直销订单处理 版本一
*
* @category apibusiness
* @package apibusiness/response/order/shopex/fxw
* @author chenping<chenping@shopex.cn>
* @version $Id: b2cv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_shopex_fxw_b2cv1 extends apibusiness_response_order_shopex_fxw_abstract
{
    public function get_create_plugins()
    {
        $plugins = parent::get_create_plugins();

        $plugins[] = 'sellingagent';

        return $plugins;
    }

    protected function get_update_components()
    {
        $components = parent::get_update_components();
        $components[] = 'consigner';

        return $components;
    }

    public function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        $plugins[] = 'sellingagent';

        return $plugins;
    }
}