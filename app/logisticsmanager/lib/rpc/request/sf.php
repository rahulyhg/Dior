<?php

class logisticsmanager_rpc_request_sf extends logisticsmanager_rpc_abstract {
    /**
     * @var String $node_type 结点类型
     */
    public $node_type = 'sf';
    /**
     * @var String $to_node 结点编号
     */
    public $to_node = '1588336732';
    /**
     * @var String $shop_name 店铺名称
     */
    public $shop_name = '顺丰官方电子面单';

    /**
     * 获取运单号
     * @param Mix $data 发送数据
     */
    public function get_waybill_number($data) {
            $params = array(
                'sysAccount' => $data['sysAccount'],
                'passWord' => $data['passWord'],
                'orderid' => $data['orderid'],
                'express_type' => $data['express_type'], 
                'j_company' => $data['j_company'],
                'j_contact' => $data['j_contact'],
                'j_tel' => $data['j_tel'],
                'j_address' => $data['j_address'],
                'd_company' => $data['d_company'],
                'd_contact' => $data['d_contact'],
                'd_tel' => $data['d_tel'],
                'd_address' => $data['d_address'],
                'parcel_quantity' => $data['parcel_quantity'],
                'pay_method' => $data['pay_method'],
                'j_province' => $data['j_province'],
                'j_city' => $data['j_city'],
                'd_province' => $data['d_province'],
                'd_city' => $data['d_city'],
                'cargo' => $data['cargo'],
                'custid' => $data['custid'],
                //'cargo_count' => $data['cargo_count'],
                //'cargo_unit' => $data['cargo_unit'],
                //'cargo_weight' => $data['cargo_weight'],
                //'cargo_amount' => $data['cargo_amount'],
                'cargo_total_weight' => $data['cargo_total_weight'],
            );
            if (isset($data['sf_cod'])) {
                $params['sf_cod'] = $data['sf_cod'];
                $params['sf_cod_value'] = $data['sf_cod_value'];
                $params['sf_cod_value1'] = $data['sf_cod_value1'];
            }
            if (isset($data['sf_insure']) && $data['sf_insure'] == 'INSURE') {
                $params['sf_insure'] = $data['sf_insure'];
                $params['sf_insure_value'] = $data['sf_insure_value'];
            }
            $method = 'store.sf.orderservice';
            $writelog = array(
                'log_type' => 'other',
                'log_title' => '获取顺丰面单_' . $data['cp_code'],
                'original_bn' => $data['out_biz_code'],
            );
            $callback = array();
            $result = $this->request($method, $params, $callback, $data['shop_id'], $writelog,30);
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

        if ($status == 'succ' && !empty($data['mailno'])) {
            $waybill_code = $data['mailno'];
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
        elseif ($result['res'] == '8016') {
            //清空日志编号 （重复下单）
            $this->emptyGenId();
            $sfObj = kernel::single('logisticsmanager_service_sf');
            $searchParams = array(
                'order_bn' => $params['orderid'],
                'channel_id' => $params['channel_id'],
                'delivery_id' => $params['delivery_id'],
                'delivery_bn' => $params['delivery_bn'],
            );
            $searchResult = $sfObj->search_waybill_number($searchParams);
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
            'orderid' => $data['orderid'],
        );
        $method = 'store.sf.ordersearchservice';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '搜索顺丰面单_' . $data['cp_code'],
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
        if ($status == 'succ' && !empty($data['mailno'])) {
            $waybill_code = $data['mailno'];
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