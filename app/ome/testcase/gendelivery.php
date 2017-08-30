<?php
class gendelivery extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testGendelivery(){
        $original_data = kernel::single('ome_event_data_delivery')->generate(1395);
        $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
        kernel::single('ome_event_trigger_delivery')->create($wms_id,$original_data, true);

        //kernel::single('wms_event_receive_delivery')->create($data);
        //echo $msg;
    }
}
