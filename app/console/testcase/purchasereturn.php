<?php
class purchasereturn extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testPurchasereturn(){
        
        $rs = kernel::single('console_event_trigger_purchasereturn')->create(array('rp_id'=>24),false);
        var_dump($rs);
            
            
    }

}
