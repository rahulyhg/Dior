<?php
/**
* 第三方平台 请求抽象类
*
* @category apibusiness
* @package apibusiness/lib/request/
* @author chenping<chenping@shopex.cn>
* @version $Id: partyabstract.php 2013-13-12 14:44Z
*/
abstract class apibusiness_request_partyabstract extends apibusiness_request_abstract
{
   /**
     * 添加支付单
     *
     * @param Array $delivery
     * @return void
     * @author
     **/
    public function add_delivery($delivery)
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$delivery) {
            $rs['msg'] = 'no delivery';
            return $rs;
        }

        $deliOrderModel = app::get(self::_APP_NAME)->model('delivery_order');
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        if ($delivery['is_bind'] == 'true') {
            $deliOrderList = $deliOrderModel->getList('*',array('delivery_id'=>$delivery['delivery_id']));
            if ($deliOrderList) {
                foreach ($deliOrderList as $key => $deliOrder) {
                    $order = $orderModel->dump(array('order_id'=>$deliOrder['order_id']),'ship_status,shop_id,order_bn,is_delivery,mark_text,sync,order_id,self_delivery,createway');

                    //ExBOY加入部分发货时也回写
                    if ($order['ship_status'] != '1' && $order['ship_status'] != '2') {
                        continue;
                    }
                    
                    if ($delivery['shop_id'] != $order['shop_id']) {
                        $mydelivery = $deliOrderModel->dump(array('order_id' => $deliOrder['order_id'],'delivery_id|noequal'=>$delivery['delivery_id']));
                        if ($mydelivery) {
                            kernel::single('ome_service_delivery')->delivery($mydelivery['delivery_id']);
                        }
                        continue;
                    }

                    $delivery['order'] = $order;
                     //判断是否家装类
                    $partner_request = $this->jzpartner_request($order);

                    if ($partner_request) {
                        $this->jzdelivery_request($partner_request,$delivery);
                    }else{
                        $this->delivery_request($delivery);
                    }
                }
            }
        } else {
            
            if( !isset($delivery['delivery_id']) ){
                $deliOrder['order_id'] = $delivery['order']['order_id'];
            }else{
                $deliOrder = $deliOrderModel->dump(array('delivery_id'=>$delivery['delivery_id']),'*');
            }
            
            $order = $orderModel->dump(array('order_id'=>$deliOrder['order_id']),'ship_status,order_bn,shop_id,is_delivery,mark_text,sync,order_id,self_delivery,createway');

            //ExBOY加入部分发货时也回写
            if ($order['ship_status'] != '1' && $order['ship_status'] != '2') {
                return false;
            }

            $delivery['order'] = $order;
 
            //判断是否家装类
            $partner_request = $this->jzpartner_request($order);

            if ($partner_request) {
                $this->jzdelivery_request($partner_request,$delivery);
            }else{

                $this->delivery_request($delivery);
            }
            
        }

        $rs['rsp'] = 'success';

        return $rs;
    }// TODO TEST


    /**
     * 更新售后状态
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function update_aftersale_status($aftersale,$status='' , $mod='async')
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order      = $orderModel->dump($aftersale['order_id'], 'order_bn');
        if (!$status) {
            $status = $aftersale['status'];
        }
        $api_method = $this->aftersale_api($status);
        
        if ($api_method == '') {
            return true;
        }
        $title = '店铺('.$this->_shop['name'].')更新[交易售后状态]:'.$status.',(订单号:'.$order['order_bn'].'退款单号:'.$aftersale['return_bn'].')';
        $params     = $this->format_aftersale_params($aftersale,$status);
        
        $params['tid'] = $order['order_bn'];
       
        $addon['bn'] = $order['order_bn'];
        $result = array();
        if ($mod == 'async') {
            $callback = array(
                'class' => get_class($this),
                'method' => 'update_aftersale_status_callback',
            );
            $result = $this->_caller->request($api_method,$params,$callback,$title,$this->_shop['shop_id'],10,false,$addon);
            
        }else{
            $timeout = 20;
            $shop_id = $this->_shop['shop_id'];
            $rsp = $this->_caller->call($api_method, $params, $shop_id, $timeout);
            
            //生成日志ID号
            $oApi_log = app::get(self::_APP_NAME)->model('api_log');
            $log_id = $oApi_log->gen_id();
            $callback = array(
                'class'   => get_class($this),
                'method'  => __METHOD__,
                '2'       => array(
                    'log_id'  => $log_id,
                    'shop_id' => $shop_id,
                ),
            );
            $api_status = 'running';
            //$oApi_log->write_log($log_id,$title,'apibusiness_router_request','refuse_return',array($api_method, $params, $callback),'','request','running','','','api.store.trade',$addon['bn']);
            if (!$rsp) return false;
            if($rsp->rsp == 'succ'){
                $api_status = 'success';
                $msg = '售后单状态更新成功<BR>';
                //$oApi_log->update(array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
            }else{
                $api_status = 'fail';
                $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                $msg = '售后单状态更新成功('.$err_msg.')<BR>';
                //$oApi_log->update(array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
            }
            $oApi_log->write_log($log_id,$title,'apibusiness_router_request','refuse_return',array($api_method, $params, $callback),'','request','running','','','api.store.trade',$addon['bn']);
            $result['rsp']     = $rsp->rsp;
            $result['err_msg'] = $rsp->err_msg;
            $result['msg_id']  = $rsp->msg_id;
            $result['res']     = $rsp->res;
            $result['data']    = json_decode($rsp->data,1);
        }
        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];
        $rs['data'] = $result['data'];
        return $rs;
    }

    public function update_aftersale_status_callback($result)
    {
        return $this->_caller->callback($result);
    }

    protected function format_aftersale_params($refund,$status){}
    protected function aftersale_api($status){}

    /**
     * 更新退款单状态
     * @param   
     * @return array 
     * @access  public
     * @author cyyr24@sina.cn
     */
    function update_refund_apply_status($refund,$status,$mod = 'sync')
    {
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $order = $orderModel->dump($refund['order_id'], 'order_bn');

        $api_method = $this->refund_apply_api($status);
        if ($api_method == '') {
            return false;
        }
        $params     = $this->format_refund_applyParams($refund,$status);
        $params['tid'] = $order['order_bn'];
        $title = '店铺('.$this->_shop['name'].')更新[交易退款状态],(订单号:'.$order['order_bn'].'退款单号:'.$refundinfo['refund_apply_bn'].')';
        $addon['bn'] = $order['order_bn'];
        $shop_id = $this->_shop['shop_id'];
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $oApi_log->gen_id();
        if ($mod == 'sync') {
            $timeout = 20;
            $rsp = $this->_caller->call($api_method, $params, $shop_id, $timeout);
            
            $callback = array(
                'class'   => get_class($this),
                'method'  => $api_method,
                '2'       => array(
                    'log_id'  => $log_id,
                    'shop_id' => $shop_id,
                ),
            );
            $oApi_log->write_log($log_id,$title,'apibusiness_router_request',$api_method,array($api_method, $params, $callback),'','request','running','','','api.store.trade',$addon['bn']);
            if ($rsp->rsp == 'succ') {
                $api_status = 'success';
                $msg = '退款申请单状态更新成功<br>';
                $oApi_log->update(array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
            }else{
                $api_status = 'fail';
                $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                $msg = '退款申请单状态更新失败<br>';
                $oApi_log->update(array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
            }
            $result['rsp']     = $rsp->rsp;
            $result['err_msg'] = $rsp->err_msg;
            $result['msg_id']  = $rsp->msg_id;
            $result['res']     = $rsp->res;
            $result['data']    = json_decode($rsp->data,1);
        }
            
        if(isset($result['msg']) && $result['msg']){
            $rs['msg'] = $result['msg'];
        }elseif(isset($result['err_msg']) && $result['err_msg']){
            $rs['msg'] = $result['err_msg'];
        }elseif(isset($result['res']) && $result['res']){
            $rs['msg'] = $result['res'];
        }
        $rs['rsp'] = $result['rsp'];

        return $rs;
        
    }
    protected function format_refund_applyParams($refund,$status){
    }

    protected function refund_apply_api($status)
    {
    }
    
    public function searchAddress($rdef)
    {

        $shop_id = $this->_shop['shop_id'];
        
        $params = array(
            'search_type'=>$rdef,    
        );
        
        $callback = array(
            'class' => get_class($this),
            'method' => 'searchAddress_callback',
        );
        $title = '店铺('.$this->_shop['name'].')获取地址库列表';
        
        $addon=array();
        $this->_caller->request(LOGISTICS_ADDRESS_SEARCH,$params,$callback,$title,$this->_shop['shop_id'],10,false,$addon);
    }

    
    /**
     * 查询地址库回调
     * @param   type    
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function searchAddress_callback($result)
    {
     
        $oAddress = app::get('ome')->model('return_address');
        
        $shop_type = $this->_shop['shop_type'];
        $callback_params = $result->get_callback_params();
        if ($callback_params['shop_id']) {
            $shopModel = app::get(self::_APP_NAME)->model('shop');
            $this->_shop = $shopModel->dump(array('shop_id'=>$callback_params['shop_id']),'shop_id,shop_type');
        }
        $shop_id = $this->_shop['shop_id'];
        $rsp = $result->response;
        if ($rsp['rsp']=='succ') {
            $address_list = json_decode($rsp['data'],true);
             $address_list= $address_list['address_result'];
            //保存至本地
            if ($address_list) {
                $oAddress->db->exec("DELETE FROM sdb_ome_return_address WHERE shop_id='$shop_id'");
                foreach ($address_list as $list ) {
                    $data = array(
                        'cancel_def'    =>$list['cancel_def'],
                        'city'          =>$list['city'],
                        'area_id'       =>$list['area_id'],
                        'phone'         =>$list['phone'],
                        'mobile_phone'  =>$list['mobile_phone'],
                        'province'      =>$list['province'],
                        'addr'          =>$list['addr'],
                        'country'       =>$list['country'],
                        'contact_id'    =>$list['contact_id'],
                        'get_def'       =>$list['get_def'],
                        'contact_name'  =>$list['contact_name'],
                        'seller_company'=>$list['seller_company'],
                        'send_def'      =>$list['send_def'],
                        'zip_code'      =>$list['zip_code'],
                        'shop_type'     =>$this->_shop['shop_type'],
                        'shop_id'       =>$this->_shop['shop_id'],
                    );
                    
                    $rp = $oAddress->save($data);
                    
                }
            }
            
        }
        return $this->_caller->callback($result);
    }

    /**
     * 更新交易收货人信息
     *
     * @param Array $order 订单信息
     * @return void
     * @author 
     **/
    public function update_order_shippinginfo($order)
    { 
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (!$order) {
            $rs['msg'] = 'no order';
            return $rs;
        }

        $consignee_area = $order['consignee']['area'];
        if(strpos($consignee_area,":")){
            $t_area            = explode(":",$consignee_area);
            $t_area_1          = explode("/",$t_area[1]);
            $receiver_state    = $t_area_1[0];
            $receiver_city     = $t_area_1[1];
            $receiver_district = $t_area_1[2];
        }

        $params['tid']               = $order['order_bn'];
        $params['receiver_name']     = $order['consignee']['name']?$order['consignee']['name']:'';
        $params['receiver_phone']    = $order['consignee']['telephone']?$order['consignee']['telephone']:'';
        $params['receiver_mobile']   = $order['consignee']['mobile']?$order['consignee']['mobile']:'';
        $params['receiver_state']    = $receiver_state?$receiver_state:'';
        $params['receiver_city']     = $receiver_city?$receiver_city:'';
        $params['receiver_district'] = $receiver_district?$receiver_district:'';
        $params['receiver_address']  = $order['consignee']['addr']?$order['consignee']['addr']:'';
        $params['receiver_zip']      = $order['consignee']['zip']?$order['consignee']['zip']:'';
        
        $callback = array(
            'class' => get_class($this),
            'method' => 'update_order_shippinginfo_callback',
        );

        $title = '店铺('.$this->_shop['name'].')更新[交易收货人信息]:'.$params['receiver_name'].'(订单号:'.$order['order_bn'].')';

        $shop_id = $order['shop_id'];

        $addon['bn'] = $order['order_bn'];

        $this->_caller->request(UPDATE_TRADE_SHIPPING_ADDRESS_RPC,$params,$callback,$title,$shop_id,150,false,$addon);
        
        $rs['rsp'] = 'success';

        return $rs;
    }// TODO TEST

    public function update_order_shippinginfo_callback($result)
    {
        return $this->_caller->callback($result);
    }// TODO TEST

    
    /**
     * 获取家装服务列表
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_jzpartner($orders)
    {
        
        $shop_id = $this->_shop['shop_id'];

        $api_name = QUERY_JZPARTNER;
        $params = array(
            'taobao_trade_id'=>$orders['order_bn'],
        );
        $order_bn = $orders['order_bn'];
        $title = "店铺(".$this->_shop['name'].")获取前端店铺".$orders['order_bn']."的服务商详情";
        $rsp = $this->_caller->call($api_name,$params,$shop_id,10);

        $result = array();
        $result['rsp']     = $rsp->rsp;
        $result['err_msg'] = $rsp->err_msg;
        $result['msg_id']  = $rsp->msg_id;
        $result['res']     = $rsp->res;
        
        $api_status = 'running';
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $oApi_log->gen_id();
        $params['msg_id']    = $result['msg_id'];
        if($rsp){
           if($rsp->rsp == 'succ'){
                $data    = json_decode($rsp->data,1);
                if ($data['server_list']['partner_new']) {
                    $data = $data['server_list']['partner_new'][0];
                }
                
                $result['data']     = $data;
                $api_status = 'success';
                $msg = '获取家装详情成功<BR>';
                
           }else{
                //api日志记录
                $api_status = 'fail';
                $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                $msg = '获取家装详情失败('.$err_msg.')<BR>';
                
           }
        }
        $oApi_log->write_log($log_id,$title,'apibusiness_router_request','rpc_request',array($api_name, $params),'','request',$api_status,$msg ,'',$api_name,$order_bn);
        return $result;
    }

    
    /**
     * 家装类发货
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function jzpartner_request($orders)
    {
        $data = array();
        $partnerObj = app::get('ome')->model('tbjz_partner');
        $jzObj = app::get('ome')->model('tbjz_orders');
        $order_id = $orders['order_id'];
        $jzorders = $jzObj->dump(array('order_id'=>$order_id));

        if ($jzorders) {
            $partner_detail = $partnerObj->dump(array('order_id'=>$order_id));
            if ($partner_detail) {
                $data = $partner_detail;
            }else{
                $partnerinfo = $this->get_jzpartner($orders);
         
                if ($partnerinfo['rsp'] == 'succ') {
                    $data = $partnerinfo['data'];
                    if ($data) {
                        $jzdata = array(
                            'order_id'   => $order_id,
                            'tp_code'   =>   $data['tp_code'],
                            'tp_name'=> $data['tp_name'],
                            'service_type'=>$data['service_type'],
                            'is_virtual_tp'=>$data['is_virtual_tp'],
                        );
                        $partnerObj->save($jzdata);
                    }
                }
            }
            
        }
        return $data;
    }

    
    /**
     * 家装类发货
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function jzdelivery_request($partner,$delivery)
    {
       
        $tms_partner = array(
            'tp_code'       => $partner['tp_code'],
            'tp_name'      => $partner['tp_name'],
            'service_type' => $partner['service_type'],
            'is_virtual_tp'  => $partner['is_virtual_tp'],
        );
        $jz_consign_args = array(
            'mail_no'   =>$delivery['logi_no'] ? $delivery['logi_no'] : '',
            'package_remark'=>'',
            'zy_mail_no'=>$delivery['logi_no'] ? $delivery['logi_no'] : '',
            'zy_company'=>$delivery['logi_name'] ? $delivery['logi_name'] : '',
            'zy_phone_number'=>$this->_shop['mobile'] ? $this->_shop['mobile'] : $this->_shop['tel'],
            'zy_consign_time'=>date('Y-m-d',$delivery['delivery_time']),
        
        );
        $param = array(
            'tid'          =>$delivery['order']['order_bn'],
            'tms_partner' => json_encode($tms_partner),
            'jz_consign_args' => json_encode($jz_consign_args),

        );
        
        $callback = array(
           'class' => get_class($this),
           'method' => 'add_delivery_callback',
        );

        $shop_id = $delivery['shop_id'];
        $delivery = $this->format_delivery($delivery);
        if ($delivery['type'] == 'reject') {
            $title = '店铺('.$this->_shop['name'].')添加[家装交易发货单](<font color="red">补差价</font>订单号:'.$delivery['order']['order_bn'].')';
        } else {
            $title = '店铺('.$this->_shop['name'].')添加[家装交易发货单](订单号:'.$param['tid'].',发货单号:'.$delivery['delivery_bn'].')';
        }
        $addon['bn'] = $delivery['order']['order_bn'];

        // 记录发货日志
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $oApi_log->gen_id();

        $opInfo = kernel::single('ome_func')->getDesktopUser();
        //增加更新发货状态日志
        $log = array(
            'shopId'           => $shop_id,
            'ownerId'          => $opInfo['op_id'],
            'orderBn'          => $delivery['order']['order_bn'],
            'deliveryCode'     => $delivery['logi_no'],
            'deliveryCropCode' => $delivery['dly_corp']['type'],
            'deliveryCropName' => $delivery['logi_name'],
            'receiveTime'      => time(),
            'status'           => 'send',
            'updateTime'       => '0',
            'message'          => '',
            'log_id'           => $log_id,
        );

        # 已经解绑的店铺订单，直接将订单设为回写失败
        if (!$this->_shop['node_id'] && $delivery['order']['createway'] == 'matrix') {
            $log['status'] = 'fail';
            $log['message'] = '店铺已解绑';
        }

        $shipmentLogModel = app::get(self::_APP_NAME)->model('shipment_log');
        $shipmentLogModel->save($log);

        $orderModel = app::get(self::_APP_NAME)->model('orders');

        $updateData = array('sync'=>'run');

        # 已经解绑的店铺订单，直接将订单设为回写失败
        if (!$this->_shop['node_id'] && $delivery['order']['createway'] == 'matrix') {
            $updateData['sync'] = 'fail';
        }

        $orderModel->update($updateData,array('order_id'=>$delivery['order']['order_id']));                

        $write_log = array('log_id' => $log_id);

        $this->_caller->request(CONSIGN_JZWIGHINS,$param,$callback,$title,$shop_id,100,false,$addon,$write_log);

        return true;
    }
    /**
     * 获取云栈大头笔信息
     * @param   array  $data  
     * @param   string $cp_code  物流公司编号
     * @param   string $shop_id  店铺id 
     * @return  object $res      返回信息结果
     * @access  public
     * @author  liuzecheng@shopex.cn
     */
    function getCloudStackPrintTag($data,$cp_code,$shop_id) {
        $params = array(
            'cp_code'=>$cp_code,
        );
        $address_pairs = array(
            'shipping_address'=>array(
                'area'=>$data['dly_area_2'],
                'province'=>$data['dly_area_0'],
                'town'=>'',
                'city'=>$data['dly_area_1'],
                'address_detail'=>$data['dly_address'],
            ),
            //订单号非必须参数（暂时屏蔽）
            //'trade_order_code'=> '',
            //收货人地址
            'consignee_address'=> array(
                'area'=>$data['ship_area_2'],
                'province'=>$data['ship_area_0'],
                'town'=> '',
                'city'=>$data['ship_area_1'],
                'address_detail'=> $data['ship_addr'],
            ),
            //物流单号非必须参数（暂时屏蔽）
            // 'waybill_code'=> ''
        );
        $params['address_pairs'] = json_encode($address_pairs);

        $title='获取发货单号（'.$data['delivery_bn'].'）的'.'云栈大头笔';
        $oApi_log = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $oApi_log->gen_id();
        $operinfo = kernel::single('ome_func')->getDesktopUser();
        $params_log = array('cp_code'=>$cp_code,'address_pairs'=>  serialize($address_pairs),'op_name'=>$operinfo['op_name']);
        $oApi_log->write_log($log_id,$title,get_class($this),'call',array(GET_CLOUD_STACK_PRINT_TAG, $params_log),'','request','running','','','',$data['delivery_bn']);

        // 记录获取中通大头笔日志
        $result = $this->_caller->call(GET_CLOUD_STACK_PRINT_TAG,$params,$shop_id,100);
        if($result->rsp == 'succ'){
            $apilog_status = 'success';
            $msg = '成功';
            $data = json_decode($result->data,ture);
            if(isset($data['waybill_distribute_info_response']['waybill_distribute_infos']['waybill_distribute_info'][0]['error_code'])){
                $apilog_status = 'fail';
                $msg = $data['waybill_distribute_info_response']['waybill_distribute_infos']['waybill_distribute_info'][0]['error_msg'] ? $data['waybill_distribute_info_response']['waybill_distribute_infos']['waybill_distribute_info'][0]['error_msg'] : $data['waybill_distribute_info_response']['waybill_distribute_infos']['waybill_distribute_info'][0]['error_code'];
            }
        }else{
            $apilog_status = 'fail';
            $msg = $result->err_msg;
        }
        $oApi_log->update(array('msg_id'=>$result->msg_id,'msg'=>$msg,'status'=>$apilog_status),array('log_id'=>$log_id));
        return $result;
    }
}