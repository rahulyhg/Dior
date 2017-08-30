<?php
class apibusiness_response_logistics_hqepay_v1 extends apibusiness_response_logistics_v1{
    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($Logisticsdf = array()){
        return parent::canAccept($Logisticsdf);
    }
    /**
     * 添加物流信息
     *
     * @return void
     * @author 
     **/
    public function push(){
       $obj_delivery = app::get(self::_APP_NAME)->model('delivery');
       $oOperation_log = app::get(self::_APP_NAME)->model('operation_log');
       $sdf = $this->Logisticsdf;
       #拒收非已签收物流信息
       if($sdf['State'] != '3'){
           $this->_apiLog['title'] = '接受物流信息（物流单号：' . $sdf['LogisticCode']. '）';
           $this->_apiLog['info'][] = "接受参数：".var_export($sdf,true);
           $this->_apiLog['info']['msg'] = '未签收单号不接收';
           return false;
       }
       $logi_no =  $sdf['LogisticCode'];
       if(empty($logi_no)){
           $this->_apiLog['title'] = '接受物流信息（物流单号：' . $sdf['LogisticCode']. '）';
           $this->_apiLog['info'][] = "接受参数：".var_export($sdf,true);
           $this->_apiLog['info']['msg'] = '缺少物流单号不接收！';
           return false;
       }
       #已发货的发货单
       $filter['process'] = "TRUE";
       $filter['pause'] = "FALSE";
       $filter['logi_no'] = $logi_no;
       $filter['status'] = 'succ';
       $filter['is_received'] = '0';#未签收
       
       $delivery_info = $obj_delivery->getList('delivery_id,is_cod',$filter);#补打的物流单号，不用管了
       if(empty( $delivery_info)){
           $this->_apiLog['title'] = '接受物流信息（物流单号：' . $sdf['LogisticCode']. '）';
           $this->_apiLog['info'][] = "接受参数：".var_export($sdf,true);
           $this->_apiLog['info']['msg'] = '发货单已签收或不存在,不接受！';
           return false;
       }
       $obj_delivery->update(array('is_received'=>'1'),array('delivery_id'=>  $delivery_info[0]['delivery_id'])); #更新物流签收状态
       $memo ='更新签收状态为：已签收（物流单号：'.$logi_no.'）';
       $oOperation_log->write_log('delivery_process@ome',$delivery_info[0]['delivery_id'],$memo);
       
       $this->_apiLog['title'] = '接受物流信息（物流单号：' . $sdf['LogisticCode']. '）';
       $this->_apiLog['info'][] = "接受参数：".var_export($sdf,true);
       $this->_apiLog['info']['msg'] = '更新签收状态成功！';

       if($delivery_info[0]['is_cod'] == 'true'){#货到付款，自动将订单转为已支付
           $db = kernel::database();
           $transaction_status = $db->beginTransaction();

          $objOrder = app::get(self::_APP_NAME)->model('orders');
          $oPayment = app::get(self::_APP_NAME)->model('payments');

           $order_deliverys = app::get(self::_APP_NAME)->model('delivery_order')->getList('*',array('delivery_id'=>$delivery_info[0]['delivery_id']));

           foreach ($order_deliverys as $od) {
             $arrOrder = $objOrder->dump(array('order_id'=>$od['order_id'],'is_cod'=>'true','pay_status'=>'0'), 'order_id,total_amount,ship_status,shop_id,currency');

             if (!$arrOrder) continue;

             if($arrOrder['order_id']) {
                 $orderData = array();
                 $orderData['payed']      = $arrOrder['total_amount'];
                 $orderData['pay_status'] = 1;
                 $orderData['paytime']    = time();
                 $orderData['payment']    = '线下支付';
                 if($arrOrder['ship_status'] == 1) {
                     $orderData['status'] = 'finish';
                 }
                 $orderFilter = array('order_id'=>$arrOrder['order_id']);
                 $objOrder->update($orderData, $orderFilter);
             }

             if($arrOrder['ship_status'] == 1) {
                 $objsales = app::get(self::_APP_NAME)->model('sales');
                 #检查销售单单是否存在
                 $sale_id = $objsales->getList('sale_id', array('order_id' => $arrOrder['order_id']));
                 if (!empty($sale_id)) {
                     $objsales->update(array('paytime' => $orderData['paytime']), array('order_id' => $arrOrder['order_id']));
                 }
             }

             //日志
             $memo = '签收COD订单自动确认支付';
             $oOperation_log->write_log('order_modify@ome',$arrOrder['order_id'],$memo);
             //生成支付单

             $payment_bn = $oPayment->gen_id();
             $paymentdata = array();
             $paymentdata['payment_bn'] = $payment_bn;
             $paymentdata['order_id'] = $arrOrder['order_id'];
             $paymentdata['shop_id'] = $arrOrder['shop_id'];
             $paymentdata['currency'] = $arrOrder['currency'];
             $paymentdata['money'] = $orderData['payed'];
             $paymentdata['paycost'] = 0;
             $curr_time = time();
             $paymentdata['t_begin'] = $curr_time;//支付开始时间
             $paymentdata['t_end'] = $curr_time;//支付结束时间
             $paymentdata['cur_money'] = $orderData['payed'];
             $paymentdata['pay_type'] = 'offline';
             $paymentdata['ip'] = kernel::single("base_request")->get_remote_addr();
             $paymentdata['status'] = 'succ';
             $paymentdata['memo'] = '签收COD订单系统自动确认支付';
             $paymentdata['is_orderupdate'] = 'false';
             $oPayment->create_payments($paymentdata);

             //日志
             $oOperation_log->write_log('payment_create@ome',$paymentdata['payment_id'],'生成支付单');
           }

           $db->commit($transaction_status);
       }
    }
}
?>
