<?php
/**
* amazon(亚马逊)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: amazon.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_amazon extends apibusiness_request_partyabstract
{
    /**
     * 发货处理
     *
     * @return void
     * @author 
     **/
    public function delivery_request($delivery)
    {
        if($delivery['order']['self_delivery'] == 'false') return true;

        return parent::delivery_request($delivery);
    }

    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {

        $item_list = array();
        $Oorder_items = app::get(self::_APP_NAME)->model('order_items');
        $orderitems = $Oorder_items->getList('shop_goods_id,bn',array('order_id'=>$delivery['order']['order_id']));

        foreach ($orderitems as $v) {
            $orderitem[$v['bn']] = $v['shop_goods_id'];
        }

        foreach ($delivery['delivery_items'] as $k=>$v) {
            $item_list[$k]['oid'] = $delivery['order']['order_bn'];
            $item_list[$k]['itemId'] = $orderitem[$v['bn']];//取order_items上的商品ID
            $item_list[$k]['num'] = $v['number'];
        }
        
        $param = array(
            'tid'               => $delivery['order']['order_bn'],
            'company_code'      => $delivery['dly_corp']['type'],
            'company_name' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no'      => $delivery['logi_no'] ? $delivery['logi_no'] : '',
            'item_list'         => json_encode($item_list),//发货明细
        );

        return $param;
    }// TODO TEST

    /**
     * 获取必要的发货数据
     *
     * @param Array $delivery 发货单信息
     * @return MIX
     * @author 
     **/
    protected function format_delivery($delivery)
    {
        $delivery = parent::format_delivery($delivery);

        // 发货单明细
        $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
        $develiy_items = $deliItemModel->getList('product_name as name,bn,number',array('delivery_id'=>$delivery['delivery_id']));

        // 过滤发货单明细中的空格
        foreach((array)$develiy_items as $key=>$item){
            $develiy_items[$key] = array_map('trim', $item);
        }

        $delivery['delivery_items'] = $develiy_items;

        return $delivery;
    }
    /**
     * 售后请求
     * @param   array    $returninfo    售后信息
     * @return  
     * @access  protected
     * @author 
     */
    protected function update_aftersale_request($returninfo)
    {

    }

    public function update_order_shippinginfo($order)
    {
        
    }
}