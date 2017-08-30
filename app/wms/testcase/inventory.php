<?php
class inventory extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testInventory(){
           $inventory_data = array (
      
      'inventory_name' => '20130522我的仓库552',
      'pos' => '0',
      '_DTYPE_DATE' => 
      array (
        0 => 'add_time',
      ),
      'add_time' => '2013-05-22',
      'inventory_type' => '3',
      'memo' => 'test',
      'branch_id' => '1',
      'inventory_id' => '',
      'join_pd' => '',
      'branch_name' => '我的仓库',
    );
         $inventory_id = kernel::single('taoguaninventory_inventorylist')->create_inventory($inventory_data,$msg);
           $_POST = array (
          'branch_id' => '1',
          'inventory_id' => $inventory_id,
          'in_online_inv' => '1',
          'pos' => '0',
          'selecttype' => 'barcode',
          'barcode' => 'test001',
          'product_id' => '1',
          'number' => '10',
        );
        $result = kernel::single('taoguaninventory_inventorylist')->save_inventory($_POST,$msg);
        if ($result){
            echo '创建成功,盘点单ID'.$inventory_id;
        }else{
            echo '失败'.$msg;
        }
    }
}
