<?php
class ome_interface_delivery{

    public function iscancel($delivery_bn){
        $dlyObj = app::get('ome')->model('delivery');
        $dlyInfo = $dlyObj->dump(array('delivery_bn'=>$delivery_bn),'status');
        if(in_array($dlyInfo['status'],array('failed,cancel,back,stop'))){
            return true;
        }else{
            return false;
        }
    }

    public function getOmeDlyShipType($delivery_bn){
        $dlyObj = app::get('ome')->model('delivery');
        $dlyInfo = $dlyObj->dump(array('delivery_bn'=>$delivery_bn),'delivery');
        return isset($dlyInfo['delivery']) ? $dlyInfo['delivery'] : '';
    }
}