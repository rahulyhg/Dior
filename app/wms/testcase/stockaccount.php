<?php
class stockaccount extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testStockaccount(){
        $data = array (
          'wms_id' => 2,
          
          'items' => 
          array (
            0 => 
            array (
              'branch_bn' => 'stockhouse',
              'logi_code' => 'test001',
              'product_bn' => 'test001',
              'normal_num' => '1',
              'defective_num' => '1',
              'batch'=>1
            ),
          ),
        );

        $wms_id = kernel::single('ome_branch')->getWmsIdById(1);
        $res = kernel::single('middleware_wms_response', $wms_id)->stock_result($data);;
        print_r($res);
    }
}
