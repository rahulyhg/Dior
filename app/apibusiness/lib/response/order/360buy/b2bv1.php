<?php
/**
* 360buy(京东平台)分销订单处理 版本一
*
* @category apibusiness
* @package apibusiness/response/order/360buy
* @author chenping<chenping@shopex.cn>
* @version $Id: b2bv1.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_360buy_b2bv1 extends apibusiness_response_order_360buy_abstract
{

    /**
     * 是否接收订单
     *
     * @return void
     * @author 
     **/
    protected function canAccept()
    {

        $this->_apiLog['info']['msg'] = '京东分销订单暂时不接收';

        return false;
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
        
        return $plugins;
    }

    /**
     * 对平台接收的数据纠正(有些是前端打的不对的)
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        parent::reTransSdf();

        foreach($this->_ordersdf['selling_agent'] as $k=>$v){
            if($k == 'agent'){
                $this->_ordersdf['selling_agent']['member_info'] = $this->_ordersdf['selling_agent']['agent'];
                unset($this->_ordersdf['selling_agent']['agent']);
            }
        }
    }
    
}