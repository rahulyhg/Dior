<?php

class logisticsmanager_waybill_360buy extends logisticsmanager_waybill_abstract implements logisticsmanager_waybill_interface{
    //获取物流公司
    public function logistics($logistics_code) {
        $logistics = array(
            'SOP'=>array('code'=>'SOP','name'=>'SOP'),
        );

        if(!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }

        return $logistics;
    }

    public function businessType($logistics_code) {
        $businessType = array(
            'SOP'=>1,
        );

        if(!empty($logistics_code)) {
            return $businessType[$logistics_code];
        }

        return $businessType;
    }

    //获取物流公司编码
    public function logistics_code($businessType) {
        $logistics_code = array(
            1 => 'SOP',
        );

        if(!empty($businessType)) {
            return $logistics_code[$businessType];
        }

        return $logistics_code;
    }

    public function request_waybill() {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');

        $jdAccount = explode('|||', $this->_channel['shop_id']);
        if ($jdAccount[0]) {
            // $jdObj = kernel::single('logisticsmanager_waybill_360buy');

            $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
            $log_id = $waybillLogObj->gen_id();
            $rpcData = array(
                'preNum'       => 100,
                'customerCode' => $jdAccount[0],
                'out_biz_code' => $log_id,
                'businessType' => $this->businessType($this->_channel['logistics_code']),
            );

            //重试日志信息
            $logSdf = array(
                'log_id'      => $log_id,
                'channel_id'  => $this->_channel['channel_id'],
                'status'      => 'running',
                'create_time' => time(),
                'params'      => $rpcData,
            );
            if($waybillLogObj->insert($logSdf)) {
                $jdRpcObj = kernel::single('logisticsmanager_rpc_request_360buy');
                $jdRpcObj->get_waybill_number($rpcData);
            }
        }

        $rs['rsp'] = 'succ';

        return $rs;
    }

    /**
     * 获取缓存中的运单号前动作
     *
     * @return void
     * @author 
     **/
    public function pre_get_waybill()
    {
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');
        
        if ($this->_shop['addon']['type'] != 'SOP') {
            $rs['rsp'] = 'fail';
        }

        return $rs;
    }

    /**
    * 回填发货信息
    *
    * @access public
    * @param array $delivery_id 
    * @return void
    */
    public function delivery($delivery_id) {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');

        $deliveryObj       = &app::get('ome')->model('delivery');
        $deliveryBillObj   = &app::get('ome')->model('delivery_bill');
        $dlyCorpObj        = &app::get('ome')->model('dly_corp');
        $shopObj           = &app::get('ome')->model('shop');
        $channelObj        = &app::get('logisticsmanager')->model('channel');
        $logisticsLogObj   = &app::get('logisticsmanager')->model('logistics_log');
        $waybill360buysObj = kernel::single('logisticsmanager_waybill_360buy');

        $delivery = $deliveryObj->dump($delivery_id);
        // $dlyCorp  = $dlyCorpObj->dump($delivery['logi_id'],'channel_id,tmpl_type,shop_id');
        // $channel  = $channelObj->dump($dlyCorp['channel_id']);

        if($delivery['status']=='succ' && $delivery['logi_no']) {
            // $shop = $shopObj->dump($delivery['shop_id']);
            $consignee = $delivery['consignee'];
            $jdAccount = explode('|||',$this->_channel['shop_id']);

            $orderBns = $this->get360buyOrderBns($delivery_id);

            if($jdAccount && $shop && $consignee) {
                //回填基本信息
                $params = array(
                    'logi_no'                        => $delivery['logi_no'],//运单号
                    'billno'                         => $delivery['logi_no'],//运单号
                    'delivery_id'                    => $delivery_id,//发货单号
                    'deliveryId'                     => $delivery['logi_no'],//运单号
                    //                    'salePlat' => '0030001', //销售平台编码     0030001 : 其它平台
                    'salePlat'                       => '0010001', //销售平台编码     0010001 : 京东
                    'customerCode'                   => $jdAccount[0], //商家编码
                    'orderId'                        => $delivery_id,//ERP发货单
                    'thrOrderId'                     => implode(',', $orderBns),//京东订单
                    'senderName'                     => $this->_shop['default_sender'],//寄件人姓名 必填
                    'senderAddress'                  => $this->_shop['addr'],//寄件人地址 必填
                    'senderTel'                      => $this->_shop['tel'],//寄件人电话
                    'senderMobile'                   => $this->_shop['mobile'],//寄件人手机
                    'senderPostcode'                 => $this->_shop['zip'],//寄件人邮编
                    'receiveName'                    => $consignee['name'],//收件人姓名 必填
                    'receiveAddress'                 => $consignee['addr'], //收件人地址 必填
                    'province'                       => $consignee['province'], //到件省
                    'city'                           => $consignee['city'], //到件市 
                    'county'                         => $consignee['district'], //到件县 
                    'receiveTel'                     => $consignee['telephone'],//收件人电话
                    'receiveMobile'                  => $consignee['mobile'],//收件人手机
                    'postcode'                       => $consignee['zip'],//收件人邮编
                    'packageCount'                   => 1,//包裹数量
                    'weight'                         => ($delivery['weight']) > 0 ? sprintf("%.2f", (max(0, $delivery['weight']) / 1000)) : 0,//重量
                    'vloumn'                         => 0,//体积,
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
                    'logi_no'     => $delivery['logi_no'],
                    'delivery_id' => $delivery_id,
                    'channel_id'  => $this->_channel['channel_id'],
                    'status'      => 'running',
                    'create_time' => time(),
                    'params'      => $params,
                );

                if($logisticsLogObj->insert($logSdf)) {
                    //发送回填请求
                    $emsRpcObj = kernel::single('logisticsmanager_rpc_request_360buy');
                    $emsRpcObj->delivery($params);

                    $rs['rsp'] = 'succ';
                    return $rs;
                }
            }
        }

        return $rs;
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
}