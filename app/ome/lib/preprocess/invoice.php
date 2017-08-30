<?php

class ome_preprocess_invoice {

    /**
     * 处理一号店的发票抬头信息获取
     */
    public function process($order_id,&$msg){
        if(!$order_id){
            $msg = '缺少处理参数';
            return false;
        }

        $orderObj = &app::get('ome')->model('orders');
        $opinfo = kernel::single('ome_func')->get_system();
        $operationLogObj = &app::get('ome')->model('operation_log');

        $orderInfo = $orderObj->dump(array('order_id'=>$order_id,'shop_type'=>array('yihaodian','vjia'),'is_tax'=>'true'),'order_bn,shop_id,tax_company');
        if(!$orderInfo || (isset($orderInfo['tax_title']) && !empty($orderInfo['tax_title']) && !is_null($orderInfo['tax_title']))){
            return true;
        }

        $invoiceInfo = $this->getInvoiceFromYiHaoDian($orderInfo['order_bn'],$orderInfo['shop_id']);
        if($invoiceInfo && isset($invoiceInfo['invoice'][0]['invoice_title']) && !empty($invoiceInfo['invoice'][0]['invoice_title']) && !is_null($invoiceInfo['invoice'][0]['invoice_title']) && $invoiceInfo['invoice'][0]['invoice_title'] != 'null'){
            $data['order_id'] = $order_id;
            $data['tax_title'] = $invoiceInfo['invoice'][0]['invoice_title'];//暂时只取第一张发票抬头
            $orderObj->save($data);

            $operationLogObj->write_log('order_preprocess@ome',$order_id,'订单预处理获取发票信息',time(),$opinfo);
            return true;
        }else{
            $msg = '没有找到发票详细信息';
            $operationLogObj->write_log('order_preprocess@ome',$order_id,$msg,time(),$opinfo);            
            return false;
        }
    }

    /**
     * 根据订单号tid和shop_id获取发票详细信息
     */
    private function getInvoiceFromYiHaoDian($tid,$shop_id){
        if(!$tid || !$shop_id){
            return false;
        }
        
        $rs = kernel::single('ome_service_order')->get_invoice($tid,$shop_id);

        return $rs;

        /*
        $api_name ='store.trade.invoice.get';
        $param = array(
            'tid' => $tid,
        );
        $timeout = 5;

        $result = kernel::single('ome_rpc_request')->call($api_name, $param, $shop_id, $timeout);
        if($result){
            if($result->rsp == 'succ'){
                $tmp = json_decode($result->data,true);
                return $tmp;
            }else{
                return false;
            }
        }else{
            return false;
        }*/
    }

}
