<?php
/**
 * 发货同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_shipping extends ome_rpc_request {

    //发货状态
    var $ship_status = array(
        'succ'=>'SUCC',
        'failed'=>'FAILED',
        'cancel'=>'CANCEL',
        'lost'=>'LOST',
        'progress'=>'PROGRESS',
        'timeout'=>'TIMEOUT',
        'ready'=>'READY',
        'stop'=>'STOP',
        'back'=>'BACK',
        'verify' => 'VERIFY',//TODO:新增加的校验
    );

    //货品类型
    var $item_type  = array(
        'product'=>'product',
        'gift'=>'gift',
        'adjunct'=>'adjunct',
        'pkg'=>'pkg'
    );

    //发货类型
    var $delivery_type = array(
        'Y' => 'delivery_needed',
        'N' => 'virtual_goods',
    );


    /**
     * 添加交易发货单
     * @access public
     * @param int $delivery_id 发货单ID
     * @return boolean
     */
    public function add($delivery_id){

        if(!empty($delivery_id)){
            //通过内部会员id找到外部会员id
            $deliveryObj = &app::get('ome')->model('delivery');
            $orderObj = &app::get('ome')->model('orders');
            $shopObj = &app::get('ome')->model('shop');
            $delivery_orderObj = &app::get('ome')->model('delivery_order');

            $delivery_detail = $deliveryObj->dump($delivery_id, 'is_bind,parent_id');
            $delivery_order = $delivery_orderObj->dump(array('delivery_id'=>$delivery_id));
            $order_detail = $orderObj->dump($delivery_order['order_id'], 'ship_status,shop_id,self_delivery,createway');
            $shop_detail = $shopObj->dump($order_detail['shop_id'], 'node_type,node_id');
            $node_type = $shop_detail['node_type'];

            //判断是否发起添加发货单请求同步
            $is_request = 'true';
            if ($delivery_add_service = kernel::service('service.delivery.'.$node_type)){
                if (method_exists($delivery_add_service, 'add')){
                    $delivery_add_data = $delivery_add_service->add($delivery_id, $is_request);
                }
            }

            if ($is_request == 'false'){
                return false;
            }

            $c2c_shop_list = ome_shop_type::shop_list();
            if (in_array($shop_detail['node_type'], $c2c_shop_list)){
                    //如果是合并的发货单，则向所有合并后的订单发货通知
                    if($delivery_detail['is_bind']=='true'){
                        $delivery_order_list = $delivery_orderObj->getList('order_id',array('delivery_id'=>$delivery_id),0,-1);
                        if ($delivery_order_list) {
                            foreach ($delivery_order_list as $k=>$v){
                                $deliveryorder = $orderObj->dump($v['order_id'],'ship_status,self_delivery,shop_type');
                                //判断此订单是否是已发货状态
                                if ($deliveryorder['ship_status']!=1 || ($deliveryorder['self_delivery'] == 'false' && $deliveryorder['shop_type'] == 'amazon') ) continue;
                                $this->_get_shipping_params($delivery_id,$v['order_id'],'',$shop_detail['node_type'],$addon,'normal');
                            }
                        }
                    }else{
                        //判断此订单是否是已发货状态
                        if ($order_detail['ship_status']!=1 || ($order_detail['self_delivery'] == 'false' && $shop_detail['node_type'] == 'amazon') ) return false;

                        $this->_get_shipping_params($delivery_id,'','',$shop_detail['node_type'],$addon,'normal');
                    }
            }else{
                if( $order_detail['self_delivery'] == 'false' && $shop_detail['node_type'] == 'amazon' ) return false;

                $this->_get_shipping_params($delivery_id,'',$delivery_detail['parent_id'],$shop_detail['node_type'],$addon,'normal');
            }
        }else{
            return false;
        }
    }
    function shipping_add_callback($result){

        //更新订单发货成功后的回传时间
        $status = $result->get_status();

        if ($status == 'succ'){
            $oOrder = &app::get('ome')->model('orders');
            $oApi_log = &app::get('ome')->model('api_log');
            $callback_params = $result->get_callback_params();
            $request_params = $result->get_request_params();

            /*
            $log_id = $callback_params['log_id'];
            $apilog_detail = $oApi_log->dump(array('log_id'=>$log_id), 'params');
            $apilog_detail = unserialize($apilog_detail['params']);
            $order_bn = $apilog_detail[1]['tid'];
            $shop_id = $apilog_detail[1]['shop_id'];
            */
            $order_bn = $request_params['tid'];
            $shop_id = $callback_params['shop_id'];
            $oOrder->update(array('up_time'=>time()), array('order_bn'=>$order_bn,'shop_id'=>$shop_id));
        }

        $msg = json_decode($result->get_result(), true);
        if($msg){
            $msg = serialize($msg);
        }else{
            $msg = $result->get_result();
        }
        # 返回结果中文提示
        $err_msg = $result->get_err_msg();
        if ($err_msg) {
            $msg .= '：'.$err_msg;
        }

        $ret = $this->callback($result);
        //增加订单状态回写
        $callback_params = $result->get_callback_params();


        $log_id = $callback_params['log_id'];
        $log = array('log_id' => $log_id, 'status' => $result->get_status(), 'updateTime' => time(), 'message' => $msg);

        $shipment_log = app::get('ome')->model('shipment_log');
        $shipment_log->save($log);

        $res = $shipment_log->dump(array('log_id' => $log_id), '*');

        if ($res) {

            $orderMdl = app::get('ome')->model('orders');
            $order = $orderMdl->dump(array('order_bn' => $res['orderBn'], 'shop_id' => $res['shopId']), '*');
            if ($order) {
                $order_id = $order['order_id'];
                if (trim($order['sync']) <> 'succ') {
                    $status = $result->get_status();
                } else {
                    $status = 'succ';
                }
                $sdf = array('order_id' => $order_id, 'sync' => $status, 'up_time' => time());

                //增加同步失败类型
                if($status != 'succ') {
                    $sync_code = $result->get_result();
                    $sync_code = trim($sync_code);
                    switch ($sync_code) {
                        case 'W90010':
                        case 'W90012':
                            $sdf['sync_fail_type'] = 'shipped';
                            break;
                        case 'W90011':
                        case 'W90013':
                        case 'W90014':
                            $sdf['sync_fail_type'] = 'params';
                            break;
                        default:
                            $sdf['sync_fail_type'] = 'none';
                            break;
                    }
                }

                $orderMdl->save($sdf);
            }
        }
        return $ret;

    }

    /**
     * 根据店铺类型输入不同的参数
     * @access public
     * @param $delivery_id  发货单BN
     * @param $shop_type 店铺类型
     * @param $order_id 订单ID
     * @param $parent_id 合并后的发货单ID
     * @param $delivery_type 发货类型 normal:正常发货, aftersale:售后补差订单发货
     * @return boolean
     */
    public function _get_shipping_params($delivery_id='', $order_id='', $parent_id='', $shop_type='',$addon=array(),$delivery_type='normal'){

        $orderObj = &app::get('ome')->model('orders');
        $shopObj = &app::get('ome')->model('shop');
        $b2b_list = ome_shop_type::b2b_shop_list();
        $c2c_shop_list = ome_shop_type::shop_list();
        $is_modify_title = false;//是否自定义日志标题

        switch ($delivery_type) {
            case 'aftersale'://暂时只针对C2C店铺做处理
                if (!in_array($shop_type, $c2c_shop_list)) {
                    return false;
                }

                $orderinfo = $orderObj->dump(array('order_id'=>$order_id),'order_bn,shop_id,is_delivery,mark_text,sync,ship_area,createway');
                $shop_id = $orderinfo['shop_id'];
                $shop_detail = $shopObj->dump($shop_id,'*');

                if ($shop_type=='ecos.b2c'){
                    $consignee_area = $orderinfo['consignee']['area'];
                }else{
                    $consignee_area = $shop_detail['area'];
                }

                kernel::single('eccommon_regions')->split_area($consignee_area);
                $receiver_state = ome_func::strip_bom(trim($consignee_area[0]));
                $receiver_city = ome_func::strip_bom(trim($consignee_area[1]));
                $receiver_district = ome_func::strip_bom(trim($consignee_area[2]));

                $dly_detail['type'] = 'OTHER';
                $delivery_detail['logi_no'] = $orderinfo['order_bn'];
                $delivery_detail['logi_name'] = $dly_detail['name'] = '其他物流公司';
                $is_modify_title = true;
                $modify_title = '店铺('.$shop_detail['name'].')添加[交易发货单](<font color="red">补差价</font>订单号:'
                   .$orderinfo['order_bn'].')';
            break;
            default:
                $deliveryObj = &app::get('ome')->model('delivery');
                $order_delivery = &app::get('ome')->model('delivery_order');
                $memberObj = &app::get('ome')->model('members');
                $dly_corpObj = &app::get('ome')->model('dly_corp');

                $delivery_detail = $deliveryObj->dump($delivery_id, '*');
                if ($parent_id){
                    $parent_delivery_detail = $deliveryObj->dump(array('delivery_id'=>$parent_id), '*');
                    $delivery_detail['status'] = $parent_delivery_detail['status'];
                    $delivery_detail['logi_id'] = $parent_delivery_detail['logi_id'];
                    $delivery_detail['logi_name'] = $parent_delivery_detail['logi_name'];
                    $delivery_detail['logi_no'] = $parent_delivery_detail['logi_no'];
                    $delivery_detail['logi_code'] = $parent_delivery_detail['logi_code'];
                }
                $ord_delivery_info = $order_delivery->dump(array('delivery_id'=>$delivery_id));

                //去除物流单号存在bom字符
                $pattrn = chr(239).chr(187).chr(191);
                $delivery_detail['logi_no'] = str_replace($pattrn, '', $delivery_detail['logi_no']);
                $delivery_detail['logi_no'] = trim($delivery_detail['logi_no']);

                if (!$order_id)
                $order_id = $ord_delivery_info['order_id'];
                $orderinfo = $orderObj->dump($order_id,'order_bn,shop_id,is_delivery,mark_text,sync,createway');
                $shop_id = $orderinfo['shop_id'];
                $shop_detail = $shopObj->dump($shop_id,'*');

                if ($shop_type=='ecos.b2c'){
                    $consignee_area = $delivery_detail['consignee']['area'];
                }else{
                    $consignee_area = $shop_detail['area'];
                }

                kernel::single('eccommon_regions')->split_area($consignee_area);
                $receiver_state = ome_func::strip_bom(trim($consignee_area[0]));
                $receiver_city = ome_func::strip_bom(trim($consignee_area[1]));
                $receiver_district = ome_func::strip_bom(trim($consignee_area[2]));

                // 物流公司信息
                $dly_detail = $dly_corpObj->dump(array('corp_id'=>$delivery_detail['logi_id']),'type,name');
                $is_normal_product = true;

                $is_deliveryitem = ome_shop_type::is_shop_deliveryitem($shop_type); #是否需要发货明细

                if($is_deliveryitem == 'on'){

                    $smemberObj = &app::get('ome')->model('shop_members');


                    if($shop_type == 'shopex_b2b'){
                        #针对B2B 同一货号 前端是普通商品，淘管是捆绑商品 发货处理
                        #如果存在捆绑商品 发货时取订单obj上的bn 回写前端,但有个缺陷 如果以后出现捆绑商品支持部分发货 就不行了。目前只是临时解决办法
                        $delivery_items_detailObj = &app::get('ome')->model('delivery_items_detail');
                        $order_objectsObj = &app::get('ome')->model('order_objects');

                        $delivery_items_details = $delivery_items_detailObj->getList('order_id',array('delivery_id'=>$delivery_id),0,1);

                        $order_objects = $order_objectsObj->getList('count(order_id) as _count',array('order_id'=>$delivery_items_details[0]['order_id'],'obj_type'=>'pkg'));

                        if($order_objects[0]['_count'] > 0){
                            $orders = $order_objectsObj->db->select('select oo.obj_id,oo.quantity as obj_number,oo.bn as obj_bn,oo.name as obj_name,oo.obj_type as obj_type,oi.bn as item_bn,oi.name as item_name,oi.sendnum as item_number from sdb_ome_order_items oi left join sdb_ome_order_objects oo on oi.obj_id = oo.obj_id where oi.delete = "false" and oo.order_id = '.$delivery_items_details[0]['order_id']);
                            $i = 0;
                            $is_pkg = array();

                            foreach((array)$orders as $k=>$v){
                                if($v['obj_type'] == 'pkg'){
                                    if(!isset($is_pkg[$v['obj_id']])){
                                        $develiyitems[$i]['number'] = $v['obj_number'];
                                        $develiyitems[$i]['name'] = trim($v['obj_name']);
                                        $develiyitems[$i]['bn'] = trim($v['obj_bn']);
                                        $is_pkg[$v['obj_id']] = true;
                                        $i++;
                                    }

                                }else{
                                    $develiyitems[$i]['number'] = $v['item_number'];
                                    $develiyitems[$i]['name'] = trim($v['item_name']);
                                    $develiyitems[$i]['bn'] = trim($v['item_bn']);
                                    $i++;
                                }

                            }
                            unset($is_pkg);
                            $is_normal_product = false;
                        }

                    }

                    if($is_normal_product){

                        $delivery_itemsObj = &app::get('ome')->model('delivery_items');
                        $develiyitems = $delivery_itemsObj->getList('product_name as name,bn,number',array("delivery_id"=>$delivery_id),0,-1);
                        //$develiyitems['sku_type'] = 'goods';
                        // 过滤发货单明细中的空格
                        foreach((array)$develiyitems as $k=>$v){
                            foreach($v as $kk=>$vv){
                                $v[$kk] = trim($vv);
                            }
                            $develiyitems[$k] = $v;
                        }
                    }

                    $memberinfo = $memberObj->dump($delivery_detail['member_id'],'uname,name');

                    if ($delivery_detail['last_modified']){
                        $modify = $delivery_detail['last_modified'];
                    }else{
                        $modify = time();
                    }
                }

            break;
        }

        //平台获取的订单如果回写时店铺解绑，直接将订单设为回写失败
        if(!$shop_detail['node_id'] && $orderinfo['createway']=='matrix'){
            $orderObj->update(array('sync'=>'fail','sync_fail_type'=>'unbind','up_time' => time()), array('order_id'=>$order_id));
        }

        switch ($shop_type){
            case 'shopex_b2b'://分销王b2b
            case 'shopex_b2c'://485
            case 'ecos.b2c'://ec store
            case 'ecos.dzg'://店掌柜
                $params = array(
                    'tid' => $orderinfo['order_bn'],
                    'shop_id' => $shop_id,
                    'shipping_fee' => $delivery_detail['delivery_cost_actual'] ? $delivery_detail['delivery_cost_actual'] :'',
                    'shipping_id' => $delivery_detail['delivery_bn'],
                    'create_time' => date("Y-m-d H:i:s",$delivery_detail['create_time']),
                    'is_protect' => $delivery_detail['is_protect'],
                    'is_cod' => $delivery_detail['is_cod'],
                    'buyer_id' => $memberinfo['account']['uname'],
                    'status' => $this->ship_status[$delivery_detail['status']],
                    'shipping_type' => $delivery_detail['delivery'] ? $delivery_detail['delivery'] : '',
                    'logistics_id' => $delivery_detail['logi_id'] ? $delivery_detail['logi_id'] : '',
                    'logistics_company' => $delivery_detail['logi_name'] ? $delivery_detail['logi_name'] : '',
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',
                    'logistics_code' => $dly_detail['type'],
                    'receiver_name' => $delivery_detail['consignee']['name'] ? $delivery_detail['consignee']['name'] : '',
                    'receiver_state' => $receiver_state ? $receiver_state : '',
                    'receiver_city' => $receiver_city ? $receiver_city : '',
                    'receiver_district' => $receiver_district ? $receiver_district : '',
                    'receiver_address' => $delivery_detail['consignee']['addr'] ? $delivery_detail['consignee']['addr'] :'',
                    'receiver_zip' => $delivery_detail['consignee']['zip']?$delivery_detail['consignee']['zip']:'',
                    'receiver_email' => $delivery_detail['consignee']['email']?$delivery_detail['consignee']['email']:'',
                    'receiver_mobile' => $delivery_detail['consignee']['mobile']?$delivery_detail['consignee']['mobile']:'',
                    'receiver_phone' => $delivery_detail['consignee']['telephone']?$delivery_detail['consignee']['telephone']:'',
                    'memo' => $delivery_detail['memo']?$delivery_detail['memo']:'',
                    't_begin' => date("Y-m-d H:i:s",$delivery_detail['create_time']),
                    'refund_operator' => kernel::single('desktop_user')->get_login_name(),
                    'shipping_items' => json_encode($develiyitems),
                    'ship_type' => 'delivery',
                    'modify' => date('Y-m-d H:i:s',$delivery_detail['last_modified']),
                );

                if($shop_type == 'shopex_b2b'){
                    $params['t_begin'] = $params['t_end'] = $params['modify'] = date('Y-m-d H:i:s',$delivery_detail['last_modified']);
                }

                if ( (!trim($delivery_detail['logi_no']) && !$parent_id) || in_array($shop_type, $b2b_list)){
                    $api_name = 'store.trade.shipping.add';
                }
                else{
                    //更新物流信息
                    foreach(kernel::servicelist('service.delivery') as $object=>$instance){
                        if(method_exists($instance,'update_logistics_info')){
                            $instance->update_logistics_info($delivery_id, $parent_id, true);
                        }
                    }
                    return false;
                }
                break;
            case 'paipai'://拍拍
                //订单备注
                $oldmemo = unserialize($orderinfo['mark_text']);
                if ($oldmemo)
                foreach($oldmemo as $k=>$v){
                    $memo = $v['op_content']."<br/>";
                }
                if ($receiver_district){
                    $receiver_district = '_'.$receiver_district;
                }
                $params = array(
                    'tid' => $orderinfo['order_bn'],
                    'shop_id' => $shop_id,
                    'send_type' => $this->delivery_type[$orderinfo['is_delivery']],
                    'logistics_code' => trim($dly_detail['type']),
                    'logistics_company' => $dly_detail['name']?$dly_detail['name']:'',
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',
                    'seller_name' => $shop_detail['default_sender'] ? $shop_detail['default_sender'] : '',
                    'seller_area_id' => $receiver_state.'_'.$receiver_city.$receiver_district,
                    'seller_address' => $shop_detail['addr'] ? $shop_detail['addr'] : '',
                    'seller_zip' => $shop_detail['zip'] ? $shop_detail['zip'] : '',
                    'seller_mobile' => $shop_detail['mobile'] ? $shop_detail['mobile'] : '',
                    'seller_phone' => $shop_detail['tel'] ? $shop_detail['tel'] : '',
                    'memo' => $memo ? $memo : '',
                );
                $api_name = 'store.trade.delivery.send';
                break;
            case 'qq_buy'://qq网购
                #发货
                $params = array(
                    'tid'          => $orderinfo['order_bn'],
                    'company_code' => $dly_detail['type'],
                    'company_name' => $delivery_detail['logi_name'] ? $delivery_detail['logi_name'] : '',
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',
                    'send_type' => $dly_detail['type'] == 'EMS' ? 3 : 1,
                );
                $api_name = 'store.logistics.offline.send';
                break;
            case 'youa'://有啊
            case 'taobao'://淘宝
                $params = array(
                    'tid' => $orderinfo['order_bn'],
                    'company_code' => trim($dly_detail['type']),
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',
                );
                //$api_name = 'store.logistics.offline.send';

                if(((app::get('ome')->getConf('ome.delivery.method'))=='on')&&($orderinfo['sync']=='none')){
                    $api_name = 'store.logistics.online.send';
                }else{
                    if($delivery_detail['is_cod'] == 'true'){
                        $api_name = 'store.logistics.online.send';
                    }else{
                        $api_name = 'store.logistics.offline.send';
                    }
                }

                break;
            case 'yintai':
                $params = array(
                    'tid' => $orderinfo['order_bn'],
                    'company_name' => $delivery_detail['logi_name'] ? $delivery_detail['logi_name'] : '',
                    'company_code' => trim($dly_detail['type']),
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',
                );
                $api_name = 'store.logistics.offline.send';

                break;
           case '360buy'://京东
                #发货
                $params = array(
                    'tid'          => $orderinfo['order_bn'],
                    //'shop_id'      => $shop_id,
                    'company_code' => $dly_detail['type'],
                    'company_name' => $delivery_detail['logi_name'] ? $delivery_detail['logi_name'] : '',
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',
                );
                $api_name = 'store.logistics.offline.send';
                //$this->deliveryGoods($delivery_id, $order_id);
                break;
           case 'yihaodian':
                //订单备注
                $oldmemo = unserialize($orderinfo['mark_text']);
                if ($oldmemo)
                foreach($oldmemo as $k=>$v){
                    $memo = $v['op_content']."<br/>";
                }
                if ($receiver_district){
                    $receiver_district = '_'.$receiver_district;
                }
                $params = array(
                    'tid' => $orderinfo['order_bn'],
                    //'shop_id' => $shop_id,
                    'company_code' => $dly_detail['type'],
                    'logistics_company' => $delivery_detail['logi_name'] ? $delivery_detail['logi_name'] : '',
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',
                );
                $api_name = 'store.logistics.offline.send';
                break;
            case 'dangdang':

                $item_list = array();
                $Oorder_items = app::get('ome')->model('order_items');
                $orderitems = $Oorder_items->getList('shop_goods_id,bn',array('order_id'=>$order_id));

                foreach ($orderitems as $v) {
                    $orderitem[$v['bn']] = $v['shop_goods_id'];
                }

                foreach ($develiyitems as $k=>$v) {
                    $item_list[$k]['oid'] = $orderinfo['order_bn'];
                    $item_list[$k]['itemId'] = $orderitem[$v['bn']];//取order_items上的商品ID
                    $item_list[$k]['num'] = $v['number'];
                }

                $params = array(
                    'tid' => $orderinfo['order_bn'],
                    'logisticstel' => '13612345678',//物流电话 - 当当物流电话固定写死
                    'company_name' => $delivery_detail['logi_name'] ? $delivery_detail['logi_name'] : '',//物流公司
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',//物流单号
                    'ship_date' => date('Y-m-d H:i:s',$delivery_detail['last_modified']),//发货时间
                    'item_list' => json_encode($item_list),//发货明细
                );
                $api_name = 'store.logistics.offline.send';
                break;
            case 'amazon':

                $item_list = array();
                $Oorder_items = app::get('ome')->model('order_items');
                $orderitems = $Oorder_items->getList('shop_goods_id,bn',array('order_id'=>$order_id));

                foreach ($orderitems as $v) {
                    $orderitem[$v['bn']] = $v['shop_goods_id'];
                }

                foreach ($develiyitems as $k=>$v) {
                    $item_list[$k]['oid'] = $orderinfo['order_bn'];
                    $item_list[$k]['itemId'] = $orderitem[$v['bn']];//取order_items上的商品ID
                    $item_list[$k]['num'] = $v['number'];
                }

                $params = array(
                    'tid' => $orderinfo['order_bn'],
                    'company_code' => $dly_detail['type'],
                    'logistics_company' => $delivery_detail['logi_name'] ? $delivery_detail['logi_name'] : '',
                    'logistics_no' => $delivery_detail['logi_no'] ? $delivery_detail['logi_no'] : '',
                    'item_list' => json_encode($item_list),//发货明细
                );
                $api_name = 'store.logistics.offline.send';
                break;
        }

        if($shop_id){
            $shop_info = $shopObj->dump($shop_id,'name');
            if ($is_modify_title) {
                $title = $modify_title;
            }else{
                $title = '店铺('.$shop_info['name'].')添加[交易发货单](订单号:'
                    .$orderinfo['order_bn'].',发货单号:'.$delivery_detail['delivery_bn'].')';
            }
        }else{
            return false;
        }

        $opInfo = kernel::single('ome_func')->getDesktopUser();

        //增加更新发货状态日志
        $log = array(
            'shopId' => $shop_id,
            'ownerId' => $opInfo['op_id'],
            'orderBn' => $orderinfo['order_bn'],
            'deliveryCode' => $delivery_detail['logi_no'],
            'deliveryCropCode' => $dly_detail['type'],
            'deliveryCropName' => $delivery_detail['logi_name'],
            'receiveTime' => time(),
            'status' => 'send',
            'updateTime' => '0',
            'message' => '');


        if ($shop_type == '360buy') {

            if ($is_modify_title) {
                $title = $modify_title;
            }else{
                $title = '店铺('.$shop_info['name'].')[京东360BUY]添加[交易发货单](订单号:'
                   .$orderinfo['order_bn'].',发货单号:'.$delivery_detail['delivery_bn'].')';
            }

            //京东临时使用实时接口
            $result = kernel::single('ome_rpc_request')->call($api_name, $params, $shop_id);
            //接口日志生成
            $oApi_log = &app::get('ome')->model('api_log');
            $log_id = $oApi_log->gen_id();
            $callback = array(
                                'class'   => 'ome_rpc_request_shipping',
                                'method'  => 'add',
                                '2'       => array(
                                    'log_id'  => $log_id,
                                    'shop_id' => $shop_id,
                                ),
            );
            $oApi_log->write_log($log_id,$title,'ome_rpc_request','rpc_request',array($api_name, $params, $callback),'','request','running','','','api.store.trade.delivery',$orderinfo['order_bn']);

            if($result){
                if($result->rsp == 'succ'){
                    //发货日志记录
                    $log['status']  = 'succ';
                    $log['updateTime']  = time();
                    $log['message'] = $result->data;
                    //api日志记录
                    $api_status = 'success';
                    $msg = '发货成功<BR>';
                    $oApi_log->update(array('msg_id'=>$result->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
                    //订单回写状态
                    $status = 'succ';
                }elseif($result->rsp == 'fail'){
                    if($result->res =='w06105'){
                        //发货日志记录
                        $log['status']  = 'succ';
                        $log['updateTime']  = time();
                        $log['message'] = '';
                        //api日志记录
                        $api_status = 'success';
                        $msg = '发货成功('.$this->jdErrorMsg($result->res).')<BR>';
                        $oApi_log->update(array('msg_id'=>$result->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
                        //订单回写状态
                        $status = 'succ';
                    }else{
                        //发货日志记录
                        $log['status']  = 'fail';
                        $log['updateTime']  = time();
                        $log['message'] = $result->data;
                        //api日志记录
                        $api_status = 'fail';
                        $err_msg = $result->err_msg ? $result->err_msg : $this->jdErrorMsg($result->res);
                        $msg = '发货失败('.$err_msg.')<BR>';
                        $oApi_log->update(array('msg_id'=>$result->msg_id,'msg'=>$msg,'status'=>$api_status),array('log_id'=>$log_id));
                        //订单回写状态
                        $status = 'fail';
                    }
                }
            }else{
                return false;
            }
        } else {
            $callback = array(
               'class' => 'ome_rpc_request_shipping',
               'method' => 'shipping_add_callback',
            );

            $addon['bn'] = $delivery_detail['delivery_bn'];


            $log_id = $this->request($api_name,$params,$callback,$title,$shop_id,10,false,$addon);

             if (empty($log_id) || $log_id['rsp'] == 'fail') {
                return ;
            }

            $status = 'run';
        }

        $log['log_id'] = $log_id;
         //保存日志
        $shipment_log = app::get('ome')->model('shipment_log');
        $result = $shipment_log->save($log);
        //更新订单状态
        $sdf = array('order_id'=>$order_id, 'sync' => $status);
        app::get('ome')->model('orders')->save($sdf);
    }


    /**
     * 更新交易发货状态
     * 注意：发货单打回也需要更新状态
     * @param $delivery_id
     * @param $status
     * @param boolean $queue true：进队列  false：立即发起
     */
    function status_update($delivery_id,$status='',$queue=false){

           if(!empty($delivery_id)){
            $deliveryObj = &app::get('ome')->model('delivery');
            $delivery_oObj = &app::get('ome')->model('delivery_order');
            $orderObj = &app::get('ome')->model('orders');

            $delivery_detail = $deliveryObj->dump(array('delivery_id'=>$delivery_id), 'delivery_bn,shop_id,is_bind,status,parent_id');

            if($delivery_detail['is_bind'] == 'true'){
                $delivery_ids = $deliveryObj->getItemsByParentId($delivery_id,'array');
                if ($delivery_ids)
                foreach($delivery_ids as $v){
                    $this->status_update($v);
                }
            }else{

                $dlyOrder = $delivery_oObj->dump(array('delivery_id'=>$delivery_id), 'order_id');
                $order = $orderObj->dump($dlyOrder['order_id'], 'order_bn');

                if (!$status){
                    //如果是合并后的发货单，发货单状态为合并后的发货单状态
                    if($delivery_detail['parent_id']>0)
                    {
                        $parent_delivery_detail = $deliveryObj->dump(array('delivery_id'=>$delivery_detail['parent_id']), 'status');
                        $delivery_detail['status'] = $parent_delivery_detail['status'];
                    }
                }else{
                    $delivery_detail['status'] = $status;
                }

                $params['tid'] = $order['order_bn'];
                $params['shipping_id'] = $delivery_detail['delivery_bn'];
                $params['status'] = $this->ship_status[$delivery_detail['status']];

                $callback = array(
                    'class' => 'ome_rpc_request_shipping',
                    'method' => 'shipping_status_update_callback',
                );

                $shop_id = $delivery_detail['shop_id'];
                if($shop_id){
                    $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                    $title = '店铺('.$shop_info['name'].')更新交易发货单状态['.$params['status'].'],订单号:'.$order['order_bn'].'发货单号:'.$delivery_detail['delivery_bn'].')';
                }else{
                    return false;
                }
                $api_name = 'store.trade.shipping.status.update';

                if ($params['status']){

                    $addon['bn'] = $delivery_detail['delivery_bn'];

                    $this->request($api_name,$params,$callback,$title,$shop_id,10,false,$addon);
                }

            }
        }else{
            return false;
        }
    }

    function shipping_status_update_callback($result){
        return $this->callback($result);
    }

    /**
     * 更改发货物流信息
     * @access public
     * @param int $delivery_id 发货单主键ID
     * @param int $parent_id 支付单主键ID
     * @param boolean $queue true：进队列  false：立即发起
     * @return boolean
     */
    public function logistics_update($delivery_id,$parent_id='',$queue=false){

        if(!empty($delivery_id)){
            $deliveryObj = &app::get('ome')->model('delivery');
            $delivery_oObj = &app::get('ome')->model('delivery_order');
            $orderObj = &app::get('ome')->model('orders');
            $shopObj = &app::get('ome')->model('shop');
            $dly_corpObj = &app::get('ome')->model('dly_corp');

            $delivery_detail = $deliveryObj->dump(array('delivery_id'=>$delivery_id), '*');
            //如果发现当前请求是合并后的发货单编辑详情后触发的，直接返回，因为前端没有合并后的发货单。
            if($delivery_detail['is_bind'] == 'true'){
                return false;
            }

            $dlyOrder = $delivery_oObj->dump(array('delivery_id'=>$delivery_id));
            $order = $orderObj->dump($dlyOrder['order_id'], 'order_bn');

            $params['tid'] = $order['order_bn'];
            $params['shipping_id'] = $delivery_detail['delivery_bn'];

            $parent_id = $delivery_detail['parent_id'];
            //如果是合并后的发货单，发货单状态为合并后的发货单状态
            if($parent_id>0)
            {
                $parent_delivery_detail = $deliveryObj->dump(array('delivery_id'=>$parent_id), 'logi_name,logi_no,logi_id');
                $delivery_detail['logi_name'] = $parent_delivery_detail['logi_name'];
                $delivery_detail['logi_no'] = $parent_delivery_detail['logi_no'];
                $delivery_detail['logi_id'] = $parent_delivery_detail['logi_id'];
            }
            // 物流公司信息
            $dly_detail = $dly_corpObj->dump(array('corp_id'=>$delivery_detail['logi_id']),'type,name');

            $params['logistics_code'] = $dly_detail['type']?$dly_detail['type']:'';
            $params['logistics_company'] = $delivery_detail['logi_name']?$delivery_detail['logi_name']:'';
            $params['logistics_no'] = $delivery_detail['logi_no']?$delivery_detail['logi_no']:'';

            $callback = array(
                'class' => 'ome_rpc_request_shipping',
                'method' => 'logistics_update_callback',
            );

            $shop_id = $delivery_detail['shop_id'];
            //排除发送给网店端
            $shop_detail = $shopObj->dump($shop_id, 'node_type');
            $foreground_shop_list = ome_shop_type::shop_list();
            if (in_array($shop_detail['node_type'],$foreground_shop_list)) return false;

            if($shop_id){
                $shop_info = &app::get('ome')->model('shop')->dump($shop_id,'name');
                $title = '店铺('.$shop_info['name'].')更改[发货物流信息](订单号:'.$order['order_bn'].',物流单号:'.$params['logistics_no'].',发货单号:'.$delivery_detail['delivery_bn'].')';
            }else{
                return false;
            }

            $addon['bn'] = $delivery_detail['delivery_bn'];

            $this->request('store.trade.shipping.update',$params,$callback,$title,$shop_id,10,false,$addon);
        }else{
            return false;
        }
    }

    function logistics_update_callback($result){
        // 更新运单号
        //$oApi_log = app::get('ome')->model('api_log');
        $callback_params = $result->get_callback_params();
        $request_params = $result->get_request_params();

        //$log_id = $callback_params['log_id'];
        //$apilog_detail = $oApi_log->dump(array('log_id'=>$log_id), 'params');
        //$apilog_detail = unserialize($apilog_detail['params']);
        //$apilog_detail = $request_params;

        $order_bn = $request_params['tid'];
        $shop_id = $callback_params['shop_id'];

        $shipment_log = app::get('ome')->model('shipment_log');
        $filter = array(
            'shopId' => $shop_id,
            'orderBn' => $order_bn,
        );
        $data = array(
            'deliveryCode' => $request_params['logistics_no'],
            'deliveryCropName' => $request_params['logistics_company'],
            'deliveryCropCode' => $request_params['logistics_code'],
        );
        $shipment_log->update($data,$filter);

        return $this->callback($result);
    }

    /**
     * @根据矩阵返回的错误码，表述具体京东请求的返回消息内容
     * @access public
     * @param void
     * @return void
     */
    public function jdErrorMsg($code)
    {
        $errormsgs = array(
                    'w06000'=>'成功',
                    'w06001'=>'其他',
                    'w06101'=>'已经出库',
                    'w06102'=>'出库订单不存在或已被删除',
                    'w06104'=>'订单状态不为等待发货',
                    'w06105'=>'订单已经发货',
                    'w06106'=>'正在出库中',
        );
        return isset($errormsgs[$code]) ? $errormsgs[$code] : '其他';
    }
}