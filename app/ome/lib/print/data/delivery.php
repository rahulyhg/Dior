<?php
/**
 * 发货单
 * @author liaoyu
 *
 */
class ome_print_data_delivery extends ome_print_data_abstract {
    /**
     * 电子面单
     */
    public function getElectronOrder($delivery_id) {
        $delivery = $this->getDelivery($delivery_id);
        $data = $this->formatDelivery($delivery_id);
        return $data;
    }

    /**
     * 格式化发货数据
     * @param Int $delivery_id 发货ID
     */
    public function formatDelivery($delivery_id) {
        $delivery = $this->getDelivery($delivery_id);
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
        $orderWith = $this->getOrderWith($delivery_id);
        //会员备注
        $data['buyWord'] = $orderWith['buyWord'];
        //订单附言
        $data['orderMark'] = $orderWith['orderMark'];
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
        $member = $this->getMembers($delivery['member_id']);
        $member_name = '';
        $member_tel = '';
        if ($member) {
            $member_name = $member['contact']['name'];
            $member_tel = empty($member['contact']['phone']['mobile']) ? $member['contact']['phone']['telephone'] : $member['contact']['phone']['mobile'];
        }
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
        $data['order_bn'] = $orderWith['order_bn'];
        //商品重量
        $data['net_weight'] = strval($delivery['net_weight']);
        //预计物流费用
        $data['delivery_cost_expect'] = strval(sprintf("%.2f", $delivery['delivery_cost_expect']));
        //物流公司
        $data['logi_name'] = $delivery['logi_name'];
        //批次号
        $data['batch_number'] = $this->getsBatchNumber($delivery_id);
        $delivery_items = $this->getDeliveryItems($delivery_id);
        $delivery_items['net_weight'] = $data['net_weight'];
        $data['countDeliveryMsg'] = $this->formatCountDeliveryMsgField($delivery_items);
        $data['delivery_items'] = $delivery_items['delivery_items'];
        return $data;
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
    public function getDeliveryItems($delivery_id) {
        $orders = $this->getDeliveryOrder($delivery_id);
        $orderItems = array();
        foreach ($orders as $order) {
            $orderItems[] = $this->getOrderObject($order['order_id']);
        }
        $deliveryItems = array();
        $i = 0;
        foreach ($orderItems as $object) {
            foreach ($object as $items) {
                $deliveryItems[$i]['price'] = $items['price'];
                $deliveryItems[$i]['goods_bn'] = $items['bn'];
                $deliveryItems[$i]['name'] = $items['name'];
                $deliveryItems[$i]['sale_price'] = $items['amount'];
                $deliveryItems[$i]['number'] = $items['quantity'];
                //捆绑商品处理
                if ($items['obj_type'] == 'pkg') {
                    $orderItems = $this->getPkgItems($items['obj_id']);
                    $deliveryItems[$i]['items'] = $orderItems;
                }
                $i++;
            }
        }
        $deliveryItemsStruct = array();
        if ($deliveryItems) {
            $deliveryItemsStruct = $this->deliveryItemsStructA($deliveryItems);
        }
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
        foreach ($data as &$vd) {
            $sumSalePrice += $vd['sale_price'];
            $goodsNum += $vd['number'];
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
        usort($data, array('ome_print_data_delivery', 'cmpItem'));
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
        foreach ($data as $v) {
            $items[$ii]['price'] = $v['price'];
            $items[$ii]['goods_bn'] = $v['bn'];
            $items[$ii]['name'] = $v['name'];
            $items[$ii]['sale_price'] = $v['amount'];
            $items[$ii]['number'] = $v['nums'];
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
}