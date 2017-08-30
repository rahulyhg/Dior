<?php
/**
* meilishuo（美丽说)接口请求实现
*/
class apibusiness_request_v1_meilishuo extends apibusiness_request_partyabstract{
    
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
    /**
     * 取得退款申请对应状态接口名     *
     * @return void
     * @author
     **/
    protected function refund_apply_api($status){
        $api_method = '';
        switch($status){
            #同意退款
            case '2':
                $api_method = MEILISHUO_REFUND_GOOD_RETURN_AGREE;
                break;
        }
        return $api_method;
    }
    protected function aftersale_api($status){
        $api_method = '';
        switch( $status ){
            #同意退货
            case '3':
                $api_method = MEILISHUO_REFUND_GOOD_RETURN_AGREE;
                break;
        }
        return $api_method;
    }
    protected function format_aftersale_params($aftersale,$status){
        $params = array(
                'refund_id'     =>$aftersale['return_bn'],
                //'addr_id'  => '',
        );
        return $params;
    }
    protected function format_refund_applyParams($refund,$status){
        $params = array(
                'refund_id'  =>$refund['refund_apply_bn'],
                //'addr_id'  => '',
        );
        return $params;
    }              
}
