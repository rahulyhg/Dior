<?php
/**
 * 订单业务同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_order extends ome_rpc_request {
    //订单状态
    var $status = array(
        'active' => 'TRADE_ACTIVE',
        'dead' => 'TRADE_CLOSED',
        'finish' => 'TRADE_FINISHED',
    );
    //订单暂停状态
    var $pause_status = array(
        'true' => 'TRADE_PENDING',//暂停
        'false' => 'TRADE_ACTIVE',//恢复
    );
    //订单状态名称
    var $status_name = array(
        'active' => '活动',
        'dead' => '取消',
        'finish' => '完成',
        'pause:true' => '暂停',
        'pause:false' => '恢复',
    );
    //订单支付状态
    var $pay_status = array(
        '0' => 'PAY_NO',
        '1' => 'PAY_FINISH',
        '2' => 'PAY_TO_MEDIUM',
        '3' => 'PAY_PART',
        '4' => 'REFUND_PART',
        '5' => 'REFUND_ALL',
    );
    //订单发货状态
    var $ship_status = array(
        '0' => 'SHIP_NO',
        '1' => 'SHIP_FINISH',
        '2' => 'SHIP_PART',
        '3' => 'RETRUN_PART',
        '4' => 'RETRUN_ALL',
    );
    //订单旗标(b0:灰色  b1:红色  b2:橙色  b3:黄色  b4:蓝色  b5:紫色)
    var $mark_type = array(
        'b0' => '0',
        'b1' => '1',
        'b2' => '2',
        'b3' => '3',
        'b4' => '4',
        'b5' => '5',
    );
    //订单类型。可选值:goods(商品),gift(赠品)。默认为goods
    var $obj_type = array(
        'goods' => 'goods',
        'gift' => 'gift',
    );
    //货品状态:默认为false（正常）,true：取消
    var $product_status = array(
        'false' => 'normal',
        'true' => 'cancel',
    );

    /**
    * 订单编辑 iframe
    * @access public
    * @param String $order_id 订单ID
    * @param Bool $is_request 是否发起请求
    * @param Array $ext 扩展参数
    * @return Array
    */
    public function update_iframe($order_id,$is_request=true,$ext=array()){
        if (empty($order_id)) return NULL;

        $orderObj = &app::get('ome')->model('orders');
        $shopObj = &app::get('ome')->model('shop');
        $orders = $orderObj->getRow($order_id, 'shop_id,order_bn,source');
        $shop_detail = $shopObj->getRow(array('shop_id'=>$orders['shop_id']),'node_id');
        $sdf['order_bn'] = $orders['order_bn'];
        $sdf['order_id'] = $order_id;
        $sdf['is_request'] = $is_request;
        $sdf['ext'] = $ext;

        #店铺解除绑定后或者本地订单,调用本地编辑
        if (empty($shop_detail['node_id']) || $orders['source'] == 'local'){
            $data = array('edit_type'=>'local');
            return array('rsp'=>'success','msg'=>'本地订单编辑','data'=>$data);
        }

        $rs = kernel::single('ome_rpc_mapper')->request_router($shop_detail['node_id'],'order','update_iframe',$sdf);
        return $rs;
    }

    /**
     * 订单编辑
     * @access public
     * @param int $order_id 订单主键ID
     * @return boolean
     */
    public function update_order($order_id=''){
        if (empty($order_id)) return NULL;

        $orderObj = &app::get('ome')->model('orders');
        $shopObj = &app::get('ome')->model('shop');
        $orders = $orderObj->getRow($order_id, 'shop_id,order_bn');
        $shop_detail = $shopObj->getRow(array('shop_id'=>$orders['shop_id']),'node_id');
        $sdf['order_id'] = $order_id;
        $rs = kernel::single('ome_rpc_mapper')->request_router($shop_detail['node_id'],'order','update_order',$sdf);

        return $rs;
    }

    function update_order_callback($result){
        return $this->callback($result);
    }


    /**
     * 更新订单状态
     * @access public
     * @param int $order_id 订单主键ID
     * @param string $status 状态
     * @param string $memo 备注
     * @param string $mode 请求类型:sync同步  async异步
     * @return boolean
     */
    public function order_status_update($order_id,$status='',$memo='',$mode='sync'){
        $rs = array('rsp'=>'fail','msg'=>'');

        if(!empty($order_id)){

            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id,status,shop_type');
            $shop_id = $order['shop_id'];
            $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
            $shop_list = ome_shop_type::shop_list();
            $order_status = $status ? $status : $order['status'];
            $api_name = 'store.trade.status.update';

            $params['tid'] = $order['order_bn'];
            $params['status'] = $this->status[$order_status];
            $params['type'] = 'status';
            $params['modify'] = date('Y-m-d H:i:s', time());
            $params['is_update_trade_status'] = 'true';

            if ($order_status == 'dead'){
                //订单取消理由
                $params['reason'] = $memo;
            }

            if(in_array($order['shop_type'], $shop_list) && ($order_status == 'dead')){
                $api_name = 'store.trade.close';
                $params = array('tid'=>$order['order_bn'],'close_reason'=>$memo);
            }


            if($shop_id){
                $title = '店铺('.$shop_info['name'].')更新[订单状态]:'.$this->status_name[$order_status].'(订单号:'.$order['order_bn'].')';
            }else{
                $rs['msg'] = '订单无法关联店铺';
                return $rs;
            }

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'order_status_update_callback',
            );

            if($mode == 'sync'){
              $rsp = $this->call($api_name,$params,$shop_id);
              $oApi_log = &app::get('ome')->model('api_log');
              $log_id = $oApi_log->gen_id();
              $callback = array(
                                'class'   => 'ome_rpc_request_order',
                                'method'  => 'order_status_update',
                                '2'       => array(
                                    'log_id'  => $log_id,
                                    'shop_id' => $shop_id,
                                ),
              );
              $oApi_log->write_log($log_id,$title,'ome_rpc_request','rpc_request',array($api_name, $params, $callback),'','request','running','','','api.store.trade',$order['order_bn']);
              if($rsp->rsp == 'succ'){
                    $api_status = 'success';
                    $msg = '订单状态更新成功<BR>';
                    $oApi_log->update(array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
              }else{
                    $api_status = 'fail';
                    $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                    $msg = '订单状态更新失败('.$err_msg.')<BR>';
                    $oApi_log->update(array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
              }

              $result['rsp'] = $rsp->rsp;
              $result['err_msg'] = $rsp->err_msg;
              $result['msg_id'] = $rsp->msg_id;
              $result['res'] = $rsp->res;
              $result['data'] = json_decode($rsp->data,1);
            }else{
              $result = $this->request($api_name,$params,$callback,$title,$shop_id);
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

        }else{
            return false;
        }
    }

    function order_status_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 订单暂停与恢复
     * @access public
     * @param int $order_id 订单主键ID
     * @param string $status 状态true:暂停  false:恢复
     * @return boolean
     */
    public function order_pause_status_update($order_id,$status=''){
        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id,pause');
            $params['tid'] = $order['order_bn'];
            $shop_id = $order['shop_id'];
            $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name,node_type');
            $node_type = $shop_info['node_type'];
            $b2b_shop= ome_shop_type::b2b_shop_list();

            if (in_array($node_type, $b2b_shop)){
                if ($status == ''){
                    $params['status'] = $this->pause_status[$order['pause']];
                }else{
                    $params['status'] = $this->pause_status[$status];
                    $order['pause'] = $status;
                }
                $params['type'] = 'status';
                $params['modify'] = date('Y-m-d H:i:s', time());
                if($shop_id){
                    $title = '店铺('.$shop_info['name'].')更新[订单状态]:'.$this->status_name['pause:'.$order['pause']].'(订单号:'.$order['order_bn'].')';
                }else{
                    return false;
                }

                $callback = array(
                    'class' => 'ome_rpc_request_order',
                    'method' => 'order_pause_status_update_callback',
                );
                $api_name = 'store.trade.status.update';

                $this->request($api_name,$params,$callback,$title,$shop_id);
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    function order_pause_status_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 更新订单发票信息
     * @access public
     * @param int $order_id 订单主键ID
     * @return boolean
     */
    public function order_tax_update($order_id){

        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id,tax_no');
            $params['tid'] = $order['order_bn'];
            $shop_id = $order['shop_id'];
            $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');

            $params['tax_no'] = $order['tax_no'];
            if($shop_id){
                $title = '店铺('.$shop_info['name'].')更新[订单发票号]:'.$order['tax_no'].'(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'order_tax_update_callback',
            );
            $api_name = 'store.trade.tax.update';

            $this->request($api_name,$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }
    function order_tax_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 更新订单发货状态
     * @access public
     * @param int $order_id 订单主键ID
     * @param boolean $queue 是否走队列
     */
    public function ship_status_update($order_id,$queue=false){

        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id,ship_status');
            $params['tid'] = $order['order_bn'];
            $shop_id = $order['shop_id'];
            $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');

            $params['ship_status'] = $this->ship_status[$order['ship_status']];
            if($shop_id){
                $title = '店铺('.$shop_info['name'].')更新[订单发货状态]:'.$params['ship_status'].'(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'ship_status_update_callback',
            );

            $api_name = 'store.trade.ship_status.update';
            $this->request($api_name,$params,$callback,$title,$shop_id,'',$queue);
        }else{
            return false;
        }
    }
    function ship_status_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 更新订单支付状态
     * @access public
     * @param int $order_id 订单主键ID
     * @return boolean
     */
    public function pay_status_update($order_id){

        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id,pay_status');
            $params['tid'] = $order['order_bn'];
            $shop_id = $order['shop_id'];
            $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');

            $params['pay_status'] = $this->ship_status[$order['pay_status']];
            if($shop_id){
                    $title = '店铺('.$shop_info['name'].')更新[订单支付状态]:'.$params['pay_status'].'(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'pay_status_update_callback',
            );

            $api_name = 'store.trade.pay_status.update';
            $this->request($api_name,$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function pay_status_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 更新订单交易备注
     * @access public
     * @param int $order_id 订单主键ID
     * @param array $memo 备注内容
     * @return boolean
     */
    public function memo_update($order_id,$memo){

        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id,mark_type');
            $params['tid'] = $order['order_bn'];
            $params['memo'] = $memo['op_content'];
            $params['flag'] = $this->mark_type[$order['mark_type']]?$this->mark_type[$order['mark_type']]:'';
            $params['sender'] = $memo['op_name'];
            $params['add_time'] = $memo['op_time'];

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'memo_update_callback',
            );

            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更新订单备注(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.memo.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function memo_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 添加订单交易备注
     * @access public
     * @param int $order_id 订单主键ID
     * @param array $memo 备注内容
     * @return boolean
     */
    public function memo_add($order_id,$memo){

        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id,mark_type');
            $params['tid'] = $order['order_bn'];
            $params['memo'] = $memo['op_content'];
            $params['flag'] = $this->mark_type[$order['mark_type']]?$this->mark_type[$order['mark_type']]:'';
            $params['sender'] = $memo['op_name'];
            $params['add_time'] = $memo['op_time'];

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'memo_add_callback',
            );

            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')添加订单备注(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.memo.add',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function memo_add_callback($result){
        return $this->callback($result);
    }

    /**
     * 添加买家留言
     * @access public
     * @param int $order_id 订单主键ID
     * @param array $memo 备注内容
     * @return boolean
     */
    public function custom_mark_add($order_id,$memo){

        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id');
            $params['tid'] = $order['order_bn'];
            $params['message'] = $memo['op_content'];
            $params['sender'] = $memo['op_name'];
            $params['add_time'] = $memo['op_time'];

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'custom_mark_add_callback',
            );

            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')添加订单附言(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.buyer_message.add',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function custom_mark_add_callback($result){
        return $this->callback($result);
    }

    /**
     * 更新交易收货人信息
     * @access public
     * @param int $order_id 订单主键ID
     * @return boolean
     */
    public function shippinginfo_update($order_id){

        if(!empty($order_id)){

            $orderObj = &app::get('ome')->model('orders');

            $order = $orderObj->dump($order_id, '*');

            $consignee_area = $order['consignee']['area'];
            if(strpos($consignee_area,":")){
                $t_area = explode(":",$consignee_area);
                $t_area_1 = explode("/",$t_area[1]);
                $receiver_state = $t_area_1[0];
                $receiver_city = $t_area_1[1];
                $receiver_district = $t_area_1[2];
            }
            $params['tid'] = $order['order_bn'];
            $params['receiver_name'] = $order['consignee']['name']?$order['consignee']['name']:'';
            $params['receiver_state'] = $receiver_state?$receiver_state:'';
            $params['receiver_city'] = $receiver_city?$receiver_city:'';
            $params['receiver_district'] = $receiver_district?$receiver_district:'';
            $params['receiver_address'] = $order['consignee']['addr']?$order['consignee']['addr']:'';
            $params['receiver_zip'] = $order['consignee']['zip']?$order['consignee']['zip']:'';
            $params['receiver_email'] = $order['consignee']['email']?$order['consignee']['email']:'';
            $params['receiver_mobile'] = $order['consignee']['mobile']?$order['consignee']['mobile']:'';
            $params['receiver_phone'] = $order['consignee']['telephone']?$order['consignee']['telephone']:'';
            $params['receiver_time'] = $order['consignee']['r_time']?$order['consignee']['r_time']:'';

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'shippinginfo_update_callback',
            );

            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更新[交易收货人信息]:'.$params['receiver_name'].'(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.shippingaddress.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function shippinginfo_update_callback($result){
        return $this->callback($result);
    }

    function notify_get_order($shop,$start_time,$end_time){
        $params = array('service'=>'notify.ome_order',
                        'v'=>'1.0',
                        'certi_id'=>base_certificate::get('certificate_id'),
                        'from_node_id'=>base_shopnode::node_id('ome'),
                        'to_node_id'=>$shop['node_id'],
                        'shop_type'=>$shop['shop_type'],
                        'start_time'=>$start_time,
                        'end_time'=>$end_time
        );
        $params['sign'] = $this->make_sign_matrix($params);
        $this->direct_request(AFRESH_GET_ORDER_URL,$params);
    }

    /**
     * 更新交易发货人信息
     * @access public
     * @param int $order_id 订单主键ID
     * @return boolean
     */
    public function consigner_info_update($order_id){

        if(!empty($order_id)){

            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, '*');

            $consigner_area = $order['consigner']['area'];
            kernel::single('eccommon_regions')->split_area($consigner_area);

            $params['tid'] = $order['order_bn'];
            $params['shipper_name'] = $order['consigner']['name'];
            $params['shipper_state'] = $consigner_area[0];
            $params['shipper_city'] = $consigner_area[1];
            $params['shipper_district'] = $consigner_area[2];
            $params['shipper_address'] = $order['consigner']['addr'];
            $params['shipper_zip'] = $order['consigner']['zip'];
            $params['shipper_email'] = $order['consigner']['email'];
            $params['shipper_mobile'] = $order['consigner']['mobile'];
            $params['shipper_phone'] = $order['consigner']['tel'];

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'consigner_info_update_callback',
            );

            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更新[交易发货人信息]:'.$params['consigner_name'].'(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.shipper.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function consigner_info_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 更新代销人信息
     * @access public
     * @param int $order_id 订单主键ID
     * @return boolean
     */
    public function sellagent_info_update($order_id){

        if(!empty($order_id)){

            $orderObj = &app::get('ome')->model('orders');
            $sellagentObj = &app::get('ome')->model('order_selling_agent');

            $order = $orderObj->dump($order_id, 'order_bn');
            $sellagent_detail = $sellagentObj->dump($order_id, '*');

            $sellagent_area = $order['member_info']['area'];
            kernel::single('eccommon_regions')->split_area($sellagent_area);

            $params = array(
                'tid' => $order['order_bn'],
                '_uname' => $sellagent_detail['member_info']['uname'],
                '_name' => $sellagent_detail['member_info']['name'],
                '_birthday' => $sellagent_detail['member_info']['birthday'],
                '_sex' => $sellagent_detail['member_info']['sex'],
                '_state' => $sellagent_area[0],
                '_city' => $sellagent_area[1],
                '_district' => $sellagent_area[2],
                '_address' => $sellagent_detail['member_info']['addr'],
                '_zip' => $sellagent_detail['member_info']['zip'],
                '_email' => $sellagent_detail['member_info']['email'],
                '_mobile' => $sellagent_detail['member_info']['mobile'],
                '_phone' => $sellagent_detail['member_info']['tel'],
                '_website_name' => $sellagent_detail['website']['name'],
                '_website_domain' => $sellagent_detail['website']['domain'],
            );

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'sellagent_info_update_callback',
            );

            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更新[交易代销人信息]:(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.selling_agent.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function sellagent_info_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 更新订单失效时间
     * @access public
     * @param int $order_id 订单主键ID
     * @param string $order_limit_time 订单失效时间
     * @return true or false
     */
    public function update_order_limit_time($order_id,$order_limit_time=''){

        if(!empty($order_id)){
            $orderObj = &app::get('ome')->model('orders');
            $order = $orderObj->dump($order_id, 'order_bn,shop_id');
            $params['tid'] = $order['order_bn'];
            $params['order_limit_time'] = $order_limit_time;

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'update_order_limit_time_callback',
            );

            $shop_id = $order['shop_id'];
            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '更新店铺('.$shop_info['name'].')订单失效时间(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $this->request('store.trade.order_limit_time.update',$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }

    function update_order_limit_time_callback($result){
        return $this->callback($result);
    }

    /*
    *获取店铺订单详情
    *@param order_id 订单号 shop_id 店铺ID order_type 订单类型
    *@return array
    2012-06-19 by yangminsheng
    */
    function get_order_detial($order_id='',$shop_id='',$order_type='')
    {
        if(empty($order_id) || empty($shop_id)) return array('rsp'=>'fail','msg'=>'数据有误!');
        $Apilog = &app::get('ome')->model('api_log');
        $Oshop = &app::get('ome')->model('shop');
        $shop_info = $Oshop->getRow($shop_id,'name');
        $result = array();
        if($order_type == 'direct'){
            $api_name = 'store.trade.fullinfo.get';
        }else{
            $api_name = 'store.fenxiao.trade.fullinfo.get';
        }
        $params['tid'] = $order_id;
        $addon['bn'] = $order_id;
        $title = "店铺(".$shop_info['name'].")获取前端店铺".$order_id."的订单详情";
        $rsp = $this->call($api_name,$params,$shop_id);

        $result['rsp'] = $rsp->rsp;
        $result['err_msg'] = $rsp->err_msg;
        $result['msg_id'] = $rsp->msg_id;
        $result['res'] = $rsp->res;
        $result['data'] = json_decode($rsp->data,1);
        $result['order_type'] = $order_type;
        $log_id = $Apilog->gen_id();
        $callback = array(
                                'class'   => 'ome_rpc_request_order',
                                'method'  => 'get_order_detial',
                                '2'       => array(
                                    'log_id'  => $log_id,
                                    'shop_id' => $shop_id,
                                ),
        );
        $Apilog->write_log($log_id,$title,'ome_rpc_request','rpc_request',array($api_name, $params, $callback),'','request','running','','','api.store.trade',$order_id);
        if($rsp){
           if($rsp->rsp == 'succ'){
            //api日志记录
            $api_status = 'success';
            $msg = '获取订单详情成功<BR>';
            $filter_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
            $Apilog->update($filter_data,array('log_id'=>$log_id));
           }else{
            //api日志记录
            $api_status = 'fail';
            $filter_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
            $msg = '获取订单详情('.$rsp->res.')<BR>';
            $Apilog->update($filter_data,array('log_id'=>$log_id));
           }
        }

        return $result;
    }

    /*
    *获取店铺指定时间范围内的订单列表
    *@param start_time 开始时间 end_time 结束时间 shop_id 店铺ID
    *@return array
    2012-06-19 by yangminsheng
    */
    function get_order_list($start_time='',$end_time='',$shop_id='')
    {
        $rs = array('rsp'=>'fail','msg'=>'','data'=>$data,'is_update_time'=>'false');
        if(empty($start_time) || empty($end_time) || empty($shop_id)) return $rs;
        $orders = app::get("ome")->model("orders");
        $params['start_time'] = date("Y-m-d H:m:s",$start_time);
        $params['end_time'] = date("Y-m-d H:m:s",$end_time);
        $api_name = 'store.trades.sold.get';
        $params['page_size'] = 100;
        $params['fields'] = 'tid,status,pay_status,ship_status,modified';
        $result = $this->call($api_name,$params,$shop_id);
        $return_data['rsp'] = $result->rsp;
        $return_data['err_msg'] = $result->err_msg;
        $return_data['msg_id'] = $result->msg_id;
        $return_data['res'] = $result->res;
        $return_data['data'] = json_decode($result->data,1);

        if($return_data['rsp'] == 'succ')
        {
            if(intval($return_data['data']['total_results'])<1){
               $rs['msg'] = '该时间段内没有订单.';
               $rs['is_update_time'] = 'true';
               $rs['msg_id'] = $return_data['msg_id'];
               $rs['rsp'] = 'success';
               $rs['data'] = array();
               return $rs;
            }
            $page_total = ceil($return_data['data']['total_results']/$params['page_size']);
            $tids = array();
            $aTmp = array();
            for($i=1;$i<=$page_total;$i++)
            {
                $matrix_tids = array();
                $order_data = array();
                $return_data_page['data'] = array();
                $params['page_no'] = $i;
                $resp = $this->call($api_name,$params,$shop_id);

                $return_data_page['rsp'] = $resp->rsp;
                $return_data_page['err_msg'] = $resp->err_msg;
                $return_data_page['msg_id'] = $resp->msg_id;
                $return_data_page['res'] = $resp->res;
                $return_data_page['data'] = json_decode($resp->data,1);

                if($return_data_page['rsp'] == 'succ')
                {

                    foreach($return_data_page['data']['trades'] as $k=>$v){
                        $matrix_tids[$v['tid']]['status'] = $v['status'];
                        $matrix_tids[$v['tid']]['tid'] = $v['tid'];
                        $matrix_tids[$v['tid']]['modified'] = $v['modified'];
                        $matrix_tids[$v['tid']]['ship_status'] = $v['ship_status'];
                        $matrix_tids[$v['tid']]['pay_status'] = $v['pay_status'];
                    }//获取到矩阵返回的数据后，对数据进行重组


                    $matrix_tid_keys = array_keys($matrix_tids);
                    $row = $orders->db->select("select outer_lastmodify,order_bn from sdb_ome_orders where order_bn in ('".implode("','",$matrix_tid_keys)."')");
                    if(empty($row)){
                       $aTmp = array_merge($matrix_tids,$aTmp);
                    }else{
                        $local_exist_tids = array();
                        foreach($row as $return_k=>$return_v)
                        {
                            if($row && strtotime($matrix_tids[$order_bn]['modified'])<$return_v['outer_lastmodify']){
                                $local_exist_tids[] = $return_v['order_bn'];//将本地不需要的订单放入数组
                            }
                        }

                        foreach ($local_exist_tids as $value) {
                            unset($matrix_tids[$value]);
                        }//将不需要修改的订单从总list中删除.

                        $aTmp = array_merge($matrix_tids,$aTmp);
                    }

                }
                else{
                   $rs['msg'] = $return_data_page['err_msg'];
                   $rs['msg_id'] = $return_data_page['msg_id'];
                   return $rs;
                };
            }
            if(count($aTmp)==0){
               $rs['msg'] = '经过比对,该时间段内没有发现漏单情况';
               $rs['is_update_time'] = 'true';
            }
        }else{
            $rs['msg'] = $return_data['err_msg'];
            $rs['msg_id'] = $return_data['msg_id'];
            return $rs;
        }

        $rs['data'] = $aTmp;
        $rs['rsp'] = 'success';
        $rs['msg_id'] = $return_data_page['msg_id'];

        return $rs;
    }

}