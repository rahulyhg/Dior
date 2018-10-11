<?php
class creditorderapi_service_site
{
    public function order_add_1_0($params,$service){
        $data=json_decode($params['params'],true);
        //echo '<pre>dd';print_r($data);exit;
        $res=$this->add($data);
        if($res['status']=='fail'){
            $service->error('300',$res['msg'],$res['order_bn']);
        }else{
            $service->response(200,'',$res['order_bn']);
        }
    }
    public function add($order){
        $oObj = kernel::single("ome_mdl_orders");
        $mObj = kernel::single("ome_mdl_members");
        $itemObj=kernel::single("ome_mdl_order_items");
        $pObj = kernel::single("ome_mdl_products");
        $sales_model=kernel::single("ome_mdl_goods");
        $sObj=  kernel::single("ome_mdl_shop");
        $objPayment = kernel::single("ome_mdl_payment_cfg");
        $arrOrders=array();
        $order_bn=$order['order_bn'];

        //check地区
        if(!$address_id=$this->checkArea($order['address_id'])){
            return array('status'=>'fail','msg'=>'地区不正确','order_bn'=>$order_bn);exit();
        }

        //shipping
        $arrOrders['shipping']['shipping_name']='快递';
        $arrOrders['shipping']['is_protect']='false';
        $arrOrders['shipping']['cost_protect']='0';
        $arrOrders['shipping']['is_cod']='false';
        $arrOrders['shipping']['cost_shipping']=$order['cost_shipping'];

        //创建会员
        $member_uname=empty($order['account']['mobile'])?$order['account']['email']:$order['account']['mobile'];
        if(empty($member_uname)){//游客购买
            $member_uname='游客_'.rand(1,1000).rand(1,1000).rand(1,1000).'@'.$order['shop_id'].'com';
        }
        $member = $mObj->dump(array('uname'=>$member_uname),'*');
        if (!$member){
            $member['account']['uname']=$member_uname;
            $member['contact']['phone']['mobile']=$order['account']['mobile'];
            $member['contact']['name']=$order['account']['name'];
            $member['contact']['email']=$order['account']['email'];
            $member['contact']['area']=$address_id;
            if (!$mObj->save($member)){
                return array('status'=>'fail','msg'=>'会员更新失败','order_bn'=>$order_bn);exit();
            }
        }

        //consignee
        $arrOrders['member_id']=$member['member_id'];
        $arrOrders['consignee']=$order['consignee'];
        $arrOrders['consignee']['r_time']='任意日期 任意时间段';
        $arrOrders['consignee']['area']=$address_id;
        //该接口默认属于积分订单
        $arrOrders['is_creditOrder']='1';
        $iorder=array();
        $totalNums=0;
        $cost_item=0;
        $total_goods_pmt=0;
        $total_amount=0;
        $is_price_abnormal='false';

        foreach($order['products'] as $item){
            $amount=0;
            $pmt_price=0;
            $arrProduct=$pObj->getList('*',array('bn'=>$item['bn']));
            $arrProduct=$arrProduct[0];
            $sm_id=$sales_model->getList('*',array('bn'=>$item['bn']));
            if (empty($arrProduct['goods_id']) || empty($sm_id)){
                return array('status'=>'fail','msg'=>'商品不存在','order_bn'=>$order_bn);exit();
            }

            $amount=$item['price']*$item['num'];
            $pmt_price=$amount-$item['sale_price'];

//            if($item['type']=="sales"){
//                $obj_type='goods';
//                $item_type='product';
//            }else{
//                $obj_type='gift';
//                $item_type='gift';
//            }
            $obj_type='goods';
            $item_type='product';
            $iorder['order_objects'][] = array(
                'obj_type' =>$obj_type,
                'obj_alias' =>$obj_type,
                'goods_id' =>$sm_id[0]['sm_id'],
                'bn' => $item['bn'],
                'name' =>$item['name'],
                'price' =>$item['price'],
                'pmt_price'=>$pmt_price,
                'sale_price'=>$item['sale_price'],
                'amount' => $amount,
                'quantity' => $item['num'],
                'order_items' => array(
                    array(
                        'product_id' => $arrProduct['product_id'],
                        'bn' => $item['bn'],
                        'name' => $item['name'],
                        'price' =>$item['price'],
                        'pmt_price'=>$pmt_price,
                        'sale_price'=>$item['sale_price'],
                        'amount' => $amount,
                        'quantity' => $item['num'],
                        //'ax_pmt_percent'=>$item['pmt_percent'],
                        'item_type' =>$item_type,
                    )
                )
            );
            $totalNums=$totalNums+$item['num'];
            $cost_item+=$amount;
            $total_goods_pmt=$total_goods_pmt+$pmt_price;//商品总优惠
        }
        //判断下金额 //商品总金额-优惠+运费
        if(bccomp(bcadd(bcsub($cost_item,$order['pmt_order'],2),$order['cost_shipping'],2),$order['pay'],2)!='0'){
            return array('status'=>'fail','msg'=>'订单金额不一致','order_bn'=>$order_bn);exit();
        }
        $arrOrders['order_objects']=$iorder['order_objects'];
        $arrOrders['cost_item']=$cost_item;
        $arrOrders['total_amount']=$order['pay'];
        $arrOrders['pmt_goods']=$total_goods_pmt;
        $arrOrders['pmt_order']=$order['pmt_order']-$total_goods_pmt;

        //店铺
        $shopInfo = $sObj->getList('shop_type',array('shop_id'=>$order['shop_id']));
        if(empty($shopInfo)){
            return array('status'=>'fail','msg'=>'店铺不存在','order_bn'=>$order_bn);exit();
        }
        $arrOrders['shop_id']=$order['shop_id'];
        $arrOrders['shop_type']='magento';
        $arrOrders['createtime']=$order['createtime'];
        $arrOrders['itemnum']=$totalNums;
        $arrOrders['order_bn']=$order_bn;

        if(!empty($order['order_memo'])){
            $c_memo = $order['order_memo'];
            $c_memo = array('op_name'=>'系统', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$c_memo);
            $tmp[]  = $c_memo;
            $arrOrders['custom_mark']  = serialize($tmp);
            $arrOrders['mark_type']    = 'b1';
        }

        //payment
        $pay_bn=$objPayment->getList('id,pay_bn,custom_name',array('pay_bn'=>$order['pay_bn']));//支付方式
        if(empty($pay_bn)||empty($order['trade_no'])){
            return array('status'=>'fail','msg'=>'支付参数异常','order_bn'=>$order_bn);exit();
        }else{
            $arrOrders['pay_bn']=$pay_bn['0']['pay_bn'];
            $arrOrders['payment']=$pay_bn['0']['custom_name'];
            $arrOrders['pay_id']=$pay_bn['0']['id'];
        }
        $arrOrders['trade_no']=$order['trade_no'];
        $arrOrders['paytime']=$order['paytime'];
        //发票相关
        if($order['is_tax']=='true'){
            $arrOrders['is_tax'] = 'true';
            $arrOrders['tax_title'] = $order['invoice']['tax_title'];
            $arrOrders['tax_no'] = $order['invoice']['tax_no'];
            $arrOrders['invoice_name'] = $order['invoice']['invoice_name'];
            if(!$arrOrders['invoice_area']=$this->checkArea( $order['invoice']['invoice_area'])){
                return array('status'=>'fail','msg'=>'发票地区不正确','order_bn'=>$order_bn);exit();
            }
            $arrOrders['invoice_addr'] = $order['invoice']['invoice_addr'];
            $arrOrders['invoice_zip'] = $order['invoice']['invoice_zip'];
            $arrOrders['invoice_contact'] = $order['invoice']['invoice_contact'];
            $arrOrders['taxpayer_identity_number'] = $order['invoice']['taxpayer_identity_number'];
            $arrOrders['is_einvoice'] = $order['invoice']['is_einvoice'];
        }
        //相关扩展信息
        $arrOrders['is_letter']=$order['is_letter'];
        $arrOrders['is_card']=$order['is_card'];
        $arrOrders['is_w_card']=$order['is_w_card'];
        $arrOrders['welcomecard']=$order['welcomecard'];
        $arrOrders['is_wechat']=$order['is_wechat'];
        $arrOrders['wechat_openid']=$order['wechat_openid'];
        $arrOrders['message1']=$order['giftmessage']['message1'];
        $arrOrders['message2']=$order['giftmessage']['message2'];
        $arrOrders['message3']=$order['giftmessage']['message3'];
        $arrOrders['message4']=$order['giftmessage']['message4'];
        $arrOrders['message5']=$order['giftmessage']['message5'];
        $arrOrders['message6']=$order['giftmessage']['message6'];
        $arrOrders['order_refer_source']=$order['order_refer_source'];
        //echo '<pre>dd';print_r($arrOrders);
        $transaction = $oObj->db->beginTransaction();
        if(!$oObj->create_order($arrOrders)){
            $oObj->db->rollBack();
            return array('status'=>'fail','msg'=>'订单保存失败','order_bn'=>$order_bn);exit();
        }
        //订单明细相关信息添加**修改明细中的product_id为基础物料的id**
        foreach ($order['products'] as $value) {
            $item_update_data=array('letter_info'=>$value['letter_info']);
            $itemObj->update($item_update_data,array('order_id'=>$arrOrders['order_id'],'bn'=>$value['bn']));
        }
        //添加订单优惠信息
        if(bccomp($order['pmt_order'],'0',2)=='1' && !empty($order['order_pmt'])) {
            if (!$this->add_order_pmt($arrOrders['order_id'], $order['order_pmt'])) {
                $oObj->db->rollBack();
                return array('status' => 'fail', 'msg' => '订单优惠信息保存失败','order_bn'=>$order_bn);
                exit();
            }
        }

        if(!$this->do_payorder($arrOrders)){
            $oObj->db->rollBack();//保存失败
            return array('status'=>'fail','msg'=>'订单保存失败(支付单创建失败)','order_bn'=>$order_bn);exit();
        }

        $oObj->db->commit($transaction);

        ###### 订单状态回传kafka august.yao 创建订单 start ####
        $kafkaQueue = app::get('ome')->model('kafka_queue');
        $queueData = array(
            'queue_title' => '订单创建推送',
            'worker'      => 'ome_kafka_api.createOrder',
            'start_time'  => time(),
            'params'      => array(
                'status'      => 'create',
                'order_bn'    => $arrOrders['order_bn'],
                'shop_id'     => $arrOrders['shop_id'],
                'createOrder' => $arrOrders,
            ),
        );
        $kafkaQueue->save($queueData);
        ###### 订单状态回传kafka august.yao 创建订单 end ####

        return array('status'=>'succ','msg'=>'创建成功');exit();
    }

    function do_payorder($iorder){
        $paymentCfgObj = kernel::single("ome_mdl_payment_cfg");
        $objOrder = kernel::single("ome_mdl_orders");
        $objMath = kernel::single('eccommon_math');
        $oPayment = kernel::single("ome_mdl_payments");

        $pay_money=$iorder['total_amount'];
        $orderdata = array();


        $orderdata['pay_status']='1';

        $orderdata['order_id'] = $iorder['order_id'];
        $orderdata['pay_bn'] = $iorder['pay_bn'];
        $orderdata['payed'] = $objMath->number_plus(array(0,$pay_money));
        $orderdata['payed'] = floatval($orderdata['payed']);
        $orderdata['paytime'] = $iorder['paytime'];
        $orderdata['payment'] = $iorder['payment'];
        $pay_id=$iorder['pay_id'];

        $filter = array('order_id'=>$iorder['order_id']);
        if(!$objOrder->update($orderdata,$filter)){
            return false;
        }


        $payment_bn = $iorder['trade_no'];//$oPayment->gen_id();
        $paymentdata = array();
        $paymentdata['payment_bn'] = $payment_bn;
        $paymentdata['order_id'] = $iorder['order_id'];
        $paymentdata['shop_id'] =$iorder['shop_id'];//'295605e1914b3e33b650a9b9bd36c8ae';
        $paymentdata['currency'] ='CNY';
        $paymentdata['money'] = $pay_money;
        $paymentdata['paycost'] = 0;
        $paymentdata['t_begin'] = $iorder['paytime'];//支付开始时间
        $paymentdata['t_end'] = $iorder['paytime'];//支付结束时间
        $paymentdata['trade_no'] = $iorder['trade_no'];//支付网关的内部交易单号，默认为空
        $paymentdata['cur_money'] = $pay_money;
        if($pay_id=="3"){
            $paymentdata['pay_type'] = 'offline';
        }else{
            $paymentdata['pay_type'] = 'online';
        }
        $paymentdata['payment'] = $pay_id;
        $paymentdata['paymethod'] = $iorder['payment'];
        $paymentdata['ip'] = kernel::single("base_request")->get_remote_addr();
        $paymentdata['status'] = 'succ';
        $paymentdata['memo'] = '';
        $paymentdata['is_orderupdate'] = 'false';
        if(!$oPayment->create_payments($paymentdata,'api_order_add')){
            return false;
        }

        return true;
    }

    public function add_order_pmt($order_id,$pmt_detail=''){
        if (empty($pmt_detail) || empty($order_id)) return false;
        $pmtObj = app::get('ome')->model('order_pmt');
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

    public function checkArea($address_id){
        //地区处理
        $mObj = kernel::single("ome_mdl_members");
        list($city1, $city2, $city3) = explode('-',$address_id);
        if(empty($city1)){
            return false;
        }
        $isCity2=$mObj->db->select("SELECT region_id FROM sdb_eccommon_regions WHERE local_name='$city2' AND region_grade='2'");
        if(empty($isCity2['0']['region_id'])){
            return $city1.'/'.$city2.'/'.$city3;
        }
        $isCity2=$isCity2['0']['region_id'];
        if(!empty($city3)){
            $isCity3=$mObj->db->select("SELECT local_name,region_id FROM sdb_eccommon_regions WHERE p_region_id='$isCity2' AND region_grade='3' AND local_name='$city3'");
            if(empty($isCity3['0']['region_id'])){
                return $city1.'/'.$city2.'/'.$city3;
            }
            return 'mainland:'.$city1.'/'.$city2.'/'.$city3.':'.$isCity3['0']['region_id'];
        }else{
            return 'mainland:'.$city1.'/'.$city2.':'.$isCity2;
        }


    }
}