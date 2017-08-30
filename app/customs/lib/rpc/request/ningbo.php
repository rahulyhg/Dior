<?php
/**
 +----------------------------------------------------------
 * [宁波]跨境申报接口
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2015-04-18 $
 * [Ecos!] (C)2003-2015 Shopex Inc.
 +----------------------------------------------------------
 */
class customs_rpc_request_ningbo extends customs_rpc_abstract
{
    /**
     * 绑定接口
     */
    public function bind($shop_id)
    {
        #绑定电子口岸[店铺结点]
        if ($this->getNode($shop_id))
        {
            $shop_name      = self::$node[$shop_id]['OrgName'];
            $user_id        = self::$node[$shop_id]['user_id'];
            $user_secret    = self::$node[$shop_id]['user_secret'];
        }
        else
        {
            return false;
        }
        
        $params = array(
                'app' => 'app.applyNodeBind',
                'node_id' => base_shopnode::node_id('ome'),
                'from_certi_id' => base_certificate::certi_id(),
                'callback' => kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>$this->node_type)),
                'sess_callback' => urlencode(kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>'ems'))),
                'api_url' => kernel::base_url(1).kernel::url_prefix().'/api',
                
                'node_type' => $this->node_type,
                'to_node' => $this->to_node,
                'shop_name' => $shop_name,
                
                'user_id' => $user_id,#测试账号:iloveshopex
                'user_secret' => $user_secret,#测试 密码:85196319-0dec-4f48-b0c6-ed86fbf99781
        );
        
        $token = base_certificate::token();
        $params['certi_ac'] = $this->genSign($params, $token);
        
        //$api_url = 'http://sws.ex-sandbox.com/api.php';#沙箱[测试]
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';#矩阵[正式]
        
        $headers = array('Connection' => 5);
        
        #矩阵日志
        $msg        = '请求中';
        $writelog   = array(
                        'log_title' => '绑定电子口岸',//任务名称
                        'original_bn' => self::$node[$shop_id]['CustomsCode'],//企业代码
                     );
        $log_id   = $this->writeApiLog($writelog, 'app.applyNodeBind', $params, $msg);
        
        #Api
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(5)->post($api_url, $params, $headers);
        $response = json_decode($response,true);
        
        if($response['res']=='succ' || $response['msg']['errorDescription'] == '绑定关系已存在,不需要重复绑定')
        {
            #[更新]矩阵日志
            if($log_id)
            {
                $uplog_data    = array('status'=>'success', 'msg_id'=>$response['msg_id'], 'msg'=>$response['msg']);//成功
                self::$apilogModel->update($uplog_data, array('log_id'=>$log_id));
            }
            
            return true;
        }
        else 
        {
            #[更新]矩阵日志
            if($log_id)
            {
                $uplog_data    = array('status'=>'fail', 'msg_id'=>$response['msg_id'], 'msg'=>$response['msg']);//失败
                self::$apilogModel->update($uplog_data, array('log_id'=>$log_id));
            }
            
            return false;
        }
    }
    
    /**
     * 解除绑定接口
     */
    public function unbind($shop_id)
    {
        #绑定电子口岸[店铺结点]
        if ($this->getNode($shop_id))
        {
            $shop_name      = self::$node[$shop_id]['OrgName'];
            $user_id        = self::$node[$shop_id]['user_id'];
            $user_secret    = self::$node[$shop_id]['user_secret'];
        }
        else
        {
            return false;
        }
        
        $params = array(
                'app' => 'app.changeBindRelStatus',
                'from_node' => base_shopnode::node_id('ome'),
                
                'to_node' => $this->to_node,
                'status' => 'del',
                'reason' => '解除绑定关系',
                'sess_id' => '',
                'op_id' => '',
                'op_user' => '',
        );
        
        $token = base_certificate::token();
        $params['certi_ac'] = $this->genSign($params, $token);
        
        //$api_url = 'http://sws.ex-sandbox.com/api.php';#沙箱[测试]
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';#矩阵[正式]
        
        $headers = array('Connection' => 5);
        
        #矩阵日志
        $msg        = '请求中';
        $writelog   = array(
                        'log_title' => '解绑电子口岸',//任务名称
                        'original_bn' => self::$node[$shop_id]['CustomsCode'],//企业代码
                    );
        $log_id   = $this->writeApiLog($writelog, 'app.changeBindRelStatus', $params, $msg);
        
        #Api
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(5)->post($api_url, $params, $headers);
        $response = json_decode($response,true);
        
        if($response['res'] == 'succ')
        {
            #[更新]矩阵日志
            if($log_id)
            {
                $uplog_data    = array('status'=>'success', 'msg_id'=>$response['msg_id'], 'msg'=>$response['msg']);//成功
                self::$apilogModel->update($uplog_data, array('log_id'=>$log_id));
            }
            
            return true;#$response['msg'] => SUCCESS_NODE_RELATION_UNBINDED
        }
        else 
        {
            #[更新]矩阵日志
            if($log_id)
            {
                $uplog_data    = array('status'=>'fail', 'msg_id'=>$response['msg_id'], 'msg'=>$response['msg']);//失败
                self::$apilogModel->update($uplog_data, array('log_id'=>$log_id));
            }
            
            return false;
        }
    }
}
