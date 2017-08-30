<?php
class purchase extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testPurchase(){
        $data = array (
          'io_type' => 'PURCHASE',
          'io_bn' => 'I201306041617001263',
          'branch_bn' => 'ytc',
          'storage_code' => 'a',
          'create_time' => '1368682745',
         'memo' => '备注啦。。啦啦啦。。5533',
         'io_status'=>'FINISH',//PARTIN FINISH
          'items' => 
          array (
            0 => 
            array (
              'normal_num' => '10',
              'bn' => 'test001',
              'name' => 'test001',
              'price' => '0.000',
               #'defective_num'=>'1',
              
            ),
            
          ),
         
        );
        $branch_productObj = &app::get('ome')->model('branch_product');
        $pObj = &app::get('ome')->model('products');
        $branch = $pObj->db->selectrow('SELECT branch_id FROM sdb_ome_branch where branch_bn=\''.$data['branch_bn'].'\'');
        $branch_id = $branch['branch_id'];
        echo "入库前商品库存明细如下\r\n";
        foreach ($data['items'] as $item){

            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$branch_id);
            echo $item['bn']."可用库存:".$usable_store."请求入库良品:".$item['normal_num']."不良品:".$item['defective_num']."\r\n";
        }
        echo "----------------------------\r\n";
        $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id);
        $res = kernel::single('middleware_wms_response', $wms_id)->stockin_result($data);;
        print_r($res);
        #查看此单据是否有异常，如果有异常显示入库数量
        $iso_item = kernel::single('wms_iostockdata')->getPoBybn($data['io_bn']);
        echo "入库后商品库存明细如下\r\n";
        foreach ($data['items'] as $item){
            $product = $pObj->dump(array('bn'=>$item['bn']), 'product_id');
            $usable_store = $branch_productObj->getStoreByBranch($product['product_id'],$branch_id);
            echo $item['bn']."可用库存:".$usable_store."\r\n";
        }
        echo "------------------------\r\n";
        if ($iso_item){
           
            echo "以下数据异常:\r\n";
            foreach($iso_item as $item){
                echo '货号:'.$item['bn'].',原数量'.$item['num'].',实际入库数量,'.$item['in_num'].',不良品数量为:'.$item['defective_num']."\r\n";
            }
        }
    }
}
