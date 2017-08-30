<?php
define('COLLECTION_URL','http://openapi.ishopex.cn/api/dcinput/data/insert');
define('CUSTOMER_URL','http://openapi.ishopex.cn/api/dccrm/customer/queryid_ensure');
/**
* collection 数据请求类
* @author chenjun 2013.04.15
* @copyright shopex.cn
*/
class ome_collection_request extends ome_collection_rpc{
    public $beginTime;
    public $endTime;
    public $serverName;

    /**
    * 发起
    * @access public
    * @return result
    */
    public function request($serverName = ''){
        if($serverName == '') return false;
        $result = array();
        $lastRequestTime = '';
        kernel::single('base_kvstore_filesystem','ome')->fetch('request_collection_last_time',$lastRequestTime);
        $this->beginTime = $lastRequestTime ? $lastRequestTime : strtotime(date('Y-m-d',time()-24*60*60));
        $this->endTime = strtotime(date('Y-m-d',time()));
        $this->serverName = $serverName;
        $days = $this->preTime($this->beginTime,$this->endTime);
        if(!$days) return true;

        $email = $this->get_manage_email();
        $customerid = $this->get_customerid($email);

        $aDay = $days-7;
        $startDay = $aDay > 0 ? $aDay : 0;

        for($day=$startDay;$day<$days;$day++){
            $beginTime = $this->beginTime + (3600*24*$day);
            $endTime = strtotime(date('Y-m-d',$beginTime).' 23:59:59');
            #参数
            $key = array(
                'requiredproductid' => 33,
                'requiredcustomerid' => $customerid,
                'requireddate' => $endTime,
            );
            /*------------------订单相关数据------------------*/
            kernel::single('ome_collection_func_order')->beginTime = $beginTime;
            kernel::single('ome_collection_func_order')->endTime = $endTime;
            $orderData = kernel::single('ome_collection_func_order')->getData();
            /*------------------订单相关数据------------------*/
            
    
            foreach($orderData as $shop_id => $data){
                $key['shop_id'] = $shop_id;
                $queryParams = array(
                    'json' => json_encode(array('key' => $key,'data' => $data)),
                );
                $sign = $this->sign(COLLECTION_URL,$queryParams);
                $apiurl = COLLECTION_URL.'?'.$sign;
                #请求
                $result[] = $this->http($apiurl,5,$queryParams);
            }
        }
        kernel::single('base_kvstore_filesystem','ome')->store('request_collection_last_time',$this->endTime);
        return $result;
    }

    /**
    * 检查上次同步时间 与这次 同步时间 是否只相隔一天
    * 每相隔一天发起一次同步
    * @params $beginTime 上次同步时间
    * @params $endTime 当前时间
    * @return number
    */
    public function preTime($beginTime,$endTime){
      $count = ($endTime - $beginTime) / (60*60*24);
      if ($count == 0) return false;
      return $count;
    }

    /**
    * 获取customerid
    * 首次获取发起接口 获取之后缓存到本地
    * @params $shopex_id 
    * @return array
    */
    public function get_customerid($email = ''){
        $customerid = '';
        $customer_info = '';
        kernel::single('base_kvstore_filesystem','ome')->fetch('request_collection_customer_info',$customer_info);
        if(!$customer_info[$email]){
            $params = array(
                'customerinfo' => '{"contact_email":"'.$email.'"}',
                'contact_email' => $email,
            );
            $sign = $this->sign(CUSTOMER_URL,$params);
            $apiurl = CUSTOMER_URL.'?'.$sign;
            $result = $this->http($apiurl,5,$params);
            $customerid = $result['result']['customerinfo_id'];
            $customer_info[$email] = $customerid;
            kernel::single('base_kvstore_filesystem','ome')->store('request_collection_customer_info',$customer_info);
        }else{
            $customerid = $customer_info[$email];
        }
        return $customerid;
    }

    /**
    * manage平台获取email客户信息
    * 首次获取发起接口 获取之后缓存到本地
    * @return string
    */
    public function get_manage_email(){
        $email = '';
        $manage_info = '';
        kernel::single('base_kvstore_filesystem','ome')->fetch('request_collection_manage_info',$manage_info);
        if(!$manage_info){
            include_once ROOT_DIR."/config/saasapi.php";
            $api = new SaasOpenClient();
            define('SASS_APP_KEY', 'taoguan');
            define('SAAS_SECRE_KEY', '49F4589687E79D815339B13A73E5FBB4');
            $api->appkey = SASS_APP_KEY;
            $api->secretKey = SAAS_SECRE_KEY;
            $api->format = 'json';
            $params = array('server_name' => $this->serverName);
            $result = $api->execute('host.getinfo_byservername', $params);
            unset($api);
            if ($result->success=='true') {
                $email = $result->data->username;
            }
            kernel::single('base_kvstore_filesystem','ome')->store('request_collection_manage_info',$email);
        }else{
            $email = $manage_info;
        }
        return $email;
    }
}