<?php
/**
* vjia(凡客平台)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
*/
class apibusiness_request_v1_vjia extends apibusiness_request_partyabstract
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
        //如果出库失败发货时再次发送出库请求
        $outstorageRpc = new ome_preprocess_outstorage();
        $outstorageRpc->process($delivery['order']['order_id'],$msg);

        $param = array(
            'tid'          => $delivery['order']['order_bn'],
            'company_code' => trim($delivery['dly_corp']['type']),
            'company_name' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no' => $delivery['logi_no'] ? $delivery['logi_no'] : '',
        );

        return $param;
    }

    /**
     * 取得发货接口
     *
     * @return void
     * @author 
     **/
    protected function delivery_api($delivery = '')
    {
        return DELIVERY_OUT_STORAGE_CONFIRM;
    }

    /**
     * 发送出库请求
     *
     * @param Array $params
     * @return void
     * @author 
     **/
    public function outstorage_request($params) {
        $result = array();
        $order_id = $params['order_id'];
        unset($params['order_id']);

        $shop_id = $this->_shop['shop_id'];
        $title = '店铺('.$this->_shop['name'].')发货出库(订单号:'.$params['tid'].')';
        $api_name = DELIVERY_OUT_STORAGE;

        //记录api日志
        $apiLogObj = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $apiLogObj->gen_id();
        $apiLogObj->write_log($log_id,$title,'apibusiness_request_v1_vjia','outstorage_request',array($api_name,$params,array()),'','request','running','','',$api_name,$params['tid']);

        //发送出库请求
        $rsp = $this->_caller->call($api_name,$params,$shop_id,10);
        if($rsp){
            $outstorageObj = app::get('ome')->model('order_outstorage');
            if($rsp->rsp == 'succ'){
                $result['rsp'] = 'succ';
                //更新api日志
                $api_status = 'success';
                $msg = '发货出库成功<BR>';
                $log_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
                $apiLogObj->update($log_data,array('log_id'=>$log_id));

                //出库成功删除失败记录
                $outstorageObj->delete(array('order_id'=>$order_id));
            }else{
                //更新api日志记录
                $api_status = 'fail';
                $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                $msg = '发货出库失败('.$err_msg.')<BR>';
                $log_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
                $apiLogObj->update($log_data,array('log_id'=>$log_id));

                //记录出库失败信息
                $result['rsp'] = 'fail';
                $result['msg'] = $err_msg;

                //记录出库失败订单
                $outstorage = array('order_id'=>$order_id);
                $outstorageObj->insert($outstorage);
            }
        }

        return $result;
    }

    /**
     * 修改配送信息
     *
     * @param Array $params
     * @return void
     * @author 
     **/
    public function logistics_modify($params) {
        $shop_id = $this->_shop['shop_id'];
        $title = '店铺('.$this->_shop['name'].')修改配送信息(订单号:'.$params['tid'].')';
        $api_name = DELIVERY_CONSIGN_RESEND;

        //记录api日志
        $apiLogObj = app::get(self::_APP_NAME)->model('api_log');
        $log_id = $apiLogObj->gen_id();
        $apiLogObj->write_log($log_id,$title,'apibusiness_request_v1_vjia','logistics_modify',array($api_name,$params,array()),'','request','running','','',$api_name,$params['tid']);

        //发送出库请求
        $rsp = $this->_caller->call($api_name,$params,$shop_id,10);
        if($rsp){
            if($rsp->rsp == 'succ'){
                //更新api日志
                $api_status = 'success';
                $msg = '修改配送信息成功<BR>';
                $log_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
                $apiLogObj->update($log_data,array('log_id'=>$log_id));
            }else{
                //更新api日志记录
                $api_status = 'fail';
                $err_msg = $rsp->err_msg ? $rsp->err_msg : $rsp->res;
                $msg = '修改配送信息失败('.$err_msg.')<BR>';
                $log_data = array('msg_id'=>$rsp->msg_id,'msg'=>$msg,'status'=>$api_status);
                $apiLogObj->update($log_data,array('log_id'=>$log_id));
            }
        }

        return true;
    }

    public function update_order_shippinginfo($order)
    {
        
    }
}