<?php
class order extends PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->request = require 'request.php';
    }
    
    /** 
     * 模拟前端店铺订单
     * @access public
     * @return 前端店铺API返回结果
     */
    public function testOrder(){
        $this->request->token =  'fb744cc02d0da9938e1c9c6e17a88ba280171fb89e5fb1a87781d68b98004d8c';// 接收方token
        $this->request->url = 'http://localhost/oms/index.php/api';// 接收方api地址
        $this->request->node_id = 'taobao';// 接收方节点
       
        #获取销售单信息
        for($i=1;$i<=1;$i++){
         $rs = $this->_call('','ome.order.add','zx');
         print_r(json_decode($rs,1));
        }
    }
    
    /** 
     * 组织订单数据
     * @access public
     * @param String $order_bn 订单号
     * @param String $type zx 直销 jx 经销 fx 分销
     */
    private function _call($order_bn=NULL,$method='ome.order.add',$type="zx"){
        if (empty($order_bn)){
            $order_bn = "999".time();
        }
        
        $consignee = array(
            'name' => '可可',
            'area_state' => '北京',
            'area_city' => '北京市',
            'area_district' => '东城区',
            'addr' => '东城区1234号',
            'zip' => '123123',
            'telephone' => '',
            'email' => '',
            'r_time' => '任意日期 任意时间段',
            'mobile' => '18752566525',
        );
        $payinfo = array(
            'pay_name' => '线下支付',
            'cost_payment' => '',
        );
        $pmt_detail = array(
            array(
            'pmt_amount' => '30',
            'pmt_describe' => '打扰e',
            ),
            array(
            'pmt_amount' => '30',
            'pmt_describe' => '哧哧',
            ),
        );
        $member_info = array(
            'uname' => 'candy',
            'name' => '',
            'area_state' => '',
            'area_city' => '',
            'area_district' => '',
            'addr' => '',
            'mobile' => '',
            'tel' => '',
            'email' => '',
            'zip' => '',
        );
        $payment_detail = array(
            'trade_no' => time(),
            'pay_time' => '2012-06-12 22:22:12',
            'pay_account' => '6220029328234234',
            'paymethod' => '支付宝3',
            'memo' => '没什么好说的',
            'money' => '30',
        );
        $payments = array(
            array(
                'trade_no' => time(),
                'pay_time' => '2013-05-20 13:38:27',
                'pay_account' => '6220029328234234',
                'paymethod' => '支付宝2',
                'memo' => '没什么好说的',
                'money' => '520',
            )
        );
        $p = array('SK-0002','SK-0001');#更改 订单明细商品
        $object_num = count($p);
        for($i=0;$i<$object_num;$i++){
            $nums = '10';#明细商品的数量
            $order_objects[$i] = array(
                'obj_type' => 'goods',
                'obj_alias'=> 'goods',
                'shop_goods_id'=> '1',
                'bn'=>$p[$i],
                'name'=>$p[$i],
                'price'=>'30',
                'quantity'=>'2',
                'amount'=>'50',
                'weight'=>'4',
                'score'=>'4',
                'oid'=>rand(1,100),#子订单号
                'fx_oid'=>rand(1,100),#淘宝分销子采购单id[仅当店铺是淘宝并且类型是分销]
                'cost_tax'=>rand(1,100),#发票应开金额[仅当店铺是淘宝并且类型是分销]
                'buyer_payment'=>rand(1,100),#发票应开金额[仅当店铺是淘宝并且类型是分销]
                'order_items'=>array(
                    array(
                        'shop_product_id' => '',
                        'shop_goods_id' => '',
                        'item_type' => 'product',
                        'bn' => $p[$i],
                        'name' => $p[$i],
                        'cost' => '2',
                        'quantity' => $nums,
                        'sendnum' => '0',
                        'amount' => '60',
                        'price' => '30',
                        'weight' => '2',
                        'product_attr' => array(),
                        'addon' => '',
                        'score' => '3',
                        'cost_tax'=>rand(1,100),#发票应开金额[仅当店铺是淘宝并且类型是分销]
                        'buyer_payment'=>rand(1,100),#发票应开金额[仅当店铺是淘宝并且类型是分销]
                    ),
                ),
            );
            if($type != 'zx'){
                $order_objects[$i]['oid'] = '0';
                $order_objects[$i]['fx_oid'] = '0';
                $order_objects[$i]['cost_tax'] = '0';
                $order_objects[$i]['buyer_payment'] = '0';
                $order_objects[$i]['order_items'][$i]['cost_tax'] = '0';
                $order_objects[$i]['order_items'][$i]['buyer_payment'] = '0';
            }
        }
        $consigner = array(
            'name' => '发货人姓名5',
            'area_state' => '江西省4',
            'area_city' => '南昌市4',
            'area_district' => '东湖区4',
            'addr' => '发货人地址4',
            'zip' => '3300364',
            'telephone' => '123412344',
            'email' => 'werer@163.com4',
            'mobile' => '13899994',
        );
        $selling_agent = array(
            'member_info' => array(    
                'uname' => 'dongqiujing',
                'name' => '代销人姓名2',
                'level' => '1',
                'birthday' => '2010-12-21',
                'sex' => 'male',
                'area_state' => '上海',
                'area_city' => '',
                'area_district' => '徐汇区',
                'addr' => '代销人地址',
                'zip' => '330036',
                'telephone' => '021-4564654',
                'mobile' => '13343343434',
                'email' => 'adfaf@613.com2',
            ),
            'website' => array(
                'name' => '代销人网站名称',
                'domain' => 'www.daixiao.com',
                'logo' => 'http://www.baidu.com',
            )
        );
        $payed = 520;
        $total_amount = 520;
        $pay_status = '1';
        $is_cod = 'false';
        $ship_status = '0';
        $status = 'active';
        $shipping = array(
            'shipping_name' => '快递2',
            'cost_shipping' => '3',
            'is_protect' => 'true',
            'cost_protect' => '2',
            'is_cod' => $is_cod,
        );
        $signfor_status = '0';
        if($type == 'jx'){
            $order_type = 'jx';
        }elseif($type == 'dx'){
            $order_type = 'dx';
        }else{
            $order_type ='normal';
        }
        $params = array(
            "cur_rate" => '1.0000',
            "consignee" => json_encode($consignee),
            "payment_detail" => json_encode($payment_detail),
            "payments" => json_encode($payments),
            "currency" => 'CNY',
            "cost_item" => '100',
            "cost_tax" => '0.00',
            "title" => 'Order Create',
            "pay_bn" => 'alipay',
            "payinfo" => json_encode($payinfo),
            "member_info" => json_encode($member_info),
            //"pmt_detail" => json_encode($pmt_detail),
            "order_bn" => $order_bn,
            "pay_status" => $pay_status,
            "status" => $status,
            "score_u" => '0',
            "is_delivery" => Y,
            //"order_limit_time"=> '2012-06-18 22:22:22',
            "discount" => '',
            "pmt_goods" => '90',
            "score_g" => '0',
            "pmt_order" => '0.00',
            "total_amount" => $total_amount,
            "ship_status" => $ship_status,
            "cur_amount" => '0.00',
            "modified" => time(),
            "shipping" => json_encode($shipping),
            "consigner" => json_encode($consigner),
            "selling_agent" => json_encode($selling_agent),
            "payed" => $payed,
            "order_objects" => json_encode($order_objects),
            "is_tax" => 'true',
            "tax_title" => '发票抬头',
            "score_u"=> '',
            "score_g"=>'',
            "createtime" => time(),
            "mark_text" => "[-admin6-]", 
            "custom_mark" => '买家留言6',
            "mark_type" => 'b5',
            "lastmodify" => date('Y-m-d H:i:s',time()),
            //"lastmodify" => '2012-06-11 12:58:22'
            "node_version" => '1',
            "real_time" => 'true',
            "order_type" => $order_type,
            "signfor_status" => $signfor_status,
        );
        if($type != 'zx'){
            $params['fx_order_id'] = time();#淘宝分销供应商交易ID (用于发货 作为订单号回写到前端)[仅当店铺是淘宝并且类型是分销]
            $params['tc_order_id'] = time();#淘宝分销的主订单ID （经销不显示）[仅当店铺是淘宝并且类型是分销]
            $params['t_type'] = 'fenxiao';
        }
        return $this->request->call($method, $params);        
    }
    
}