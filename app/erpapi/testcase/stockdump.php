<?php
// define('BASE_URL','http://localhost/erp.trunk.localhost');
class stockdump extends PHPUnit_Framework_TestCase
{
    function setUp() {}
    

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function teststockdump()
    {


        // $_SERVER['HTTP_X_FORWARDED_HOST'] = 'erp.trunk.localhost';
        // $this->push();
        $this->callback();
    }

    public function push()
    {
        $filter = array(
            'view'=>'5',
            'inner_product_id'=>array(9),
            'wms_id' => 6,
        );

        $rs = kernel::single('console_goodssync')->sync_all($filter);

        print_r($rs);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function callback()
    {   

        $filter = array('process_id'=>'55cdc66a58f51');

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


}
