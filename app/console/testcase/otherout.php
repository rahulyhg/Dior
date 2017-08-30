<?php
class otherout extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testOtherout(){
    $result = kernel::single('console_event_trigger_otherstockout')->create(array('iso_id'=>77),true);
           
            print_r($result);
    }
}
