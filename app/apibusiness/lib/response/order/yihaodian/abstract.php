<?php
/**
* yihaodian(1号店平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/yihaodian
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_yihaodian_abstract extends apibusiness_response_order_abstractbase
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
     * 需要更新的组件
     *
     * @return void
     * @author 
     **/
    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype','tax');

        return $components;
    }

    protected function reTransSdf()
    {
        parent::reTransSdf();
        
        // 重新计算商品优惠
        $pmt_goods = $cost_item = 0;
        foreach ($this->_ordersdf['order_objects'] as $objkey => $object){
            foreach ($object['order_items'] as $itemkey => $item){
                if($item['status'] == 'close') continue;

                $pmt_goods += (float) $item['pmt_price'];

                $cost_item += $item['amount'] ? (float) $item['amount'] : bcmul((float) $item['price'], $item['quantity'],3);
            }
        }
        
        $total_amount = (float) $cost_item 
                                + (float) $this->_ordersdf['shipping']['cost_shipping'] 
                                + (float) $this->_ordersdf['shipping']['cost_protect'] 
                                + (float) $this->_ordersdf['discount'] 
                                + (float) $this->_ordersdf['cost_tax'] 
                                + (float) $this->_ordersdf['payinfo']['cost_payment'] 
                                - (float) $pmt_goods
                                - (float) $this->_ordersdf['pmt_order'];
        if(0 == bccomp($this->_ordersdf['pmt_goods'], 0,3)
            && 1 == bccomp($pmt_goods, 0,3)
            && 0 == bccomp($total_amount, $this->_ordersdf['total_amount'],3) ){
            $this->_ordersdf['cost_item'] = $cost_item;
            $this->_ordersdf['pmt_goods'] = $pmt_goods;
        }
    }
}