<?php
/**
* 短信模板请求
*/
class taoexlib_request_sms{
    public static $serverTimestamp = null;
    const VERSION = '1.0';
    const SERVICE_URL = '/sms-tpl/';
    const API_URL = 'http://webapi.sms.shopex.cn/';
    public static $localTimestamp = null;
    public static $writeLog = true;//开启日志
    /**
    * 根据类型返回请求URL
    *
    */
    public function _sms_templateUrl($type){
        $api_name = '';
        $api_url = '';
        switch ($type){
            case 'register':
                $api_name = 'template/register';
            break;
            case 'list':
                $api_name = 'template/list';
            break;
            case 'update':
                $api_name = 'template/update';
            break;
            case 'sendByTmpl':
                $api_name = 'sendByTmpl';
            break;
           
        }
        if ($api_name)
            $api_url = self::SERVICE_URL.$api_name;
        
        return $api_url;

    }

    public function _sms_templateParams($type,$sms_data){
        $params = array();
        base_kvstore::instance('taoexlib')->fetch('account', $account);
        if (!unserialize($account)) {
            return false;
        }
        $account = unserialize($account);
        $keys = $this->_keys();
        $keys = implode(',',$keys);
        switch($type){
            case 'register':
                $params = array(
                    'name'      =>  $sms_data['title'],
                    'entid'     =>  $account['entid'],
                    'product'   =>  APP_SOURCE,
                    'content'   =>  $this->_format_content($sms_data['content']),
                    'keys'      =>  $keys,
                    'tags'=>'',
                    'callback'=> kernel::openapi_url('openapi.taoexlib.sms','sms_callback'),
                );
                
            break;
            case 'list':
                $params = array(
                    'entid'     =>  $account['entid'],
                    'product'   =>  APP_SOURCE,
                    'tag'  =>'',
                    'offset'=>'',
                    'limit'=>'100',
                );
            break;
            case 'update':
                $params = array(
                    'tplid'     =>  $sms_data['tplid'],
                    'entid'     =>  $account['entid'],
                    'product'   =>  APP_SOURCE,
                    'content'   =>  $this->_format_content($sms_data['content']),
                    'keys'      =>  $keys,
                );
            break;
            case 'sendByTmpl':
                $params = array(
                    'tplid'     =>  $sms_data['tplid'],
                    'product'   =>  APP_SOURCE,
                    'phones'    =>  $sms_data['phones'],
                    'replace'   =>  $sms_data['replace'],
                    'timestamp' =>  self::get_server_time(),
                    'license'   =>  base_certificate::get('certificate_id') ? base_certificate::get('certificate_id') : 1,
                    'entid'     =>  $account['entid'],
                    'entpwd'    =>  md5($account['password'] . 'ShopEXUser'),
                    'use_reply'=>'',
                    'use_backlist'=>'',
                );
            break;
        }
        
        return $params;
    }

    /**
    * 模板请求
    */
    public function sms_request($type,$method,$data){
        
        switch ($type){
            case 'register':
                $result = $this->register_request($type,$method,$data);
            break;
            case 'list':
                $result = $this->list_request($type,$method,$data);
            break;
            case 'update':
                $result = $this->update_request($type,$method,$data);
            break;
            case 'sendByTmpl':
                $result = $this->sendByTmpl_request($type,$method,$data);
            break;
           
        }
        return $result;
    }
    /**
    *注册模板
    */
    public function register_request($type,$method,$data){
        $params     = $this->_sms_templateParams($type,$data);
        $api_url    = $this->_sms_templateUrl($type);
        $result = $this->_request($api_url,$method,$params);
        
        $result = json_decode($result,1);
        return $result;
    }

    /**
    * 模板列表请求
    */
    public function list_request($type,$method,$data){
        $oSms_sample = app::get('taoexlib')->model('sms_sample');
        $params     = $this->_sms_templateParams($type,$data);
        $api_url    = $this->_sms_templateUrl($type);
        $result = $this->_request($api_url,$method,$params);
        $result = json_decode($result,1);
        if ($result['res'] == 'succ') {
            $result = $result['data'];
            foreach ($result as $re ) {
                $sqlstr = array();
                if (in_array($re['approved'],array('0','1'))) {
                    
                    if ($re['approved']=='0') {
                        $approved = '2';
                        $reason = $re['reason'];
                        $sqlstr[]="sync_reason='".$reason."'";
                    }else if($re['approved']=='1'){
                        $approved = '1';
                    }
                    $isapproved = $data['isapproved'];
                    if ($isapproved == 'true') {
                        $sqlstr[]=',`status`=\'1\'';
                    }
                    $approved_at = $re['approved_at'];
                    $sqlstr[]="approved='".$approved."',approvedtime=".$approved_at;
                    $tplid = $re['tplid'];
                    $name = $re['name'];
                    if ($sqlstr) {
                        $sqlstr = implode(',',$sqlstr);
                        $oSms_sample->db->exec("UPDATE sdb_taoexlib_sms_sample_items SET ".$sqlstr." WHERE tplid='".$tplid."'");
                        $oSms_sample->db->exec("UPDATE sdb_taoexlib_sms_sample SET approved='".$approved."' WHERE tplid='".$tplid."'");
                    }
               }
                
                    //其它更新为不启用
            }
        }
        return true;
    }

    /**
    * 更新短信模板
    *
    */
    public function update_request($type,$method,$data){
        $oSms_sample = app::get('taoexlib')->model('sms_sample');
        $params     = $this->_sms_templateParams($type,$data);
        $api_url    = $this->_sms_templateUrl($type);
        $result = $this->_request($api_url,$method,$params);
        $result = json_decode($result,1);
        $id = $data['id'];
        $iid = $data['iid'];
        $sync_status = 'true';
        if ($result['res']=='fail') {
            $sync_status = 'fail';
        }
        $oSms_sample->db->exec("UPDATE sdb_taoexlib_sms_sample_items SET sync_status='".$sync_status."' WHERE id=".$id." AND iid=".$iid);

        return true;
    }

    /**
    * 发送短信模板提醒
    */
    public function sendByTmpl_request($type,$method,$data){
        $params     = $this->_sms_templateParams($type,$data);
        $params['replace'] = json_encode($params['replace']);
        $api_url    = $this->_sms_templateUrl($type);
        $result = $this->_request($api_url,$method,$params);
        return $result;
    }

    public function _request($api_url,$method,$params){
        $url = 'http://openapi.ishopex.cn:80/api';
        $key = '7F97Z0';
        $secret = '76MASB5G3X3A199LAQOV';
        $http = new taoexlib_request_client($url, $key, $secret);
        if ($method == 'post'){
            $result = $http->post($api_url,$params);
        }else{
            $params = http_build_query($params);
            $result = $http->get($api_url.'?'.$params);
        }

       return $result ;
    }

    public static function get_server_time() {
        if (null === self::$serverTimestamp || null === self::$localTimestamp) {
            $param = array(
                'certi_app' => 'sms.servertime',
                'version' => self::VERSION,
                'format' => 'json',
            );
            $param['certi_ac'] = self::make_shopex_ac($param, 'SMS_TIME');
            $http = new base_httpclient;
            $result = $http->post(self::API_URL, $param);
            $result = json_decode($result);
            self::$serverTimestamp = ('succ' == $result->res) ? $result->info : 0;
            self::$localTimestamp = time();
            return self::$serverTimestamp;
        }else {
            return self::$serverTimestamp + time() - self::$localTimestamp;
        }
    }

    public static function make_shopex_ac($arr, $token) {
        $temp_arr = $arr;
        ksort($temp_arr);
        $str = '';
        foreach ($temp_arr as $key => $value) {
            if ($key != 'certi_ac') {
                $str .= $value;
            }
         }
        return md5($str . md5($token));
    }
    /**
     *格式化内容
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function _format_content($content)
    {
        $find = array('{会员名}','{收货人}','{店铺名称}','{物流公司}','{物流单号}','{发货时间}','{配送费用}','{订单号}','{订单金额}','{付款金额}','{订单优惠}','{发货单号}','{收货人手机号}','{订单时间}','{短信签名}');
        $replace = $this->_keys_rp();
        
        $messcontent = str_replace($find,$replace,$content);
        return $messcontent;
    }

    /**
    * 替换键值
    *
    */
    public function _keys_rp(){
        $keys = array('{uname}','{ship_name}','{shopname}','{logi_name}','{logi_no}','{delivery_time}','{logi_actual}','{orderstr}','{total_amount}','{payed}','{cheap}','{delivery_bn}','{ship_mobile}','{create_time}','{msgsign}');
        return $keys;
    }

    /**
    * 原始替换键值
    *
    */
    public function _keys(){
        $keys = array('uname','ship_name','shopname','logi_name','logi_no','delivery_time','logi_actual','orderstr','total_amount','payed','cheap','delivery_bn','ship_mobile','create_time','msgsign');
        return $keys;
    }

    /**
     * 验签注册
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function newoauth_request($data)
    {
        $name = $data['sms_sign'];
        $sms_signObj = app::get('taoexlib')->model('sms_sign');
        $sms_sign = $sms_signObj->dump(array('name'=>$name),'*');

        if (!$sms_sign || $sms_sign['extend_no']=='') {
            base_kvstore::instance('taoexlib')->fetch('account', $account);
            if (!unserialize($account)) {
                return false;
            }
            $account = unserialize($account);
            $url = 'https://openapi.shopex.cn:80/';
            $key = 'n4v7d32k';
            $secret = '6kcft2jbr5c7xiyoo7mz';
            $http = new taoexlib_request_client($url, $key, $secret);
            $http->sign_params_in_url=false;
            $params = array(
                'shopexid'     =>  $account['entid'],
                'passwd'    =>  md5($account['password'] . 'ShopEXUser'),
                'content'=>$name,
            );
            $api_url = '/api/addcontent/new';
            $result = $http->post($api_url,$params);
            $result = json_decode($result,1);
            
            if ($result['res']) {
                
                $extend_no = $result['data']['extend_no'];
                $sign_data = array(
                    'name'  =>$name,
                    'extend_no'=>$extend_no,
                );
                if ($sms_sign && $extend_no) {
                    
                    $sms_signObj->db->exec("UPDATE sdb_taoexlib_sms_sign SET extend_no='".$extend_no."' WHERE s_id=".$sms_sign['s_id']);
                }else{
                    $sms_signObj->save($sign_data);
                }
            }
            return $result;  
        }
        

    }
}
?>