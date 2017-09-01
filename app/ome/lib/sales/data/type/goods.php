<?php

class ome_sales_data_type_goods{

    public function doTrans($obj){
        $deliveryObj = &app::get('ome')->model('delivery');
        $delivery_items_detailObj = &app::get('ome')->model('delivery_items_detail');
        $productsObj = &app::get('ome')->model('products');
        $specObj  = &app::get('ome')->model('specification');
        $spec_valuesObj  = &app::get('ome')->model('spec_values');

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
                'pmt_price' => $item['pmt_price'],
                'orginal_price' => $item['price'],
            	'price' => $item['price'],
                'nums' => $item['quantity'],
            	'sale_price' => $item['sale_price'],
                'cost'=> $item['cost'],
                'obj_id' => $obj['obj_id'],
            );

            $delivery_items_detail_info = $delivery_items_detailObj->dump(array('order_id'=>$item['order_id'],'order_item_id'=>$item['item_id'],'order_obj_id'=>$item['obj_id'],'delivery_id'=>$delivery_id));
            $sale_item[$k]['item_detail_id'] = $delivery_items_detail_info['item_detail_id'];

            $delivery_info = $deliveryObj->dump(array('delivery_id'=>$delivery_items_detail_info['delivery_id']),'branch_id');
            $sale_item[$k]['branch_id'] = $delivery_info['branch_id'];


            $tmp_poducts =$productsObj->dump(array('product_id'=>$item['product_id']),'spec_desc');
            $spec_desc = $tmp_poducts['spec_desc'];
            $productattr = '';
            if ($spec_desc['spec_value_id']){
                foreach ($spec_desc['spec_value_id'] as $sk=>$sv){
                    $specval = $spec_valuesObj->dump($sv,"spec_value,spec_id");
                    $spec = $specObj->dump($specval['spec_id'],"spec_name");
                    $productattr .= $spec['spec_name'].':'.$specval['spec_value'].';';
                }
            }
            $sale_item[$k]['spec_name'] = $productattr;
        }
        return $sale_item;
    }
}