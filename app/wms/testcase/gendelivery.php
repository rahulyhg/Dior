<?php
class gendelivery extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testGendelivery(){

        $data = kernel::single('ome_event_data_delivery')->generate(7);

        kernel::single('wms_event_receive_delivery')->create($data);
        //echo $msg;
    }
}
