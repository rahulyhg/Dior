<?php
class aftersale extends PHPUnit_Framework_TestCase
{
    function setUp() {
        
        
    }
    public function testAftersale(){
       $data = array (
          'return_id' => '29',
          'return_bn' => '231322222',
          'order_id' => '14',
          'title' => '201309031710001966ÊÛºó',
          'content' => NULL,
          'attachment' => NULL,
          'product_data' => NULL,
          'comment' => NULL,
          'add_time' => '1378362728',
          'disabled' => 'false',
          'shop_id' => '5f93c1bb9d3fecc84e1f757a84edc139',
          'member_id' => '1',
          'process_data' => NULL,
          'memo' => NULL,
          'money' => NULL,
          'op_id' => '16777215',
          'refundmoney' => '0.000',
          'delivery_id' => '11',
          'status' => '3',
          'last_modified' => '1378365934',
          'tmoney' => NULL,
          'bmoney' => NULL,
          'problem_id' => NULL,
          'recieved' => 'false',
          'verify' => 'false',
          'source' => 'matrix',
          'shop_type' => NULL,
          'refund_type' => NULL,
          'refund_phase' => NULL,
        );
        //$result = kernel::single('apibusiness_request_whitelist')->check_node('yihaodian','AGREE_RETURN_GOOD');
        var_dump($result);
        $data = array (
            'refuse_message' => '55555',
            'return_id' => '25',
        );
        $result = kernel::single('ome_service_aftersale')->refuse_return($data);
        var_dump($result);
    }
}

?>