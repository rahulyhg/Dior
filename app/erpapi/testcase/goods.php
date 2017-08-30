<?php
// define('BASE_URL','http://localhost/erp.trunk.localhost');
class goods extends PHPUnit_Framework_TestCase
{
    function setUp() {}
    

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function testgoods()
    {
        // $_SERVER['HTTP_X_FORWARDED_HOST'] = 'erp.trunk.localhost';
        $this->push();
        // $this->callback();
    }

    public function push()
    {

        app::get('console')->model('foreign_sku')->update(array('status'=>4),array('wms_id'=>31));
        $filter = array(
            'view'=>'17',
            'inner_product_id'=>array(4),
            'wms_id' => 31,
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

        $filter = array('process_id'=>'55cd49ec560fc');

        $rpcpollModel = app::get('base')->model('rpcpoll');
        $rpcpoll = $rpcpollModel->dump($filter);

        $params = json_decode($rpcpoll['params']['item_lists'],true);

        foreach ($params['item'] as $key => $value) {
            $succ[] = array(
                'item_code' => $value['item_code'],
                'wms_item_code' => 'wms_'.$value['item_code'],
            );
        }
        $data = array(
            'succ'=>$succ,
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
