<?php

class ome_sales_data_type_gift{

    public function doTrans($obj){
        $deliveryObj = &app::get('ome')->model('delivery');
        $delivery_items_detailObj = &app::get('ome')->model('delivery_items_detail');

        $delivery_id = $obj['delivery_id'];
        
        #[拆单]获取订单对应所有发货单delivery_id ExBOY
        $oDelivery      = &app::get('ome')->model('delivery');
        $split_seting   = $oDelivery->get_delivery_seting();
        if($split_seting && !empty($obj['order_id']))
        {
            $order_id       = $obj['order_id'];
            $delivery_id    = $oDelivery->getDeliverIdByOrderId($order_id);
        }

        $items = $obj['order_items'];
        foreach($items as $k =>$item){
            $sale_item[$k] = array(
                'iostock_id'=>'',
                'product_id' => $item['product_id'],
                'bn' => $item['bn'],
                'name' => $item['name'],
                'spec_name'=>'',
                'pmt_price' => 0.00,
                'orginal_price' => 0.00,
            	'price' => 0.00,
                'nums' => $item['quantity'],
            	'sale_price' => 0.00,
                'cost'=> $item['cost'],
                'obj_id' => $obj['obj_id'],
            );

            $delivery_items_detail_info = $delivery_items_detailObj->dump(array('order_id'=>$item['order_id'],'order_item_id'=>$item['item_id'],'order_obj_id'=>$item['obj_id'],'delivery_id'=>$delivery_id));
            $sale_item[$k]['item_detail_id'] = $delivery_items_detail_info['item_detail_id'];

            $delivery_info = $deliveryObj->dump(array('delivery_id'=>$delivery_items_detail_info['delivery_id']),'branch_id');
            $sale_item[$k]['branch_id'] = $delivery_info['branch_id'];
        }
        return $sale_item;
    }
}