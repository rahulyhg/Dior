<?php

class logisticsmanager_service_yunda extends logisticsmanager_service_abstract {
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
            $result = array('rsp' => 'fail', 'delivery_bn' => '', 'delivery_id' => $params['delivery_id']);
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
            $channel = $this->getChannel($params['channel_id']);
            list ($sysAccount, $passWord) = explode('|||', $channel['shop_id']);
            //订单数据
            $orders = $this->getOrdersFormat($params);
            $log_id = $this->getGenId();
            $businessType = logisticsmanager_waybill_yunda::getBusinessType($channel['logistics_code']);

            //请求接口参数
            $rpcData = array(
                'sysAccount' => $sysAccount,
                'passWord' => $passWord,
                'request' => 'data',
                'version' => '1.0',
                'orderid' => $orders[0]['khddh'],
                'orders' => json_encode($orders),

                'cp_code' => $channel['logistics_code'],
                'out_biz_code' => $log_id,
                'businessType' => $businessType,
                'shop_id' => 'yunda',
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
                $taobaoRpcObj = kernel::single('logisticsmanager_rpc_request_yunda');
                if($this->isChildBill){
                    $rpcData['orderid'] = $this->setChildRqOrdNo($rpcData['orderid']);
                    $orders[0]['khddh'] = $rpcData['orderid'];
                    $rpcData['orders']  = json_encode($orders);
                    $taobaoRpcObj->setCurrChildBill($this->childBill_id);
                }
                $result = $taobaoRpcObj->setChannelType('yunda')->get_waybill_number($rpcData);
            }
        }
        return $result;
    }

    /**
     * 获得订单格式
     */
    public function getOrdersFormat($params) {
        $delivery = $this->getDelivery($params['delivery_id']);
        $orderList = $this->getTradeOrderList($params['delivery_id']);
        $channel = $this->getChannel($params['channel_id']);
        list ($sysAccount, $passWord) = explode('|||', $channel['shop_id']);
        //获取发货信息
        $sender = $this->getSenderInfo($params);
        //获取收货信息
        $receiver = $this->getReceiverInfo($params);
        //发货订单
        $deliveryOrder = $this->getDeliveryOrder($params['delivery_id']);
        $total_amount = 0;
        foreach ($deliveryOrder['order_info'] as $order_id => $orderInfo) {
            $total_amount += $order_id['total_amount'];
        }

        //发货单明细
        $deliveryItems = $this->getDeliveryItems($params['delivery_id']);
        $items = array();
        foreach ($deliveryItems as $item) {
            $items[] = array(
                'name' => $item['product_name'],
                'number' => $item['number'],
                'remark' => '',
            );
        }

        $orders = array(
            0 => array(
                'khddh' => $orderList[0],
                'nbckh' => $sysAccount,
                's_name' => $sender['name'],
                's_company' => $sender['company'],
                's_city' => $sender['city'],
                's_address' => $sender['city'].$sender['address'],
                's_postcode' => $sender['postcode'],
                's_phone' => $sender['phone'],
                's_mobile' => $sender['mobile'],
                's_branch' => '',
                'r_name' => $receiver['name'],
                'r_company' => $receiver['company'],
                'r_city' => $receiver['city'],
                'r_address' => $receiver['city'].$receiver['address'],
                'r_postcode' => $receiver['postcode'],
                'r_phone' => $receiver['phone'],
                'r_mobile' => $receiver['mobile'],
                'r_branch' => '',
                'weight' => $delivery['net_weight'],
                'size' => '',
                'value' => $total_amount,
                'collection_value' => '',//代收金额，暂时不用
                'special' => '',
                'items' => $items,
                'remark' => '',
                'receiver_force' => 0,
            ),
        );
        return $orders;
    }

    /**
     * 获取发货信息
     * @param Array $params 参数
     */
    public function getSenderInfo($params) {
        $shop = $this->getChannelExtend($params['channel_id']);
        $sender = array(
            'name' => $shop['default_sender'],
            'company' => $shop['shop_name'],
            'city' => $shop['city'],
            'address' => $shop['address_detail'],
            'postcode' => $shop['zip'],
            'phone' => $shop['tel'],
            'mobile' => $shop['mobile'],
        );
        return $sender;
    }

    /**
     * 获得收货信息
     * @param Array $params 参数
     */
    public function getReceiverInfo($params) {
        $delivery = $this->getDelivery($params['delivery_id']);
        if ($this->isMunicipality($delivery['ship_province']) ) {
            $delivery['ship_province'] = str_replace('市', '', $delivery['ship_province']);
            $delivery['ship_province'] .= '市';

        }

        $city = $delivery['ship_province'] . ',' . $delivery['ship_city'] . ',' . $delivery['ship_district'];
        $reciver = array(
            'name' => $delivery['ship_name'],
            'company' => $delivery['ship_name'],
            'city' => $city,
            'address' => $delivery['ship_addr'],
            'postcode' => $delivery['ship_zip'],
            'phone' => $delivery['ship_tel'],
            'mobile' => $delivery['ship_mobile'],
        );
        return $reciver;
    }

    /**
     * 搜索运单号
     * @param Array $params 参数
     */
    public function search_waybill_number($params) {
        $result = array('rsp' => 'succ');

        //非子单用原有参数检查，子单用解析后的做检查
        if(!$this->checkChildRqOrdNo($params['order_bn'], $main_order_bn, $waybill_cid)){
            $main_order_bn = $params['order_bn'];
        }

        //检查订单是否存在
        if ($this->checkOrderBnIsExist($main_order_bn) == false) {
            $result = array('rsp' => 'fail');
            $result['data'][0] = array(
                'order_bn' => $params['order_bn'],
                'channel_id' => $params['channel_id'],
                'err_msg' => '订单order_bn不存在',
            );
        }
        else {
            $channel = $this->getChannel($params['channel_id']);
            $businessType = logisticsmanager_waybill_yunda::getBusinessType($channel['logistics_code']);
            $log_id = $this->getGenId();
            list ($sysAccount, $passWord) = explode('|||', $channel['shop_id']);
            $orders = array(
                0 => array(
                    'khddh' => $params['order_bn'],
                    'mailno' => '',
                    'print_file' => 0,
                    'json_data' => 1,
                    'json_encrypt' => 0,
                ),
            );

            //请求接口参数
            $rpcData = array(
                'sysAccount' => $sysAccount,
                'passWord' => $passWord,
                'version' => '1.0',
                'request' => 'data',
                'orders' => json_encode($orders),

                'cp_code' => $channel['logistics_code'],
                'out_biz_code' => $log_id,
                'businessType' => $businessType,
                'shop_id' => 'yunda',
                'channel_id' => $params['channel_id'],
                'order_bn' => $params['order_bn'],
                'logistics_code' => $channel['logistics_code'],
                'delivery_id' => isset($params['delivery_id']) ? $params['delivery_id'] : '',
                'delivery_bn' => isset($params['delivery_bn']) ? $params['delivery_bn'] : '',
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
                $yundaRpcObj = kernel::single('logisticsmanager_rpc_request_yunda');
                if($this->isChildBill){
                    $yundaRpcObj->setCurrChildBill($this->childBill_id);
                }
                $result = $yundaRpcObj->setChannelType('yunda')->search_waybill_number($rpcData);
            }
        }
        return $result;
    }
}