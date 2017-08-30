<?php
class logisticsmanager_service_sto extends logisticsmanager_service_abstract{
    
    
    /**
    * 申通官方电子面单物流回传
    *
    * @access public
    * @param string $shop_id 店铺ID
    * @return 接口响应结果
    */
    public function delivery($delivery) {
        $deliveryObj = &app::get('ome')->model('delivery');
        $deliveryBillObj = &app::get('ome')->model('delivery_bill');
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $shopObj = &app::get('ome')->model('shop');
        $channelObj = &app::get('logisticsmanager')->model('channel');
        $logisticsLogObj = &app::get('logisticsmanager')->model('logistics_log');
        $waybillstoObj = kernel::single('logisticsmanager_waybill_sto');
        $delivery_id = $delivery['delivery_id'];
        $deliveryinfo = $deliveryObj->dump($delivery_id,'ship_name,ship_area,ship_province,ship_city,ship_district,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,logi_id,shop_id');
        $dlyCorp = $dlyCorpObj->dump($deliveryinfo['logi_id'],'channel_id,tmpl_type,shop_id');
        $channel = $channelObj->dump($dlyCorp['channel_id']);
        $deliveryItems = $this->getDeliveryItems($delivery_id);
        $pObj = &app::get('ome')->model('products');
        $product_name = '';
        foreach ($deliveryItems as $item) {
            $productInfo= $pObj->dump(array('bn'=>$item['bn']),'name');
            
            $product_name = $productInfo['name'];
            break;
        }
        
        
        $params = array();
        if( $channel['channel_type']=='sto') {
            $shopInfo = $this->getChannelExtend($dlyCorp['channel_id']);
            
            $consignee = $deliveryinfo['consignee'];
            $Account = explode('|||',$channel['shop_id']);
            $op_name = kernel::single('desktop_user')->get_name();
            
            $log_id = $this->getGenId();
            
            if($Account && $consignee) {
            
                //回填基本信息
                $params = array(
                    'sendsite' => $Account[1], //网点名称
                    'sendcus' => $Account[0], //客户名称
                    'cuspwd'=>$Account[2],
                    'billno' => $delivery['logi_no'],
                    'delivery_id' => $delivery_id,
                    'senddate'=>date('Y-m-d H:i:s'),//寄件日期yyyy-mm-dd
                    'sendperson'=>$shopInfo['default_sender'] ? $shopInfo['default_sender'] : '_SYSTEM',//寄件人
                    'sendtel'=>$shopInfo['mobile'] ? $shopInfo['mobile'] : ($shopInfo['tel'] ? $shopInfo['tel'] : '_SYSTEM'),//寄件人电话
                    'receivecus'=>'',//收件客户
                    'receiveperson'=>$consignee['name'],//收件人
                    'receivetel'=>$consignee['mobile'],//收件人电话
                    'goodsname'=>$product_name,//内件品名
                    'inputdate'=>date('Y-m-d H:i:s'),//录入时间
                    'inputperson'=>$op_name ? $op_name : '_SYSTEM',//录入人
                    'inputsite'=>$Account[1],//录入网点
                    'lasteditdate'=>'',//最后编辑时间
                    'lasteditperson'=>'',//最后编辑人
                    'lasteditsite'=>'',//最后编辑网点
                    'remark'=>'',//备注
                    'receiveprovince'=>$consignee['province'],//收件省份
                    'receivecity'=>$consignee['city'],//收件城市
                    'receivearea'=>$consignee['district'],//收件地区
                    'receiveaddress'=>$consignee['addr'],//收件地址
                    'sendprovince'=>$province,//寄件省份
                    'sendcity'=>$city,//寄件城市
                    'sendarea'=>$area,//寄件地区
                    'sendaddress'=>$shopInfo['address_detail'] ? $province . $city . $area . $shopInfo['address_detail'] : '_SYSTEM',//寄件地址
                    'weight'=>'',//重量
                    'productcode'=>'',//产品代码
                    'sendpcode'=>'',//寄件省份编号
                    'sendccode'=>'',//寄件城市编号
                    'sendacode'=>'',//寄件地区编号
                    'receivepcode'=>'',//收件省份编号
                    'receiveccode'=>'',//收件城市编号
                    'receiveacode'=>'',//
                    'bigchar'=>'',//
                    'orderno'=>'',//
                    'businessType' => $waybillstoObj->getBusinessType($channel['logistics_code']),
                    'out_biz_code' => $log_id,
                );
            }
            
            if($params) {
                //记录回填日志
                $logSdf = array(
                    'log_id' => $log_id,
                    //'logi_no' => $delivery['logi_no'],
                    //'delivery_id' => $delivery_id,
                    'channel_id' => $channel['channel_id'],
                    'status' => 'running',
                    'create_time' => time(),
                    'params' => $params,
                );
                
                if($this->insertWaybillLog($logSdf)) {
                    //发送回填请求
                    $stoRpcObj = kernel::single('logisticsmanager_rpc_request_sto');
                    
                    $result = $stoRpcObj->setChannelType('sto')->delivery($params);
                   
                    return $result;
                }
            }
        }

        return false;
    }

    public function cancel_billno($recycle_params) {
        $channelObj = &app::get('logisticsmanager')->model('channel');
        $channel = $channelObj->dump($recycle_params['channel_id']);
        $log_id = $this->getGenId();
        if( $channel['channel_type']=='sto') {
             $Account = explode('|||',$channel['shop_id']);
             $params = array(
                'cusname' => $Account[1], //网点名称
                'cuspwd' => $Account[2], //客户名称
                'billno' => $recycle_params['billno'].',',
                'out_biz_code' => $log_id,
             );

            if($params) {
                //记录回填日志
                $logSdf = array(
                    'log_id' => $log_id,
                    'channel_id' => $recycle_params['channel_id'],
                    'status' => 'running',
                    'create_time' => time(),
                    'params' => $params,
                );
                
                if($this->insertWaybillLog($logSdf)) {
                    //发送回填请求
                    $stoRpcObj = kernel::single('logisticsmanager_rpc_request_sto');
                    
                    $result = $stoRpcObj->setChannelType('sto')->cancel_billno($params);
                   
                    return true;
                }
            }
        }

    
    }
}