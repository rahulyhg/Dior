<?php
/**
* vjia(凡客平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/vjia
* @author shangshuai<shangshuai@shopex.cn>
* @version $Id: abstract.php 2013-08-01 17:23Z
*/
abstract class apibusiness_response_order_vjia_abstract extends apibusiness_response_order_abstractbase
{
    /**
     * 是否接收订单
     *
     * @return void
     * @author
     **/
    protected function canAccept()
    {
        $result = parent::canAccept();
        if ($result === false) {
            return false;
        }

        # 未支付的款到发货订单拒收
        if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }

        return true;
    }

    /**
     * 对平台接收的数据纠正(有些是前端打的不对的)
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        // 如果是担保交易,订单支付状态修复成已支付
        if ($this->_ordersdf['pay_status'] == '2') {
            $this->_ordersdf['pay_status'] = '1';
        }

        $this->_ordersdf['shop_id']   = $this->_shop['shop_id'];
        $this->_ordersdf['shop_type'] = $this->_shop['shop_type'];

        //vjia货到付款不重置支付信息
    }

    /**
     * 订单转换淘管格式
     *
     * @return void
     * @author
     **/
    public function component_convert()
    {
        parent::component_convert();

        $this->_newOrder['pmt_goods'] = abs($this->_newOrder['pmt_goods']);
        $this->_newOrder['pmt_order'] = abs($this->_newOrder['pmt_order']);
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

        $plugins[] = 'outstorage';

        return $plugins;
    }

    /**
     * 需要更新的组件
     *
     * @return void
     * @author
     **/
    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype');

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

        return $plugins;
    }

    /**
     * 能够创建订单
     *
     * @return void
     * @author 
     **/
    public function canCreate()
    {

        if ($this->_ordersdf['status'] != 'active') {
            $this->_apiLog['info']['msg'] = '取消的订单不接收';
            return false;
        }     

        return parent::canCreate();
    }

}