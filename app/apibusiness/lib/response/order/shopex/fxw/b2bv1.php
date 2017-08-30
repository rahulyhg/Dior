<?php
/**
* fxw(分销王系统)分销订单处理 版本一
*
* @category apibusiness
* @package apibusiness/response/order/shopex/fxw
* @author chenping<chenping@shopex.cn>
* @version $Id: b2bv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_shopex_fxw_b2bv1 extends apibusiness_response_order_shopex_fxw_abstract
{
    protected function accept_dead_order()
    {
       $result = parent::accept_dead_order();
        if ($result === false) {
            if ($this->_ordersdf['status'] == 'dead' ) {
                unset($this->_apiLog['info']['msg']);
                return true;
            }
        }
       return $result; 
    }

    /**
     * 插件
     *
     * @return void
     * @author 
     **/
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

    /**
     * 获取插件
     *
     * @return void
     * @author 
     **/
    public function get_update_plugins()
    {
        $plugins = parent::get_update_plugins();

        $plugins[] = 'sellingagent';

        //$key = array_search('refundapply', $plugins);
        //if($key !== false) unset($plugins[$key]);
        
        return $plugins;
    }
}