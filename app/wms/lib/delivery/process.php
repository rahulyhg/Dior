<?php
class wms_delivery_process{

    /**
     *
     * 发货单校验完成处理方法
     * @param int $delivery_id 发货单ID
     * @param boolean $auto 是否整单校验
     */
    function verifyDelivery($delivery_id,$auto=false){
        $dly_id = $delivery_id;
        $dlyObj = &app::get('wms')->model('delivery');
        $dly_itemObj  = &app::get('wms')->model('delivery_items');
        $opObj        = &app::get('ome')->model('operation_log');
        //对发货单详情进行校验完成处理
        if ($this->verifyItemsByDeliveryId($dly_id)){
            $res = $dlyObj->db->exec("update sdb_wms_delivery set process_status = (process_status | 2) where delivery_id =".$dly_id);
            if (!$res){
                return false;
            }

            //增加捡货绩效
            foreach(kernel::servicelist('tgkpi.pick') as $o){
                if(method_exists($o,'finish_pick')){
                    $o->finish_pick($dly_id);
                }
            }

            if($auto){
                $msg = '发货单校验完成(免校验)';
            }else{
                $msg = '发货单校验完成';
            }

            if (kernel::single('desktop_user')->get_id()){
                $opObj->write_log('delivery_check@wms', $dly_id, $msg);
            }

            //同步校验状态到oms
            $deliveryInfo = $dlyObj->dump($dly_id,'outer_delivery_bn,branch_id');
            $wms_id = kernel::single('ome_branch')->getWmsIdById($deliveryInfo['branch_id']);
            $data = array(
                'delivery_bn' => $deliveryInfo['outer_delivery_bn'],
            );
            $res = kernel::single('wms_event_trigger_delivery')->doCheck($wms_id, $data, true);

            return true;
        }else {

            if (kernel::single('desktop_user')->get_id()){
                $opObj->write_log('delivery_check@wms', $dly_id, '发货单校验未完成');
            }
            return false;
        }
    }

    /**
     *
     * 校验完成，对发货单对应详情进行更新保存方法
     * @param bigint $dly_id
     * @return boolean
     */
    function verifyItemsByDeliveryId($dly_id){
        $dly_itemObj  = &app::get('wms')->model('delivery_items');
        $items = $dly_itemObj->getList('item_id,number,verify,verify_num', array('delivery_id'=>$dly_id), 0, -1);
        foreach ($items as $item){
            $data['verify'] = 'true';
            $data['verify_num'] = $item['number'];

            if ($dly_itemObj->update($data, array('item_id'=>$item['item_id'])) == false) return false;
            $data = null;
        }
        return true;
    }

    /**
     *
     * 校验内容的临时保存方法
     * @param int $dly_id 发货单ID
     */
    function verifyItemsByDeliveryIdFromPost($dly_id){
        $dly_itemObj  = &app::get('wms')->model('delivery_items');
        $items = $dly_itemObj->getList('item_id,number,product_id,verify,verify_num', array('delivery_id'=>$dly_id), 0, -1);
        $pObj = &app::get('ome')->model('products');
        foreach ($items as $item){
            $p = $pObj->dump($item['product_id'], 'barcode');
            $num = intval($_POST['number_'. $p['barcode']]);
            $num = $num>$item['number']? $item['number'] : $num;
            $data['verify'] = 'false';
            $data['verify_num'] = $num;

            if ($dly_itemObj->update($data, array('item_id'=>$item['item_id'])) == false) return false;
            $data = null;
            $_POST['number_'. $p['barcode']] -= $num;
        }
        return true;
    }

    /**
     *
     * 执行具体的发货处理事务
     * @param int $dly_id
     */
    function consignDelivery($dly_id) {
        $deliveryObj = app::get('wms')->model('delivery');

        $delivery_time = time();
        $dlydata['delivery_id'] = $dly_id;
        $dlydata['status'] = 3;
        $dlydata['process_status'] = 7;
        $dlydata['delivery_time'] = $delivery_time;
        $deliveryObj->save($dlydata);

        //WMS发货单发货触发通知OMS发货
        $deliveryInfo = $deliveryObj->dump($dly_id,'outer_delivery_bn,branch_id,weight,delivery_cost_actual');
        $wms_id = kernel::single('ome_branch')->getWmsIdById($deliveryInfo['branch_id']);

        $data = array(
            'delivery_bn' => $deliveryInfo['outer_delivery_bn'],
            'delivery_time' => $delivery_time,
            'weight' => $deliveryInfo['weight'],
            'delivery_cost_actual' => $deliveryInfo['delivery_cost_actual'],
        );
        $res = kernel::single('wms_event_trigger_delivery')->consign($wms_id, $data, true);
        return true;
    }

    /**
     * 打回发货单操作
     *
     * @param array() $dly_ids
     * @param string $memo
     * @return boolean
     */
    function rebackDelivery($dly_ids, $memo){
        if (is_array($dly_ids)){
            $ids = $dly_ids;
        }else {
            $ids[] = $dly_ids;
        }
        $data['memo']    = $memo;
        $data['status']  = 1;
        $data['logi_no'] = null;

        $dlyObj            = &app::get('wms')->model('delivery');
        $dlyItemsObj       = &app::get('wms')->model('delivery_items');
        $dlyBillObj        = &app::get('wms')->model('delivery_bill');
        $branch_productObj = &app::get('ome')->model('branch_product');
        $dlyCorpObj        = app::get('ome')->model('dly_corp');

        foreach ($ids as $item)    {
            $deliveryInfo = $dlyObj->dump($item,'status, branch_id, outer_delivery_bn, logi_id');
            if ($deliveryInfo['status'] == 3){
                continue;
            }

            $data['delivery_id'] = $item;

            //撤销所有发货单包裹单
            $billdata = array(
                'status'=> 2,
                'logi_no' => null,
            );
            $billfilter = array('delivery_id'=>$item);
            
            //回收电子面单
            $dlyCorp = $dlyCorpObj->dump($deliveryInfo['logi_id'],'tmpl_type,channel_id');

            if ($dlyCorp['tmpl_type'] == 'electron') {
                $logiBillList = $dlyBillObj->getList('logi_no',$billfilter);
                foreach ((array) $logiBillList as $_logi_bill) {
                    if ($_logi_bill['logi_no']) {
                        kernel::single('logisticsmanager_service_waybill')->recycle_waybill($_logi_bill['logi_no'],$dlyCorp['channel_id'],$item);
                    }
                }
            }

            $dlyBillObj->update($billdata,$billfilter);


            //将发货单状态更新为打回并记录备注
            if ($dlyObj->save($data)){
                $wms_id = kernel::single('ome_branch')->getWmsIdById($deliveryInfo['branch_id']);
                $data = array(
                    'delivery_bn' => $deliveryInfo['outer_delivery_bn'],
                    'memo' => $data['memo'],
                );
                $res = kernel::single('wms_event_trigger_delivery')->reback($wms_id, $data, true);
            }

        }
        return true;
    }
}
