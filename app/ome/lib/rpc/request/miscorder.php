<?php

    /**
     * ShopEx licence
     *
     * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
     * @license  http://ecos.shopex.cn/ ShopEx License
     * @version osc---hanbingshu sanow@126.com
     * @date 2012-06-06
     */
class ome_rpc_request_miscorder
{
    function __construct($app)
    {
        $this->app = $app;
    }
    //获取指定时间范围内前端店铺的订单列表 每一小时执行一次
    function getlist_order()
    {
        $run_time = 60; //每次执行脚本的时间间隔 单位 分钟
        $shop = $this->app->model("shop");
        $orders = $this->app->model("orders");
        $c2c_shop_list = ome_shop_type::shop_list();
        $filter = array('filter_sql'=>' node_type not in("'.implode('","',$c2c_shop_list).'") and api_version !="" ');
        $shop_data = $shop->getList("shop_id,name,node_id",$filter);
        $rpc_request_order = kernel::single("ome_rpc_request_order");
        $omequeueModel = kernel::single("ome_syncshoporder");
        $apilog = &app::get('ome')->model('api_order_log');
        $time = time();
        $last_time = $this->app->getConf("getlist_order_sync_time");

        if(time()-$last_time>$run_time*60)  //执行时间间隔到了一小时
        {
            //获取订单
            foreach ($shop_data as $k => $val) {
                if(empty($val['node_id'])) continue;
                $shop_last_time = $this->app->getConf("getlist_order_shop_last_time".$val['node_id']);//店铺上一次同步时间
                if(empty($shop_last_time)) //如果店铺上次同步时间没有就取该店铺在TG订单中第一个订单创建时间
                {
                    $shop_last_time = time()-60*60;
                }

                if(($time-$shop_last_time)>14*24*3600) $shop_last_time = $time - 14*24*3600;

                $api_params['start_time'] = date('Y-m-d H:i:s',$shop_last_time);
                $api_params['end_time'] = date('Y-m-d H:i:s',$time);
                $method = 'store.trades.sold.get';
                $class = "ome_syncorder";
                $sdf_log = array();

                $log_title_start = date("Y-m-d H:i:s",$shop_last_time);
                $log_title_end = date("Y-m-d H:i:s",$time);
                $title = "获取店铺【".$val['name']."】从".$log_title_start."到".$log_title_end."时间范围内的订单数据";

                $log_id = $apilog->gen_id();

                $rpc_callback = array('ome_rpc_request','callback',array('log_id'=>$log_id,'shop_id'=>$val['shop_id']));

                $apilog->write_log($log_id,$title,'ome_rpc_request','rpc_request',array($method, $api_params, $rpc_callback));

                //$return = $rpc_request_order->get_order_list($shop_last_time,$time,$val['shop_id']);
                $return = kernel::single('apibusiness_router_request')->setShopId($val['shop_id'])->get_order_list($shop_last_time,$time);

                if($return['rsp'] == 'success')
                {
                    $query_data = array();
                    $queue_title = "获取店铺【".$val['name']."】指定时间范围内的订单数据";
                    $queue_id = '';
                    if(!empty($return['data'])){
                        foreach((array)$return['data'] as $k1=>$v1)
                        {
                            $params = array();
                            $params['shop_id'] = $val['shop_id'];
                            $params['shop_name'] = $val['name'];
                            $params['order_bn'] = $v1['tid'];
                            $status = 'running';
                            $queue_id = $omequeueModel->create($queue_title,$method,$params,$apilog,$status);
                        }
                    }
                    $params['data'] = $return['data'];
                    $params['shop_id'] = $val['shop_id'];
                    $msg = $return['msg'];
                    $msg_id = $return['msg_id'];
                    $apilog->update_log($log_id,$msg,'success',$params,$msg_id); //更新同步日志状态
                    if($queue_id || $return['is_update_time'] = 'true') $this->app->setConf("getlist_order_shop_last_time".$val['node_id'],time());  //获取到数据并成功插入队列之后 修改该店铺的订单同步时间

                }else{
                    $msg = $return['msg'];
                    $msg_id = $return['msg_id'];
                    $apilog->update_log($log_id,$msg,'fail','',$msg_id);//更新同步日志状态
                }

            }

            $this->app->setConf("getlist_order_sync_time",$time);

        }
        return true;
    }

}