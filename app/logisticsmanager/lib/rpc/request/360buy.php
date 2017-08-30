<?php
class logisticsmanager_rpc_request_360buy extends logisticsmanager_rpc_abstract {

    /**
     * 京东电子面单
    * @access public
    * @param string $shop_id 店铺ID
    * @return 接口响应结果
     */
    public function get_waybill_number($data) {
        $params = array(
            'preNum' => $data['preNum'], //运单量数据量
            'customerCode' => $data['customerCode'], //商家编码
        );
        $method = 'store.etms.waybillcode.get';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '获取京东电子面单',
            'original_bn' => $data['out_biz_code'],
        );
        $callback = array(
//            'class' => get_class($this),
//            'method' => 'get_waybill_number_callback',
//            'params' => array('out_biz_code' => $data['out_biz_code']),
        );
        $result = $this->request($method, $params, $callback, $data['shop_id'], $writelog);

        if (empty($callback) && $result) {
            $result = $this->get_waybill_number_process($result, $data);
        }
        return $result;
    }
    /**
    * 京东电子面单物流回传
    *
    * @access public
    * @param string $shop_id 店铺ID
    * @return 接口响应结果
    */
    public function get_waybill_number_callback($result,$params) {
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();
        $data = $result->get_data();
        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];
        $ret = $this->callback($result);
        $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');

        if ($status == 'succ' && $data['resultInfo']['deliveryIdList'] > 0){
            $waybillObj = app::get('logisticsmanager')->model('waybill');
            $channelObj = app::get('logisticsmanager')->model('channel');
            $emsObj = kernel::single('logisticsmanager_waybill_360buy');
            $logistics_code = 'SOP';
            //获取单号来源信息
            $cFilter = array(
                'channel_type' => '360buy',
                'logistics_code' => $logistics_code,
                'status'=>'true',
            );
            $channel = $channelObj->dump($cFilter);

            //保存数据
            if($channel['channel_id']) {
                foreach($data['resultInfo']['deliveryIdList'] as $val){
                    $waybill = array();
                    $waybill = $waybillObj->dump(array('waybill_number'=>$val, 'channel_id' => $channel['channel_id']), 'id');
                    if(!$waybill['id'] && $val) {
                        $logisticsNo = array(
                            'waybill_number' => $val,
                            'channel_id' => $channel['channel_id'],
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
    * 获取运单处理
    *
    */
    public function get_waybill_number_process($result , $params) {
        //状态
        $status = isset($result['rsp']) ? $result['rsp'] : '';
        $data = empty($result['data']) ? '' : json_decode($result['data'], true);
        $ret = $this->rpc_log($result);
        $waybillCodeArr = array();
        if ($status == 'succ' && count($data['resultInfo']['deliveryIdList']) > 0) {
            foreach ($data['resultInfo']['deliveryIdList'] as $k => $v) {
                $waybill_code = $v;
                if ($waybill_code) {
                    if ($this->insertWaybillCode($waybill_code, $params)) {
                        $updata = array('status' => 'success');
                        $this->updateDeliveryLogino($params['delivery_id'], $waybill_code);
                        $waybillCodeArr[] = array(
                            'logi_no' => $waybill_code,
                            'delivery_id' => $params['delivery_id'],
                            'delivery_bn' => $params['delivery_bn'],
                        );
                       
                    }
                    else {
                        $updata = array('status' => 'fail');
                    }
                    $filter = array('log_id' => $params['out_biz_code']);
                    $this->updateWaybillLog($updata, $filter);
                }
            }
        }
        else {
            $waybillCodeArr[] = array(
                'delivery_id' => $params['delivery_id'],
                'delivery_bn' => $params['delivery_bn'],
            );
        }
        $ret['data'] = $waybillCodeArr;
        return $ret;

    }
    /**
    * 京东电面单物流回传
    *
    * @access public
    * @param string $shop_id 店铺ID
    * @return 接口响应结果
    */
    public function delivery($data) {
        $params = array();
        $params['deliveryId'] = $data['deliveryId'];//运单号
        $params['salePlat'] = $data['salePlat'];//销售平台编码
        $params['customerCode'] = $data['customerCode'];//商家编码
        $params['orderId'] = $data['orderId'];//ERP发货单
        $params['thrOrderId'] = substr($data['thrOrderId'],0,98);//京东订单  不能超过100所以先截取
        $params['senderName'] = $data['senderName'];//寄件人姓名 必填
        $params['senderAddress'] = $data['senderAddress'];//寄件人地址 必填
        if ($data['senderTel']) {
            $params['senderTel'] = $data['senderTel'];//寄件人电话
        }
        if ($data['senderMobile']) {
            $params['senderMobile'] = $data['senderMobile'];//寄件人手机
        }
        if ($data['senderPostcode']) {
            $params['senderPostcode'] = $data['senderPostcode'];//寄件人邮编
        }
        $params['receiveName'] = $data['receiveName'];//收件人姓名 必填
        $params['receiveAddress'] = $data['receiveAddress'];//收件人地址 必填
        if ($data['province']) {
            $params['province'] = $data['province'];//收件人省 
        }
        if ($data['city']) {
            $params['city'] = $data['city'];//收件人市
        }
        if ($data['county']) {
            $params['county '] = $data['county'];//收件人县
        }
        if ($data['receiveTel']) {
            $params['receiveTel '] = $data['receiveTel '];//收件人电话
        }
        if ($data['receiveMobile']) {
            $params['receiveMobile'] = $data['receiveMobile'];//收件人手机
        }
        if ($data['postcode']) {
            $params['postcode'] = $data['postcode'];//收件人邮编
        }
        $params['packageCount'] = $data['packageCount'];//包裹数量
        $params['weight'] = $data['weight'];//重量
        $params['vloumn'] = $data['vloumn'];//体积
        //是否代收货款
        if ($data['collectionValue'] == 1) {
            $params['collectionValue'] = $data['collectionValue'];//是否代收货款
            $params['collectionMoney'] = $data['collectionMoney'];//代收货款金额
        }
        else {
            $params['collectionValue'] = $data['collectionValue'];//是否代收货款
        }
        $method = 'store.etms.waybill.send';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '京东电子面单物流回传',
            'original_bn' => $data['billno'],
        );
        $callback = array(
//            'class' => get_class($this),
//            'method' => 'delivery_360buy_callback',
//            'params' => array('logi_no' => $data['logi_no'], 'delivery_id' => $data['delivery_id']),
        );
        $result = $this->request($method, $params, $callback, $data['shop_id'], $writelog);
//        $result = $this->request($method, $params, $callback, '360buy', $writelog);
//        return $result;
        $sync_callback = array();
        //$result = $this->call($method, $params, $sync_callback, '360buy', $writelog);
        if ($result && empty($callback)) {
            $ret = $this->deliveryResult($result, $data);
        }
        
        return true;
    }
    /**
     * 处理同步发货结果
     * @param Array $result 操作结果
     * @param Array $callback 调用日志信息
     */
    public function deliveryResult($result, $callbackLog) {
        $status = isset($result['rsp']) ? $result['rsp'] : '';
        $data = empty($result['data']) ? '' : json_decode($result['data'], true);
        $ret = $this->rpc_log($result);
        $logisticsLogObj = app::get('logisticsmanager')->model('logistics_log');
        if ($status == 'succ'){
            $logisticsLogObj->update(array('status'=>'success'),array('delivery_id'=>$callback_params['delivery_id'],'logi_no'=>$callback_params['logi_no']));
        } else {
            $logisticsLogObj->update(array('status'=>'fail'),array('delivery_id'=>$callback_params['delivery_id'],'logi_no'=>$callback_params['logi_no']));
        }
        
        return $ret;
    }
    /**
     * 同步日志
     * @param Array $result 操作结果
     * @param Array $callback 调用日志信息
     */
    public function syncLog($result, $callbackLog) {
        //状态
        $status = isset($result['rsp']) ? $result['rsp'] : '';
        //错误信息
        $err_msg = isset($result['err_msg']) ? $result['err_msg'] : '';
        //信息 
        $msg = isset($result['msg']) ? $result['msg'] : '';
        //msg_id
        $msg_id = isset($result['msg_id']) ? $result['msg_id'] : '';
        //数据
        $data = (isset($result['data']) && $result['data']) ? ($status != 'succ' ? $result['data'] : json_decode($result['data'], true) ) : '';
        if($status == 'succ'){
            $api_status = 'success';
        }else{
            $api_status = 'fail';
        }
        if($msg !=''){
            $msg = '('.$msg.')'.$err_msg;
        }
        $rsp  ='succ';
        if ($status != 'succ' && $status != 'fail' ){
            $msg = 'rsp:'.$status .'res:'. $msg. 'data:'. $data;
            $rsp = 'fail';
        }
        //错误等级
        if (is_array($data) && isset($data['error_level']) && !empty($data['error_level'])){
            $addon['error_lv'] = $data['error_level'];
        }
        $log_id = $callbackLog['log_id'];
        $apiLogObj = &app::get('ome')->model('api_log');
        $apiLogObj->update_log($log_id, $msg, $api_status, null, $addon);
        if ($msg_id) {
            $update_data = array('msg_id' => $msg_id);
            $update_filter = array('log_id'=>$log_id);
            $bssss = $apiLogObj->update($update_data, $update_filter);
        }
        return array('rsp'=>$rsp, 'res'=>$msg, 'msg_id'=>$msg_id);
    }
    public function delivery_360buy_callback($result) {
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