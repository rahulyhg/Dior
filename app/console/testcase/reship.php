<?php
class reship extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testReship(){
        $reship_data = kernel::single('ome_receipt_reship')->reship_create(array('reship_id'=>3));
        $wms_id = kernel::single('ome_branch')->getWmsIdById($reship_data['branch_id']);
        kernel::single('console_event_trigger_reship')->create($wms_id, $reship_data, false);
        
    }
}
