<?php
class otherin extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testOtherin(){
        $data = array (
          'io_type' => 'OTHER',
          'io_bn' => 'E20130530000001',
          #'branch_id' => '6',
          'io_source' => 'selfwms',
          'io_status' => 'FINISH',
          'branch_bn' => 'branch1',
          'supplier_bn' => NULL,
          'items' => 
          array (
            0 => 
            array (
              
              'bn' => 'test001',
              'product_name' => 'test001',
              'normal_num' => '6',
          
            ),
             1 => 
            array (
              
              'bn' => 'test002',
              'product_name' => 'test002',
              'normal_num' => '6',
          
            ),
          ),
        );
        $branch_productObj = &app::get('ome')->model('branch_product');
        $pObj = &app::get('ome')->model('products');
        $branch = $pObj->db->selectrow('SELECT branch_id FROM sdb_ome_branch where branch_bn=\''.$data['branch_bn'].'\'');
        $branch_id = $branch['branch_id'];
        echo "出库前商品库存明细如下\r\n";
        foreach ($data['items'] as $item){
            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$branch_id);
            echo $item['bn']."可用库存:".$usable_store."\r\n";
        }
        echo "----------------------------\r\n";
        $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id);
        $res = kernel::single('middleware_wms_response', $wms_id)->stockin_result($data);
        echo "返回结果:\r\n";
        print_r($res);
        echo "出库后商品库存明细如下\r\n";
        foreach ($data['items'] as $item){
            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$branch_id);
            echo $item['bn']."入库良品:".$item['normal_num']."不良品".$item['defective_num'].",可用库存:".$usable_store."\r\n";
        }
        #查看此单据是否有异常，如果有异常显示入库数量
        $iso_item = kernel::single('wms_iostockdata')->getIsoBybn($data['io_bn']);
        if ($iso_item){
            echo "以下数据异常\r\n";
            foreach($iso_item as $item){
                echo '货号:'.$item['bn'].'原数量:'.$item['nums'].',实际入库数量:'.$item['normal_num'].',残损数量:'.$item['defective_num'].'<br>';
            }
        }
    }
}
