<?php
/*
 * 自动取消过期未支付且未确认的订单(除货到付款)
 */
class ome_sync_order{

    function cancel_order(){

        $time = time();
        /*排除shopex内部系统的前端店铺 --begin--*/
        //$remove_shopex = '';
        $shopex_shop_list = ome_shop_type::shopex_shop_type();
        //$remove_shopex = ' AND shop_type NO IN('.implode(',', $shopex_shop_list).')';
        /*排除shopex内部系统的前端店铺 --end--*/
        $oQueue = &app::get('base')->model('queue');
        $selectWhere = "SELECT count(*)";
        $sql0 = " FROM `sdb_ome_orders` WHERE order_limit_time is NOT NULL AND order_limit_time<'".$time."' AND pay_status='0' AND process_status='unconfirmed' AND is_cod='false' AND shop_type IN('".implode("','", $shopex_shop_list)."')";
        $sql = $selectWhere.$sql0;
        $count = kernel::database()->count($sql);
        $page = 1;
        $limit = 10;
        $pagecount = ceil($count/$limit);
        for ($i=0;$i<$pagecount;$i++){
            $lim = ($page+$i-1)*$limit;
            $sql = " SELECT order_id ".$sql0." LIMIT ".$lim.",".$limit;
            $data = kernel::database()->select($sql);
            if ($data){
                $sdfdata['order_id'] = array();
                foreach ($data as $k=>$v){
                    $sdfdata['order_id'][] = $v['order_id'];
                }
                $queueData = array(
                    'queue_title'=>'自动取消过期订单队列'.$page.'(共'.count($sdfdata['order_id']).'个)',
                    'start_time'=>$time,
                    'params'=>array(
                        'sdfdata'=>$sdfdata['order_id'],
                        'app' => 'ome',
                        'mdl' => 'order'
                    ),
                    'status' => 'hibernate',
                    'worker'=> 'ome_order_to_api.run',
                   );
                $oQueue->save($queueData);

                $log = &app::get('ome')->model('api_log');
                $log->write_log($log->gen_id(), '自动取消订单同步', __CLASS__, __METHOD__, '', '', 'response', 'success', var_export($queueData, true));
            }
        }

    }

    /**
     * 自动重新发起发货请求
     * 没有启用
     */
    public function retry_shipment($limit = 100){
        $sql = 'SELECT delivery_id FROM sdb_ome_delivery WHERE process="true" AND has_checked="0" LIMIT '.$limit;
        $orders = kernel::database()->select($sql);
        if($orders) {
            foreach($orders as $v) {
                $delivery_id = $v['delivery_id'];

                //获取订单编号 sdb_ome_orders:order_bn
                $sql = 'SELECT order_id FROM sdb_ome_delivery_order WHERE delivery_id="'.$delivery_id.'" LIMIT 1';
                $order = kernel::database()->select($sql);
                $order_id = $order[0]['order_id'];

                $sql = 'SELECT order_bn FROM sdb_ome_orders WHERE order_id="'.$order_id.'" LIMIT 1';
                $order = kernel::database()->select($sql);
                $order_bn = $order[0]['order_bn'];

                //检查是否存在api日志
               //$sql = 'SELECT log_id FROM sdb_ome_api_log WHERE task_name like "%(订单号:'.$order_bn.',发货单号%" LIMIT 1';
                $apiObj = &app::get('ome')->model('api_log');
                $filter = array(
                    'task_name|has' => '(订单号:'.$order_bn.',发货单号',
                );
                $log = $apiObj->getList('*',$filter,0,1);
                //$log = kernel::database()->select($sql);
                if($log) {
                    $sql = 'UPDATE sdb_ome_delivery SET has_checked="1" WHERE delivery_id='.$delivery_id.' LIMIT 1';
                    kernel::database()->exec($sql);
                    continue;
                }

                //发货API
                foreach(kernel::servicelist('service.delivery') as $object=>$instance){
                    if(method_exists($instance,'delivery')){
                        $instance->delivery($delivery_id);
                    }
                }
            }
        }
    }

}