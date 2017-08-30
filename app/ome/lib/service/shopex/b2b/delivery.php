<?php
/**
 * shopex_b2b发货
 * 此类负责是否发起发货请求及请求的数据参数
 * @author ome 
 * @copyright 2011.1.20
 *
 */
class ome_service_shopex_b2b_delivery{
    
    /**
     * 添加发货单
     * @param int $delivery_id 发货单ID
     * @param 引用 $is_request 是否发起请求
     */
    function add($delivery_id,&$is_request){
        //TODO:暂时只返回是否发起请求的标识，发起的数据参数后期完善
        $deliveryObj = &app::get('ome')->model('delivery');
        $delivery_detail = $deliveryObj->dump($delivery_id, 'process');
        if ($delivery_detail['process'] == 'true'){
            $is_request = 'true';
        }else{
            $is_request = 'false';
        }
        return NULL;
    }
    
}