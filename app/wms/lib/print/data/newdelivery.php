<?php
/**
 * 发货单
 * @author liaoyu
 *
 */
class wms_print_data_newdelivery extends wms_print_data_abstract {
    /**
     * 电子面单
     */
    public function getElectronOrder($delivery_id) {
      
        $data = $this->formatDelivery($delivery_id);
        return $data;
    }

    /**
     * 格式化发货数据
     * @param Int $delivery_id 发货ID
     */
    public function formatDelivery($delivery) {
       
        if (empty($delivery)) {
            return array();
        }
        //收货人-姓名
        $data['consignee_name'] = $delivery['ship_name'];
        //收货人-地区1级
        $data['consignee_province'] = $delivery['ship_province'];
        //收货人-地区2级
        $data['consignee_city'] = $delivery['ship_city'];
        //收货人-地区3级
        $data['consignee_district'] = $delivery['ship_district'];
        //收货人-地址
        $data['consignee_addr'] = $delivery['ship_addr'];
        //收货人-邮编
        $data['consignee_zip'] = strval($delivery['ship_zip']);
        //收货人-联系电话
        $data['consignee_telephone'] = strval($delivery['ship_tel']);
        //收货人-手机
        $data['consignee_mobile'] = strval($delivery['ship_mobile']);
        //收货人-Email
        $data['consignee_email'] = $delivery['ship_email'];
        $orderWith = $this->getOrderWith($delivery['delivery_id']);
        //会员备注
    
        $data['buyWord'] = $delivery['buyWord'];
        //订单附言
        $data['orderMark'] = $delivery['orderMark'];
       
        //收货人-发货单号
        $data['delivery_bn'] = strval($delivery['delivery_bn']);
        //店铺信息
        $shop = $this->getShop($delivery['shop_id']);
        $sender_name = '';
        $sender_province = '';
        $sender_city = '';
        $sender_district = '';
        $sender_addr = '';
        $sender_tel = '';
        $sender_mobile = '';
        $shop_name = '';
        if ($shop) {
            $sender_name = $shop['default_sender'];
            $area = substr($shop['area'], strpos($shop['area'], ':') + 1, strrpos($shop['area'], ':') - strpos($shop['area'], ':') -1);
            list ($sender_province, $sender_city, $sender_district) = explode('/', $area);
            $sender_addr = $shop['addr'];
            $sender_tel = strval($shop['tel']);
            $sender_mobile = strval($shop['mobile']);
            $shop_name = $shop['name'];
        }
        //发货人-姓名
        $data['sender_name'] = $sender_name;
        //发货人-地区1级
        $data['sender_province'] = $sender_province;
        //发货人-地区2级
        $data['sender_city'] = $sender_city;
        //发货人-地区3级
        $data['sender_district'] = $sender_district;
        //发货人-地址
        $data['sender_addr'] = $sender_addr;
        //发货人-联系电话
        $data['sender_tel'] = $sender_tel;
        //发货人-手机
        $data['sender_mobile'] = $sender_mobile;
        //店铺名称
        $data['shop_name'] = $shop_name;
        //会员名
        //$member = $this->getMembers($delivery['member_id']);
        $member_name = '';
        $member_tel = '';
        //if ($member) {
            $member_name = $delivery['member_name'];
            $member_tel = $delivery['member_tel'];;
        //}
        $data['member_name'] = $member_name;
        //会员联系方式
        $data['member_tel'] = $member_tel;
        //操作员
        $data['op_name'] = $delivery['op_name'];
        //当日日期-年
        $data['date_y'] = date("Y");
        //当日日期-月
        $data['date_m'] = date("m");
        //当日日期-日
        $data['date_d'] = date("d");
        //当日日期-年月日
        $data['date_ymd'] = date("Ymd");
        //订单-订单号
        $data['order_bn'] = $delivery['order_bn'];
        //商品重量
        $data['net_weight'] = strval($delivery['net_weight']);
        //预计物流费用
        $data['delivery_cost_expect'] = strval(sprintf("%.2f", $delivery['delivery_cost_expect']));
        //物流公司
        $data['logi_name'] = $delivery['logi_name'];
        //批次号
        $batch_number = $delivery['batch_number'];
        $data['batch_number'] = $batch_number ? $batch_number : '';
        $delivery_items = $this->getDeliveryItems($delivery['delivery_items']);
        
       
        $data['countDeliveryMsg'] = $this->formatCountDeliveryMsgField($delivery_items);
        $data['delivery_items'] = $delivery_items['delivery_items'];
        return $data;
    }

   
    function _custom_mark($custom_mark)
    {
        $custom = '';
        $custom_mark = unserialize($custom_mark);
                foreach ($custom_mark as $cv) {
                    $custom .= $cv['op_content'] . $nbsp . $cv['op_time'] . $nbsp . $byText . $nbsp . $cv['op_name'] ."\r\n";
                }
                 if ($custom) {
            $custom = trim($custom, "\r\n");
        }
        
        return $custom;
    }

    
    
    function _mark_text($mark_text)
    {
         $mark = '';
        $mark_text = unserialize($mark_text);
                foreach ($mark_text as $mv) {
                    $mark .= $mv['op_content'] . $nbsp . $mv['op_time'] . $nbsp . $byText . $nbsp . $mv['op_name'] . "\r\n";
                }
                if ($mark) {
            $mark = trim($mark, "\r\n");
        }
        return $mark;
    }
    /**
     * 获取订单编号
     * @param Int $delivery_id 发货单ID
     */
    public function getOrderBnstr($delivery_id) {
        $orders = $this->getOrderInfoByDeliverId($delivery_id);
        $orderBnStr = '';
        foreach ($orders as $v) {
            $orderBnStr .= $v['order_bn'] . ',';
        }
        if ($orderBnStr) {
            $orderBnStr = trim($orderBnStr, ',');
        }
        return $orderBnStr;
    }

    /**
     * 获取订单相关信息
     * @param Int $delivery_id 发货单ID
     */
    public function getOrderWith($delivery_id) {
        $orders = $this->getOrderInfoByDeliverId($delivery_id);
        $orderBnStr = '';
        $custom = '';
        $mark = '';
        $nbsp = "　";
        $byText = 'by';
        foreach ($orders as $v) {
            #订单编号
            if ($v['order_bn']) {
                $orderBnStr .= $v['order_bn'] . ',';
            }
            #会员留言
            if ($v['custom_mark']) {
                $custom_mark = unserialize($v['custom_mark']);
                foreach ($custom_mark as $cv) {
                    $custom .= $cv['op_content'] . $nbsp . $cv['op_time'] . $nbsp . $byText . $nbsp . $cv['op_name'] ."\r\n";
                }
            }
            #订单备注
            if ($v['mark_text']) {
                $mark_text = unserialize($v['mark_text']);
                foreach ($mark_text as $mv) {
                    $mark .= $mv['op_content'] . $nbsp . $mv['op_time'] . $nbsp . $byText . $nbsp . $mv['op_name'] . "\r\n";
                }
            }
        }
        if ($orderBnStr) {
            $orderBnStr = trim($orderBnStr, ',');
        }
        if ($custom) {
            $custom = trim($custom, "\r\n");
        }
        if ($mark) {
            $mark = trim($mark, "\r\n");
        }
        $data = array(
            'order_bn' => $orderBnStr,
            'buyWord' => $custom,
            'orderMark' => $mark
        );
        return $data;
    }

    /**
     * 设置批次号
     * @param Array $idents 批次号
     */
    public function setBatchNumbers($items) {
        foreach ($items as $k => $v) {
            if (!$this->identsItems[$k]) {
                $this->identsItems[$k] = $v;
            }
        }
    }

    /**
     * 获取批次号
     * @param Int $delivery_id 发货单ID
     */
    public function getsBatchNumber($delivery_id) {
        return $this->identsItems[$delivery_id];
    } 
    
    /**
     * 获取发货单项
     * @param Int $delivery_id 发货单ID
     */
    public function getDeliveryItems($items) {

        $deliveryItems = array();
        foreach ($items as $ik=>$item ) {
            if ($ik == 'pkg') {
                foreach ($item as $pkg_item ) {
                    $pkg_items = array();
                    $order_items =$pkg_item['order_items'];
                    foreach ($order_items as $orditem ) {
                        $pkg_items[] = array(
                            'bn'=>$orditem['bn'],
                            'name'=>$orditem['name'],
                            'store_position'=>$orditem['store_position'],
                            'sale_price'=>$orditem['sale_price'],
                            'price'=>$orditem['price'],
                            'number'=>$orditem['number'],
                            'goods_bn'=>$orditem['goods_bn'],
                            'product_weight'=>$orditem['product_weight'],
                             'unit'=>$orditem['unit'],
                            'brand_name'=>$orditem['brand_name'],
                            'type_name'=>$orditem['type_name'],
                            'spec_info'=>$orditem['spec_info'],
                        );
                    }
                    $object_item = array(
                        'bn'=>$pkg_item['bn'],
                        'name'=>$pkg_item['name'],
                        'sale_price'=>$pkg_item['sale_price'],
                        'price'=>$pkg_item['price'],
                        'number'=>$pkg_item['quantity'],
                        'pmt_price'=>$pkg_item['pmt_price'],
                        'spec_info'=>'-',
                        'items'=>$pkg_items,

                    );
                    $deliveryItems[] =$object_item; 
                }
            }else{
                foreach ($item as $product_item ) {
                    foreach ($product_item['order_items'] as $order_items ) {
                        $product_item =array(
                            'bn'=>$order_items['bn'],
                            'name'=>$order_items['name'],
                            'store_position'=>$order_items['store_position'],
                            'sale_price'=>$order_items['sale_price'],
                            'price'=>$order_items['price'],
                            'number'=>$order_items['number'],
                            'goods_bn'=>$order_items['goods_bn'],
                            'product_weight'=>$order_items['product_weight'],
                            'unit'=>$order_items['unit'],
                            'brand_name'=>$order_items['brand_name'],
                            'type_name'=>$order_items['type_name'],
                            'spec_info'=>$order_items['spec_info'],
                        ); 
                        $deliveryItems[] =$product_item; 
                    }
                }
            }
        }
        
        $deliveryItemsStruct = array();
        if ($deliveryItems) {
            $deliveryItemsStruct = $this->deliveryItemsStructA($deliveryItems);
        }

        //格式化明细

        return $deliveryItemsStruct;
    }

    /**
     * 发货单数据格式A
     * @param Arr $data 发货单数据
     */
    public function deliveryItemsStructA($data) {
       
        if (empty($data)) {
            return array();
        }
        #商品数量
        $goodsNum = 0;
        #货品数量
        $saleGoodsNum = 0;
        #实收金额总数
        $sumSalePrice = 0.00;
        #没有捆绑商品总数
        $nobindGoodsNum = 0;
        #商品种类
        $class = 0;
        $item = array();
        $print_number = 1;
        foreach ($data as &$vd) {
            $sumSalePrice += $vd['sale_price'];
            $goodsNum += $vd['number'];
            $vd['print_number'] = $print_number;
            $print_number++;
            if (isset($vd['items'])) {
                
                foreach ($vd['items'] as &$vdv) {
                    $vdv['sale_price'] = '-';
                    $vdv['price'] = '-';
                    $saleGoodsNum += $vdv['number'];
                    
                    $class++;
                }
            }
            else {
                $class++;
                $nobindGoodsNum += $vd['number'];
            }
        }
        //usort($data, array('ome_print_data_delivery', 'cmpItem'));
        $item = array(
            'nobindGoodsNum' => $nobindGoodsNum,
            'goodsNum' => $goodsNum,
            'posNum' => ($nobindGoodsNum + $saleGoodsNum),
            'sumSalePrice' => $sumSalePrice,
            'class' => $class,
            'delivery_items' => $data, 
        );

        return $item;
    }

    /**
     * 获得捆绑商品信息
     * @param Int $obj_id 订单对象ID
     */
    public function getPkgItems($obj_id) {
        $data = $this->getOrderItems($obj_id);
        $items = array();
        $ii = 0;
        //

        foreach ($data as $v) {
            
            $items[$ii]['price'] = $v['price'] ? $v['price'] : '';
            $items[$ii]['goods_bn'] = $v['bn'];
            $items[$ii]['name'] = $v['name'];
            $items[$ii]['sale_price'] = $v['amount'];
            $items[$ii]['number'] = $v['nums'];
            $items[$ii]['spec_info'] = '';
            
            $ii++;
        }
        usort($items, array('ome_print_data_delivery', 'cmp'));
        return $items;
    }

    /**
     * 格式化CountDeliveryMsg字段
     * @param Array $delivery_items 发货单报表数据
     */
    public function formatCountDeliveryMsgField($delivery_items) {
        $nbsp = "　";
        $data['total'] = '商品数量：' . $delivery_items['goodsNum'] . $nbsp . $nbsp .
                         '累计品种：' . $delivery_items['class'] . $nbsp . $nbsp .
                         '货品数量：' . $delivery_items['posNum'] . $nbsp . $nbsp .
                         '总重量：' . sprintf("%d", $delivery_items['net_weight']);
        $data['empty'] = '';
        return $data;
    }

    /**
     * 排序
     * @param Array $goods1 商品1
     * @param Array $goods2 商品2
     */
    public function cmp($goods1, $goods2) {
        return strcmp($goods1['goods_bn'], $goods2['goods_bn']) > 0 ? 1 : -1;
    }

    /**
     * 排序发货单项目
     * @param Array $goods1 商品1
     * @param Array $goods2 商品2
     */
    public function cmpItem($goods1, $goods2) {
        $gc1 = isset($goods1['items']) ? count($goods1['items']) : 0;
        $gc2 = isset($goods2['items']) ? count($goods2['items']) : 0;
        return $gc1 == $gc2 ? ($this->cmp($goods1, $goods2)) : ($gc1 > $gc2 ? 1 : -1); 
    }

    
    /**
     * 产品信息.
     * @param
     * @return  type    description
     * @access  public
     * @author sunjing@shopex.cn
     */
    function _getProductinfo($bn)
    {
        $productObj =  app::get('ome')->model('products');
        $product = $productObj->dump(array('bn'=>$bn),'spec_info');
        return $product;
    }

   
 
}