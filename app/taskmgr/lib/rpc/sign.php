<?php
//加载配置信息
require_once(dirname(__FILE__) . '/../../config/config.php');
class taskmgr_rpc_sign{

    /**
     *
     * 生成签名算法函数
     * @param array $params
     */
    static public function gen_sign($params){
        return strtoupper(md5(strtoupper(md5(self::assemble($params))).REQ_TOKEN));
    }

	/**
     *
     * 签名参数组合函数
     * @param array $params
     */
    static private function assemble($params)
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
    }
}