<?php
/*
 * 采购退款单
 */

class purchase_refunds{

    function save_refunds($data, &$msg){
        $refundObj = &app::get('purchase')->model('purchase_refunds');

         return  $refundObj->createRefund($data);

    }
}
