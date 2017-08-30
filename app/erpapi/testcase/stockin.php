<?php
class stockin extends PHPUnit_Framework_TestCase
{
    function setUp() {}
    

    public function testpurchase()
    {
        // $this->search(36);
         // $this->purchase_push(56);
        //$this->purchase_cancel(56);
        $this->callback('123123123');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function purchase_push($po_id)
    {
      $rs = kernel::single('console_event_trigger_purchase')->create(array('po_id'=>$po_id), false);
      error_log(var_export($rs,true),3,'d:response.log');
    }

    public function purchase_cancel($po_id)
    {
        $poObj = app::get('purchase')->model('po');
        $po = $poObj->dump($po_id, '*');

        $purchaseObj = kernel::single('console_event_trigger_purchase');
        $po_bn = $po['po_bn'];
        $branch_id = $po['branch_id'];
        $data = array(
            'io_type'   =>'PURCHASE',
            'io_bn'     =>$po_bn,
            'branch_id' =>$branch_id
        );

        $result = $purchaseObj->cancel($data, true);

    }

  public function callback($process_id)
    {   

        $filter = array('process_id'=>$process_id);

        $rpcpollModel = app::get('base')->model('rpcpoll');
        $rpcpoll = $rpcpollModel->dump($filter);

        // $params = json_decode($rpcpoll['params']['item_lists'],true);

        // foreach ($params['item'] as $key => $value) {
        //     $succ[] = array(
        //         'item_code' => $value['item_code'],
        //         'wms_item_code' => 'wms_'.$value['item_code'],
        //     );
        // }
        // print_r($rpcpoll);exit;
        $data = array(
            'wms_order_code'=>$rpcpoll['params']['out_order_code'],
        );
        $query_params = array(
            'rsp'=>'fail',
            'data'=>json_encode($data),
        );

        $query_params['sign'] = base_certificate::gen_sign($query_params);

        $url = 'http://192.168.41.98/erpbugfix/index.php/openapi/asynccallback/async_result_handler/id/'.$rpcpoll['id'].'-'.$rpcpoll['calltime'];

        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(10)->post($url,$query_params);

        print_r($response);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function search($id)
    {
        $Opo = app::get('purchase')->model('po');
        $po = $Opo->dump($id, 'branch_id,out_iso_bn,po_bn');
        $branch_id = $po['branch_id'];
        $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id);
        $data = array(
            'out_order_code'=>$po['out_iso_bn'],
            'stockin_bn'=>$po['po_bn'],
        );
        $result = kernel::single('console_event_trigger_purchase')->search($wms_id,$data, true);
    }
}
