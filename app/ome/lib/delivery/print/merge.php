<?php
/**
 * 联合打印单据相关方法
 *
 * @author kami
 * @package ome_delivery_print_stock
 * @copyright www.shopex.cn 2014.08.05
 *
 */
class ome_delivery_print_merge extends ome_delivery_print_abstract{

    public function format($print_data, $sku,&$_err){
        //注意按分组来
        $dlyObj = app::get('ome')->model('delivery');
        $orderObj       = app::get('ome')->model('orders');
        $pObj           = app::get('ome')->model('products');
        $oiObj          = app::get('ome')->model('order_items');
        $odObj          = app::get('ome')->model('delivery_order');
        $diObj          = app::get('ome')->model('delivery_items');
        $didObj         = app::get('ome')->model('delivery_items_detail');
        $orderObjectObj = app::get('ome')->model('order_objects');
        $memberModel    = app::get('ome')->model('members');
        $shopModel      = app::get('ome')->model('shop');
        $omepkgObj = &app::get('omepkg')->model('pkg_goods');
        $printDlyLib = kernel::single('ome_delivery_print_delivery');
        
        //备注显示方式
        $markShowMethod = &app::get('ome')->getConf('ome.order.mark');
        #发货配置
        $deliveryCfgLib = kernel::single('ome_delivery_cfg');
        # 获取手推车格子数
        $trolley_count = $deliveryCfgLib->getValue('ome_eachgroup_print_count',$sku);

        $allItems = array(); 
        $items = $stock = $delivery = array();
        $delivery_total_except_pkg = array();
        if ($print_data['ids']) {
            $print_queue = app::get('ome')->model('print_queue');
            $delivery_cfg = app::get('ome')->getConf('ome.delivery.status.cfg');
            #联合打印分组数
            $print_count = $delivery_cfg['set']['ome_eachgroup_print_count'];
            $i = 1; $k = 0; $delivery_total_nums = $delivery_total_price = 0;
            $km_shopinfo = array();
            $km_member = array();
            foreach ($print_data['deliverys'] as $dly) {
                # 发货单详情
                $data = $dly;

                # 发货单明细
                $allItems[$data['delivery_id']] = $data;

                # 货品货位明细信息
                $productInfo = $dlyObj->getProductPosInfo($dly['delivery_id'],$dly['branch_id']);
                if(!$productInfo) continue;

                # ==备货==
                foreach ($productInfo as $product) {
                    //$ident_dly =  $print_queue->findIdendlyById($product['delivery_id']);
                    $old_ident_dly =  $print_data['identInfo']['ids'][$dly['delivery_id']];
                    $_num = $print_data['identInfo']['ids'][$dly['delivery_id']] % $print_count;
                    if($_num == 0){
                        $ident_dly['ident_dly'] = $print_count;
                    }elseif($_num > 0){
                        $ident_dly['ident_dly'] = $_num;
                    }
                    $bn = $product['bn'];

                    $stock[$bn]['bn']             = $bn;
                    $stock[$bn]['name']           = $product['name'];         // 后端名称
                    $stock[$bn]['product_name']   = $product['product_name']; // 前端名称
                    $stock[$bn]['store_position'] = $product['store_position'];
                    $stock[$bn]['spec_info']      = $product['spec_info'];
                    $stock[$bn]['num']            += $product['number'];
                    $stock[$bn]['box']            .= '<' . $ident_dly['ident_dly']. '(' . $product['number'] . ')'.'-'.$old_ident_dly.'>,';   // 格子编号
                    $stock[$bn]['box_price']      = 0;
                    $stock[$bn]['barcode']        = $product['barcode'];
                    $stock[$bn]['product_weight']        = $product['weight'];
                    $stock[$bn]['unit']        = $product['unit'];


                    $delivery_total_nums  += $product['number'];
                }

                $items[$k]['delivery_total_nums'] = $delivery_total_nums;

                # ==发货==
                # 订单优惠
                $pmt_orders = $printDlyLib->getPmtPrice($dly['orders']);
                # 销售价格
                $sale_orders = $printDlyLib->getSalePrice($dly['orders']);
                # 店铺名称
                if(!isset($km_shopinfo[$data['shop_id']])){
                    $data['shop_name'] = $shopModel->select()->columns('name')->where('shop_id=?',$data['shop_id'])->instance()->fetch_one();
                    $km_shopinfo[$data['shop_id']]['shop_name'] = $data['shop_name'];
                }else{
                    $data['shop_name'] = $km_shopinfo[$data['shop_id']]['shop_name'];
                }

                # 发货明细
                foreach ($data['delivery_items'] as &$deliItem) {
                    $bn = $deliItem['bn'];
                    $deliItem['store_position'] = $stock[$bn]['store_position'];
                    $deliItem['addon']          = $stock[$bn]['spec_info'];
                    $deliItem['pmt_price']      = $pmt_orders[$bn]['pmt_price'];
                    $deliItem['sale_price']     = ($sale_orders[$bn]*$deliItem['number']);
                    $deliItem['name']           = $stock[$bn]['name'];
                    
                    $deliItem['product_weight']           = $stock[$bn]['product_weight'];
                    $deliItem['unit']           = $stock[$bn]['unit'];
                    
                    $data['delivery_total_nums'] += $deliItem['number'];
                    
                }
                //print_r($data);
                $order_bn = array(); $shop_type = array(); $order_source = array();
                foreach ($data['orders'] as $order) {

                    # 发货单订单总额
                    $data['order_total_amount'] += $order['total_amount'];

                    # 订单折扣
                    $items[$k]['delivery_discount_price'] += $order['discount'];

                    # 买家留言
                    if ($order['custom_mark']) {
                        $mark = unserialize($order['custom_mark']);
                        if (is_array($mark) || !empty($mark)){
                            if($markShowMethod == 'all'){
                                foreach ($mark as $im) {
                                    $data['_mark'][$order['order_bn']][] = $im;
                                }
                            }else{
                                $data['_mark'][$order['order_bn']][] = array_pop($mark);
                            }
                        }
                    }
                    # 交易备注
                    if ($order['mark_text']) {
                        $mark = unserialize($order['mark_text']);
                        if (is_array($mark) || !empty($mark)){
                            if($markShowMethod == 'all'){
                                foreach ($mark as $im) {
                                    $data['_mark_text'][$order['order_bn']][] = $im;
                                }
                            }else{
                                $data['_mark_text'][$order['order_bn']][] = array_pop($mark);
                            }
                        }
                    }

                    //发票信息
                    if($order['tax_no'] || $order['tax_company']){
                        $data['_tax_info'][] = array(
                            'order_bn'=>$order['order_bn'],
                            'tax_no'=>$order['tax_no'],
                            'tax_title'=>$order['tax_company'],
                        );
                    }

                    # 订单号
                    $order_bn[] = $order['order_bn'];

                    if(!in_array($order['shop_type'],$shop_type)){
                        $shop_type[] = $order['shop_type'];
                    }

                    if(!in_array($order['order_source'],$order_source)){
                         $order_source[] = $order['order_source'];
                    }

                    $data['order_cost_item'] += $order['cost_item'];
                    $data['pmt_order_total'] += $order['pmt_order'];// 订单总优惠
                    # 不是合并发货单取订单的前端物流费用
                    
                    // 运费累加
                    $data['front_cost_freight'] += $order['cost_freight'];
                }
                
                # 会员信息
                if(!isset($km_member[$data['member_id']])){
                    $member = $memberModel->select()->columns('uname,name,mobile,tel')->where('member_id=?',$data['member_id'])
                                ->instance()->fetch_row();
                    $data['member_name'] = $member['uname'];
                    if ($member['tel'] && $member['mobile']) {
                        $data['member_tel'] = $member['tel'].'/'.$member['mobile'];
                    }else{
                        $data['member_tel'] = $member['tel'] ? $member['tel'] : $member['mobile'];
                    }
                    $km_member[$data['member_id']] = array('uname'=>$data['member_name'],'tel'=>$data['member_tel']);
                }else{
                    $data['member_name'] = $km_member[$data['member_id']]['uname'];
                    $data['member_tel'] = $km_member[$data['member_id']]['tel'];
                }

                $data['order_bn'] = implode(" , ", $order_bn);

                # 有两级价格的发货单不显示价格
                $show_delivery_price = true;
                # all:代表一张发货关联多个前端店铺
                $data['shop_type'] = 'all'; $data['order_source'] = 'all';
                if(count($shop_type) == 1 && count($order_source) == 1){
                    $data['shop_type'] = $shop_type[0];
                    $data['order_source'] = $order_source[0];
                    if($shop_type[0] == 'shopex_b2b'){
                        $arr = array('fxjl','b2c','taofenxiao');
                        if(in_array($order_source[0], $arr)){
                             $show_delivery_price = false;
                        }
                    }
                }
                $show_delivery_price = true;#有两级价格的，显示价格
                $data['show_delivery_price'] = $show_delivery_price;
                if (!$data['show_delivery_price']) {
                     foreach ($data['delivery_items'] as $kk => $vv) {
                        $data['delivery_items'][$kk]['price'] = '-';
                     }
                    $data['order_total_amount'] = '-';
                    $data['order_cost_item'] = '-';
                }

                # 除多余的三级区域
                $reg = preg_quote(trim($data['consignee']['province']));
                if (!empty($data['consignee']['city'])) {
                    $reg .= '.*' . preg_quote(trim($data['consignee']['city']));
                }
                if (!empty($data['consignee']['district'])) {
                    $reg .= '.*' . preg_quote(trim($data['consignee']['district']));
                }
                $data['consignee']['addr'] = preg_replace('/' . $reg . '/is', '', $data['consignee']['addr']);

                # 京东订单添加打印标示
                if($data['shop_type']=='360buy'){
                    $logo_url = kernel::base_url(1)."/app/ome/statics/360buylogo.png";
                    $data['shop_name']      = '<img src="'.$logo_url.'" width="129" height="25" alt="京东商城">&nbsp;';
                    $data['shop_logo_url']  = $logo_url;
                    $data['shop_logo_html'] = '<img src="'.$logo_url.'" width="129" height="25" alt="京东商城">';
                }
                $data['box'] = '<'.$i.'>';

                $items[$k]['delivery'][$dly['delivery_id']] = $data;
                $items[$k]['stock'] = $stock;
                if ($idents['items']) {
                    $items[$k]['idents_range'][] = $idents['items'][$dly['delivery_id']];
                }

                $i++;
                if ($i>$trolley_count) {
                    $i = 1; $k++;$delivery_total_nums = $delivery_total_price = 0; $delivery_total_except_pkg = $stock = array();
                }
            }
        }

        //按货位进行排序
        function cmp($a, $b) {
            return strcmp($a["store_position"], $b["store_position"]);
        }

        foreach ($items as $k => $item) {
           usort($items[$k]['stock'], "cmp");
           foreach($items[$k]['stock'] as $kk=>$vv){
                 $items[$k]['stock'][$vv['bn']] = $vv;
                 unset($items[$k]['stock'][$kk]);
           }
        }

        # 是否使用前端名称
        if (1 == $deliveryCfgLib->getValue('ome_delivery_merge_print',$sku)) {
            $is_front_pname = true;
        }

        foreach ($items as $key=>$value) {
            $delivery_total_except_pkg = 0;
            # 备货单 明细小计
            $delivery_ids = array_keys($value['delivery']);
            $arrPrintStockPrice = $dlyObj->getPrintStockPrice($delivery_ids);
            foreach ($value['stock'] as $bn=>$v) {
                $items[$key]['stock'][$bn]['box_price'] = isset($arrPrintStockPrice[$bn]) ? $arrPrintStockPrice[$bn] : 0;
                $delivery_total_except_pkg += $items[$key]['stock'][$bn]['box_price'];
            }
            $items[$key]['delivery_total_price'] = $delivery_total_except_pkg;

            # 批次号范围
            if ($value['idents_range']) {
                $items[$key]['idents_range'] = array_shift($value['idents_range']);
                if ($pop = array_pop($value['idents_range'])) {
                    $items[$key]['idents_range'] .= ' ~ '.$pop;
                }
            }
        }

        //to do? 联合打印不支持新版本打印方式，模板目前都没有，下面老代码有问题先注释掉,打印版本强制设置为老版本
        $print_version = '0';

        #获取当印模板版本
        //$print_version = $deliveryCfgLib->getprintversion();
        #获取当前打印模式
        //$print_style = $deliveryCfgLib->getprintstyle();

         #打印订单将捆绑商品分类展示
         /*
         if($print_version=='1'){
            //获取pkg后台名称
            foreach($items as $k=>$v){
                foreach($v['delivery'] as $k1=>$v1){
                    if ($print_style == '0') {
                        $items[$k]['delivery'][$k1] = $this->format_print_delivery($v1);
                    } else {
                        
                        foreach($v1['delivery_items'] as $key=>$val){
                            if(!isset($items[$k]['delivery'][$k1]['delivery_items'][$val['item_type']][$val['order_obj_id']])){
                                $order_objects = $orderObjectObj->dump($val['order_obj_id'],'bn,name,price,amount,quantity,pmt_price,sale_price,obj_type');
                                $obj_type = $order_objects['obj_type'];
                                if($obj_type=='pkg'){
                                    //取出所有OBJ
                                    $pkg = $omepkgObj->getlist('name',array('pkg_bn'=>$order_objects['bn']),0,1);
                                    
                                    if (!$is_print_front) {
                                        $order_objects['product_name'] = $pkg[0]['name'];
                                    }else{
                                        $order_objects['product_name'] = $order_objects['name'];
                                    }
                                    
                                    $order_objects_sum = &app::get('ome')->model('delivery_items_detail')->getOrderobjQuantity($val['item_id'],$val['delivery_id'],$order_objects['bn']);
                                    $order_objects['quantity'] = $order_objects_sum['quantity'];
                                    
                                }
                                $items[$k]['delivery'][$k1]['delivery_items'][$val['item_type']][$val['order_obj_id']] = $order_objects;
                            }
                            $items[$k]['delivery'][$k1]['delivery_items'][$val['item_type']][$val['order_obj_id']]['order_items'][$key]=$val;

                            unset($items[$k]['delivery'][$k1]['delivery_items'][$key]);
                        }
                    }
                }
            }
       }
        */

       return array(
           'items' => $items,
           'allItems' => $allItems,
           'print_style' => $print_style,
           'print_version' => $print_version,
           'is_front_pname' => $is_front_pname,
       );
    }
}