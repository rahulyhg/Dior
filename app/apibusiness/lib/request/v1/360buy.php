<?php
/**
* 360buy接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: 360buy.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_360buy extends apibusiness_request_partyabstract
{
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
            'company_code' => $delivery['dly_corp']['type'],
            'company_name' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no' => $delivery['logi_no'] ? $delivery['logi_no'] : '',
            '360buy_business_type' => $this->_shop['addon']['type'],
        );
        if ($this->_shop['addon']['type'] == 'SOPL') {
            $param['package_num'] = $delivery['itemNum'];
        }
        return $param;
    }// TODO TEST

    /**
     * 发货请求
     *
     * @return void
     * @author 
     **/
    protected function delivery_request($delivery)
    {

        $delivery = $this->format_delivery($delivery);

        if ($delivery === false) return false;

        $param = $this->getDeliveryParam($delivery);

        if ($delivery['type'] == 'reject') {
            $title = '店铺('.$this->_shop['name'].')[京东360BUY]添加[交易发货单](<font color="red">补差价</font>订单号:'.$delivery['order']['order_bn'].')';
        } else {
            $title = '店铺('.$this->_shop['name'].')[京东360BUY]添加[交易发货单](订单号:'.$delivery['order']['order_bn'].',发货单号:'.$delivery['delivery_bn'].')';
        }

        $shop_id = $delivery['shop_id'];
        
        // $result = $this->_caller->call(LOGISTICS_OFFLINE_RPC,$param,$shop_id);
        $result = $this->syncCall(LOGISTICS_OFFLINE_RPC,$param,$shop_id);

        $apiLogModel = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $apiLogModel->gen_id();
        
        $callback = array(
            'class'   => get_class($this),
            'method'  => 'add',
            '2'       => array(
                'log_id'  => $log_id,
                'shop_id' => $shop_id,
            ),
        );

        $apiLogModel->write_log($log_id,$title,'apibusiness_router_request','add_delivery',array(LOGISTICS_OFFLINE_RPC, $param, $callback),'','request','running','','','api.store.trade.delivery',$delivery['order']['order_bn']);

        if (!$result) return false;

        if ($result->rsp == 'succ') {
            $log['status']     = 'succ';
            $log['updateTime'] = time();
            $log['message']    = $result->data;

            $api_status = 'success';

            $msg = '发货成功<BR>';

            $apiLogModel->update(array('msg_id'=>$result->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));

            $status = 'succ';
        } elseif ($result->rsp == 'fail') {
            if($result->res =='w06105'){
                //发货日志记录
                $log['status']     = 'succ';
                $log['updateTime'] = time();
                $log['message']    = 'w06105';
                //api日志记录
                $api_status = 'success';

                $msg = '发货成功('.$this->jdErrorMsg($result->res).')<BR>';

                $apiLogModel->update(array('msg_id'=>$result->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
                
                //订单回写状态
                $status = 'succ';
            }else{
                //发货日志记录
                $log['status']     = 'fail';
                $log['updateTime'] = time();
                $log['message']    = $result->data;

                //api日志记录
                $api_status = 'fail';

                $err_msg = $result->err_msg ? $result->err_msg : $this->jdErrorMsg($result->res);
                
                $msg = '发货失败('.$err_msg.')<BR>';
                
                $apiLogModel->update(array('msg_id'=>$result->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
                
                //订单回写状态
                $status = 'fail';
            }
        }

        $opInfo = kernel::single('ome_func')->getDesktopUser();
        //增加更新发货状态日志
        $shipment_log = array(
            'shopId'           => $shop_id,
            'ownerId'          => $opInfo['op_id'],
            'orderBn'          => $delivery['order']['order_bn'],
            'deliveryCode'     => $delivery['logi_no'],
            'deliveryCropCode' => $delivery['dly_corp']['type'],
            'deliveryCropName' => $delivery['logi_name'],
            'receiveTime'      => time(),
            'status'           => 'send',
            'updateTime'       => '0',
            'message'          => $result->err_msg,
            'log_id'           => $log_id,
        );

        $log = array_merge($shipment_log,$log);
        if(!$log['message']){
            $log['message'] = $status=='succ' ? '发货成功' : '发货失败';
        }

        $shipmentLogModel = app::get(self::_APP_NAME)->model('shipment_log');
        $shipmentLogModel->save($log);

        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $orderModel->update(array('sync' => $status),array('order_id'=>$delivery['order']['order_id']));
        
        return true;
    }// TODO TEST

    /**
     * @根据矩阵返回的错误码，表述具体京东请求的返回消息内容
     * @access public
     * @param void
     * @return void
     */
    public function jdErrorMsg($code)
    {
        $errormsgs = array(
                    'w06000'=>'成功',
                    'w06001'=>'其他',
                    'w06101'=>'已经出库',
                    'w06102'=>'出库订单不存在或已被删除',
                    'w06104'=>'订单状态不为等待发货',
                    'w06105'=>'订单已经发货',
                    'w06106'=>'正在出库中',
        );
        return isset($errormsgs[$code]) ? $errormsgs[$code] : '其他';
    }

    
    
    
    protected function format_aftersale_params($aftersale,$status){
        $params = array(
                
            'refund_id'=>$aftersale['return_bn'],
        );
        return $params;
    }

    protected function aftersale_api($status){
        $api_method = '';
        switch( $status ){
            case '4':
                $api_method = CHECK_REFUND_GOOD;
            break;
            
        }
        return $api_method;
    }
    
    public function update_order_shippinginfo($order)
    {
        
    }

}