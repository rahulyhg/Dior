<?php
class reship extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->_rpc = include 'rpc.php';
    }
    
    /**
    * 退货单
    */
    public function testreship(){

        //#退货单添加
        $items = array(
            array('bn'=>'pb001','name'=>'pb001-name','num'=>'1','price'=>'2.0'),
            array('bn'=>'pb002','name'=>'pb002-name','num'=>'2','price'=>'3.0'),
        );
        $sdf = array(
            'reship_bn' => 'pu001',
            'branch_bn' => '仓库编号',
            'create_time' => date('Y-m-d H:i:s'),
            'memo' => '备注',
            'original_delivery_bn' => '原始发货单号',
            'logi_no' => '物流单号',
            'logi_name' => '物流公司名称',
            'order_bn' => '订单号',
            'receiver_name' => '收货人姓名',
            'receiver_zip' => '收货人邮编',
            'receiver_state' => '收货人所在省',
            'receiver_city' => '收货人所在市',
            'receiver_district' => '收货人所在县（',
            'receiver_address' => '收货人详细地址',
            'receiver_phone' => '收货人固定电话',
            'receiver_mobile' => '收货人手机号码',
            'receiver_email' => '收货人邮箱',
            'storage_code' => '库内存放点编号',
            'items' => $items
        );
        $c = 'middlewaretest_reship';
        $m = 'create_callback';
        $p = array('wo le ge qu');
        //$rs = $this->_rpc->setUserCallback($c,$m,$p)->request('reship_create',$sdf,$sync=false);

        #退货单取消
        $sdf = array(
            'reship_bn' => 'pu001',
            'branch_bn' => '999',    
        );
        //$rs = $this->_rpc->request('reship_cancel',$sdf,$sync=true);
        
        #退货单状态回传
        $item = array(
            array('jd001'=>'pbn1','normal_num'=>'1','defective_num'=>'0'),
           
        );
        $sdf = array(
           'logistics'=>'',
'status'=>'FINISH',
'remark'=>'',
'task'=>'',
'logi_no'=>'',
'node_version'=>'1.0',
'app_id'=>'ecos.ome',
'item'=>'[{"product_bn":"6921463745526","normal_num":"1","defective_num":"0"}]',
'reship_bn'=>'201407250913004618',
'warehouse'=>'ILC-SH',
        );
        $rs = $this->_rpc->response('wms.reship.status_update',$sdf);

        print_r($rs);
    }

}
