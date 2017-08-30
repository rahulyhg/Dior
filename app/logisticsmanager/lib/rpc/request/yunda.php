<?php

class logisticsmanager_rpc_request_yunda extends logisticsmanager_rpc_abstract {
    /**
     * @var String $node_type 结点类型
     */
    public $node_type = 'yunda';
    /**
     * @var String $to_node 结点编号
     */
    public $to_node = '1273396838';
    /**
     * @var String $shop_name 店铺名称
     */
    public $shop_name = '韵达官方电子面单';


    public function get_waybill_number($data) {
        $params = array(
            'sysAccount' => $data['sysAccount'],
            'passWord' => $data['passWord'],
            'version' => $data['version'],
            'request' => $data['request'],
            'orders' => $data['orders'],
        );

        $method = 'store.yd.orderservice';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '获取韵达面单_' . $data['cp_code'],
            'original_bn' => $data['out_biz_code'],
        );
        $callback = array();
        $result = $this->request($method, $params, $callback, $data['shop_id'], $writelog);
        if (empty($callback) && $result) {
            $result = $this->get_waybill_number_process($result, $data);
        }
        return $result;
    }

    /**
     * 获取运单处理
     * @param Array $data 返回数据
     */
    public function get_waybill_number_process($result , $params) {
        //状态
        $status = isset($result['rsp']) ? $result['rsp'] : '';
        $data = empty($result['data']) ? '' : json_decode($result['data'], true);
        $ret = $this->rpc_log($result);

        if ($status == 'succ' && !empty($data['responses'])) {
            foreach ($data['responses'] as $respone) {
                $waybill_code = $respone['mail_no'];
                if ($waybill_code) {
                    //$params['waybill_status'] = '0';//先无视传过来的值直接为0
                    if ($this->insertWaybillCode($waybill_code, $params)) {
                        $updata = array('status' => 'success');
                        $this->updateDeliveryLogino($params['delivery_id'], $waybill_code);
                        $waybillCodeArr[] = array(
                            'logi_no' => $waybill_code,
                            'delivery_id' => $params['delivery_id'],
                            'delivery_bn' => $params['delivery_bn'],
                        );
                        //获取物流单信息
                        $waybill = $this->getWayBill($waybill_code, $params);
                        if ($waybill) {
                            //订单客户唯一序号
                            $order_serial_no = $respone['order_serial_no'];
                            //pdf信息
                            $pdf_info = json_decode($respone['pdf_info'], true);
                            //运单barcode
                            $mailno_barcode = $pdf_info[0][0]['mailno_barcode'];
                            //二维码信息
                            $qrcode = $pdf_info[0][0]['qrcode'];
                            //大头笔
                            $position = $pdf_info[0][0]['position'];
                            //大头笔编码
                            $position_no = $pdf_info[0][0]['position_no'];
                            //集包地
                            $package_wdjc = $pdf_info[0][0]['package_wdjc'];
                            //集包地编码
                            $package_wd = $pdf_info[0][0]['package_wd'];
                            //json包
                            $json_packet = $respone['pdf_info'];
                            $waybillExtned = array(
                                'waybill_id' => $waybill['id'],
                                'mailno_barcode' => $mailno_barcode,
                                'qrcode' => $qrcode,
                                'position' => $position,
                                'position_no' => $position_no,
                                'package_wdjc' => $package_wdjc,
                                'package_wd' => $package_wd,
                                'json_packet' => $json_packet,
                            );
                            //保存电子面单扩展信息
                            $this->saveWaybillExtend($waybillExtned);
                        }
                    }
                    else {
                        $updata = array('status' => 'fail');
                    }
                    $filter = array('log_id' => $params['out_biz_code']);
                    $this->updateWaybillLog($updata, $filter);
                }
            }
        }
        elseif (isset($result['err_msg']) && $result['err_msg'] == '更新订单请使用更新接口') {
             //清空日志编号 （重复下单）
             $this->emptyGenId();
             $yundaObj = kernel::single('logisticsmanager_service_yunda');
             $searchParams = array(
                 'order_bn' => $params['orderid'],
                 'channel_id' => $params['channel_id'],
                 'delivery_id' => $params['delivery_id'],
                 'delivery_bn' => $params['delivery_bn'],
             );
             $searchResult = $yundaObj->search_waybill_number($searchParams);
             if ($searchResult['rsp'] == 'succ') {
                 $waybillCodeArr = $searchResult['data'];
             }
             $ret = $searchResult;
        }
        else {
            $waybillCodeArr[] = array(
                'delivery_id' => $params['delivery_id'],
                'delivery_bn' => $params['delivery_bn'],
            );
        }
        $ret['data'] = $waybillCodeArr;
        $this->emptyGenId();
        return $ret;
    }

    /**
     * 搜索订单信息
     * @param Array $data 搜索信息
     */
    public function search_waybill_number($data) {
        $params = array(
            'sysAccount' => $data['sysAccount'],
            'passWord' => $data['passWord'],
            'version' => $data['version'],
            'request' => $data['request'],
            'orders' => $data['orders'],
        );
        $method = 'store.yd.searchorderservice';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '搜索韵达面单_' . $data['cp_code'],
            'original_bn' => $data['out_biz_code'],
        );
        $callback = array();
        $result = $this->request($method, $params, $callback, $data['shop_id'], $writelog);
        if (empty($callback) && $result && $data['delivery_id']) {
            $result = $this->search_waybill_number_process($result, $data);
        }
        return $result;
    }

    /**
     * Enter description here ...
     * @param Array $result 返回数据
     * @param Array $params 搜索信息
     */
    public function search_waybill_number_process($result, $params) {
        //状态
        $status = isset($result['rsp']) ? $result['rsp'] : '';
        $data = empty($result['data']) ? '' : json_decode($result['data'], true);
        $ret = $this->rpc_log($result);
        if ($status == 'succ' && !empty($data['responses'])) {
            foreach ($data['responses'] as $respone) {
                $waybill_code = $respone['mailno'];
                if ($this->insertWaybillCode($waybill_code, $params)) {
                    $updata = array('status' => 'success');
                    $this->updateDeliveryLogino($params['delivery_id'], $waybill_code);
                    $waybillCodeArr[] = array(
                        'logi_no' => $waybill_code,
                        'delivery_id' => $params['delivery_id'],
                        'delivery_bn' => $params['delivery_bn'],
                    );
                    //获取物流单信息
                    $waybill = $this->getWayBill($waybill_code, $params);
                    if ($waybill) {
                        //订单客户唯一序号
                        $order_serial_no = $respone['order_serial_no'];
                        //pdf信息
                        $pdf_info = json_decode($respone['json_data'], true);
                        //运单barcode
                        $mailno_barcode = $pdf_info[0][0]['mailno_barcode'];
                        //二维码信息
                        $qrcode = $pdf_info[0][0]['qrcode'];
                        //大头笔
                        $position = $pdf_info[0][0]['position'];
                        //大头笔编码
                        $position_no = $pdf_info[0][0]['position_no'];
                        //集包地
                        $package_wdjc = $pdf_info[0][0]['package_wdjc'];
                        //集包地编码
                        $package_wd = $pdf_info[0][0]['package_wd'];
                        //json包
                        $json_packet = $respone['json_data'];
                        $waybillExtned = array(
                            'waybill_id' => $waybill['id'],
                            'mailno_barcode' => $mailno_barcode,
                            'qrcode' => $qrcode,
                            'position' => $position,
                            'position_no' => $position_no,
                            'package_wdjc' => $package_wdjc,
                            'package_wd' => $package_wd,
                            'json_packet' => $json_packet,
                        );
                        //保存电子面单扩展信息
                        $this->saveWaybillExtend($waybillExtned, true);
                    }
                }
                else {
                    $updata = array('status' => 'fail');
                }
                $filter = array('log_id' => $params['out_biz_code']);
                $this->updateWaybillLog($updata, $filter);
            }
        }
        else {
            $waybillCodeArr[] = array(
                'delivery_id' => $params['delivery_id'],
                'delivery_bn' => $params['delivery_bn'],
            );
        }
        $ret['data'] = $waybillCodeArr;
        $this->emptyGenId();
        return $ret;
    }
}