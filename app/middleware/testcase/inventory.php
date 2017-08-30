<?php
class inventory extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->_rpc = include 'rpc.php';
    }
    
    /**
    * 盘点单
    */
    public function testinventory(){

        #盘点单状态回传
        $item = array(
            array('product_bn'=>'SK-0001','normal_num'=>'1','defective_num'=>'2'),
            array('product_bn'=>'SK-0002','normal_num'=>'1','defective_num'=>'2'),
        );
        $sdf = array(
            'inventory_bn' => 'D00001',
            'warehouse' => 'kejie',
            'task' => time(),
            'remark' => '备注啦',
            'item' => json_encode($item),
            'operate_time' => date('Y-m-d H:i:s')
        );
        $rs = $this->_rpc->response('wms.inventory.status_update',$sdf);
        print_r($rs);
    }

}
