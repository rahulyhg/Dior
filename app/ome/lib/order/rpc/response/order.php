<?php
/**
 * 订单接口业务逻辑处理
 * @access public
 * @author ome
 * @copyright www.shopex.cn 2010.12.28
 */
class ome_order_rpc_response_order{

    /**
     * 订单数据业务逻辑过滤
     * @access static
     * @param array $order_filter 订单过滤数据
     * @param
     */
    public static function order_filter($order_filter){

        $order_bn = $order_filter['order_bn'];
        $shop_id = $order_filter['shop_id'];
        $shop_name = $order_filter['shop_name'];
        $shop_type = $order_filter['shop_type'];
        $status = $order_filter['status'];
        $pay_status = $order_filter['pay_status'];
        $ship_status = $order_filter['ship_status'];
		$is_cod = $order_filter['is_cod'];
        $consignee = $order_filter['consignee'];
        $log_title = "接收店铺({$shop_name})的订单:".$order_bn;
        $request_class = 'ome_rpc_response_order';
        $request_method = 'add';
        //前端C2C店铺类型
        $c2c_shop_list = ome_shop_type::shop_list();
        //过滤结果初始值
        $filter_result = array('status'=>true, 'rsp'=>'succ', 'res'=>'');

        //实例化数据模型
        $oApi_log = &app::get('ome')->model('api_log');

        //订单order_bn为空
        if (empty($order_bn)){
            $msg = 'Order NO can not be empty';
            $log_id = $oApi_log->gen_id();
            $oApi_log->write_log($log_id,$log_title,$request_class,$request_method,'','','response','fail',$msg,'','api.store.trade',$order_bn);
            $filter_result = array(
                'status' => false,
                'rsp' => 'fail',
                'res' => $msg,
            );
            return $filter_result;
        }
        //订单存在失败订单当中
        if(app::get('omeapilog')->is_installed()){
            $apilogOrderObj = &app::get('omeapilog')->model('orders');
            $apilogOrderInfo = $apilogOrderObj->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,order_bn,mark_text');
            if($apilogOrderInfo['order_bn']){
                $filter_result = array(
                    'status'=>false,
                    'rsp'=>'succ',
                );
                return $filter_result;
            }
        }
        //拒绝未支付并且是款到发货的订单
        if (in_array($shop_type, $c2c_shop_list)){
            if ( $is_cod == 'false' && $pay_status=='0'){
                $filter_result = array(
                    'status'=>false,
                    'rsp'=>'succ',
                );
                return $filter_result;
            }
        }
        //jingjiu江浙沪皖订单过滤2012-6-5
        /*if (!self::filter_special_order($consignee['area_state'])){
            $filter_result = array(
                'status'=>false,
                'rsp'=>'succ',
            );
            return $filter_result;
        }*/
        //拒绝关闭订单
        if ($status != 'active'){
            if ($status == 'close'){
                $msg = 'order:'.$order_bn.' has been closed';
                $log_title = '店铺('.$shop_name.')订单'.$order_bn . '已关闭';
            }else{
                $msg = 'order:'.$order_bn.' has been finished';
                $log_title = '店铺('.$shop_name.')订单'.$order_bn . '已完成';
            }
            //日志记录
            /*
            $api_filter = array('marking_value'=>$order_bn,'marking_type'=>'order_close');
            $api_detail = $oApi_log->dump($api_filter, 'log_id');
            if (empty($api_detail['log_id'])){
                $addon = $api_filter;
                $log_id = $oApi_log->gen_id();
                $oApi_log->write_log($log_id,$log_title,$request_class,$request_method,'','','response','fail',$msg,$addon,'api.store.trade',$order_bn);
            }
            */
            $filter_result = array(
                'status' => false,
                'rsp' => 'fail',
                'res' => $msg,
            );
            return $filter_result;
        }
        //拒绝已发货订单
        if ($ship_status != '0'){
            $msg = 'order '.$order_bn.' has been shipped';
            //日志记录
            /*
            $api_filter = array('marking_value'=>$order_bn,'marking_type'=>'order_delivery');
            $api_detail = $oApi_log->dump($api_filter, 'log_id');
            if (empty($api_detail['log_id'])){
                $log_title = '店铺('.$shop_name.')订单'.$order_bn.'已发货';
                $addon = $api_filter;
                $log_id = $oApi_log->gen_id();
                $oApi_log->write_log($log_id,$log_title,$request_class,$request_method,'','','response','fail',$msg,$addon,'api.store.trade',$order_bn);
            }
            */
            $filter_result = array(
                'status' => false,
                'rsp' => 'fail',
                'res' => $msg,
            );
            return $filter_result;
        }
        return $filter_result;
    }

    /**
     * 将API订单数据转换成本地标准的SDF订单结构数据
     * @access public
     * @param array $order_sdf API订单结构数据
     * @return array 本地标准的sdf结构
     */
    public function order_sdf_convert($order_sdf){

        $shop_id = $order_sdf['shop_detail']['shop_id'];
        $shop_type = $order_sdf['shop_detail']['node_type'];
        $c2c_shop_list = ome_shop_type::shop_list();
        if (in_array($shop_type, $c2c_shop_list)){
            //淘宝订单优惠、折扣和商品优惠金额转为正数
            $order_sdf['pmt_goods'] = abs($order_sdf['pmt_goods']);
            $order_sdf['pmt_order'] = abs($order_sdf['pmt_order']);
        }
        //买家留言
        $custom_memo = $order_sdf['custom_mark'];
        if(in_array($shop_type,array('taobao','paipai')) && app::get('ome')->getConf('ome.checkems') =='true'){
            $tmp['shipping'] = json_decode($order_sdf['shipping'],true);
            if (strtolower(trim($tmp['shipping']['shipping_name'])) == 'ems') {
                $custom_memo = empty($custom_memo) ? "系统：用户选择了 EMS 的配送方式" : "{$custom_memo}\n系统：用户选择了 EMS 的配送方式";
            }
        }
        if ($custom_memo){
            $custommemo[] = array('op_name'=>$shop_type, 'op_time'=>date("Y-m-d H:i:s",time()), 'op_content'=>htmlspecialchars($custom_memo));
            $order_sdf['custom_mark'] = serialize($custommemo);
        }
        //订单备注
        $mark_memo = $order_sdf['mark_text'];
        if ($mark_memo){
            $markmemo[] = array('op_name'=>$shop_type, 'op_time'=>date("Y-m-d H:i:s",time()), 'op_content'=>htmlspecialchars($mark_memo));
            $order_sdf['mark_text'] = serialize($markmemo);
        }
        //配送信息
        $order_sdf['shipping'] = json_decode($order_sdf['shipping'],true);
        if (!empty($order_sdf['shipping'])){
            $order_sdf['shipping']['cost_shipping'] =  $order_sdf['shipping']['cost_shipping']?$order_sdf['shipping']['cost_shipping']:0.00;
            $order_sdf['shipping']['is_protect'] =  $order_sdf['shipping']['is_protect']?$order_sdf['shipping']['is_protect']:'false';
            $order_sdf['shipping']['cost_protect'] =  $order_sdf['shipping']['cost_protect']?$order_sdf['shipping']['cost_protect']:0.00;
            $order_sdf['shipping']['is_cod'] =  $order_sdf['shipping']['is_cod']?$order_sdf['shipping']['is_cod']:'false';
        }
        $order_sdf['shop_id'] = $shop_id;
        $order_sdf['shop_type'] = $shop_type;
        $order_sdf['is_delivery'] = $order_sdf['is_delivery'] ? $order_sdf['is_delivery'] : 'Y';
        $order_sdf['cost_item'] = $order_sdf['cost_item']?$order_sdf['cost_item']:0.00;
        $order_sdf['is_tax'] = $order_sdf['is_tax']?$order_sdf['is_tax']:'false';
        $order_sdf['cost_tax'] = $order_sdf['cost_tax']?$order_sdf['cost_tax']:0.00;
        $order_sdf['discount'] = $order_sdf['discount']?$order_sdf['discount']:0.00;
        $order_sdf['total_amount'] = $order_sdf['total_amount']?$order_sdf['total_amount']:0.00;
        $order_sdf['pmt_goods'] = $order_sdf['pmt_goods'] ? $order_sdf['pmt_goods'] : 0.00;
        $order_sdf['pmt_order'] = $order_sdf['pmt_order'] ? $order_sdf['pmt_order'] : 0.00;
        $order_sdf['payed'] = $order_sdf['payed']?$order_sdf['payed']:0.00;
        $order_sdf['cur_amount'] = !empty($order_sdf['cur_amount'])?$order_sdf['cur_amount']:0.00;
        $order_sdf['score_u'] = !empty($order_sdf['score_u'])?$order_sdf['score_u']:0.00;
        $order_sdf['score_g'] = !empty($order_sdf['score_g'])?$order_sdf['score_g']:0.00;
        $order_sdf['currency'] = !empty($order_sdf['currency'])?$order_sdf['currency']:'CNY';

        $order_sdf['source'] = 'matrix';
        $order_sdf['createtime'] = kernel::single('ome_func')->date2time($order_sdf['createtime']);
        $order_sdf['download_time'] = time();
        //收货人信息
        $order_sdf['consignee'] = json_decode($order_sdf['consignee'],true);
        if (!empty($order_sdf['consignee'])){
            $order_sdf['consignee']['area'] = $order_sdf['consignee']['area_state'].'/'.$order_sdf['consignee']['area_city'].'/'.$order_sdf['consignee']['area_district'];
        }
        //收货人名称去两端空格 fix by danny
        $order_sdf['consignee']['name'] = trim($order_sdf['consignee']['name']);

        //发货人信息
        $order_sdf['consigner'] = json_decode($order_sdf['consigner'],true);
        if (!empty($order_sdf['consigner'])){
            $order_sdf['consigner']['area'] = $order_sdf['consigner']['area_state'].'/'.$order_sdf['consigner']['area_city'].'/'.$order_sdf['consigner']['area_district'];
        }
        $order_sdf['consigner']['tel'] = $order_sdf['consigner']['telephone'];
        $order_sdf['payinfo'] = json_decode($order_sdf['payinfo'],true);
        //设置订单失败时间
        if (empty($order_sdf['order_limit_time'])){
            $order_sdf['order_limit_time'] = time() + 60*(app::get('ome')->getConf('ome.order.failtime'));
        }
        return $order_sdf;
    }


    /**
     * 更新订单备注和买家留言
     *
     * @access public
     * @param object $orderObj 订单号
     * @param string $shop_id 前端店铺ID
     * @param string $shop_type 前端店铺类型
     * @param array $mark_memo 订单买家留言
     * @param array $mark_text 订单备注
     * @return 成功或失败
     */
    public function update_custom_mark($order_bn='',$shop_id='',$shop_type='',$mark_memo='',$mark_text=''){

        $mark_memo = trim($mark_memo);
        $mark_text = trim($mark_text);
        if (empty($mark_memo) && empty($mark_text)) return false;
        $orderObj = &app::get('ome')->model('orders');
        $order_detail = $orderObj->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id), 'order_id,mark_text,custom_mark');

        $order_id = $order_detail['order_id'];
        if($order_id){
            //更新订单备注
            if ($mark_memo){
                $newmemo = array();
                if ($order_detail['mark_text']){
                    $newmemo = unserialize($order_detail['mark_text']);
                }
                $allow_update = true;
                $same_memo = false;
                if ($newmemo){
                    foreach($newmemo as $k=>$v){
                        if ($v['op_name'] == $shop_type){
                            if (trim($v['op_content']) == $mark_memo){
                                $allow_update = false;
                                break;
                            }
                        }
                    }
                }
                if ($allow_update == true){
                    if ($same_memo == false){
                        $newmemo[] = array('op_name'=>$shop_type,'op_time'=>time(),'op_content'=>$mark_memo);
                    }
                    $newmemo = serialize($newmemo);
                    $update_status = $orderObj->update(array('mark_text'=>$newmemo), array('order_id'=>$order_id));
                    return $update_status;
                }
            }
            //更新买家留言
            if ($mark_text){
                $newmemo = array();
                if ($order_detail['custom_mark']){
                    $newmemo = unserialize($order_detail['custom_mark']);
                }
                $allow_update = true;
                $same_memo = false;
                if ($newmemo){
                    foreach($newmemo as $k=>$v){
                        if ($v['op_name'] == $shop_type){
                            if (trim($v['op_content']) == $mark_text){
                                $allow_update = false;
                                break;
                            }
                        }
                    }
                }
                if ($allow_update == true){
                    if ($same_memo == false){
                        $newmemo[] = array('op_name'=>$shop_type,'op_time'=>time(),'op_content'=>$mark_text);
                    }
                    $newmemo = serialize($newmemo);
                    $orderObj->update(array('custom_mark'=>$newmemo), array('order_id'=>$order_id));
                }
            }
        }
        return true;
    }

    /**
     * 订单商品明细规则处理
     * @param string $order_bn 订单号
     * @param string $shop_id 店铺ID
     * @param string $shop_name 店铺名称
     * @param array $order_objects 订单明细数据
     * @param object $responseObj RPC基类对象引用
     * @return 货号过滤状态
     */
    public static function order_objects_filter($order_bn='',$shop_id='',&$order_objects,&$responseObj){

        if (empty($order_objects)) return false;

        $oGoods = &app::get('ome')->model('goods');
        $oApi_log = &app::get('ome')->model('api_log');
        $shopObj = &app::get('ome')->model('shop');
        $shop_detail = $shopObj->dump(array('shop_id'=>$shop_id), 'name');
        $shop_name = $shop_detail['name'];
        $return_value = array('tid'=>$order_bn);

        $bn_exists = array();
        $new_order_objects = $order_objects;
        foreach($new_order_objects as $key=>$object){
            //子订单
            if($object['bn']){
                $goods_info = $oGoods->dump(array('bn'=>$object['bn']),"goods_id");
                if($goods_info){
                    $order_objects[$key]['goods_id'] = $goods_info['goods_id'];
                }
            }
            $order_objects[$key]['obj_type'] = $object['obj_type']?$object['obj_type']:'goods';
            $order_objects[$key]['shop_goods_id'] = $object['shop_goods_id']?$object['shop_goods_id']:0;
            $order_objects[$key]['price'] = $object['price']?$object['price']:0.00;
            $order_objects[$key]['weight'] = $object['weight']?$object['weight']:0.00;
            $order_objects[$key]['amount'] = $object['amount']?$object['amount']:0.00;
            $order_objects[$key]['quantity'] = $object['quantity']?$object['quantity']:0;
            $order_objects[$key]['bn'] = $object['bn']?$object['bn']:null;
            //增加pmt_price sale_price
            $order_objects[$key]['pmt_price'] = $object['pmt_price']?$object['pmt_price']:0.00;
            $order_objects[$key]['sale_price'] = $object['sale_price']?$object['sale_price']:0.00;
            //商品明细
            $items = $object['order_items'];
            foreach($items as $k=>$item){
                //判断货号是否存在
                $sql = "SELECT `product_id` FROM `sdb_ome_products` WHERE `bn`='".trim($item['bn'])."' ";
                $product_info = kernel::database()->selectrow($sql);
                $product_status = false;
                if(empty($product_info)){
                    foreach(kernel::servicelist('ome.product') as $name=>$object){
                        if(method_exists($object, 'getProductByBn')){
                            $product_info = $object->getProductByBn($item['bn']);
                            if(!empty($product_info)){
                                $product_status = true;
                                break;
                            }
                        }
                    }
                }else{
                    $product_status = true;
                }
                $bn_exists[] = array('bn'=>$item['bn'],'status'=>$product_status);
                //商品状态
                $item_status = 'false';
                if ($item['status'] == 'close' ){
                    $item_status = 'true';
                }
                //货号规格属性
                if ($item['product_attr']){
                    $product_attr['product_attr'] = $item['product_attr'];
                    if($item['original_str']) $product_attr['product_attr'][0] = $item['original_str'];
                    $order_objects[$key]['order_items'][$k]['addon'] = serialize($product_attr);
                }
                $order_objects[$key]['order_items'][$k]['delete'] = $item_status;
                $order_objects[$key]['order_items'][$k]['product_id'] = $product_info['product_id']?$product_info['product_id']:0;
                $order_objects[$key]['order_items'][$k]['shop_goods_id'] = $item['shop_goods_id']?$item['shop_goods_id']:0;
                $order_objects[$key]['order_items'][$k]['shop_product_id'] = $item['shop_product_id']?$item['shop_product_id']:0;
                $order_objects[$key]['order_items'][$k]['price'] = $item['price']?$item['price']:0.00;
                //增加pmt_price sale_price
                $order_objects[$key]['order_items'][$k]['pmt_price'] = $item['pmt_price']?$item['pmt_price']:0.00;
                $order_objects[$key]['order_items'][$k]['sale_price'] = $item['sale_price']?$item['sale_price']:0.00;
                $order_objects[$key]['order_items'][$k]['amount'] = $item['amount']?$item['amount']:0.00;
                $order_objects[$key]['order_items'][$k]['quantity'] = $item['quantity']?$item['quantity']:1;
                $order_objects[$key]['order_items'][$k]['sendnum'] = $item['sendnum']?$item['sendnum']:0;
                $order_objects[$key]['order_items'][$k]['item_type'] = trim($item['item_type'])?$item['item_type']:'product';
            }
        }
        //获取bn是否存在状态
        if ($bn_exists){
            foreach ($bn_exists as $bn_status){
                if (!$bn_status['status']){
                    $product_status = false;
                    break;
                }
            }
        }
        return $product_status;
    }

    /**
     * 增加订单优惠方案
     * @param string $order_id 订单号
     * @param array $pmt_detail 优惠方案详情
     * @return true or false
     */
    public function add_order_pmt($order_id,$pmt_detail=''){
        if (empty($pmt_detail) || empty($order_id)) return false;
        $pmtObj = &app::get('ome')->model('order_pmt');
        foreach ($pmt_detail as $k=>$v){
            $pmt_sdf = array(
                'order_id' => $order_id,
                'pmt_amount' => $v['pmt_amount'],
                'pmt_describe' => $v['pmt_describe'],
            );
            $pmtObj->save($pmt_sdf);
        }
        return true;
    }

    /**
     * 更新代销人信息
     * @param string $order_id 订单ID
     * @param array $selling_agent_sdf 代销人会员信息
     * @return ture or false
     */
    public function update_selling_agent_info($order_id='',$selling_agent_sdf=''){

        $uname = $selling_agent_sdf['member_info']['uname'];
        if (empty($order_id) || empty($uname)) return false;
        $sellagentObj = &app::get('ome')->model('order_selling_agent');
        if ($selling_agent_sdf['member_info']['area']){
            $area = $selling_agent_sdf['member_info']['area'];
        }else{
            $area = $selling_agent_sdf['member_info']['area_state'].'/'.$selling_agent_sdf['member_info']['area_city'].'/'.$selling_agent_sdf['member_info']['area_district'];
        }
        kernel::single('eccommon_regions')->region_validate($area);
        $sellagent_detail = $sellagentObj->dump(array('order_id'=>$order_id), 'selling_agent_id');
        $sellagent_data = array(
            'order_id' => $order_id,
            'uname' => $uname,
            'level' => $selling_agent_sdf['member_info']['level'],
            'name' => $selling_agent_sdf['member_info']['name'],
            'birthday' => $selling_agent_sdf['member_info']['birthday'],
            'sex' => $selling_agent_sdf['member_info']['sex']=='male' ? 'male' : 'female',
            'email' => $selling_agent_sdf['member_info']['email'],
            'area' => $area,
            'addr' => $selling_agent_sdf['member_info']['addr'],
            'zip' => $selling_agent_sdf['member_info']['zip'],
            'qq' => $selling_agent_sdf['member_info']['qq'],
            'mobile' => $selling_agent_sdf['member_info']['mobile'],
            'tel' => $selling_agent_sdf['member_info']['telephone'],
            'website_name' => $selling_agent_sdf['website']['name'],
            'website_domain' => $selling_agent_sdf['website']['domain'],
            'website_logo' => $selling_agent_sdf['website']['logo'],
        );
        $sellagent_id = $sellagent_detail['selling_agent_id'];
        if ($sellagent_id){
            //更新
            $sellagent_filter = array('selling_agent_id'=>$sellagent_id);
            $result = $sellagentObj->update($sellagent_data, $sellagent_filter);
        }else{
            //添加
            $result = $sellagentObj->insert($sellagent_data);
        }
        return $result;
    }

    /**
     * jingjiu江浙沪皖订单过滤
     *
     * @param  $consignee
     * @return bool
     **/
    public static function filter_special_order($consignee)
    {
        $url = kernel::base_url(1);
        $filter_url = array('http://jinjiu-chuchengjiu.xyt-erp.taoex.com','http://bugfix.gs.taoshopex.com');

        $city = trim(preg_replace('/省|市/', '', $consignee));
        $filter_city = array('江苏','上海','浙江','安徽');

        if (in_array($url, $filter_url) && in_array($city, $filter_city)) {
            return false;
        }else{
            return true;
        }

    }

}