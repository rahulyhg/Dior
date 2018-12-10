<?php

/**
 * kafka接口操作类
 * Class ome_kafka_api
 */
class ome_kafka_api extends ome_kafka_request{

    // api公共参数
    private $public_params = array(
        'method'    => '',      // 请求方式
        'app_key'   => '',      // app_key
        'timestamp' => '',      // 时间戳
        'version'   => '1.0',   // 接口版本号
        'sign'      => '',      // 接口签名信息
        'random'    => '',      // 随机数
        'format'    => 'json',  // 数据格式 json/xml
    );

    public function __construct(){
        set_time_limit(0);
        ini_set("memory_limit", "128M");
        ini_set("max_execution_time", 0);
    }

    /**
     * 检查接口返回状态是否是200
     * @param $result
     * @return bool
     */
    public function check_api_status($result){
        if($result['code'] != '200'){
            return false;
        }
        return true;
    }

    /**
     * 处理接口请求参数
     * @param $params
     * @return array
     */
    private function build_request($params, $method = 'POST'){
        $params = $this->get_request_params($params);
        return array(
            'method' => $method,
            'url'    => $this->build_request_url(),
            'data'   => $this->build_request_data($params),
        );
    }

    /**
     * 处理接口公共请求参数
     * @param $params
     * @param $shop_id
     * @return array
     */
    private function get_request_params($params){
        unset($this->public_params['sign']);
        $this->public_params['app_key']   = $this->get_app_key();
        $this->public_params['method']    = $this->api_method;
        $this->public_params['random']    = $this->get_rand();
        $this->public_params['timestamp'] = time();
        $this->public_params['sign']      = $this->build_sign($params);
        return array_merge($params, $this->public_params);
    }

    /**
     * 随机数
     * @return string
     */
    private function get_rand(){
        $str  = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
        $rand = substr(str_shuffle($str),5,10);
        return $rand;
    }

    /**
     * 生成签名数据
     * @param $params
     * @param $shop_id
     * @return string
     */
    private function build_sign($params){

        // 1001和1002签名规则
        if($this->api_method == '10001'){
            $request_data = json_decode($params['params'], true);
        }else{
            $request_data = array_merge($params, $this->public_params);
        }

        ksort($request_data, 2);
        $sign_str = ''; // 签名字符串
        foreach($request_data as $k=>$v){
            $sign_str .= $k . '=' . $v . '&';
        }
        $secret_key = $this->get_secret_key();  // 签名key
        return strtoupper(md5($sign_str . $secret_key));
    }

    /**
     * 获取app_key
     * @return string
     */
    private function get_app_key(){
        // 获取缓存信息
        $KafkaConf = app::get('ome')->getConf('KafkaConf');
        $KafkaConf = unserialize($KafkaConf);
        return $KafkaConf['app_key'];
    }

    /**
     * 获取签名key
     * @return string
     */
    private function get_secret_key(){
        // 获取缓存信息
        $KafkaConf = app::get('ome')->getConf('KafkaConf');
        $KafkaConf = unserialize($KafkaConf);
        return $KafkaConf['secret_key'];
    }

    /**
     * 获取请求地址
     * @param $shop_id
     * @return mixed
     */
    private function build_request_url(){
        // 获取缓存信息
        $KafkaConf = app::get('ome')->getConf('KafkaConf');
        $KafkaConf = unserialize($KafkaConf);
        return $KafkaConf['api_url'] . $this->api_method;
        //return 'http://kafka.chinanorth.cloudapp.chinacloudapi.cn/kafka/send/' . $this->api_method; // 测试环境
        //return 'http://kafkagw.chinanorth.cloudapp.chinacloudapi.cn/kafka/send/' . $this->api_method; // 正式环境
    }

    /**
     * 返回数据-功能。。。
     * @param $data
     * @return array()
     */
    private function build_request_data($data){
        return $data;
    }

    /**
     * 订单状态推送方法
     * @param $order_bn 订单编号
     * @param $status 订单状态
     * @param $data 订单数据
     * @param $shopId 店铺id
     * @param $apiLogId 如果存在则更新log
     * @return array()
     */
    public function sendOrderStatus($order_bn, $status, $data, $shopId, $apiLogId = 0){

        if(empty($order_bn) || empty($status)){
            return array('success'=>false, 'msg'=>'订单号或者订单状态为空');
        }

        $this->api_method = '10002';    // 接口名称

        $update_data = array(
            'order_bn'   => $order_bn,
            'status'     => $status,
            'createtime' => $data['createtime'],
            'statusTime' => $data['statusTime'],
        );

//        'paid'=>'已支付',
//        'synced'=>'已审核',
//        'shipped'=>'已发货',
//        'completed'=>'已完成',
//        'reshipping'=>'退货申请中',
//        'reshipped'=>'已退货',
//        'refunding'=>'退款申请中',
//        'refunded'=>'已退款',
//        'cancel'=>'已取消'

        switch ($status){
            case 'shipped': // 已发货
                $update_data['logi_bn']   = $data['logi_bn'];
                $update_data['item_info'] = $data['item_info'];
                break;

            case 'reshipped': // 已退货
                $update_data['item_info'] = $data['item_info'];
                break;

            case 'refunded': // 已退款
                $update_data['bill_info'] = $data['bill_info'];
        }

        $request_data  = $this->build_request(array('params'=>json_encode($update_data)));
        $response_data = $this->rpc($request_data, 'json', $apiLogId);

        if(!$this->check_api_status($response_data)){
            // 请求失败发送邮件提醒
            $shop_bn  = app::get('ome')->model('shop')->dump(array('shop_id' => $shopId), 'shop_bn');
            $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
            $subject  = '【' . strtoupper($shop_bn['shop_bn']) . '】订单#' . $order_bn . '状态回传kafka失败';
            $bodys    = '订单号为[' . $order_bn . ']的订单状态回传kafka失败,请求原始数据为<br/>' . serialize($update_data) . '<br/>,错误信息为:[' . $response_data['message'] . ']，如已处理请忽略！';
            kernel::single('emailsetting_send')->send($acceptor, $subject, $bodys);

            return array('success'=>false, 'msg'=>$response_data['message'] ? $response_data['message'] : '订单状态推送kafka失败');
        }
        return array('success'=>true, 'msg'=>$response_data['message'] ? $response_data['message'] : '订单状态推送kafka成功');
    }

    /**
     * 订单状态推送方法
     * @param $order_bn 订单编号
     * @param $status 订单状态
     * @param $data 订单数据
     * @param $shopId 店铺id
     * @param $apiLogId 如果存在则更新log
     * @return array()
     */
    public function createOrder($order_bn, $status, $data, $shopId, $apiLogId){

        if(empty($order_bn) || empty($status)){
            return array('success'=>false, 'msg'=>'订单号或者订单状态为空');
        }

        $this->api_method = '10001';    // 接口名称

        // 组装请求数据
        if(isset($data['createOrder']['member_id'])){
            // 用户信息
            $userInfo = kernel::single("ome_mdl_members")->dump(array('member_id'=>$data['createOrder']['member_id']),'*');
            // 地址信息
            $addressId = app::get('ome')->model('orders')->dump(array('order_bn'=>$order_bn),'ship_area,order_id');
            $addressId = explode(':', $addressId['consignee']['area']);
            $addressId = str_replace('/', '-', $addressId[1]);
            $addCount  = count(explode('-', $addressId));
            if($addCount < 3 ){
                $addressId = $data['createOrder']['address_id'];
            }
            $products = array();
            foreach ($data['createOrder']['order_objects'] as $key=>$val){
                $products[] = array(
                    'bn'         => $val['bn'],
                    'name'       => urlencode($val['name']),
                    'num'        => $val['quantity'],
                    'sale_price' => $val['sale_price'],
                );
            }
            $request_data  = array(
                'order_bn'      => $data['createOrder']['order_bn'],
                'createtime'    => $data['createOrder']['createtime'],
                'pay_bn'        => $data['createOrder']['pay_bn'],
                'paytime'       => $data['createOrder']['paytime'],
                'cost_shipping' => $data['createOrder']['shipping']['cost_shipping'] ? $data['createOrder']['shipping']['cost_shipping'] : '0.00',
                'is_letter'     => $data['createOrder']['is_letter'],
                'address_id'    => urlencode($addressId),
                'consignee'     => array(
                    'name'      => urlencode($data['createOrder']['consignee']['name']),
                    'mobile'    => $data['createOrder']['consignee']['mobile'],
                    'addr'      => urlencode($data['createOrder']['consignee']['addr']),
                ),
                'account'       => array(
                    'name'      => urlencode($userInfo['contact']['name']),
                    'mobile'    => $userInfo['contact']['phone']['mobile'],
                ),
                'products'      => $products,
                'order_refer_source' => $data['createOrder']['order_refer_source'],
                'order_type'    => $data['createOrder']['order_type'],
            );
        }else{
            $request_data = $data['createOrder'];
        }

        $request_data  = $this->build_request(array('params'=>json_encode($request_data)));
        $response_data = $this->rpc($request_data, 'json', $apiLogId);

        if(!$this->check_api_status($response_data)){
            // 请求失败发送邮件提醒
            $shop_bn  = app::get('ome')->model('shop')->dump(array('shop_id' => $shopId), 'shop_bn');
            $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
            $subject  = '【' . strtoupper($shop_bn['shop_bn']) . '】订单#' . $order_bn . '推送kafka失败';
            $bodys    = '订单号为[' . $order_bn . ']推送kafka失败,请求原始数据为<br/>' . serialize($data['createOrder']) . '<br/>,错误信息为:[' . $response_data['message'] . ']，如已处理请忽略！';
            kernel::single('emailsetting_send')->send($acceptor, $subject, $bodys);

            return array('success'=>false, 'msg'=>$response_data['message'] ? $response_data['message'] : '订单推送kafka失败');
        }
        return array('success'=>true, 'msg'=>$response_data['message'] ? $response_data['message'] : '订单推送kafka成功');
    }
}