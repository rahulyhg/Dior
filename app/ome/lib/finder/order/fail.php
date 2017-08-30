<?php
class ome_finder_order_fail{
    var $detail_fail = '货品修正';

    function detail_fail($order_id){
        $render = app::get('ome')->render();
        $oOrder = &app::get('ome')->model('orders');

        $item_list = $oOrder->getItemList($order_id,true);
        $item_list = ome_order_func::add_getItemList_colum($item_list);
        ome_order_func::order_sdf_extend($item_list);
        $productObj = &app::get('ome')->model('products');
        if ($item_list){
            foreach ($item_list as &$obj){
                if ($obj){
                    foreach ($obj as &$value){
                        if ($value['order_items']){
                            foreach($value['order_items'] as &$item){
                                #商品是否存在于TG中
                                if ($productObj->count(array('bn'=>$item['bn']))){
                                    $item['status'] = '1';
                                }else{
                                    $item['status'] = '0';
                                }

                            }
                        }
                    }
                }
            }
        }
        $orders = $oOrder->dump($order_id,'*');
        $shopex_shop_list = ome_shop_type::shopex_shop_type();
        $shops = &app::get('ome')->model('shop')->dump(array('shop_id'=>$orders['shop_id']),'node_type');

        $render->pagedata['shop_type'] = in_array($shops['node_type'],$shopex_shop_list) ? 'shopex' : 'c2c';
        $render->pagedata['orderInfo'] = $orders;
        $render->pagedata['item_list'] = $item_list;
        $render->pagedata['object_alias'] = $oOrder->getOrderObjectAlias($order_id);
        return $render->fetch('admin/order/detail_fail.html');
    }
}