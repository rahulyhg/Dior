<?php
class purchasereturn extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testPurchasereturn(){
        $data = array (
          'io_type' => 'PURCHASE_RETURN',
          #'io_source' => 'selfwms',
          'io_bn' => 'H201305301517002401',
          'branch_bn' => 'branch1',
          'memo' => 'test采购退货',
          'io_status' => 'FINISH',
          'items' => 
          array (
            0 => 
            array (
              'bn' => 'test002',
              'num' => '2',
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
            echo $item['bn']."出库数量".$item['num'].",可用库存:".$usable_store."\r\n";
        }
        echo "-------------------------------------------------------\r\n";
        $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id);
        $res = kernel::single('middleware_wms_response', $wms_id)->stockout_result($data);;
        print_r($res);
        echo "出库后商品库存明细如下\r\n";
        foreach ($data['items'] as $item){
            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$branch_id);
            echo $item['bn']."可用库存:".$usable_store."\r\n";
        }
        echo "---------------------------------------------------------\r\n";
        #查看此单据是否有异常，如果有异常显示入库数量
        $iso_item = kernel::single('wms_iostockdata')->getPurchasereturnBybn($data['io_bn']);
        if ($iso_item){
            echo "以下数据异常\r\n";
            foreach($iso_item as $item){
                echo '货号:'.$item['bn'].'原数量'.$item['num'].',实际出库数量'.$item['out_num']."\r\n";
            }
        }
    }
}
