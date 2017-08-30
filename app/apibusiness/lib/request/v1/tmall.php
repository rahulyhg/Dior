<?php
/**
* 天猫接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: taobao.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_tmall extends apibusiness_request_partyabstract
{
    
    protected function delivery_api($delivery = '')
    {
        if ('on' == app::get(self::_APP_NAME)->getConf('ome.delivery.method') && $delivery['order']['sync'] == 'none') {
            $api_name = LOGISTICS_ONLINE_RPC;
        } else {
            $api_name = $delivery['is_cod'] == 'true' ? LOGISTICS_ONLINE_RPC : LOGISTICS_OFFLINE_RPC;
        }

        return $api_name;
    }


   /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {
        $param = array(
            'tid'          => $delivery['order']['order_bn'],
            'company_code' => trim($delivery['dly_corp']['type']),
            'company_name' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no' => $delivery['logi_no'] ? $delivery['logi_no'] : '',
        );
        
        /*------------------------------------------------------ */
        //-- [是否拆单]增加参数回写  ExBOY
        /*------------------------------------------------------ */
        if($delivery['is_split'] == 1 && !empty($delivery['oid_list']))
        {
            #[部分回写]捆绑商品去重oid_list
            $oid_list    = explode(',', $delivery['oid_list']);
            $oid_list    = array_unique($oid_list);
            $oid_list    = implode(',', $oid_list);
            
            $param['is_split']  = $delivery['is_split'];
            $param['oid_list']  = $oid_list;
        }
        
        #判断是否开启唯一码回写
        if ( app::get('wms')->getConf('wms.product.serial.delivery') =='true') {
            $deliveryObj = app::get('ome')->model('delivery');
            $serial = $deliveryObj->getProductserial($delivery['delivery_id']);
            if ($serial) {
                $param['feature'] = $serial;
            }
        }
        return $param;
    }// TODO TEST


    protected function stock_api($stocks){

        switch($this->_shop['business_type']){
            case 'fx':
                $api_name = UPDATE_FENXIAO_ITEMS_QUANTITY_LIST_RPC;
            break;
            default:
                $api_name = UPDATE_ITEMS_QUANTITY_LIST_RPC;
            break;            
        }

        return $api_name;
    }

     /**
     * 取得退款申请对应状态接口名     *
     * @return void
     * @author 
     **/
    protected function refund_apply_api($status)
    {
        $api_method = '';
        switch($status){
            case '2':
                $api_method = AGREE_REFUND_I_TMALL;
            break;
            case '3':
                $api_method = REFUNSE_REFUND_I_TMALL;
            break;
        }

        return $api_method;
    }// TODO TEST

    protected function format_refund_applyParams($refund,$status){
        $oRefund_tmall = app::get(self::_APP_NAME)->model('refund_apply_tmall');
        $refund_tmall = $oRefund_tmall->dump(array('shop_id'=>$this->_shop['shop_id'],'refund_apply_bn'=>$refund['refund_apply_bn']));
        
        $params = array(
            'refund_id'  =>$refund['refund_apply_bn'],
            'refund_type'=>$refund_tmall['refund_phase'],
            'return_type'=>$refund_tmall['refund_phase'],
            'refund_version'=>$refund_tmall['refund_version'],
        );
        $op_name = kernel::single('desktop_user')->get_name();
        if ($status == '3') {#退款单拒绝
            $params['refuse_proof']   = $refund['refuse_proof'];
            $params['refuse_message'] = $refund['refuse_message'];
        }
        if ($status == '2') {#接受
            $batchList = kernel::single('ome_refund_apply')->return_batch('accept_refund');
            $return_batch = $batchList[$this->_shop['shop_id']];
            //判断退货地址，备注信息
            
            $params['refuse_message'] = $return_batch['memo'] ? $return_batch['memo'] : '同意退款申请';
            $params['username'] = $op_name;
            

        }
        return $params;
    }
    
    protected function format_aftersale_params($aftersale,$status){
        $shop_id = $this->_shop['shop_id'];
        $oReturn_tmall = app::get(self::_APP_NAME)->model('return_product_tmall');
        $return_tmall = $oReturn_tmall->dump(array('shop_id'=>$shop_id,'return_id'=>$aftersale['return_id']));
        $oReturn_address = app::get(self::_APP_NAME)->model('return_address');
        $return_address = $oReturn_address->getDefaultAddress($shop_id);

        $params = array(
            'refund_id'     =>$aftersale['return_bn'],
            'refund_version'=>$return_tmall['refund_version'],
            'refund_type'   =>$return_tmall['refund_phase'],
            'return_type'   =>$return_tmall['refund_phase'],
        );
        switch ($status) {
            case '3':
                $batchList = kernel::single('ome_refund_apply')->return_batch('accept_return');
                $return_batch = $batchList[$shop_id];
                $params['seller_logistics_address_id'] = $return_tmall['contact_id'] ? $return_tmall['contact_id'] : $return_address['contact_id'];
                $params['memo'] = $return_batch['memo'] ? $return_batch['memo'] : '同意退货申请';
                
            break;
            case '5':
                $batchList = kernel::single('ome_refund_apply')->return_batch('refuse_return');
                $return_batch = $batchList[$shop_id];
                $params['oid'] = $return_tmall['oid'];
                $params['imgext']         = $aftersale['imgext'];                
                $params['refuse_proof']   = $aftersale['refuse_proof'];
                $params['refuse_message'] = $aftersale['refuse_message'];
      
            break;
        }
        
        return $params;
    }
    protected function aftersale_api($status){
        $api_method = '';
        switch( $status ){
            case '3':
                $api_method = AGREE_RETURN_I_GOOD_TMALL;
            break;
            case '5':
                $api_method = REFUSE_RETURN_I_GOOD_TMALL;
            break;
        }
        return $api_method;
    }

    /**
     * 获取电子面单
     *
     * @param Array $data
     * @return void
     * @author shshuai
     **/
    public function get_waybill_number($data){
        $param = array(
            'user_id' => $this->_shop['addon']['tb_user_id'],
            'num' => $data['num'],
            'service_code' => $data['service_code'],
            'out_biz_code' => $data['out_biz_code'],
            'pool_type' => $data['pool_type'],
        );
        $callback = array(
           'class' => get_class($this),
           'method' => 'get_waybill_number_callback',
        );
        $shop_id = $this->_shop['shop_id'];
        $title = '店铺('.$this->_shop['name'].')获取电子面单';

        $log_id = $this->_caller->request(GET_WAYBILL_NUMBER,$param,$callback,$title,$shop_id,10,false,$addon);

        return true;
    }

    public function get_waybill_number_callback($result) {
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();
        $data = $result->get_data();
        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];

        $ret = $this->_caller->callback($result);

        $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
        if ($status == 'succ' && $data['tms_waybill_list']['string']){
            $waybillObj = app::get('logisticsmanager')->model('waybill');
            $channelObj = app::get('logisticsmanager')->model('channel');
            $wlbObj = kernel::single('logisticsmanager_waybill_wlb');
            $logistics_code = $wlbObj->logistics_code($request_params['service_code'],$request_params['pool_type']);
            //获取单号来源信息
            $cFilter = array(
                'shop_id' => $shop_id,
                'logistics_code' => $logistics_code,
                'status'=>'true',
            );
            $channel = $channelObj->dump($cFilter);

            //保存数据
            if($channel['channel_id'] && $logistics_code) {
                foreach($data['tms_waybill_list']['string'] as $val){
                    $waybill = array();
                    $waybill = $waybillObj->dump(array('waybill_number'=>$val),'id');
                    if(!$waybill['id'] && $val) {
                        $logisticsNo = array(
                            'waybill_number' => $val,
                            'channel_id' => $channel['channel_id'],
                            'logistics_code' => $logistics_code,
                            'status' => 0,
                        );
                        $waybillObj->insert($logisticsNo);
                    }
                    unset($val,$logisticsNo,$waybill);
                }
                $waybillLogObj->update(array('status'=>'success'),array('log_id'=>$request_params['out_biz_code']));
            }
        } else {
            $waybillLogObj->update(array('status'=>'fail'),array('log_id'=>$request_params['out_biz_code']));
        }

        return $ret;
    }

    /**
     +----------------------------------------------------------
     * [拆单]天猫回写参数格式化 ExBOY
     +----------------------------------------------------------
     * return   array   $delivery
     +----------------------------------------------------------
     */
    protected function format_delivery($delivery)
    {
        $delivery   = parent::format_delivery($delivery);
        $order_id   = $delivery['order']['order_id'];
        $order_bn   = $delivery['order']['order_bn'];
        
        #[部分发货]未发货的发货单不进行回写
        if($delivery['order']['ship_status'] == '2' && $delivery['status'] != 'succ')
        {
            return false;// && $delivery['process'] != 'true'
        }
        
        #售后发货单直接回写$delivery
        if($delivery['type'] == 'reject')
        {
            return $delivery;
        }
        
        #[发货配置]拆单发货回写方式
        $split_model   = parent::getDeliverySeting();
        if($split_model == 0)
        {
            return $delivery;//未开启拆单,直接回写
        }
        
        #判断订单是否已拆单
        $chk_split  = parent::check_order_is_split($delivery, true);
        if($chk_split == false)
        {
            return $delivery;//未进行拆单操作,直接回写
        }
        
        #判断"拆单方式"配置是否变更
        $is_change_set  = false;
        $change_split   = parent::get_split_model_change($order_id);
        
        if(!empty($change_split))
        {
            if($change_split['old_split_model'] == 2)
            {
                return $delivery;//直接回写全部[按SKU拆单]
            }
            elseif($change_split['old_split_model'] == 1)
            {
                $split_model    = 1;//延续上次的[按子订单方式]回写
                $is_change_set  = true;//与"余单撤消"处理过程一致
            }
        }
        
        #拆单回写方式
        if($split_model == 1)
        {
            //获取天猫订单进入ERP的原始数据
            $mdl_orddly = &app::get('ome')->model('order_delivery');
            $getData    = $mdl_orddly->getList('id, bn, oid', array('order_bn'=>$order_bn), 0, 1);
            $getData    = $getData[0];

            if(empty($getData['oid']))
            {
                return $delivery;//没有天猫原数据对比,直接回写
            }
            $bn_data   = unserialize($getData['bn']);
            $oid_data  = explode(',', $getData['oid']);
            
            /*------------------------------------------------------ */
            //-- “余单撤消”回写操作
            /*------------------------------------------------------ */
            $remain_cancel  = parent::order_remain_cancel($order_id);
            if($remain_cancel || $is_change_set)
            {
                if($is_change_set)
                {
                    $delivery_data  = parent::get_delivery_succ($order_id, $delivery['delivery_id']);//配置变更_排除本次的delivery_id
                }
                else 
                {
                    $delivery_data  = parent::get_delivery_succ($order_id);//获取订单关联的所有成功发货的"发货单"
                }
                
                if(empty($delivery_data))
                {
                    return $delivery;
                }
                
                $delivery_ids   = array();
                foreach ($delivery_data as $key => $val)
                {
                    $delivery_ids[] = $val['delivery_id'];
                }
                
                //发货单明细
                $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
                $develiy_items = $deliItemModel->getList('product_id, bn, number', array('delivery_id'=>$delivery_ids));
                
                //获取购买商品的bn
                $goods_bn     = array();
                foreach($develiy_items as $key => $item)
                {
                    $val_bn            = trim($item['bn']);
                    $goods_bn[$val_bn] = $val_bn;
                }
                
                //获取订单原始数据[货号对应的oid]
                $buy_oid   = array();
                foreach($bn_data as $key => $val)
                {
                    $val          = trim($val);
                    if(!empty($goods_bn[$val]))
                    {
                        $buy_oid[]   = $oid_data[$key];
                    }
                }
                $buy_oid    = array_diff($oid_data, $buy_oid);
                
                if(!empty($buy_oid))
                {
                    $delivery['oid_list'] = implode(',', $buy_oid);
                    $delivery['is_split'] = 1;
                }
                
                return $delivery;
            }
            
            /*------------------------------------------------------ */
            //-- 判断订单中购买商品如果被"删除或调换"则直接回写
            /*------------------------------------------------------ */
            
            //获取现系统中的订单数据
            $in_oids        = '';
            foreach ($oid_data as $key => $val)
            {
                $in_oids   .= ",'".$val."'";
            }
            $in_oids    = substr($in_oids, 1);

            $sql        = "SELECT oi.item_id, oi.delete, ob.obj_id, ob.oid FROM sdb_ome_order_items AS oi LEFT JOIN sdb_ome_order_objects AS ob ON oi.obj_id=ob.obj_id 
                            WHERE oi.order_id='".$order_id."' AND ob.oid in(".$in_oids.")";
            $result     = kernel::database()->select($sql);
            
            $erp_oids   = array();
            foreach ($oid_data as $key_j => $val_j)
            {
                foreach ($result as $key => $item)
                {
                    $val_oid    = $item['oid'];
                    if($val_oid == $val_j && $item['delete']=='false')
                    {
                        $erp_oids[$val_oid] = $val_oid;
                    }
                }
            }
            $chk_data   = array_diff($oid_data, $erp_oids);
            
            //有变更的订单商品，直接回写
            if(!empty($chk_data))
            {
                return $delivery;
            }
            
            /*------------------------------------------------------ */
            //-- 按天猫子订单方式进行拆单回写 [回写多次]          
            /*------------------------------------------------------ */
            
            //发货单明细
            $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
            $develiy_items = $deliItemModel->getList('product_id, bn, number', array('delivery_id'=>$delivery['delivery_id']));
            
            //获取购买商品的bn
            $goods_bn     = array();
            foreach($develiy_items as $key => $item)
            {
                $val_bn            = trim($item['bn']);
                $goods_bn[$val_bn] = $val_bn;
            }
            
            //获取订单原始数据[货号对应的oid]
            $buy_oid   = array();
            foreach($bn_data as $key => $val)
            {
                $val          = trim($val);
                if(!empty($goods_bn[$val]))
                {
                    $buy_oid[]   = $oid_data[$key];
                }
            }
            
            if(!empty($buy_oid))
            {
                $delivery['oid_list'] = implode(',', $buy_oid);
                $delivery['is_split'] = 1;
            }
        }
        else 
        {
            //按sku进行拆单,只回写一次[无需处理，直接回写]
        }
        
        return $delivery;
    }
}