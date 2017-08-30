<?php

/**
 * 发货单数据打接口.
 * @package     main
 * @subpackage  classes
 * @author cyyr24@sina.cn
 */
class ome_receipt_reship
{
    
    
    /**
     * 退货单数据
     * @param   array   退货单信息
     * @
     * @return array
     * @
     */
    function reship_create($data)
    {
        $reship_id = $data['reship_id'];
        $oReship = &app::get('ome')->model('reship');
        $oReship_item = &app::get('ome')->model('reship_items');
        $oReturn = &app::get('ome')->model('return_product');
        $oDelivery_order = &app::get('ome')->model('delivery_order');
        $oDelivery = &app::get('ome')->model('delivery');
        $oOrder = &app::get('ome')->model('orders');
        $reship = $oReship->dump($reship_id,'return_type,reship_bn,t_begin,memo,ship_name,ship_area,ship_addr,ship_zip,ship_tel,ship_mobile,ship_email,return_logi_no,return_logi_name,return_id,order_id,source,branch_id');
        if ($reship['source'] == 'archive') {
            $oOrder = &app::get('archive')->model('orders');
            $oDelivery = &app::get('archive')->model('delivery');
            $oDelivery_order = &app::get('archive')->model('delivery_order');
        }
        $reship_item = $oReship_item->getlist('bn,product_name as name,num,price,branch_id',array('reship_id'=>$reship_id,'return_type'=>array('return','refuse')),0,-1);
        $branch_id = $reship['branch_id'];
        $iostockdataObj = kernel::single('console_iostockdata');
        $branch = $iostockdataObj->getBranchByid($branch_id);
        $return_id = $reship['return_id'];
        $order_id = $reship['order_id'];
        $delivery_order = $oDelivery_order->dump(array('order_id'=>$order_id),'delivery_id');
        $delivery_id = $delivery_order['delivery_id'];
        $order = $oOrder->dump($order_id,'order_bn');
        $delivery = $oDelivery->dump($delivery_id,'delivery_bn');
        $ship_area = $reship['ship_area'];
        $ship_area = explode(':',$ship_area);
        $ship_area = explode('/',$ship_area[1]);
        $reship_data = array(
            'reship_bn'=>$reship['reship_bn'],
            'branch_id'=>$branch_id,
            'branch_bn'=>$branch['branch_bn'],
            'create_time'=>$reship['t_begin'],
            'memo'=>$reship['memo'],
            'original_delivery_bn'=>$delivery['delivery_bn'],
            'logi_no'=>$reship['return_logi_no'],
            'logi_name'=>$reship['return_logi_name'],
            'order_bn'=>$order['order_bn'],
            'receiver_name'=>$reship['ship_name'],
            'receiver_zip'=>$reship['ship_zip'],
            'receiver_state'=>$ship_area[0],
            'receiver_city'=>$ship_area[1],
            'receiver_district'=>$ship_area[2],
            'receiver_addr'=>$reship['ship_addr'],
            'receiver_phone'=>$reship['ship_tel'],
            'receiver_mobile'=>$reship['ship_mobile'],
            'receiver_email'=>$reship['ship_email'],
            'storage_code'=>$branch['storage_code'],
            'items'=>$reship_item,
            'return_type' => $reship['return_type'],
        );
        return $reship_data;
    } // end func
} // end class

?>