<?php
/**
* ccb（建设银行)接口请求实现
*/
class apibusiness_request_v1_ccb extends apibusiness_request_partyabstract{
    
    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author
     **/
    protected function getDeliveryParam($delivery){
        $param = array(
                'tid'          => $delivery['order']['order_bn'],
                'company_code' => trim($delivery['dly_corp']['type']),
                'logistics_no' => $delivery['logi_no'] ? $delivery['logi_no'] : '',
        );
        return $param;
    }
}
