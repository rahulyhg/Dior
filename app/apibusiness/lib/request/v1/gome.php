<?php
/**
* gome(国美)接口请求实现
*/
class apibusiness_request_v1_gome extends apibusiness_request_partyabstract
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
        $company_code = trim($delivery['dly_corp']['type']);
        $shop_id = $delivery['order']['shop_id'];
        $gome_code = array('wu074quanfeng','GOME_ZJS');#国美代运物流
        $addressShort_name = null;
        #如果承运商为国美代运,addressShort_name是商家在后配置的发货地址
        if(in_array($company_code,$gome_code)){
            $obj_shop = &app::get('ome')->model('shop');
            $shop_info = $obj_shop->dump($shop_id,'addr');
            $addressShort_name = $shop_info['addr'];
        }
        
        $param = array(
                'tid'          => $delivery['order']['order_bn'],
                'company_code' => $company_code,
                'logistics_no' => $delivery['logi_no'] ? $delivery['logi_no'] : '',
                'addressShort_name' => $addressShort_name #商家在后配置的发货地址（如果承运商为国美代运此项为必填项）
        );
        
        return $param;
    } 
}
