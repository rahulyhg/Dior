<?php
class omeauto_mdl_autoconfirm extends dbeav_model{
    function suf_delete($oid){
        $orderTypeObj = app::get('omeauto')->model('order_type');
        $orderTypeObj->update(array('oid'=>0),array('oid'=>$oid));
        return true;
    }
}