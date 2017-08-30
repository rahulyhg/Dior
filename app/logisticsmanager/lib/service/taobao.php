<?php

class logisticsmanager_service_taobao extends logisticsmanager_service_abstract{
    /**
     * 获得物流单号
     * @param Array $params 参数
     * @param Array $channel 电子面单链接信息
     */
    public function get_waybill_number($params) {
        $result = array('rsp' => 'succ');
        $delivery = $this->getDelivery($params['delivery_id']);
        //检查发货单是否存在
        if (empty($delivery)) {
            $result = array('rsp' => 'fail', 'delivery_bn' => $delivery['delivery_bn'], 'delivery_id' => $delivery['delivery_id']);
            return $result;
        }

        if ($this->isExistlogino($params)) {
            $result['data'][0] = array(
                'logi_no' => $this->currentLogiNo,
                'delivery_id' => $params['delivery_id'],
                'delivery_bn' => $this->currentDeliveryBn
            );
        }
        else {
            /**
             * 检查系统中是否存在未使用的物流单
             */
            if (false && $this->checkWaybillNumberIsUse($params['channel_id'])) {
                $result = $this->update_waybill_number($params);
            }
            else {
                $channel = $this->getChannel($params['channel_id']);
                $shipping_addres = $this->getShippingAddress($params['channel_id']);
                $trade_order_info_cols = $this->getTradeOrderInfoCols($params['delivery_id']);
                $trade_order_info_cols[0]['real_user_id'] = $shipping_addres['seller_id'];
                #德邦快递需要加增加发货人、联系方式
                if($channel['logistics_code'] == 'DBKD'){
                    #需要传相关发货人数据
                    $trade_order_info_cols[0]['send_name'] = $shipping_addres['default_sender'];#发货人姓名
                    $trade_order_info_cols[0]['send_phone'] = $shipping_addres['mobile']?$shipping_addres['mobile']:$shipping_addres['tel'];#发货人联系方式
                }else{
                    unset($shipping_addres['default_sender'],$shipping_addres['tel'],$shipping_addres['mobile']);
                }
                unset($shipping_addres['seller_id']);
                $log_id = $this->getGenId();
                $businessType = logisticsmanager_waybill_taobao::getBusinessType($channel['logistics_code']);
    
                //请求接口参数
                $rpcData = array(
                    'cp_code' => $channel['logistics_code'],
                    'shipping_address' => $shipping_addres,
                    'trade_order_info_cols' => $trade_order_info_cols,
                    'out_biz_code' => $log_id,
                    'businessType' => $businessType,
                    'shop_id' => $channel['shop_id'],
                    'channel_id' => $params['channel_id'],
                    'delivery_id' => $params['delivery_id'],
                    'delivery_bn' => $delivery['delivery_bn'],
                    'logistics_code' => $channel['logistics_code'],
                );
                //重试日志信息
                $logSdf = array(
                    'log_id' => $log_id,
                    'channel_id' => $params['channel_id'],
                    'status' => 'running',
                    'create_time' => time(),
                    'params' => $rpcData,
                );
    
                if ($this->insertWaybillLog($logSdf)) {
                    $taobaoRpcObj = kernel::single('logisticsmanager_rpc_request_taobao');
                    if($this->isChildBill){
                        $rpcData['trade_order_info_cols'][0]['trade_order_list'] = $this->setChildRqOrdNo($rpcData['trade_order_info_cols'][0]['trade_order_list']);
                        $taobaoRpcObj->setCurrChildBill($this->childBill_id);
                    }
                    $result = $taobaoRpcObj->setChannelType('taobao')->get_waybill_number($rpcData);
                }
            }
        }
        return $result;
    }

    /**
     * 获得发货地址
     * @param Int $delivery_id 发货单ID
     */
    public function getShippingAddress($channel_id) {
        //$delivery = $this->getDelivery($delivery_id);
        //$shop_id = $delivery['shop_id'];
        $extendObj = app::get('logisticsmanager')->model('channel_extend');
        $extend = $extendObj->dump(array('channel_id'=>$channel_id),'province,city,area,address_detail,seller_id,default_sender,tel,mobile');
//        $shop = $this->getShop($shop_id);
//        $first = strpos($shop['area'], ':');
//        $last = strrpos($shop['area'], ':');
//        $pca = substr($shop['area'], $first + 1, $last - $first - 1);
//        list($province, $city, $area) = explode('/', $pca);
        $shipping_address = array(
            'province' => $extend['province'],
            'city' => $extend['city'],
            'area' => $extend['area'],
            'address_detail' => $extend['address_detail'],
            'seller_id' => $extend['seller_id'],
            'default_sender'=>$extend['default_sender'],
            'tel'=>$extend['tel'],
            'mobile'=>$extend['mobile']
        );
        return $shipping_address;
    }

    /**
     * 获得订单信息
     * @param Int $delivery_id 发货单ID
     */
    public function getTradeOrderInfoCols($delivery_id) {
        $delivery = $this->getDelivery($delivery_id);
        $trade_order_list = $this->getTradeOrderList($delivery_id);
        $order_channels_type = $this->getOrderChannelsType($delivery_id);
        $item_name = $this->getItemName($delivery_id);
        $package_items = $this->getpackage_items($delivery_id);
        $trade_order_info_cols = array(
            0 => array(
                'consignee_address' => array(
                    'address_detail' => $delivery['ship_addr'],
                    'area' => $delivery['ship_district'],
                    'city' => $delivery['ship_city'],
                    //'division_id' => $delivery['ship_zip'] ? $delivery['ship_zip'] : 0,
                    'province' => $delivery['ship_province'],
                ),
                'consignee_name' => $delivery['ship_name'],
                'consignee_phone' => $delivery['ship_mobile'] != '' ? $delivery['ship_mobile'] : $delivery['ship_tel'],
                'item_name' => $item_name,
                'package_items'=>$package_items,
                'order_channels_type' => $order_channels_type,
                'trade_order_list' => implode(',',$trade_order_list),
                'product_type'=>'STANDARD_EXPRESS',
                'package_id'=>$delivery['delivery_bn'],
 
            )
        );
        return $trade_order_info_cols;
    }

    /**
     * 获取订单店铺类型
     * @param Int $delivery_id 发货单ID
     */
    public function getOrderChannelsType($delivery_id) {
        $deliveryOrder = $this->getDeliveryOrder($delivery_id);
        $tbbusiness_type = 'other';
        $orderList = $this->getDeliveryOrder($delivery_id);
        $createway = 'local';
        foreach ($orderList['order_info'] as $k => $v) {
            $createway = $v['createway'];
            if ($v['createway'] != 'matrix') {
                break;
            }
        }

        if ($deliveryOrder && $createway == 'matrix') {
            $firstOrderId = $deliveryOrder['order_id'][0];
            $shop_id = $deliveryOrder['order_info'][$firstOrderId]['shop_id'];
            $shop = $this->getShop($shop_id);
            if ($shop) {
                $tbbusiness_type = $shop['tbbusiness_type'];
            }
        }
        $order_channels_type = logisticsmanager_waybill_taobao::get_order_channels_type($tbbusiness_type);
        return $order_channels_type;
    }

    /**
     * 获取商品名称
     * @param Int $delivery_id 发货单ID
     */
    public function getItemName($delivery_id) {
        $deliveryOrder = $this->getDeliveryOrder($delivery_id);
        $item_name = 'other item name';
        if ($deliveryOrder) {
            $firstOrderId = $deliveryOrder['order_id'][0];
            $orderItems = $this->getOrderItems($firstOrderId);
            if ($orderItems) {
                //订单明细中第一个商品名称
                $item_name = $orderItems[0]['name'] ? $orderItems[0]['name']:'other item name';
            }
        }
        return $item_name;
    }


    
   /**
    * 获取包裹明细
    * @param   
    * @return  
    * @access  public
    * @author sunjing@shopex.cn
    */
   public function getpackage_items($delivery_id)
   {
       $items = $this->getDeliveryItems($delivery_id);
       $package = array();
       foreach ($items as $item ) {
           $package[] = array('item_name'=>$item['product_name'],'count'=>$item['number']);
       }
       return $package;
   }
    /**
     * 更新电子面单
     * @param Array $params 参数
     */
    public function update_waybill_number($params) {
        #获取物流单
        $waybillNumber = $this->getBufferPoolWayBillNumber($params['channel_id']);
        
    }
    /**
    * 确认打印
    */
     public function delivery($delivery_id) {
        $rpcObj = kernel::single('logisticsmanager_rpc_request_taobao');
        $channelObj = &app::get('logisticsmanager')->model('channel');
        //WMS信息
        $deliveryObj = app::get('ome')->model('delivery');
        //$wms_deliveryObj = app::get('wms')->model('delivery');
        $delivery = $deliveryObj->dump($delivery_id,'delivery_bn,ship_area,ship_province,ship_city,ship_district,ship_addr,ship_tel,ship_mobile,logi_no,logi_id,ship_name');
        $delivery_bn = $delivery['delivery_bn'];

        
        $dlyCorpObj = app::get('ome')->model('dly_corp');
        $dlyCorp = $dlyCorpObj->dump($delivery['logi_id'],'channel_id');

        $shipping_address = $this->getShippingAddress($dlyCorp['channel_id']);
        $waybill_extend = $this->getWaybillExtend(array('logi_no'=>$delivery['logi_no'],'channel_id'=>$dlyCorp['channel_id']));
     
        $channel = $channelObj->dump($dlyCorp['channel_id']);
        $print_check_info_cols = array(
            'consignee_name'=>$delivery['consignee']['name'],
            'waybill_code'=>$delivery['logi_no'],
            'consignee_address'=> array(
                    'address_detail' => $delivery['consignee']['addr'],
                    'area' => $delivery['consignee']['district'],
                    'city' => $delivery['consignee']['city'],
                    //'division_id' => $delivery['ship_zip'] ? $delivery['ship_zip'] : 0,
                    'province' => $delivery['consignee']['province'],
                ),
            'consignee_phone'=>$delivery['consignee']['mobile'] != '' ? $delivery['consignee']['mobile'] : $delivery['consignee']['telephone'],
            'shipping_address'=>$shipping_address,
             'short_address'=>$waybill_extend['position'],
             'real_user_id'=>$shipping_address['seller_id'],
        );

        $params = array(
            'print_check_info_cols'=>json_encode($print_check_info_cols),
            'cp_code'=>$channel['logistics_code'],
            'shop_id'=>$channel['shop_id'],
        
        );

        $result = $rpcObj->setChannelType('taobao')->delivery($params);
     }
    
    

}