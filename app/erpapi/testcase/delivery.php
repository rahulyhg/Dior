<?php
// define('BASE_URL','http://localhost/erp.trunk.localhost');
class delivery extends PHPUnit_Framework_TestCase
{
    function setUp() {}
    

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function testdelivery()
    {
                            $tmp_data = array(
                                'delivery_bn' => '1507071100004',
                                'logi_no'     => 'test12345678',
                                'action'      => 'addLogiNo',
                                'status'      => 'update',
                            );
                            //kernel::single('wms_event_trigger_delivery')->doUpdate($this->__channelObj->wms['channel_id'], $tmp_data, true);
                            //app::get('ome')->model('delivery')->update(array(''));
                            kernel::single('ome_event_receive_delivery')->update($tmp_data);return;

        // $_SERVER['HTTP_X_FORWARDED_HOST'] = 'erp.trunk.localhost';
        $this->push(46);
        // $this->cancel(45);
        // $this->search(45);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function push($delivery_id)
    {
        $deliveryModel = app::get('ome')->model('delivery');

        $deliveryModel->wmsdelivery_create($delivery_id);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function cancel($delivery_id)
    {
        $dlyObj = &app::get('ome')->model("delivery");
        $branchLib = kernel::single('ome_branch');
        $eventLib = kernel::single('ome_event_trigger_delivery');
        $deliveryInfo = $dlyObj->dump($delivery_id,'*');
        $wms_id = $branchLib->getWmsIdById($deliveryInfo['branch_id']);
        $res = $eventLib->cancel($wms_id,array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']),true);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function search($delivery_id)
    {
                $oDelivery = app::get('ome')->model('delivery');
                $delivery = $oDelivery->dump($delivery_id,'delivery_bn,branch_id');
                $wms_id = kernel::single('ome_branch')->getWmsIdById($delivery['branch_id']);
                $result = kernel::single('ome_event_trigger_delivery')->search($wms_id, $delivery, true);
    }

}
