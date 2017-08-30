<?php
class ome_order_to_api {

    function run(&$cursor_id,$params){

        set_time_limit(300);
        $orderObj = app::get('ome')->model('orders');
        if (!is_array($params)){
            $params = unserialize($params);
        }
        $Sdf = $params['sdfdata'];
        $sdf_data = array();
        if ($Sdf)
        foreach ($Sdf as $k=>$v){
            //danny_freeze_stock_log
            define('FRST_TRIGGER_OBJECT_TYPE','订单：订单超过失效时间取消');
            define('FRST_TRIGGER_ACTION_TYPE','ome_order_to_api：run');
            $memo = "此订单已过期，且未付款未确认 ";
            $orderInfo = $orderObj->dump($v,'source');
            if($orderInfo['source'] == 'local'){
                $orderObj->cancel($v,$memo,false,'async');
            }else{
                $orderObj->cancel($v,$memo);
            }
        }
        return false;
    }
}
