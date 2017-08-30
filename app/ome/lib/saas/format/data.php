<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_saas_format_data {
    
    private $site;
    
    private $hostId;
    private $serviceId;
    private $tenantId;
    private $orderId;
    private $resourceId;
    private $certiId;
    private $nodeId;
    private $hostName;
    private $dbServer;
    private $dbHost;
    private $dbPort;
    private $dbName;
    private $dbUser;
    private $dbPasswd;
    private $status;
    private $sourceType;
    private $cycleStart;
    private $cycleEnd;
    private $createTime;
    private $activeTime;
    private $loginTime;
    private $lastTime;
    private $token;
    private $limitShop = 0;
    
    public function setLimitShop($number) {
        $this->limitShop = $number;
    }
    
    public function getLimitShop() {
        return $this->limitShop;
    }
    
    public function setSite(&$site){
        $this->site = $site;
    }
    
    public function getSite(){
        return $this->site;
    }

    public function getDbHost() {
        return $this->dbHost;
    }

    public function setDbHost($dbHost) {
        $this->dbHost = $dbHost;
    }

	public function getHostId() {
        return $this->hostId;
    }

    public function getServiceId() {
        return $this->serviceId;
    }

    public function getTenantId() {
        return $this->tenantId;
    }

    public function getOrderId() {
        return $this->orderId;
    }

    public function getResourceId() {
        return $this->resourceId;
    }

    public function getCertiId() {
        return $this->certiId;
    }

    public function getNodeId() {
        return $this->nodeId;
    }

    public function getHostName() {
        return $this->hostName;
    }

    public function getDbName() {
        return $this->dbName;
    }

    public function getDbServer() {
        return $this->dbServer;
    }

    public function getDbUser() {
        return $this->dbUser;
    }

    public function getDbPasswd() {
        return $this->dbPasswd;
    }

    public function getDbPort() {
        return $this->dbPort;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getSourceType() {
        return $this->sourceType;
    }

    public function getCycleStart() {
        return $this->cycleStart;
    }

    public function getCycleEnd() {
        return $this->cycleEnd;
    }

    public function getCreateTime($format=null) {
        if(empty($format)) {
            return $this->createTime;
        } else {
            return date($format, $this->createTime);
        }
    }

    public function getActiveTime($format=null) {
        if(empty($format)) {
            return $this->activeTime;
        } else {
            return date($format, $this->activeTime);
        }
    }

    public function getLoginTime() {
        return $this->loginTime;
    }

    public function getLastTime() {
        return $this->lastTime;
    }

    public function setHostId($hostId) {
        $this->hostId = $hostId;
    }

    public function setServiceId($serviceId) {
        $this->serviceId = $serviceId;
    }

    public function setTenantId($tenantId) {
        $this->tenantId = $tenantId;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    public function setResourceId($resourceId) {
        $this->resourceId = $resourceId;
    }

    public function setCertiId($certiId) {
        $this->certiId = $certiId;
    }

    public function setNodeId($nodeId) {
        $this->nodeId = $nodeId;
    }

    public function setHostName($hostName) {
        $this->hostName = $hostName;
    }

    public function setDbName($dbName) {
        $this->dbName = $dbName;
    }

    public function setDbServer($dbServer) {
        $this->dbServer = $dbServer;
    }

    public function setDbUser($dbUser) {
        $this->dbUser = $dbUser;
    }
    
    public function setDbPasswd($dbPassword) {
        $this->dbPasswd = $dbPassword;
    }

    public function setDbPort($dbPort) {
        $this->dbPort = $dbPort;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function setSourceType($sourceType) {
        $this->sourceType = $sourceType;
    }

    public function setCycleStart($cycleStart) {
        $this->cycleStart = $cycleStart;
    }

    public function setCycleEnd($cycleEnd) {
        $this->cycleEnd = $cycleEnd;
    }

    public function setCreateTime($createTime) {
        $this->createTime = $createTime;
    }

    public function setActiveTime($activeTime) {
        $this->activeTime = $activeTime;
    }

    public function setLoginTime($loginTime) {
        $this->loginTime = $loginTime;
    }

    public function setLastTime($lastTime) {
        $this->lastTime = $lastTime;
    }
    
    public function setToken($token) {
        $this->token = $token;
    }
    
    public function getToken() {
        return $this->token;
    }
    
    public function isActive() {
        return $this->status == 'HOST_STATUS_ACTIVE';
    }
    
}