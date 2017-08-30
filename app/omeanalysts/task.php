<?php
class omeanalysts_task{
    function post_install($options){
        $relateObj = &app::get('omeanalysts')->model("relate");
        $shopObj = &app::get('ome')->model("shop");
        $shopData = $shopObj->getlist('*');
        foreach($shopData as $shop){
            $aData = array();
            $aData['relate_table'] = 'ome_shop';
            $aData['relate_key'] = $shop['shop_id'];
            $relateObj->insert($aData);
        }
    }
}