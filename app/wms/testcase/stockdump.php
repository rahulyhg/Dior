<?php
class stockdump extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testStockdump(){
        $data = array (
          'stockdump_bn' => '20130531000004',
          'branch_bn' => 'stockhouse',
          'status' => 'FINISH',
          'create_time' => '1368682745',
         'memo' => '备注啦。。啦啦啦。。',
          'items' => 
          array (
            0 => 
            array (
              'num' => '2',
              'bn' => 'test001',
            
            ),
//             1 => 
//            array (
//              'num' => '2',
//              'bn' => 'test002',
//            
//            ),
          ),
        );
        $branch_productObj = &app::get('ome')->model('branch_product');
        #取出转储出和入仓
        $oStockdump = &app::get('console')->model('stockdump');
        $stockdump =  $oStockdump->dump(array('stockdump_bn'=>$data['stockdump_bn']),'from_branch_id,to_branch_id');
        $from_branch_id = $stockdump['from_branch_id'];
        $to_branch_id = $stockdump['to_branch_id'];
        $pObj = &app::get('ome')->model('products');
        $branch = $pObj->db->selectrow('SELECT branch_id FROM sdb_ome_branch where branch_bn=\''.$data['branch_bn'].'\'');
        $branch_id = $branch['branch_id'];
        echo "执行前仓库商品库存明细如下\r\n";
        foreach ($data['items'] as $item){
            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$from_branch_id);
            $to_branch_store = $branch_productObj->getStoreByBranch($product['product_id'],$to_branch_id);
            echo $item['bn']."出库仓库存:".$usable_store."入库仓库存:".$to_branch_store."\r\n";
        }
        
        echo "----------------------------\r\n";
        $wms_id = kernel::single('ome_branch')->getWmsIdById(1);
        $res = kernel::single('middleware_wms_response', $wms_id)->stockdump_result($data);;
        print_r($res);
        echo "出库后商品库存明细如下\r\n";
        foreach ($data['items'] as $item){
            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$from_branch_id);
            $to_branch_store = $branch_productObj->getStoreByBranch($product['product_id'],$to_branch_id);
            echo $item['bn']."出库仓库存:".$usable_store."入库仓库存:".$to_branch_store."\r\n";
        }
    }
}
