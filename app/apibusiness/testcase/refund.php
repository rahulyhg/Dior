<?php
class refund extends PHPUnit_Framework_TestCase
{
    function setUp() {
        
        
    }
    public function testRefund(){

        kernel::single('ome_service_refund')->update_status(1);
    }
}

?>