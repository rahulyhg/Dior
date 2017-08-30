<?php
class omepkg_order_fail {

    function modify_order_after($orders){
        if (empty($orders)) return false;
        $oPkggoods = &app::get('omepkg')->model('pkg_goods');
        $oPkgproduct = &app::get('omepkg')->model('pkg_product');
        $oOrder_items = &app::get('ome')->model('order_items');
        $oOrder_objects = &app::get('ome')->model('order_objects');
        $oProducts = &app::get('ome')->model('products');

        foreach($orders['order_objects'] as $key=>$objects){
            $obj_flag = false;
            $new_objects = array();
            if ($objects['obj_type'] != 'pkg'){
                foreach($objects['order_items'] as $item_key=>$item_val){
                    if ($item_val['item_type'] != 'pkg'){
                        $new_items = array();
                        // 判断是否为捆绑商品
                        if ($goods = $oPkggoods->dump(array('pkg_bn'=>$item_val['bn']))){
                            $new_objects = array(
                                'order_id'      => $orders['order_id'],
                                'obj_type'      => 'pkg',
                                'obj_alias'     => '捆绑商品',
                                'shop_goods_id' => $objects['shop_goods_id'],
                                'oid'           => $objects['oid'],
                                'goods_id'      => $goods['goods_id'],
                                'bn'            => $item_val['bn'],
                                'name'          => $item_val['name'],
                                'price'         => $item_val['price'],
                                'amount'        => $item_val['amount'],
                                'pmt_price'     => $item_val['pmt_price'],
                                'sale_price'    => $item_val['sale_price'],
                                'quantity'      => $item_val['quantity'],
                                'weight'        => $item_val['weight'],
                                'score'         => $item_val['score'],
                            );
                            $items = $oPkgproduct->getList('*', array('goods_id'=>$goods['goods_id']));
                            foreach($items as $pkg_items){
                                $product_detail = $oProducts->dump(array('bn'=>$pkg_items['bn']));
                                $new_items[] = array(
                                    'order_id' => $orders['order_id'],
                                    'shop_goods_id' => '0',
                                    'product_id' => $product_detail['product_id'],
                                    'shop_product_id' => '0',
                                    'bn' => $product_detail['bn'],
                                    'name' => $product_detail['name'],
                                    'cost' => '0',
                                    'price' => '0',
                                    'amount' => '0',
                                    'weight' => $product_detail['weight'],
                                    'nums' => $item_val['quantity']*$pkg_items['pkgnum'],
                                    'sendnum' => '0',
                                    'item_type' => 'pkg',
                                    'score' => '0',
                                    'delete' => $item_val['delete'],
                                );
                            }
                            // 添加新的pkg与items
                            $oOrder_objects->insert($new_objects);
                            if ($new_objects['obj_id']){
                                if ($new_items){
                                    foreach ($new_items as $newitems){
                                        $newitems['obj_id'] = $new_objects['obj_id'];
                                        $oOrder_items->insert($newitems);
                                        //增加捆绑拆分后的货品冻结
                                        if($newitems['delete'] != 'true'){
                                            $oProducts->chg_product_store_freeze($newitems['product_id'],intval($newitems['nums']),"+");
                                        }
                                    }
                                }
                            }
                            // 删除原始items
                            $oOrder_items->delete(array('item_id'=>$item_val['item_id'],'order_id'=>$orders['order_id']));
                        }
                    }
                }
                // 删除原始pkg:items为空
                if (!$oOrder_items->dump(array('obj_id'=>$objects['obj_id']))){
                    $oOrder_objects->delete(array('obj_id'=>$objects['obj_id'],'order_id'=>$orders['order_id']));
                }
            }
        }
        return true;
    }

}