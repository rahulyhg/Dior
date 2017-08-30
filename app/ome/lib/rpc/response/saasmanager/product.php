<?php
class ome_rpc_response_saasmanager_product
{
    function do_reset_freeze($data,& $apiObj){
        //danny_freeze_stock_log
        define('FRST_TRIGGER_OBJECT_TYPE','saas中心接口重置所有货品冻结库存');
        define('FRST_TRIGGER_ACTION_TYPE','ome_rpc_response_saasmanager_product：do_reset_freeze');
        $productObj = kernel::single('ome_sync_product');
        $productObj->reset_freeze();

        $apiObj->api_response('已重置'); 
    }
}
