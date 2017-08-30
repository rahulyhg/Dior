<?php
// define('BASE_URL','http://localhost/erp.trunk.localhost');
class stockout extends PHPUnit_Framework_TestCase
{
    function setUp() {}
    

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function teststockout()
    {


        // $_SERVER['HTTP_X_FORWARDED_HOST'] = 'erp.trunk.localhost';
        // $this->push();
        //$this->callback();
        // $this->search(24);
        $this->purchase_return_push(19);
    }

    public function purchase_return_push($rp_id)
    {
        kernel::single('console_event_trigger_purchasereturn')->create(array('rp_id'=>$rp_id), false);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function callback()
    {   

        $filter = array('process_id'=>'55cdc295855dd');

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
            'rsp'=>'succ',
            'data'=>json_encode($data),
        );

        $query_params['sign'] = base_certificate::gen_sign($query_params);

        $url = 'http://erp.trunk.localhost/index.php/openapi/asynccallback/async_result_handler/id/'.$rpcpoll['id'].'-'.$rpcpoll['calltime'];

        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(10)->post($url,$query_params);

        print_r($response);
    }

    public function search($isoid)
    {
        $oIso = &app::get('taoguaniostockorder')->model("iso");
                $iso_data = $oIso->dump(array('iso_id'=>$isoid),'branch_id,out_iso_bn,iso_bn');
                $data = array(
                     'out_order_code'=>$iso_data['out_iso_bn'],
                    'stockout_bn'=>$iso_data['iso_bn'],
                );
                $wms_id = kernel::single('ome_branch')->getWmsIdById($iso_data['branch_id']);
                
                $result = kernel::single('console_event_trigger_otherstockout')->search($wms_id, $data, true);
    }

}
