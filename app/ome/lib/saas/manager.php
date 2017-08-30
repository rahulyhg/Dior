<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_saas_manager extends ome_saas_request {
    
    /*api请求host地址*/
    const HOST = 'http://api.saas.taoex.com/';

    /*api请求host地址的具体路径*/
    const INFO_API = 'api.php';

    /*api请求方法名*/
    const INFO_METHOD = 'host.getinfo_byservername';
    
    private $info = null;
    
    public function __construct(ome_saas_site &$site) {
        $this->site = $site;
    }
    
    /**
     * @获取服务到期的剩余天数
     * @access public
     * @param void
     * @return int
     */
    public function getValidityDate() {
        $this->initInfo();
        $tmp_days = $this->site->getCycleEnd();
        if($tmp_days<= 0){
                return false;
        }
        $end = strtotime($tmp_days);
        $days = $end - time();
        $days = ceil($days/86400);
        
        return $days;
    }
    
    /**
     * @获取服务基本信息
     * @access public
     * @param void
     * @return int
     */
    public function getInfo() {
        $this->initInfo();
        
        return $this->site->getInfo();
    }
    
    /**
     * @具体根据应用访问域名获取主机资源信息的方法
     * @access public
     * @param void
     * @return void
     */
    public function initInfo() {
        if($this->info !== null) {
            return $this->info;
        }
        
        $sysParams ['app_key'] = $this->site->getKey();
        $sysParams ['format'] = $this->site->getFormat();
        $sysParams ['method'] = self::INFO_METHOD;
        $sysParams ['server_name'] = $this->site->getDomain();
        
        $sysParams ['sign'] = $this->generateSign ($sysParams);
        
        $requestUrl = self::HOST . self::INFO_API . "?";
        foreach ( $sysParams as $sysParamKey => $sysParamValue ) {
            $requestUrl .= "$sysParamKey=" . urlencode ( $sysParamValue ) . "&";
        }
        
        $requestUrl = substr ( $requestUrl, 0, - 1 );
        
        try {
            $resp = $this->curl ( $requestUrl );
        } catch ( Exception $e ) {
            return false;
        }
        
        $respWellFormed = false;
        if ("json" == $this->site->getFormat()) {
            $respObject = json_decode ( $resp );
            if (null !== $respObject) {
                $respWellFormed = true;
                foreach ( $respObject as $propKey => $propValue ) {
                    $respObject = $propValue;
                }
            }
        } else if ("xml" == $this->site->getFormat()) {
            $respObject = @simplexml_load_string ( $resp );
            if (false !== $respObject) {
                $respWellFormed = true;
            }
        }
        
        if (false === $respWellFormed) {
            return false;
        }
        
        $this->info = $respObject;
        
        $fc = 'ome_saas_format_'. $this->site->getFormat();
        $format = new $fc($respObject);
        
        $this->site->setInfo($format);
        
        return $respObject;
    }

}