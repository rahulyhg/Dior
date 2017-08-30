<?php
/**
* amazon(亚马逊平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/amazon
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_amazon_abstract extends apibusiness_response_order_abstractbase
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

        if ($this->_ordersdf['trade_type'] == 'AFN') {
            $this->_apiLog['info']['msg'] = '不接受配送方式为亚马逊配送的订单';
            return false;
        }

        if (empty($this->_ordersdf['consignee']['addr']) && empty($this->_ordersdf['consignee']['name'])) {
            $this->_apiLog['info']['msg'] = '收货人信息不完整';
            return false;
        }

        return true;
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

        if (trim($this->_ordersdf['shipping']['shipping_name']) == '卖家自行配送') {
            $this->_newOrder['self_delivery'] = 'true';
        } else {
            $this->_newOrder['self_delivery'] = 'false';
        }
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
}