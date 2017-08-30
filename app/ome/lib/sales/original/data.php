<?php

class ome_sales_original_data{

    /**
     * 初始化销售单所需的原始数据
     */
    public function init($order_id){
        if(!$order_id){
            return false;
        }

        $ordersObj = &app::get('ome')->model('orders');
        $order_info = $ordersObj->dump($order_id,'*',array('order_objects'=>array('*',array('order_items'=>array('*')))));

        //数据做兼容修正
        foreach ($order_info['order_objects'] as $key => $obj){

            $is_obj_delete = false;

            $tmp_obj_price = ($obj['price'] > 0) ? $obj['price'] : (($obj['amount']/$obj['quantity'] > 0) ? $obj['amount']/$obj['quantity'] : 0.00);
            $order_info['order_objects'][$key]['price'] = $tmp_obj_price;

            $tmp_obj_amount = ($obj['amount'] > 0) ? $obj['amount'] : (($tmp_obj_price*$obj['quantity'] > 0) ? $tmp_obj_price*$obj['quantity'] : 0.00);
            $order_info['order_objects'][$key]['amount'] = $tmp_obj_amount;

            $items = $obj['order_items'];
            $tmp_item_pmt_price_all = 0.00;
            $items_count =count($items);
            $item_delete_flag = 0;
            foreach($items as $k => $item){
                //如果存在已删除货品，该商品对象直接排除
                $is_item_delete = false;
                if($item['delete'] == 'true'){
                    $is_item_delete = true;
                    unset($order_info['order_objects'][$key]['order_items'][$k]);
                    $item_delete_flag++;
                }
                if (!$is_item_delete){
                    $tmp_item_price = ($item['price'] > 0) ? $item['price'] : (($item['amount']/$item['quantity'] > 0) ? $item['amount']/$item['quantity'] : 0.00);
                    $order_info['order_objects'][$key]['order_items'][$k]['price'] = $tmp_item_price;

                    $tmp_item_amount = ($item['amount'] > 0) ? $item['amount'] : (($tmp_item_price*$item['quantity'] > 0) ? $tmp_item_price*$item['quantity'] : 0.00);
                    $order_info['order_objects'][$key]['order_items'][$k]['amount'] = $tmp_item_amount;

                    $tmp_item_pmt_price = ($item['pmt_price'] > 0) ? $item['pmt_price'] : 0.00;
                    $order_info['order_objects'][$key]['order_items'][$k]['pmt_price'] = $tmp_item_pmt_price;

                    $tmp_item_cost = ($item['cost'] > 0) ? $item['cost'] : 0.00;
                    $order_info['order_objects'][$key]['order_items'][$k]['cost'] = $tmp_item_cost;

                    $tmp_sale_price = ($item['sale_price'] > 0) ? $item['sale_price'] : ((($tmp_item_price*$item['quantity']-$tmp_item_pmt_price) > 0) ? ($tmp_item_price*$item['quantity']-$tmp_item_pmt_price) : 0.00);
                    $order_info['order_objects'][$key]['order_items'][$k]['sale_price'] = $tmp_sale_price;

                    $tmp_item_pmt_price_all += $tmp_item_pmt_price;
                }
            }
            
            if($items_count==$item_delete_flag){//明细数量和删除数量一致，删除OBJ
                unset($order_info['order_objects'][$key]);
                $is_obj_delete = true;
            }

            if(!$is_obj_delete){
                $tmp_obj_pmt_price = ($obj['pmt_price'] > 0) ? $obj['pmt_price'] : 0.00;
                $order_info['order_objects'][$key]['pmt_price'] = $tmp_obj_pmt_price;

                $tmp_obj_sale_price = ($obj['sale_price'] > 0) ? $obj['sale_price'] : ((($tmp_obj_amount-$tmp_obj_pmt_price-$tmp_item_pmt_price_all) > 0) ? ($tmp_obj_amount-$tmp_obj_pmt_price-$tmp_item_pmt_price_all) : 0.000);
                $order_info['order_objects'][$key]['sale_price'] = $tmp_obj_sale_price;

            }
        }


        //校验最终数据
        if($this->_check($order_info,$flag)){

        }else{
            //将异常原始订单塞队列里
            if(defined('ERROR_HTTPSQS_HOST') && defined('ERROR_HTTPSQS_PORT') && defined('ERROR_HTTPSQS_CHARSET') && defined('ERROR_PENDING_QUEUE')){
                $tmp = array(
                    'domain' => $_SERVER['SERVER_NAME'],
                    'order_bn' => $order_info['order_bn'].$flag,
                );
                $httpsqsLib = kernel::single('taoexlib_httpsqs');
                $httpsqsLib->put(ERROR_HTTPSQS_HOST, ERROR_HTTPSQS_PORT, ERROR_HTTPSQS_CHARSET, ERROR_PENDING_QUEUE, json_encode($tmp));
            }
        }
        return $order_info;
    }

    /**
     * 检查原始数据是否有异常
     */
    private function _check(&$data,&$flag) {
        $sum = $data['cost_item']+$data['shipping']['cost_shipping']+$data['shipping']['cost_protect']+$data['cost_tax']+$data['payinfo']['cost_payment'];

        if($data['discount'] > 0){
            $sum += $data['discount'];
        }else{
            $sum -= abs($data['discount']);
        }

        $sum = $sum - $data['pmt_goods'] - $data['pmt_order'];

        if(bccomp($data['total_amount'], $sum, 3) == 0){
            return true;
        }elseif(bccomp($data['total_amount'], $sum, 3) > 0){
            $data['discount'] = $data['discount']+($data['total_amount']-$sum);
            $flag = '_q';
            return false;
        }elseif(bccomp($data['total_amount'], $sum, 3) < 0){
            $data['discount'] = $data['discount']+($data['total_amount']-$sum);
            $flag = '_d';
            return false;
        }
    }

}