<?php
class genconsign extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testGenconsign(){
        $dly_id = 27;
        $deliveryObj = app::get('wms')->model('delivery');
        $deliveryInfo = $deliveryObj->dump($dly_id,'outer_delivery_bn,branch_id,delivery_time,weight,delivery_cost_actual');
        $wms_id = kernel::single('ome_branch')->getWmsIdById($deliveryInfo['branch_id']);

        $data = array(
            'delivery_bn' => $deliveryInfo['outer_delivery_bn'],
            'delivery_time' => $deliveryInfo['delivery_time'],
            'weight' => $deliveryInfo['weight'],
            'delivery_cost_actual' => $deliveryInfo['delivery_cost_actual'],
        );
        $res = kernel::single('wms_event_trigger_delivery')->consign($wms_id, $data, true);
        //echo $msg;
    }
}
