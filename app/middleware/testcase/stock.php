<?php
class stock extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->_rpc = include 'rpc.php';
    }
    
    /**
    * 库存对账单
    */
    public function teststock(){

        #库存对账单状态回传
        $sdf = array(
            'task' => time(),
            'item' => $item,
            'operate_time' => date('Y-m-d H:i:s')
        );
        $rs = $this->_rpc->response('wms.stock.quantity',$sdf);

        print_r($rs);
    }


}
