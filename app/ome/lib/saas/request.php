<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_saas_request {
    protected $site;
    
    /**
     * @模拟请求方法
     * @access public
     * @param string $url 请求地址可带参数 array $postFields post方式参数数组
     * @return string
     */
    protected function curl($url, $postFields = null) {
        
        $ch = curl_init ();
        
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_FAILONERROR, false );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        
        if (is_array ( $postFields ) && 0 < count ( $postFields )) {
            $postBodyString = "";
            $postMultipart = false;
            foreach ( $postFields as $k => $v ) {
                if ("@" != substr ( $v, 0, 1 )) {
                    $postBodyString .= "$k=" . urlencode ( $v ) . "&";
                } else {
                    $postMultipart = true;
                }
            }
            
            unset ( $k, $v );
            curl_setopt ( $ch, CURLOPT_POST, true );
            if ($postMultipart) {
                curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );
            } else {
                curl_setopt ( $ch, CURLOPT_POSTFIELDS, substr ( $postBodyString, 0, - 1 ) );
            }
        }
        $reponse = curl_exec ( $ch );
        
        if (curl_errno ( $ch )) {
            throw new Exception ( curl_error ( $ch ), 0 );
        } else {
            $httpStatusCode = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
            if (200 !== $httpStatusCode) {
                throw new Exception ( $reponse, $httpStatusCode );
            }
        }
        curl_close ( $ch );
        
        return $reponse;
    }
    
    /**
     * @请求签名算法
     * @access public
     * @param array $params 签名参数
     * @return string
     */
    protected function generateSign($params) {
        ksort($params);

        $stringToBeSigned = '';
        foreach ($params as $k => $v)
        {
            if(($k != '_') && ($k != 'sign') && ($k != 'app_secret') && "@" != substr($v, 0, 1))
            {
                $stringToBeSigned .= $k . $v;
            }
        }
        
        unset($k, $v);
        $stringToBeSigned .= $this->site->getSecret();
        
        return strtoupper(md5($stringToBeSigned));
    }

}