<?php
 
/**
 * Class omeftp_request_qimen
 * 组装请求奇门接口数据
 */
class qmwms_request_qimen{

    /**
     * @param $delivery_id
     * @return array
     * @throws Exception
     * 发货单创建
     */
    public function _deliveryOrderCreate($order_id) {

        $ordersObj = app::get('ome')->model('orders');
        $orderItems = app::get('ome')->model('order_items');
        $deliveryObj = app::get('ome')->model('delivery');
        $deliveryOrder = app::get('ome')->model('delivery_order');
        $paymentObj = app::get('ome')->model('payments');
        $memberObj = app::get('ome')->model('members');
        $shopObj = app::get('ome')->model('shop');

        $delivery_id = $deliveryOrder->dump(array('order_id'=>$order_id),'delivery_id');
        if(empty($delivery_id)){
            return false;
        }
        $deliveryData = $deliveryObj->getList('*',array('delivery_id'=>$delivery_id),0,1);
        $ordersData = $ordersObj->getList('*',array('order_id'=>$order_id),0,1);

        $paymentData = $paymentObj->getList('payment_bn,trade_no',array('order_id'=>$order_id),0,1);
        $membersData = $memberObj->getList('name,uname',array('member_id'=>$ordersData[0]['member_id']),0,1);

        $shop_id = $ordersData[0]['shop_id'];
        $shopData = $shopObj->getList('*',array('shop_id'=>$shop_id));
        $shopNick = $shopData[0]['name'];

        $orderItemsData  = $orderItems->getList('*',array('order_id'=>$order_id),0,-1);
        $Engraving = '';
        foreach($orderItemsData as $item){
            $Engraving .= $item['message1'];
        }

        //comment和remark信息及刻字信息,全部在custom_mark字段
        $custom_mark = unserialize($ordersData[0]['custom_mark']);
        $op_content    = '';
        if(!empty($ordersData[0]['custom_mark'])){
            foreach($custom_mark as $text){
                $op_content .= $text['op_content'].';';
            }
        }

        $ship_area = $ordersData[0]['ship_area'];
        kernel::single('ome_func')->split_area($ship_area);
        $ordersData[0]['ship_province'] = ome_func::strip_bom(trim($ship_area[0]));
        $ordersData[0]['ship_city']     = ome_func::strip_bom(trim($ship_area[1]));
        $ordersData[0]['ship_district'] = ome_func::strip_bom(trim($ship_area[2]));

        $consigner_area =$ordersData[0]['consigner_area'];
        kernel::single('ome_func')->split_area($consigner_area);
        $consigner_area[0] = ome_func::strip_bom(trim($consigner_area[0]));
        $consigner_area[1] = ome_func::strip_bom(trim($consigner_area[1]));
        $consigner_area[2] = ome_func::strip_bom(trim($consigner_area[2]));
        $ordersData[0]['consigner_province'] = !empty($consigner_area[0]) ? $consigner_area[0]:'';
        $ordersData[0]['consigner_city']     = !empty($consigner_area[1]) ? $consigner_area[1]:'';
        $ordersData[0]['consigner_district'] = !empty($consigner_area[2]) ? $consigner_area[2]:'';

        //发货单信息
        $body['deliveryOrder']['deliveryOrderCode']    = $ordersData[0]['order_bn'];//必须 出库单号
        $body['deliveryOrder']['preDeliveryOrderCode'] = $ordersData[0]['order_bn'];//原出库单号(ERP分配)
        $body['deliveryOrder']['preDeliveryOrderId']   = $ordersData[0]['order_bn'];//原出库单号(WMS分配)
        $body['deliveryOrder']['orderType']            = 'JYCK';//必须 出库单类型：JYCK 一般交易出库单;
        $body['deliveryOrder']['warehouseCode']        = 'LVMH_DMALL';//必须 仓库编码
        $body['deliveryOrder']['orderFlag']            = strtoupper($ordersData[0]['pay_bn']);//订单标记
        if($ordersData[0]['is_cod'] == 'true')$body['deliveryOrder']['orderFlag'] = 'COD';//订单标记
        $body['deliveryOrder']['sourcePlatformCode'] = 'OTHER';//订单来源平台
        $body['deliveryOrder']['sourcePlatformName'] = 'DIOR官方商城';//订单来源平台名称
        $body['deliveryOrder']['createTime']     = date('Y-m-d H:i:s', $deliveryData[0]['create_time']);//必须 发货单创建时间
        $body['deliveryOrder']['placeOrderTime'] = date('Y-m-d H:i:s',$ordersData[0]['createtime']);//必须 下单时间
        $body['deliveryOrder']['payNo']          = isset($paymentData[0]['trade_no'])?$paymentData[0]['trade_no']:$paymentData[0]['payment_bn'];//支付平台交易号
        $body['deliveryOrder']['payTime']        = !empty($ordersData[0]['paytime'])?date('Y-m-d H:i:s',$ordersData[0]['paytime']):'';//订单支付时间
        $body['deliveryOrder']['operatorName']   = $deliveryData[0]['op_name'];//操作员(审核员)名称
        $body['deliveryOrder']['operateTime']    = date('Y-m-d H:i:s');//必须 操作(审核)时间(YYYY-MM-DD HH:MM:SS)
        $body['deliveryOrder']['shopNick']       = 'DIOR官方商城';//必须 店铺名称
        //$body['deliveryOrder']['buyerNick']    = $membersData[0]['name'];//买家昵称  (取值情况不确定)
        $body['deliveryOrder']['totalAmount']    = number_format($ordersData[0]['total_amount'],2,'.','');//订单总金额
        $body['deliveryOrder']['itemAmount']     = number_format($ordersData[0]['cost_item'],2,'.','');//商品总金额
        $body['deliveryOrder']['discountAmount'] = number_format($ordersData[0]['pmt_goods'],2,'.','');//订单折扣金额
        if($ordersData[0]['is_pay_trial']){
            //付费试用订单
            $body['deliveryOrder']['freightSample'] = number_format($ordersData[0]['cost_freight']-$ordersData[0]['pmt_cost_shipping'],2,'.','');//快递费用
        }else{
            $body['deliveryOrder']['freight']        = number_format($ordersData[0]['cost_freight']-$ordersData[0]['pmt_cost_shipping'],2,'.','');//快递费用
        }
        if($deliveryData[0]['is_cod'] == 'true'){
            $body['deliveryOrder']['arAmount']   = number_format($ordersData[0]['total_amount']-$ordersData[0]['payed'],2,'.','');//应收金额
            $body['deliveryOrder']['gotAmount']  = number_format($ordersData[0]['payed'],2,'.','');//已付金额
            $body['deliveryOrder']['serviceFee'] = number_format($ordersData[0]['cost_payment'],2,'.','');//COD服务费
        }
        if($deliveryData[0]['type'] == 'reject'){//售后发货单
            $body['deliveryOrder']['logisticsCode'] = 'OTHER';//必须 物流公司编码
            $body['deliveryOrder']['logisticsName'] = '其他物流公司';//物流公司名称
        }else{
            $body['deliveryOrder']['logisticsCode'] = 'SF_SP';//必须 物流公司编码
            if(in_array(trim($deliveryData[0]['ship_province']),array("上海","江苏省","安徽省","浙江省","西藏自治区"))){
                $body['deliveryOrder']['logisticsCode'] = 'SF_STD';//必须 物流公司编码
            }
            $body['deliveryOrder']['logisticsName'] = '顺丰速运';//物流公司名称
        }
        //$body['deliveryOrder']['expressCode'] = $deliveryData[0]['logi_no'];//运单号
        //发货人信息
        $body['deliveryOrder']['senderInfo']['name']        = $ordersData[0]['consigner_name'];//发货人
        $body['deliveryOrder']['senderInfo']['zipCode']     = $ordersData[0]['consigner_zip'];//邮编
        $body['deliveryOrder']['senderInfo']['tel']         = $ordersData[0]['consigner_tel'];//
        $body['deliveryOrder']['senderInfo']['mobile']      = $ordersData[0]['consigner_mobile'];//
        $body['deliveryOrder']['senderInfo']['email']       = $ordersData[0]['consigner_email'];//
        $body['deliveryOrder']['senderInfo']['countryCode'] = 'CN';//国家二字码 默认中国
        $body['deliveryOrder']['senderInfo']['province']    = $ordersData[0]['consigner_province'];//
        $body['deliveryOrder']['senderInfo']['city']        = $ordersData[0]['consigner_city'];//
        $body['deliveryOrder']['senderInfo']['area']        = $ordersData[0]['consigner_district'];//
        $body['deliveryOrder']['senderInfo']['detailAddress'] = $ordersData[0]['consigner_addr'];//详细地址
        //收货人信息
        $body['deliveryOrder']['receiverInfo']['name']        = $ordersData[0]['ship_name'];//收货人
        $body['deliveryOrder']['receiverInfo']['zipCode']     = $ordersData[0]['ship_zip'];//邮编
        $body['deliveryOrder']['receiverInfo']['tel']         = $ordersData[0]['ship_tel'];//
        $body['deliveryOrder']['receiverInfo']['mobile']      = $ordersData[0]['ship_mobile'];//
        $body['deliveryOrder']['receiverInfo']['email']       = $ordersData[0]['ship_email'];//
        $body['deliveryOrder']['receiverInfo']['countryCode'] = 'CN';//国家二字码 默认中国
        $body['deliveryOrder']['receiverInfo']['province']    = $ordersData[0]['ship_province'];//
        $body['deliveryOrder']['receiverInfo']['city']        = $ordersData[0]['ship_city'];//
        $body['deliveryOrder']['receiverInfo']['area']        = $ordersData[0]['ship_district'];//
        $body['deliveryOrder']['receiverInfo']['detailAddress'] = $ordersData[0]['ship_addr'];//详细地址
        $body['deliveryOrder']['remark'] = $op_content;

        $extendPropsFapiao = array();
        if($ordersData[0]['is_einvoice'] == 'false'){
            $body['deliveryOrder']['invoiceFlag'] = ($ordersData[0]['is_tax'] == 'true')?'Y':'N';//是否开发票

            if($ordersData[0]['is_tax'] == 'true'){
                //发票信息
                $body['deliveryOrder']['invoices']['invoice']['type'] = 'VINVOICE';//发票类型(INVOICE=普通发票;VINVOICE=增值税普通发票;EVINVOICE=电子增票;填写的 条件 是:invoiceFlag为Y)
                $body['deliveryOrder']['invoices']['invoice']['header'] = $ordersData[0]['tax_company'];//发票抬头
                $invoice_area = $ordersData[0]['invoice_area'];
                kernel::single('ome_func')->split_area($invoice_area);
                $invoice_area[0] = ome_func::strip_bom(trim($invoice_area[0]));
                $invoice_area[1] = ome_func::strip_bom(trim($invoice_area[1]));
                $invoice_area[2] = ome_func::strip_bom(trim($invoice_area[2]));
                $extendPropsFapiao['fapiao_regnumber']         = $ordersData[0]['taxpayer_identity_number'];//纳税人识别号
                $extendPropsFapiao['fapiao_receiver_name']     = $ordersData[0]['invoice_name'];
                $extendPropsFapiao['fapiao_receiver_phone']    = $ordersData[0]['invoice_contact'];//联系方式
                $extendPropsFapiao['fapiao_receiver_province'] = $invoice_area[0];//省份
                $extendPropsFapiao['fapiao_receiver_city']     = $invoice_area[1];//(地级)城市
                $extendPropsFapiao['fapiao_receiver_district'] = $invoice_area[2];//县级市、区
                $extendPropsFapiao['fapiao_receiver_detailAddress'] = $ordersData[0]['invoice_addr'];
            }
        }
        $comments = array();
        if(!empty($op_content))$comments = array('comments'=>$op_content);

        //SF到货时间选择
        $tDelivery = array();
        if(!empty($ordersData[0]['ship_time'])){
            $ship_time = $ordersData[0]['ship_time'];
            $is_date = strstr($ship_time,'_');
            if($is_date){
                $arr = explode('_',$ship_time);
                $tDelivery = array('tDeliveryDate'=>$arr[0],'tDelivery'=>$arr[1]);
            }
        }

        $extendProps = array_merge($extendPropsFapiao,$comments,$tDelivery);
        $body['deliveryOrder']['extendProps'] = $extendProps;

        //订单列表信息
        $body['orderLines'] = array();
        $apiParams       = app::get('qmwms')->model('qmwms_api')->getList('api_params',array());
        $qmApiSetting    = unserialize($apiParams['0']['api_params']);
        $gift_bn         = $qmApiSetting['gift_bn'];//品牌礼品卡
        $sample_bn       = $qmApiSetting['sample_bn'];//礼品卡
        $mcd_sample_bn   = $qmApiSetting['mcd_sample_bn'];//MCD礼品卡
        $mcd_package_sku = $qmApiSetting['mcd_package_sku'];//MCD包装
        $cvd_sample_bn = $qmApiSetting['cvd_sample_bn'];//cvd礼品卡

        $message = '';
        if($ordersData[0]['message1']||$ordersData[0]['message2']||$ordersData[0]['message3']||$ordersData[0]['message4']||$ordersData[0]['message5']||$ordersData[0]['message6']){
            $message1 = !empty($ordersData[0]['message1'])?$ordersData[0]['message1'].'==CR==':'';
            $message2 =!empty($ordersData[0]['message2'])?$ordersData[0]['message2'].'==CR==':'';
            $message3 = !empty($ordersData[0]['message3'])?$ordersData[0]['message3'].'==CR==':'';
            $message4 = !empty($ordersData[0]['message4'])?$ordersData[0]['message4'].'==CR==':'';
            $message5 = !empty($ordersData[0]['message5'])?$ordersData[0]['message5'].'==CR==':'';
            $message6 = !empty($ordersData[0]['message6'])?$ordersData[0]['message6']:'';
            $message = trim($message1.$message2.$message3.$message4.$message5.$message6,'==CR==');
        }

        $itemId = 0;
        foreach($orderItemsData as $mx){
            $itemId = $itemId + 1;
            $orderLine = array(
                'orderLineNo'     => $itemId,//单据行号
                'ownerCode'	      => 'LVMH_PCD_OMS',  //必须 货主编码
                'itemCode'        => $mx['bn'],  // 必须 商品编码
                'itemId'          => $mx['bn'],//仓储系统商品编码 必须(文档标注)
                'inventoryType'   => 'ZP',//库存类型
                'itemName'        => $mx['name'],
                'planQty'         => $mx['nums'],
                'retailPrice'     =>number_format($mx['true_price']+$mx['ax_pmt_price']/$mx['nums'],2,'.',''),//零售价(零售价=实际成交价+单件商品折扣金额) (取值不确定)
                'actualPrice'     => number_format($mx['true_price'],2,'.',''), //必须 实际成交价
                'discountAmount'  =>number_format($mx['ax_pmt_price']/$mx['nums'],2,'.','')//单件商品折扣金额
            );

            if(count($orderItemsData) <=1){
                $body['orderLines']['orderLine'] = $orderLine;
            }
            else{
                $body['orderLines']['orderLine'][] = $orderLine;
            }
        }

        if(count($orderItemsData) <=1){
            if($ordersData[0]['is_card'] == 'true'){
                $body['orderLines']['orderLine'] = array($body['orderLines']['orderLine']);
            }
            elseif($ordersData[0]['is_w_card'] == 'true'){
                $body['orderLines']['orderLine'] = array($body['orderLines']['orderLine']);
            }
            elseif(!empty($ordersData[0]['ribbon_sku'])){
                $body['orderLines']['orderLine'] = array($body['orderLines']['orderLine']);
            }
            elseif($ordersData[0]['is_mcd_card'] == 'true'){
                $body['orderLines']['orderLine'] = array($body['orderLines']['orderLine']);
            }
            elseif($ordersData[0]['mcd_package_sku'] == 'MCD'){
                $body['orderLines']['orderLine'] = array($body['orderLines']['orderLine']);
            }
        }

        //礼品卡（留言卡）(普通留言卡与MCD留言卡只能有其中一种)
        $card_flag=false;
        if($ordersData[0]['is_card'] == 'true'){
            $gift_card_bn = $sample_bn;
            $card_flag=true;
        }
        elseif($ordersData[0]['is_mcd_card'] == 'true'){
            $gift_card_bn = $mcd_sample_bn;
            $card_flag=true;
        }
        if($card_flag){
            $itemId = $itemId + 1;
            $giftMessage = array(
                'orderLineNo'     => $itemId,//单据行号
                'ownerCode'	      => 'LVMH_PCD_OMS',  //必须 货主编码
                'itemCode'        => $gift_card_bn,  // 必须 商品编码
                'itemId'          => $gift_card_bn,//仓储系统商品编码 必须(文档标注)
                'inventoryType'   => 'ZP',//库存类型
                'itemName'        => 'gift message',
                'planQty'         => 1,
                'retailPrice'     =>'0.00',//零售价(零售价=实际成交价+单件商品折扣金额) (取值不确定)
                'actualPrice'     => '0.00', //必须 实际成交价
                'discountAmount'  =>'0.00',//单件商品折扣金额
                'extendProps'     =>array('itemType'=>'Gift','itemMessage'=>$message),
            );
            $body['orderLines']['orderLine'][] = $giftMessage;
        }
        
        //CVD
        if($ordersData[0]['is_cvd'] == 'true'){
            $itemId = $itemId + 1;
            $giftCvd = array(
                'orderLineNo'     => $itemId,//单据行号
                'ownerCode'	      => 'LVMH_PCD_OMS',  //必须 货主编码
                'itemCode'        => $cvd_sample_bn,  // 必须 商品编码
                'itemId'          => $cvd_sample_bn,//仓储系统商品编码 必须(文档标注)
                'inventoryType'   => 'ZP',//库存类型
                'itemName'        => 'gift message',
                'planQty'         => 1,
                'retailPrice'     =>'0.00',//零售价(零售价=实际成交价+单件商品折扣金额) (取值不确定)
                'actualPrice'     => '0.00', //必须 实际成交价
                'discountAmount'  =>'0.00',//单件商品折扣金额
                'extendProps'     =>array('itemType'=>'Gift'),
            );
            $body['orderLines']['orderLine'][] = $giftCvd;
        }

        //MCD包装  $mcd_package_sku
        if($ordersData[0]['mcd_package_sku'] == 'MCD'){
            $itemId = $itemId + 1;
            $mcdPackage = array(
                'orderLineNo'     => $itemId,//单据行号
                'ownerCode'	      => 'LVMH_PCD_OMS',  //必须 货主编码
                'itemCode'        => $mcd_package_sku,  // 必须 商品编码
                'itemId'          => $mcd_package_sku,//仓储系统商品编码 必须(文档标注)
                'inventoryType'   => 'ZP',//库存类型
                'itemName'        => 'MCD package',
                'planQty'         => 1,
                'retailPrice'     =>'0.00',//零售价(零售价=实际成交价+单件商品折扣金额) (取值不确定)
                'actualPrice'     => '0.00', //必须 实际成交价
                'discountAmount'  =>'0.00',//单件商品折扣金额
                'extendProps'     =>array('itemType'=>'GIFT WRAP'),
            );
            $body['orderLines']['orderLine'][] = $mcdPackage;
        }

        //品牌礼品卡(WelcomeCard)
        if($ordersData[0]['is_w_card'] == 'true'){
            $itemId = $itemId + 1;
            $welcomeCard = array(
                'orderLineNo'     => $itemId,//单据行号
                'ownerCode'	      => 'LVMH_PCD_OMS',  //必须 货主编码
                'itemCode'        => $gift_bn,  // 必须 商品编码
                'itemId'          => $gift_bn,//仓储系统商品编码 必须(文档标注)
                'inventoryType'   => 'ZP',//库存类型
                'itemName'        => 'WelcomeCard',
                'planQty'         => 1,
                'retailPrice'     =>'0.00',//零售价(零售价=实际成交价+单件商品折扣金额) (取值不确定)
                'actualPrice'     => '0.00', //必须 实际成交价
                'discountAmount'  =>'0.00',//单件商品折扣金额
                'extendProps'     =>array('itemType'=>'Card'),
            );
            $body['orderLines']['orderLine'][] = $welcomeCard;
        }

        //Ribbon丝带
        if(!empty($ordersData[0]['ribbon_sku'])){
            $itemId = $itemId + 1;
            $ribbonData = kernel::single("ome_mdl_products")->getList('name',array('bn'=>$ordersData[0]['ribbon_sku']));
            $itemName   = $ribbonData[0]['name'];
            $ribbon_sku = $ordersData[0]['ribbon_sku'];
            $ribbon = array(
                'orderLineNo'     => $itemId,//单据行号
                'ownerCode'	      => 'LVMH_PCD_OMS',  //必须 货主编码
                'itemCode'        => $ribbon_sku,  // 必须 商品编码
                'itemId'          => $ribbon_sku,//仓储系统商品编码 必须(文档标注)
                'inventoryType'   => 'ZP',//库存类型
                'itemName'        => $itemName,
                'planQty'         => 1,
                'retailPrice'     =>'0.00',//零售价(零售价=实际成交价+单件商品折扣金额) (取值不确定)
                'actualPrice'     => '0.00', //必须 实际成交价
                'discountAmount'  =>'0.00',//单件商品折扣金额
                'extendProps'     =>array('itemType'=>'Ribbon','itemMessage'=>$itemName),
            );
            $body['orderLines']['orderLine'][] = $ribbon;
        }

        //返回xml格式数据
        $return = array();
        $return['body'] =  kernel::single('qmwms_request_xml')->data_encode($body);
        //$return['body'] =  $this->array2xml($body,'request');
        $return['order_bn']   = $ordersData[0]['order_bn'];
        return $return;
    }

    //退货入库单创建
    public function _returnOrderCreate($reship_id){

        $reshipObj   = app::get('ome')->model('reship');
        $reshipDetail = $reshipObj->dump($reship_id,"*",array("reship_items"=>array("*")));
        $orderData = app::get('ome')->model('orders')->getList('*',array('order_id'=>$reshipDetail['order_id']));
        $preDeliveryOrderCode = $orderData[0]['order_bn'];
        $preDeliveryOrderId   = $orderData[0]['ax_order_bn'];
        //退货入库单无省、市、区字段，需要对收货地区ship_area进行explode
        $ship_area = $reshipDetail['ship_area'];
        kernel::single('ome_func')->split_area($ship_area);
        $reshipDetail['province'] = ome_func::strip_bom(trim($ship_area[0]));
        $reshipDetail['city']     = ome_func::strip_bom(trim($ship_area[1]));
        $reshipDetail['district'] = ome_func::strip_bom(trim($ship_area[2]));

        $allReship = $reshipObj->getList('reship_id',array('order_id'=>$reshipDetail['order_id']));
        $nums = count($allReship);

        $body['returnOrder']['returnOrderCode']        = $preDeliveryOrderCode.'-R'.$nums;//erp退货入库单编号 (必须)
        $body['returnOrder']['warehouseCode']          = "OTHER";//必须 仓库编码(统仓统配等无需ERP指定仓储编码的情况填OTHER)
        if($reshipDetail['return_type'] == 'return'){
            $body['returnOrder']['orderType']              = "THRK" ;//单据类型(THRK=退货入库;HHRK=换货入库;只传英文编码)
        }elseif($reshipDetail['return_type'] == 'change'){
            $body['returnOrder']['orderType']              = "HHRK" ;//单据类型(THRK=退货入库;HHRK=换货入库;只传英文编码)
        }
        $body['returnOrder']['preDeliveryOrderCode']   = $preDeliveryOrderCode ;//原出库单号(ERP分配) (必填)
        $body['returnOrder']['preDeliveryOrderId']     = !empty($preDeliveryOrderId)?$preDeliveryOrderId:"DM-".$preDeliveryOrderCode ;//原出库单号(WMS分配) (必填)
        $body['returnOrder']['logisticsCode']          = "SF" ;//物流公司编码(必填)
        $body['returnOrder']['logisticsName']          = "顺丰速运" ;//物流公司名称
        $body['returnOrder']['expressCode']            = $reshipDetail['logi_no'] ;//运单号
        $body['returnOrder']['returnReason']           = $reshipDetail['return_reason'] ;//退货原因
        //发件人信息(即退货入库单的收件人信息)
        $body['returnOrder']['senderInfo']['name']     = $reshipDetail['ship_name'];//姓名(必填)
        $body['returnOrder']['senderInfo']['zipCode']  = $reshipDetail['ship_zip'];//邮编
        $body['returnOrder']['senderInfo']['tel']      = $reshipDetail['ship_tel'];//电话
        $body['returnOrder']['senderInfo']['mobile']   = $reshipDetail['ship_mobile'];//手机(必填)
        $body['returnOrder']['senderInfo']['email']    = $reshipDetail['ship_email'];//邮箱
        $body['returnOrder']['senderInfo']['countryCode'] = "CN";//国家二字码
        $body['returnOrder']['senderInfo']['province'] = $reshipDetail['province'];//省份(必填)
        $body['returnOrder']['senderInfo']['city']     = $reshipDetail['city'];//城市(必填)
        $body['returnOrder']['senderInfo']['area']     = $reshipDetail['district'];//区域
        $body['returnOrder']['senderInfo']['detailAddress'] = $reshipDetail['ship_addr'];//详细地址(必填)
        $itemId = 0;
        foreach($reshipDetail['reship_items'] as $item){
            $itemId += 1;
            $orderLine = array(
                'orderLineNo'    =>$itemId,
                'ownerCode'      =>'LVMH_PCD_OMS',//货主编码(必填)
                'itemCode'       =>$item['bn'],//商品编码(必填)
                'itemId'         =>$item['bn'],//仓储系统商品编码(条件为提供后端（仓储系统）商品编码的仓储系统)
                'inventoryType'  =>'ZP',//库存类型 (ZP=正品, CC=残次,JS=机损, XS= 箱损, 默认为ZP)
                'planQty'        =>$item['num'],//应收商品数量(必填)
            );
            if(count($reshipDetail['reship_items']) <= 1){
                $body['orderLines']['orderLine'] = $orderLine;
            }
            else{
                $body['orderLines']['orderLine'][] = $orderLine;
            }
        }
        //返回xml格式数据
        $return = array();
        //$return['body'] =  kernel::single('qmwms_request_xml')->data_encode($body);
        $return['body']      =  $this->array2xml($body,'request');
        $return['reship_bn'] = $reshipDetail['reship_bn'];
        return $return;

    }

    //单据取消
    public function _orderCancel($delivery_id,$memo){
        //撤销发货单(发货拦截)
        $deliveryOrder = app::get('ome')->model('delivery_order')->getList('*',array('delivery_id'=>$delivery_id),0,-1);
        $orderData = app::get('ome')->model('orders')->getList('order_bn',array('order_id'=>array($deliveryOrder[0]['order_id'])));

        $body['warehouseCode'] = 'LVMH_DMALL';//仓库编码(必填)
        $body['ownerCode']     = 'LVMH_PCD_OMS';//货主编码
        $body['orderCode']     = $orderData[0]['order_bn'];//单据编号(必填)
        $body['orderId']       = !empty($orderData[0]['ax_order_bn'])?$orderData[0]['ax_order_bn']:'DM-'.$orderData[0]['order_bn'];//仓储系统单据编码(必填)
        $body['orderType']     = 'JYCK';//单据类型(JYCK=一般交易出库单;HHCK= 换货出库;PTCK=普通出库单;DBCK=调拨出库;B2BRK=B2B入库;B2BCK=B2B出库;QTCK=其他出库;SCRK=生产入库;CGRK=采购入库;DBRK= 调拨入库;QTRK=其他入库;XTRK= 销退入库;THRK=退货入库;HHRK= 换货入库;CGTH=采购退货出库单)
        $body['cancelReason']  = $memo;//取消原因

        //返回xml格式数据
        $return = array();
        //$return['body']        =  $this->array2xml($body,'request');
        $return['body']     =  kernel::single('qmwms_request_xml')->data_encode($body);
        $return['order_bn'] = $orderData[0]['order_bn'];
        return $return;
    }

    //库存查询
    public function _inventoryQuery($arrBn){
        //$arrBn是要进行库存查询的多维数组(多商品)
        foreach($arrBn as $mx){
            $criteria = array(
                'warehouseCode' => 'LVMH_DMALL',//仓库编码
                //'ownerCode'     => 'LVMH_PCD_OMS',//货主编码
                'itemCode'      => $mx['bn'],//商品编码  必须
                'itemId'        => $mx['bn'],//仓储系统商品ID 必须
                'inventoryType' => 'ZP',//库存类型
            );
            if(count($arrBn) <= 1){
                $body['criteriaList']['criteria'] = $criteria;
            }
            else{
                $body['criteriaList']['criteria'][] = $criteria;
            }
        }

        //返回xml格式数据
        $return =  $this->array2xml($body,'request');
        return $return;

    }

    //array数组转换成xml字符串
    public function array2xml($data, $root){
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<' . $root . '>';
        $this->_array2xml($data, $xml);
        $xml .= '</' . $root . '>';
        return $xml;
    }

    public function _array2xml(&$data, &$xml, $key = ''){
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_numeric($k)) {
                    $xml .= '<' . $key . '>';
                    $xml .= $this->_array2xml($v, $xml);
                    $xml .= '</' . $key . '>';
                } else {
                    if(!$this->isnumericArray($v))
                    {
                        $xml .= '<' . $k . '>';
                    }
                    $xml .= $this->_array2xml($v, $xml, $k);
                    if(!$this->isnumericArray($v))
                    {
                        $xml .= '</' . $k . '>';
                    }
                }
            }
        } elseif (is_numeric($data)) {
            $xml .= $data;
        } elseif (is_string($data)) {
            //$xml .= '<![CDATA[' . $data . ']]>';//防止因为如昵称中的特殊符号而中断xml解析
            $xml .=  $data;
        }
    }

    public function isnumericArray($array){
        if (count($array) > 1 && !empty($array[0]))
            return true;
        else
            return false;
    }

}
?>