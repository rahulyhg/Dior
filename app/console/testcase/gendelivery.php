<?php
class gendelivery extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testGendelivery(){
         $original_data = kernel::single('ome_event_data_delivery')->generate(1416);

        $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
       
        $result = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, true);
       
    }
}
