<?php
class getmemo extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testGetmemo(){

        $res = kernel::single('ome_extint_order')->getMemoByDlyId(23);
        var_dump($res);exit;

        //kernel::single('wms_event_receive_delivery')->create($data);
        //echo $msg;
    }
}
