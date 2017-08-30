<?php
/**
 * 打印备货单相关方法
 *
 * @author kami
 * @package wms_delivery_print_stock
 * @copyright www.shopex.cn 2014.08.05
 *
 */
class wms_delivery_print_stock extends wms_delivery_print_abstract{

    public function format($print_data, $sku,&$_err){
        $goodsObj = app::get('ome')->model('goods');
        $branchObj = app::get('ome')->model('branch');
        $dlyObj = &app::get('wms')->model('delivery');

        $idstr = implode(',', $print_data['ids']);
        //统计商品数量
        $data = $dlyObj->countProduct($idstr);
        $arrPrintStockPrice = $dlyObj->getPrintStockPrice($print_data['ids']);

        $branch_list = array();
        $rows = array();
        $memo = array();
        $mark_text = array();
        $delivery_discount_price = 0;
        foreach($print_data['deliverys'] as $k => $dly) {
            if(!isset($branch_list[$dly['branch_id']])){
                $branch = $branchObj->dump(array('branch_id'=>$dly['branch_id']),'name');
                if($branch){
                    $branch_list[$dly['branch_id']] = $branch['name'];
                }
            }

            $daa = $dlyObj->getProductPosInfo($dly['delivery_id'],$dly['branch_id']);
            if (!$daa) continue;

            foreach ($daa as $i) {
                $goods = $goodsObj->dump($i['goods_id'],'bn,picurl');
                if ($i['spec_info']) {
                    $picurl = $i['picurl'];
                } else {
                    $picurl = $goods['picurl'];
                }
                $stockkey = strtoupper($i['bn']);
                $rows[$stockkey]['bn'] = $i['bn'];
                $rows[$stockkey]['name'] = $i['name'];
                $rows[$stockkey]['product_name'] = $i['product_name'];
                $rows[$stockkey]['product_weight'] = $i['weight'];
                $rows[$stockkey]['unit'] = $i['unit'];
                $rows[$stockkey]['store_position'] = $i['store_position'];
                $rows[$stockkey]['spec_info'] = $i['spec_info'];
                $rows[$stockkey]['picurl'] = $picurl;//图片地址
                $rows[$stockkey]['num'] += $i['number'];
                $rows[$stockkey]['boxs'][] = '<' . $print_data['identInfo']['ids'][$dly['delivery_id']] . '(' . $i['number'] . ')>';
                $rows[$stockkey]['box'] = implode(' ', $rows[$stockkey]['boxs']);
                $rows[$stockkey]['box_price'] = isset($arrPrintStockPrice[$stockkey]) ? $arrPrintStockPrice[$stockkey] : 0;
                $rows[$stockkey]['barcode'] = $i['barcode'];
                #商品编号,仓库名称
                
                $rows[$stockkey]['goods_bn'] = $goods['bn'];
            }

            foreach ($dly['orders'] as $odk => $order){
                $delivery_discount_price += $order['discount'] + $order['pmt_order'] + $order['pmt_goods'];
                if(!empty($order['custom_mark'])){
                    $memo =array_merge($memo,unserialize($order['custom_mark']));
                }

                if(!empty($order['mark_text'])){
                    $mark_text =array_merge($mark_text,unserialize($order['mark_text']));
                }
            }
        }

        // 对usort进行扩展，对多位数组进行值的排序
        function cmp($a, $b) {
            return strcmp($a["store_position"], $b["store_position"]);
        }
        usort($rows, "cmp");

        //商品名称和规格取前台,是合并发货单,取第一个订单的货品名称
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $is_print_front = (1 == $deliCfgLib->getValue('wms_delivery_is_printstock',$sku)) ? true : false ;

        if ($print_data['ids'] && $is_print_front) {
            $arrPrintProductName = $dlyObj->getPrintProductName($print_data['ids']);
            if (!empty($arrPrintProductName)) {
                foreach ($rows as $k => $v) {
                    $rows[$k]['product_name'] = $arrPrintProductName[$v['bn']]['name'];
                    $rows[$k]['name'] = $arrPrintProductName[$v['bn']]['name'];
                    $rows[$k]['addon'] = $arrPrintProductName[$v['bn']]['addon'];
                    $rows[$k]['spec_info'] = $arrPrintProductName[$v['bn']]['addon'];
                }
            }
        }

        $delivery_total_nums = 0;
        $delivery_total_price = 0;
        foreach ($rows as $k => $v) {
            $delivery_total_nums += $v['num'];
            $delivery_total_price += $v['box_price'];
        }

        return array(
            'rows'=>$rows,
            'delivery_total_nums'=>$delivery_total_nums,
            'delivery_total_price'=>$delivery_total_price - $delivery_discount_price,
            'delivery_discount_price'=>$delivery_discount_price,
            'picking_list_price' => $delivery_total_price,
            'branch_list' => $branch_list,
            'vid' => $idstr,
            'data' => $data,
            'memo' => $memo,
            'mark_text' => $mark_text,
        );
    }

    public function arrayToJson($items, $idents,$pagedata) {
        $nbsp = "　";
        $this->covertNullToString($items);
        $delivery_total_nums = 0;
        $delivery_total_price = 0;
        foreach ($items as $k => $v) {
            if (isset($v['boxs'])) {
                unset($items[$k]['boxs']);
            }
        }
        $countDeliveryMsg['num_total'] = "数量总计：" . $pagedata['delivery_total_nums'];
        $countDeliveryMsg['num_money_total'] = "数量总计：" . $pagedata['delivery_total_nums'] . $nbsp . $nbsp .
                                               "备货金额总计：" . $pagedata['picking_list_price'] . $nbsp . $nbsp .
                                               "优惠金额总计：" . $pagedata['delivery_discount_price'];
        $countDeliveryMsg['empty'] = '';

        $data = array(
            0 => array(
                'stock_items' => $items,
                'date_y' => date('Y'),
                'date_m' => date('m'),
                'date_d' => date('d'),
                'date_ymd' => date('Ymd'),
                'batch_number' => isset($idents[0]) ? $idents[0] : '',
                'countDeliveryMsg' => $countDeliveryMsg,
            ),
        );
        return json_encode($data);
    }
}