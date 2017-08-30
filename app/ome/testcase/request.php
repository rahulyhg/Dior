<?php
class request{
    
    CONST V = 1;
    public $node_id = '';
    static $token = '';
    public $url = '';
    public $time_out = '1';
    
    /**
     * 发起API
     * @access public
     * @param String $method 方法
     * @param Array $params 应用级参数
     * @return 远程API返回的结果
     */
    function call($method,$params){
        $headers = array(
            'Connection'=>$this->time_out,
        );

        $system_params = array(
            //'app_id' => 'taobao',
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
return new request();