<?php
#华强宝物流跟踪
class ome_hqepay{
    function detail_delivery($logi_no = false,$order_bn = false){
        #获取物流数据
        $data = $this->getDeliveryInfo($logi_no);
        $delivery_html = null;
        $rpc_data['order_bn'] = $order_bn;
        $rpc_data['logi_code'] = $data['type'];#物流编码
        $rpc_data['company_name'] = $data['name'];
        $rpc_data['logi_no'] = $data['logi_no'];
    
        $rpc_result = $this->get_dly_info($rpc_data);
        if($rpc_result['rsp'] == 'succ'){
            $count = count( $rpc_result['data']);
            $max = $count - 1;#最新那条物流记录
            $html = "<ul style='margin-top:10px;'>";
            foreach($rpc_result['data'] as $key=>$val){
                #这时间是最新的
                if($max == $key ){
                    $html .= "<li style='line-height:15px;border-bottom:1px dotted  #ddd;'><font  style='font-size:13px;COLOR: red'>".$val['AcceptTime']."".$val['AcceptStation']."</font><li/>";
                }else{
                    $html .= "<li style='line-height:15px;border-bottom:1px dotted  #ddd;'>"."<em style='font-size:13px;COLOR: black'>".$val['AcceptTime']."</em>&nbsp;&nbsp;".$val['AcceptStation']." <li/>";
                }
            }
            $html .='</ul>';
        }else{
            $html = "<ul>";
            if($rpc_result['err_msg'] == "'HTTP Error 500: Internal Server Error'"){
                $html .= "<li style='line-height:15px;margin-top:10px;border-bottom:1px dotted  #ddd;'><font color='red'>此订单可能缺少物流公司或运单号</font><li/>";
            }else{
                $html .= "<li style='line-height:15px;margin-top:10px;border-bottom:1px dotted  #ddd;'><font color='red'>".$rpc_result['err_msg']."</font><li/>";
            }
        }
        $html .='</ul>';
        $html .= "<div  style='font-weight:700;font-color:#000000;margin-bottom:10px;'>华强宝提供数据支持(<font>以上信息由物流公司提供，如无跟踪信息或有疑问，请咨询对应物流公司</font>)<div>";
        return $html;
    }
    function getDeliveryInfo($logi_no = false){
        $logi_no = "'".$logi_no."'";
        #主单
        $delivery_sql = "select
                            d.logi_no,d.logi_name name,c.type from sdb_ome_delivery  d
                          left join sdb_ome_dly_corp  c on d.logi_id= c.corp_id
                          where d.logi_no=".$logi_no;
        #子单
        $bill_sql = "select
                d.logi_name name ,b.logi_no,c.type
                from sdb_ome_delivery d
                left join sdb_ome_delivery_bill b on d.delivery_id=b.delivery_id
                left join sdb_ome_dly_corp  c on d.logi_id= c.corp_id
                where b.logi_no=".$logi_no;
        #先找主单
        $rs = kernel::database()->selectrow($delivery_sql);
        #主单没有，再查子单
        if(empty($rs)){
            $rs = kernel::database()->selectrow($bill_sql);
        }
        return $rs;
    }    
    #ERP与华强宝快递对接，查看物流状态
    function get_dly_info($rpc_data = false){
        $rs = array();
        $data = $this->rpc_logistics_hqepay($rpc_data);    
        if($data['rsp'] == 'succ'){
            #倒叙排序
            krsort($data['data']);
        }
        return $data;
    }    
    
    public function rpc_logistics_hqepay($rpc_data){
        #检测是否已经绑定华强宝物流
        base_kvstore::instance('ome/bind/hqepay')->fetch('ome_bind_hqepay', $is_ome_bind_hqepay);
        if(!$is_ome_bind_hqepay){
            $rs = $this->bind();
            if(!$rs){
                $return_data['rsp'] = 'fail';
                $return_data['err_msg'] = '没有绑定!';
                return  $return_data;
            }
        }
        $Ofunc = kernel::single('ome_rpc_func');
        $app_xml = $Ofunc->app_xml();
        $params['to_node_id'] = '1227722633';#写死node_id
        
       
         $params['tid'] = $rpc_data['order_bn']; #订单号
        $params['company_code'] = $rpc_data['logi_code'];
        $params['company_name'] = $rpc_data['company_name'];
        $params['logistic_code'] = $rpc_data['logi_no'];  
        

        
        
        $time_out = 5;
        $res = &app::get('ome')->matrix()->set_realtime(true)->set_timeout($time_out)->call('logistics.trace.detail.get', $params);
        $return_data = null;
        if($res->rsp == 'fail'){
            $return_data['rsp'] = 'fail';
            $return_data['err_msg'] = $res->err_msg;
        }else{
            $return_data['rsp'] = 'succ';
            $_data = json_decode($res->data,true);
            $return_data['data'] =  $_data['Traces'];           
        }
        return $return_data;
    }
    #绑定华强宝物流
    public function bind() {
        $token = base_certificate::token();
        $params = array(
                'app' => 'app.applyNodeBind',
                'node_id' => base_shopnode::node_id('ome'),
                'from_certi_id' => base_certificate::certi_id(),
                'callback' => '',
                'sess_callback' => '',
                'api_url' => kernel::base_url(1).kernel::url_prefix().'/api',
                'node_type' => 'hqepay',
                'to_node' => '1227722633',#写死的
                'shop_name' => '物流跟踪',
                "api_key"=> "1236217",#写死的
                "api_secret"=> "cf98e49d-9ebe-43cb-a690-ad96295b3457",#写死的
               // "api_url"=>"http://port.hqepay.com/Ebusiness/EbusinessOrderHandle.aspx", #写死的
        );
        
        $params['certi_ac']=$this->genSign($params,$token);
        //$api_url = 'http://sws.ex-sandbox.com/api.php';
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';
        $headers = array('Connection' => 5);
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->set_timeout(10)->post($api_url, $params, $headers);
        $response = json_decode($response,true);
        if($response['res'] == 'succ' || $response['msg']['errorDescription'] == '绑定关系已存在,不需要重复绑定') {
            base_kvstore::instance('ome/bind/hqepay')->store('ome_bind_hqepay', true);
            return true;
        }
        return false;
    }   
    public function genSign($params, $token) {
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

