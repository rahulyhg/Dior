<?php

abstract class logisticsmanager_rpc_abstract {

    public static $taobaoObj = null;
    public static $apilogModel = null;
    public static $waybillModel = null;
    public static $waybillExtendModel = null;

    public static $node = array();
    public $channelType = null;
    public $defaultChannelType = 'taobao';
    public $log_id = '';
    public $callbackParams = array();
    public $isChildBill = false;
    public $childBillId ='';

    public function setCurrChildBill($cid){
        $this->isChildBill = true;
        $this->childBillId = $cid;
    }
    
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
        }
        return $status;
    }

    public function request($method, $params, $callback = array(), $shop_id, $writelog = array(), $time_out=30) {
        $result = false;
        //过滤参数中的空值
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                unset($params[$key]);
            }
        }

        if ($this->getNode($shop_id)) {
            $params['to_node_id'] = self::$node[$shop_id]['node_id'];
            $params['node_type'] = self::$node[$shop_id]['node_type'];
        }
        else {
            return false;
        }
        //设置callback异常返回参数为空时的默认值
        $basic_callback_params = array('log_id'=> $this->getGenId(), 'shop_id' => $shop_id);
        if ($callback && $callback['class'] && $callback['method']) {
            $callback_params = $callback['params'] ? array_merge($basic_callback_params, $callback['params']) : $basic_callback_params;
            $rpc_callback = array($callback['class'], $callback['method'], $callback_params);
        }
        else {
            $rpc_callback = array();
        }

        //设置同步callback
        if (empty($rpc_callback)) {
            $this->setCallbackParams($basic_callback_params);
        }

        $msg = '请求中';
        $this->writeApiLog($writelog, $method, $params, $msg);
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
     * 设置同步callback_params参数
     * @param String $shop_id 店铺ID
     */
    public function setCallbackParams($callback_params) {
        $this->callbackParams = $callback_params;
    }

    /**
     * 获取callbackParams参数
     * Enter description here ...
     */
    public function getCallBackParams() {
        return $this->callbackParams;
    }

    /**
     * RPC同步返回数据日志
     * @param Array $result 数组
     */
    public function rpc_log($result) {
        if (is_object($result)) {
            $callback_params = $result->get_callback_params();
            $status = $result->get_status();
            $msg = $result->get_result();
            $err_msg = $result->get_err_msg();
            $data = $result->get_data();
            $msg_id = $result->get_msg_id();
        }
        elseif (is_array($result)) {
            $callback_params = $this->getCallBackParams();
            $status = $result['rsp'];
            $msg = isset($result['msg']) ? $result['msg'] : '';
            $err_msg = isset($result['err_msg']) ? $result['err_msg'] : '';
            $data = $result['data'];
            $msg_id = $result['msg_id'];
        }
        else {
            return false;
        }

        $api_status = $status == 'succ' ? 'success' : 'fail';

        $msg != '' &&  $msg = '('.$msg.')' . $err_msg;
        if (empty($msg) && $err_msg) {
            $msg = $err_msg;
        }

        $rsp = 'succ';
        if ($status != 'succ' && $status != 'fail') {
            $msg = 'rsp:' . $status  . 'res:'. $msg. 'data:' . $data;
            $rsp = 'fail';
        }
        else if ($status != 'succ') {
            $rsp = 'fail';
        }

        //错误等级
        if (is_array($data) && isset($data['error_level']) && !empty($data['error_level'])) {
            $addon['error_lv'] = $data['error_level'];
        }
        
        $log_id = $callback_params['log_id'];
        $this->updateLog($log_id, $msg, $api_status, null, $addon);
        if ($msg_id) {
            $updata = array('msg_id' => $msg_id);
            $update_filter = array('log_id' => $log_id);
            $this->updateLogItem($updata, $update_filter);
        }
        return array('rsp'=> $rsp, 'res' => $msg, 'msg_id'=>$msg_id);
    }

    /**
     * 设置结点信息
     * @param String $shop_id 店铺ID
     */
    public function setNode($shop_id) {
        $channelType = $this->getChannelType();
        if (self::$taobaoObj === null) {
            self::$taobaoObj = kernel::single('logisticsmanager_service_taobao');
        }
        $node = array();
        switch ($channelType) {
            case 'taobao':
                $shop = self::$taobaoObj->getShop($shop_id, $channelType);
                $node = $this->formatNode($shop);
                break;
            case 'sf':
            case 'yunda':
            case 'sto':
                $node = $this->getBindNode();
                break;
            case '360buy':
                $node = $this->get360buyNodeInfo($shop_id);
                break;
           
        }
        if ($node) {
            self::$node[$shop_id] = $node;
        }
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
     * 格式化结点
     * @param Array $shop 店铺信息
     */
    public function formatNode($shop) {
        $node = array();
        $node['shop_id'] = $shop['shop_id'];
        $node['node_type'] = $shop['node_type'];
        $node['node_id'] = $shop['node_id'];
        $node['node_type_shop'] = $shop['addon']['type'];
        return $node;
    }

    public function getNode($shop_id) {
        if (!self::$node[$shop_id]) {
            $this->setNode($shop_id);
        }
        return self::$node[$shop_id];
    }

    /**
     * 设置结点类型
     * @param String $type 结点类型
     */
    public function setChannelType($type) {
        $this->channelType = $type;
        return $this;
    }

    /**
     * 获取节点类型
     */
    public function getChannelType() {
        $channelType = $this->channelType ? $this->channelType : $this->defaultChannelType;
        return $channelType;
    }

    /**
     * 写API日志
     */
    public function writeApiLog($writelog, $method, $params, $msg) {
        if (self::$apilogModel === null) {
            self::$apilogModel = app::get('ome')->model('api_log');
        }
        $log_type = $writelog['log_type'];
        $original_bn = $writelog['original_bn'];
        $log_title = $writelog['log_title'];
        $log_id = $this->getGenId();
        self::$apilogModel->write_log($log_id,$log_title,'logisticsmanager_rpc_request','request',array($method, $params),'','request','running', $msg, '',$log_type,$original_bn);
    }

    /**
     * 更新API请求日志
     * @param unknown_type $log_id
     * @param unknown_type $msg
     * @param unknown_type $status
     * @param unknown_type $params
     * @param unknown_type $addon
     */
    public function updateLog($log_id, $msg = NULL, $status = NULL, $params = NULL, $addon=NULL) {
        if (self::$apilogModel === null) {
            self::$apilogModel = app::get('ome')->model('api_log');
        }
        return self::$apilogModel->update_log($log_id, $msg, $status, $params, $addon);
    }
    

    /**
     * 更新API日志
     * @param Array $data 更新数据
     * @param Array $filter 过滤器
     */
    public function updateLogItem($data, $filter) {
        if (self::$apilogModel === null) {
            self::$apilogModel = app::get('ome')->model('api_log');
        }
        return self::$apilogModel->update($data, $filter);
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

    /**
     * 检查物流单是否存在
     * @param String $waybill_code 物流单号
     * @param Array $params 物流配置参数
     */
    public function checkWaybillCode($waybill_code, $params) {
        if (self::$waybillModel == null) {
            self::$waybillModel = app::get('logisticsmanager')->model('waybill');
        }
        $filter = array('channel_id' => $params['channel_id'], 'waybill_number' => $waybill_code);
        $result = self::$waybillModel->dump($filter);
        return $result ? true : false;
    }

    /**
     * 插入物流单号
     * @param String $waybill_code 物流单号
     * @param Array $params 物流配置参数
     */
    public function insertWaybillCode($waybill_code, $params) {
        $result = true;
        if ($this->checkWaybillCode($waybill_code, $params) == false) {
            $data = array(
                'waybill_number' => $waybill_code,
                'channel_id' => $params['channel_id'],
                'logistics_code' => $params['logistics_code'],
                'status' => isset($params['waybill_status']) ? $params['waybill_status'] : 1,
                'create_time'=>time(),
            );
            $result = self::$waybillModel->insert($data);
        }
        return $result;
    }

    /**
     * 获取运单
     * @param String $waybill_code 运单号
     * @param Array $params 参数
     */
    public function getWayBill($waybill_code, $params) {
        if (self::$waybillModel == null) {
            self::$waybillModel = app::get('logisticsmanager')->model('waybill');
        }
        $filter = array(
            'waybill_number' => $waybill_code,
            'channel_id' => $params['channel_id'],
        );
        $result = self::$waybillModel->dump($filter);
        return $result;
    }

    /**
     * 更新物流单获取日志
     * @param Array $data 更新数据
     * @param Array $filter 过滤器
     */
    public function updateWaybillLog($updata, $filter) {
        if (self::$taobaoObj === null) {
            self::$taobaoObj = kernel::single('logisticsmanager_service_taobao');
        }
        return self::$taobaoObj->updateWaybillLog($updata, $filter);
    }

    /**
     * 更新发货单物流单
     * @param Int $delivery_id 发货单
     * @param String $waybill_code 物流单
     */
    public function updateDeliveryLogino($delivery_id, $waybill_code) {
        if (self::$taobaoObj === null) {
            self::$taobaoObj = kernel::single('logisticsmanager_service_taobao');
        }

        if($this->isChildBill){
            return self::$taobaoObj->updateDlyBillLogino($delivery_id, $this->childBillId, $waybill_code);
        }else{
            return self::$taobaoObj->updateDeliveryLogino($delivery_id, $waybill_code);
        }
    }

    /**
     * 获取绑定结点信息
     */
    public function getBindNode() {
        return array(
            'node_id' => $this->to_node,
            'node_type' => $this->node_type,
        ); 
    }

    /**
     * 检查面单扩展信息是否存在
     * @param Array $params 参数
     */
    public function checkWaybillExtend($params) {
        if (self::$waybillExtendModel === null) {
            self::$waybillExtendModel = app::get('logisticsmanager')->model('waybill_extend');
        }
        $filter['waybill_id'] = $params['waybill_id'];
        $result = self::$waybillExtendModel->dump($filter);
        return $result ? true : false;
    }

    /**
     * 保存订单扩展信息
     * @param Array $data 数据
     * @param Array $params 参数
     */
    public function saveWaybillExtend($data, $enforce = false) {
        $result = '';
        if (!$this->checkWaybillExtend($data)) {
            //插入数据
            $result = self::$waybillExtendModel->insert($data);
        }
        else {
            if ($enforce) {
                $filter = array('waybill_id' => $data['waybill_id']);
                $result = self::$waybillExtendModel->update($data, $filter);
            }
        }
        return $result ? true : false;
    }

    /**
     * 获取京东node_id
     */
    private function get360buyNodeInfo($shop_id) {
        $sql = "SELECT `shop_id`,`node_type`,`node_id`,`addon` FROM `sdb_ome_shop` WHERE `node_type`='360buy' and `node_id`!='' and shop_id='".$shop_id."'";
       
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


    
}