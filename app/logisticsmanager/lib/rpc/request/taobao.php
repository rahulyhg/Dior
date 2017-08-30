<?php

class logisticsmanager_rpc_request_taobao extends logisticsmanager_rpc_abstract {

    /**
     * 获取运单号
     * @param Mix $data 发送数据
     */
    public function get_waybill_number($data) {
        $params = array(
            'cp_code' => $data['cp_code'],
            'shipping_address' => json_encode($data['shipping_address']),
            'trade_order_info_cols' => json_encode($data['trade_order_info_cols']),
        );
        $method = 'store.wlb.waybill.i.get';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '获取淘宝云栈面单_' . $data['cp_code'],
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
        $waybillCodeArr = array();
        if ($status == 'succ' && count($data['waybill_apply_new_info']) > 0) {
            foreach ($data['waybill_apply_new_info'] as $k => $v) {
                $waybill_code = $v['waybill_code'];
                if ($waybill_code) {
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
                            $waybillExtned = array(
                                'waybill_id' => $waybill['id'],
                                'mailno_barcode' => '',
                                'qrcode' => '',
                                'position' => $v['short_address'],
                                'position_no' => '',
                                'package_wdjc' => '',
                                'package_wd' => '',
                                'json_packet' => '',
                                'package_id'=>$v['trade_order_info']['package_id'],
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
     * 取消面单号
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function cancel_billno($data) {
        
        $method = 'store.wlb.waybill.cancel';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '淘宝云栈电子面单取消',
            'original_bn' => $data['billno'],
            
        );
        $channel_id = $data['channel_id'];
        $serviceObj = kernel::single('logisticsmanager_service_taobao');
        $delivery_id = $data['delivery_id'];

        
        $deliveryObj = &app::get('ome')->model('delivery');
        $delivery = $serviceObj->getDelivery($delivery_id);
        $trade_order_list = array($delivery['delivery_bn']);
        $channel = $serviceObj->getChannel($data['channel_id']);
        $shipping_address = $serviceObj->getShippingAddress($data['channel_id']);
        $waybill_extend = $serviceObj->getWaybillExtend(array('logi_no'=>$data['billno'],'channel_id'=>$data['channel_id']));
        //$trade_order_list = implode(',',$trade_order_list);
        $params = array(
            'trade_order_list'=>json_encode($trade_order_list),
            'cp_code'=>$channel['logistics_code'],
            'waybill_code'=>$data['billno'],
            'package_id'=>$delivery['delivery_bn'],
            'real_user_id'=>$shipping_address['seller_id'],
        
        );

        $callback = array(
            'class' => get_class($this),
            'method' => 'cancel_billno_callback',
        );
        $this->emptyGenId();
        $result = $this->request($method, $params, $callback, $channel['shop_id'], $writelog);
        if (empty($callback) && $result) {
                $this->rpc_log($result);
        }else{
            return false;
        }

        return $result;
    }

    
    /**
     * 获取订购地址
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_ship_address($params)
    {
        $rs = array('rsp'=>'fail','msg'=>'获取失败');
         $shop_id = $params['shop_id'];
        $method = 'store.wlb.waybill.i.search';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '淘宝订购地址获取',
            'original_bn' => $data['billno'],
            
        );
        
        unset($params['shop_id']);
        $callback = array(

        );
        $this->emptyGenId();
        $result = $this->request($method, $params, $callback, $shop_id, $writelog);

        if (empty($callback) && $result) {
                $this->get_ship_address_process($result,$params);
        }
        if ($result['rsp']=='succ') {
            $rs = array('rsp'=>'succ','msg'=>'获取成功');
        }
        return $rs;
    }

    public function get_ship_address_process($result , $params) {
        //状态
        $status = isset($result['rsp']) ? $result['rsp'] : '';
        $data = empty($result['data']) ? '' : json_decode($result['data'], true);
        $ret = $this->rpc_log($result);

        if ($status == 'succ' ) {
               $data = $data['waybill_apply_subscription_info'][0]['branch_account_cols']['waybill_branch_account'][0];
               $address = $data['shipp_address_cols']['waybill_address'][0];
               $channel_id = $params['channel_id'];
               $process_params = array(
                'cancel_quantity'=>$data['cancel_quantity'],
                'allocated_quantity'=>$data['allocated_quantity'],
                'province'=>$address['province'],
                'city'=>$address['city'],
                'address_detail'=>$address['address_detail'],
                'waybill_address_id'=>$address['waybill_address_id'],
                'area'=>$address['area'],
                'channel_id'=>$channel_id,
                'print_quantity'=>$data['print_quantity'],
                'seller_id'=>$data['seller_id'],
               );
               $extendObj = app::get('logisticsmanager')->model('channel_extend');
               $extend = $extendObj->dump(array('channel_id'=>$channel_id),'id');
               if ($extend) {
                   $process_params['id'] = $extend['id'];
               }

               $extendObj->save($process_params);
        }else {
            
        }
       
        
    }

    /**
    * 云栈面单确认
    */
    public function delivery($params) {
        $method = 'store.wlb.waybill.print';

        $writelog = array(
            'log_type' => 'other',
            'log_title' => '淘宝云栈官方电子面单打印确认',
            'original_bn' => $params['billno'],
        );

        $callback = array(
            'class' => get_class($this),
            'method' => 'delivery_callback',
        );

        $result = $this->request($method, $params, $callback,$params['shop_id'], $writelog);
        if (empty($callback) && $result) {
                $this->rpc_log($result);
        }else{
            return false;
        }
        return $result;

    }

     
    /**
     * 淘宝打印确认回调
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function delivery_callback($result)
    {
        $ret = $this->callback($result);
        return true;
    }

    
    /**
     * 取消单号回调.
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function cancel_billno_callback($result)
    {

        $ret = $this->callback($result);
        return true;
    }
}
