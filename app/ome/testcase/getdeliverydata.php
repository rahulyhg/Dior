<?php
class getdeliverydata extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testGetdeliverydata(){
        $data =  kernel::single('ome_event_data_delivery')->generate(6);
        //error_log(var_export($data,true),3,__FILE__.".log");
    }
}
