<?php
class purchase extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->branch_bn = 'stockhouse';
        $wms_id = kernel::single('ome_branch')->getWmsId($this->branch_bn);
        $this->wms_request_instance = kernel::single('middleware_wms_request',$wms_id);

        //$this->wms_response_instance = kernel::single('middleware_wms_response',$wms_id);
    }
    
    /**
    * 采购单
    */
    public function testPurchase(){
        #采购单通知
        $sdf = array(
            'purchase_no' => 'pu001',
        );
        $rs = $this->wms_request_instance->stockin_create($sdf,$sync=false);
        print_r($rs);exit;

        #采购单通知变更
        //$sdf = array(
            //'purchase_no' => 'pu001',    
        //);
        //$rs = $this->wms_request_instance->purchase_noticeUpdate($sdf,$sync=true);
        //print_r($rs);
        
        #采购单状态回传
        $sdf = array(
            'purchase_no' => 'pu001',    
        );
        $rs = $this->wms_response_instance->purchase_result($sdf);
        print_r($rs);
    }
}
