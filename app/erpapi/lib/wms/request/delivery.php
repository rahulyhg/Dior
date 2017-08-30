<?php
/**
 * 发货单推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_request_delivery extends erpapi_wms_request_abstract
{
    /**
     * 发货单暂停
     *
     * @return void
     * @author 
     **/
    public function delivery_pause($sdf){}

    /**
     * 发货单暂停恢复
     *
     * @return void
     * @author 
     **/
    public function delivery_renew($sdf){}

    /**
     * 发货单创建
     *
     * @return void
     * @author 
     **/
    public function delivery_create($sdf){
        $delivery_bn = $sdf['outer_delivery_bn'];

        $iscancel = kernel::single('ome_interface_delivery')->iscancel($delivery_bn);
        if ($iscancel) {
            return $this->succ('发货单已取消,终止同步');
        }

        $title = $this->__channelObj->wms['channel_name'] . '发货单添加';

        $params = $this->_format_delivery_create_params($sdf);
        if (!$params) {
            return $this->error('参数为空,终止同步');
        }


        $callback = array(
            'class' => get_class($this),
            'method' => 'delivery_create_callback',
            'params' => array('delivery_bn'=>$delivery_bn,'obj_bn'=>$delivery_bn,'obj_type'=>'delivery'),
        );

        return $this->__caller->call(WMS_SALEORDER_CREATE, $params, $callback, $title,10,$delivery_bn);
    }

    protected function _format_delivery_create_params($sdf)
    {
        $delivery_bn = $sdf['outer_delivery_bn'];

        $delivery_items = $sdf['delivery_items'];
        $sdf['item_total_num'] = $sdf['line_total_count'] = count($delivery_items);

        $items = array('item'=>array());
        if ($delivery_items){
            sort($delivery_items);
            foreach ($delivery_items as $k => $v){
                $items['item'][] = array(
                    'item_code'       => $v['bn'],
                    'item_name'       => $v['product_name'],
                    'item_quantity'   => $v['number'],
                    'item_price'      => $v['price'],
                    'item_line_num'   => ($k + 1),// 订单商品列表中商品的行项目编号，即第n行或第n个商品
                    'trade_code'      => $sdf['order_bn'],//可选(若是淘宝交易订单，并且不是赠品，必须要传订单来源编号) 
                    'item_id'         => $v['bn'],// 外部系统商品sku
                    'is_gift'         => $v['is_gift'] == 'ture' ? '1' : '0',// 是否赠品
                    'item_remark'     => $v['memo'],// TODO: 商品备注
                    'inventory_type'  => '1',// TODO: 库存类型1可销售库存101类型用来定义残次品201冻结类型库存301在途库存
                    'item_sale_price' => $v['sale_price']//成交额
                );
            }
        }

        // 发票信息
        if ($sdf['is_order_invoice'] == 'true' && $sdf['is_wms_invoice'] == 'true'){
            $invoice       = $sdf['invoice'];
            $is_invoice    = 'true';
            $invoice_type  = $invoice_type_arr[$invoice['invoice_type']]; // ?什么情况
            $invoice_title = $invoice['invoice_title']['title'];
            
            // 增值税抬头信息
            if ($invoice['invoice_type'] == 'increment'){
                $invoice_info = array(
                    'name'         => $invoice['invoice_title']['uname'],
                    'phone'        => $invoice['invoice_title']['tel'],
                    'address'      => $invoice['invoice_title']['reg_addr'],
                    'taxpayer_id'  => $invoice['invoice_title']['identify_num'],
                    'bank_name'    => $invoice['invoice_title']['bank_name'],
                    'bank_account' => $invoice['invoice_title']['bank_account'],
                );
                $invoice_info = json_encode($invoice_info);
            }
            
            // 发票明细
            if ($invoice['invoice_items']){
                $invoice_items = array();
                $i_money = 0;
                foreach ($invoice['invoice_items'] as $val){
                    $price = round($val['money'],2);
                    $invoice_items[] = array(
                        'name'     => $val['item_name'],
                        'spec'     => $val['spec'],
                        'quantity' => $val['nums'],
                        'price'    => $price,
                    );
                    $i_money += $price;
                }
            }

            if ($invoice['content_type'] == 'items'){
                $invoice_item  = json_encode($invoice_items);
                $invoice_money = $i_money;
            }else{
                $invoice_desc  = $invoice['invoice_desc'];
                $invoice_money = round($invoice['invoice_money'],2);
            }
        }
        
        $create_time = preg_match('/-|\//',$sdf['create_time']) ? $sdf['create_time'] : date("Y-m-d H:i:s",$sdf['create_time']);

        $logistics_code = kernel::single('wmsmgr_func')->getWmslogiCode($this->__channelObj->wms['channel_id'],$sdf['logi_code']);
        $shop_code = kernel::single('wmsmgr_func')->getWmsShopCode($this->__channelObj->wms['channel_id'],$sdf['shop_code']);
        $params = array(
            'uniqid'              => self::uniqid(),
            'out_order_code'      => $delivery_bn,
            'order_source'        => $sdf['shop_type'] ? strtoupper($sdf['shop_type']) : 'OTHER',
            'shipping_type'       => 'EXPRESS',
            'shipping_fee'        => $sdf['logistics_costs'],
            'platform_order_code' => $sdf['order_bn'],
            'logistics_code'      => $logistics_code ? $logistics_code : $sdf['logi_code'],
            'shop_code'           => $shop_code ? $shop_code : $sdf['shop_code'],
            'remark'              => $sdf['memo'],//订单上的客服备注
            'created'             => $create_time,
            'wms_order_code'      => $delivery_bn,
            'is_finished'         => 'true',
            'current_page'        => 1,// 当前批次,用于分批同步
            'total_page'          => 1,// 总批次,用于分批同步
            'has_invoice'         => $is_invoice == 'true' ? 'true' : 'false',
            'invoice_type'        => $invoice_type,
            'invoice_title'       => $invoice_title,
            'invoice_fee'         => $invoice_money,
            'invoice_info'        => $invoice_info,
            'invoice_desc'        => $invoice_desc,
            'invoice_item'        => $invoice_item,
            'discount_fee'        => $sdf['discount_fee'],
            'is_protect'          => $sdf['is_protect'],
            'protect_fee'         => $sdf['cost_protect'],
            'is_cod'              => $sdf['is_cod'],//是否货到付款。可选值:true(是),false(否)
            'cod_fee'             => $sdf['cod_fee'],//应收货款（用于货到付款）
            'cod_service_fee'     => '0',//cod服务费（货到付款 必填）
            'total_goods_fee'     => $sdf['total_goods_amount']-$sdf['goods_discount_fee'],//商品原始金额-商品优惠金额
            'total_trade_fee'     => $sdf['total_amount'],//订单交易金额
            'receiver_name'       => $sdf['consignee']['name'],
            'receiver_zip'        => $sdf['consignee']['zip'],
            'receiver_phone'      => $sdf['consignee']['telephone'],
            'receiver_mobile'     => $sdf['consignee']['mobile'],
            'receiver_state'      => $sdf['consignee']['province'],
            'receiver_city'       => $sdf['consignee']['city'],
            'receiver_district'   => $sdf['consignee']['district'],
            'receiver_address'    => $sdf['consignee']['addr'],
            'receiver_email'      => $sdf['consignee']['email'],
            'receiver_time'       => $sdf['consignee']['r_time'],// TODO: 要求到货时间
            'line_total_count'    => $sdf['line_total_count'],// TODO: 订单行项目数量
            'item_total_num'      => $sdf['item_total_num'],
            'storage_code'        => $sdf['storage_code'],// 库内存放点编号
            'items'               => json_encode($items),
            'print_remark'        => $sdf['print_remark'] ? json_encode($sdf['print_remark']) : '',
            'dispatch_time'       => $sdf['delivery_time']
        );
        return $params;
    }

    public function delivery_create_callback($response, $callback_params)
    {
        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'];
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $delivery_bn = $callback_params['delivery_bn'];

        if ($data) $data = @json_decode($data,true);

        if (is_array($data) && $data['wms_order_code']) {
            $oDelivery_extension = app::get('console')->model('delivery_extension');
            $ext_data['original_delivery_bn'] = $data['wms_order_code'];
            $ext_data['delivery_bn']          = $delivery_bn;
            $oDelivery_extension->save($ext_data);
        }

        $deliveryObj = app::get('ome')->model('delivery');
        $deliverys = $deliveryObj ->dump(array('delivery_bn'=>$delivery_bn),'delivery_id');
        
        $msg        = $err_msg ? $err_msg : $res;
        $api_status = $rsp=='succ' ? 'success' : 'fail';
        app::get('console')->model('delivery_send')->update_send_status($deliverys['delivery_id'],$api_status,$msg);

        $callback_params['obj_bn'] = $delivery_bn;
        $callback_params['obj_type'] = 'delivery';
        return $this->callback($response, $callback_params);
    }


    /**
     * 发货单取消
     *
     * @return void
     * @author 
     **/
    public function delivery_cancel($sdf){
        $delivery_bn = $sdf['outer_delivery_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '发货单取消';

        $params = $this->_format_delivery_cancel_params($sdf);

        return $this->__caller->call(WMS_SALEORDER_CANCEL, $params, null, $title,10,$delivery_bn);

    }

    protected function _format_delivery_cancel_params($sdf)
    {
        $params = array(
            'warehouse_code' => $sdf['branch_bn'],
            'out_order_code' => $sdf['outer_delivery_bn'],
        );
        return $params;
    }


    /**
     * 发货单查询
     *
     * @return void
     * @author 
     **/
    public function delivery_search($sdf)
    {
        $delivery_bn = $sdf['delivery_bn'];

        $title = $this->__channelObj->wms['channel_name'] . '发货单查询';

        $params = $this->_format_delivery_search_params($sdf);

        return $this->__caller->call(WMS_SALEORDER_GET, $params, null, $title,10,$delivery_bn);
    }

    protected function _format_delivery_search_params($sdf)
    {
        $params = array(
            'out_order_code'=>$sdf['delivery_bn'],    
        );
        return $params;
    }

    protected function _get_electron_logi_no($sdf)
    {
        if (!$sdf['logi_no'] && $sdf['shop_type'] == '360buy') {

            $dlyCorpObj = app::get('ome')->model('dly_corp');
            $dlyCorp = $dlyCorpObj->dump($sdf['logi_id'], 'channel_id');

            $channel = app::get('logisticsmanager')->model('channel')->dump(array('channel_id'=>$dlyCorp['channel_id'],'status' => 'true'));
            $jdAccount = explode('|||', $channel['shop_id']);

            if ($jdAccount[0]) {
                $params = array(
                    'preNum'       => 1, //运单量数据量
                    'customerCode' => $jdAccount[0], //商家编码
                );

                $writelog = array(
                    'log_type'    => 'other',
                    'log_title'   => '获取京东电子面单',
                    'original_bn' => $sdf['outer_delivery_bn'],
                );

                $result = kernel::single('logisticsmanager_rpc_request_360buy')->request('store.etms.waybillcode.get',$params,array(),$sdf['shop_id'],$writelog);
                $status = isset($result['rsp']) ? $result['rsp'] : '';
                $data = empty($result['data']) ? '' : json_decode($result['data'], true);

                if ($status == 'succ' && count($data['resultInfo']['deliveryIdList']) > 0) {
                    foreach ($data['resultInfo']['deliveryIdList'] as $waybill_code) {
                        if ($waybill_code) {
                            $tmp_data = array(
                                'delivery_bn' => $sdf['outer_delivery_bn'],
                                'logi_no'     => $waybill_code,
                                'action'      => 'addLogiNo',
                                'status'      => 'update',
                            );

                            kernel::single('ome_event_receive_delivery')->update($tmp_data);
                            return $waybill_code;
                        }
                    }
                }

                // 更新一下状态
                if ($sdf['delivery_id']) {
                    $sendObj = app::get('console')->model('delivery_send');
                    $msg = $result['res'] ? $result['res'] : $result['err_msg'];
                    $sendObj->update_send_status($sdf['delivery_id'],'fail',$msg);                
                }

                return false;
            }
        }
        return '';
    }
}