<?php
class ome_misc_task{

    function week(){

    }

    function minute(){
        //TODO:确认订单自动过期是1小时一次还是1分钟一次

        $time = time();

        //定时回写库存，每5分钟触发
        /*
        base_kvstore::instance('setting_ome')->fetch('auto_sync_store',$last_auto_sync_store);
        if($last_auto_sync_store){
            if($time >= ($last_auto_sync_store + 300)){
                //API同步日志每5分钟触发一次：以5分钟为基数将所有正在运行中的请求自动发起重试（最多3次）,超过3次的设置为失败)
                //kernel::single('ome_sync_api_log')->auto_retry();

                kernel::single('ome_sync_product')->run_stock_sync();
                base_kvstore::instance('setting_ome')->store('auto_sync_store',$time);
            }
        }else{
            base_kvstore::instance('setting_ome')->store('auto_sync_store',$time);
        }*/

        //自动取消订单(9分钟执行一次)：目标(将超时订单，且未付款未确认的订单取消，同时向各绑定前端店铺发送同步)
        base_kvstore::instance('setting_ome')->fetch('auto_sync_cancel_order',$last_auto_sync_cancel_order);
        if($last_auto_sync_cancel_order){
            if($time >= ($last_auto_sync_cancel_order + 540)){
                kernel::single('ome_sync_order')->cancel_order();
                base_kvstore::instance('setting_ome')->store('auto_sync_cancel_order',$time);
            }
        }else{
            base_kvstore::instance('setting_ome')->store('auto_sync_cancel_order',$time);
        }

        //重新发起漏发的发货请求，每5分钟一次
        /*
        base_kvstore::instance('setting_ome')->fetch('auto_sync_shipment',$last_auto_sync_shipment);
        if($last_auto_sync_shipment){
            if($time >= ($last_auto_sync_shipment + 60*5)){
                 kernel::single('ome_sync_order')->retry_shipment();
            }
        }else{
            base_kvstore::instance('setting_ome')->store('auto_sync_shipment',$time);
        }
        */
    }

    function hour(){
        // 将所有正在运行中或发起中，时间超过30分钟的同步日志设置为失败
        // $apiObj = &app::get('ome')->model('api_log');
        // $msg = '请求超时';
        // $updateSdf = array(
        //     'status' => 'fail',
        //     'msg' => $msg,
        // );
        // $updateFilter = array(
        //     'status' => array('running','sending'),
        //     'api_type' => 'request',
        //     'last_modified|<' => (int)(time()-1800),
        // );
        // $apiObj->update($updateSdf,$updateFilter);

        //将发起库存同步1小时后，还是“同步中”的日志状态改为“失败”
        $db = kernel::database();
        $sql = "UPDATE sdb_ome_api_stock_log SET status='fail' WHERE status='running' AND createtime<".(time()-3600);
        $db->exec($sql);

        //将发起发货同步超过30分钟，状态还是“发货同步中”的订单改成“失败”
        $sql = "SELECT distinct orderBn, shopId FROM sdb_ome_shipment_log WHERE status = 'send' AND receiveTime < ".(time()-1800);
        $orderlist = $db->select($sql);
        if($orderlist){
            foreach($orderlist as $v){
                $order_arr[$v['shopId']][] = $v['orderBn'];
            }
            $shipmentLogObj = app::get('ome')->model('shipment_log');
            $orderObj = app::get('ome')->model('orders');
            foreach($order_arr as $k => $val){
                $orderObj->update(array('sync'=>'fail'),array('order_bn'=>$val,'shop_id'=>$k,'sync'=>'run'));
                $shipmentLogObj->update(array('status'=>'fail'),array('orderBn'=>$val,'shopId'=>$k,'status'=>'send'));
            }
        }
    }

    function day(){

        //每天检测日志表，将超过7天的数据清除（放到副表，不实际删除）
        kernel::single('ome_sync_api_log')->clean();

        //csm系统统计
        //kernel::single('ome_rpc_request_csm')->request();

		//将超过2天的防止并发的历史数据清除
	    kernel::single('ome_concurrent')->clean();
	    
	    #[ExBOY删除]复审日志任务计划
        kernel::single('ome_retrial')->clean();
    }

    function month(){

    }

}