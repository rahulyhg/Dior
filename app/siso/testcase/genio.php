<?php
class genio extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testGenio(){
        $soldIoLib = kernel::single('siso_receipt_iostock_sold');
        $soldSalesLib = kernel::single('siso_receipt_sales_sold');

        if($soldIoLib->create(array('delivery_id'=>61), $data, $msg)){
            if($soldSalesLib->create(array('delivery_id'=>61,'iostock'=>$data), $msg)){
                echo 'true';
            }else{
                echo 'false';
            }
        }
    }
}
