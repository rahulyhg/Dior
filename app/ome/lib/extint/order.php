<?php
class ome_extint_order{

    /**
     *
     * 根据发货通知单获取对应的订单备注信息
     * @param string $delivery_bn 发货通知单编号
     * @return array
     */
    function getMemoByDlyId($delivery_bn){
        $deliveryObj = app::get('ome')->model('delivery');
        $orderObj = app::get('ome')->model('orders');
        $markShowMethod = &app::get('ome')->getConf('ome.order.mark');

        $deliveryInfo = $deliveryObj->dump(array('delivery_bn'=>$delivery_bn), '*', array('delivery_order' => array('*')));
        foreach ($deliveryInfo['delivery_order'] as $v) {
            $order = $orderObj->dump($v['order_id'], 'order_bn,mark_text,custom_mark');
            if ($order['mark_text']) {
                $mark = unserialize($order['mark_text']);
                if (is_array($mark) || !empty($mark)){
                    if($markShowMethod == 'all'){
                        $mark_text[$order['order_bn']] = $mark;
                    }else{
                        $mark = array_pop($mark);

                        $mark_text[$order['order_bn']][] = $mark;
                    }
                }
            }

            if ($order['custom_mark']) {
                $custommark = unserialize($order['custom_mark']);
                if (is_array($custommark) || !empty($custommark)){
                    if($markShowMethod == 'all'){
                        $custom_mark[$order['order_bn']] = $custommark;
                    }else{
                        $mark = array_pop($custommark);
                        $custom_mark[$order['order_bn']][] = $mark;
                    }
                }
            }
        }
        $tmp = array('mark_text'=>$mark_text,'custom_mark'=>$custom_mark);
        return $tmp;
    }

    /**
     *
     * 检查订单是否存在异常，导致发货单操作不允许执行
     * @param string $delivery_bn 发货通知单编号
     */
    function existOrderPause($delivery_bn){
        $dlyObj = app::get('ome')->model('delivery');
        $dlyInfo = $dlyObj->dump(array('delivery_bn'=>$delivery_bn),'is_bind,delivery_id');

        if ($dlyInfo['is_bind'] == 'true'){
            $ids = $dlyObj->getItemsByParentId($dlyInfo['delivery_id']);
        }else {
            $ids = $dlyInfo['delivery_id'];
        }
        $sql = "SELECT COUNT(*) AS '_count'  FROM sdb_ome_delivery_order dord JOIN sdb_ome_orders o ON dord.order_id=o.order_id WHERE dord.delivery_id in ($ids) AND (o.process_status='cancel' OR o.abnormal='true' OR o.disabled='true' OR o.pause='true' OR pay_status='6' OR pay_status='7' OR pay_status='5') ";
        $row = $dlyObj->db->select($sql);
        if ($row[0]['_count'] > 0){
            return false;
        }else {
            return true;
        }
    }

    /**
     *
     * 根据发货单获取相关的订单号
     * @param string $delivery_bn 发货通知单编号
     */
    function getOrderBns($delivery_bn){
        $deliveryObj = app::get('ome')->model('delivery');
        $orderObj = app::get('ome')->model('orders');

        $deliveryInfo = $deliveryObj->dump(array('delivery_bn'=>$delivery_bn), '*', array('delivery_order' => array('*')));
        foreach ($deliveryInfo['delivery_order'] as $v) {
            $order = $orderObj->dump($v['order_id'], 'order_bn,order_id');
            $tmp[$order['order_id']] = $order['order_bn'];
        }
        return $tmp;
    }

}