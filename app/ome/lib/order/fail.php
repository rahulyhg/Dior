<?php
class ome_order_fail{
    public function batchModifyOrder(&$cursor_id,$params){
        //danny_freeze_stock_log
        define('FRST_TRIGGER_OBJECT_TYPE','订单：失败订单恢复批量修改');
        define('FRST_TRIGGER_ACTION_TYPE','ome_order_fail：batchModifyOrder');
        $oldPbn = $params['sdfdata']['oldPbn'];
        $pbn = $params['sdfdata']['pbn'];
        $opinfo = $params['opinfo'];
        foreach($params['sdfdata']['orderId'] as $val){
            $this->addFailOrderLog($val,$opinfo);//失败订单操作日志记录添加
            $this->modifyOrderItemsByBn($val,$oldPbn,$pbn);
        }
        return false;
    }

    public function modifyOrder($order_id){
        $orderObj = &app::get('ome')->model('orders');

        $order = $orderObj->dump($order_id,'*',array('order_objects'=>array('*',array('order_items'=>array('*')))));
        if ($order['is_fail'] == 'true'){
            foreach($order['order_objects'] as $obj=>$items){
                foreach($items['order_items'] as $key=>$item){
                    if($item['product_id']<=0 || !isset($item['product_id'])){
                        $data = array('edit_status'=>'true');
                        $orderObj->update($data,array('order_id' =>$order_id));
                        return false;
                    }
                }
            }

            //修正订单
            $orderData['is_fail'] = 'false';
            $orderData['archive'] = 0;
            $orderObj->update($orderData,array('order_id' =>$order_id));

          //创建订单后执行的操作
            if($oServiceOrder = kernel::servicelist('ome_fail_modify_order_after')){
               foreach(kernel::servicelist('ome_fail_modify_order_after') as $object){
                  if(method_exists($object,'modify_order_after')){
                      $object->modify_order_after($order);
                   }
               }
            }
        }
        return true;
    }

    public function modifyOrderItems($order_id,$oldPbn,$pbn){

        $this->addFailOrderLog($order_id);//失败订单操作日志记录添加

        $productObj = &app::get('ome')->model('products');
        $orderObj = &app::get('ome')->model('orders');
        $itemObj = &app::get('ome')->model('order_items');
        $Oorder_objects = &app::get('ome')->model('order_objects');

        #[拆单]修复_淘宝平台_订单进入ERP的原始数据 ExBOY
        $modify_order_oid    = array();
        
        $data = array('edit_status'=>'false');
        $orderObj->update($data,array('order_id' =>$order_id));

        //对货品进行过滤更新
        if($pbn){
            foreach($pbn as $item_id=>$bn){
                if($bn){
                    $is_normal_goods = true;
                    $product = $productObj->dump(array('bn'=>$bn),'product_id,bn,name,goods_id');
                    if(!$product){
                        $is_normal_goods = false;
                        foreach(kernel::servicelist('ome.product') as $name=>$object){
                            if(method_exists($object, 'getProductByBn')){
                                $product = $object->getProductByBn($bn);
                                if($product){
                                    break;
                                }
                            }
                        }
                    }

                    if($product){
                        $product_spec = $productObj->dump($product['product_id'], 'spec_desc');
                        if($product_spec){
                            $spec_desc = $product_spec['spec_desc']['spec_value_id'];
                            if(count($spec_desc) > 0){
                                foreach ($spec_desc as $spec){
                                    if($spec){
                                        $spec_value_ids .= $spec.",";
                                    }
                                 }
                            }

                            $spec_value_ids = substr($spec_value_ids, 0, strlen($spec_value_ids)-1);

                            $product_attr = array();
                            if($spec_value_ids){
                                $sql = "SELECT sopv.spec_value , sos.spec_name FROM sdb_ome_spec_values AS sopv
                                        LEFT JOIN sdb_ome_specification AS sos ON sopv.spec_id = sos.spec_id
                                        WHERE sopv.spec_value_id IN(".$spec_value_ids.")";
                                $spec_attr = kernel::database()->select($sql);
                                if($spec_attr){
                                    foreach ($spec_attr as $spec_v){
                                        $product_attr['product_attr'][] = array(
                                            'label' => $spec_v['spec_name'],
                                            'value' => $spec_v['spec_value'],
                                        );
                                    }
                                }
                            }
                            $product['addon'] = serialize($product_attr);

                            unset($spec_value_ids);
                            unset($product_attr);
                        }

                        $item = array(
                            'product_id'=>$product['product_id'],
                            'bn'=>$product['bn'],
                            //'old_bn'=>$oldPbn[$item_id],
                            'addon' => $product['addon'],
                        );

                        //danny_freeze_stock_log
                        $frst_info = $orderObj->dump(array('order_id'=>$order_id),'shop_id,shop_type,order_bn');
                        $GLOBALS['frst_shop_id'] = $frst_info['shop_id'];
                        $GLOBALS['frst_shop_type'] = $frst_info['shop_type'];
                        $GLOBALS['frst_order_bn'] = $frst_info['order_bn'];

                        //判断是否是普通商品，普通商品才记冻结，捆绑在后续逻辑处理节点才去记冻结 danny 2012-4-26
                        if($is_normal_goods){
                            $itemInfo = $itemObj->dump(array('order_id'=>$order_id,'item_id'=>$item_id));
                            $productObj->chg_product_store_freeze($item['product_id'],(intval($itemInfo['quantity'])-intval($itemInfo['sendnum'])),"+");
                        }
                        $itemObj->update($item,array('order_id'=>$order_id,'item_id'=>$item_id));
                        //修复order_objects上的goods_id和bn
                        $obj = array(
                            'goods_id'=>$product['goods_id'],
                            'bn'=>$product['bn'],
                        );
                        if($is_normal_goods){
                            $Oorder_objects->update($obj,array('obj_id'=>$itemInfo['obj_id']));
                        }
                        
                        #修复_淘宝平台_原始属性值 ExBOY
                        if($frst_info['shop_type'] == 'taobao')
                        {
                            $modify_order_oid[]    = array(
                                                            'order_id'=>$order_id,
                                                            'order_bn'=>$frst_info['order_bn'],
                                                            'obj_id'=>$itemInfo['obj_id'],
                                                            'old_bn'=>$oldPbn[$item_id],
                                                            'new_bn'=>$product['bn'],
                                                        );
                        }
                        
                        unset($obj);
                        unset($item);
                    }
                }
            }
        }

        #修复_淘宝平台_原始属性值 ExBOY
        if($modify_order_oid)
        {
            $this->modifyOrderOid($modify_order_oid);
        }
        
        //修正为正常订单
        if($this->modifyOrder($order_id)){
            return true;
        }else{
            return false;
        }
    }

    public function modifyOrderItemsByBn($order_id,$oldPbn,$pbn){
        $productObj = &app::get('ome')->model('products');
        $orderObj = &app::get('ome')->model('orders');
        $itemObj = &app::get('ome')->model('order_items');
        $Oorder_objects = &app::get('ome')->model('order_objects');

        #[拆单]修复_淘宝平台_订单进入ERP的原始数据 ExBOY
        $modify_order_oid    = array();
        
        $data = array('edit_status'=>'false');
        $orderObj->update($data,array('order_id' =>$order_id));

        //对货品进行过滤更新
        if($oldPbn && $pbn){
            foreach($pbn as $key=>$bn){
                if($bn){
                    $is_normal_goods = true;
                    $product = $productObj->dump(array('bn'=>$bn),'product_id,bn,name,goods_id');
                    if(!$product){
                        $is_normal_goods = false;
                        foreach(kernel::servicelist('ome.product') as $name=>$object){
                            if(method_exists($object, 'getProductByBn')){
                                $product = $object->getProductByBn($bn);
                                if($product){
                                    break;
                                }
                            }
                        }
                    }

                    if($product){
                        $product_spec = $productObj->dump($product['product_id'], 'spec_desc');
                        if($product_spec){
                            $spec_desc = $product_spec['spec_desc']['spec_value_id'];
                            if(count($spec_desc) > 0){
                                foreach ($spec_desc as $spec){
                                    if($spec){
                                        $spec_value_ids .= $spec.",";
                                    }
                                 }
                            }

                            $spec_value_ids = substr($spec_value_ids, 0, strlen($spec_value_ids)-1);

                            $product_attr = array();
                            if($spec_value_ids){
                                $sql = "SELECT sopv.spec_value , sos.spec_name FROM sdb_ome_spec_values AS sopv
                                        LEFT JOIN sdb_ome_specification AS sos ON sopv.spec_id = sos.spec_id
                                        WHERE sopv.spec_value_id IN(".$spec_value_ids.")";
                                $spec_attr = kernel::database()->select($sql);
                                if($spec_attr){
                                    foreach ($spec_attr as $spec_v){
                                        $product_attr['product_attr'][] = array(
                                            'label' => $spec_v['spec_name'],
                                            'value' => $spec_v['spec_value'],
                                        );
                                    }
                                }
                            }
                            $product['addon'] = serialize($product_attr);

                            unset($spec_value_ids);
                            unset($product_attr);
                        }

                        $item = array(
                            'product_id'=>$product['product_id'],
                            'bn'=>$product['bn'],
                            'addon' => $product['addon'],
                        );
                        //danny_freeze_stock_log
                        $frst_info = $orderObj->dump(array('order_id'=>$order_id),'shop_id,shop_type,order_bn');
                        $GLOBALS['frst_shop_id'] = $frst_info['shop_id'];
                        $GLOBALS['frst_shop_type'] = $frst_info['shop_type'];
                        $GLOBALS['frst_order_bn'] = $frst_info['order_bn'];

                        //判断是否是普通商品，普通商品才记冻结，捆绑在后续逻辑处理节点才去记冻结 danny 2012-4-26
                        if($is_normal_goods){
                            $itemInfo = $itemObj->dump(array('order_id'=>$order_id,'bn'=>$oldPbn[$key]));
                            $productObj->chg_product_store_freeze($item['product_id'],(intval($itemInfo['quantity'])-intval($itemInfo['sendnum'])),"+");
                        }
                        $itemObj->update($item,array('order_id'=>$order_id,'bn'=>$oldPbn[$key]));

                        //修复order_objects上的goods_id和bn
                        $obj = array(
                            'goods_id'=>$product['goods_id'],
                            'bn'=>$product['bn'],
                        );
                        if($is_normal_goods){
                            $Oorder_objects->update($obj,array('obj_id'=>$itemInfo['obj_id']));
                        }
                        
                        #修复_淘宝平台_原始属性值 ExBOY
                        if($frst_info['shop_type'] == 'taobao')
                        {
                            $modify_order_oid[]    = array(
                                                            'order_id'=>$order_id,
                                                            'order_bn'=>$frst_info['order_bn'],
                                                            'obj_id'=>$itemInfo['obj_id'],
                                                            'old_bn'=>$oldPbn[$key],
                                                            'new_bn'=>$product['bn'],
                                                        );
                        }
                        
                        unset($obj);
                        unset($item);

                    }
                }
            }
        }

        #修复_淘宝平台_原始属性值 ExBOY
        if($modify_order_oid)
        {
            $this->modifyOrderOid($modify_order_oid);
        }
        
        //修正为正常订单
        if($this->modifyOrder($order_id)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 失败订单操作日志记录添加
     *
     * @return void
     * @author 
     **/
    function addFailOrderLog($order_id,$opinfo=NULL)
    {
        $oLog = &app::get('ome')->model('operation_log');

        $log_id = $oLog->write_log('order_edit@ome',$order_id,"失败订单恢复",'',$opinfo);

        $orderObj = &app::get('ome')->model('orders');
        $opObj = &app::get('ome')->model('order_pmt');
        $membersObj = &app::get('ome')->model('members');
        $paymentsObj = &app::get('ome')->model('payments');
        $orders = $orderObj->dump(array('order_id'=>$order_id),"*",array("order_objects"=>array("*",array("order_items"=>array('*')))));

        //优惠方案
        $orders['pmt'] = $opObj->getList('*',array('order_id'=>$order_id));//订单优惠方案
        //会员信息
        $orders['mem_info'] = $membersObj->getRow($orders['member_id']);
        //支付单
        $orders['payments'] = $paymentsObj->getList('*',array('order_id'=>$order_id));

        $orderObj->write_log_detail($log_id,$orders);
    }

    /**
     * 修复[淘宝平台]原始属性值
     * PS:拆单开启后,订单部分回写会使用
     *
     * @param  Array    $modify_order_oid
     * @return void
     * @author ExBOY
     **/
    function modifyOrderOid($modify_order_oid)
    {
        if(empty($modify_order_oid))
        {
            return false;
        }
        
        $orderDlyObj    = &app::get('ome')->model('order_delivery');
        foreach ($modify_order_oid as $item_id => $item)
        {
            $getData    = $bn_data = array();
            
            #获取淘宝平台的原始数据
            $getData    = $orderDlyObj->dump(array('order_bn'=>$item['order_bn']), 'id, bn');
            if(empty($getData['bn']))
            {
                continue;
            }
            
            $bn_data   = unserialize($getData['bn']);
            foreach ($bn_data as $key => $val)
            {
                if($val == $item['old_bn'])
                {
                    $bn_data[$key]    = $item['new_bn'];
                }
            }
            $getData['bn']    = serialize($bn_data);
            
            $orderDlyObj->save($getData);
            
            unset($getData, $bn_data);
        }
        
        return true;
    }
}
