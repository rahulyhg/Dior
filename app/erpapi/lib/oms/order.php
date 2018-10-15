<?php
 
class erpapi_oms_order
{
    public $shop_id='295605e1914b3e33b650a9b9bd36c8ae';
    public $params='';
    public $code='x4sXzRmoIQ7EQpte7912KpuS25gfOp7y';//测试:a6VDTWxVfaeR
    public $sfUrl='http://bsp-oisp.sf-express.com/bsp-oisp/ws/sfexpressService?wsdl';//测试:http://218.17.248.244:11080/bsp-oisp/ws/sfexpressService?wsdl
    public $reshipMsg=array('退回','转寄','遗失');
     
    public function getIP(){
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }
    
    public function xmlToArr($xml, $root = true) {
    
        if (!$xml->children()) {
            return (string) $xml;
        }
        $array = array();
        foreach ($xml->children() as $element => $node) {
            $totalElement = count($xml->{$element});
            if (!isset($array[$element])) {
                $array[$element] = "";
            }
        // Has attributes
            if ($attributes = $node->attributes()) {
                $data = array(
                    'attributes' => array(),
                    'value' => (count($node) > 0) ? $this->xmlToArr($node, false) : (string) $node
                );
                foreach ($attributes as $attr => $value) {
                    $data['attributes'][$attr] = (string) $value;
                }
                if ($totalElement > 1) {
                    $array[$element][] = $data;
                } else {
                    $array[$element] = $data;
                }
        // Just a value
            } else {
                if ($totalElement > 1) {
                    $array[$element][] = $this->xmlToArr($node, false);
                } else {
                    $array[$element] = $this->xmlToArr($node, false);
                }
            }
        }
        if ($root) {
            return array($xml->getName() => $array);
        } else {
            return $array;
        }
    
    } 
    
    public function GetRoute(){
        $params=NULL;
        for($i=0;$i<=40;$i++){
            $begin=$i*10;
            $end=10;
            $this->RoutePush($params,$begin,$end);
        }
    }
    
    public function RoutePush($params=NULL,$begin=10,$end=10){
        $twoweek=strtotime(date("Y-m-d H:i:s",strtotime("-3 week")));
        $objOrder = kernel::single("ome_mdl_orders");
        $mdl_reship=kernel::single("ome_mdl_reship");
        // Dior卡夫卡队列表 august.yao
        $kafkaQueue = app::get('ome')->model('kafka_queue');
        if(!empty($params)){
            $sql="SELECT shop_id,logi_no,order_id,pay_bn,total_amount,payment FROM sdb_ome_orders WHERE process_status='splited' AND order_bn='$params'";
            $arrDelivery=$objOrder->db->select($sql);
            $arrRoute['order_id']=$arrDelivery[0]['order_id'];
            if(!empty($arrRoute['order_id'])){//echo 1111111;
                $arrRoute['paytime']=time();
                $arrRoute['payment']='货到付款';
                $arrRoute['pay_bn']='cod';
                $arrRoute['trade_no']=$arrDelivery[0]['logi_no'];
                $arrRoute['total_amount']=$arrDelivery[0]['total_amount'];//echo "<pre>";print_r($arrRoute);exit();
                $this->do_payorder($arrRoute);
                $accept_time=time();
                $objOrder->db->exec("UPDATE sdb_ome_orders SET route_status='1',routetime='$accept_time' WHERE order_bn='$params'");
                kernel::single('omemagento_service_order')->update_status($params,'complete','',$accept_time);
		### 订单状态回传kafka august.yao 已完成 starts ###
                $queueData = array(
                    'queue_title' => '订单已完成状态推送',
                    'worker'      => 'ome_kafka_api.sendOrderStatus',
                    'start_time'  => time(),
                    'params'      => array(
                        'status'   => 'completed',
                        'order_bn' => $params,
                        'logi_bn'  => '',
                        'shop_id'  => $arrDelivery[0]['shop_id'],
                        'item_info'=> array(),
                        'bill_info'=> array(),
                    ),
                );
                $kafkaQueue->save($queueData);
                ### 订单状态回传kafka august.yao 已完成 end ###
            }
            return true;
        }else{
            $sql="SELECT logi_no,order_id,pay_bn,total_amount,payment,order_bn,shop_type,is_mcd,createway,relate_order_bn,is_creditOrder FROM sdb_ome_orders WHERE (pay_status='0' AND is_cod='true' AND ship_status='1' AND process_status='splited' AND route_status='0') OR (pay_status='1' AND ship_status='1' AND is_cod='false' AND process_status='splited' AND route_status='0' AND createtime>'$twoweek') ORDER BY paytime ASC limit $begin,$end";
            //$sql="SELECT logi_no,order_id,pay_bn,total_amount,payment,order_bn FROM sdb_ome_orders WHERE order_bn='500000472'";
        }
        $arrDelivery=$objOrder->db->select($sql);
        
        if(empty($arrDelivery['0']['order_id']))return false;
        
        $arrRoute=array();
        foreach($arrDelivery as $key=>$value){
            if(!empty($value['logi_no'])){
                $isReship='';
                $order_id=$value['order_id'];
                $sql="SELECT order_id FROM sdb_ome_reship WHERE order_id='$order_id'";
                $isReship=$objOrder->db->select($sql);
                $isReship=$isReship[0]['order_id'];
                if(!empty($isReship)){//申请退货中的订单不去查询
                    $objOrder->db->exec("UPDATE sdb_ome_orders SET route_status='1' WHERE order_id='$order_id'");
                    continue;
                }
                $strRoute.=$value['logi_no'].",";
                $arrRoute[$value['logi_no']]['order_id']=$value['order_id'];
                $arrRoute[$value['logi_no']]['trade_no']=$value['logi_no'];
                $arrRoute[$value['logi_no']]['order_bn']=$value['order_bn'];
                $arrRoute[$value['logi_no']]['pay_bn']=$value['pay_bn'];
                $arrRoute[$value['logi_no']]['shop_type']=$value['shop_type'];
                $arrRoute[$value['logi_no']]['total_amount']=$value['total_amount'];
                $arrRoute[$value['logi_no']]['payment']=$value['payment'];
		$arrRoute[$value['logi_no']]['shop_id']=$value['shop_id'];
                //mcd
                $arrRoute[$value['logi_no']]['is_mcd']=$value['is_mcd'];
                $arrRoute[$value['logi_no']]['createway']=$value['createway'];
                $arrRoute[$value['logi_no']]['relate_order_bn']=$value['relate_order_bn'];
                $arrRoute[$value['logi_no']]['is_creditOrder']=$value['is_creditOrder'];
            }
        }
        $strRoute=substr($strRoute,0,-1);
        
        if(empty($arrRoute))return false;
        
        //$strRoute='603935228630,605007032490';
        //$xml="<Request service='RouteService' lang='zh-CN'><Head>LWMXXS</Head><Body><RouteRequest tracking_type='1' method_type='1' tracking_number='".$strRoute."'/></Body></Request>";
        $xml="<Request service='RouteService' lang='zh-CN'><Head>0210634542</Head><Body><RouteRequest tracking_type='1' method_type='1' tracking_number='".$strRoute."'/></Body></Request>";
        
        $verifyCode=base64_encode(md5($xml.$this->code,TRUE));
        $client = new SoapClient($this->sfUrl,array(
                                        'trace'      => 1,
                                        'exceptions' => 1,
                                        'encoding'  =>'UTF-8',
                                        'soap_version'=>'SOAP_1_2',
                                        'cache_wsdl'=>0,
                                        //'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
                                 ));
        $arrPostWsdl=array('arg0'=>$xml,'arg1'=>$verifyCode);//正式
        try{
            $Wsdl_result=$client->sfexpressService($arrPostWsdl);
        }catch(SoapFault $e){
            $errMessage=$e->getMessage();
        } 
        
        /*$Wsdl_result->return='<?xml version="1.0" encoding="UTF-8"?><Response service="RouteService"><Head>OK</Head><Body><RouteResponse mailno="6677543221"><Route remark="顺丰速运 已收取快件" accept_time="2016-01-26 14:05:59" accept_address="深圳市" opcode="50"/><Route remark="快件正送往顺丰合作点【大洲通迅】" accept_time="2016-01-26 18:06:00" accept_address="深圳市" opcode="123"/><Route remark="快件到达顺丰店/站 " accept_time="2016-01-26 19:06:00" accept_address="深圳市" opcode="130"/><Route remark="正在派送途中,请您准备签收" accept_time="2016-01-27 09:06:00" accept_address="深圳市" opcode="44"/><Route remark="快件派送不成功(因收方客户拒收快件),待进一步处理" accept_time="2016-01-27 12:06:00" accept_address="深圳市" opcode="70"/><Route remark="正在派送途中,请您准备签收" accept_time="2016-01-27 13:05:00" accept_address="深圳市" opcode="44"/><Route remark="已签收,感谢使用顺丰,期待再次为您服务" accept_time="2016-01-27 13:06:00" accept_address="深圳市" opcode="80"/></RouteResponse></Body></Response>';*/
        $arrXML=(array)$Wsdl_result->return;
        $result=simplexml_load_string($arrXML['0'],'SimpleXMLElement',LIBXML_NOCDATA);
        $result=$this->xmlToArr($result);
       
        if(isset($result['Response']['Body']['RouteResponse'])&&$result['Response']['Head']=="OK"){//成功
            if(isset($result['Response']['Body']['RouteResponse']['value'])){
                $data=array();
                $data['Response']['Body']['RouteResponse']=$result['Response']['Body']['RouteResponse'];
                unset($result['Response']['Body']['RouteResponse']);
                $result['Response']['Body']['RouteResponse']['0']=$data['Response']['Body']['RouteResponse'];
            }
            
            foreach($result['Response']['Body']['RouteResponse'] as $k=>$v){
                $intDeliveryId=$v['attributes']['mailno'];
                $arrVRoute=array();
                if(isset($v['value']['Route']['attributes'])){
                    $arrVRoute['value']['Route']['0']=$v['value']['Route']['attributes'];
                }else{
                    $arrVRoute['value']['Route']=$v['value']['Route'];
                }//echo "<pre>";print_r($arrVRoute);exit();
                foreach($arrVRoute['value']['Route'] as $attributes){
                    $route=$attributes['attributes']['opcode'];
                    $remark=$attributes['attributes']['remark'];
                    //if($route=="70"){//拒签退回
                        
                    //}
                    if($route=="8000"){//已签收
                        foreach($this->reshipMsg as $e_message){
                            if(strpos($remark,$e_message)!==false){
                                continue 2;
                            }
                        }
                    
                        $order_id=$arrRoute[$intDeliveryId]['order_id'];
                        $accept_time=strtotime($attributes['attributes']['accept_time']);
                        if(!empty($order_id)){
                            
                            $order_bn=$arrRoute[$intDeliveryId]['order_bn'];
                            error_log('logi_no'.$intDeliveryId.'订单Begin:'.json_encode($result['Response']['Body']['RouteResponse'][$k])."订单End".$order_bn,3,DATA_DIR.'/sfroute/'.date("Ymd").'zjrorder.txt');
                            if($arrRoute[$intDeliveryId]['pay_bn']=='cod'&&$arrRoute[$intDeliveryId]['createway']!="after"){//货到付款模拟支付
                                $arrRoute[$intDeliveryId]['paytime']=time();
                                $arrRoute[$intDeliveryId]['payment']='货到付款';
                                $arrRoute[$intDeliveryId]['pay_id']='3';
                                $this->do_payorder($arrRoute[$intDeliveryId]);
                            }
                            
                            $objOrder->db->exec("UPDATE sdb_ome_orders SET route_status='1',routetime='$accept_time' WHERE order_bn='$order_bn'");
                            
                            //发给magento
                            if($arrRoute[$intDeliveryId]['is_mcd']=="true"&&$arrRoute[$intDeliveryId]['createway']=="after"){
                                $post=$arrReship=array();
                                $arrReship=$mdl_reship->getList("m_reship_bn",array('p_order_id'=>$order_id));
                                $post['order_bn']=$arrRoute[$intDeliveryId]['relate_order_bn'];
                                $post['exchange_no']=$arrReship[0]['m_reship_bn'];
                                $post['status']='complete';
                                $post['event_time']=date('Y-m-d H:i:s',$accept_time);
                                kernel::single('omemagento_service_change')->updateStatus($post);
                            }else{
                                if($arrRoute[$intDeliveryId]['is_creditOrder']=='1'){//积分订单
                                    kernel::single('creditorderapi_api_diorsite')->update_order_status($order_id,'completed');
                                }else{
                                    kernel::single('omemagento_service_order')->update_status($order_bn,'complete','',$accept_time);
                                }

                            }
                            
			                ### 订单状态回传kafka august.yao 已完成 start ###
                            $queueData = array(
                                'queue_title' => '订单已完成状态推送',
                                'worker'      => 'ome_kafka_api.sendOrderStatus',
                                'start_time'  => time(),
                                'params'      => array(
                                    'status'   => 'completed',
                                    'order_bn' => $arrRoute[$intDeliveryId]['order_bn'],
                                    'logi_bn'  => '',
                                    'shop_id'  => $arrRoute[$intDeliveryId]['shop_id'],
                                    'item_info'=> array(),
                                    'bill_info'=> array(),
                                ),
                            );
                            $kafkaQueue->save($queueData);
                            ### 订单状态回传kafka august.yao 已完成 end ###
                        }
                        $order_id=NULL;
                    }
                    
                }
                //echo $v['attributes']['mailno']."<br>";
            }
            
        }else{//失败
            if($result['Response']['Head']!="OK"){
                error_log('顺丰接口失败'.$result['Response']['ERROR']['value'],3,DATA_DIR.'/sfroute/'.date("Ymd").'zjrorder.txt');
            }
        }
        echo 'succ';
    //  echo "<pre>";print_r($result);print_r($arrRoute);exit();
    }
    
    public function getSign($params){
        $shop_sign=$params['shop_sign'];
        if(empty($shop_sign)){
            return false;
        }
        unset($params['shop_sign']);
        
        $sign='';
        asort($params);
        foreach($params as $k=>$v){
            $sign.=$k.'='.$v.'&';
        }
         
        $sign_time=$params['createtime']-101;
        
        if($shop_sign!==strtoupper(md5(strtoupper(md5('Dior')).'ILoveDior~!'.$sign.$sign_time))){
            return false;
        }
        return true;
    }
    
    public function base64json($params,&$msg=''){
        error_log('订单开始:'.$params['order'],3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
        $sign=$params['sign'];
        if($sign!="123456"||empty($sign)){
            $msg='SignError 40001';
            return false;
        }
        
        if(strpos($params['order'], ' ') !== false) {
            $params['order'] = str_replace(' ', '+', $params['order']);
        }
        
        error_log('订单中间:'.base64_decode($params['order']),3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
        $this->params=base64_decode($params['order']);
        
        if($this->getSign(json_decode(base64_decode($params['order']),true))!==true){
            $msg='SignError 40002';
            return false;
        }
        
        $post=json_decode(str_replace(' ','',base64_decode($params['order'])),true);
        
        if(isset($post['order_bn'])){
            $post['address_id']=urldecode($post['address_id']);
            $post['account']['uname']=urldecode($post['account']['uname']);
            $post['account']['name']=urldecode($post['account']['name']);
            foreach($post['products'] as $k=>$v){
                $post['products'][$k]['name']=urldecode($v['name']);
                $post['products'][$k]['pkg_name']=urldecode($v['pkg_name']);
                if(!empty($v['pkg_name'])){
                    error_log('捆绑商品:'.$post['products'][$k]['pkg_name'],3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
                }
                if(!empty($v['lettering'])){
                    $post['products'][$k]['lettering']=str_replace(array("\r\n", "\r", "\n"),'\n',urldecode($v['lettering']));
                }
            }
            $post['giftmessage']['message1']=urldecode($post['giftmessage']['message1']);
            $post['giftmessage']['message2']=urldecode($post['giftmessage']['message2']);
            $post['giftmessage']['message3']=urldecode($post['giftmessage']['message3']);
            $post['giftmessage']['message4']=urldecode($post['giftmessage']['message4']);
            $post['giftmessage']['message5']=urldecode($post['giftmessage']['message5']);
            $post['giftmessage']['message6']=urldecode($post['giftmessage']['message6']);
            if(!empty($post['order_pmt'])){
                foreach($post['order_pmt'] as $k=>$v){
                    $post['order_pmt'][$k]['pmt_describe']=urldecode($v['pmt_describe']);
                }
            }
            $post['consignee']['addr']=urldecode($post['consignee']['addr']);
            $post['consignee']['name']=urldecode($post['consignee']['name']);
            
            $post['tax_title']=urldecode($post['tax_title']);
            $post['invoice_name']=urldecode($post['invoice_name']);
            $post['invoice_area']=urldecode($post['invoice_area']);
            $post['invoice_addr']=urldecode($post['invoice_addr']);
            error_log('URLDECODE后:'.json_encode($post['products']),3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
            return $post;
        }else{
            kernel::single("erpapi_oms_email_sendemail")->sendEmail();
            $msg='接口异常';
            return false;
        }
    }
    
    public function checkArea($address_id){
        //地区处理
        $mObj = kernel::single("ome_mdl_members");
        list($city1, $city2, $city3) = explode('-',$address_id);
        $isCity2=$mObj->db->select("SELECT region_id FROM sdb_eccommon_regions WHERE local_name='$city2' AND region_grade='2'");
        if(empty($isCity2['0']['region_id'])){
            return false;   
        }
        $isCity2=$isCity2['0']['region_id'];
        $isCity3=$mObj->db->select("SELECT local_name,region_id FROM sdb_eccommon_regions WHERE p_region_id='$isCity2' AND region_grade='3' AND local_name='$city3'");
        if(empty($isCity3['0']['region_id'])){
            return false;   
        }
     
        return 'mainland:'.$city1.'/'.$city2.'/'.$city3.':'.$isCity3['0']['region_id'];
    }
    
    public function add($params){
        $ip=$this->getIp();
        
        if(!$post=$this->base64json($params,$msg)){
            return $this->send_error($msg);
        }
         
        error_log('IP--:'.$ip.'Order_bn:'.$post['order_bn'],3,DATA_DIR.'/ip/'.date("Ymd").'zjrorder.txt');
        
        if(substr($ip,0,8)!='10.0.103'){
            kernel::single("erpapi_oms_email_sendemail")->sendEmail('Dior IP ERROR !!!!');
            error_log('ErrorIP--:'.$ip.'Order_bn:'.$post['order_bn'],3,DATA_DIR.'/ip/'.date("Ymd").'zjrorder.txt');
        }   
        //echo "<pre>";print_r($post);exit();       
        $mathLib = kernel::single('eccommon_math');
        $pObj = kernel::single("ome_mdl_products");
        $bpObj = kernel::single("ome_mdl_branch_product");
        $oObj = kernel::single("ome_mdl_orders");
        $dObj = kernel::single("ome_mdl_delivery");
        $mObj = kernel::single("ome_mdl_members");
        $oPcfg = kernel::single("ome_mdl_payment_cfg");
        $oShop = kernel::single("ome_mdl_shop");
        
        if(empty($post['order_bn'])){
            return $this->send_error('order_bn必须填写');
        }
        $order_bn=$post['order_bn'];
        $isExistOrderBn=$oObj->getList("order_bn",array('order_bn'=>$order_bn));
        if(!empty($isExistOrderBn[0]['order_bn'])){
            error_log('订单已存在:'.$order_bn,3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
            return $this->send_succ('此订单已经存在');
        }

        $logi_id = $post['logi_id'];
        $logi_no = $post['logi_no'];
        $arrShop=$oShop->getList('shop_id',array('shop_type'=>'magento'));
        $post['shop_id']=$arrShop['0']['shop_id']."*ecos.b2c";//'8a24cd49e61ab1193ae21dcdd33384b2*ecos.b2c';//8a24cd49e61ab1193ae21dcdd33384b2//f983d9b59cf0d45b54a4336032146d12
        
        $address_id=$post['address_id'];
        if(!$post['address_id']=$this->checkArea($post['address_id'])){
            return $this->send_error('地区不正确');
        }
        
        if(empty($post['consignee']['r_time'])){
            $post['consignee']['r_time']    = '任意日期 任意时间段';
        }
        $post['consignee']['area']      = $post['address_id'];
        
        //商品处理
        if(empty($post['products'][0])){
            return $this->send_error('请传入商品');
        }
        
        $consignee = $post['consignee'];
        if ($consignee){
            if (!$consignee['name']){
                return $this->send_error('请填写收件人');
            }
            if (!$consignee['area']){
                return $this->send_error('请填写配送三级区域');
            }
            if (!$consignee['addr']){
                return $this->send_error('请填写配送地址');
            }
            if (!$consignee['mobile'] && !$consignee['telephone']){
                return $this->send_error('收件人手机和固定电话必须填写一项');
            }
        }else {
            return $this->send_error('请填写配送地址信息');
        }
        
        $arrLin=array();
        $arrLinPkg=array();
        $lettering='';
        foreach($post['products'] as $k=>$v){
            if($v['type']!="pkg"){  
                if(isset($arrLin[$v['type']][$v['bn']])){
                    if($v['pmt_price']>0){
                        return $this->send_error('优惠金额异常');
                    }
                    if(!empty($v['lettering'])){
                        $post['products'][$k]['lettering']=$v['lettering']."。".$arrLin[$v['type']][$v['bn']]['lettering'];
                        $arrLin[$v['type']][$v['bn']]['lettering']=$post['products'][$k]['lettering'];
                        
                        $post['products'][$k]['lettering_type']=$v['lettering_type']."。".$arrLin[$v['type']][$v['bn']]['lettering_type'];
                        $arrLin[$v['type']][$v['bn']]['lettering_type']=$post['products'][$k]['lettering_type'];
                    }else{
                        $post['products'][$k]['lettering']=$arrLin[$v['type']][$v['bn']]['lettering'];
                        $post['products'][$k]['lettering_type']=$arrLin[$v['type']][$v['bn']]['lettering_type'];
                    }
                    $post['products'][$k]['num']=$post['products'][$k]['num']+$arrLin[$v['type']][$v['bn']]['num'];
                    unset($post['products'][$arrLin[$v['type']][$v['bn']]['key']]);
                }else{
                    $post['products'][$k]['num']=$v['num'];
                    $arrLin[$v['type']][$v['bn']]['lettering']=$v['lettering'];
                    $arrLin[$v['type']][$v['bn']]['lettering_type']=$v['lettering_type'];
                }
                
                $arrLin[$v['type']][$v['bn']]['num']=$post['products'][$k]['num'];
                $arrLin[$v['type']][$v['bn']]['key']=$k;
            }else{
                if(isset($arrLinPkg[$v['pkg_id']][$v['bn']])){//直接报错
                    return $this->send_error('PKG参数异常');
                }
                $arrLinPkg[$v['pkg_id']][$v['bn']]=$v['pkg_id'];
            }
            
            if(!empty($v['lettering'])){
                $lettering.=$v['lettering'];
            }
        }
        
        $h=0;
        foreach($post['products'] as $product){
            $bn=$product['bn'];
            $isBn=$pObj->getList('bn,product_id,price',array('bn'=>$bn));
            if(empty($isBn['0']['bn'])){
                $isBn = $pObj->getList('bn,product_id,price',array('short_bn'=>$bn));
                if(empty($isBn['0']['bn'])){
                    return $this->send_error('不存在的货号');
                }
            } 
            $mprice=$mathLib->number_plus(array($product['price'],0));
            $eprice=$mathLib->number_plus(array($isBn['0']['price'],0));
            
            $true_price=$mathLib->number_plus(array($product['true_price'],0));
            if($mprice!=$eprice){
                //return $this->send_error('商品价格不一致,请联系OMS管理员');
            }
            //echo "<pre>1";print_r($member);print_r($post);exit();
            if($product['type']=='pkg'){
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['num']=$product['num'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['type']=$product['type'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['pmt_price']=$product['pmt_price'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['pmt_percent']=$product['pmt_percent'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['pkg_name']=$product['pkg_name'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['name']=$product['name'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['message1']=$product['lettering'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['lettering_type']=$product['lettering_type'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['pkg_id']=$product['pkg_id'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['pkg_bn']=$product['pkg_bn'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['pkg_price']=$product['pkg_price'];
                $post['num'][$isBn['0']['product_id'].'_pkg'.$h]['pkg_num']=$product['pkg_num'];
                $post['price'][$isBn['0']['product_id'].'_pkg'.$h]=$mprice;
                
                $post['true_price'][$isBn['0']['product_id'].'_pkg'.$h]=$true_price;
            }else{
                if($product['type']=='gift'){
                    $post['num'][$isBn['0']['product_id']."_gift".$h]['num']=$product['num'];
                    $post['num'][$isBn['0']['product_id']."_gift".$h]['type']=$product['type'];
                    $post['num'][$isBn['0']['product_id']."_gift".$h]['name']=$product['name'];
                    $post['num'][$isBn['0']['product_id']."_gift".$h]['pmt_price']=$product['pmt_price'];
                    $post['num'][$isBn['0']['product_id']."_gift".$h]['pmt_percent']=$product['pmt_percent'];
                    $post['price'][$isBn['0']['product_id']."_gift".$h]=$mprice;
                    
                    $post['true_price'][$isBn['0']['product_id']."_gift".$h]=$true_price;
                }else{
                    $post['num'][$isBn['0']['product_id']."_".$h]['num']=$product['num'];
                    $post['num'][$isBn['0']['product_id']."_".$h]['type']=$product['type'];
                    $post['num'][$isBn['0']['product_id']."_".$h]['name']=$product['name'];
                    $post['num'][$isBn['0']['product_id']."_".$h]['is_mcd_product']=$product['is_mcd_product'];
                    $post['num'][$isBn['0']['product_id']."_".$h]['pmt_price']=$product['pmt_price'];
                    $post['num'][$isBn['0']['product_id']."_".$h]['pmt_percent']=$product['pmt_percent'];
                    $post['num'][$isBn['0']['product_id']."_".$h]['message1']=$product['lettering'];
                    $post['num'][$isBn['0']['product_id']."_".$h]['lettering_type']=$product['lettering_type'];
                    $post['price'][$isBn['0']['product_id']."_".$h]=$mprice;
                    
                    $post['true_price'][$isBn['0']['product_id']."_".$h]=$true_price;
                }
            }
            $h++;
            $isBn='';
        }
        
        //新建会员
        if(empty($post['account']['m_memeber_num'])){
            if($post['order_refer_source']=="minishop"){//EC小程序
                $member['account']['uname']=empty($post['account']['mobile'])?$post['consignee']['mobile']:$post['account']['mobile'];
                $member['contact']['phone']['mobile']=empty($post['account']['mobile'])?$post['consignee']['mobile']:$post['account']['mobile'];
            }else{
                $member['account']['uname']=$post['consignee']['mobile'];
                $member['contact']['phone']['mobile']=$post['consignee']['mobile'];
            }
            
            $member['contact']['area']=$post['address_id'];
            
            if (!$mObj->save($member)){ 
                return $this->send_error('会员更新失败 请重试');
            }
        }else{
            $member = $mObj->dump(array('m_memeber_num'=>$post['account']['m_memeber_num']),'member_id');
            
            $member['account']['uname']=empty($post['account']['mobile'])?$post['consignee']['mobile']:$post['account']['mobile'];
            $member['m_memeber_num']=$post['account']['m_memeber_num'];
            $member['m_memeber_card']=$post['account']['m_memeber_card'];
            $member['contact']['name']=$post['account']['name'];
            $member['contact']['phone']['mobile']=empty($post['account']['mobile'])?$post['consignee']['mobile']:$post['account']['mobile'];
            $member['contact']['area']=$post['address_id'];
            
            if (!$mObj->save($member)){
                return $this->send_error('会员更新失败 请重试');
            }
            
            
        } 
        unset($post['account']);
        unset($post['products']);
        
        $post['member_id'] = $member['member_id'];
        if (!$post['member_id'])
            return $this->send_error('请选择会员');
        if (!$post['cost_shipping'])
            $post['cost_shipping'] = 0;
        if (!$post['discount'])
            $post['discount'] = 0;

        $ship = $post['address_id'];
        #检测是不是货到付款
        if($post['is_cod'] == 'true' || $post['is_cod'] == 'false'){
            $is_code = $post['is_cod'];
        }
        $shipping = array();
        if ($ship){
            $shipping = array(
                'shipping_name' => '快递',
                'cost_shipping' => $post['cost_shipping'],
                'is_protect' => 'false',
                'cost_protect' => 0,
                'is_cod' => $is_code?$is_code:'false'
            );
        }else {
            return $this->send_error('请选择物流信息');
        }
        $num = $post['num'];
        $price = $post['price'];
        $true_price = $post['true_price'];
       
        $iorder = $post['order'];
        $iorder['consignee'] = $consignee;
        $iorder['shipping'] = $shipping;

        //goods
        $intTotalNums=0;
        $ax_pmt_price=0;
        if ($num)
        foreach ($num as $k => $i){
            $p = $pObj->dump(substr($k,0,strpos($k,'_')));
            
            $z_g_tpye=$i['type'];
            $z_price=$price[$k];
            $z_true_price=$true_price[$k];
            if($z_g_tpye=="gift"){
                $z_p_tpye='gift';
                $z_price=0;
            }else if($z_g_tpye=="simple"){
                $z_p_tpye='simple';
            }else if($z_g_tpye=="pkg"){
                $z_g_tpye='pkg';
                $z_p_tpye='pkg';
            }else{
                $z_g_tpye='goods';
                $z_p_tpye='product';
            }
            $iorder['order_objects'][] = array(
                'obj_type' => $z_g_tpye,
                'obj_alias' => $z_g_tpye,
                'goods_id' => $p['goods_id'],
                'bn' => $p['bn'],
                'name' => !empty($i['name'])?$i['name']:$p['name'],
                'pkg_name'=>$i['pkg_name'],
                'pkg_id'=>$i['pkg_id'],
                'pkg_bn'=>$i['pkg_bn'],
                'pkg_price'=>$i['pkg_price'],
                'pkg_num'=>$i['pkg_num'],
                'price' => $price[$k],
                'sale_price'=>$z_true_price*$i['num'],
                'amount' => $z_true_price*$i['num'],
                'quantity' => $i['num'],
                'order_items' => array(
                    array(
                        'product_id' => $p['product_id'],
                        'bn' => $p['bn'],
                        'name' => !empty($i['name'])?$i['name']:$p['name'],
                        'price' => $z_price,
                        'true_price'=>$z_true_price,
                        'amount' => $z_true_price*$i['num'],
                        'sale_price'=> $z_true_price*$i['num'],
                        'ax_pmt_price'=>$i['pmt_price'],
                        'pmt_price'=>$i['pmt_price'],
                        'ax_pmt_percent'=>$i['pmt_percent'],
                        'quantity' => $i['num'],
                        'is_mcd_product' => $i['is_mcd_product'],
                        'sendnum' => 0,
                        'item_type' => $z_p_tpye,
                        'message1' => $i['message1'],
                        'lettering_type' => $i['lettering_type'],
                        'message3' => $i['message3'],
                        'message4' => $i['message4'],
                    )
                )
            );
            $ax_pmt_price=$ax_pmt_price+$i['pmt_price'];
            
            $weight += $i['num']*$p['weight'];
            if(strpos($k,'_pkg')==true){
                $pkg_cost[$i['pkg_id']]=$i['pkg_price']*$i['pkg_num'];
                $intTotalNums=$intTotalNums+$i['num'];
            }else{
                $item_cost += $i['num']*$price[$k];
                $intTotalNums=$intTotalNums+$i['num'];
            }
        }
        
        if(isset($pkg_cost)){
            foreach($pkg_cost as $cost){
                $item_cost=$cost+$item_cost;
            }
        }
        
        $iorder['golden_box']=$post['golden_box']=="1"?true:false;//金色礼盒
        if(!empty($post['ribbon_sku'])){
            $iorder['ribbon_sku']=$post['ribbon_sku'];
            $intTotalNums=$intTotalNums+1;
        }
        $iorder['order_refer_source']=$post['order_refer_source'];
        
        if (!empty($lettering)){
            $iorder['is_lettering']=true;
            $c_memo = array('op_name'=>'系统', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>'刻字订单');
            $tmp[]  = $c_memo;
            $iorder['custom_mark']  = serialize($tmp);
            $tmp = null;
        }
        
        if($post['is_presell']=="1"){
            $iorder['is_prepare']=true;
            $post['order_memo']='预售订单'.$post['order_memo'];
        }
        if ($post['order_memo']){
            $o_memo =  htmlspecialchars($post['order_memo']);
            $o_memo = array('op_name'=>'系统', 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$o_memo);
            $tmp[]  = $o_memo;
            $iorder['mark_text']    = serialize($tmp);
            $tmp = null;
        }
        
        //mcd
        $iorder['is_mcd'] = $post['is_mcd'];
        $iorder['mcd_package_sku'] = $post['mcd_package_sku'];
        //is_cvd
        $iorder['is_cvd'] = $post['is_cvd'];
        //is_gift_choose
        if($post['is_gift_choose'] == '1') {
            $iorder['is_gift_choose'] = true;
        }
        
        foreach($post['giftmessage'] as $message){
            if(!empty($message)){
                if($post['is_mcd']=="1"&&$iorder['mcd_package_sku']=="MCD"){
                    $iorder['is_mcd_card']=true;
                }else{
                    $iorder['is_card']=true;
                }
                $intTotalNums=$intTotalNums+1;
                break;
            }
        }
        
        if($post['shop_id']){
            $shop = explode('*',$post['shop_id']);
            $iorder['shop_id'] = $shop[0];
            $iorder['shop_type'] = $shop[1];
        }else{
            return $this->send_error('请选择来源店铺！');
        }

        $iorder['member_id']    = $post['member_id'];
        $iorder['weight']       = $weight;
        $iorder['title']        = $p['bn'].$p['name'];
        $iorder['createtime']   = isset($post['createtime'])?$post['createtime']:time();
        $iorder['ip']           = $_SERVER['REMOTE_ADDR'];
        $iorder['cost_item']    = $item_cost;
        $iorder['currency']     = 'CNY';
        $iorder['discount']     = $post['discount'];
        $iorder['message1']     = $post['giftmessage']['message1'];
        $iorder['message2']     = $post['giftmessage']['message2'];
        $iorder['message3']     = $post['giftmessage']['message3'];
        $iorder['message4']     = $post['giftmessage']['message4'];
        $iorder['message5']     = $post['giftmessage']['message5'];
        $iorder['message6']     = $post['giftmessage']['message6'];
        //echo "<pre>";print_r($post);print_r($iorder);exit();
        //改成默认有welcomecard
        $iorder['is_w_card']=true;
        $intTotalNums=$intTotalNums+1;
        
        $iorder['welcomecard']     = $post['welcomecard'];
        $iorder['itemnum']      = $intTotalNums;//count($iorder['order_objects']);
        if($post['is_wechat']){
            $iorder['is_wechat']      ='yes';
        }
        $iorder['wechat_openid']      = $post['wechat_openid'];
        
        if($post['is_cod']!='true'){
            $iorder['total_amount'] = $mathLib->number_plus(array($item_cost,$post['cost_shipping']));
        }else{//货到付款手续费
        
            $iorder['payinfo']['cost_payment'] =$post['cost_freight_cod'];
            $iorder['total_amount'] = $mathLib->number_plus(array($item_cost,$post['cost_shipping'],$post['cost_freight_cod']));
        }
        
        $iorder['pmt_cost_shipping'] = $post['pmt_order']-$ax_pmt_price;
        $iorder['total_amount'] = $mathLib->number_minus(array($iorder['total_amount'],$post['pmt_order']));
     
        if($mathLib->number_plus(array($post['pay'],0))!=$iorder['total_amount']){
            return $this->send_error('订单总金额不一致');
        }
        $iorder['pmt_order']    = $iorder['pmt_cost_shipping'];
        $iorder['pmt_goods']    = $ax_pmt_price;
            
        $iorder['is_delivery']  = 'Y';
        $iorder['source']  = 'local';//订单来源标识，local为本地新建订单
        $iorder['createway'] = 'local';
        #新建订单时，要开票的
        
        if($post['is_tax'] == 'true'){
            $iorder['is_tax'] = $post['is_tax'];
            $iorder['tax_title'] = $post['tax_title'];
            $iorder['tax_no'] = $post['tax_no'];

            $iorder['taxpayer_identity_number'] = $post['taxpayer_identity_number'];

            $iorder['invoice_name']=trim($post['invoice_name']);
            if(!$iorder['invoice_area']=$this->checkArea($post['invoice_area'])){
                return $this->send_error('发票地区不正确');
            }
            $iorder['invoice_addr']=$post['invoice_addr'];
            $iorder['invoice_zip']=$post['invoice_zip'];
            $iorder['invoice_contact']=$post['invoice_contact'];

            //if(urldecode($post['invoice_type'])=='电子发票'){
                $iorder['is_einvoice']='true';
        //  }
        }

        if ($iorder['total_amount'] < 0)
            return $this->send_error('订单金额不能小于0');
        
        if($post['is_cod']!='true'){
            if(empty($post['trade_no'])){
                return $this->send_error('trade_no必须填写');
            }
        }
        $iorder['order_bn'] = $post['order_bn'];//$oObj->gen_id();
        $iorder['trade_no'] = $post['trade_no'];
        $iorder['paytime'] = $post['paytime'];
        $iorder['order_pmt']=$post['order_pmt'];//订单优惠方案

        //设置订单失败时间
        $iorder['order_limit_time'] = time() + 60*(app::get('ome')->getConf('ome.order.failtime'));
        
        $pay_bn=$oPcfg->getList('id,pay_bn,custom_name',array('pay_bn'=>$post['pay_bn']));//支付方式
        if(empty($pay_bn)){
            return $this->send_error('不存在的支付方式');
        }else{
            $iorder['pay_bn']=$pay_bn['0']['pay_bn'];
            $iorder['payment']=$pay_bn['0']['custom_name'];
            $iorder['pay_id']=$pay_bn['0']['id'];
        }
      
        $transaction = $oObj->db->beginTransaction();
        if(!$oObj->create_order($iorder)){
            $oObj->db->rollBack();
            return $this->send_error('订单保存失败.请重试');
        }
        //生成收款单
        if($post['is_cod']!='true'){//货到付款不生成收款单
            if(!$this->do_payorder($iorder)){
                $oObj->db->rollBack();
                return $this->send_error('订单保存失败.请重试');
            }
        }
        
        $oObj->db->commit($transaction);
        #货到付款类型订单，增加应收金额
        if($is_code == 'true'){
            $oObj_orextend = kernel::single("ome_mdl_order_extend");
            $code_data = array('order_id'=>$iorder['order_id'],'receivable'=>$iorder['total_amount'],'sellermemberid'=>$iorder['member_id']);
            $oObj_orextend->save($code_data);
            
        }
        
	#### 订单状态回传kafka august.yao 已支付 start ####
        $kafkaQueue = app::get('ome')->model('kafka_queue'); // 引入kafka接口操作类
        $queueData = array(
            'queue_title' => '订单已支付状态推送',
            'worker'      => 'ome_kafka_api.sendOrderStatus',
            'start_time'  => time(),
            'params'      => array(
                'status'   => 'paid',
                'order_bn' => $iorder['order_bn'],
                'logi_bn'  => '',
                'shop_id'  => $iorder['shop_id'],
                'item_info'=> array(),
                'bill_info'=> array(),
            ),
        );
        $kafkaQueue->save($queueData);
        #### 订单状态回传kafka august.yao 已支付 end ####
        if($post['is_tax'] == 'true'){
            $order_id = $oObj->getList('order_id',array('order_bn'=>$post['order_bn']));
            if($order_id){
                $data = array(
                    'order_id' => $order_id[0]['order_id'],
                    'order_bn' => $post['order_bn'],
                    'invoice_type' => 'ready',
                );
                $objInvoice = app::get('einvoice')->model('invoice');
                $objInvoice->insert($data);
            }
        }

        //小程序发送模板消息
        if($post['order_refer_source']=="minishop"){//EC小程序
            $iorder['address_id']=$address_id;
            $iorder['form_id']=$post['form_id'];
            kernel::single("giftcard_wechat_request_message")->send($iorder);
        
        }

        return $this->send_succ('创建成功');
    }
    
    public function do_payorder($iorder){
        
        
        $paymentCfgObj = kernel::single("ome_mdl_payment_cfg");
        $objOrder = kernel::single("ome_mdl_orders");
        $objMath = kernel::single('eccommon_math');
        $oPayment = kernel::single("ome_mdl_payments");
        
        $pay_money=$iorder['total_amount'];
        $orderdata = array();
         
        $orderdata['order_id'] = $iorder['order_id'];
        $orderdata['pay_bn'] = $iorder['pay_bn'];
        $orderdata['payed'] = $objMath->number_plus(array(0,$pay_money));
        $orderdata['payed'] = floatval($orderdata['payed']);
        //$aORet['total_amount'] = floatval($aORet['total_amount']);
         
        $orderdata['pay_status'] = 1;
         
        $orderdata['paytime'] = $iorder['paytime'];
        $orderdata['payment'] = $iorder['payment'];
        $pay_id=$iorder['pay_id'];
        //  echo "<pre>"; print_r($iorder);print_r($orderdata);exit();
        $filter = array('order_id'=>$iorder['order_id']);
        if(!$objOrder->update($orderdata,$filter)){
            return false;
        }
     
         
        //生成支付单
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
        
        //MCD
        if($iorder['is_mcd']=="true"&&$iorder['createway']=="after"){
            $paymentdata['statement_status']='true';//不需要对账
        }
        if(!$oPayment->create_payments($paymentdata)){
            return false;
        }
        
        return true;
    
    }
    
    public function change($params){
        error_log('begin:'.$params['order'],3,DATA_DIR.'/changeorder/'.date("Ymd").'zjrorder.txt');
        error_log('end:'.base64_decode($params['order']),3,DATA_DIR.'/changeorder/'.date("Ymd").'zjrorder.txt');
        $this->params=base64_decode($params['order']);
        $post=json_decode(base64_decode($params['order']),true);
        
        if($this->getSign($post)!==true){
            return $this->send_error('SignError 40002');
        }
        
        $objOrder=kernel::single("ome_mdl_orders");
        $objMember=kernel::single("ome_mdl_members");
        $objDelivery=kernel::single("ome_mdl_delivery");
        $objReship=kernel::single("ome_mdl_reship");
        $objShop=kernel::single("ome_mdl_shop");
        $objProduct=kernel::single("ome_mdl_products");
        $objBp=kernel::single("ome_mdl_branch_product");
        
        $arrOrderBn=$arrOrder=$arrMember=$arrDelivery=$arrShop=$arrPost=$data=array();
        
        $order_bn=$post['order_bn'];
        $arrOrderBn=$objOrder->getList("order_id",array('order_bn'=>$order_bn));
        $order_id=$arrOrderBn[0]['order_id'];
        if(empty($order_id))return $this->send_error('请传入有效订单');
        
        //整理数组
        $str_memo='';
        $arrRelateChangeItems=array();
        foreach($post['items'] as $k=>$items){
            
            $memo='';//兑换明细一对一
            
            foreach($items as $return_type=>$product){
                if($return_type=="return"){
                    $memo="货号：".$product['bn'].$memo;
                }else{
                    $memo=$memo." 换货：".$product['bn']." 数量：1  |  ";
                }
                
                if(isset($arrPost[$return_type][$product['bn']])){
                    $arrPost[$return_type][$product['bn']]['num']=$arrPost[$return_type][$product['bn']]['num']+1;
                }else{
                    $arrPost[$return_type][$product['bn']]['num']=$product['num'];
                }
                $arrPost[$return_type][$product['bn']]['bn']=$product['bn'];
                $arrPost[$return_type][$product['bn']]['price']=$product['price'];
                
                $arrRelateChangeItems['items'][$k]=array(
                    'sku'=>$items['return']['bn'],
                    'ex_sku'=>$items['change']['bn'],
                );
            }
            
            $str_memo.=$memo;
        }
        $post=array_merge($post,$arrPost);
        //echo "<pre>";print_r($arrRelateChangeItems);exit;
        //判断是否能够申请
        if(!$objReship->isCanAddMcdReship($order_id,'change',$msg)){
            return $this->send_error($msg);
        }
        
        $arrOrder=$objOrder->dump ( array ('order_id' => $order_id ),'*' );
        $arrMember=$objMember->dump(array('member_id'=>$arrOrder['member_id']));
        $arrDelivery = $objDelivery->getDeliveryByOrder('*',$order_id);
        $arrOrder = array_merge($arrOrder,$arrDelivery[0]);
        $arrOrder['member_id'] = $arrMember['account']['uname'];
        $arrOrder['createtime'] = date('Y-m-d H:i:s',$arrOrder['createtime']);
        $arrShop=$objShop->getList('shop_id',array('shop_type'=>'magento'));
        
        $data['return_type']='change';
        $data['supplier']=$order_bn;
        $data['m_reship_bn']=$post['exchange_no'];
        $data['order_id']=$order_id;
        $data['is_protect']='false';
        $data['shop_id']=$arrShop[0]['shop_id'];
        $data['shop_type']='ecos.b2c';
        $data['member_id']=$arrOrder['member_id'];
        $data['logi_name']='顺丰速运';
        $data['logi_id']=$arrOrder['logi_id'];
        $data['logi_no']=$arrOrder['logi_no'];
        $data['ship_name']=$arrOrder['ship_name'];
        $data['ship_area']=$arrOrder['ship_area'];
        $data['ship_addr']=$arrOrder['ship_addr'];
        $data['ship_zip']=$arrOrder['ship_zip'];
        $data['ship_tel']=$arrOrder['ship_tel'];
        $data['ship_email']=$arrOrder['ship_email'];
        $data['ship_mobile']=$arrOrder['ship_mobile'];
        $data['delivery']='快递';
        $data['branch_id']='1';
        $data['return_reason']='ACF';
        $data['bmoney']='0';
        $data['cost_freight_money']='0';
        $data['custom_mark'] = serialize(array(array('op_name'=>'system','op_time'=>date('Y-m-d H:i:s',time()), 'op_content'=>htmlspecialchars($post['reason']))));
        $data['memo']=$str_memo;
        
        $return_price=0;
        foreach($post['return'] as $return){
            $bn=$return['bn'];
            $num=$return['num'];
            $price=$return['price'];
            $arrProduct=array();
            $arrProduct=$objProduct->getList("product_id,name",array('bn'=>$bn));
            
            $data['return']['goods_name'][$bn]=$arrProduct[0]['name'];
            $data['return']['product_id'][$bn]=$arrProduct[0]['product_id'];
            $data['return']['effective'][$bn]=$num;
            $data['return']['num'][$bn]=$num;
            $data['return']['branch_id'][$bn]=1;
            $data['return']['price'][$bn]=$price;
            $data['return']['goods_bn'][$bn]=$bn;
            $return_price=$return_price+($price*$num);
        }
        
        $change_price=0;
        foreach($post['change'] as $change){
            $bn=$change['bn'];
            $num=$change['num'];
            $price=$change['price'];
            
            $arrProduct=array();
            $store=0;
            $arrProduct=$objProduct->getList("product_id,name",array('bn'=>$bn));
            //判断库存
            $arrBp=$objBp->getList('store,store_freeze',array('product_id'=>$arrProduct[0]['product_id'],'branch_id'=>1));
            $store=$arrBp['0']['store']-$arrBp['0']['store_freeze'];
            $store=$store-kernel::single("omeftp_auto_update_product")->getHasUseStore($bn);
            if($num>$store){
                return $this->send_error($bn.'库存不足，无法进行换货');
            }
            
            $data['change']['product']['name'][$bn]=$arrProduct[0]['name'];
            $data['change']['product']['product_id'][$bn]=$arrProduct[0]['product_id'];
            $data['change']['product']['sale_store'][$bn]=$objProduct->get_product_store(1,$arrProduct[0]['product_id']);
            $data['change']['product']['num'][$bn]=$num;
            $data['change']['product']['branch_id'][$bn]=1;
            $data['change']['product']['price'][$bn]=$price;
            $data['change']['product']['bn'][$bn]=$bn;
            $change_price=$change_price+($price*$num);
        }
        if($change_price!=$return_price){
            return $this->send_error('换货金额异常，无法进行换货');
        }
        //echo "<pre>2";print_r($post);print_r($data);print_r($str_memo);exit;
        $db=kernel::database();
        $transaction =  $db->beginTransaction();
        
        $reship_bn = $objReship->create_treship($data,$msg);
        
        if($reship_bn == false) {
            $db->rollBack();
            return $this->send_error($msg);
        }
        
        if($reship_bn){
            $refund=$money=array();
            $money = kernel::single('ome_return_rchange')->calDiffAmount($data);
            $refund['totalmoney'] = $money['totalmoney'];
            $refund['tmoney'] = $money['tmoney'];
            $refund['bmoney'] = $money['bmoney'];
            $refund['bcmoney'] = $money['bcmoney'];
            $refund['diff_money'] = $money['diff_money'];
            $refund['change_amount'] = $money['change_amount'];
            $refund['diff_order_bn'] = $data['diff_order_bn'] ? $data['diff_order_bn'] : '';
            $refund['cost_freight_money'] = $money['cost_freight_money'];
            
            $refund['is_check']=1;
            $refund['check_time']=time();
            $refund['relate_change_items']=serialize($arrRelateChangeItems);
            if(!$objReship->update($refund,array('reship_bn'=>$reship_bn))){
                $db->rollBack();
                return $this->send_error('System Error');
            }
        }
        $db->commit($transaction);
        //echo "<pre>";print_r($data);exit;
        //锁库存
        $reship_id=$objReship->getList("reship_id",array('reship_bn'=>$reship_bn));
        $reship_id=$reship_id[0]['reship_id'];
        kernel::single('console_reship')->change_freezeproduct($reship_id,'+');
        //传给ax
        $objDeliveryOrder = app::get('ome')->model('delivery_order');
        $delivery_id = $objDeliveryOrder->getList('*',array('order_id'=>$order_id,'status'=>'succ'));
        $delivery_id = array_reverse($delivery_id);
        //kernel::single('omeftp_service_reship')->delivery($delivery_id[0]['delivery_id'],$reship_id,'change');
        //magento前端新建退换货单的时候调用奇门接口
        kernel::single('qmwms_request_omsqm')->returnOrderCreate($delivery_id[0]['delivery_id'],$reship_id,'change');
        
        return $this->send_succ('succ');
    }
    
    public function checkOrderStatus($params){
        $post=json_decode($params['order'],true);
        $objOrder = kernel::single("ome_mdl_orders");
        error_log('订单check:'.$params['order'],3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
        $order_bn=$post['order_bn'];
        if(empty($order_bn)){
            return $this->send_error('请传入订单号');
        }
        $isCodOrder=$objOrder->getList("order_bn",array('order_bn'=>$order_bn));
        if(empty($isCodOrder['0']['order_bn'])){
            return $this->send_error('订单不存在');
        }
        
        $isStatus=$objOrder->db->select("SELECT order_bn,order_id,payed FROM sdb_ome_orders WHERE order_bn='$order_bn' AND (process_status='unconfirmed' OR process_status='confirmed')");
        if(!empty($isStatus['0']['order_bn'])){
            return $this->send_succ('1');
        }else{
            return $this->send_succ('0');
        }
        
    }
    public function checkCod($params){
        $this->params=$params['order'];
        $post=json_decode($params['order'],true);
        error_log('订单codcheck:'.$params['order'],3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
        $objOrder = kernel::single("ome_mdl_orders");
        
        $order_bn=$post['order_bn'];
        if(empty($order_bn)){
            return $this->send_error('请传入订单号');
        }
        $isCodOrder=$objOrder->getList("order_bn",array('is_cod'=>'true','order_bn'=>$order_bn,'pay_status'=>'1','ship_status'=>'1'));
        if(empty($isCodOrder['0']['order_bn'])){
            return $this->send_error('请传入有效的订单');
        }
        
        //echo "<pre>";print_r($isCodOrder);exit();
        return $this->send_succ('1');
        //echo "<pre>";print_r($post);exit();
    }
    
    public function refund($params){
        $this->params=$params['order'];
        $post=json_decode($params['order'],true);
        error_log('订单取消:'.$params['order'],3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
        $objOrder = kernel::single("ome_mdl_orders");
        $order_bn=$post['order_bn'];
        //$isCodOrder=$objOrder->getList("order_bn,order_id,payed",array('order_bn'=>$order_bn,'process_status'=>'unconfirmed'));
        $isCodOrder=$objOrder->db->select("SELECT order_bn,order_id,payed FROM sdb_ome_orders WHERE order_bn='$order_bn' AND (process_status='unconfirmed' OR process_status='confirmed')");//getList("order_bn,order_id,payed",array('order_bn'=>$order_bn,'process_status'=>'unconfirmed'));
        
        if(empty($isCodOrder['0']['order_bn'])){
            return $this->send_error('请传入有效订单');
        }
        
        if($post['pay_bn']=="wxpayjsapi"){
            $data['payment']='1';
        }else if($post['pay_bn']=="alipay"){
            $data['payment']='2';
        }else if($post['pay_bn']=="cod"){
            $order_id=$isCodOrder['0']['order_id'];
            $mod = 'async';
            $sync_rs = $objOrder->cancel($order_id,'',true,$mod);
            if($sync_rs['rsp'] == 'success')
            {
                //取消订单发票记录 ExBOY 2014.04.08
                if(app::get('invoice')->is_installed())
                {
                    $Invoice       = &app::get('invoice')->model('order');
                    $Invoice->delete_order($order_id);
                }
                return $this->send_succ('订单取消成功');
            }else{
               return $this->send_error('货到付款订单取消失败原因:'.$sync_rs['msg']);
            }
            exit();
            //echo "<pre>";print_r($isCodOrder);exit();
        }else{
            return $this->send_error('无效的支付方式');
        }
        
        $mathLib = kernel::single('eccommon_math');
        $refundapp = &app::get('ome')->model('refund_apply');
        $oOrderItems = &app::get('ome')->model('order_items');
        $oLoger = &app::get('ome')->model('operation_log');
        $oShop = &app::get('ome')->model ( 'shop' );
        
        $order_id=$isCodOrder['0']['order_id'];
        $hasRefund=$refundapp->db->select("SELECT order_id,status FROM sdb_ome_refund_apply WHERE order_id='$order_id' AND (status='0' OR status='1' OR status='2' OR status='5')");//0,1,2,5
        if(!empty($hasRefund[0]['order_id'])){
            return $this->send_error('此订单已存在退款单');
        }
        
        $order_id=$isCodOrder['0']['order_id'];
        $data['order_id']=$order_id;
        $data['shop_id']=$this->shop_id;
        $data['order_bn']=$order_bn;
        //$data['back_url']='order_confirm';
        $data['pay_type']='online';
        $data['account']='';
        $data['refund_money']=$isCodOrder['0']['payed'];
        
            $transaction = $objOrder->db->beginTransaction();
            $bcmoney = $mathLib->getOperationNumber($data['bcmoney']);//补偿费用
            $countPrice=0;
            $countPrice=$data['refund_money'];
            $totalPrice=0;
            $totalPrice=$countPrice+$bcmoney;
            //获取支付单号
            $trade_no=$objOrder->db->select("SELECT p.trade_no FROM sdb_ome_orders o LEFT JOIN sdb_ome_payments p ON o.order_id=p.order_id WHERE o.order_id='$order_id' limit 0,1");
            $trade_no=$trade_no['0']['trade_no'];
            if(empty($trade_no)){
                return $this->send_error('订单异常');
            }
            $trade_no=$refundapp->checkRefundApplyBn($trade_no);
            
            $refund_apply_bn = $trade_no;//$refundapp->gen_id();
            
            if ($data['source'] &&  in_array($data['source'],array('archive'))) {
                $objOrder = &app::get('archive')->model('orders');
                $source = $data['source'];
            }else{
                $objOrder = &app::get('ome')->model('orders');
            }
            $orderdata = $objOrder->order_detail($data['order_id']);
            $data=array(
                 'return_id'=>$data['return_id'],
                 'refund_apply_bn'=>$refund_apply_bn,
                 'order_id'=>$data['order_id'],
                 'shop_id'=>$orderdata['shop_id'],
                 'pay_type'=>$data['pay_type'],
                 'bank'=>$data['bank'],
                 'account'=>$data['account'],
                 'pay_account'=>$data['pay_account'],
                 'money'=>$totalPrice,
                 'bcmoney'=>$bcmoney,
                 'apply_op_id'=>kernel::single('desktop_user')->get_id(),
                 'payment'=>is_numeric($data['payment'])?$data['payment']:null,
                 'memo'=>mb_strcut($post['memo'],0,200,'utf-8'),
                 'verify_op_id' =>kernel::single('desktop_user')->get_id(),
                 'addon' => serialize(array('return_id'=>$data['return_id'])),
                 'refund_refer' => $refund_refer,
                 'pay_account'=>$post['BeneficiaryAccountNumber'],//收款人账号
                 'BeneficiaryName'=>$post['BeneficiaryName'],//收款人姓名
                 'BeneficiaryBankName'=>$post['BeneficiaryBankName'],//收款人账号
            );
            if ($source && in_array($source,array('archive'))) {
                $data['source'] = 'archive';
                $data['archive'] = 1;
            }
            $shop_type = $oShop->getShoptype($orderdata['shop_id']);
            $data['shop_type'] = $shop_type;
            $msg = array('result'=>true, 'msg'=>'申请退款成功,单据号为:'.$refund_apply_bn);
            
            $data['create_time'] = time();
            
            if($refundapp->save($data))
            {    
                 if(kernel::single('ome_order_func')->update_order_pay_status($data['order_id'])){
                     $objOrder->db->commit($transaction);
                     
                     //传给买尽头
                     $z_refund_id=$data['apply_id'];
                     app::get('ome')->model('refund_apply')->sendRefundToM($z_refund_id,$order_bn,$totalPrice);
                 
                     return $this->send_succ('申请成功');
                 }else{
                     $objOrder->db->rollBack();
                     return $this->send_error('申请失败');
                 }
            }
            $objOrder->db->rollBack();  
            return $this->send_error('申请失败');
        
    }
    
    public function codeRefund($params){
        $this->params=$params['order'];
        error_log('cod退款单:'.$params['order'],3,DATA_DIR.'/orderadd/'.date("Ymd").'zjrorder.txt');
        $post=json_decode($params['order'],true);
        $objOrder = &app::get('ome')->model('orders');
        
        $order_bn=$post['order_bn'];
        if(empty($order_bn)){
            return $this->send_error('请传入订单号');
        }
        $isCodOrder=$objOrder->getList("order_bn,order_id,total_amount",array('is_cod'=>'true','order_bn'=>$order_bn));
        if(empty($isCodOrder['0']['order_bn'])){
            return $this->send_error('请传入货到付款的订单');
        }
    
        $mathLib = kernel::single('eccommon_math');
        $refundapp = &app::get('ome')->model('refund_apply');
        $oOrderItems = &app::get('ome')->model('order_items');
        $oLoger = &app::get('ome')->model('operation_log');
        $oShop = &app::get('ome')->model ( 'shop' );
        
        $order_id=$isCodOrder['0']['order_id'];
        $apply_id=$post['refund_id'];
        if(empty($apply_id)){
            $hasRefund=$refundapp->db->select("SELECT order_id,status FROM sdb_ome_refund_apply WHERE order_id='$order_id' AND (status!='4' OR status!='5')");
        }else{
            $hasRefund=$refundapp->db->select("SELECT order_id,status FROM sdb_ome_refund_apply WHERE apply_id='$apply_id'");
            if(!empty($hasRefund[0]['order_id'])){//是否能修改
                $status=$hasRefund[0]['status'];
                if($status=="4"){
                    return $this->send_error('此订单已退款成功');
                }
                if($status=="5"){
                    return $this->send_error('此订单证在退款中');
                }
                if($status=="1"||$status=="0"||$status=="2"){//修改
                    $pay_account=$post['BeneficiaryAccountNumber'];
                    $BeneficiaryName=$post['BeneficiaryName'];
                    if($post['iss']=="1"){
                        $BeneficiaryBankName=$post['BankName'];
                        $isk='0';
                        $iss='1';
                    }else{
                        if(strpos($post['BeneficiaryBankName'],'建设银行')!==false){
                            $isk='0';
                        }else{
                            $isk='1';
                        }
                        $BeneficiaryBankName=$post['BankName'];
                        $iss='0';
                    }
                    $BankName=$post['BeneficiaryBankName'];
                    
                    $sql="UPDATE sdb_ome_refund_apply SET pay_account='$pay_account',BeneficiaryName='$BeneficiaryName',BeneficiaryBankName='$BeneficiaryBankName',BankName='$BankName',isk='$isk',iss='$iss' WHERE apply_id='$apply_id'";
                    if($refundapp->db->exec($sql)){
                         return $this->send_succ('修改成功');
                    }else{
                         return $this->send_error('修改失败');
                    }
                    //echo $sql;exit();
                }
            }
            return $this->send_error('修改失败,不存在的退款单');
        }
        //echo "<pre>";print_r($post);exit();
        if(!empty($hasRefund[0]['order_id'])){
            return $this->send_error('此订单已有退款单');
        }
        //getList("order_id",array('order_id'=>$isCodOrder['0']['order_id']));
        
        $data['order_id']=$order_id;
        $data['shop_id']=$this->shop_id;
        $data['order_bn']=$order_bn;
        //$data['back_url']='order_confirm';
        $data['pay_type']='online';
        $data['payment']='4';
        $data['account']='';
        $data['refund_money']=$isCodOrder['0']['total_amount'];
        
            $transaction = $objOrder->db->beginTransaction();
            $bcmoney = $mathLib->getOperationNumber($data['bcmoney']);//补偿费用
            $countPrice=0;
            $countPrice=$data['refund_money'];
            $totalPrice=0;
            $totalPrice=$countPrice+$bcmoney;
            $refund_apply_bn = $refundapp->gen_id();
            if ($data['source'] &&  in_array($data['source'],array('archive'))) {
                $objOrder = &app::get('archive')->model('orders');
                $source = $data['source'];
            }else{
                $objOrder = &app::get('ome')->model('orders');
            }
            $orderdata = $objOrder->order_detail($data['order_id']);
            $data=array(
                 'return_id'=>$data['return_id'],
                 'refund_apply_bn'=>$refund_apply_bn,
                 'order_id'=>$data['order_id'],
                 'shop_id'=>$orderdata['shop_id'],
                 'pay_type'=>$data['pay_type'],
                 'bank'=>$data['bank'],
                 'account'=>$data['account'],
                 'pay_account'=>$data['pay_account'],
                 'money'=>$totalPrice,
                 'bcmoney'=>$bcmoney,
                 'apply_op_id'=>kernel::single('desktop_user')->get_id(),
                 'payment'=>is_numeric($data['payment'])?$data['payment']:null,
                 'memo'=>mb_strcut($post['memo'],0,200,'utf-8'),
                 'verify_op_id' =>kernel::single('desktop_user')->get_id(),
                 'addon' => serialize(array('return_id'=>$data['return_id'])),
                 'refund_refer' => $refund_refer,
                 'pay_account'=>$post['BeneficiaryAccountNumber'],//收款人账号
                 'BeneficiaryName'=>$post['BeneficiaryName'],//收款人姓名
                 'BeneficiaryBankName'=>$post['BeneficiaryBankName'],//收款人账号
                 'isk'=>$post['isk'],
                 'iss'=>$post['iss'],
            );
            if ($source && in_array($source,array('archive'))) {
                $data['source'] = 'archive';
                $data['archive'] = 1;
            }
            $shop_type = $oShop->getShoptype($orderdata['shop_id']);
            $data['shop_type'] = $shop_type;
            $msg = array('result'=>true, 'msg'=>'申请退款成功,单据号为:'.$refund_apply_bn);
            
            $data['create_time'] = time();
           // echo "<pre>11111";print_r($data);exit();
            if($refundapp->save($data))
            {    
                 if(kernel::single('ome_order_func')->update_order_pay_status($data['order_id'])){
                     $objOrder->db->commit($transaction);
                     return $this->send_succ('申请成功');
                 }else{
                     $objOrder->db->rollBack();
                     return $this->send_error('申请失败');
                 }
            }
            $objOrder->db->rollBack();  
            return $this->send_error('申请失败');
        //$oObj->db->rollBack();
        
    }
    
    public function send_succ($msg=''){
        // return $this->_response->output('succ',$msg);
        $rs = array(
            'rsp'      => 'succ',
            'msg'      => $msg,
            'msg_code' => null,
            'data'     => null,
        );
        return $rs;
    }

    public function send_error($msg, $msg_code='', $data=''){
        // return $this->_response->output($rsp='fail', $msg, $msg_code, $data);
        $params=json_decode($this->params,true);
        
        if(app::get('magentoapi')->is_installed()){
            $objMagentoapi=app::get('magentoapi')->model('errororders');
            $error['params']=$this->params;
            $error['apitime']=time();
            $error['err_msg']=$msg;
            $order_bn=$params['order_bn'];
            if(!empty($order_bn)){
                $error['order_bn']=$order_bn;
            }else{
                $error['order_bn']='999';
            }
            $objMagentoapi->save($error);
            
        }
        
        if(!empty($params['order_bn'])){
            if($msg!='订单保存失败.请重试'){
                kernel::single("emailsetting_send")->send("jasmine.yu@d1m.cn",'Dior 订单错误',$msg);
            }
            error_log('订单:'.$params['order_bn']."错误:".$msg,3,DATA_DIR.'/magentoapi/'.date("Ymd").'zjrorder.txt');
        }else{
            error_log('错误:'.$msg,3,DATA_DIR.'/magentoapi/'.date("Ymd").'zjrorder.txt');
        }
        //echo "<pre>";print_r($error);
        //echo 1;exit();
        $rs = array(
            'rsp'      => 'fail',
            'msg'      => $msg,
            'msg_code' => $msg_code,
            'data'     => $data,
        );
        return $rs;
    }


    public function getInvoice($params){
        $sign = $params['sign'];
        if($sign!="123456"||empty($sign)){
            return $this->send_error('pwd不正确');
        }
        
        error_log('补开发票:'.$params['order'],3,DATA_DIR.'/orderadd/'.date("Ymd").'einvoice.txt');
        $post=json_decode($params['order'],true);
//echo "<pre>";print_r($params);exit;
        $objOrder = &app::get('ome')->model('orders');

        $order_bn = $post['order_bn'];

        $info = $objOrder->getList('*',array('order_bn'=>$order_bn));
        if(empty($info)){
            return $this->send_error('订单不存在！');
        }

        $info = $info[0];

        $data = array(
                'taxpayer_identity_number'=>$post['taxpayer_identity_number'],
                'tax_company'=>$post['tax_title'],
                'is_einvoice'=>'true',
            );
        //echo "<pre>";print_r($data);exit;
        $objOrder->update($data,array('order_id'=>$info['order_id']));
        
        $edata = array(
                'order_id' => $info['order_id'],
                'order_bn' => $info['order_bn'],
                'invoice_id' => $res['id'],
                'invoiceCode' => $res['invoiceCode'],
                'invoiceNo' => $res['invoiceNo'],
                'invoiceTime' => $res['invoiceTime'],
                'pdfUrl' => $res['pdfUrl'],
                'invoice_type' => 'ready',
            );
            
        $objInvoice = app::get('einvoice')->model('invoice');
        $objInvoice->insert($edata);//echo "<pre>";print_r(2);exit;
        if($info['ship_status']=='1'){
            kernel::single('einvoice_request_invoice')->invoice_request($info['order_id'],'getApplyInvoiceData');
        }
        if(($info['ship_status']=='3'&&$info['pay_status']=='4')||($info['ship_status']=='3'&&$info['pay_status']=='3')){
            kernel::single('einvoice_request_invoice')->invoice_request($info['order_id'],'getApplyInvoiceData');
        }
         $res=$this->send_succ('申请成功');
         //print_r($res);exit;
         echo json_encode($res);exit;

    


    }
}