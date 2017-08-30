<?php

/**
 * 请求saas平台
 *
 * @package config/
 * @author chenping@shopex.cn
 **/
class taskmgr_saasrequest
{
    private $_is_rds = '1';

    private $_saasapi = null;

    const _SASS_APP_KEY = 'taoguan';

    const _SAAS_SECRE_KEY = '49F4589687E79D815339B13A73E5FBB4';

    public function __construct()
    {
        $this->_saasapi = new taskmgr_saasapi();
    }

    public function setIsRds($is_rds)
    {
        $this->_is_rds = $is_rds;

        return $this;
    }

    /**
     * 从待开通队列中获取订单
     *
     * @param String $code 订单类型
     * @return void
     * @author 
     **/
    public function fetchActionFromQueue($code) {
        
        $this->_saasapi->appkey    = self::_SASS_APP_KEY;
        $this->_saasapi->secretKey = self::_SAAS_SECRE_KEY;
        $this->_saasapi->format    = 'json';

        $params = array('service_code' => $code, 'is_rds' => $this->_is_rds);

        $result = $this->_saasapi->execute('host.get_pending', $params);

        if ($result->success == 'true') {
            if ($result->data == 'QUEUE_END') {
                return null;
            } else {
                return $result->data;
            }
        } else {
            return null;
        }
    }

    /**
     * 获取订单信息
     *
     * @param String $host 域名
     * @return void
     * @author 
     **/
    public function getInfoByHost($host) {

        $this->_saasapi->appkey    = self::_SASS_APP_KEY;
        $this->_saasapi->secretKey = self::_SAAS_SECRE_KEY;
        $this->_saasapi->format    = 'json';

        $params = array('server_name' => $host);
        $result = $this->_saasapi->execute('host.getinfo_byservername', $params);

        if ($result->success == 'true') {
            if ($result->data == 'QUEUE_END') {
                return null;
            } else {
                return $result->data;
            }
        } else {
            return null;
        }
    }

    /**
     * 获取已开通的订单列表
     *
     * @param String $code 订单类型
     * @param String 订单状态
     * @return void
     * @author 
     **/
    public function fetchHostListByCode($code,$status='2') {

        $this->_saasapi->appkey    = self::_SASS_APP_KEY;
        $this->_saasapi->secretKey = self::_SAAS_SECRE_KEY;
        $this->_saasapi->format    = 'json';

        $params = array('service_code' => $code,'status' => $status, 'is_rds' => $this->_is_rds);
        $result = $this->_saasapi->execute('host.getlist', $params);

        if ($result->success == 'true') {
            if ($result->data == 'QUEUE_END') {
                return null;
            } else {
                return $result->data;
            }
        } else {
            return null;
        }
    }


    /**
     * 向SAAS平台推送数据
     *
     * @param Array $saasdata 数据
     * @return void
     * @author 
     **/
    public function storeDataToSaas($saasdata)
    {
        $this->_saasapi->appkey    = self::_SASS_APP_KEY;
        $this->_saasapi->secretKey = self::_SAAS_SECRE_KEY;
        $this->_saasapi->format    = 'json';

        $this->_saasapi->execute('application.storedata',array('service_code'=>'taoex-tg','appdata' => serialize(array($saasdata))));
    }

    public function getSaasHostLists(){
        $products = array('shopex-erp','tp-shopex-erp');
        foreach ($products as $p) {
            $allDomain = $this->fetchHostListByCode($p);
            $allDomain = $allDomain->host_name_list;
            if($allDomain){
                foreach ($allDomain as $key => $domain) {
                    $domain = strtolower($domain);
                    $hosts[] = $domain;
                }
            }
        }
        return $hosts;
    }
}