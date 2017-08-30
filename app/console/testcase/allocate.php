<?php
class allocate extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testAllocate(){
        $_POST = array (
  'from_branch_num' => 
  array (
    1 => '37',
    2 => '37',
  ),
  'to_branch_num' => 
  array (
    1 => '37',
    2 => '37',
  ),
  'at' => 
  array (
    1 => '2',
    2 => '3',
  ),
  'appropriation_type' => '2',
  'from_branch_id' => '1',
  'to_branch_id' => '2',
  'bn' => 'T20130520789938',
  'name' => 'T20130520789938',
  'barcode' => '',
  'product_id' => 
  array (
    0 => '1',
    1 => '2',
  ),
  'memo' => '',
  'operator' => 'admin',
);
        $oAppropriation = &app::get('taoguanallocate')->model('appropriation');
        $oBranch_product = &app::get('ome')->model('branch_product');
        $from_branch_id = $_POST['from_branch_id'];
        $to_branch_id = $_POST['to_branch_id'];
        $memo = $_POST['memo'];
        $nums = $_POST['at'];
        $from_branch_num = $_POST['from_branch_num'];
        $to_branch_num = $_POST['to_branch_num'];
        $operator = $_POST['operator'];
        $product_id = $_POST['product_id'];
        $appropriation_type = $_POST['appropriation_type'];
        foreach($nums as $product_id=>$num){
             

           $adata[] = array('from_pos_id'=>0,'to_pos_id'=>0,'from_branch_id'=>$from_branch_id,'to_branch_id'=>$to_branch_id,'product_id'=>$product_id,'num'=>$num);
        }
        if(kernel::single('console_receipt_allocate')->to_savestore($adata,$appropriation_type,$memo,$operator,$msg)){
        }

    }
}
