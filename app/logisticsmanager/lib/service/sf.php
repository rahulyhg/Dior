<?php

class logisticsmanager_service_sf extends logisticsmanager_service_abstract {
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
            list ($sysAccount, $passWord, $pay_method, $custid) = explode('|||', $channel['shop_id']);
            $orderList = $this->getTradeOrderList($params['delivery_id']);
            $shopInfo = $this->getChannelExtend($params['channel_id']);
//            $first = strpos($shopInfo['area'], ':');
//            $last = strrpos($shopInfo['area'], ':');
//            $pca = substr($shopInfo['area'], $first + 1, $last - $first - 1);
//            list($province, $city, $area) = explode('/', $pca);
            $province = $shopInfo['province'];
            $city = $shopInfo['city'];
            $area = $shopInfo['area'];
            $cargos = $this->getCargos($params['delivery_id']);
            $log_id = $this->getGenId();
            $businessType = logisticsmanager_waybill_sf::getBusinessType($channel['logistics_code']);
            $delivery['net_weight'] = $delivery['net_weight']/1000;
            //请求接口参数
            $rpcData = array(
                'sysAccount' => $sysAccount,
                'passWord' => $passWord,
                'orderid' => $orderList[0],
                'express_type' => $channel['logistics_code'],
                'cp_code' => $channel['logistics_code'],
                'j_company' => $shopInfo['shop_name'],
                'j_contact' => $shopInfo['default_sender'] ? $shopInfo['default_sender'] : '_SYSTEM',
                'j_tel' => $shopInfo['mobile'] ? $shopInfo['mobile'] : ($shopInfo['tel'] ? $shopInfo['tel'] : '_SYSTEM'),
                'j_address' => $shopInfo['address_detail'] ? $province . $city . $area . $shopInfo['address_detail'] : '_SYSTEM',
                'd_company' => $delivery['ship_name'],
                'd_contact' => $delivery['ship_name'],
                'd_tel' => $delivery['ship_mobile'] ? $delivery['ship_mobile'] : $delivery['ship_tel'],
                'd_address' => $delivery['ship_addr'] ? $delivery['ship_province'] . $delivery['ship_city'] . $delivery['ship_district'] . $delivery['ship_addr'] : '_SYSTEM',
                'parcel_quantity' => 1,
                'pay_method' => $pay_method,
                'j_province' => $province,
                'j_city' => $this->isMunicipality($province) ? $province : $city,
                'd_province' => $delivery['ship_province'],
                'd_city' => $this->isMunicipality($delivery['ship_province']) ? $delivery['ship_province'] : $delivery['ship_city'],
                'custid' => $custid,//月卡号
                'cargo' => htmlspecialchars($cargos['cargo']),
                //'cargo_count' => $cargos['cargo_count'],
                //'cargo_unit' => $cargos['cargo_unit'],
                //'cargo_weight' => $cargos['cargo_weight'],
                //'cargo_amount' => $cargos['cargo_amount'],

                'cargo_total_weight' => $delivery['net_weight'] ? sprintf("%.2f", $delivery['net_weight']) : '',

                'out_biz_code' => $log_id,
                'businessType' => $businessType,
                'shop_id' => 'sf',
                'channel_id' => $params['channel_id'],
                'delivery_id' => $params['delivery_id'],
                'delivery_bn' => $delivery['delivery_bn'],
                'logistics_code' => $channel['logistics_code'],
                
            );
            //货到付款
            if ($delivery['is_cod'] == 'true') {
                $orderInfo = $this->getOrderItemInfo($params['delivery_id']);
                $rpcData['sf_cod'] = 'COD';
                $rpcData['sf_cod_value'] = $orderInfo['totalAmount'];
                $rpcData['sf_cod_value1'] = $custid;
            }

            $dlyCorp = $this->getDlyCorp($delivery['logi_id']);
            if ($dlyCorp['protect'] == 'true') {
                $deliveryOrder = $this->getDeliveryOrder($params['delivery_id']);
                $total_amount = 0;
                foreach ($deliveryOrder['order_info'] as $order_id => $orderInfo) {
                    $total_amount += $orderInfo['total_amount'];
                }
                $rpcData['sf_insure'] = 'INSURE';
                $rpcData['sf_insure_value'] = max($total_amount * $dlyCorp['protect_rate'], $dlyCorp['minprice']);
            }
            //重试日志信息
            $logSdf = array(
                'log_id' => $log_id,
                'channel_id' => $params['channel_id'],
                'status' => 'running',
                'create_time' => time(),
                'params' => $rpcData,
            );

            if ($this->insertWaybillLog($logSdf)) {
                $taobaoRpcObj = kernel::single('logisticsmanager_rpc_request_sf');
                if($this->isChildBill){
                    $rpcData['orderid'] = $this->setChildRqOrdNo($rpcData['orderid']);
                    $taobaoRpcObj->setCurrChildBill($this->childBill_id);
                }
                $result = $taobaoRpcObj->setChannelType('sf')->get_waybill_number($rpcData);
            }
        }
        return $result;
    }

    /**
     * 获取货物名称
     * @param Int $delivery_id 发货单ID
     */
    public function getCargos($delivery_id) {
        $deliveryItems = $this->getDeliveryItems($delivery_id);
        $cargo = '';
        $cargo_count = '';
        $cargo_unit = '';
        $cargo_weight = '';
        $cargo_amount = '';
        foreach ($deliveryItems as $item) {
            $cargo .= $item['product_name'] . '/';
            $cargo_count.= $item['number'] . ',';
            //获取商品信息
            $goods = $this->getGoods($item['bn']);
            $unit = '件';
            $cargo_unit .= $unit . ',';
            $weight = $goods['weight'];
            $cargo_weight .= $weight . ',';
            $amount = '100';
            $cargo_amount .= $amount . ',';
        }
        if ($cargo) {
            $cargo = trim($cargo, '/');
        }
        if ($cargo_count) {
            $cargo_count = trim($cargo_count, ',');
        }
        if ($cargo_weight) {
            $cargo_weight = trim($cargo_weight, ',');
        }
        if ($cargo_unit) {
            $cargo_unit = trim($cargo_unit, ',');
        }
        if ($cargo_amount) {
            $cargo_amount = trim($cargo_amount, ',');
        }
        
        $cargos = array(
            'cargo' => $cargo,
            'cargo_count' => $cargo_count,
            'cargo_unit' => $cargo_unit,
            'cargo_weight' => $cargo_weight,
            'cargo_amount' => $cargo_amount,
        );
        return $cargos;
    }

    /**
     * 获取订单信息
     * @param Int $delivery_id 发货单ID
     */
    public function getOrderItemInfo($delivery_id) {
        $orderList = $this->getDeliveryOrder($delivery_id);
        $totalAmount = 0;
        foreach ($orderList['order_info'] as $k => $v) {
            $totalAmount += $v['total_amount'];
        }
        $orderInfo = array(
            'totalAmount' => $totalAmount,
        );
        return $orderInfo;
    }

    /**
     * 搜索运单号
     * Enter description here ...
     * @param unknown_type $params
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
            $businessType = logisticsmanager_waybill_sf::getBusinessType($channel['logistics_code']);
            $log_id = $this->getGenId();
            list ($sysAccount, $passWord, $pay_method, $custid) = explode('|||', $channel['shop_id']);

            //请求接口参数
            $rpcData = array(
                'sysAccount' => $sysAccount,
                'passWord' => $passWord,
                'orderid' => $params['order_bn'],

                'cp_code' => $channel['logistics_code'],
                'out_biz_code' => $log_id,
                'businessType' => $businessType,
                'shop_id' => 'sf',
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
                $sfRpcObj = kernel::single('logisticsmanager_rpc_request_sf');
                if($this->isChildBill){
                    $sfRpcObj->setCurrChildBill($this->childBill_id);
                }
                $result = $sfRpcObj->setChannelType('sf')->search_waybill_number($rpcData);
            }
        }
        return $result;
    }
}