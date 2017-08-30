<?php
class logisticsmanager_rpc_request_ems extends logisticsmanager_rpc_request{
    public function bind() {
        $params = array(
            'app' => 'app.applyNodeBind',
            'node_id' => base_shopnode::node_id('ome'),
            'from_certi_id' => base_certificate::certi_id(),
            'callback' => kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>'ems')),
            'sess_callback' => urlencode(kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>'ems'))),
            'api_url' => kernel::base_url(1).kernel::url_prefix().'/api',
            'node_type' => 'ems',
            'to_node' => self::EMS_NODE_ID,
            'shop_name' => 'EMS官方电子面单',
        );
        $token = base_certificate::token();
        $params['certi_ac'] = $this->genSign($params,$token);

        //$api_url = 'http://sws.ex-sandbox.com/api.php';
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';

        $headers = array(
            'Connection' => 5,
        );

        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(5)->post($api_url, $params, $headers);
        $response = json_decode($response,true);
        if($response['res']=='succ' || $response['msg']['errorDescription'] == '绑定关系已存在,不需要重复绑定') {
            return true;
        }
        return false;
    }

    /**
    * 获取EMS官方电子面单
    *
    * @access public
    * @param string $shop_id 店铺ID
    * @return 接口响应结果
    */
    public function get_waybill_number($data) {
        $params = array(
            'sysAccount' => $data['sysAccount'], //客户号
            'passWord' => $data['passWord'], //客户密码
            'businessType' => $data['businessType'], //单据类型
            'billNoAmount' => $data['billNoAmount'], //单据数量
        );
        $method = 'store.waybillprintdata.get';

        $writelog = array(
            'log_type' => 'other',
            'log_title' => '获取EMS官方电子面单',
            'original_bn' => $data['out_biz_code'],
        );

        $callback = array(
            'class' => get_class($this),
            'method' => 'get_waybill_number_callback',
            'params' => array('out_biz_code' => $data['out_biz_code'],'channel_id'=>$data['channel_id']),
        );

        $result = $this->request($method, $params, $callback, 'ems', $writelog);
        return $result;
    }

    public function get_waybill_number_callback($result) {
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();
        $data = $result->get_data();
        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];

        $ret = $this->callback($result);

        $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
        if ($status == 'succ' && $data['assignId']){
            $waybillObj = app::get('logisticsmanager')->model('waybill');
            $channelObj = app::get('logisticsmanager')->model('channel');
            $emsObj = kernel::single('logisticsmanager_waybill_ems');
            $logistics_code = $emsObj->logistics_code($request_params['businessType']);
            //获取单号来源信息
            $cFilter = array(
                'channel_type' => 'ems',
                'logistics_code' => $logistics_code,
                'status'=>'true',
            );
            //$channel = $channelObj->dump($cFilter);
            $channel_id = $callback_params['channel_id'];
            //保存数据
            if($channel_id && $logistics_code) {
                foreach($data['assignId'] as $val){
                    $waybill = array();
                    $waybill = $waybillObj->dump(array('waybill_number'=>$val['billno']),'id');
                    if(!$waybill['id'] && $val['billno']) {
                        $logisticsNo = array(
                            'waybill_number' => $val['billno'],
                            'channel_id' => $channel_id,
                            'logistics_code' => $logistics_code,
                            'status' => 0,
                            'create_time'=>time(),
                        );
                        $waybillObj->insert($logisticsNo);
                    }
                    unset($val,$logisticsNo,$waybill);
                }
                $waybillLogObj->update(array('status'=>'success'),array('log_id'=>$callback_params['out_biz_code']));
            }
        } else {
            $waybillLogObj->update(array('status'=>'fail'),array('log_id'=>$callback_params['out_biz_code']));
        }

        return $ret;
    }

    /**
    * EMS官方电子面单物流回传
    *
    * @access public
    * @param string $shop_id 店铺ID
    * @return 接口响应结果
    */
    public function delivery($data) {
        $params = array(
            'sysAccount' => $data['sysAccount'], //客户号
            'passWord' => $data['passWord'], //客户密码
            'printKind' => $data['printKind'], //打印类型，1为五联单打印，2为热敏打印
            'printDatas' => array(),
        );
        foreach($data['printDatas'] as $val) {
            $params['printDatas']['printData'][] = array(
                'bigAccountDataId' => $val['bigAccountDataId'] ? $val['bigAccountDataId'] : '', //大客户数据的唯一标识，如某电商公司的配货单号 必填
                'billno' => $val['billno'] ? $val['billno'] : '', //详情单号，和配货单号对应 必填
                'scontactor' => $val['scontactor'] ? $val['scontactor'] : '', //寄件人姓名 必填
                'scustMobile' => $val['scustMobile'] ? $val['scustMobile'] : '', //寄件人联系方式1 必填
                'scustTelplus' => $val['scustTelplus'] ? $val['scustTelplus'] : '', //寄件人联系方式2
                'scustPost' => $val['scustPost'] ? $val['scustPost'] : '', //寄件人邮编 必填
                'scustAddr' => $val['scustAddr'] ? $val['scustAddr'] : '', //寄件人地址 必填
                'scustComp' => $val['scustComp'] ? $val['scustComp'] : '', //寄件人公司
                'tcontactor' => $val['tcontactor'] ? $val['tcontactor'] : '', //收件人姓名 必填
                'tcustMobile' => $val['tcustMobile'] ? $val['tcustMobile'] : '', //收件人联系方式1 必填
                'tcustTelplus' => $val['tcustTelplus'] ? $val['tcustTelplus'] : '', //收件人联系方式2
                'tcustPost' => $val['tcustPost'] ? $val['tcustPost'] : '', //收件人邮编 必填
                'tcustAddr' => $val['tcustAddr'] ? $val['tcustAddr'] : '', //收件人地址 必填
                'tcustComp' => $val['tcustComp'] ? $val['tcustComp'] : '', //收件人公司
                'tcustProvince' => $val['tcustProvince'] ? $val['tcustProvince'] : '', //到件省 必填
                'tcustCity' => $val['tcustCity'] ? $val['tcustCity'] : '', //到件市 必填
                'tcustCounty' => $val['tcustCounty'] ? $val['tcustCounty'] : '', //到件县 必填
                'weight' => $val['weight'] ? $val['weight']/1000 : 0.00, //寄件重量
                'length' => $val['length'] ? $val['length'] : 0.00, //物品长度
                'insure' => $val['insure'] ? $val['insure'] : 0.00, //保价
                'cargoType' => $val['cargoType'] ? $val['cargoType'] : '', //内件类型：（文件、物品）
                'remark' => $val['remark'] ? $val['remark'] : '', //备注
                'customerDn' => $val['customerDn'] ? $val['customerDn'] : '', //大客户数据的客户订单号，主要是对于电商客户有用
                'businessType' => $val['businessType'] ? $val['businessType'] : '', //业务类型，1为标准快递，2为代收货款，3为收件人付费，4为经济快递（传数字）



                /*'insurance' => $val['insurance'], //保险
                'fee' => $val['fee'], //小写金额，代收货款和收件人付费不保留小数点；标准快递和经济快递保留两位小数点
                'feeUppercase' => $val['feeUppercase'], //大写金额（代收货款和收件人付费需要填写）
                'cargoDesc' => $val['cargoDesc'], //内件信息，根据货品的实际情况填写（对个别已与EMS和买家达成协议的，可只写货号，不写实际货物名称）
                'deliveryclaim' => $val['deliveryclaim'], //对揽投员的投递要求，填写客户的个性化投递要求
                'productCode' => $val['productCode'], //产品代码
                'blank1' => $val['blank1'], //预留字段1
                'blank2' => $val['blank2'], //预留字段2*/
            );
        }
        $params['printDatas'] = json_encode($params['printDatas']);

        $method = 'store.print.data.create';

        $writelog = array(
            'log_type' => 'other',
            'log_title' => 'EMS官方电子面单物流回传',
            'original_bn' => $data['billno'],
        );

        $callback = array(
            'class' => get_class($this),
            'method' => 'ems_delivery_callback',
            'params' => array('logi_no' => $data['logi_no'],'delivery_id' => $data['delivery_id']),
        );

        $result = $this->request($method, $params, $callback, 'ems', $writelog);
        return $result;
    }

    public function ems_delivery_callback($result) {
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
      
        $ret = $this->callback($result);

        $logisticsLogObj = app::get('logisticsmanager')->model('logistics_log');
        if ($status == 'succ'){
            $logisticsLogObj->update(array('status'=>'success'),array('delivery_id'=>$callback_params['delivery_id'],'logi_no'=>$callback_params['logi_no']));
        } else {
            $logisticsLogObj->update(array('status'=>'fail'),array('delivery_id'=>$callback_params['delivery_id'],'logi_no'=>$callback_params['logi_no']));
        }

        return $ret;
    }
}