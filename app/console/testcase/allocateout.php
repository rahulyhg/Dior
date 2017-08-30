<?php
class allocateout extends PHPUnit_Framework_TestCase
{
    function setUp() {

    }

    public function testAllocateout(){
        $isoObj = &app::get('taoguaniostockorder')->model("iso");
        $data = array (
            'io_bn' => 'R20130624000001',
            'iso_id'=>19
        );

        $iso_id = $data['iso_id'];
        $result = kernel::single('console_event_trigger_otherstockout')->create(array('iso_id'=>$iso_id),false);
        $isoObj->update(array('check_status'=>'2'),array('iso_bn'=>$data['io_bn']));
         
//        #ocs发货单请求日志参数
//        $contents = @file_get_contents('http://ocs2.test.vmod.cn/ocs1.3.0encode/data/store.wms.outorder.create');
//        $ocs_params = @unserialize($contents);
//
//        #讨管发货单请求日志参数
//        $tg_contents = @file_get_contents('http://localhost/oms/data/store.wms.outorder.create');
//        $tg_params = @unserialize($tg_contents);
//
//        $diff_arr_item = middleware_func::compare_params($ocs_params,$tg_params);
//        print_r($diff_arr_item);
    }
}
