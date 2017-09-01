<?php

class ome_sales_data_type_pkg{

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

        //捆绑取最后一个货品的item_id做下标以及库存信息
        $items = $obj['order_items'];
        foreach($items as $k =>$item){
            $mark = $k;
            $pkg['order_id'] = $item['order_id'];
            $pkg['item_id'] = $item['item_id'];
            $pkg['obj_id'] = $item['obj_id'];
        }

        $sale_item[$mark] = array(
    		'iostock_id'=>'',
    		'product_id' => 0,
    		'bn' => $obj['bn'],
    		'name' => $obj['name'],
    		'spec_name'=>'',
    		'pmt_price' => $obj['pmt_price'],
    		'orginal_price' => $obj['price'],
    		'price' => $obj['price'],
    		'nums' => $obj['quantity'],
    		'sale_price' => $obj['sale_price'],
    		'cost'=> 0.00,
        	'obj_id' => $obj['obj_id'],
        );

        $delivery_items_detail_info = $delivery_items_detailObj->dump(array('order_id'=>$pkg['order_id'],'order_item_id'=>$pkg['item_id'],'order_obj_id'=>$pkg['obj_id'],'delivery_id'=>$delivery_id));
        $sale_item[$mark]['item_detail_id'] = $delivery_items_detail_info['item_detail_id'];

        $delivery_info = $deliveryObj->dump(array('delivery_id'=>$delivery_items_detail_info['delivery_id']),'branch_id');
        $sale_item[$mark]['branch_id'] = $delivery_info['branch_id'];

        return $sale_item;
    }
}