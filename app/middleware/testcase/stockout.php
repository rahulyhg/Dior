<?php
class stockout extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->_rpc = include 'rpc.php';
    }
    
    /**
    * 出库单
    */
    public function teststockout(){

        #出库单添加
        $items = array(
            array('bn'=>'pb001','name'=>'pb001-name','num'=>'1','price'=>'2.0'),
            array('bn'=>'pb002','name'=>'pb002-name','num'=>'2','price'=>'3.0'),
        );
        $sdf = array(
            'io_type' => 'PURCHASE_RETURN',
            'io_bn' => 'pu001',
            'branch_bn' => 'selfwms',
            'total_goods_fee' => '100',
            'create_time' => date('Y-m-d H:i:s'),
            'memo' => '备注啦',
            'receiver_name' => '收货人姓名',
            'receiver_zip' => '收货人邮编',
            'receiver_state' => '省',
            'receiver_city' => '市',
            'receiver_district' => '县',
            'receiver_address' => '地址',
            'receiver_phone' => '电话',
            'receiver_mobile' => '手机',
            'receiver_email' => '邮箱',
            'storage_code' => '库内存放点编号',
            'items' => $items
        );
        $c = 'middlewaretest_stockout';
        $m = 'create_callback';
        $p = array('wo le ge qu');
        //$rs = $this->_rpc->setUserCallback($c,$m,$p)->request('stockout_create',$sdf,$sync=false);

        #出库单取消
        $sdf = array(
            'io_type' => 'PURCHASE_RETURN',  
            'io_bn' => 'pu001',    
        );
        //$rs = $this->_rpc->request('stockout_cancel',$sdf,$sync=true);
        
        #出库单状态回传
        
        $sdf = array (
'stockout_bn'=>'I201406231717001837',
'warehouse'=>'ILC-SH',
'status'=>'FINISH',
'remark'=>'',
'operate_time'=>'2014-06-13 00:00:00',
'item'=>'[{"product_bn":"barytztest003","num":"2"}]',

);
        $rs = $this->_rpc->response('wms.stockout.status_update',$sdf);

        print_r(is_array($rs) ? $rs : json_decode($rs,1));
    }

}
