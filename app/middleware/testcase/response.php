<?php
class response{
    
    CONST V = 1;
    //public $node_id = '1635323432';
    //public $token = '1b653a634a39bbbe5ac29aa4752eb5198704482e08ac325d2c386cb396272d73';tnf

    //public $node_id = '1567937930';tnf
    //public $token = '8cf3765afb50f979c1d63f851cb58f6a22056b1b11cf9a95d65be0f32388f4a0';
    //public $url = 'http://youweiping.tg.taoex.com/index.php/api';
    //public $node_id = '1937340136';
    //public $token = '13f4c9ea59c9dfe74083992a210d42746fb9856813de9cb06644a389f5daf97c';
//    public $url = 'http://pantuo.erp.taoex.com/index.php/api';
//    public $node_id = '1501958830';
//    public $token = 'bca8c45039e6b66f2ae645f3c7e7884333550f1d9cde47b7e7ff461e0aa9118a';

   public $url = 'http://192.168.41.198/taoguan/trunk/shopex_erp/index.php/api';
    public $node_id = '1738363832';
    public $token = '8cf3765afb50f979c1d63f851cb58f6a22056b1b11cf9a95d65be0f32388f4a0';
    //public $node_id = 'Columbia';
    //public $token = 'bbf1db138fcdbee799ec9042c5da2a81522b81f0a56330c9fb8c6fafd715e830';
    //public $url = 'http://col.yueweiec.com/index.php/api';
    public $time_out = '10';
    
    function call($method,$params){
        $headers = array(
            'Connection'=>$this->time_out,
        );

        $system_params = array(
            'app_id' => 'ome',
            'method' => $method,
            'date' => date('Y-m-d H:i:s'),
            'format' => 'json',
            'certi_id' => '',
            'v' => self::V,
            'node_id' => $this->node_id,
        );
        $token = $this->token;
        $url = $this->url;
        
        $query_params = array_merge((array)$params,$system_params);
        $query_params['sign'] = self::gen_sign($query_params,$token);

        $core_http = kernel::single('base_httpclient');
        $core_http->set_timeout(30);
        $response = $core_http->post($url,$query_params,$headers='');
        return $response;
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