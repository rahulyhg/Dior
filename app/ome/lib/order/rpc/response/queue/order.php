<?php
class ome_order_rpc_response_queue_order{
    
    function add(&$cursor_id,$params){
      
        set_time_limit(300);
        if (!is_array($params)){
            $params = unserialize($params);
        }
        $limit = 50;
        $offset = $cursor_id;
        $params = $params['params'];
        $order_bn = $params['order_bn'];
        $shop_id = $params['shop_id'];
        $order_kvname = 'order-'.$order_bn.'-'.$shop_id;
        $order_objects_key_confname = 'order_objects_key_'.$order_bn.'-'.$shop_id;
        $total_saved_order_objects_kvname = 'total_saved_order_objects_'.$order_bn.'-'.$shop_id;
        $order_sdf = '';

        ome_kvstore::instance('ome_order')->fetch($order_kvname, $order_sdf);

        if ($order_sdf){
            //远程订单接收
            if (!isset($order_sdf['from'])){
                $order_object_kvname = 'order-objects-'.$order_bn.'-'.$shop_id;
                $kv_order_objects = '';
                ome_kvstore::instance('ome_order')->fetch($order_object_kvname, $kv_order_objects);
                if (!$kv_order_objects){
                    $order_objects = json_decode($order_sdf['order_objects'],true);
                    $responseObj = '';
                    $product_status = ome_order_rpc_response_order::order_objects_filter($order_bn, $shop_id, $order_objects, $responseObj);
                    ome_kvstore::instance('ome_order')->store($order_object_kvname, $order_objects);
                }else{
                    $order_objects = $kv_order_objects;
                }
                $order_sdf['order_objects'] = $order_objects;
            }else{
                $product_status = true;
            }            
            
            base_kvstore::instance('ome_order')->fetch('order_save_to-'.$order_bn.'-'.$shop_id, $order_type);
            if ($order_type == 'order_fail_list'){
                $app = 'omeapilog';
            }else{
                $app = 'ome';
            }
            $OrderObj = &app::get($app)->model('orders');
            $Order_objectObj = &app::get($app)->model('order_objects');
            $Order_itemObj = &app::get($app)->model('order_items');
            $order_detail = $OrderObj->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id');
            $order_id = $order_detail['order_id'];
            if ($order_id){
                //追加/更新订单明细
                $save_order_objects_num = 0;
                $tmp_count_order_objects = count($order_sdf['order_objects']);
                $tmp_order_objects_key = $cursor_id;
                if ($cursor_id >= $tmp_count_order_objects){
                    //结束队列
                    ome_kvstore::instance('ome_order')->delete($order_kvname);
                    ome_kvstore::instance('ome_order')->delete($order_object_kvname);
                    ome_kvstore::instance('ome_order')->delete('order_save_to-'.$order_bn.'-'.$shop_id);
                    $OrderObj->update(array('disabled'=>'false'), array('order_id'=>$order_id));
                    return false;
                }
                if ($cursor_id != 1){
                    $tmp_order_objects_key++;
                }
                for ($i=$tmp_order_objects_key;$i<=$tmp_count_order_objects;$i++){
                    if ($save_order_objects_num >= $limit){
                        break;
                    }
                    if ($order_sdf['order_objects'][$i]){
                        $tmp_order_items = $order_sdf['order_objects'][$i]['order_items'];
                        unset($order_sdf['order_objects'][$i]['order_items']);
                        $tmp_order_objects = $order_sdf['order_objects'][$i];
                        $objects = array(
                            'order_id' => $order_id
                        );
                        $objects = array_merge($objects, $tmp_order_objects);
                        $Order_objectObj->insert($objects);
                        if ($tmp_order_items){
                            foreach ($tmp_order_items as $itemkey=>$itemval){
                                $order_items = array(
                                    'order_id' => $order_id,
                                    'obj_id' => $objects['obj_id'],
                                );
                                $order_items = array_merge($order_items, $itemval);
                                $Order_itemObj->insert($order_items);
                            }
                        }
                    }
                    $save_order_objects_num++;
                    $tmp_order_objects_key = $i;
                }
                $cursor_id = $tmp_order_objects_key;
            }else{
                //新建订单
                $tmp_payed = $order_sdf['payed'];
                $tmp_pay_status = $order_sdf['pay_status'];
                $order_sdf['payed'] = '0';
                $order_sdf['pay_status'] = '0';
                
                if (!isset($order_sdf['from'])){
                    //更新前端店铺会员信息
                    if ($order_sdf['member_info']){
                        $member_id = kernel::single('ome_order_rpc_response_member')->update_order_member($order_sdf['member_info'],$shop_id);
                        if ($member_id){
                            $order_sdf['member_id'] = $member_id;
                        }
                    }else{
                        unset($order_sdf['member_id']);
                    }
                }
                $orderobjects = $order_sdf['order_objects'][0];
                unset($order_sdf['order_objects']);
                $order_sdf['order_objects'][0] = $orderobjects;
             
                //订单创建
                unset($order_sdf['shop_detail']);
                unset($order_sdf['responseObj']);
                $order_sdf['disabled'] = 'true';
                if ($product_status==false){
                    $order_sdf['is_fail'] = 'true';
                    $order_sdf['edit_status'] = 'true';
                }
                $OrderObj->create_order($order_sdf);
                $order_save_to = 'order_list';
                //优惠方案
                $pmt_detail = json_decode($order_sdf['pmt_detail'], true);
                if (!empty($pmt_detail)){
                    kernel::single('ome_order_rpc_response_order')->add_order_pmt($order_sdf['order_id'], $pmt_detail);            
                }
                //更新代销人信息
                $selling_agent = json_decode($order_sdf['selling_agent'], true);
                if (!empty($selling_agent)){
                    kernel::single('ome_order_rpc_response_order')->update_selling_agent_info($order_sdf['order_id'], $selling_agent);
                }
                
                if (!isset($order_sdf['from'])){
                    
                    //更新店铺下载订单时间
                    $shopObj = &app::get('ome')->model('shop');
                    $shopdata = array('last_download_time'=>time());
                    $shopObj->update($shopdata, array('shop_id'=>$shop_id));
                }
                base_kvstore::instance('ome_order')->store('order_save_to-'.$order_bn.'-'.$shop_id, $order_save_to);
                $cursor_id = 1;
            }

            return true;
        }else{
            return false;
        }
    }
}