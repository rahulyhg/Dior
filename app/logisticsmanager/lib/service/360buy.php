<?php
class logisticsmanager_service_360buy extends logisticsmanager_service_abstract{
    /**
    * 回填发货信息
    *
    * @access public
    * @param array $delivery_id 
    * @return void
    */
    public function delivery($delivery_id) {
        $deliveryObj = &app::get('ome')->model('delivery');
        $deliveryBillObj = &app::get('ome')->model('delivery_bill');
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $shopObj = &app::get('ome')->model('shop');
        $channelObj = &app::get('logisticsmanager')->model('channel');
        $logisticsLogObj = &app::get('logisticsmanager')->model('logistics_log');
        $waybill360buysObj = kernel::single('logisticsmanager_waybill_360buy');

        $delivery = $deliveryObj->dump($delivery_id);
        $dlyCorp = $dlyCorpObj->dump($delivery['logi_id'],'channel_id,tmpl_type,shop_id');
        $channel = $channelObj->dump($dlyCorp['channel_id']);

        if($delivery['status']=='succ' && $delivery['logi_no'] && $channel['channel_type']=='360buy') {
            $shop = $this->getChannelExtend($dlyCorp['channel_id']);
            $consignee = $delivery['consignee'];
            $jdAccount = explode('|||',$channel['shop_id']);
            $orderBns = $this->get360buyOrderBns($delivery_id);

            if($jdAccount  && $consignee) {
                //回填基本信息
                $params = array(
                    'logi_no' => $delivery['logi_no'],//运单号
                    'billno' => $delivery['logi_no'],//运单号
                    'delivery_id' => $delivery_id,//发货单号
                    'deliveryId' => $delivery['logi_no'],//运单号
//                    'salePlat' => '0030001', //销售平台编码     0030001 : 其它平台
                    'salePlat' => '0010001', //销售平台编码     0010001 : 京东
                    'customerCode' => $jdAccount[0], //商家编码
                    'orderId' => $delivery_id,//ERP发货单
                    'thrOrderId' => implode(',', $orderBns),//京东订单
                    'senderName' => $shop['default_sender'],//寄件人姓名 必填
                    'senderAddress' => $shop['address_detail'],//寄件人地址 必填
                    'senderTel' => $shop['tel'],//寄件人电话
                    'senderMobile' => $shop['mobile'],//寄件人手机
                    'senderPostcode' => $shop['zip'],//寄件人邮编
                    'receiveName' => $consignee['name'],//收件人姓名 必填
                    'receiveAddress' => $consignee['addr'], //收件人地址 必填
                    'province' => $consignee['province'], //到件省
                    'city' => $consignee['city'], //到件市 
                    'county' => $consignee['district'], //到件县 
                    'receiveTel' => $consignee['telephone'],//收件人电话
                    'receiveMobile' => $consignee['mobile'],//收件人手机
                    'postcode' => $consignee['zip'],//收件人邮编
                    'packageCount' => $delivery['logi_number'],//包裹数量
                    'weight' => ($delivery['weight']) > 0 ? sprintf("%.2f", (max(0, $delivery['weight']) / 1000)) : 0,//重量
//                    'weight' => ($delivery['weight'] || $delivery['net_weight']) > 0 ? sprintf("%.2f", (max(0, $delivery['weight'] + $delivery['net_weight']) / 1000)) : 0,//重量
                    'vloumn' => 0,//体积,
                    'shop_id'=>$jdAccount[1],
                );
                //货到付款
                if ($delivery['is_cod'] == 'true') {
                    $params['collectionValue'] = 1;
                    $params['collectionMoney'] = $this->getPayMoney($delivery_id);
                }
                else {
                    $params['collectionValue'] = 0;
                }
            }

            if($params) {
                //记录回填日志
                $logSdf = array(
                    'logi_no' => $delivery['logi_no'],
                    'delivery_id' => $delivery_id,
                    'channel_id' => $channel['channel_id'],
                    'channel_type'=>$channel['channel_type'],
                    'status' => 'running',
                    'create_time' => time(),
                    'params' => $params,
                );
                if($logisticsLogObj->insert($logSdf)) {
                    //发送回填请求
                    $params['log_id'] = $logSdf['log_id'];
                    $emsRpcObj = kernel::single('logisticsmanager_rpc_request_360buy');
                    $emsRpcObj->setChannelType('360buy')->delivery($params);
                    return true;
                }
            }
        }

        return false;
    }
    /**
     * 获得京东订单编号
     * @param Int $delivery_id 发货单号
     */
    public function get360buyOrderBns($delivery_id) {
        $orderBns = array();
        $orderIds = $this->getOrderIds($delivery_id);
        //订单order_bn
        if ($orderIds) {
            $sql = "SELECT `order_bn` FROM `sdb_ome_orders` WHERE `order_id` IN (". implode(',', $orderIds) .") and `shop_type` = '360buy'";
            $orderInfo = kernel::database()->select($sql);
            if ($orderInfo) {
                foreach ($orderInfo as $v) {
                    $orderBns[] = $v['order_bn'];
                } 
            }
        }
        return $orderBns;
    }

    /**
     * 获得订单
     * @param Int $delivery_id 发货单号
     */
    public function getOrderIds($delivery_id) {
        $orderIds = array();
        $sql = "SELECT `order_id` FROM `sdb_ome_delivery_order` WHERE `delivery_id` = {$delivery_id}";
        $deliveryOrderInfo = kernel::database()->select($sql);
        if ($deliveryOrderInfo) {
            foreach ($deliveryOrderInfo as $v) {
                $orderIds[] = $v['order_id'];
            }
        }
        return $orderIds;
    }
    /**
     * 获取货到付款费用
     * @param Int $delivery_id 发货单号
     */
    public function getPayMoney($delivery_id) {
        $money = 0;
        $orderIds = $this->getOrderIds($delivery_id);
        $orderExtendObj = &app::get('ome')->model('order_extend');
        $orderExtends = $orderExtendObj->getList('receivable', array('order_id' => $orderIds));
        foreach ($orderExtends as $extend) {
            $money += $extend['receivable'];
        }
        return $money;
    }

    /**
     * 获取运单号
     * @param Mix $data 发送数据
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
        }else {
        
            $channel = $this->getChannel($params['channel_id']);
            $jdAccount = explode('|||', $channel['shop_id']);
            if ($channel && $jdAccount[0]) {
                $jdObj = kernel::single('logisticsmanager_waybill_360buy');
                
                $rpcData = array(
                    'preNum' => 1,
                    'customerCode' => $jdAccount[0],
                    'delivery_id' => $params['delivery_id'],
                    'delivery_bn'=>$delivery['delivery_bn'],
                    'businessType' => $jdObj->businessType($channel['logistics_code']),
                    'shop_id'=>$jdAccount[1],
                    'channel_id'=>$params['channel_id'],
                    'logistics_code'=>$channel['logistics_code'],
                );
                
                $jdRpcObj = kernel::single('logisticsmanager_rpc_request_360buy');
                $result = $jdRpcObj->setChannelType('360buy')->get_waybill_number($rpcData);
            }
        
        }
        return $result;
    }
}