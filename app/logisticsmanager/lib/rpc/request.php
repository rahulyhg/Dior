<?php
/**
 * DRM相关接口请求基类
 * 各个接口先组织应用级参数，然后统一调用本类的公共方法向矩阵发起RPC
 * @author shshuai
 */
class logisticsmanager_rpc_request {
    const EMS_NODE_ID = '1815770338';
    const STO_NODE_ID = '1064384233';
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
    public function request($method, $params, $callback=array(), $shop_id, $writelog=array(), $time_out=15) {
        $result = false;
        //过滤参数中的空值
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                unset($params[$key]);
            }
        }
        //过滤此次同步前端店铺
        if ($node = $this->_get_node($shop_id)) {
            $params['to_node_id'] = $node['node_id'];
            $params['node_type'] = $node['node_type'];
            /*$params['from_api_v'] = '';
            $params['to_api_v'] = '';*/
        } else {
            return $result;
        }

        $log_type = $writelog['log_type'];
        $original_bn = $writelog['original_bn'];
        $log_title = $writelog['log_title'];
        $apiLogObj = app::get('ome')->model('api_log');
        $log_id = $apiLogObj->gen_id();

        // 设置callback异常返回参数为空时的默认值
        $basic_callback_params = array('log_id'=>$log_id,'shop_id'=>$shop_id);
        if($callback && $callback['class'] && $callback['method']){
            $callback_params = $callback['params'] ? array_merge($basic_callback_params,$callback['params']) : $basic_callback_params;
            $rpc_callback = array($callback['class'],$callback['method'],$callback_params);
        }else{
            $rpc_callback = array(get_class($this),'callback',$basic_callback_params);
        }
       
        //记录日志
        $apiLogObj->write_log($log_id,$log_title,'logisticsmanager_rpc_request','request',array($method, $params),'','request','running','请求中','',$log_type,$original_bn);

        //发送请求
        $result = $this->rpc_request($method, $params, $rpc_callback, $time_out);

        return $result;
    }
    /**
     * 
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
    public function call($method, $params, $callback=array(), $shop_id, $writelog=array(), $time_out=15) {
        $result = false;
        //过滤参数中的空值
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                unset($params[$key]);
            }
        }
        //过滤此次同步前端店铺
        if ($node = $this->_get_node($shop_id)) {
            $params['to_node_id'] = $node['node_id'];
            $params['node_type'] = $node['node_type'];
        } else {
            return $result;
        }

        $log_type = $writelog['log_type'];
        $original_bn = $writelog['original_bn'];
        $log_title = $writelog['log_title'];
        $apiLogObj = app::get('ome')->model('api_log');
        $log_id = $apiLogObj->gen_id();

        // 设置callback异常返回参数为空时的默认值
        $basic_callback_params = array('log_id'=>$log_id,'shop_id'=>$shop_id);
        if($callback && $callback['class'] && $callback['method']){
            $callback_params = $callback['params'] ? array_merge($basic_callback_params,$callback['params']) : $basic_callback_params;
            $rpc_callback = array($callback['class'],$callback['method'],$callback_params);
        }else{
            $rpc_callback = array();
        }
        
        if (empty($rpc_callback)) {
            $this->basic_callback_params = $basic_callback_params;
        }

        //记录日志
        $apiLogObj->write_log($log_id,$log_title,'logisticsmanager_rpc_request','request',array($method, $params),'','request','running','请求中','',$log_type,$original_bn);

        //发送请求
        $result = $this->rpc_request($method, $params, $rpc_callback, $time_out);
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
    public function rpc_request($method,$params,$callback,$time_out=15){
        if (empty($callback)) {
            $result = app::get('ome')->matrix()->set_realtime(true)
                ->set_timeout($time_out)
                ->call($method, $params);
            $result = json_decode(json_encode($result),true);
        } else {
            if (isset($params['gzip'])){
                $gzip = $params['gzip'];
            }else{
                $gzip = false;
            }
            $callback_class = $callback[0];
            $callback_method = $callback[1];
            $callback_params = (isset($callback[2])&&$callback[2])?$callback[2]:array();
            if (isset($params[1]['task'])){
                $rpc_id = $params[1]['task'];
            }
            $result = app::get('ome')->matrix()->set_callback($callback_class,$callback_method,$callback_params)
                ->set_timeout($time_out)
                ->call($method,$params,$rpc_id,$gzip);
        }


        //模拟接口，本地调用
        //$result = kernel::single('drm_rpc_testresult')->call($method,$params);
        return $result;
    }

    /**
     * RPC异步返回数据接收
     * @access public
     * @param object $result 经由框架层处理后的同步结果数据
     * @return 返回业务处理结果
     */
    public function callback($result){

        if (is_object($result)){
            $callback_params = $result->get_callback_params();
            $status = $result->get_status();
            $msg = $result->get_result();
            $err_msg = $result->get_err_msg();
            $data = $result->get_data();
            $msg_id = $result->get_msg_id();
        }else{
            return true;
        }

        if($status == 'succ'){
            $api_status = 'success';
        }else{
            $api_status = 'fail';
        }

        if($msg !=''){
            $msg = '('.$msg.')'.$err_msg;
        }

        $rsp  ='succ';
        if ($status != 'succ' && $status != 'fail' ){
            $msg = 'rsp:'.$status .'res:'. $msg. 'data:'. $data;
            $rsp = 'fail';
        }
        //错误等级
        if (is_array($data) && isset($data['error_level']) && !empty($data['error_level'])){
            $addon['error_lv'] = $data['error_level'];
        }
        $log_id = $callback_params['log_id'];
        $apiLogObj = &app::get('ome')->model('api_log');
        $apiLogObj->update_log($log_id, $msg, $api_status, null, $addon);

        return array('rsp'=>$rsp, 'res'=>$msg, 'msg_id'=>$msg_id);
    }

    /**
     * RPC同步返回数据接收
     * @access public
     * @param json array $res RPC响应结果
     * @param array $params 同步日志ID
     */
    public function response_log($res, $params){
        $response = json_decode($res, true);
        if (!is_array($response)){
            $response = array(
                'rsp' => 'running',
                'res' => $res,
            );
        }
        $status = $response['rsp'];
        $result = $response['res'];

        if($status == 'running'){
            $api_status = 'running';
        }elseif ($result == 'rx002'){
            //将解除绑定的重试设置为成功
            $api_status = 'success';
        }else{
            $api_status = 'fail';
        }

        $log_id = $params['log_id'];
        $apiLogObj = app::get('ome')->model('api_log');

        //更新日志数据
        $apiLogObj->update_log($log_id, $result, $api_status);

        if ($response['msg_id']){
            //更新日志msg_id及在应用级参数中记录task
            $update_data = array('msg_id' => $response['msg_id']);
            $update_filter = array('log_id'=>$log_id);
            $apiLogObj->update($update_data, $update_filter);
        }
    }

    /**
     * 通过shop_id获取结点信息
     * @access private
     * @param $shop_id
     * @return array 店铺绑定的节点数据
     */
    private function _get_node($shop_id){
        if($shop_id=='ems'){
            $node = array(
                'node_id' => self::EMS_NODE_ID,
                'node_type' => 'ems',
            );
            return $node;
        } elseif ($shop_id == '360buy') {
            $nodeInfo = $this->get360buyNodeInfo();
            $node = array(
                'node_id' => $nodeInfo['node_id'],
                'node_type' => $nodeInfo['node_type']
            );
            return $node;
        } elseif($shop_id == 'sto'){
            $node = array(
                'node_id' => self::STO_NODE_ID,
                'node_type' => 'sto',
            );
            return $node;
        }
        else {
            return false;
        }
    }
    /**
     * 获取京东node_id
     */
    private function get360buyNodeInfo() {
        $sql = "SELECT `shop_id`,`node_type`,`node_id`,`addon` FROM `sdb_ome_shop` WHERE `node_type`='360buy' and `node_id`!=''";
        $shopList = kernel::database()->select($sql);
        $node = array();
        foreach ($shopList as $v) {
            if ($v['addon']) {
                $addon = unserialize($v['addon']);
                if ($addon['type'] == 'SOP') {
                    $node['shop_id'] = $v['shop_id'];
                    $node['node_type'] = $v['node_type'];
                    $node['node_id'] = $v['node_id'];
                    $node['node_type_shop'] = $addon['type'];
                    $break = true;
                }
            }
            if ($break) {
                break;
            }
        }
        return $node;
    }

    /**
     * 获取签名串
     * @access public
     * @param array $params
     * @param string $token
     * @return string 签名串
     */
    public function genSign($params,$token) {
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