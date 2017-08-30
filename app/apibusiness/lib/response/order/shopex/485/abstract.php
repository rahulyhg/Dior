<?php
/**
* 485(485系统)抽象类
*
* @category apibusiness
* @package apibusiness/response/order/shopex/485
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_shopex_485_abstract extends apibusiness_response_order_shopex_abstract
{
    /**
     * 数据转
     *
     * @return void
     * @author 
     **/
    public function reTransSdf()
    {
        parent::reTransSdf();

        foreach ($this->_ordersdf['order_objects'] as $key_obj => $value_obj) {
            foreach ($value_obj['order_items'] as $key_item => $value_item) {
                $this->_ordersdf['order_objects'][$key_obj]['order_items'][$key_item]['item_type'] = ($value_item['item_type'] == 'goods') ? 'product' : $value_item['item_type'];
            }
        }
    }

}