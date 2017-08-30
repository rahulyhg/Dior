<?php
class omeauto_mdl_autodispatch extends dbeav_model{
    /**
     *
     * 分派规则回收站删除后删除订单分组记录上的分派规则id
     * @param int $did
     */
    function suf_delete($did){
        $orderTypeObj = app::get('omeauto')->model('order_type');
        $orderTypeObj->update(array('did'=>0),array('did'=>$did));
        return true;
    }
}