<?php

class ome_sales_price{
    private function cmp_by_sale_price($a,$b){
        if(0 == bccomp((float) $a['sale_price'],(float) $b['sale_price'],3) ){
            return 0;
        }

        return (bccomp((float) $a['sale_price'],(float) $b['sale_price'],3) == -1) ? -1 : 1;
    }

    // 对订单明细重新排序
    private function sort_order($order){
        foreach ($order['order_objects'] as &$object){
            uasort($object['order_items'],array($this,'cmp_by_sale_price'));
        }

        uasort($order['order_objects'],array($this,'cmp_by_sale_price'));

        return $order;
    }

    public function calculate($order_original_data,&$sales_data){
        $order_original_data = $this->sort_order($order_original_data);

        //优惠金额
        $all_discount = $order_original_data['pmt_order'];
        if($order_original_data['discount'] < 0){
            $all_discount = bcadd($all_discount,abs($order_original_data['discount']),2);
        }

        $tmp_goods = array();
        $all_goods_sale_price = 0.00;//所有商品销售价格:去商品object上优惠后的所有商品销售价合计
        foreach ($order_original_data['order_objects'] as $key => $object){
            if($object['obj_type'] == 'pkg' || $object['obj_type'] == 'giftpackage'){
                $tmp_goods[$key]['product'][$object['bn']] = array(
                	'price' => $object['price'],
                    'nums' => $object['quantity'],
                    'amount' => $object['amount'],
                    'product_pmt_price' => $object['pmt_price'],
                    'sale_price' => $object['sale_price'],
                    'obj_id' => $object['obj_id'],
                );

                $tmp_goods[$key]['obj_id'] = $object['obj_id'];
                $tmp_goods[$key]['bn'] = $object['bn'];
                $tmp_goods[$key]['sale_price'] = $object['sale_price'];
                $tmp_goods[$key]['goods_pmt_price'] = 0.00;
                $tmp_goods[$key]['obj_type'] = 'package';
                $tmp_goods[$key]['price_worth'] = bcsub($object['sale_price'],$tmp_goods[$key]['goods_pmt_price'],2);
            }else{
                $items = $object['order_items'];
                foreach ($items as $k => $item){
                    $tmp_goods[$key]['product'][$item['bn']] = array(
                    	'price' => $item['price'],
                        'nums' => $item['nums'],
                        'amount' => $item['amount'],
                        'product_pmt_price' => $item['pmt_price'],
                        'sale_price' => $item['sale_price'],
                    	'obj_id' => $object['obj_id'],
                    );

                    $tmp_goods[$key]['obj_id'] = $object['obj_id'];
                    $tmp_goods[$key]['sale_price'] = bcadd($tmp_goods[$key]['sale_price'],$item['sale_price'],2);
                    $tmp_goods[$key]['goods_pmt_price'] = $object['pmt_price'];
                    $tmp_goods[$key]['obj_type'] = 'normal';
                }
                $tmp_goods[$key]['price_worth'] = bcsub($tmp_goods[$key]['sale_price'],$tmp_goods[$key]['goods_pmt_price'],2);
            }
            $all_goods_sale_price = bcadd($all_goods_sale_price,$tmp_goods[$key]['price_worth'],2);
        }

        $sale_product = array();

        $goods_count = 0;
        $goods_count = count($tmp_goods);

        $has_apportion_pmt = 0.00;
        $loop = 1;
        foreach($tmp_goods as $key => $goods){
            if($tmp_goods[$key]['price_worth'] > 0){
                if($goods_count == $loop){
                    $tmp_goods[$key]['apportion_pmt'] = bcsub($all_discount,$has_apportion_pmt,2);
                }else{
                    $tmp_goods[$key]['apportion_pmt'] = bcmul($all_discount/$all_goods_sale_price,$tmp_goods[$key]['price_worth'],2);
                    $has_apportion_pmt = bcadd($has_apportion_pmt,$tmp_goods[$key]['apportion_pmt'],2);
                }
            }else{
                $tmp_goods[$key]['apportion_pmt'] = 0.00;
            }

            $tmp_products = $goods['product'];
            if($goods['obj_type'] == 'package'){
                    $sale_product[$goods['obj_id']][$tmp_goods[$key]['bn']]['apportion_pmt'] = $tmp_goods[$key]['apportion_pmt'];
                    $sale_product[$goods['obj_id']][$tmp_goods[$key]['bn']]['sales_amount'] = bcsub($tmp_goods[$key]['sale_price'],$tmp_goods[$key]['apportion_pmt'],2);
            }elseif($goods['obj_type'] == 'normal'){
                $now_goods_all_discount = bcadd($tmp_goods[$key]['apportion_pmt'],$goods['goods_pmt_price'],2);
                $products_count = count($tmp_products);

                $has_apportion_goods_pmt = 0.00;
                $loop2 = 1;
                foreach($tmp_products as $bn => $product){
                    //判断商品是否有价格贡献度,如赠品是没有的
                    if($product['sale_price'] > 0){
                        if($products_count == 1){
                            $sale_product[$goods['obj_id']][$bn]['apportion_pmt'] = $now_goods_all_discount;
                            $sale_product[$goods['obj_id']][$bn]['sales_amount'] = bcsub($product['sale_price'],$sale_product[$goods['obj_id']][$bn]['apportion_pmt'],2);
                        }else{
                            if($products_count == $loop2){
                                $sale_product[$goods['obj_id']][$bn]['apportion_pmt'] = bcsub($now_goods_all_discount,$has_apportion_goods_pmt,2);
                                $sale_product[$goods['obj_id']][$bn]['sales_amount'] = bcsub($product['sale_price'],$sale_product[$goods['obj_id']][$bn]['apportion_pmt'],2);
                            }else{
                                $sale_product[$goods['obj_id']][$bn]['apportion_pmt'] = bcmul($now_goods_all_discount/$goods['sale_price'],$product['sale_price'],2);
                                $sale_product[$goods['obj_id']][$bn]['sales_amount'] = bcsub($product['sale_price'],$sale_product[$goods['obj_id']][$bn]['apportion_pmt'],2);
                                $has_apportion_goods_pmt = bcadd($has_apportion_goods_pmt,$sale_product[$goods['obj_id']][$bn]['apportion_pmt'],2);
                            }

                        }
                    }else{
                        $sale_product[$goods['obj_id']][$bn]['apportion_pmt'] = 0.00;
                        $sale_product[$goods['obj_id']][$bn]['sales_amount'] = 0.00;
                    }
                    $loop2++;
                }
            }
            $loop++;
        }

        $loop3 = 1;
        $sale_items_count = count($sales_data['sales_items']);
        foreach($sales_data['sales_items'] as $k => $sale_item){
            $sales_data['sales_items'][$k]['apportion_pmt'] = $sale_product[$sale_item['obj_id']][$sale_item['bn']]['apportion_pmt'] ? $sale_product[$sale_item['obj_id']][$sale_item['bn']]['apportion_pmt'] : 0.00;
            $sales_data['sales_items'][$k]['sales_amount'] = $sale_product[$sale_item['obj_id']][$sale_item['bn']]['sales_amount'] ? $sale_product[$sale_item['obj_id']][$sale_item['bn']]['sales_amount'] : 0.00;

            if($sale_items_count == $loop3){
                unset($sales_data['sales_items'][$k]['obj_id']);
            }
            $loop3++;
        }

        if($this->_check($order_original_data, $sales_data)){

        }else{
            //将异常订单塞队列里
            if(defined('ERROR_HTTPSQS_HOST') && defined('ERROR_HTTPSQS_PORT') && defined('ERROR_HTTPSQS_CHARSET') && defined('ERROR_PENDING_QUEUE')){
                $tmp = array(
                    'domain' => $_SERVER['SERVER_NAME'],
                    'order_bn' => $order_original_data['order_bn'],
                );
                $httpsqsLib = kernel::single('taoexlib_httpsqs');
                $httpsqsLib->put(ERROR_HTTPSQS_HOST, ERROR_HTTPSQS_PORT, ERROR_HTTPSQS_CHARSET, ERROR_PENDING_QUEUE, json_encode($tmp));
            }
            $this->_worldPeaceMode($sales_data);
        }

        return true;
    }

    private function _check($order_original_data, $sales_data){
        $all_product_price = 0.00;
        foreach($sales_data['sales_items'] as $k => $sale_item){
            $all_product_price += $sale_item['sales_amount'];
        }

        $sum = $all_product_price+$order_original_data['shipping']['cost_shipping']+$order_original_data['shipping']['cost_protect']+$order_original_data['cost_tax']+$order_original_data['payinfo']['cost_payment'];

        if($order_original_data['discount'] > 0){
            $sum += $order_original_data['discount'];
        }

        if(bccomp($order_original_data['total_amount'], $sum, 3) == 0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 验证不通过的数据走万金油模式
     */
    private function _worldPeaceMode(&$sales_data){
        //销售明细不同货品的数量合计
        $sale_items_count = count($sales_data['sales_items']);

        $all_sale_price = 0.00;
        foreach($sales_data['sales_items'] as $k => $sale_item){
            $all_sale_price += $sale_item['price']*$sale_item['nums'];
        }

        $old_discount = $sales_data['discount'];

        if(bccomp($sales_data['total_amount'], $all_sale_price, 3) > 0){
            $sales_data['discount'] = $sales_data['discount']-($sales_data['total_amount']-$all_sale_price);
            $sales_data['total_amount'] = $all_sale_price;
        }elseif(bccomp($sales_data['total_amount'], $all_sale_price, 3) < 0){
            $sales_data['discount'] = $sales_data['discount']+($all_sale_price-$sales_data['total_amount']);
            $sales_data['total_amount'] = $all_sale_price;
        }

        if($old_discount > 0 && $sales_data['discount'] > 0 && $old_discount > $sales_data['discount']){
            $sales_data['additional_costs'] = $sales_data['additional_costs'] - ($old_discount - $sales_data['discount']);
        }elseif($old_discount > 0 && $sales_data['discount'] > 0 && $old_discount < $sales_data['discount']){
            $sales_data['additional_costs'] = $sales_data['additional_costs'] + ($sales_data['discount'] - $old_discount);
        }elseif($old_discount > 0 && $sales_data['discount'] <= 0){
            $sales_data['additional_costs'] = $sales_data['additional_costs'] - $old_discount;
        }elseif($old_discount < 0 && $sales_data['discount'] > 0){
            $sales_data['additional_costs'] = $sales_data['additional_costs'] + $sales_data['discount'];
        }

        //货品实际销售价 = 支付总金额-运费-其他附加费
        $product_sales_amount = $sales_data['sale_amount']-$sales_data['cost_freight']-$sales_data['additional_costs'];

        //可分摊优惠金额
        $can_apportion_pmt_amount = $all_sale_price - $product_sales_amount;

        $loop = 1;
        $has_apportion_pmt_price = 0.00;
        foreach($sales_data['sales_items'] as $k => $sale_item){
            if(bccomp($sale_item['price'], 0.000, 3) == 0){
                $sales_data['sales_items'][$k]['sale_price'] = 0.00;
                $sales_data['sales_items'][$k]['pmt_price'] = 0.00;
                $sales_data['sales_items'][$k]['apportion_pmt'] = 0.00;
                $sales_data['sales_items'][$k]['sales_amount'] = 0.00;
            }elseif($sale_items_count == $loop){
                $sales_data['sales_items'][$k]['sale_price'] = $sale_item['price']*$sale_item['nums'];
                $sales_data['sales_items'][$k]['pmt_price'] = 0.00;
                $sales_data['sales_items'][$k]['apportion_pmt'] = $can_apportion_pmt_amount - $has_apportion_pmt_price;
                $sales_data['sales_items'][$k]['sales_amount'] = $sales_data['sales_items'][$k]['sale_price'] - $sales_data['sales_items'][$k]['apportion_pmt'];
            }else{
                $sales_data['sales_items'][$k]['sale_price'] = $sale_item['price']*$sale_item['nums'];
                $sales_data['sales_items'][$k]['pmt_price'] = 0.00;
                $sales_data['sales_items'][$k]['apportion_pmt'] = round($sale_item['price']*$sale_item['nums']/$all_sale_price*$can_apportion_pmt_amount,2);
                $sales_data['sales_items'][$k]['sales_amount'] = $sales_data['sales_items'][$k]['sale_price'] - $sales_data['sales_items'][$k]['apportion_pmt'];
                $has_apportion_pmt_price += $sales_data['sales_items'][$k]['apportion_pmt'];
            }
            $loop++;
        }
    }

}