<?php
/**
* 通知
*
* @category apibusiness
* @package apibusiness/lib/
* @author chenping<chenping@shopex.cn>
* @version $Id: notice.php 2013-3-12 14:37Z
*/
class apibusiness_notice
{
    const _APP_NAME = 'ome';
    /**
     * 通知作业单
     *
     * @param Array $tgOrder 原订单
     * @param Array $newOrder 更新后的订单
     * @return void
     * @author 
     **/
    public function notice_process_order($tgOrder,$newOrder)
    {
        // 原来是退款中或退款申请中的订单,更新后变已支付||部分支付||部分退款||全额退款
        if (in_array($tgOrder['pay_status'], array('6','7')) && $newOrder['pay_status']) {
            // 全额退款取消作业单
            if ($newOrder['pay_status'] == '5') {
                define('FRST_TRIGGER_OBJECT_TYPE','订单：未发货订单全额退款导致订单取消');
                define('FRST_TRIGGER_ACTION_TYPE','ome_order_func：update_order_pay_status');
                
                $refund_applyObj = app::get(self::_APP_NAME)->model('refund_apply');
                $refund_applyObj->check_iscancel($tgOrder['order_id']);

                $logModel = app::get(self::_APP_NAME)->model('operation_log');
                $logModel->write_log('order_edit@ome',$tgOrder['order_id'],'全额退款');

                return ;
            }
        }

        $is_reback = $this->notice_process_delivery($tgOrder,$newOrder);
        if ($is_reback) {
            $orderModel = app::get(self::_APP_NAME)->model('orders');

            $updateOrder = array('confirm'=>'N','process_status'=>'unconfirmed');
            $orderModel->update($updateOrder,array('order_id'=>$tgOrder['order_id']));
        }
    }

    /**
     * 通知发货单
     *
     * @param Array $tgOrder 原订单
     * @param Array $newOrder 更新后的订单
     * @return void
     * @author 
     **/
    public function notice_process_delivery($tgOrder,$newOrder)
    {   
        // 收货人是否发生变更
        $consignee_change = false;
        $consignee_column = array('ship_name','ship_area','ship_addr','ship_zip','ship_tel','ship_email','ship_time','ship_mobile');
        foreach ($consignee_column as $key => $column) {
            if ($newOrder[$column]) {
                $consignee_change = true; break;
            }
        }

        $is_reback = false;
        if (in_array($tgOrder['pay_status'], array('6','7')) && $newOrder['pay_status']) {
            if ($newOrder['pay_status'] == '4') {
                // 发货单叫回
                $is_reback = $this->rebackdelivery($tgOrder);
            }
        } elseif ($consignee_change == true || $newOrder['consignee']) {
            // 收货地址发生变更
            $is_reback = $this->rebackdelivery($tgOrder);
        } elseif ($newOrder['order_objects']) {
            // 订单明细发生变更
            $is_reback = $this->rebackdelivery($tgOrder);
        }

        return $is_reback;
    }

    private function rebackdelivery($tgOrder)
    {

        $is_reback = false;
        if(in_array($tgOrder['process_status'], array('splitting','splited')) && $tgOrder['ship_status'] == '0'){
            
            define('FRST_TRIGGER_OBJECT_TYPE','发货单：发货单撤销');
            define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_delivery：doReback');
            $memo = '由于订单明细修改或收货人信息被修改,发货单被打回';

            $Objdly  = app::get(self::_APP_NAME)->model('delivery');
            $detail = $Objdly->getDeliveryByOrderBn($tgOrder['order_bn']);

            if(!$detail) return $is_reback;

            if($detail['is_bind'] == 'true'){
                $ids = $Objdly->getItemsByParentId($detail['delivery_id'], 'array');
                if($ids){
                    $result = $Objdly->splitDelivery($detail['delivery_id'], $ids);
                    
                    if(!$result) return $is_reback;

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

            $opObj = app::get(self::_APP_NAME)->model('operation_log');
            $doObj = app::get(self::_APP_NAME)->model('delivery_order');
            foreach($ids as $id){
                $opObj->write_log('delivery_back@ome', $id, '发货单打回');
                $order_ids= $doObj->dump(array('delivery_id'=>$id),'order_id');
                $delivery_bn = $Objdly->dump(array('delivery_id'=>$id),'delivery_bn');
                $opObj->write_log('order_back@ome', $order_ids['order_id'], '发货单'.$delivery_bn['delivery_bn'].'打回+'.'备注:'.$memo); 
                $Objdly->updateOrderPrintFinish($id, 1);
            }
            
            $is_reback = true;
        }

        return $is_reback;
    }

    private function rebackSerial($filter){
        $serialObj    = app::get(self::_APP_NAME)->model('product_serial');
        $serialLogObj = app::get(self::_APP_NAME)->model('product_serial_log');

        if($filter['delivery_id'] && count($filter['delivery_id'])>0){
            $logFilter['act_type']  = 0;
            $logFilter['bill_type'] = 0;
            $logFilter['bill_no']   = $filter['delivery_id'];
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