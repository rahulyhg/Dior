<?php

/**
 * 奇门标准化WMS流程（OMS->WMS）
 * 处理奇门接口返回信息，记录日志等
 */
class qmwms_request_omsqm extends qmwms_request_qimen{

    public function __construct($app){
        $this->log_mdl = app::get('qmwms')->model('qmrequest_log');
    }

    //发货单创建
    public function deliveryOrderCreate($order_id){
        $method = 'deliveryorder.create';
        $msg = '发货单创建';
        $res = $this->_deliveryOrderCreate($order_id);
        $body = $res['body'];
        $order_bn = $res['order_bn'];

        //记录ERP请求日志
        $data = $this->pre_params($order_bn,$order_id,$method,$msg,$body);
        $insert_id = $this->writeLog($data);
        $response = kernel::single('qmwms_request_abstract')->request($body,$method);
        //检查接口string返回是否是XML
        $is_xml = $this->check_xml($response);
        //ERP请求奇门返回信息写日志
        if(isset($insert_id)){
            $res_data = $this->res_params($response,$order_id,'deliveryOrderCreate',null);
            if(!isset($response) || empty($response) || !$is_xml){
                $res_data['status'] = 'failure';
                $res_data['res_msg'] = '单据创建失败';
            }
            $this->writeLog($res_data,$insert_id);

            if($res_data['status']=='success'){
                kernel::single('omemagento_service_order')->update_status($order_bn,'sent_to_ax');
            }else{
                //发送报警邮件
                $failure_msg = !empty($res_data['res_msg'])?$res_data['res_msg']:$response;
                $original_params = htmlspecialchars($body);
                $response_params = htmlspecialchars($response);
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

                $subject = '【Dior-PROD】ByPass订单#'.$order_bn.'发货创建失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>OMS请求XML：<br>$original_params<br/><br>WMS返回XML：<br>$response_params<br/><br>失败信息：<br>$failure_msg<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                //kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);//关闭即时触发邮件
            }
        }
    }

    //退货入库单创建
    public function returnOrderCreate($delivery_id,$reship_id,$return_type='return',$change=array()){
        $method = 'returnorder.create';
        $msg = '退货入库单创建';
        //组织请求数据体
        $res  = $this->_returnOrderCreate($reship_id);
        $body = $res['body'];
        $reship_bn = $res['reship_bn'];

        //记录ERP请求日志
        $data = $this->pre_params($reship_bn,$reship_id,$method,$msg,$body,$return_type,$change);
        $insert_id = $this->writeLog($data);
        $response = kernel::single('qmwms_request_abstract')->request($body,$method);
        //检查接口string返回是否是XML
        $is_xml = $this->check_xml($response);
        //ERP请求奇门返回信息写日志
        if(isset($insert_id)){
            $res_data = $this->res_params($response,$reship_id,'returnOrderCreate',null);
            if(!isset($response) || empty($response) || !$is_xml){
                $res_data['status'] = 'failure';
                $res_data['res_msg'] = '单据创建失败';
            }
            $this->writeLog($res_data,$insert_id);

            if($res_data['status']=='success'){
                $delivery = app::get('ome')->model('delivery')->dump($delivery_id);
                $delivery = kernel::single('omeftp_service_reship')->format_delivery($delivery);

                $orderReship = app::get('ome')->model('reship_items');
                $objReship = app::get('ome')->model('reship');

                //如果是magento发起的 直接返回无需再走下去
                if($return_type=="change"&&empty($change)){
                    return true;
                }
                if($return_type=="change"){
                    kernel::single('omemagento_service_change')->sendChangeOrder($change);
                }else{

                    // 售后生成的新订单退货需传原始订单号,原始退的商品
                    $arrOriginalOrder = $arrReship = array();
                    if($delivery['order']['createway'] == "after"){
                        $arrOriginalOrder = $objReship->getOriginalOrder($delivery['order']['order_bn']);
                        $order_bn = $arrOriginalOrder['relate_order_bn']; // 老订单号
                        $order_id = $arrOriginalOrder['order_id']; // 新订单号
                        // 新订单号即为reship表的p_order_id,来查询退了哪些
                        $arrReship = $objReship->getList("reship_id,relate_change_items",array('p_order_id'=>$order_id));
                        $relate_reship_id = $arrReship[0]['reship_id'];
                        $relate_change_items = unserialize($arrReship[0]['relate_change_items']);
                        // 查看当前退货单退的商品
                        $arrReshipItems = app::get('ome')->model('reship_items')->getList('*',array('reship_id'=>$reship_id,'return_type'=>'return'));
                        //当前退货单退的商品即使原始退货单所换的商品，关联原始退的商品数量
                        $arrRelateReturn = array();
                        foreach($arrReshipItems as $k=>$reship){
                            foreach($relate_change_items['items'] as $relate){
                                if($arrReshipItems[$k]['num']>0&&$relate['ex_sku']==$reship['bn']){
                                    if(isset($arrRelateReturn[$relate['sku']])){
                                        $arrRelateReturn[$relate['sku']]['nums']=$arrRelateReturn[$relate['sku']]['nums']+1;
                                    }else{
                                        $arrRelateReturn[$relate['sku']]['nums']=1;
                                    }
                                    $arrReshipItems[$k]['num']--;
                                }
                            }
                        }
                    }else{
                        $order_bn=$delivery['order']['order_bn'];
                        $relate_reship_id=$reship_id;
                    }

                    ###### 订单状态回传kafka august.yao 退货申请中 start####
                    $orderData  = app::get('ome')->model('orders')->getList('*',array('order_bn'=>$delivery['order']['order_bn']));
                    $kafkaQueue = app::get('ome')->model('kafka_queue');
                    $queueData = array(
                        'queue_title' => '订单退货申请中状态推送',
                        'worker'      => 'ome_kafka_api.sendOrderStatus',
                        'start_time'  => time(),
                        'params'      => array(
                            'status'   => 'reshipping',
                            'order_bn' => $order_bn,
                            'logi_bn'  => '',
                            'shop_id'  => $orderData[0]['shop_id'],
                            'item_info'=> array(),
                            'bill_info'=> array(),
                        ),
                    );
                    $kafkaQueue->save($queueData);
                    ###### 订单状态回传kafka august.yao 退货申请中 end ####

                    $reInfo = $orderReship->getList('*',array('reship_id'=>$relate_reship_id,'return_type'=>'return'));
                    $refund_info = array();
                    foreach($reInfo as $reItem){
                        if($delivery['order']['createway']=="after"){
                            if(!isset($arrRelateReturn[$reItem['bn']]))continue;
                            $nums=$arrRelateReturn[$reItem['bn']]['nums'];
                        }else{
                            $nums=$reItem['num'];
                        }
                        $refund_info[] = array(
                            'sku'=>$reItem['bn'],
                            'nums'=>$nums,
                            'price'=>$reItem['price'],
                            'oms_rma_id'=>$reship_id,//始终用新reship_id
                        );
                    }
                    if(!empty($order_bn))kernel::single('omemagento_service_order')->update_status($order_bn,'return_required','',time(),$refund_info);
                }

            }else{
                //发送报警邮件
                $failure_msg = !empty($res_data['res_msg'])?$res_data['res_msg']:$response;
                $original_params = htmlspecialchars($body);
                $response_params = htmlspecialchars($response);
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

                $subject = '【Dior-PROD】ByPass退单#'.$reship_bn.'退货创建失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>OMS请求XML：<br>$original_params<br/><br>WMS返回XML：<br>$response_params<br/><br>失败信息：<br>$failure_msg<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                //kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
        }
    }

    //单据取消
    public function orderCancel($delivery_id,$memo=null){
        $method = 'order.cancel';
        $msg   = '单据取消';
        $res   = $this->_orderCancel($delivery_id,$memo);
        $body  = $res['body'];
        $dj_bn = $res['order_bn'];
        //记录ERP请求日志
        $data = $this->pre_params($dj_bn,$delivery_id,$method,$msg,$body);
        $insert_id = $this->writeLog($data);
        $response = kernel::single('qmwms_request_abstract')->request($body,$method);
        //检查接口string返回是否是XML
        $is_xml = $this->check_xml($response);
        //ERP请求奇门返回信息写日志
        if(isset($insert_id)){
            $res_data = $this->res_params($response,$delivery_id,'orderCancel',$memo);
            if(!isset($response) || empty($response) || !$is_xml){
                $res_data['status'] = 'failure';
                $res_data['res_msg'] = '单据取消失败';
            }
            $res_data['param1'] = $memo;
            $this->writeLog($res_data,$insert_id);

            ###### 订单状态回传kafka august.yao 已取消 start####
            $orderData   = app::get('ome')->model('orders')->getList('*',array('order_bn'=>$dj_bn));
            $kafkaQueue  = app::get('ome')->model('kafka_queue');
            $queueData = array(
                'queue_title' => '订单已取消状态推送',
                'worker'      => 'ome_kafka_api.sendOrderStatus',
                'start_time'  => time(),
                'params'      => array(
                    'status'   => 'cancel',
                    'order_bn' => $orderData[0]['order_bn'],
                    'logi_bn'  => '',
                    'shop_id'  => $orderData[0]['shop_id'],
                    'item_info'=> array(),
                    'bill_info'=> array(),
                ),
            );
            $kafkaQueue->save($queueData);
            ###### 订单状态回传kafka august.yao 已取消 end ####

            //发送报警邮件
            if($res_data['status'] != 'success'){
                $failure_msg = !empty($res_data['res_msg'])?$res_data['res_msg']:$response;
                $original_params = htmlspecialchars($body);
                $response_params = htmlspecialchars($response);
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

                $subject = '【Dior-PROD】ByPass订单#'.$dj_bn.'单据取消失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys   = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>OMS请求XML：<br>$original_params<br/><br>WMS返回XML：<br>$response_params<br/><br>失败信息：<br>$failure_msg<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                //kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
        }
        return $res_data;
    }

    //库存查询
    public function inventoryQuery($bn=array(),$offset,$limit){
        $method = 'inventory.query';
        $msg = '库存查询';
        $body = $this->_inventoryQuery($bn);
        //记录ERP请求日志
        $data = $this->pre_params('','',$method,$msg,$body);
        $insert_id = $this->writeLog($data);
        $response = kernel::single('qmwms_request_abstract')->request($body,$method);
        //检查接口string返回是否是XML
        $is_xml = $this->check_xml($response);
        //ERP请求奇门返回信息写日志
        if(isset($insert_id)){
            $res_data = $this->res_params($response,null,'inventoryQuery',null,$offset,$limit);
            if(!isset($response) || empty($response) || !$is_xml){
                $res_data['status'] = 'failure';
                $res_data['res_msg'] = '库存更新失败';
            }
            $this->writeLog($res_data,$insert_id);
            //发送报警邮件
            if($res_data['status'] != 'success'){
                $failure_msg = !empty($res_data['res_msg'])?$res_data['res_msg']:$response;
                $original_params = htmlspecialchars($body);
                $response_params = htmlspecialchars($response);
                $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

                $subject = '【Dior-PROD】ByPass库存更新失败';//【ADP-PROD】ByPass订单#10008688发送失败
                $bodys   = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>OMS请求XML：<br>$original_params<br/><br>WMS返回XML：<br>$response_params<br/><br>失败信息：<br>$failure_msg<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
                kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
            }
        }
    }

    //记录接口请求和返回信息日志
    public function writeLog($data,$log_id=null){
        if(!$log_id){
            $insert_id = $this->log_mdl->insert($data);

        }else{
            $this->log_mdl->update($data,array('log_id'=>$log_id));
        }
        return $insert_id;
    }

    //请求信息处理
    protected function pre_params($bn,$id,$method,$msg,$body,$param1=null,$param2=null){
        $data = array(
            'original_bn'=> $bn,
            'original_id'=> $id,
            'task_name'=>$method,
            'original_params'=>$body,
            'log_type'=>'向奇门发起请求',
            'msg'=>$msg,
            'createtime'=>time(),
            'param1'=>$param1,
            'param2'=>empty($param2) ? '' : serialize($param2)
        );
        return $data;
    }

    //返回信息处理
    public function res_params($response,$dj_id,$method,$memo,$offset=null,$limit=null){
        //把返回的xml解析成数组
        $_response = kernel::single('qmwms_response_qmoms')->xmlToArray($response);
        //根据不同接口类型进行不同的处理
        if($_response['flag'] == 'success'){
            switch($method){
                case 'deliveryOrderCreate':
                    $ax_order_bn = $_response['deliveryOrderId'];
                    $sql = sprintf("update sdb_ome_orders set ax_order_bn = '%s' where order_id = %s ",$ax_order_bn,$dj_id);
                    if(!empty($ax_order_bn))kernel::database()->exec($sql);
                    break;
                case 'returnOrderCreate':
                    break;
                case 'orderCancel':
                    //$this->do_cancel($dj_id,$memo);
                    break;
                case 'inventoryQuery':
                    //更新系统库存
                    $this->update_store_all($_response,$offset,$limit);
                    break;
                default:
                    break;

            }
        }

        $data = array(
            'response' => $response,
            'status' =>$_response['flag'],
            'res_msg' =>$_response['message'],
        );
        return $data;
    }

    /**
     * @param $order_id
     * 订单取消
     */
    public function do_cancel($delivery_id,$memo){
        $orderId = app::get('ome')->model('delivery_order')->getList('order_id',array('delivery_id'=>$delivery_id));
        $order_id = $orderId[0]['order_id'];
        $oOrder = &app::get('ome')->model('orders');
        $orderdata = $oOrder->dump($order_id);
        $order_bn = $orderdata['order_bn'];
        $memo = "订单被取消 ".$memo;
        $mod = 'sync';
        $oShop = &app::get('ome')->model('shop');
        $c2c_shop_list = ome_shop_type::shop_list();
        $shop_detail = $oShop->dump(array('shop_id'=>$orderdata['shop_id']),'node_id,node_type');
        if(!$shop_detail['node_id'] || in_array($shop_detail['node_type'],$c2c_shop_list) || $orderdata['source'] == 'local'){
            $mod = 'async';
        }
        $sync_rs = $oOrder->cancel($order_id,$memo,true,$mod);
        if($sync_rs['rsp'] == 'success') {
            //取消订单发票记录
            if(app::get('invoice')->is_installed()) {
                $Invoice       = &app::get('invoice')->model('order');
                $Invoice->delete_order($order_id);
            }
            //状态更新到dw
            if(app::get('omedw')->is_installed()){
                kernel::single('omedw_dw_to_order')->send_cancel(array($order_bn));
            }
        }else{
            //error_log(var_export('订单'.$order_bn.'取消失败,原因是:'.$sync_rs['msg'],true),3,__FILE__.'fail.txt');
            error_log(date('Y-m-d H:i:s').'订单'.$order_bn.'取消失败,原因是:'."\r\n".var_export($sync_rs['msg'],true)."\r\n", 3, __FILE__.'fail.txt');
        }

    }

    /**
     * @param $res
     * 读取WMS库存 同步到Magento
     */
    public function update_store_all($res,$offset,$limit){
        $product_mdl = app::get('ome')->model('products');
        $branch_product = app::get('ome')->model('branch_product');
        $all_product = $product_mdl->db->select("select p.product_id,p.bn from sdb_ome_products as p left join sdb_ome_goods as g on g.goods_id=p.goods_id where g.is_prepare='false' order by p.product_id ASC limit $offset,$limit ");

        if(!$res['items']['item'][0]){
            $res['items']['item'] = array($res['items']['item']);
        }
        $wms_all_products = array();
        foreach($res['items']['item'] as $items){
            $product_id = $product_mdl->getList('product_id',array('bn'=>$items['itemCode']));
            if(empty($product_id[0])){
                continue;
            }
            $wms_all_products[$items['itemCode']] = $items;
        }
		
		$arrStock=array();
        
		foreach($all_product as $product){
            $bn = $product['bn'];
            if($wms_all_products[$bn]){
                $wms_product = $wms_all_products[$bn];
                $store_freeze = $branch_product->getList('store,store_freeze,safe_store,store_freeze_change',array('product_id'=>$product['product_id'],'branch_id'=>1));
                $oms_store = $store_freeze[0]['store'];//OMS系统库存
                $oms_freeze = $store_freeze[0]['store_freeze'];//OMS冻结库存
                $wms_store = $wms_product['quantity'];//获取的WMS库存
                $store = $wms_product['quantity']+$store_freeze[0]['store_freeze']-$store_freeze[0]['store_freeze_change'];
                $re = $branch_product->change_store(1,$product['product_id'],$store);
                if($re){
                    $hasUser = kernel::single('omeftp_auto_update_product')->getHasUseStore($product['bn']);
                    $magentoStore = $wms_product['quantity']-$hasUser-$store_freeze[0]['store_freeze_change'];
                    if($magentoStore<0){
                        $magentoStore = 0;
                    }
					
					$arrStock[]=array(
						'sku'=>$product['bn'],
						'number'=>$magentoStore,
					);
                }
            }else{
                $store_freeze = $branch_product->getList('store,store_freeze',array('product_id'=>$product['product_id'],'branch_id'=>1));
                $oms_store = $store_freeze[0]['store'];//OMS系统库存
                $oms_freeze = $store_freeze[0]['store_freeze'];//OMS冻结库存
                $wms_store = 0;//获取的WMS为0
                $store = $store_freeze[0]['store_freeze']?$store_freeze[0]['store_freeze']:0;
                $re = $branch_product->change_store(1,$product['product_id'],$store);
                if($re){
                    $hasUser = kernel::single('omeftp_auto_update_product')->getHasUseStore($product['bn']);
                    $magentoStore = 0;
                    $arrStock[]=array(
						'sku'=>$product['bn'],
						'number'=>$magentoStore,
					);
                }
            }

            # 记录商品库存更新记录
            $log_dir=DATA_DIR.'/stock_log/';
            # 创建日志目录
            if(!is_dir($log_dir)){
                mkdir($log_dir,0777,true);
                chmod($log_dir,0777);
            }
            $var_export = "OMS系统更新前库存:".$oms_store.",OMS冻结库存:".$oms_freeze.",获取的WMS库存:".$wms_store.",未审单的库存:".$hasUser.",同步Magento的库存:".$magentoStore;
            error_log(date('Y-m-d H:i:s')."货号".$product['bn']."的库存情况:"."\r\n".var_export($var_export,true)."\r\n",3,$log_dir.'stock'.date('Y-m-d').'.txt');
        }
		
		if(!empty($arrStock)){
			kernel::single('omemagento_service_product')->update_store($arrStock);
		}
    }

    /**
     * @param $response
     * @return bool
     * 检查string是否是xml
     */
    public function check_xml($response){
        $is_xml = true;
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$response,true)){
            xml_parser_free($xml_parser);
            $is_xml = false;
        }
        return $is_xml;
    }

















































}