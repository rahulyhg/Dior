<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/21
 * Time: 14:46
 */
class creditorderapi_api_site extends creditorderapi_api{

    private $public_params = array(
        'method'    => '',
        'app_key'   => '',
        'timestamp' => '',
        'version'   => '1.0',
        'sign'      => '',
        'random'    => '',
        'format'    => 'json',
    );

    public function __construct(){
        set_time_limit(0);
        ini_set("memory_limit","128M");
        ini_set("max_execution_time",0);
        $this->api_name = 'site';
    }

    /**
     * 接口名称： send_invoice
     * 业务描述：发送发票信息到前端店铺
     * @param  订单主键id
     */
    public function send_invoice($order_id){
        $this->api_method='creditorderapi.site.send.invoice';
        if(empty($order_id)){
            return false;
        }
        $order_data=app::get('ome')->model('orders')->getList('shop_id,order_bn',array('order_id'=>$order_id));
        $invoice_data=app::get('einvoice')->model('invoice')->getList('invoice_id,pdfUrl,invoiceCode,invoiceNo',array('order_id'=>$order_id,'invoice_type'=>'active'));
        if(empty($invoice_data) || empty($order_data)){
            return false;
        }
        $request_data=array(
            'order_bn'=>$order_data[0]['order_bn'],
            'invoice_id'=>$invoice_data[0]['invoice_id'],
            'pdf_url'=>$invoice_data[0]['pdfUrl'],
            'invoice_code'=>$invoice_data[0]['invoiceCode'],
            'invoice_no'=>$invoice_data[0]['invoiceNo'],
        );
        $data=array('params'=>json_encode($request_data));
        $request_data=$this->build_request($data,$order_data[0]['shop_id']);
        $response_data=$this->rpc($request_data);
        if(!$this->check_api_status($response_data)){
            //发送邮件提醒
            /*$shop_bn=app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$order_data[0]['shop_id']));
            $acceptor=app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
            $subject='【'.strtoupper($shop_bn[0]['shop_bn']).'-PROD】订单#'.$order_data[0]['order_bn'].'发送前端发票信息失败';
            $bodys='订单号为['.$data['order_bn'].']的订单发送前端电子发票信息失败,请求原始数据为<br/>'.serialize($request_data).'<br/>失败返回信息为:['.$response_data['message'].']';
            kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            return false;*/
        }
        return true;
    }

    /**
     * 接口名称：sync_goods_stock
     * 业务描述：根据店铺全量同步oms库存到前台店铺
     * @param  店铺号
     */
    public function sync_goods_stock($shop_id=''){
        $this->api_method='creditorderapi.site.sync.goods.stock';
        if(empty($shop_id)){
            $shop_list=$this->get_shop_list();
        }else{
            $shop_list[]=$shop_id;
        }
        if(empty($shop_list)){
            return false;
        }
        foreach($shop_list as $value){
            $limit=100;
            $page=0;
            while(1){
                $offset=$page*$limit;
                $sql="SELECT b.material_bn,b.bm_id,p.store,p.store_freeze
                  FROM sdb_material_basic_material b
                  LEFT JOIN sdb_ome_branch_product p ON b.bm_id=p.product_id
                  WHERE b.shop_id='$value'
                  LIMIT $offset,$limit";
                $product_data=app::get('ome')->model('branch_product')->db->select($sql);
                $store_data=array();
                foreach($product_data as $k=>$v){
                    //查找material_basic_material_stock_freez表中对应的冻结库存
                    $filter=array('bm_id'=>$v['bm_id'],'branch_id'=>'0');
                    $freez_data=app::get('material')->model('basic_material_stock_freeze')->getList('num',$filter);
                    $freez_num=0;
                    foreach($freez_data as $num){
                        $freez_num+=$num['num'];
                    }
                    $final_stock=$v['store']-$v['store_freeze']-$freez_num;
                    if($final_stock<0){
                        $final_stock=0;
                    }
                    $store_data[$v['material_bn']]=$final_stock;
                }
                $data=array('params'=>json_encode($store_data));
                $request_data=$this->build_request($data,$value);
                $response_data=$this->rpc($request_data);
                //请求失败发送邮件提醒
                if(!$this->check_api_status($response_data)){
                    $shop_bn=app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$value));
                    $acceptor=app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
                    $subject='【'.strtoupper($shop_bn[0]['shop_bn']).'-PROD】库存同步前端失败';
                    $bodys='库存同步前端失败,请求原始数据为<br/>'.serialize($store_data).'<br/>错误信息为:['.$response_data['message'].']';
                    kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
                }
                if(count($product_data)<$limit){
                    break;
                }
                $page++;
            }
        }
        return true;
    }

    /**
     * 接口名称：sync_goods_stock_v2
     * 业务描述：根据商品编号同步oms库存到前台店铺
     * @param  shop_id  string
     * @param  商品bn  array
     */
    public function sync_goods_stock_v2($shop_id='',$bn=array()){
        $this->api_method='creditorderapi.site.sync.goods.stock';
        if(empty($bn) || empty($shop_id)){
            return false;
        }
        $bn_array=$bn_array=implode("','",$bn);
        $sql="SELECT b.material_bn,b.shop_id,b.bm_id,p.store,p.store_freeze
          FROM sdb_material_basic_material b
          LEFT JOIN sdb_ome_branch_product p ON b.bm_id=p.product_id
          WHERE b.material_bn in ('$bn_array') ";
        $product_data=app::get('ome')->model('branch_product')->db->select($sql);
        $store_data=array();
        foreach($product_data as $k=>$v){
            if($v['shop_id']!=$shop_id){
                return false;
            }
            //查找material_basic_material_stock_freez表中对应的冻结库存
            $filter=array('bm_id'=>$v['bm_id'],'branch_id'=>'0');
            $freez_data=app::get('material')->model('basic_material_stock_freeze')->getList('num',$filter);
            $freez_num=0;
            foreach($freez_data as $num){
                $freez_num+=$num['num'];
            }
            $final_stock=$v['store']-$v['store_freeze']-$freez_num;
            if($final_stock<0){
                $final_stock=0;
            }
            $store_data[$v['material_bn']]=$final_stock;
        }
        $data=array('params'=>json_encode($store_data));
        $request_data=$this->build_request($data,$shop_id);
        $response_data=$this->rpc($request_data);
        //请求失败发送邮件提醒
        if(!$this->check_api_status($response_data)){
            $shop_bn=app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$shop_id));
            $acceptor=app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
            $subject='【'.strtoupper($shop_bn[0]['shop_bn']).'-PROD】库存同步前端失败';
            $bodys='库存同步前端失败,请求原始数据为<br/>'.serialize($store_data).'<br/>错误信息为:['.$response_data['message'].']';
            kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            return false;
        }
        return true;
    }

    /**
     * 接口名称：sync_goods_price
     * 业务描述：同步oms商品价格到前台店铺
     * @param  店铺号
     */
    public function sync_goods_price($shop_id=''){
        $this->api_method='creditorderapi.site.sync.goods.price';
        if(empty($shop_id)){
            $shop_list=$this->get_shop_list();
        }else{
            $shop_list[]=$shop_id;
        }
        if(empty($shop_list)){
            return false;
        }
        foreach($shop_list as $value){
            $limit=100;
            $page=0;
            while(1){
                $offset=$page*$limit;
                $sql="SELECT s.sales_material_bn,e.retail_price
              FROM sdb_material_sales_material s
              LEFT JOIN sdb_material_sales_material_ext e ON s.sm_id=e.sm_id
              WHERE s.shop_id='$value'
              LIMIT $offset,$limit";
                $product_data=app::get('material')->model('sales_material')->db->select($sql);
                $price_data=array();
                foreach($product_data as $k=>$v){
                    $price_data[$v['sales_material_bn']]=$v['retail_price'];
                }
                $data=array('params'=>json_encode($price_data));
                $request_data=$this->build_request($data,$value);
                $response_data=$this->rpc($request_data);
                //请求失败发送邮件提醒
                if(!$this->check_api_status($response_data)){
                    $shop_bn=app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$value));
                    $acceptor=app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
                    $subject='【'.strtoupper($shop_bn[0]['shop_bn']).'-PROD】价格同步前端失败';
                    $bodys='价格同步前端失败,请求原始数据为<br/>'.serialize($price_data).'<br/>错误信息为:['.$response_data['message'].']';
                    kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
                }
                if(count($product_data)<$limit){
                    break;
                }
                $page++;
            }
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
        $order_model=app::get('ome')->model('orders');
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
        $order_model->update($update_data, array('logi_no'=>$logi_no,'order_bn'=>$order_bn));
        $order_id=$order_model->getList('order_id',array('order_bn'=>$order_bn));
        //同步前端状态
        $this->update_order_status($order_id[0]['order_id'],'completed');
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
        if($status == 'shipped'){
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

        $update_data = array(
            'order_bn' => $order_bn,
            'status'   => $status,
            'logi_bn'  => $logi_bn,
            'item_info'=> $item_info,
            'bill_info'=> $bill_info,
        );

        $data          = array('params'=>json_encode($update_data));
        $request_data  = $this->build_request($data,$shop_id);
        $response_data = $this->rpc($request_data);

        if(!$this->check_api_status($response_data)){
            // 请求失败发送邮件提醒
            /*$shop_bn  = app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$shop_id));
            $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
            $subject  = '【' . strtoupper($shop_bn[0]['shop_bn']) . '-PROD】订单#' . $order_bn . '状态回传前端失败';
            $bodys    = '订单号为[' . $order_bn . ']的订单更新状态失败,请求原始数据为<br/>' . serialize($update_data) . '<br/>,错误信息为:[' . $response_data['message'] . ']';
            //kernel::single('emailsetting_send')->send($acceptor, $subject, $bodys);
            return false;*/
        }
        return true;
    }

    /**
     * 获取所有店铺的shop_id
     * @return array
     */
    public function get_shop_list(){
        $shop_data=app::get('ome')->model('shop')->getList('shop_id');
        $shop_list=array();
        foreach($shop_data as $k=>$v){
            $shop_list[]=$v['shop_id'];
        }
        return $shop_list;
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
     * @param $shop_id
     * @return array
     */
    protected function build_request($params, $shop_id, $method = 'POST'){
        $params = $this->get_request_params($params,$shop_id);
        return array(
            'method' => $method,
            'url'    => $this->build_request_url($shop_id),
            'data'   => $this->build_request_data($params),
        );
    }

    /**
     * 处理接口公共请求参数
     * @param $params
     * @param $shop_id
     * @return array
     */
    private function get_request_params($params,$shop_id){
        unset($this->public_params['sign']);
        $this->public_params['app_key']   = $this->get_app_key($shop_id);
        $this->public_params['method']    = $this->api_method;
        $this->public_params['random']    = $this->get_rand();
        $this->public_params['timestamp'] = time();
        $this->public_params['sign']      = $this->build_sign($params,$shop_id);
        return array_merge($params,$this->public_params);
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
    private function build_sign($params,$shop_id){
        $request_data = array_merge($params,$this->public_params);
        ksort($request_data,2);
        $sign_str = ''; // 签名字符串
        foreach($request_data as $k=>$v){
            $sign_str .= $k . '=' . $v . '&';
        }
        $secret_key = $this->get_secret_key($shop_id);  // 签名key
        return  strtoupper(md5($sign_str . $secret_key));
    }

    /**
     * 获取app_key
     * @param $shop_id
     * @return mixed
     */
    private function get_app_key($shop_id){
        $secret_key = app::get('ome')->model('shop')->getList('shop_bn',array('shop_id'=>$shop_id));
        return $secret_key[0]['shop_bn'];
    }

    /**
     * 获取签名key
     * @param $shop_id
     * @return mixed
     */
    private function get_secret_key($shop_id){
        //$secret_key = app::get('ome')->model('shop')->getList('secret_key',array('shop_id'=>$shop_id));
        $sql = "SELECT * FROM sdb_creditorderapi_apiconfig WHERE shop_id LIKE '%".$shop_id."%'";
        $secret_key = app::get('creditorderapi')->model('apiconfig')->db->select($sql);
        return $secret_key[0]['secret_key'];
    }

    /**
     * 获取请求地址
     * @param $shop_id
     * @return mixed
     */
    private function build_request_url($shop_id){
        $api_name = explode('.', $this->api_method);
        //$url_name = end($api_name) . '_url';    // 获取数组最后一个元素的值
        //$url = app::get('ome')->model('shop')->getList($url_name,array('shop_id'=>$shop_id));
        $sql="SELECT * FROM sdb_creditorderapi_apicinfig WHERE shop_id LIKE '%".$shop_id."%'";
        $url = app::get('creditorderapi')->model('apiconfig')->db->select($sql);
        return $url[0]['crm_api_requesturl'];
        //return $url[0][$url_name];
    }

    /**
     * 返回数据-功能。。。
     * @param $data
     * @return mixed
     */
    private function build_request_data($data){
        return $data;
    }
}