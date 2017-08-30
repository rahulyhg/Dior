<?php
class callback extends PHPUnit_Framework_TestCase
{
    function setUp() {
    }
    
    /**
    * 模拟异步回调
    */
    public function testCallback(){
        
        $token = 'fb744cc02d0da9938e1c9c6e17a88ba280171fb89e5fb1a87781d68b98004d8c';
        $url = 'http://localhost/oms/index.php/callback/id/631049b5a38f96498187165a065e2bc3-1369986077/app_id/ome';// 接收方api地址
        $core_http = kernel::single('base_httpclient');
        $data = array(
            //'succ'=> array(
                //array('item_code'=>'pbn1','wms_item_code'=>'W-pbn1-1'),
            //),
            'error'=> array(
                array('item_code'=>'pbn1'),
            ),
            //'true_bn'=> array('7101051603-099'),
            //'no_bn'=> array('7101051603-010'),
            'error_bn'=> array('pbn1'),
        );
        $query_params = array(
            'rsp' => 'succ',
            'res' => '2991',
            'err_msg' => 'ok',
            'data' => json_encode($data),
            'msg_id' => md5(time()),
            'node_id' => 'kejie',
        );
        $query_params['sign'] = self::gen_sign($query_params,$token);
        $response = $core_http->post($url,$query_params);
        print_r($response);
        exit;
    }

    static function gen_sign($params,$token){
        return strtoupper(md5(strtoupper(md5(self::assemble($params))).$token));
    }
    
    static function assemble($params) 
    {
        if(!is_array($params))  return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params AS $key=>$val){
            if(is_null($val))   continue;
            if(is_bool($val))   $val = ($val) ? 1 : 0;
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;
    }//End Function

}
