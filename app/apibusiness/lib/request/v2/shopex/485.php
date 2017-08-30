<?php
/**
* 485系统接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v2
* @author chenping<chenping@shopex.cn>
* @version $Id: 485.php 2013-13-12 14:44Z
*/
class apibusiness_request_v2_shopex_485 extends apibusiness_request_v2_shopex_abstract
{
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
        
        // 如果是捆绑，取OBJECT上明细还类型
        $orderObjModel = app::get(self::_APP_NAME)->model('order_objects');
        $objCount = $orderObjModel->count(array('order_id'=>$delivery['order']['order_id'],'obj_type'=>'pkg'));
        if ($objCount > 0) {
            $orderObj = $orderObjModel->getList('*',array('order_id'=>$delivery['order']['order_id']));

            // 订单明细
            $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
            $orderItems = $orderItemModel->getList('*',array('order_id'=>$delivery['order']['order_id'],'delete'=>'false'));
            $order_items = array();
            foreach ($orderItems as $key => $item) {
                $order_items[$item['obj_id']][] = $item;
            }
            unset($orderItems);

            $delivery_items = array();
            foreach ($orderObj as $obj) {
                if ($order_items[$obj['obj_id']]) {
                    if ($obj['obj_type'] == 'pkg') {
                        $delivery_items[] = array(
                            'number' => $obj['quantity'],
                            'name' => trim($obj['name']),
                            'bn' => trim($obj['bn']),
                            'sku_type' => 'pkg',
                        );
                    } else {
                        foreach ($order_items[$obj['obj_id']] as $item) {
                            $delivery_items[] = array(
                                'number' => $item['nums'],
                                'name' => trim($item['name']),
                                'bn' => trim($item['bn']),
                                'sku_type' => $item['item_type'],
                            );
                        }
                    }
                }
            }
            
            $delivery['delivery_items'] = $delivery_items;
        } else {
            // 取订单明细
            $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
            $orderItems = $orderItemModel->getList('*',array('order_id'=>$delivery['order']['order_id'],'delete'=>'false'));
            
            $develiy_items = array();
            foreach ((array) $orderItems as $item){
                $develiy_items[] = array(
                    'number' => $item['nums'],
                    'name' => trim($item['name']),
                    'bn' => trim($item['bn']),
                    'sku_type' => $item['item_type'],
                );
            }
            /*
            // 发货单明细
            $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
            $develiy_items = $deliItemModel->getList('product_name as name,bn,number',array('delivery_id'=>$delivery['delivery_id']));

            // 过滤发货单明细中的空格
            foreach((array)$develiy_items as $key=>$item){
                $delivery_items[$key] = array_map('trim', $item);
            }*/

            $delivery['delivery_items'] = $develiy_items;
        }

        return $delivery;
    }// TODO TEST
}