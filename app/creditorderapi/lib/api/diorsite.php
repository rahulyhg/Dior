<?php
/**
 * Created by PhpStorm.
 * User: august.yao
 * Date: 2018/07/13
 * Time: 18:19
 */
class creditorderapi_api_diorsite extends creditorderapi_api_site{

    // 状态错误信息
    public $code_msg = array(
        '000' => 'Gift reservation successful',
        '208' => 'Invalid gift redemption order',
        '209' => 'Gift redemption processed before',
        '999' => 'Gift item error',
    );

    /**
     * 检查接口返回状态
     * @param $result
     * @return bool
     */
    public function check_api_status($result){
        if($result['StatusCode'] != '000'){
            return false;
        }
        return true;
    }
   
    /**
     * 同步订单签收状态
     * @param string $shop_id 店铺id
     * @param string $order_bn 订单号
     * @param string $sign_time 签收时间
     * @param string $logi_no 物流单号
     * @return bool
     */
    public function order_receipt($shop_id, $order_bn, $logi_no, $sign_time){

        // 检验数据
        if(empty($shop_id) || empty($order_bn) || empty($logi_no)){
            return false;
        }

        $this->api_method = 'creditorderapi.site.update.order.sign';

        // 更新订单单信息
        $update_data = array(
            'sf_sign_time'   => strtotime($sign_time),
            'sf_sign_status' => 1,
        );
        app::get('ome')->model('orders')->update($update_data, array('logi_no'=>$logi_no,'order_bn'=>$order_bn));

        // 请求接口数据
        $update_data = array(
            'OrderCode'       => $order_bn,
            'ReferenceNumber' => '',
        );

        $data          = array('params'=>json_encode($update_data));
        $request_data  = $this->build_request($data, $shop_id, 'GET');
        $response_data = $this->rpc($request_data, 'xml',$order_bn);

        if(!$this->check_api_status($response_data)){
            // 请求失败发送邮件提醒
            /*$shop_bn  = app::get('ome')->model('shop')->dump(array('shop_id'=>$shop_id), 'shop_bn');
            $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
            $subject  = '【' . strtoupper($shop_bn['shop_bn']) . '-PROD】订单#' . $order_bn . '签收状态回传前端失败';
            $bodys    = '订单号为[' . $order_bn . ']的订单更新状态失败,请求原始数据为<br/>' . serialize($update_data) . '<br/>,错误信息为:[' . $this->code_msg[$response_data['StatusCode']] . ']';
            kernel::single('emailsetting_send')->send($acceptor, $subject, $bodys);*/
            return false;
        }
        return true;
    }

    /**
     * 接口名称：update_order_status
     * 业务描述：更新订单状态到前台店铺
     * @param $order_id 订单号
     * @param $status 订单状态
     * @param $business_id 状态对应的类型id主键
     */
    public function update_order_status($order_id, $status, $business_id = ''){

        // 校验状态值
        $status_list = array(
            'synced',       // '已审核',
            'shipped',      // '已发货',
            'completed',    // '已完成',
            'reshipping',   // '退货申请中',
            'reshipped',    // '已退货',
            'refunding',    // '退款申请中',
            'refunded',     // '已退款',
            'cancel',       // '已取消'
        );
        // 检验状态
        if(!in_array($status, $status_list)){
            return false;
        }

        $this->api_method = 'creditorderapi.site.update.order.status';
        // 获取订单信息
        $order_data = app::get('ome')->model('orders')->getList('order_bn,shop_id',array('order_id'=>$order_id));
        if(empty($order_data)){
            return false;
        }

        $logi_bn   = '';
        $item_info = array();
        $bill_info = array();
        $order_bn  = $order_data[0]['order_bn'];
        $shop_id   = $order_data[0]['shop_id'];

        // 已发货和已退货状态读取明细列表
        if(($status == 'shipped')||($status=='complete')){
            $item_info = app::get('ome')->model('delivery_items')->db->select("SELECT i.bn AS sku,i.number AS num
                                                                            FROM sdb_ome_delivery_items i
                                                                            LEFT JOIN sdb_ome_delivery d ON d.delivery_id = i.delivery_id
                                                                            LEFT JOIN sdb_ome_delivery_order o ON o.delivery_id=d.delivery_id
                                                                            WHERE d.status='succ'
                                                                            AND o.order_id='$order_id'
                                                                          ");
            // 避免数据处理再单独取一次物流单号
            $logi_bn = app::get('ome')->model('delivery_items')->db->select("SELECT d.logi_no
                                                                            FROM sdb_ome_delivery d
                                                                            LEFT JOIN sdb_ome_delivery_order o ON o.delivery_id=d.delivery_id
                                                                            WHERE d.status='succ'
                                                                            AND o.order_id='$order_id'
                                                                          ");
            $logi_bn = $logi_bn[0]['logi_no'];
            if(empty($item_info) || empty($logi_bn)){
                return false;
            }
        }elseif($status == 'reshipped'){
            $item_info = app::get('ome')->model('reship_items')->db->select("SELECT i.bn AS sku,i.num
                                                                            FROM sdb_ome_reship_items i
                                                                            LEFT JOIN sdb_ome_reship r ON r.reship_id=i.reship_id
                                                                            WHERE r.status='succ'
                                                                            AND r.order_id='$order_id'
                                                                            AND r.reship_id='$business_id'
                                                                            ");
            if(empty($item_info)){
                return false;
            }
        }
        // 已退款读取账单列表
        if($status == 'refunded'){
            $refund_data = app::get('ome')->model('refunds')->getList('refund_bn,money',array('refund_id'=>$business_id,'order_id'=>$order_id,'status'=>'succ'));
            if(empty($refund_data)){
                return false;
            }
            $bill_info[] = array(
                'bn'    => $refund_data[0]['refund_bn'],
                'money' => $refund_data[0]['money']
            );
        }

        // 纪梵希已审核不需要推送
        if($status == 'synced'){
            return true;
        }

        // 纪梵希只需要订单号和物流单号 august.yao
        $update_data = array(
            'OrderCode'       => $order_bn,
            'ReferenceNumber' => $logi_bn,
        );

        $data = array('params'=>json_encode($update_data));

        if($status=='completed'){
            $urlType = 'crm_api_receiveurl';
        }elseif($status=='shipped'){
            $urlType = 'crm_api_shipurl';
        }
        //$request_data  = $this->build_request($data, $shop_id, 'GET');
        $request_data  = $this->build_request2($data, $shop_id,$urlType, 'GET');
        $response_data = $this->rpc($request_data, 'xml',$order_bn);
        //echo '<pre>d';print_r($response_data);exit;
        if(!$this->check_api_status($response_data)){
            // 请求失败发送邮件提醒
            /*$shop_bn  = app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$shop_id));
            $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
            $subject  = '【' . strtoupper($shop_bn[0]['shop_bn']) . '-PROD】订单#' . $order_bn . '发货状态回传前端失败';
            $bodys    = '订单号为[' . $order_bn . ']的订单新增失败,请求原始数据为<br/>' . serialize($update_data) . '<br/>,错误信息为:[' . $this->code_msg[$response_data['StatusCode']] . ']';
            //kernel::single('emailsetting_send')->send($acceptor, $subject, $bodys);*/
            
            return false;
        }
        return true;
    }

    /**
     * 请求处理
     * @param $request
     * @param string $result_type
     * @return bool|mixed
     */
    public function rpc($request, $result_type = 'json',$api_bn=''){
        $__key = md5(serialize(func_get_args()));
        if (isset($this->instance_data[$__key])){
            return $this->instance_data[$__key];
        }

        $method = empty($request['data']) ? 'GET' : 'POST';
        $request['method'] = $request['method'] ?:$method;
        $request_time  = microtime(true);
        $response_data = $this->action($request);
        $this->runtime = microtime(true) - $request_time;
        $result = $this->result2array($response_data,$result_type);
        if (method_exists($this,'check_api_status')) {
            $status = call_user_func(array($this, 'check_api_status'), $result);
            $api_status = $status ? 'success' : 'fail';
        } else {
            $api_status ='-';
        }
        //请求日志数据组装
        $data = array(
            'api_handler'       => 'request',
            'api_name'          => $this->api_name(),
            'api_bn' => $api_bn,
            'api_status'        => $api_status,
            'api_request_time'  => $request_time,
            'api_check_time'    => time(),
            'http_runtime'      => $this->get_runtime(),
            'http_method'       => $request['method'],
            'http_response_status'=>$this->http_code,
            'http_url'          => $request['url'],
            'http_request_data' => is_array($request['data']) ? $request['data'] : htmlspecialchars($request['data']),
            'http_response_data'=> $result,
            'sys_error_data'    => 'NULL'
        );

        app::get('creditorderapi')->model('api_log')->save($data);

        if($api_status == 'success'){
            $this->instance_data[$__key] = $result;
            return $result;
        }else{
            return false;
        }
    }

    /**
     * 封装curl的调用接口，GET的请求方式。 curl请求失败重试三次
     * @param $url
     * @param $data
     * @param $retry
     * @param int $timeout
     * @return bool|mixed
     */
    public function action($request, $retry = 3, $timeout = 300){

        // 组合URL
        $data = json_decode($request['data']['params'], true);
        $request['url'] .= '?' . http_build_query($data);
        // 初始化
        $con = curl_init();
        // 设置选项，包括URL
        curl_setopt($con, CURLOPT_URL, urldecode($request['url']));
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);

//        $httpStatusCode = 100;
//        while($httpStatusCode != 200 && $retry--){
            $output         = curl_exec($con);  // 执行并获取信息
            $httpStatusCode = curl_getinfo($con, CURLINFO_HTTP_CODE);
//        }
        $this->http_code = $httpStatusCode;
        curl_close($con);   // 释放curl句柄

        return $output;
    }

    /**
     * 接口名称：sync_goods_stock
     * 业务描述：根据店铺全量同步oms库存到前台店铺
     * @param  店铺号
     */
    public function sync_goods_stock($shop_id = ''){

        $this->api_method = 'creditorderapi.site.sync.goods.stock';

        if(empty($shop_id)){
            return false;
        }

        // 查看dior店铺是否配置同步接口
        $shop_res = app::get('ome')->model('shop')->dump(array('shop_id'=>$shop_id),'shop_bn,stock_url');

        if(!$shop_res){
            return false;
        }

        if(empty($shop_res['stock_url'])){
            return false;
        }

        $page  = 0;
        $limit = 100;

        while(1){
            $offset = $page * $limit;
            $sql = "SELECT b.material_bn,b.bm_id,p.store,p.store_freeze
                    FROM sdb_material_basic_material b
                    LEFT JOIN sdb_ome_branch_product p ON b.bm_id=p.product_id
                    WHERE b.shop_id='$shop_id'
                    LIMIT $offset,$limit";
            $product_data = app::get('ome')->model('branch_product')->db->select($sql);
            $store_data = array();
            foreach($product_data as $k=>$v){
                // 查找material_basic_material_stock_freez表中对应的冻结库存
                $filter     = array('bm_id'=>$v['bm_id'],'branch_id'=>'0');
                $freez_data = app::get('material')->model('basic_material_stock_freeze')->getList('num',$filter);
                $freez_num  = 0;
                foreach($freez_data as $num){
                    $freez_num += $num['num'];
                }
                $final_stock = $v['store'] - $v['store_freeze'] - $freez_num;
                if($final_stock < 0){
                    $final_stock = 0;
                }
                $store_data[$v['material_bn']] = $final_stock;
            }
            $data          = array('params'=>json_encode($store_data));
            $request_data  = $this->build_request($data, $shop_id);
            $response_data = $this->rpc($request_data);
            // 请求失败发送邮件提醒
            if(!$this->check_api_status($response_data)){

                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
                $subject  = '【' . strtoupper($shop_res['shop_bn']) . '-PROD】库存同步前端失败';
                $bodys    = '库存同步前端失败,请求原始数据为<br/>' . serialize($store_data) . '<br/>错误信息为:[' . $response_data['message'] . ']';
                kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
            if(count($product_data) < $limit){
                break;
            }
            $page++;
        }
        return true;
    }

    /**
     * 接口名称：sync_goods_price
     * 业务描述：同步oms商品价格到前台店铺
     * @param  店铺号
     */
    public function sync_goods_price($shop_id = ''){

        if(empty($shop_id)){
            return false;
        }

        $this->api_method = 'creditorderapi.site.sync.goods.price';

        // 查看dior店铺是否配置同步接口
        $shop_res = app::get('ome')->model('shop')->dump(array('shop_id'=>$shop_id),'shop_bn,price_url');

        if(!$shop_res){
            return false;
        }
        // 判断价格同步接口是否配置
        if(empty($shop_res['price_url'])){
            return false;
        }

        $page  = 0;
        $limit = 100;

        while(1){

            $offset = $page * $limit;
            $sql = "SELECT s.sales_material_bn,e.retail_price FROM sdb_material_sales_material s
                    LEFT JOIN sdb_material_sales_material_ext e ON s.sm_id=e.sm_id
                    WHERE s.shop_id='$shop_id'
                    LIMIT $offset,$limit";

            $product_data = app::get('material')->model('sales_material')->db->select($sql);
            $price_data = array();

            foreach($product_data as $k=>$v){
                $price_data[$v['sales_material_bn']]=$v['retail_price'];
            }

            $data          = array('params'=>json_encode($price_data));
            $request_data  = $this->build_request($data, $shop_id);
            $response_data = $this->rpc($request_data);
            //请求失败发送邮件提醒
            if(!$this->check_api_status($response_data)){
                $shop_bn  = app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$shop_id));
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
                $subject  = '【'.strtoupper($shop_bn[0]['shop_bn']).'-PROD】价格同步前端失败';
                $bodys    = '价格同步前端失败,请求原始数据为<br/>'.serialize($price_data).'<br/>错误信息为:['.$response_data['message'].']';
                kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
            if(count($product_data) < $limit){
                break;
            }
            $page++;
        }
        return true;
    }
}