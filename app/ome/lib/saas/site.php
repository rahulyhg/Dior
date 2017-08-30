<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_saas_site {
    
    private $manager;
    
    private $key = '';
    private $secret = '';
    private $domain = '';
    private $format = 'xml';
    
    private $shopAccount;
    private $contactName;
    private $contactEmail;
    private $contactMobile;
    private $contactQQ;
    private $contactWangwang;
    
    private $info;
    
    public function __construct($manager=null) {
        $this->key = ome_saas_const::KEY;
        $this->secret = ome_saas_const::SECRET;
        $this->format = ome_saas_const::XML;
        
        $this->initDomain();
    }
    
    public function getKey() {
        return $this->key;
    }
    
    public function getSecret() {
        return $this->secret;
    }
    
    public function getDomain() {
        return $this->domain;
    }
    
    public function setKey($key) {
        $this->key = $key;
    }
    
    public function setSecret($secret) {
        $this->secret = $secret;
    }
    
    public function setDomain($domain) {
        $this->domain = $domain;
        
        if(substr($this->domain, 0, 7) === 'http://') {
            $this->domain = substr($this->domain, 7);
        }
        
        $td = explode('.', $this->domain);
    
        if(isset($td[1]) && $td[1]=='tfh') {
            $td[1] = 'tbfh';
            
            $this->domain = implode('.', $td);
        }
    }
    
    public function setContactName($contactName) {
        $this->contactName = $contactName;
    }
    
    public function getContactName() {
        return $this->contactName;
    }
    
    public function setContactEmail($contactEmail) {
        $this->contactEmail = $contactEmail;
    }
    
    public function getContactEmail() {
        return $this->contactEmail;
    }
    
    public function setContactMobile($contactMobile) {
        $this->contactMobile = $contactMobile;
    }
    
    public function getContactMobile() {
        return $this->contactMobile;
    }
    
    public function setContactQQ($contactQQ) {
        $this->contactQQ = $contactQQ;
    }
    
    public function getContactQQ() {
        return $this->contactQQ;
    }
    
    public function setContactWangwang($contactWangwang) {
        $this->contactWangwang = $contactWangwang;
    }
    
    public function getContactWangwang() {
        return $this->contactWangwang;
    }
    
    public function setShopAccount($shopAccount) {
        $this->shopAccount = $shopAccount;
    }
    
    public function getShopAccount() {
        return $this->shopAccount;
    }
    
    public function setManager(& $manager) {
        $this->manager = $manager;
        
        return $this->manager;
    }
    
    public function getManager() {
        if($this->manager === null) {
            $this->setManager ( new ome_saas_manager ( $this ) );
        }
        
        return $this->manager;
    }
    
    public function setFormat($format) {
        $this->format = $format;
    }
    
    public function getFormat() {
        return $this->format;
    }
    
    /**
     * server info
     */
    public function setInfo($info) {
        $this->info = $info;
        
        $this->info->setSite($this);
    }
    
    public function getInfo() {
        return $this->info;
    }
    
    public function __call($name, $argv) {
        if (method_exists ( $this->info, $name )) {
            return call_user_func ( array (
                $this->info, $name
            ), $argv );
        }
    }
    
    private function initDomain() {
        $this->setDomain($_SERVER['HTTP_HOST']);
    }
    
    public function getHostName() {
        return array_shift(explode('.', $this->domain));
    }
    
    public function getServiceCode() {
        $tmp = explode ( '.', $this->getDomain() );
        
        if (isset ( $tmp ['1'] ) && $tmp ['1'] === 'fh') {
            return ome_saas_const::SERVICE_FCFH_CODE;
        } else {
            return ome_saas_const::SERVICE_TAOBAO_CODE;
        }
    }
}