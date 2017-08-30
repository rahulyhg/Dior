<?php
class saveinventory extends PHPUnit_Framework_TestCase
{
    function setUp() {
    
    }
    
    public function testSaveinventory(){
     	$data =array ( 'branch_id' => '1', 'barcode' => '11', 'product_id' => '1', 'pos_id' => '1', 'number' => '100', );
    	if (kernel::single('taoguaninventory_inventory')->save_inventory($data,$msg)){
            echo '成功:<br />';
    		echo $msg;
        }else {
			echo '失败:<br />';
    		var_dump($msg) ;
        }
    }
}
