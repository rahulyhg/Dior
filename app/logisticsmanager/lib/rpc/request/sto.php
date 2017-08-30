<?php
class logisticsmanager_rpc_request_sto extends logisticsmanager_rpc_abstract{
    /**
     * @var String $node_type 结点类型
     */
    public $node_type = 'sto';
    /**
     * @var String $to_node 结点编号
     */
    public $to_node = '1064384233';
    /**
     * @var String $shop_name 店铺名称
     */
    public $shop_name = '申通官方电子面单';

    /**
     * 绑定接口
     */
    public function bind() {
        $params = array(
            'app' => 'app.applyNodeBind',
            'node_id' => base_shopnode::node_id('ome'),
            'from_certi_id' => base_certificate::certi_id(),
            'callback' => kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>$this->node_type)),
            'sess_callback' => urlencode(kernel::openapi_url('openapi.ome.shop','shop_callback',array('channel_type'=>$this->node_type))),
            'api_url' => kernel::base_url(1).kernel::url_prefix().'/api',
            'node_type' => $this->node_type,
            'to_node' => $this->to_node,
            'shop_name' => $this->shop_name,
        );
        $token = base_certificate::token();
        $params['certi_ac'] = $this->genSign($params,$token);
        //$api_url = 'http://sws.ex-sandbox.com/api.php';
        $api_url = 'http://www.matrix.ecos.shopex.cn/api.php';
        $headers = array(
            'Connection' => 5,
        );
        if ( $this->node_type ) {
            
        }
        $core_http = kernel::single('base_httpclient');

        $response = $core_http->set_timeout(5)->post($api_url, $params, $headers);

        $response = json_decode($response,true);

        $status = false;
        if($response['res']=='succ' || $response['msg']['errorDescription'] == '绑定关系已存在,不需要重复绑定') {
            $status = true;
            //
        }
        return $status;
    }
    /**
    * 获取申通官方电子面单
    *
    * @access public
    * @param string $shop_id 店铺ID
    * @return 接口响应结果
    */
    public function get_waybill_number($data) {
        $params = array(
            'cusname' => $data['cusname'], //客户号
            'cusite' => $data['cusite'], //客户密码
            'cuspwd'=>$data['cuspwd'],
            'businessType' => $data['businessType'], //单据类型
            'len' => $data['billNoAmount'], //单据数量
        );
        $method = 'store.waybill.mailno.get';//
        $this->emptyGenId();
        $writelog = array(
            'log_type' => 'other',
            'log_title' => '获取sto官方电子面单',
            'original_bn' => $data['out_biz_code'],
        );

        $callback = array(
            'class' => get_class($this),
            'method' => 'get_waybill_number_callback',
            'params' => array('out_biz_code' => $data['out_biz_code'],'channel_id'=>$data['channel_id']),
        );

        $result = $this->request($method, $params, $callback, 'sto', $writelog);
        
        return true;
    }

    public function get_waybill_number_callback($result) {

        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();
        $data = $result->get_data();
        $log_id = $callback_params['log_id'];
        $shop_id = $callback_params['shop_id'];
        $ret = $this->callback($result);
        $waybillLogObj = app::get('logisticsmanager')->model('waybill_log');
        if ($status == 'succ' && $data['assignId']){
            $waybillObj = app::get('logisticsmanager')->model('waybill');
            $channelObj = app::get('logisticsmanager')->model('channel');
            $stoObj = kernel::single('logisticsmanager_waybill_sto');
            $logistics_code = $stoObj->getBusinessType($request_params['businessType']);
            
            //获取单号来源信息
            $cFilter = array(
                'channel_type' => 'sto',
                'logistics_code' => $logistics_code,
                'status'=>'true',
            );
            $channel = $channelObj->dump($cFilter);
            $channel_id = $callback_params['channel_id'];
            $data['assignId'] = explode(',',$data['assignId']);
            
            //保存数据
            $insert_sql = array();
            if ($data['assignId']) {
                foreach($data['assignId'] as $val){
                    $waybill_info = $waybillObj->dump(array('waybill_number'=>$val));

                    if (!$waybill_info) {
                        $create_time=time();
                        $insert_sql[]="('$val','$channel_id','$logistics_code','$create_time')";
                    }
                    
                }
                if ($insert_sql) {
                    $sql = "INSERT INTO sdb_logisticsmanager_waybill(waybill_number,channel_id,logistics_code,create_time) VALUES ".implode(',',$insert_sql);
                    $waybillLogObj->db->exec($sql);
                }
            }
            
            $waybillLogObj->update(array('status'=>'success'),array('log_id'=>$callback_params['out_biz_code']));

        } else {
            $waybillLogObj->update(array('status'=>'fail'),array('log_id'=>$callback_params['out_biz_code']));
        }
        $this->emptyGenId();
        return true;
    }

    /**
    * EMS官方电子面单物流回传
    *
    * @access public
    * @param string $shop_id 店铺ID
    * @return 接口响应结果
    */
    public function delivery($data) {
        

        $method = 'store.waybill.data.add';

        $writelog = array(
            'log_type' => 'other',
            'log_title' => 'STO官方电子面单物流回传',
            'original_bn' => $data['billno'],
            
        );

        $callback = array(
//            'class' => get_class($this),
//            'method' => 'sto_delivery_callback',
//            'params' => array('logi_no' => $data['logi_no'],'delivery_id' => $data['delivery_id']),
        );
        $this->emptyGenId();
        $result = $this->request($method, $data, $callback, 'sto', $writelog);
        if (empty($callback) && $result) {
                $result = $this->delivery_process($result, $data);
        }else{
            return false;
        }

        return $result;
    }

    public function sto_delivery_callback($result) {
        
        //更新订单发货成功后的回传时间
        $status = $result->get_status();
        $callback_params = $result->get_callback_params();

        $ret = $this->callback($result);
        $data = $result->get_data();
        
        $logisticsLogObj = app::get('logisticsmanager')->model('logistics_log');
        $waybill_extObj =  &app::get("logisticsmanager")->model("waybill_extend");
        if ($status == 'succ' && $data[0]){
            //插入大头笔表
            
            $waybill_extObj->save_position($data[0]);
            $logisticsLogObj->update(array('status'=>'success'),array('delivery_id'=>$callback_params['delivery_id'],'logi_no'=>$callback_params['logi_no']));
        } else {
            $logisticsLogObj->update(array('status'=>'fail'),array('delivery_id'=>$callback_params['delivery_id'],'logi_no'=>$callback_params['logi_no']));
        }
        $this->emptyGenId();
        return $ret;
    }

    
    /**
     * 同步处理大头笔
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function delivery_process($result, $data)
    {
        $rpc_status = true;
        $status = isset($result['rsp']) ? $result['rsp'] : '';
        $data = empty($result['data']) ? '' : json_decode($result['data'], true);
        $ret = $this->rpc_log($result);
        $waybill_extObj =  &app::get("logisticsmanager")->model("waybill_extend");
        if ($status == 'succ' && !empty($data[0])) {
            if ($data[0]['expno']=='') {
                $data[0]['expno']=$data['billno'];
            }
            $waybill_extObj->save_position($data[0]);
            $updata = array('status' => 'success');
            
        }else{
            $updata = array('status' => 'fail');
            $rpc_status = false;
        }
        $filter = array('log_id' => $data['out_biz_code']);
        $this->updateWaybillLog($updata, $filter);
        $this->emptyGenId();
        return $rpc_status;
    }

    public function cancel_billno($data) {
        
        $method = 'store.waybill.cancel';
        $writelog = array(
            'log_type' => 'other',
            'log_title' => 'STO官方电子面单取消',
            'original_bn' => $data['billno'],
            
        );

        $callback = array(

        );

        $this->emptyGenId();
        $result = $this->request($method, $data, $callback, 'sto', $writelog);
        if (empty($callback) && $result) {
                $this->rpc_log($result);
        }else{
            return false;
        }

        return $result;
    }
}