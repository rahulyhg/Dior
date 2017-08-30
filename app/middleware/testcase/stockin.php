<?php
class stockin extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->_rpc = include 'rpc.php';
    }
    
    /**
    * 入库单
    */
    public function testStockin(){

//        #入库单添加
//        $items = array(
//            array('bn'=>'pb001','name'=>'pb001-name','num'=>'1','price'=>'2.0'),
//            array('bn'=>'pb002','name'=>'pb002-name','num'=>'2','price'=>'3.0'),
//        );
//        $sdf = array(
//            'io_type' => 'PURCHASE',
//            'io_bn' => 'pu001',
//            'total_goods_fee' => '100',
//            'create_time' => date('Y-m-d H:i:s'),
//            'memo' => '备注啦',
//            'shipper_name' => '发货人姓名',
//            'shipper_zip' => '发货人邮编',
//            'shipper_state' => '省',
//            'shipper_city' => '市',
//            'shipper_district' => '县',
//            'shipper_address' => '地址',
//            'shipper_phone' => '电话',
//            'shipper_mobile' => '手机',
//            'shipper_email' => '邮箱',
//            'storage_code' => '库内存放点编号',
//            'items' => $items
//        );
//        $c = 'middlewaretest_stockin';
//        $m = 'create_callback';
//        $p = array('wo le ge qu');
//        //$rs = $this->_rpc->setUserCallback($c,$m,$p)->request('stockin_create',$sdf,$sync=false);
//
//        //#入库单取消
//        $sdf = array(
//            'io_type' => 'DEFECTIVE',
//            'io_bn' => 'pu001',
//        );
//        //$rs = $this->_rpc->request('stockin_cancel',$sdf,$sync=true);
//        
        #入库单状态回传
      
$items = array(
            array('product_bn'=>'6957133886152','name'=>'test001','normal_num'=>'20','price'=>'2.0'),
            array('product_bn'=>'6957133886145','name'=>'kjtest001','normal_num'=>'10','price'=>'2.0'),
        );
$items = json_encode($items);
$sdf = array (
 'status'=>'FINISH',
'remark'=>'',
'task'=>'',
'node_version'=>'',
'app_id'=>'ecos.ome',
//'item'=>'[{"product_bn": "178662405", "normal_num": 0, "defective_num": 0}, {"product_bn": "178662407", "normal_num": 0, "defective_num": 0}, {"product_bn": "178662408", "normal_num": 0, "defective_num": 0}, {"product_bn": "178662209", "normal_num": 0, "defective_num": 0}, {"product_bn": "178662201", "normal_num": 0, "defective_num": 0}, {"product_bn": "178662202", "normal_num": 0, "defective_num": 0}, {"product_bn": "178662203", "normal_num": 0, "defective_num": 0}, {"product_bn": "178662204", "normal_num": 0, "defective_num": 0}, {"product_bn": "178662205", "normal_num": 0, "defective_num": 0}]',
'warehouse'=>'20545',
'stockin_bn'=>'MS201406231617005503',
);
        $rs = $this->_rpc->response('wms.stockin.status_update',$sdf);

        print_r(is_array($rs) ? $rs : json_decode($rs,1));
    }

}
