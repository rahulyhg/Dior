<?php
class wms_delivery_bill{

    /**
     *
     * 根据发货单ID获取主物流单号
     * @param int $delivery_id
     */
    function getPrimaryLogiNoById($delivery_id){
        $deliveryBillObj = app::get('wms')->model('delivery_bill');
        $deliveryBillInfo = $deliveryBillObj->dump(array('delivery_id'=>$delivery_id,'type'=>1),'logi_no');
        return isset($deliveryBillInfo['logi_no']) ? $deliveryBillInfo['logi_no'] : null;
    }

    /**
     *
     * 根据主物流单号获取发货单ID
     * @param string $logi_no
     */
    function getDeliveryIdByPrimaryLogi($logi_no){
        $deliveryBillObj = app::get('wms')->model('delivery_bill');
        $deliveryBillInfo = $deliveryBillObj->dump(array('logi_no'=>$logi_no,'type'=>1),'delivery_id');
        return isset($deliveryBillInfo['delivery_id']) ? $deliveryBillInfo['delivery_id'] : null;
    }

    function getSecondaryLogiNoById(){

    }

    /**
     *
     * 根据次物流单号获取发货单ID
     * @param string $logi_no
     */
    function getDeliveryIdBySecondaryLogi($logi_no){
        $deliveryBillObj = app::get('wms')->model('delivery_bill');
        $deliveryBillInfo = $deliveryBillObj->dump(array('logi_no'=>$logi_no,'type'=>2),'delivery_id');
        return isset($deliveryBillInfo['delivery_id']) ? $deliveryBillInfo['delivery_id'] : null;
    }

    
    /**
     * 根据单号和发货单ID返回信息
     * @param   $delivery_id 发货单ID
     * @param $logi_no 物流单号
     * @return array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getDeliveryByBill($delivery_id,$logi_no)
    {
        $deliveryBillObj = app::get('wms')->model('delivery_bill');
        $deliveryBillInfo = $deliveryBillObj->dump(array('delivery_id'=>$delivery_id,'logi_no'=>$logi_no),'*');
        return $deliveryBillInfo;
    }
}