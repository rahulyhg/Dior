<?php
/**
 * 打印发货单相关方法
 *
 * @author kami
 * @package wms_delivery_print_delivery
 * @copyright www.shopex.cn 2014.08.05
 *
 */
class wms_delivery_print_delivery extends wms_delivery_print_abstract{

    //增加发货单打印需要的扩展信息
    public function appendExtData(&$deliverys){
        foreach($deliverys as $dk =>$dly){
            foreach($dly['orders'] as $ok=> $order){
                foreach($order['order_objects'] as $ook=>$ordobj){
                    $now_obj_items_num = 0;
                    foreach($ordobj['order_items'] as $oik=>$orditem){
                        if($orditem['delete'] == 'false'){
                            $now_obj_items_num += $orditem['nums'];
                        }
                    }
                    $deliverys[$dk]['orders'][$ok]['order_objects'][$ook]['obj_item_num_sum'] = $now_obj_items_num;
                }
            }
        }
    }

    //获取平摊的优惠金额
    public function getPmtPrice($orders){
        $pmt_order = array();
        foreach($orders as $ok=>$order){
            foreach($order['order_objects'] as $ook=>$ordobj){
                $pvg_price  = 0;
                if($ordobj['obj_type']!='goods' && $ordobj['obj_item_num_sum'] > 0){
                    $pvg_price = round($ordobj['pmt_price']/$ordobj['obj_item_num_sum'],2);
                }

                foreach($ordobj['order_items'] as $k1=>$v1){
                    $item_pmt_price=0;
                    $item_pmt_price=$pvg_price*$v1['nums'];
                    if(isset($pmt_order[$v1['bn']])){
                        $pmt_order[$v1['bn']]['pmt_price'] += $v1['pmt_price']+$item_pmt_price;
                    }else{
                        $pmt_order[$v1['bn']]['pmt_price'] = $v1['pmt_price']+$item_pmt_price;
                    }
                }
            }
        }
        return $pmt_order;
    }

    //获取平台的销售价格
    function getSalePrice($orders){
        $sale_order = array();
        foreach($orders as $ok=>$order){
            foreach($order['order_objects'] as $ook=>$ordobj){
                $pvg_price = 0;
                if($ordobj['obj_type']=='pkg' || $ordobj['obj_type']=='gift' || $ordobj['obj_type']=='giftpackage'){
                    if($ordobj['obj_item_num_sum'] > 0)
                    {
                        $pvg_price = round($ordobj['sale_price']/$ordobj['obj_item_num_sum'],2);
                    
                        foreach($ordobj['order_items'] as $k1=>$v1){
                            if(isset($sale_order[$v1['bn']])){
                                $sale_order[$v1['bn']]['obj_quantity'] += $v1['nums'];
                                $sale_order[$v1['bn']]['obj_sale_price'] += ($v1['nums']*$pvg_price);
                            }else{
                                $sale_order[$v1['bn']]['obj_quantity'] = $v1['nums'];
                                $sale_order[$v1['bn']]['obj_sale_price'] = ($v1['nums']*$pvg_price);
                            }
                        }
                    }
                } else {
                    foreach( $ordobj['order_items'] as $k1=>$v1 ){
                         if ( isset( $sale_order[$v1['bn']]) ){
                            $sale_order[$v1['bn']]['quantity'] += $v1['nums'];
                            $sale_order[$v1['bn']]['sale_price'] += $v1['sale_price'];
                        }else{
                            $sale_order[$v1['bn']]['quantity'] = $v1['nums'];
                            $sale_order[$v1['bn']]['sale_price'] = $v1['sale_price'];
                        }
                    }
                }
            }
        }

        $sale_price = array();
        foreach($sale_order as $k=>$v){
            $price = ($v['obj_sale_price']+$v['sale_price']);
            $quantity = $v['quantity']+$v['obj_quantity'];
            $sale_price[$k]=round($price/$quantity,2);
        }
        return $sale_price;
    }

    public function format($print_data, $sku,&$_err,$mode='old'){
        if ($print_data['ids']) {
            $orderObj = &app::get('ome')->model('orders');
            $orderExtendObj = &app::get('ome')->model('order_extend');
            $pObj = &app::get('ome')->model('products');
            $oGoods = &app::get('ome')->model('goods');
            $bppObj = &app::get('ome')->model('branch_product_pos');
            $didObj = &app::get('ome')->model('delivery_items_detail');
            $orderObjectObj = &app::get('ome')->model('order_objects');
            $dlyorderObj = &app::get('ome')->model('delivery_order');
            $printDlyLib = kernel::single('ome_delivery_print_delivery');
            $brandObj = &app::get('ome')->model('brand');
            $goodsTypeObj = &app::get('ome')->model('goods_type');
            $tbitemObj = app::get('ome')->model('tbfx_order_items');
            $dlyObj = &app::get('wms')->model('delivery');

            //备注显示方式
            $markShowMethod = &app::get('ome')->getConf('ome.order.mark');
            $delivery_cfg = &app::get('wms')->getConf('wms.delivery.status.cfg');

            //获取仓库名称
            $branchObj = &app::get('ome')->model('branch');
            $branch_list = array();
            $kmtmp_shopInfo = array();
            $km_pd = array();
            $km_gds = array();
            $km_position = array();
            $km_brand = array();
            $km_gtype = array();
            $km_member = array();
            $logi_name = '';
            foreach ($print_data['deliverys'] as $k => $dly) {
                if(!isset($branch_list[$dly['branch_id']])){
                    $branch = $branchObj->dump(array('branch_id'=>$dly['branch_id']),'name');
                    if($branch){
                        $branch_list[$dly['branch_id']] = $branch['name'];
                    }
                }

                $pmt_orders = $printDlyLib->getPmtPrice($dly['orders']);
                $sale_orders = $printDlyLib->getSalePrice($dly['orders']);
                
                //当前发货单信息
                $data = $dly;

                //订单数量
                $data['order_number'] = count($dly['delivery_order']);
                if ($data) {
                    # 批次号
                    $allItems[$data['delivery_id']] = $data;
                    
                    //多条发货单减少查询次数
                    if(!isset($kmtmp_shopInfo[$data['shop_id']])){
                        $shop = $dlyObj->getShopInfo($data['shop_id']);
                        $data['shop_name'] = $shop['name'];
                        #新增收货人信息
                        $data['sender_name'] = $shop['default_sender'];
                        $data['sender_mobile'] = $shop['mobile'];
                        $data['sender_tel'] = $shop['tel'];
                        $data['sender_addr'] = $shop['addr'];
                        $sender_area = $shop['area'];
                        kernel::single('ome_func')->split_area($sender_area);
                        $data['sender_area'] = implode('-',$sender_area);

                        $kmtmp_shopInfo[$data['shop_id']] = array(
                            'shop_name'=>$data['shop_name'],
                            'sender_name'=>$data['sender_name'],
                            'sender_mobile'=>$data['sender_mobile'],
                            'sender_tel'=>$data['sender_tel'],
                            'sender_addr'=>$data['sender_addr'],
                            'sender_area'=>$data['sender_area']
                        );
                    }else{
                        $data = array_merge($data,$kmtmp_shopInfo[$data['shop_id']]);
                    }

                    $pmt_order_total=0;
                    $objIds = array();
                    $store_position = 0;
                    foreach ($data['delivery_items'] as $k => $item) {
                        if(!isset($km_pd[$item['bn']])){
                            $p = $pObj->dump(array('bn'=>$item['bn']));
                            $km_pd[$item['bn']] = $p;
                        }else{
                            $p = $km_pd[$item['bn']];
                        }

                        if(!isset($km_gds[$p['goods_id']])){
                            $goods = $oGoods->dump($p['goods_id'],'bn,type_id,brand_id,picurl');
                            $km_gds[$p['goods_id']] = $goods;
                        }else{
                            $goods = $km_gds[$p['goods_id']];
                        }
                        
                        //区分货品
                        $data['delivery_items'][$k]['bncode'] = md5($item['shop_id'].trim($item['bn']));
                        if(!isset($km_position[$item['product_id']."_km_".$data['branch_id']])){
                            $store_position = $bppObj->get_product_pos($item['product_id'], $data['branch_id']);
                            $km_position[$item['product_id']."_km_".$data['branch_id']] = $store_position;
                        }else{
                            $store_position = $km_position[$item['product_id']."_km_".$data['branch_id']];
                        }

                        #start新增PKG类型展示
                        $data['delivery_items'][$k]['item_type'] = $item['item_type'];
                        $data['delivery_items'][$k]['order_obj_id'] = $item['order_obj_id'];
                        $data['delivery_items'][$k]['order_item_id'] = $item['order_item_id'];
                        #end
                        if ($p['spec_info']) {
                            $picurl = $p['picurl'];
                        } else {
                            $picurl = $goods['picurl'];
                        }
                        $data['delivery_items'][$k]['picurl'] = $picurl;
                        $data['delivery_items'][$k]['spec_info'] = $p['spec_info'];
                        $data['delivery_items'][$k]['name'] = $p['name'];
                        $data['delivery_items'][$k]['store_position'] = $store_position;
                        $data['delivery_items'][$k]['addon'] = $p['spec_info'];
                        $data['delivery_items'][$k]['pmt_price'] = $pmt_orders[$item['bn']]['pmt_price'];
                        
                        $data['delivery_items'][$k]['barcode'] = $p['barcode']; //商品条形码
                        $data['delivery_items'][$k]['goods_bn'] = $goods['bn'];//商品编码
                        $data['delivery_items'][$k]['product_weight'] = $p['weight'];//商品重量
                        $data['delivery_items'][$k]['unit'] = $p['unit'];//商品单位
                        
                        if(!isset($km_brand[$goods['brand']['brand_id']])){
                            $brand = $brandObj->dump(array('brand_id'=>$goods['brand']['brand_id']),'brand_name');
                            $km_brand[$goods['brand']['brand_id']] = $brand;
                        }else{
                            $brand = $km_brand[$goods['brand']['brand_id']];
                        }

                        if(!isset($km_gtype[$goods['type']['type_id']])){
                            $goods_type = $goodsTypeObj->getList('name',array('type_id'=>$goods['type']['type_id']),0,1);
                            $km_gtype[$goods['type']['type_id']] = $goods_type;
                        }else{
                            $goods_type = $km_gtype[$goods['type']['type_id']];
                        }
                        $data['delivery_items'][$k]['brand_name'] = $brand['brand_name'];//品牌
                        $data['delivery_items'][$k]['type_name'] = $goods_type[0]['name'];//类型
                        
                        if($item['order_source'] == 'tbdx' && $item['shop_type'] == 'taobao'){
                            $tbfx_filter = array('obj_id'=>$item['order_obj_id'],'item_id'=>$item['order_item_id']);
                            $ext_item_info = $tbitemObj->getOrderByOrderId($tbfx_filter);
                            $data['delivery_items'][$k]['price'] = round(($ext_item_info[0]['buyer_payment']/$item['number']),2);
                            $data['delivery_items'][$k]['amount'] = $ext_item_info[0]['buyer_payment'];
                            $data['delivery_items'][$k]['sale_price'] = $ext_item_info[0]['buyer_payment'];                          
                        }else{
                            $data['delivery_items'][$k]['price'] = $item['price'];
                            $data['delivery_items'][$k]['amount'] = $item['amount'];
                            $data['delivery_items'][$k]['sale_price'] = ($sale_orders[$item['bn']]*$item['number']);
                        }
                    }
                    
                    if(!isset($km_member[$data['member_id']])){
                        $tmp = app::get('ome')->model('members')->dump($data['member_id'], 'uname,name,mobile,tel');
                        $data['member_name'] = $tmp['account']['uname'];
                        $t_tel = array();
                        if ($tmp['contact']['phone']['telephone']) {
                            $t_tel[] = $tmp['contact']['phone']['telephone'];
                        }
                        if ($tmp['contact']['phone']['mobile']) {
                            $t_tel[] = $tmp['contact']['phone']['mobile'];
                        }
                        $km_member[$data['member_id']] = array('uname'=>$data['member_name'],'phone'=>array($tmp['contact']['phone']['telephone'],$tmp['contact']['phone']['mobile']));
                    }else{
                        $data['member_name'] = $km_member[$data['member_id']]['uname'];
                        $t_tel = $km_member[$data['member_id']]['phone'];
                    }


                    $order_bn = array();
                    $shop_type = array();
                    $order_source = array();
                    if ($data['orders']){
                        $total_receivable = 0;
                        foreach ($data['orders'] as $odk => $order) {
                            if( $order['order_source'] == 'tbdx' && $order['shop_type'] == 'taobao' ){
                                $cost_item = $tbitemObj->getCostitemByOrderId($order['order_id']);
                                $data['order_cost_item'] += $cost_item[0]['cost_items'];
                            }else{
                                $data['order_cost_item'] += $order['cost_item'];
                            }

                            $pmt_order_total+=$order['pmt_order'];
                            $data['front_cost_freight'] += $order['shipping']['cost_shipping'];

                            if ($order['custom_mark']) {
                                $mark = unserialize($order['custom_mark']);
                                if (is_array($mark) || !empty($mark)){
                                    if($mode == 'new'){
                                        if($markShowMethod == 'all'){
                                            foreach ($mark as $im) {
                                                $data['buyWord'] .= $im['op_content'] . "　" . $im['op_time'] . "　by　". $im['op_name'] ."\r\n";
                                            }
                                        }else{
                                            $tmp_mark = array_pop($mark);
                                            $data['buyWord'] .= $tmp_mark['op_content'] . "　" . $tmp_mark['op_time'] . "　by　". $tmp_mark['op_name'] ."\r\n";
                                        }
                                        $data['buyWord'] = trim($data['buyWord'], "\r\n");
                                    }else{
                                        if($markShowMethod == 'all'){
                                            foreach ($mark as $im) {
                                                $data['_mark'][$order['order_bn']][] = $im;
                                            }
                                        }else{
                                            $data['_mark'][$order['order_bn']][] = array_pop($mark);
                                        }
                                    }
                                }
                            }

                            if ($order['mark_text']) {
                                $mark = unserialize($order['mark_text']);
                                if (is_array($mark) || !empty($mark)){
                                    if($mode == 'new'){
                                        if($markShowMethod == 'all'){
                                            foreach ($mark as $im) {
                                                $data['orderMark'] .= $im['op_content'] . "　" . $im['op_time'] . "　by　". $im['op_name'] ."\r\n";
                                            }
                                        }else{
                                            $data['orderMark'] .= $tmp_mark['op_content'] . "　" . $tmp_mark['op_time'] . "　by　". $tmp_mark['op_name'] ."\r\n";
                                        }
                                        $data['orderMark'] = trim($data['orderMark'], "\r\n");
                                    }else{
                                        if($markShowMethod == 'all'){
                                            foreach ($mark as $im) {
                                                $data['_mark_text'][$order['order_bn']][] = $im;
                                            }
                                        }else{
                                            $data['_mark_text'][$order['order_bn']][] = array_pop($mark);
                                        }
                                    }
                                }
                            }

                            if($order['tax_no'] || $order['tax_title']){
                                $data['_tax_info'][] = array(
                                    'order_bn'=>$order['order_bn'],
                                    'tax_no'=>$order['tax_no'],
                                    'tax_title'=>$order['tax_title'],
                                );
                            }

                            $order_bn[] = $order['order_bn'];

                            if(!in_array($order['shop_type'],$shop_type)){
                                $shop_type[] = $order['shop_type'];
                            }

                            if(!in_array($order['order_source'],$order_source)){
                                $order_source[] = $order['order_source'];
                            }

                            if($order['shipping']['is_cod'] == 'true'){
                                $extendInfo = array();
                                $extendInfo = $orderExtendObj->dump($order['order_id']);
                                $total_receivable += $extendInfo['receivable'];
                            }
                            if(!empty($order['paytime'])){
                                $data['paytime'] =  date('Y-m-d H:i:s',$order['paytime']);
                            }
                        }
                    }

                    $data['total_receivable'] = $total_receivable;
                    $data['order_bn'] = implode(" , ", $order_bn);

                     //有两级价格的发货单不显示价格
                    $show_delivery_price = true;
                    //all:代表一张发货关联多个前端店铺
                    $data['shop_type'] = 'all';
                    $data['order_source'] = 'all';
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
                    $show_delivery_price = true;#有两级价格的,显示价格
                    $data['show_delivery_price'] = $show_delivery_price;

                    if ($t_tel) $data['member_tel'] = implode(" / ", $t_tel);
                    //去除多余的三级区域
                    $reg = preg_quote(trim($data['consignee']['province']));
                    if (!empty($data['consignee']['city'])) {
                        $reg .= '.*?' . preg_quote(trim($data['consignee']['city']));
                    }
                    if (!empty($data['consignee']['district'])) {
                        $reg .= '.*?' . preg_quote(trim($data['consignee']['district']));
                    }

                    $data['consignee']['addr'] = preg_replace('/' . $reg . '/is', '', $data['consignee']['addr']);
                    $data['shopname'] = $data['shop_name'];#因京东会直接替掉，所以新增显示
                    #分销王订单新增代销人收货信息
                    if($shop['node_type'] == 'shopex_b2b'){
                        #开启分销王代销人发货信息
                        if($delivery_cfg['set']['ome_delivery_sellagent']){
                            foreach($data['delivery_order'] as $val){
                                //订单代销人会员信息
                                $oSellagent = &app::get('ome')->model('order_selling_agent');
                                $sellagent_detail = $oSellagent->dump(array('order_id'=>$val['order_id']));
                                #订单扩展表上的状态是1  (只有发货人与发货地址都存在，状态才会是1)
                                if($sellagent_detail['print_status'] == '1'){
                                    $data['shop_name'] = $sellagent_detail['website']['name'];
                                    $data['seller_name'] = $sellagent_detail['seller']['seller_name'];
                                    $data['seller_mobile'] = $sellagent_detail['seller']['seller_mobile'];
                                    $data['seller_phone'] = $sellagent_detail['seller']['seller_phone'];
                                    $data['seller_address'] = $sellagent_detail['seller']['seller_address'];
                                    $data['seller_area'] = $sellagent_detail['seller']['seller_area'];
                                }
                            }
                        }
                    }
                    //京东订单添加打印标示
                    if($data['shop_type']=='360buy'){
                        $logo_url = kernel::base_url(1)."/app/wms/statics/360buylogo.png";
                        $data['shop_name']      = '<img src="'.$logo_url.'" width="129" height="25" alt="京东商城">&nbsp;';
                        $data['shop_logo_url']  = $logo_url;
                        $data['shop_logo_html'] = '<img src="'.$logo_url.'" width="129" height="25" alt="京东商城">';
                    }

                    //物流信息
                    if ($logi_name == '') {
                        $logi_name = $data['logi_name'];
                    }

                    $items[] = $data;
                    //$this->checkOrderSendnum($id);
                } else {
                    $_err = 'true';
                }
            }
            unset($kmtmp_shopInfo,$km_pd,$km_gds,$km_position,$km_brand,$km_gtype,$km_member);
        }

        function cmp($a, $b) {
            return strcmp($a["store_position"], $b["store_position"]);
        }

        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $is_print_front = (1 == $deliCfgLib->getValue('wms_delivery_is_printdelivery',$sku)) ? true : false;

        foreach ($items as $k => $item) {
            usort($item['delivery_items'], "cmp");
            if (!$is_print_front) {
                foreach ($item['delivery_items'] as $i => $di) {
                    $item['delivery_items'][$i]['product_name'] = $di['name'];
                }
            }
            $items[$k] = $item;
        }
        
        //商品名称和规格取前台,是合并发货单,取第一个订单的货品名称
        if ($print_data['ids'] && $is_print_front) {
            $arrPrintProductName = $dlyObj->getPrintOrderName($items);
            if (!empty($arrPrintProductName)) {
                $productPos = $dlyObj->getPrintProductPos($items);

                foreach ($items as $k => $rows) {
                    foreach ($rows['delivery_items'] as $k2 => $v) {
                        $rows['delivery_items'][$k2]['name'] = $arrPrintProductName[$rows['delivery_id']][$v['bn']]['name'];
                        $rows['delivery_items'][$k2]['product_name'] = $arrPrintProductName[$rows['delivery_id']][$v['bn']]['name'];
                        $rows['delivery_items'][$k2]['addon'] = $arrPrintProductName[$rows['delivery_id']][$v['bn']]['addon'];
                        $rows['delivery_items'][$k2]['spec_info'] = $arrPrintProductName[$rows['delivery_id']][$v['bn']]['addon'];
                        $rows['delivery_items'][$k2]['store_position'] = $productPos[$v['product_id']];
                    }
                    $items[$k] = $rows;
                }
            }
        }

        if ($print_data['ids']) $vid = implode(',', $print_data['ids']);
        foreach ($items as $k => $rows) {
            $pmt_order_total=0;
            $pmt_order_discount = 0;
            foreach($rows['orders'] as $order){
                $pmt_order_total+=$order['pmt_order'];
                $pmt_order_total+=$order['pmt_goods'];
                $pmt_order_discount+=$order['discount'];
            }

            $delivery_total_nums = 0;
            $goods_num = 0;
            $sum_sale_price =0;
            foreach ($rows['delivery_items'] as $v) {
                $delivery_total_nums += $v['number'];
                $sum_sale_price = $v['sale_price'];
                $goods_num++;
            }
            
            $rows['goods_num'] = $goods_num;
            $rows['delivery_total_nums'] = $delivery_total_nums;
            $rows['pmt_order_total'] = $pmt_order_total;
            //订单折扣金额
            $rows['pmt_order_discount'] = $pmt_order_discount;
             //有两级价格的发货单不显示价格
            if(!$rows['show_delivery_price']){
                 foreach ($rows['delivery_items'] as $kk => $item) {
                    $rows['delivery_items'][$kk]['price'] = '-';
                 }
                $rows['order_total_amount'] = '-';
                $rows['order_cost_item'] = '-';
            }else{
                #add taobao fenxiao
                if($rows['order_source'] == 'tbdx' && $rows['shop_type'] == 'taobao'){
                    $rows['order_total_amount'] = $rows['front_cost_freight'] + $rows['order_cost_item'];
                }else{
                    $rows['order_total_amount'] = $dlyObj->getAllTotalAmountByDelivery($rows['delivery_order']);
                }
            }

            $items[$k] = $rows;
        }

        #添加打印模式
        #获取当前设置打印版本$deliCfgLib
        $print_version = $deliCfgLib->getprintversion();
        #获取当前打印模式
        $print_style = $deliCfgLib->getprintstyle();
        #打印订单将捆绑商品分类展示
        #根据版本标识
        if($print_version=='1'){
            //获取pkg后台名称
            $omepkgObj = &app::get('omepkg')->model('pkg_goods');
            foreach($items as $k=>$v){
                //格式化销售模式的数据
                $items[$k] = $this->formatSaleStyleData($v);

                #判断是否开启按货位排序
                if($delivery_cfg['set']['print_order'] == 1){
                    if(is_array($items[$k]['delivery_items']['goods'])){
                        $new_items = array();
                        foreach( $items[$k]['delivery_items']['goods'] as $_bn=>$_v){
                            #记录goods中的货位顺序
                            $store_position_order[$_bn] = trim($_v['order_items'][$_bn]['store_position']);
                        }
                        #对货位进行排序
                        asort($store_position_order);
                        #生成一个经过货位排序后的新数组
                
                        foreach($store_position_order as $bn=>$v){
                            $new_items[$bn] = $items[$k]['delivery_items']['goods'][$bn];
                        }
                        $store_position_order = array();
                        $items[$k]['delivery_items']['goods'] = $new_items;
                    }
              }
              
              //新版本拣货模式数据格式化开始
              #普通商品
              if($items[$k]['delivery_items']['goods']){
                  foreach($items[$k]['delivery_items']['goods'] as $bn=>$goods){
                      foreach($goods['order_items'] as $goods_val){
                          $pick_mode_data[$goods_val['bn']] = $goods_val;
                          $all_store_position[$goods_val['bn']] = $goods_val['store_position'];
                      }
                  }
              }

              #捆绑商品
              if($items[$k]['delivery_items']['pkg']){
                  foreach($items[$k]['delivery_items']['pkg'] as $pkg){
                      foreach($pkg['order_items'] as $pkg_val){
                          #把捆绑商品和普通商品合并
                          if(array_key_exists($pkg_val['bn'],$pick_mode_data)){
                              $pick_mode_data[$pkg_val['bn']]['number'] += $pkg_val['number'];
                          }else{
                              $pick_mode_data[$pkg_val['bn']] = $pkg_val;
                              $all_store_position[$pkg_val['bn']] = $pkg_val['store_position'];
                          }
                          
                      }
                  }
              }
              #赠品
              if($items[$k]['delivery_items']['gift']){
                  foreach($items[$k]['delivery_items']['gift'] as $gift){
                      foreach($gift['order_items'] as $gift_val){
                          if(array_key_exists($gift_val['bn'],$pick_mode_data)){
                              $pick_mode_data[$gift_val['bn']]['number'] += $gift_val['number'];
                          }else{
                              $pick_mode_data[$gift_val['bn']] = $gift_val;
                              $all_store_position[$gift_val['bn']] = $gift_val['store_position'];
                          }
                      }
                  }
              }
              #giftpackage
              if($items[$k]['delivery_items']['giftpackage']){
                  foreach($items[$k]['delivery_items']['giftpackage'] as $giftpackage){
                      foreach($giftpackage['order_items'] as $giftpackage_val){
                          if(array_key_exists($giftpackage_val['bn'],$pick_mode_data)){
                              $pick_mode_data[$giftpackage_val['bn']]['number'] += $giftpackage_val['number'];
                          }else{
                              $pick_mode_data[$giftpackage_val['bn']] = $giftpackage_val;
                              $all_store_position[$giftpackage_val['bn']] = $giftpackage_val['store_position'];
                          }
                      }
                  }
              }
              #按货位排序
              if($delivery_cfg['set']['print_order']){
                  #货位存在
                  if($all_store_position){
                      asort($all_store_position);
                      foreach($all_store_position as $bn=>$val){
                          $pick_mode_position_data[] = $pick_mode_data[$bn];
                      }
                      $items[$k]['delivery_items']['pick_mode_data'] = $pick_mode_position_data;
                  }else{
                      #货位不存在，则默认按货号排序
                      ksort($pick_mode_data);
                      $items[$k]['delivery_items']['pick_mode_data'] = $pick_mode_data;
                  }
              }else{
                  #按货号排序
                  ksort($pick_mode_data);
                  $items[$k]['delivery_items']['pick_mode_data'] = $pick_mode_data;
              }
              unset($pick_mode_data,$pick_mode_position_data,$all_store_position);
              //新版本拣货模式数据格式化结束
            }
        }else{
            #开启按货位排序后,在老版本中增加货位排序功能
            if($delivery_cfg['set']['print_order'] == 1){
                foreach($items as $k1=>$v1){
                    foreach($v1['delivery_items'] as $k2=>$v2){
                        $store_position_order[$k2] = trim($v2['store_position']);
                    }
                    #对货位进行排序
                    asort($store_position_order);
                    #生成一个经过货位排序后的新数组
                    foreach($store_position_order as $_k3=>$_v3){
                        $new_items['delivery_items'][] = $items[$k1]['delivery_items'][$_k3];
                    }
                    $items[$k1]['delivery_items']= $new_items['delivery_items'];
                    $store_position_order = $new_items =  array();
                }
            }
        }

        return array(
            'items' => $items,
            'allItems' => $allItems,
            'branch_list' => $branch_list,
            'vid' => $vid,
            'print_style' => $print_style,
            'logi_name' => $logi_name,
        );
    }

    public function arrayToJson($deliverys, $idents) {
        $nbsp = "　";
        $this->covertNullToString($deliverys);
        $delivery_total_nums = 0;
        $delivery_total_price = 0;
        foreach ($deliverys as $k => $dly) {
            list ($sender_province, $sender_city, $sender_district) = explode('-', $dly['sender_area']);
            //发货人-地区1级
            $deliverys[$k]['sender_province'] = $sender_province;
            //发货人-地区2级
            $deliverys[$k]['sender_city'] = $sender_city;
            //发货人-地区3级
            $deliverys[$k]['sender_district'] = $sender_district;
            $deliverys[$k]['batch_number'] = $idents[$dly['delivery_id']];
            $nbsp = "　";
            $deliverys[$k]['countDeliveryMsg']['total'] = '商品数量：' . $dly['delivery_total_nums'] . $nbsp . $nbsp .
                             //'累计品种：' . $dly['goods_num'] . $nbsp . $nbsp .
                             //'货品数量：' . $delivery_items['posNum'] . $nbsp . $nbsp .
                             '总重量：' . sprintf("%d", $dly['net_weight']);
            $deliverys[$k]['countDeliveryMsg']['empty'] = '';
        }
        return json_encode($deliverys);
    }

}