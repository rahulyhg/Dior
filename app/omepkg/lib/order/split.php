<?php
class omepkg_order_split{
    function order_split($res){
        $goods = &app::get('omepkg')->model('pkg_goods');
        $product = &app::get('omepkg')->model('pkg_product');
        $product_mess = &app::get('ome')->model('products');

        $pkg_objects = array();
        $goods_objects = array();
        $max_key = max(array_keys($res['order_objects']));
        $split_key = $max_key+1;

        foreach($res['order_objects'] as $k=>$v){
            if ($v['obj_type'] == 'pkg') continue;
            foreach($v['order_items'] as $ke=>$val){
                $goods_id = $goods->getPkgBn($val['bn']);
                if($goods_id){
                    $pkg_objects[$split_key]['obj_type'] = 'pkg';
                    $pkg_objects[$split_key]['oid'] = $v['oid'];
                    $pkg_objects[$split_key]['cost_tax'] = $v['cost_tax'];
                    $pkg_objects[$split_key]['buyer_payment'] = $v['buyer_payment'];
                    $pkg_objects[$split_key]['fx_oid'] = $v['fx_oid'];                    
                    $pkg_objects[$split_key]['obj_alias'] = '捆绑商品';
                    $pkg_objects[$split_key]['shop_goods_id'] = $v['shop_goods_id'] ? $v['shop_goods_id'] : '0';
                    $pkg_objects[$split_key]['goods_id'] = $goods_id['goods_id'];
                    $pkg_objects[$split_key]['bn'] = $val['bn'];
                    $pkg_objects[$split_key]['name'] = $val['name'];
                    $pkg_objects[$split_key]['price'] = $val['price'];
                    $pkg_objects[$split_key]['amount'] = $val['amount'];
                    $pkg_objects[$split_key]['pmt_price'] = $val['pmt_price'];
                    $pkg_objects[$split_key]['sale_price'] = $val['sale_price'];
                    $pkg_objects[$split_key]['quantity'] = $val['quantity'];
                    $pkg_objects[$split_key]['weight'] = $val['weight'];
                    $pkg_objects[$split_key]['score'] = $val['score'];
                    $data = $product->getproduct($goods_id['goods_id']);
                    foreach($data as $key=>$value){
                        $array = $product_mess->dump(array('bn'=>$value['bn']));
                        $add_mess = array();
                        $add_mess['shop_goods_id'] = '0';
                        $add_mess['product_id'] = $array['product_id'];
                        $add_mess['shop_product_id'] = '0';
                        $add_mess['bn'] = $array['bn'];
                        $add_mess['name'] = $array['name'];
                        $add_mess['cost'] = '0';
                        $add_mess['price'] = '0';
                        $add_mess['amount'] = '0';
                        $add_mess['weight'] = $array['weight'];
                        $add_mess['quantity'] = $val['quantity']*$value['pkgnum'];
                        $add_mess['sendnum'] = '0';
                        $add_mess['addon'] = null;
                        $add_mess['item_type'] = 'pkg';
                        $add_mess['score'] = '0';
                        $add_mess['delete'] = $val['delete'];
                        $add_mess['cost_tax'] = $val['cost_tax'];
                        $add_mess['buyer_payment'] = $val['buyer_payment'];                                                
                        $pkg_objects[$split_key]['order_items'][] = $add_mess;
                        $is_pkg = 'true';
                    }
                }else{
                    $is_pkg = 'false';
                }
                //标识此货号为pkg
                $res['order_objects'][$k]['order_items'][$ke]['is_pkg'] = $is_pkg;
                $split_key++;
            }
        }

        //去除PKG货品
        foreach($res['order_objects'] as $objectkey=>$itemsdata){
            if ($itemsdata['obj_type'] == 'pkg'){
               $goods_objects[$objectkey] = $itemsdata;
            }else{
                $tmp_obj = array();
                foreach ($itemsdata as $obj_key=>$obj_val){
                   if ($obj_key != 'order_items'){
                       $tmp_obj[$obj_key] = $obj_val;
                   }
                }
                $goods_objects[$objectkey] = $tmp_obj;
                if($itemsdata['order_items']){
                    foreach ($itemsdata['order_items'] as $item_key=>$item_val){
                        if ($item_val['is_pkg'] == 'false'){
                           $goods_objects[$objectkey]['order_items'][] = $item_val;
                        }
                    }
                }
                if (empty($goods_objects[$objectkey]['order_items'])){
                    unset($goods_objects[$objectkey]);
                }
            }
        }
        $res['order_objects'] = array_merge($goods_objects, $pkg_objects);

        kernel::single('ome_service_c2c_taobao_order')->apportion_pkg_price($res);

        return $res;
    }
}
?>
