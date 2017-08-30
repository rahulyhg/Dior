<?php
class wms_receipt_purchase{

    private static $status = array(

    );


    /**
     *
     * 采购通知单创建方法
     * @param array $data 采购通知单数据信息
     */
    public function create(&$data){

    }

    /**
     *
     * 采购通知单状态变更
     * @param array $po_bn 采购单编号
     */
    public function updateStatus($po_bn){

    }

    /**
     *
     * 检查采购通知单是否存在判断
     * @param array $po_bn 采购单编号
     */
    public function checkExist($po_bn){
        return true;
    }

    /**
     *
     * 检查采购通知单是否有效
     * @param array $po_bn 采购单编号
     */
    public function checkValid($po_bn){
        return true;
    }
}