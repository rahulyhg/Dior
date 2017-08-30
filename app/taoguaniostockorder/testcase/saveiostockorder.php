<?php
class saveiostockorder extends PHPUnit_Framework_TestCase
{
    function setUp() {
    
    }
    
    public function testSaveiostockorder(){
     	//$data =array ( 'nums' => array ( 1 => '10', ), 'price' => array ( 1 => '20', ), 'iostockorder_name' => '20110519入库单', 'supplier' => 'test', 'supplier_id' => '1', 'branch' => '1', 'type_id' => '70', 'iso_price' => '100', 'bn' => array ( 1 => 'test', ), 'product_name' => array ( 1 => 'test', ), 'unit' => array ( 1 => '个', ), 'memo' => 'aaaaaa', 'operator' => 'admin', );
    	
    	$data = array (
  'iostockorder_name' => '1306411273入库单',
  'supplier' => 'test',
  'supplier_id' => '1',
  'branch' => '1',
  'type_id' => 1,
  'iso_price' => 0,
  'memo' => 'a:1:{i:0;a:3:{s:7:"op_name";s:15:"超级管理员";s:7:"op_time";s:16:"2011-05-26 20:01";s:10:"op_content";s:0:"";}}',
  'operator' => '超级管理员',
  'products' => 
  array (
    5 => 
    array (
      'product_id' => '5',
      'name' => '新一代制砖机',
      'spec_info' => '20',
      'bn' => '新一代制砖机2',
      'unit' => '台',
      'purchase_num' => '77777',
      'nums' => 1,
      'is_new' => 'false',
      'memo' => '',
    ),
    4 => 
    array (
      'product_id' => '4',
      'name' => '新一代制砖机',
      'spec_info' => '10',
      'bn' => '新一代制砖机1',
      'unit' => '台',
      'purchase_num' => '88888',
      'nums' => 1,
      'is_new' => 'false',
      'memo' => '8',
    ),
  ),
);
    	if (kernel::single('taoguaniostockorder_iostockorder')->save_iostockorder($data,$msg)){
            echo '成功:<br />';
    		echo $msg;
        }else {
			echo '失败:<br />';
    		var_dump($msg) ;
        }
    }
}
