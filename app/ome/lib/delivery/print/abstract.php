<?php
/**
 * 单据打印抽象类
 *
 * @author kami
 * @package ome_delivery_print_abstract
 * @copyright www.shopex.cn 2014.08.05
 *
 */
class ome_delivery_print_abstract{

    protected function covertNullToString(&$items) {
        foreach ($items as $k => &$v) {
            if ($v === null) {
                $v = "";
            }
            elseif (is_array($v)) {
                $this->covertNullToString($v);
            }
            elseif (is_bool($v)) {
                $v = $v === false ? 'false' : 'true';
            }
            else {
                $v = str_replace(array('"','&quot;','&quot'),array('“','“','“'),strval($v));
            }
        }
        return $items;
    }

    
    /**
     * 打印新版本销售模式数据结构重组
     *
     * @return void
     * @author chenping<chenping@shopex.cn>
     **/
    protected function formatSaleStyleData($delivery)
    {
        #[拆单]发货单明细详情 ExBOY
        $deliItemDetailModel = app::get('ome')->model('delivery_items_detail');
        $deliItemDetailList = $deliItemDetailModel->getList('*',array('delivery_id'=>$delivery['delivery_id']));
        
        $delivery_detail    = array();
        foreach ($deliItemDetailList as $key => $val)
        {
            $order_id   = $val['order_id'];
            $obj_id     = $val['order_obj_id'];
            $item_id    = $val['order_item_id'];
            
            $delivery_detail[$order_id][$obj_id][$item_id]  = $val;
        }
        
        //仅定义当前发货单明细信息
        $format_delivery_items = array();
        foreach ($delivery['delivery_items'] as $key => $value) {
            $format_delivery_items[$value['item_id']] = $value;
        }

        //仅定义当前发货单相关订单obj信息
        $format_order_objects = array();
        foreach ($delivery['orders'] as $key => $order) {
            foreach($order['order_objects'] as $ook => $obj){
                
                #[拆单]计算PKG商品购买量 ExBOY
                if($obj['obj_type'] == 'pkg')
                {
                    $order_id       = $order['order_id'];
                    $obj_id         = $obj['obj_id'];
                    $old_quantity   = $obj['quantity'];
                    
                    foreach ($obj['order_items'] as $oik => $item)
                    {
                        if($item['nums'] && !empty($delivery_detail[$order_id][$obj_id][$item['item_id']]['number']))
                        {
                            $old_item_number    = $item['nums'];
                            $dly_quantity       = $delivery_detail[$order_id][$obj_id][$item['item_id']]['number'];
                            $dly_quantity       = intval($dly_quantity / ($old_item_number / $old_quantity));
                           
                            $obj['quantity']    = $dly_quantity ? $dly_quantity : $obj['quantity'];//发货单对应PKG购买量
                            break;
                        }
                    }
                }
                
                unset($obj['order_items']);
                $format_order_objects[$obj['obj_id']] = $obj;
            }
        }

        //仅定义当前发货单相关订单item信息
        $format_order_items = array();
        foreach ($delivery['orders'] as $key => $order) {
            foreach($order['order_objects'] as $ook => $obj){
                foreach ($obj['order_items'] as $oik => $item) {
                    
                    #计算item_product商品购买量 ExBOY
                    $order_id   = $order['order_id'];
                    $obj_id     = $obj['obj_id'];
                    $item_id    = $item['item_id'];
                    $dly_number = $delivery_detail[$order_id][$obj_id][$item_id]['number'];
                    
                    $item['number']         = $dly_number ? $dly_number : $item['nums'];
                    
                    #[拆单]重新计算打印价格 ExBOY
                    if($item['number'] != $item['nums'])
                    {
                        $item['sale_price']     = floatval($item['sale_price']) / $item['nums'] * $item['number'];//销售价
                        $item['pmt_price']      = floatval($item['pmt_price']) / $item['nums'] * $item['number'];//优惠价
                        $item['amount']         = floatval($item['amount']) / $item['nums'] * $item['number'];//商品小计
                    }
                    
                    unset($item['name'],$item['addon']);
                    $format_order_items[$item['item_id']] = $item;
                }
            }
        }

        $omepkgObj = &app::get('omepkg')->model('pkg_goods');
        //是否打印前端商品名称
        $deliCfgLib = kernel::single('ome_delivery_cfg');
        $is_print_front = (1 == $deliCfgLib->getValue('ome_delivery_is_printdelivery',$sku)) ? true : false;

        $deliItemDetailModel = app::get('ome')->model('delivery_items_detail');
        $deliItemDetailList = $deliItemDetailModel->getList('*',array('delivery_id'=>$delivery['delivery_id']));
        $data = array();
        foreach ($deliItemDetailList as $key => $value) {
            $order_object = $format_order_objects[$value['order_obj_id']];
            $order_item = $format_delivery_items[$value['delivery_item_id']];
            if (!$order_item) { continue; }

            $order_item = array_merge($order_item,$format_order_items[$value['order_item_id']]);

            if (isset($data[$order_object['obj_type']][$order_object['bn']])) {
                $obj_id_list = $data[$order_object['obj_type']][$order_object['bn']]['obj_id_list'];
                if (!in_array($order_object['obj_id'], $obj_id_list)) {
                    $obj_id_list[] = $order_object['obj_id'];
                    $data[$order_object['obj_type']][$order_object['bn']]['obj_id_list'] = $obj_id_list;
                    $data[$order_object['obj_type']][$order_object['bn']]['quantity'] += $order_object['quantity'];
                    $data[$order_object['obj_type']][$order_object['bn']]['amount'] += $order_object['amount'];
                    $data[$order_object['obj_type']][$order_object['bn']]['sale_price'] += $order_object['sale_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['pmt_price'] += $order_object['pmt_price'];
                }

                if (isset($data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']])) {
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['number'] += $order_item['number'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['pmt_price'] += $order_item['pmt_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['sale_price'] += $order_item['sale_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['amount'] += $order_item['amount'];
                } else {
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']] = $order_item;
                }
            } else {
                $order_object['obj_id_list'][] = $order_object['obj_id'];
                $data[$order_object['obj_type']][$order_object['bn']] = $order_object;
                $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']] = $order_item;

                if($order_object['obj_type']=='pkg'){
                    $pkg = $omepkgObj->getlist('name',array('pkg_bn'=>$order_object['bn']),0,1);
                    if (!$is_print_front) {
                        $data[$order_object['obj_type']][$order_object['bn']]['product_name'] = $pkg[0]['name'];
                        $data[$order_object['obj_type']][$order_object['bn']]['name'] = $pkg[0]['name'];
                    }else{
                        $data[$order_object['obj_type']][$order_object['bn']]['product_name'] = $order_object['name'];
                    }
                }
            }
        }      
        $delivery['delivery_items'] = $data;

        return $delivery;
    }
}