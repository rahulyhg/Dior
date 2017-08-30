<?php
/**
* sign验证签名算法
* @copytight shopex.cn 2011.09.02
*/
class rpc_sign{

    /**
    * 获取服务端sign签名字符串
    * @access public 
    * @param Array $request 请求参数
    * @return signt签名字符串
    */
    public function get_response_sign($response,$sign_key){
        return self::gen_sign($response, 'response',$sign_key);
    }

    /**
    * 获取客户端sign签名字符串
    * @access public 
    * @param Array $request 请求参数
    * @return signt签名字符串
    */
    public function get_request_sign($request,$sign_key){
        return self::gen_sign($request, 'request',$sign_key);
    }

    static function gen_sign($params,$rpc_type='response',$sign_key){
        return strtoupper(md5(strtoupper(md5(self::assemble($params))).$sign_key));
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