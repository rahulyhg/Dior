<?php
class taskmgr_misc_task{

    function week(){

    }

    function minute(){
        $time = time();
        $minute = date('i',$time);

        base_kvstore::instance('setting_taskmgr')->fetch('crontab_get_shoporderlist',$last_crontab_get_shoporderlist);
        if($last_crontab_get_shoporderlist){
            if($time >= ($last_crontab_get_shoporderlist + 300)){
                base_kvstore::instance('setting_taskmgr')->store('crontab_get_shoporderlist',$time);
                /*检查是否漏单begin*/
                kernel::single('ome_rpc_request_miscorder')->getlist_order();

                $ome_syncorder = kernel::single("ome_syncorder");
                $omequeueModel = kernel::single("ome_syncshoporder");
                $apilog = &app::get('ome')->model('api_order_log');

                $orderinfo = $omequeueModel->fetchAll($apilog);

                if(!empty($orderinfo)){
                    $i=0;
                    while(true){
                        if(!$orderinfo[$i]['order_bn']) return false;
                        $params['order_bn'] = $orderinfo[$i]['order_bn'];
                        $params['shop_id'] = $orderinfo[$i]['shop_id'];
                        $params['log_id'] = $orderinfo[$i]['log_id'];
                        $res = $ome_syncorder->get_order_list_detial($params);
                        $i++;
                    }
                }
                /*检查是否漏单end*/
            }
        }else{
            base_kvstore::instance('setting_taskmgr')->store('crontab_get_shoporderlist',$time);
        }
    }

    function hour(){
        $time = time();
        $hour = date('H',$time);

        base_kvstore::instance('setting_taskmgr')->fetch('crontab_update_report',$last_crontab_update_report);
        if($last_crontab_update_report){
            if($time >= ($last_crontab_update_report + 43200) && $hour == '01'){
                base_kvstore::instance('setting_taskmgr')->store('crontab_update_report',$time);
                /*生成报表数据begin*/
                //ordersPrice  客单价分布情况
                kernel::single('omeanalysts_crontab_script_ordersPrice')->orderPrice();

                //ordersTime  下单时间分布情况
                kernel::single('omeanalysts_crontab_script_ordersTime')->orderTime();

                //rmatype  售后类型分布统计
                kernel::single('omeanalysts_crontab_script_rmatype')->statistics();

                //sale
                kernel::single('omeanalysts_crontab_script_sale')->statistics();

                //catSaleStatis 商品类目销售对比统计
                kernel::single('omeanalysts_crontab_script_catSaleStatis')->statistics();

                //productSaleRank  产品销售排行榜
                kernel::single('omeanalysts_crontab_script_productSaleRank')->statistics();

                //storeStatus  库存状况综合分析
                kernel::single('omeanalysts_crontab_script_storeStatus')->statistics();
                kernel::single('omeanalysts_crontab_script_bpStockDetail')->statistics();
                /*生成报表数据end*/
            }
        }else{
            base_kvstore::instance('setting_taskmgr')->store('crontab_update_report',$time);
        }

        base_kvstore::instance('setting_taskmgr')->fetch('crontab_record_stockcost',$last_crontab_record_stockcost);
        if($last_crontab_record_stockcost){
            if($time >= ($last_crontab_record_stockcost + 43200) && $hour == '02'){
                base_kvstore::instance('setting_taskmgr')->store('crontab_record_stockcost',$time);
                /*成本计算begin*/
                if( app::get('tgstockcost')->is_installed()){
                    kernel::single('tgstockcost_crontab_stockcost')->set();
                }
                /*成本计算end*/
            }
        }else{
            base_kvstore::instance('setting_taskmgr')->store('crontab_record_stockcost',$time);
        }

        base_kvstore::instance('setting_taskmgr')->fetch('crontab_update_logisticsaccounts',$last_crontab_update_logisticsaccounts);
        if($last_crontab_update_logisticsaccounts){
            if($time >= ($last_crontab_update_logisticsaccounts + 43200) && $hour == '03'){
                base_kvstore::instance('setting_taskmgr')->store('crontab_update_logisticsaccounts',$time);
                /*更新物流对帐记录begin*/
                kernel::single('logisticsaccounts_estimate')->crontab_delivery();
                /*更新物流对帐记录end*/
            }
        }else{
            base_kvstore::instance('setting_taskmgr')->store('crontab_update_logisticsaccounts',$time);
        }

        base_kvstore::instance('setting_taskmgr')->fetch('crontab_update_truncate',$last_crontab_update_truncate);
        if($last_crontab_update_truncate){
            if($time >= ($last_crontab_update_truncate + 43200) && $hour == '04'){
                base_kvstore::instance('setting_taskmgr')->store('crontab_update_truncate',$time);
                /*清除垃圾数据begin*/
                $db = kernel::database();

                //清空生成唯一bn表
                $endTime = strtotime(date("Y-m-d"));
                $sql = "DELETE FROM `sdb_ome_concurrent` WHERE `current_time`<'".$endTime."'";
                $db->exec($sql);
                $sql = 'OPTIMIZE TABLE `sdb_ome_concurrent`';
                $db->exec($sql);

                //重围冻结库存
                define('FRST_OPER_NAME','system');
                define('FRST_TRIGGER_OBJECT_TYPE','crontab定时重置所有商品冻结库存');
                define('FRST_TRIGGER_ACTION_TYPE','updateTruncate.php');
                $productObj = kernel::single('ome_sync_product');
                $productObj->reset_freeze();
                /*清除垃圾数据end*/
            }
        }else{
            base_kvstore::instance('setting_taskmgr')->store('crontab_update_truncate',$time);
        }
    }

    function day(){
        
    }

    function month(){

    }

}