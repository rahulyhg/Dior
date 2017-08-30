<?php
/**
 +----------------------------------------------------------
 * 跨境申报绑定
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2015-04-18 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class customs_rpc_request_taobao extends customs_rpc_request
{
    public $node_type = 'kjb2c';//结点类型
    public $to_node   = '1183376836';//结点编号
    public $shop_name = '跨境申报';//店铺名称
    
    /**
     * 绑定接口
     */
    public function bind()
    {
        $params = array(
                'app' => 'app.applyNodeBind',
                'node_id' => base_shopnode::node_id('ome'),
                'from_certi_id' => base_certificate::certi_id(),
                'callback' => kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>$this->node_type)),
                'sess_callback' => urlencode(kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>'ems'))),
                'api_url' => kernel::base_url(1).kernel::url_prefix().'/api',
                
                'node_type' => $this->node_type,
                'to_node' => $this->to_node,
                'shop_name' => $this->shop_name,
                
                'user_id' => 'iloveshopex',#写死的
                'user_secret' => '85196319-0dec-4f48-b0c6-ed86fbf99781',#写死的
        );
        $token = base_certificate::token();
        $params['certi_ac'] = $this->genSign($params, $token);
        
        //$api_url = 'http://sws.ex-sandbox.com/api.php';#沙箱测试
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';
        
        $headers = array(
                'Connection' => 5,
        );
        
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(5)->post($api_url, $params, $headers);
        $response = json_decode($response,true);
        
        if($response['res']=='succ' || $response['msg']['errorDescription'] == '绑定关系已存在,不需要重复绑定')
        {
            return true;
        }
        return false;
    }
    
    /**
     * 解除绑定接口
     */
    public function unbind()
    {
        $params = array(
                'app' => 'app.changeBindRelStatus',
                'from_node' => base_shopnode::node_id('ome'),
                
                'to_node' => $this->to_node,
                'status' => 'del',
                'reason' => '重新绑定关系',
                'sess_id' => '',
                'op_id' => '',
                'op_user' => '',
                
                /*
                'from_certi_id' => base_certificate::certi_id(),
                'callback' => kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>$this->node_type)),
                'sess_callback' => urlencode(kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>'ems'))),
                'api_url' => kernel::base_url(1).kernel::url_prefix().'/api',
    
                'node_type' => $this->node_type,
                'to_node' => $this->to_node,
                'shop_name' => $this->shop_name,
    
                'user_id' => 'iloveshopex',#写死的
                'user_secret' => '85196319-0dec-4f48-b0c6-ed86fbf99781',#写死的
                */
        );
        $token = base_certificate::token();
        $params['certi_ac'] = $this->genSign($params, $token);
    
        //$api_url = 'http://sws.ex-sandbox.com/api.php';
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';
    
        $headers = array(
                'Connection' => 5,
        );
        
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(5)->post($api_url, $params, $headers);
        $response = json_decode($response,true);
        
        if($response['res'] == 'succ')
        {
            return true;//$response['msg'] => SUCCESS_NODE_RELATION_UNBINDED
        }
        return false;
    }
}
