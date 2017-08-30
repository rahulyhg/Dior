<?php
class purchase extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testPurchase(){
        kernel::single('console_event_trigger_purchase')->create(array('po_id'=>40),true);
        
    }
}
