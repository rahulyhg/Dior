<?php
class logisticsmanager_service_ems extends logisticsmanager_service_abstract{
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
        $waybillEmsObj = kernel::single('logisticsmanager_waybill_ems');

        $delivery = $deliveryObj->dump($delivery_id);
        $dlyCorp = $dlyCorpObj->dump($delivery['logi_id'],'channel_id,tmpl_type,shop_id');
        $channel = $channelObj->dump($dlyCorp['channel_id']);
        if($delivery['status']=='succ' && $delivery['logi_no'] && $channel['channel_type']=='ems') {
            $shop =$this->getChannelExtend($dlyCorp['channel_id']);
            $consignee = $delivery['consignee'];
            $emsAccount = explode('|||',$channel['shop_id']);

            if($emsAccount  && $consignee) {
                //回填基本信息
                $params = array(
                    'sysAccount' => $emsAccount[0], //客户号
                    'passWord' => $emsAccount[1], //客户密码
                    'printKind' => 2, //打印类型，1为五联单打印，2为热敏打印
                    'printDatas' => array(),
                    'logi_no' => $delivery['logi_no'],
                    'delivery_id' => $delivery_id,
                );
                //记录基本收发货信息
                $basicData = array(
                    //'billno' => $delivery['logi_no'], //详情单号，和配货单号对应 必填
                    //'weight' => $delivery['weight'], //寄件重量
                    'bigAccountDataId' => $delivery['logi_no'], //大客户数据的唯一标识，如某电商公司的配货单号 必填
                    'scontactor' => $shop['default_sender'], //寄件人姓名 必填
                    'scustMobile' => $shop['mobile'], //寄件人联系方式1 必填
                    'scustTelplus' => $shop['tel'], //寄件人联系方式2
                    'scustPost' => $shop['zip'], //寄件人邮编 必填
                    'scustAddr' => $shop['address_detail'], //寄件人地址 必填
                    'tcontactor' => $consignee['name'], //收件人姓名 必填
                    'tcustMobile' => $consignee['mobile'], //收件人联系方式1 必填
                    'tcustTelplus' => $consignee['telephone'], //收件人联系方式2
                    'tcustPost' => $consignee['zip'], //收件人邮编 必填
                    'tcustAddr' => $consignee['addr'], //收件人地址 必填
                    'tcustProvince' => $consignee['province'], //到件省 必填
                    'tcustCity' => $consignee['city'], //到件市 必填
                    'tcustCounty' => $consignee['district'], //到件县 必填
                    'businessType' => $waybillEmsObj->businessType($channel['logistics_code']),
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
                    $emsRpcObj = kernel::single('logisticsmanager_rpc_request_ems');
                    $emsRpcObj->delivery($params);
                    return true;
                }
            }
        }

        return false;
    }
}