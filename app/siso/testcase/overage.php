<?php
class overage extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testOverage(){
        $amagedinLib = kernel::single('siso_receipt_iostock_defaultstore');
        array (
  'items' => 
  array (
    0 => 
    array (
      'bn' => 'test001',
      'normal_num' => 8,
    ),
  ),
  'inventory' => '2',
);
        if ($amagedinLib->create($params, $data, $msg)){
            echo $msg;
        }
    }
}
