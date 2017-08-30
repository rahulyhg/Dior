<?php
/**
 * shopex_b2b订单数据处理
 * @author ome 
 * @copyright 2011.1.20
 *
 */
class ome_service_shopex_b2b_order{
    
    /**
     * 新建订单
     * @param int $order_sdf 订单sdf结构
     * @param $flag 是否走B2B私有订单处理方法，true:走B2B私有方法  false:不走B2B私有方法
     */
    function add($order_sdf,&$flag){

        $limit = ome_order_func::get_max_orderitems();
        $order_bn = $order_sdf['order_bn'];
        $return_value = array('tid'=>$order_bn);
        $shop_type = $node_type = $order_sdf['shop_detail']['node_type'];
        $shop_id = $order_sdf['shop_detail']['shop_id'];
        $shop_name = $order_sdf['shop_detail']['name'];
        $order_kvname = 'order-'.$order_bn.'-'.$shop_id;
        //买家留言
        $custom_memo = $order_sdf['custom_mark'];
        //订单备注 
        $mark_memo = $order_sdf['mark_text'];
        
        $order_items_num = 0;
        if (!isset($order_sdf['from'])){
            $order_objects = json_decode($order_sdf['order_objects'],true);
            if ($order_objects){
                foreach ($order_objects as $k=>$v){
                    $order_items_num += count($v['order_items']);
                }
            }
            if ($order_items_num > $limit ){
                $flag = 'true';
            }
            if ($flag == 'false'){
                return;
            }
           
            $orderObj = &app::get('ome')->model('orders');
            $responseObj = $order_sdf['responseObj'];
            //订单详情
            $order_detail = $orderObj->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,order_bn,mark_text');
            $order_id = $order_detail['order_id'];
            $order_data = '';
            base_kvstore::instance('ome_order')->fetch($order_kvname, $order_data);
            if ($order_data['order_bn'] == $order_bn || $order_id){
                return;
            }
            
            //--------------------------业务逻辑过滤处理-----------------------------
            //订单状态业务规则过滤
            $order_filter = array(
                'order_bn' => $order_bn,
                'shop_id' => $shop_id,
                'shop_type' => $shop_type,
                'shop_name' => $shop_name,
                'status' => $order_sdf['status'],
                'pay_status' => $order_sdf['pay_status'],
                'ship_status' => $order_sdf['ship_status'],
            );
            $order_filter_result = ome_order_rpc_response_order::order_filter($order_filter);
            if ($order_filter_result['status'] == false){
                if ($order_filter_result['rsp'] == 'succ'){
                    return;
                }else{
                    $msg = $order_filter_result['res'];
                    if ($order_sdf['order_from'] == 'omeapiplugin'){
                        return;
                    }else{
                        $responseObj->send_user_error(app::get('base')->_($msg), $return_value);
                    }
                }
            }
            
            //本地标准SDF格式数据转换
            $order_sdf = kernel::single('ome_order_rpc_response_order')->order_sdf_convert($order_sdf);
        }

        //存储KVSTORE
        ome_kvstore::instance('ome_order')->store($order_kvname, $order_sdf);
        
        $params = array(
            'order_bn' => $order_bn,
            'shop_id' => $shop_id,
            'shop_type' => $shop_type,
        );
        
        //存储队列
        $oQueue = &app::get('base')->model('queue');
        $queueData = array(
            'queue_title'=>$shop_name.'新建订单('.$order_bn.')',
            'start_time'=>time(),
            'params'=>array(
                'params'=>$params,
                'app' => 'ome',
                'mdl' => 'order'
            ),
            'worker'=>'ome_order_rpc_response_queue_order.add',
        );
        $oQueue->save($queueData);
        $oQueue->flush();
        return;
    }
    
    function is_edit_view($order_sdf,&$flag){
        //ENUM('local','fxjl','b2c','taofenxiao')
        $arr = array('fxjl','b2c','taofenxiao');
        if(in_array($order_sdf['order_source'], $arr)){
            $flag = 'false';
        }
    }


    /**
     * 删除商品明细中的货号是空的商品
     *
     * @return boolean
     * @author yangminsheng
     **/
    function order_objects($order_id){
        if (empty($order_id)) return array();

        $sql = 'select oi.item_id,o.obj_id,oi.bn,oi.product_id,oi.nums,oi.delete from `sdb_ome_order_objects` o left join `sdb_ome_order_items` oi on o.obj_id = oi.obj_id where o.order_id = '.$order_id.' and o.bn=""';
        $obj_count = kernel::database()->select($sql);

        if(count($obj_count)>0){

            $oProducts = &app::get('ome')->model('products');
            $oOrder_obj = &app::get('ome')->model('order_objects');
            $oOrder_items = &app::get('ome')->model('order_items');

            foreach($obj_count as $key => $val){
                if($val['delete']=="false"){
                    //修改预占库存
                    $oProducts->chg_product_store_freeze($val['product_id'],intval($item['sendnum']),"-");
                }

                //删除bn是空的obj和obj_item里的信息
                $oOrder_obj->delete(array('obj_id'=>$val['obj_id']));
                $oOrder_items->delete(array('item_id'=>$val['item_id']));
            }

        }
        return true;
    }

    /**
     * 订单未发货时,收货人信息或订单编辑时 对这个订单的发货单进行校回
     * is_reback    是否回打发货单
     * orders       订单基本信息
     * order_source 店铺来源 taofenxiao
     * @return boolean
     * @author yangminsheng
     **/
    function rebackdelivery($orders,&$is_reback,$order_source)
    {

        $is_reback = false;
        if(in_array($orders['process_status'], array('splitting','splited')) && $orders['ship_status'] == '0' && $order_source == 'taofenxiao'){
            
            define('FRST_TRIGGER_OBJECT_TYPE','发货单：发货单撤销');
            define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_delivery：doReback');
            $memo = '由于订单明细修改或收货人信息被修改,发货单被打回';
            $is_reback = true;
            
            $Objdly  = app::get('ome')->model('delivery');
            $opObj = app::get('ome')->model('operation_log');
            $doObj = app::get('ome')->model('delivery_order');

            $detail = $Objdly->getDeliveryByOrderBn($orders['order_bn']);

            if(!$detail){
                $is_reback = false;
                return false;
            }

            if($detail['is_bind'] == 'true'){
                $ids = $Objdly->getItemsByParentId($detail['delivery_id'], 'array');
                if($ids){
                    $result = $Objdly->splitDelivery($detail['delivery_id'], $ids);
                    if(!$result){
                        $is_reback = false;
                        return false;
                    }
                    define('FRST_TRIGGER_OBJECT_TYPE','发货单：父发货单撤销');
                    define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_delivery：doReback');

                }
            }else{
                define('FRST_TRIGGER_OBJECT_TYPE','发货单：发货单撤销');
                define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_delivery：doReback');
                $ids[] = $detail['delivery_id'];
            }

            $serialFilter['delivery_id'][] = $detail['delivery_id'];
            $this->rebackSerial($serialFilter);
            $Objdly->rebackDelivery($ids, $memo);

            foreach($ids as $id){
                $opObj->write_log('delivery_back@ome', $id, '发货单打回');
                $order_ids= $doObj->dump(array('delivery_id'=>$id),'order_id');
                $delivery_bn = $Objdly->dump(array('delivery_id'=>$id),'delivery_bn');
                $opObj->write_log('order_back@ome', $order_ids['order_id'], '发货单'.$delivery_bn['delivery_bn'].'打回+'.'备注:'.$memo); 
                $Objdly->updateOrderPrintFinish($id, 1);
            }
            
        }

        return array('confirm'=>'N','process_status'=>'unconfirmed');
    }

    private function rebackSerial($filter){
        $serialObj = app::get('ome')->model('product_serial');
        $serialLogObj = app::get('ome')->model('product_serial_log');
        if($filter['delivery_id'] && count($filter['delivery_id'])>0){
            $logFilter['act_type'] = 0;
            $logFilter['bill_type'] = 0;
            $logFilter['bill_no'] = $filter['delivery_id'];
            $serialLogs = $serialLogObj->getList('item_id',$logFilter);
            foreach($serialLogs as $key=>$val){
                $itemIds[] = $val['item_id'];
            }
            if(count($itemIds)>0 && $serialObj->update(array('status'=>0),array('item_id'=>$itemIds,'status'=>1))){
                return true;
            }
        }
        return false;
    }

}