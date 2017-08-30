<?php
/**
 * 订单业务同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_version_base_order extends ome_rpc_request {

    //订单状态
    var $status = array(
        'active' => 'TRADE_ACTIVE',
        'dead' => 'TRADE_CLOSED',
        'finish' => 'TRADE_FINISHED',
    );
    //订单暂停状态
    var $pause_status = array(
        'true' => 'TRADE_PENDING',//暂停
        'false' => 'TRADE_ACTIVE',//恢复
    );
    //订单状态名称
    var $status_name = array(
        'active' => '活动',
        'dead' => '取消',
        'finish' => '完成',
        'pause:true' => '暂停',
        'pause:false' => '恢复',
    );
    //订单支付状态
    var $pay_status = array(
        '0' => 'PAY_NO',
        '1' => 'PAY_FINISH',
        '2' => 'PAY_TO_MEDIUM',
        '3' => 'PAY_PART',
        '4' => 'REFUND_PART',
        '5' => 'REFUND_ALL',
    );
    //订单发货状态
    var $ship_status = array(
        '0' => 'SHIP_NO',
        '1' => 'SHIP_FINISH',
        '2' => 'SHIP_PART',
        '3' => 'RESHIP_PART',
        '4' => 'RESHIP_ALL',
    );
    //订单旗标(b0:灰色  b1:红色  b2:橙色  b3:黄色  b4:蓝色  b5:紫色)
    var $mark_type = array(
        'b0' => '0',
        'b1' => '1',
        'b2' => '2',
        'b3' => '3',
        'b4' => '4',
        'b5' => '5',
    );
    //订单类型。可选值:goods(商品),gift(赠品)。默认为goods
    var $obj_type = array(
        'goods' => 'goods',
        'gift' => 'gift',
    );
    //货品状态:默认为false（正常）,true：取消
    var $product_status = array(
        'false' => 'normal',
        'true' => 'cancel',
    );

    /**
     * 订单编辑 接口
     * @access public
     * @param Array $sdf 订单结构
     * @return boolean
     */
    public function update_order($sdf=''){
        $order_id = $sdf['order_id'];
        if(!empty($order_id)){

            $orderObj = &app::get('ome')->model('orders');
            $productsObj = &app::get('ome')->model('products');
            $membersObj = &app::get('ome')->model('members');
            $shopObj = &app::get('ome')->model('shop');
            $specificationObj = &app::get('ome')->model('specification');
            $pmtObj = &app::get('ome')->model('order_pmt');
            $order = $orderObj->dump($order_id, '*', array('order_objects'=>array('*',array('order_items'=>array('*')))));

            //-- 子订单信息
            $gzip = false;
            $max_orderitems = ome_order_func::get_max_orderitems();
            $object_key = 0;
            $order_items_num = 0;
            if ($order['order_objects']){
                foreach ($order['order_objects'] as $objects){
                    $order_objects['order'][$object_key] = array(
                        'oid' => $objects['shop_goods_id'],
                        'type' => $this->obj_type[$objects['obj_type']]?$this->obj_type[$objects['obj_type']]:'goods',
                        'type_alias' => $objects['obj_alias'],
                        'iid' => $objects['shop_goods_id'],
                        'title' => $objects['name'],
                        'orders_bn' => $objects['bn'],
                        'items_num' => $objects['quantity'],
                        'total_order_fee' => $objects['amount'],
                        'weight' => $objects['weight'],
                        'discount_fee' => $objects['pmt_price'],
                        'sale_price' => $objects['sale_price'],
                    );
                    if ($objects['order_items'])
                    foreach ($objects['order_items'] as $items){
                       $product_id = $items['product_id'];
                       $products_info = $productsObj->dump($product_id, 'spec_desc');
                       $product_attr = array();
                       if ($products_info['spec_value'])
                       foreach ($products_info['spec_value'] as $spec_key=>$spec_val){
                           $specification_info = $specificationObj->dump(array('spec_id'=>$spec_key), 'spec_name');
                           $product_attr[] = $specification_info['spec_name'].":".$spec_val;
                       }
                       $product_attr = implode(';',$product_attr);
                       $order_objects['order'][$object_key]['order_items']['item'][] = array(
                           'sku_id' => $product_id,
                           //'iid' => ,//商品ID
                           'bn' => $items['bn'],
                           'name' => $items['name'],
                           'sku_properties' => $product_attr,
                           'weight' => $items['weight'],
                           'score' => $items['score'],
                           'price' => $items['price'],
                           'total_item_fee' => $items['amount'],
                           'discount_fee' => $items['pmt_price'],
                           'sale_price' => $items['sale_price'],
                           'num' => $items['quantity'],
                           'sendnum' => $items['sendnum'],
                           'item_type' => $items['item_type']?$items['item_type']:'product',
                           'item_status' => $this->product_status[$items['delete']],
                       );
                    }
                    $order_items_num += count($objects['order_items']);
                    $object_key++;
                }
            }
            //优惠方案
            $pmt_detail = $pmtObj->getList('pmt_amount as promotion_fee,pmt_describe as promotion_name', array('order_id'=>$order['order_id']), 0, -1);
            $regionLib = kernel::single('eccommon_regions');
            //收货人地区信息
            $area = $order['consignee']['area'];
            $regionLib->split_area($area);
            //买家会员信息
            $members_info = $membersObj->dump(array('member_id'=>$order['member_id']), '*');
            $member_area = $members_info['contact']['area'];
            $regionLib->split_area($member_area);
            //卖家信息
            $shop_id = $order['shop_id'];
            $shop_info = $shopObj->dump($shop_id, '*');
            //交易备注
            $oldmemo = unserialize($order['mark_text']);
            $memo = $oldmemo[count($oldmemo)-1]['op_content'];

            $params = array(
                'tid' => $order['order_bn'],
                'created' => date('Y-m-d H:i:s',$order['createtime']),
                'modified' => date('Y-m-d H:i:s',$order['last_modified']),
                'status' => $this->status[$order['status']],
                'pay_status' => $this->pay_status[$order['pay_status']],
                'ship_status' => $this->ship_status[$order['ship_status']],
                'is_delivery' => $order['is_delivery']=='Y'?'true':'false',
                'is_cod' => $order['shipping']['is_cod'],
                'has_invoice' => $order['is_tax'],
                'invoice_title' => $order['tax_title'],
                'invoice_fee' => $order['cost_tax'],
                'total_goods_fee' => $order['cost_item'],
                'total_trade_fee' => $order['total_amount'],
                'total_currency_fee' => $order['total_amount'],
                'discount_fee' => $order['discount'],
                'goods_discount_fee' => $order['pmt_goods'],
                'orders_discount_fee' => $order['pmt_order'],
                'promotion_details' => $pmt_detail ? json_encode($pmt_detail) : '',
                'payed_fee' => $order['payed'],
                'currency' => $order['currency']?$order['currency']:'CNY',
                'currency_rate' => $order['cur_rate'],
                'pay_cost' => $order['payinfo']['cost_payment'],
                'buyer_obtain_point_fee' => $order['score_g'],
                'point_fee' => $order['score_u'],
                //'shipping_tid' => $order[''],//TODO：物流方式ID
                'shipping_type' => $order['shipping']['shipping_name'],
                'shipping_fee' => $order['shipping']['cost_shipping'],
                'is_protect' => $order['shipping']['is_protect'],
                'protect_fee' => $order['shipping']['cost_protect'],
                //'payment_tid' => $order[''],//支付方式ID
                'payment_type' => $order['payinfo']['pay_name'],
                //'pay_time' => $order[''],//支付时间
                //'end_time' => $order[''],//交易成功时间
                //'consign_time' => $order[''],//卖家发货时间
                'receiver_name' => $order['consignee']['name'],
                'receiver_email' => $order['consignee']['email'],
                'receiver_state' => $area[0],
                'receiver_city' => $area[1],
                'receiver_district' => $area[2],
                'receiver_address' => $order['consignee']['addr'],
                'receiver_zip' => $order['consignee']['zip'],
                'receiver_mobile' => $order['consignee']['mobile'],
                'receiver_phone' => $order['consignee']['telephone'],
                'receiver_time' => $order['consignee']['r_time'],
                //'buyer_alipay_no' => ,//买家支付宝账号
                //'seller_uname' => ,//卖家帐号
                //'buyer_id' => ,//买家（会员）ID
                'buyer_uname' => $members_info['account']['uname'],
                'buyer_name' => $members_info['contact']['name'],
                'buyer_mobile' => $members_info['contact']['phone']['mobile'],
                'buyer_phone' => $members_info['contact']['phone']['telephone'],
                'buyer_email' => $members_info['contact']['email'],
                'buyer_state' => $member_area[0],
                'buyer_city' => $member_area[1],
                'buyer_district' => $member_area[2],
                'buyer_address' => $members_info['contact']['addr'],
                'buyer_zip' => $members_info['contact']['zipcode'],
                //'seller_rate' => ,//卖家是否已评价
                //'buyer_rate' => ,//买家是否已评价
                //'commission_fee' => ,//交易佣金
                //'seller_alipay_no' => ,//卖家支付宝账号
                'seller_mobile' => $shop_info['mobile'],
                'seller_phone' => $shop_info['tel'],
                'seller_name' => $shop_info['default_sender'],
                //'seller_email' => $shop_info['email'],//邮箱地址
                //'trade_memo' => $memo,//交易备注
                //'orders_number' => ,//当前交易下订单数量
                'total_weight' => $order['weight'],
                'orders' => $order_objects ? json_encode($order_objects) : '',
            );

            if($shop_id){
                //本地新建订单不同步到前台
                if($order['source'] == 'local') return false;
                $title = '店铺('.$shop_info['name'].')订单编辑(订单号:'.$order['order_bn'].')';
            }else{
                return false;
            }

            $callback = array(
                'class' => 'ome_rpc_request_order',
                'method' => 'update_order_callback',
            );
            $api_name = 'store.trade.update';
            if ($order_items_num > $max_orderitems){
                $gzip = true;
            }
            //$params['gzip'] = $gzip;TODO:暂时不走GZIP
            $addon['bn'] = $order['order_bn'];
            $this->request($api_name,$params,$callback,$title,$shop_id);
        }else{
            return false;
        }
    }
    function update_order_callback($result){
        return $this->callback($result);
    }

}