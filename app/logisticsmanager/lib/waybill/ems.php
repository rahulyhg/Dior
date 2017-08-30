<?php
class logisticsmanager_waybill_ems extends logisticsmanager_waybill_abstract implements logisticsmanager_waybill_interface {
    //获取物流公司
    public function logistics($logistics_code) {
        $logistics = array(
            'EMS'=>array('code'=>'EMS','name'=>'普通EMS'),
            'EYB'=>array('code'=>'EYB','name'=>'经济EMS'),
        );

        if(!empty($logistics_code)) {
            return $logistics[$logistics_code];
        }

        return $logistics;
    }

    public function businessType($logistics_code) {
        $businessType = array(
            'EMS'=>1,
            'EYB'=>4,
        );

        if(!empty($logistics_code)) {
            return $businessType[$logistics_code];
        }

        return $businessType;
    }

    //获取物流公司编码
    public function logistics_code($businessType) {
        $logistics_code = array(
            1 => 'EMS',
            4 => 'EYB',
        );

        if(!empty($businessType)) {
            return $logistics_code[$businessType];
        }

        return $logistics_code;
    }

    /**
     * 获取渠道电子面单
     *
     * @return void
     * @author 
     **/
    public function request_waybill(){
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');

        $emsAccount = explode('|||',$this->_channel['shop_id']);

        if($this->_channel['bind_status']=='true' && $emsAccount[0] && $emsAccount[1]) {
            // $emsObj = kernel::single('logisticsmanager_waybill_ems');
            $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
            $log_id = $waybillLogObj->gen_id();

            //请求接口参数
            $rpcData = array(
                'sysAccount'   => $emsAccount[0], //客户号
                'passWord'     => $emsAccount[1], //客户密码
                'businessType' => $this->businessType($this->_channel['logistics_code']),
                'billNoAmount' => 100,
                'out_biz_code' => $log_id,
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
                $emsRpcObj = kernel::single('logisticsmanager_rpc_request_ems');
                $emsRpcObj->get_waybill_number($rpcData);
            }
        }

        $rs['rsp'] = 'succ';

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

        $deliveryObj     = &app::get('ome')->model('delivery');
        $deliveryBillObj = &app::get('ome')->model('delivery_bill');
        $dlyCorpObj      = &app::get('ome')->model('dly_corp');
        $shopObj         = &app::get('ome')->model('shop');
        $channelObj      = &app::get('logisticsmanager')->model('channel');
        $logisticsLogObj = &app::get('logisticsmanager')->model('logistics_log');
        $waybillEmsObj   = kernel::single('logisticsmanager_waybill_ems');

        $delivery = $deliveryObj->dump($delivery_id);
        // $dlyCorp = $dlyCorpObj->dump($delivery['logi_id'],'channel_id,tmpl_type,shop_id');
        // $channel = $channelObj->dump($dlyCorp['channel_id']);

        if($delivery['status']=='succ' && $delivery['logi_no']) {
            // $shop = $shopObj->dump($delivery['shop_id']);
            $consignee = $delivery['consignee'];
            $emsAccount = explode('|||',$this->_channel['shop_id']);

            if($emsAccount && $this->_shop && $consignee) {
                //回填基本信息
                $params = array(
                    'sysAccount'  => $emsAccount[0], //客户号
                    'passWord'    => $emsAccount[1], //客户密码
                    'printKind'   => 2, //打印类型，1为五联单打印，2为热敏打印
                    'printDatas'  => array(),
                    'logi_no'     => $delivery['logi_no'],
                    'delivery_id' => $delivery_id,
                );
                //记录基本收发货信息
                $basicData = array(
                    //'billno' => $delivery['logi_no'], //详情单号，和配货单号对应 必填
                    //'weight' => $delivery['weight'], //寄件重量
                    'bigAccountDataId' => $delivery['logi_no'], //大客户数据的唯一标识，如某电商公司的配货单号 必填
                    'scontactor'       => $this->_shop['default_sender'], //寄件人姓名 必填
                    'scustMobile'      => $this->_shop['mobile'], //寄件人联系方式1 必填
                    'scustTelplus'     => $this->_shop['tel'], //寄件人联系方式2
                    'scustPost'        => $this->_shop['zip'], //寄件人邮编 必填
                    'scustAddr'        => $this->_shop['addr'], //寄件人地址 必填
                    'tcontactor'       => $consignee['name'], //收件人姓名 必填
                    'tcustMobile'      => $consignee['mobile'], //收件人联系方式1 必填
                    'tcustTelplus'     => $consignee['telephone'], //收件人联系方式2
                    'tcustPost'        => $consignee['zip'], //收件人邮编 必填
                    'tcustAddr'        => $consignee['addr'], //收件人地址 必填
                    'tcustProvince'    => $consignee['province'], //到件省 必填
                    'tcustCity'        => $consignee['city'], //到件市 必填
                    'tcustCounty'      => $consignee['district'], //到件县 必填
                    'businessType'     => $this->businessType($this->_channel['logistics_code']),
                );

                //记录主单信息
                $parentData = array(
                    'billno' => $delivery['logi_no'],
                    'weight' => $delivery['weight'],
                );
                $params['printDatas'][] = array_merge($parentData,$basicData);

                //记录补打物流单信息
                $rows = $deliveryBillObj->getList('*',array('delivery_id'=>$delivery_id));
                if($rows) {
                    foreach($rows as $val) {
                        if($val['logi_no'] && $val['status']=='1') {
                            $billData = array(
                                'billno' => $val['logi_no'],
                                'weight' => $val['weight'],
                            );
                            $params['printDatas'][] = array_merge($billData,$basicData);
                        }
                    }
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
                    $emsRpcObj = kernel::single('logisticsmanager_rpc_request_ems');
                    $emsRpcObj->delivery($params);

                    $rs['rsp'] = 'succ';
                    return $rs;
                }
            }
        }

        return $rs;
    }
}