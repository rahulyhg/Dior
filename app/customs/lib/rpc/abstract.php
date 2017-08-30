<?php
/**
 +----------------------------------------------------------
 * 跨境申报[接口请求]
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2015-04-18 $
 * [Ecos!] (C)2003-2015 Shopex Inc.
 +----------------------------------------------------------
 */
abstract class customs_rpc_abstract
{
    public static $apilogModel = null;
    public static $node = array();
    
    public $node_type = 'kjb2c';//结点类型
    public $to_node   = '1183376836';//结点编号
    public $shop_name = '跨境申报';//店铺名称
    public $customs   = '3105';//关区代码(保税备货：3105, 保税集货：3115, 一般进口：3109)
    
    public $user_id      = '';#测试账号:iloveshopex
    public $user_secret  = '';#测试 密码:85196319-0dec-4f48-b0c6-ed86fbf99781
    
    /**
     * RPC开始请求
     * 业务层数据过滤后，开始向上级框架层发起
     * @access public
     * @param string $method RPC远程服务接口名称
     * @param array $params 业务参数
     * @param string $shop_id 店铺ID
     * @param array $writelog 日志参数
     * @param int $time_out 发起超时时间（秒）
     * @return RPC响应结果
     */
    public function request($method, $params, $callback=array(), $shop_id, $writelog=array(), $time_out = 15)
    {
        $result    = false;
        
        /*#过滤参数中的空值
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                unset($params[$key]);
            }
        }*/
        
        #绑定电子口岸[店铺结点]
        if ($this->getNode($shop_id))
        {
            $params['CreateTime']    = date('Y-m-d H:i:s', time());#创建时间(yyyy-MM-dd HH:mm:ss)
            $params                  = array_merge(self::$node[$shop_id], $params);
        }
        else
        {
            return false;
        }
        
        #矩阵日志
        $msg      = '请求中';
        $log_id   = $this->writeApiLog($writelog, $method, $params, $msg);
        $params['log_id']    = $log_id;
        
        #发送请求
        $result    = $this->rpc_request($method, $params, $callback, $time_out);
        return $result;
    }
    
    /**
     * RPC请求
     * 暂时只支持同步接口，若要支持异步接口需要改进此方法
     * @access public
     * @param string $method RPC远程服务接口名称
     * @param array $params 业务参数
     * @param int $time_out 发起超时时间（秒）
     * @return RPC响应结果
     */
    public function rpc_request($method, $params, $callback, $time_out = 15)
    {
        if (self::$apilogModel === null) {
            self::$apilogModel = app::get('ome')->model('api_log');
        }
        $log_id    = $params['log_id'];
        unset($params['log_id']);
        
        #request
        $result    = app::get('ome')->matrix()->set_realtime(true)->set_timeout($time_out)->call($method, $params);
        
        #更新日志
        $uplog_data    = array();
        if($result->rsp == 'succ')
        {
            $uplog_data    = array('status'=>'success', 'msg_id'=>$result->msg_id, 'msg'=>'');//成功
        }
        else
        {
            $uplog_data    = array('status'=>'fail', 'msg_id'=>$result->msg_id, 'msg'=>$result->err_msg);//失败
        }
        if(!empty($log_id))
        {
            self::$apilogModel->update($uplog_data, array('log_id'=>$log_id));
        }
        
        return $result;
    }
    
    /**
     * RPC异步返回数据接收
     * @access public
     * @param object $result 经由框架层处理后的同步结果数据
     * @return 返回业务处理结果
     */
    public function callback($result)
    {
        #error_log(var_export($result, true) . "\n====", 3, 'F:/callback_'.time().'.php');
        return true;
    }
    
    #绑定店铺结点
    public function getNode($shop_id)
    {
        if(empty($shop_id))
        {
            return false;
        }
        
        if (!self::$node[$shop_id])
        {
            $this->setNode($shop_id);
        }
        return self::$node[$shop_id];
    }
    
    /**
     * 设置结点信息
     * @param String $shop_id 店铺ID
     */
    public function setNode($shop_id)
    {
        if(empty($shop_id))
        {
            return false;
        }
        
        $node    = $this->getBindNode();
        
        #电子口岸信息
        $oSetting    = &app::get('customs')->model('setting');
        $company     = $oSetting->dump(array('sid' => $shop_id), '*');
        
        #店铺节点、类型
        if(empty($company['company_code']) || empty($company['company_name']) || empty($node['node_type']) || empty($node['to_node_id']))
        {
            return false;
        }
        
        $node['CustomsCode']    = $company['company_code'];
        $node['OrgName']        = $company['company_name'];
        
        $node['company_id']     = $company['company_id'];
        $node['custom_type']    = $company['custom_type'];
        #$node['shop_id']        = $company['shop_id'];
        $node['user_id']        = $company['username'];
        $node['user_secret']    = $company['password'];
        
        if ($node) {
            self::$node[$shop_id]    = $node;
        }
    }
    
    /**
     * 获取绑定结点信息
     */
    public function getBindNode()
    {
        return array(
                'to_node_id' => $this->to_node,
                'node_type' => $this->node_type,
                'customs' => $this->customs,
                #'user_id' => $this->user_id,
                #'user_secret' => $this->user_secret,
        );
    }
    
    /**
     * 写API日志
     */
    public function writeApiLog($writelog, $method, $params, $msg)
    {
        if(empty($writelog))
        {
            return '';
        }
        
        if (self::$apilogModel === null) {
            self::$apilogModel = app::get('ome')->model('api_log');
        }
        
        unset($params['user_id'], $params['user_secret']);#过滤敏感参数
        
        $log_id        = $this->getGenId();//日志id
        $log_title     = $writelog['log_title'];//任务名称
        $log_class     = 'customs_rpc_request_ningbo';//api请求方法的类
        $api_method    = 'request';//api请求方法的类函数
        
        $api_params    = array($method, $params);//api请求方法的参数集合
        $log_memo      = '';//备注
        $api_type     = 'request';//请求OR响应
        $log_status   = 'running';//状态
        $log_msg      = $msg;//返回信息
        
        $log_addon    = '';//marking_value或marking_type的值
        $log_type     = ($writelog['log_type'] ? $writelog['log_type'] : 'customs');//日志类型
        $original_bn  = $writelog['original_bn'];//单据号
        
        self::$apilogModel->write_log($log_id, $log_title, $log_class, $api_method, $api_params, $log_memo,
                $api_type, $log_status, $log_msg, $log_addon, $log_type, $original_bn);
        
        return $log_id;
    }
    
    /**
     * 更新API请求日志
     * @param unknown_type $log_id
     * @param unknown_type $msg
     * @param unknown_type $status
     * @param unknown_type $params
     * @param unknown_type $addon
     */
    public function updateLog($log_id, $msg = NULL, $status = NULL, $params = NULL, $addon=NULL)
    {
        if (self::$apilogModel === null) {
            self::$apilogModel = app::get('ome')->model('api_log');
        }
        
        return self::$apilogModel->update_log($log_id, $msg, $status, $params, $addon);
    }
    
    /**
     * 设置日志编号
     * Enter description here ...
     */
    public function setGenId() {
        if (self::$apilogModel === null) {
            self::$apilogModel = app::get('ome')->model('api_log');
        }
        $this->log_id = self::$apilogModel->gen_id();
    }
    
    /**
     * 获取日志编号
     */
    public function getGenId() {
        if ($this->log_id == '') {
            $this->setGenId();
        }
        return $this->log_id;
    }
    
    /**
     * 清空日志编号
     */
    public function emptyGenId() {
        if ($this->log_id != '') {
            $this->log_id = '';
        }
    }
    
    /**
     * 获取签名串
     * @access public
     * @param array $params
     * @param string $token
     * @return string 签名串
     */
    public function genSign($params, $token)
    {
        ksort($params);
        $str = '';
        foreach ($params as $key =>$value) {
            if ($key != 'certi_ac') {
                $str .= $value;
            }
        }
        $signString = md5($str.$token);
        return $signString;
    }
}