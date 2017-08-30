<?php
class stockdump extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->_rpc = include 'rpc.php';
    }
    
    /**
    * 出库单
    */
    public function teststockdump(){

        #出库单添加
        $items = array(
            array('bn'=>'pb001','name'=>'pb001-name','num'=>'1','price'=>'2.0'),
            array('bn'=>'pb002','name'=>'pb002-name','num'=>'2','price'=>'3.0'),
        );
        $sdf = array(
            'stockdump_bn' => 'pu001',
            'create_time' => date('Y-m-d H:i:s'),
            'memo' => 'memo',
            'src_storage' => 'src_storage',
            'dest_storage' => 'dest_storage',
            'items' => $items
        );
        $c = 'middlewaretest_stockdump';
        $m = 'create_callback';
        $p = array('wo le ge qu');
        //$rs = $this->_rpc->setUserCallback($c,$m,$p)->request('stockdump_create',$sdf,$sync=false);

        #出库单取消
        $sdf = array(
            'stockdump_bn' => 'pu001',    
        );
        //$rs = $this->_rpc->request('stockdump_cancel',$sdf,$sync=true);
        
        #出库单状态回传
        $item = array(
            array('product_bn'=>'pbn1','num'=>'1'),
            array('product_bn'=>'pbn12','num'=>'12'),
        );
        $sdf = array(
            'stockdump_bn' => 'H00001',
            'warehouse' => 'kejie',
            'status' => 'FINISH',
            'task' => time(),
            'remark' => '备注啦',
            'item' => '[{"product_bn": "01410003", "normal_num": "400", "defective_num": "0"}, {"product_bn": "0111000A06", "normal_num": "16", "defective_num": "0"}, {"product_bn": "0111000A05", "normal_num": "24", "defective_num": "0"}, {"product_bn": "0111000A04", "normal_num": "20", "defective_num": "0"}, {"product_bn": "0111000A03", "normal_num": "16", "defective_num": "0"}, {"product_bn": "0111000A02", "normal_num": "28", "defective_num": "0"}, {"product_bn": "0111000A01", "normal_num": "24", "defective_num": "0"}, {"product_bn": "0111000A20", "normal_num": "28", "defective_num": "0"}, {"product_bn": "0111000A19", "normal_num": "8", "defective_num": "0"}, {"product_bn": "0111000A10", "normal_num": "20", "defective_num": "0"}, {"product_bn": "0111000A09", "normal_num": "40", "defective_num": "0"}, {"product_bn": "0111000A08", "normal_num": "40", "defective_num": "0"}, {"product_bn": "0111000A07", "normal_num": "20", "defective_num": "0"}, {"product_bn": "0111000A18", "normal_num": "8", "defective_num": "0"}, {"product_bn": "0111000A17", "normal_num": "32", "defective_num": "0"}, {"product_bn": "0111000A16", "normal_num": "28", "defective_num": "0"}, {"product_bn": "0111000A15", "normal_num": "8", "defective_num": "0"}, {"product_bn": "0111000A14", "normal_num": "40", "defective_num": "0"}, {"product_bn": "0111000A13", "normal_num": "84", "defective_num": "0"}, {"product_bn": "0111000A12", "normal_num": "80", "defective_num": "0"}, {"product_bn": "0111000A11", "normal_num": "20", "defective_num": "0"}, {"product_bn": "0111000A22", "normal_num": "8", "defective_num": "0"}, {"product_bn": "0111000A21", "normal_num": "24", "defective_num": "0"}, {"product_bn": "0111000A37", "normal_num": "4", "defective_num": "0"}, {"product_bn": "0111000A28", "normal_num": "12", "defective_num": "0"}, {"product_bn": "0111000A36", "normal_num": "4", "defective_num": "0"}, {"product_bn": "0111000A27", "normal_num": "20", "defective_num": "0"}, {"product_bn": "0111000A40", "normal_num": "4", "defective_num": "0"}, {"product_bn": "0111000A26", "normal_num": "12", "defective_num": "0"}, {"product_bn": "0111000A39", "normal_num": "8", "defective_num": "0"}, {"product_bn": "0111000A25", "normal_num": "20", "defective_num": "0"}, {"product_bn": "0111000A38", "normal_num": "12", "defective_num": "0"}, {"product_bn": "0111000A24", "normal_num": "40", "defective_num": "0"}, {"product_bn": "01110003B", "normal_num": "30", "defective_num": "0"}, {"product_bn": "0111000A23", "normal_num": "60", "defective_num": "0"}, {"product_bn": "0111000A31", "normal_num": "4", "defective_num": "0"}, {"product_bn": "01110003L", "normal_num": "30", "defective_num": "0"}, {"product_bn": "0111000A30", "normal_num": "8", "defective_num": "0"}, {"product_bn": "01110003K", "normal_num": "15", "defective_num": "0"}, {"product_bn": "0111000A29", "normal_num": "4", "defective_num": "0"}, {"product_bn": "01110003J", "normal_num": "15", "defective_num": "0"}, {"product_bn": "0111000A34", "normal_num": "4", "defective_num": "0"}, {"product_bn": "0111000A33", "normal_num": "8", "defective_num": "0"}, {"product_bn": "0111000A32", "normal_num": "4", "defective_num": "0"}, {"product_bn": "0111000A35", "normal_num": "12", "defective_num": "0"}, {"product_bn": "01120010A", "normal_num": "8", "defective_num": "0"}, {"product_bn": "01110013A", "normal_num": "2", "defective_num": "0"}, {"product_bn": "01110013E", "normal_num": "6", "defective_num": "0"}, {"product_bn": "01110013B", "normal_num": "7", "defective_num": "0"}, {"product_bn": "01110013C", "normal_num": "6", "defective_num": "0"}, {"product_bn": "01110013D", "normal_num": "8", "defective_num": "0"}, {"product_bn": "01110013F", "normal_num": "4", "defective_num": "0"}, {"product_bn": "01110003D", "normal_num": "15", "defective_num": "0"}, {"product_bn": "01110013G", "normal_num": "7", "defective_num": "0"}, {"product_bn": "01110003C", "normal_num": "25", "defective_num": "0"}, {"product_bn": "01110010B", "normal_num": "2", "defective_num": "0"}, {"product_bn": "01110010C", "normal_num": "4", "defective_num": "0"}, {"product_bn": "01110010D", "normal_num": "2", "defective_num": "0"}, {"product_bn": "01110005F", "normal_num": "10", "defective_num": "0"}, {"product_bn": "01110010E", "normal_num": "11", "defective_num": "0"}, {"product_bn": "01110010F", "normal_num": "11", "defective_num": "0"}, {"product_bn": "01110005E", "normal_num": "10", "defective_num": "0"}, {"product_bn": "01110005D", "normal_num": "5", "defective_num": "0"}, {"product_bn": "01110005B", "normal_num": "16", "defective_num": "0"}, {"product_bn": "01120011B", "normal_num": "11", "defective_num": "0"}, {"product_bn": "01110010G", "normal_num": "20", "defective_num": "0"}, {"product_bn": "01110010H", "normal_num": "12", "defective_num": "0"}, {"product_bn": "01120011A", "normal_num": "6", "defective_num": "0"}, {"product_bn": "01110005A", "normal_num": "11", "defective_num": "0"}, {"product_bn": "01120010C", "normal_num": "6", "defective_num": "0"}, {"product_bn": "01120010B", "normal_num": "7", "defective_num": "0"}, {"product_bn": "01110006C", "normal_num": "10", "defective_num": "0"}, {"product_bn": "01110006B", "normal_num": "10", "defective_num": "0"}, {"product_bn": "01110012A", "normal_num": "2", "defective_num": "0"}, {"product_bn": "01110011C", "normal_num": "3", "defective_num": "0"}, {"product_bn": "01110006F", "normal_num": "6", "defective_num": "0"}, {"product_bn": "01110006E", "normal_num": "5", "defective_num": "0"}, {"product_bn": "01110011D", "normal_num": "2", "defective_num": "0"}, {"product_bn": "01110011E", "normal_num": "4", "defective_num": "0"}, {"product_bn": "01110011F", "normal_num": "3", "defective_num": "0"}, {"product_bn": "01110014D", "normal_num": "1", "defective_num": "0"}, {"product_bn": "01110014G", "normal_num": "1", "defective_num": "0"}, {"product_bn": "01110012D", "normal_num": "7", "defective_num": "0"}, {"product_bn": "01110012E", "normal_num": "5", "defective_num": "0"}, {"product_bn": "01110015C", "normal_num": "1", "defective_num": "0"}, {"product_bn": "01110012F", "normal_num": "5", "defective_num": "0"}, {"product_bn": "01110012G", "normal_num": "4", "defective_num": "0"}, {"product_bn": "01410001", "normal_num": "220", "defective_num": "0"}, {"product_bn": "01410002", "normal_num": "625", "defective_num": "0"}, {"product_bn": "01310011B", "normal_num": "180", "defective_num": "0"}, {"product_bn": "01310011A", "normal_num": "190", "defective_num": "0"}, {"product_bn": "01310012B", "normal_num": "100", "defective_num": "0"}, {"product_bn": "01310012A", "normal_num": "100", "defective_num": "0"}, {"product_bn": "01310010B", "normal_num": "325", "defective_num": "0"}, {"product_bn": "01310010A", "normal_num": "260", "defective_num": "0"}, {"product_bn": "01320009C", "normal_num": "12", "defective_num": "0"}, {"product_bn": "01320009B", "normal_num": "12", "defective_num": "0"}, {"product_bn": "01310005E", "normal_num": "15", "defective_num": "0"}, {"product_bn": "01310005C", "normal_num": "180", "defective_num": "0"}, {"product_bn": "01310005B", "normal_num": "190", "defective_num": "0"}, {"product_bn": "01310005A", "normal_num": "5", "defective_num": "0"}, {"product_bn": "01310003E", "normal_num": "5", "defective_num": "0"}, {"product_bn": "01310003C", "normal_num": "100", "defective_num": "0"}, {"product_bn": "01310003B", "normal_num": "100", "defective_num": "0"}, {"product_bn": "01310003A", "normal_num": "4", "defective_num": "0"}, {"product_bn": "01310004D", "normal_num": "5", "defective_num": "0"}, {"product_bn": "01310004C", "normal_num": "330", "defective_num": "0"}, {"product_bn": "01310004B", "normal_num": "200", "defective_num": "0"}, {"product_bn": "01310004A", "normal_num": "5", "defective_num": "0"}, {"product_bn": "01320006A", "normal_num": "15", "defective_num": "0"}, {"product_bn": "01320006B", "normal_num": "20", "defective_num": "0"}, {"product_bn": "01510049", "normal_num": "50", "defective_num": "0"}, {"product_bn": "01510048", "normal_num": "5000", "defective_num": "0"}, {"product_bn": "01510050", "normal_num": "50", "defective_num": "0"}]',
            'operate_time' => date('Y-m-d H:i:s')
        );
        $rs = $this->_rpc->response('wms.stockdump.status_update',$sdf);

        print_r(is_array($rs) ? $rs : json_decode($rs,1));
    }

}
