<?php
/**
 * 奇门标准化WMS流程（WMS->OMS）
 *
 */
class qmwms_response_qmoms{
    protected $method = array(
        //业务接口
        'returnorder.confirm' => 'returnOrderConfirm', //退货入库单确认
        'deliveryorder.confirm' => 'deliveryOrderConfirm', //发货单确认
        'orderprocess.report' => 'orderProcessReport', //订单流水通知接口
        'itemlack.report' => 'itemLackReport', //发货单缺货通知
    );

    protected $exception_code = array(
        'INVALID_PARAMS' => '参数不完整',
        'INVALID_REQUEST_CONTENT' => '无效的请求消息体',
        'INVALID_WMS_CUSTOMERID' => 'WMS系统账号无效',
        'INVALID_SIGNATURE' => '无效签名',
        'INVALID_METHOD' => '不支持的接口请求',
        'INVALID_ORDER_TYPE' => '不支持的单据类型',
        'INVALID_ORDER_CODE' => '单据编码不存在',
        'FAILURE' => '系统处理异常',
        'INVALID_CHECK_STATUS' => '单据状态不允许更新',
        'TO_QC_FAILED' => '退货质检失败',
        'ORDER_ALREADY_SHIPPED' => '单据已发货',
    );

    protected $wms_code = 'OTHER';

    public function __construct(&$app){
        $this->log_mdl = app::get('qmwms')->model('qmrequest_log');
        $this->objectOrder = app::get('ome')->model('orders');
        $this->objectDelivery = app::get('ome')->model('delivery');
        $this->deliveryOrder = app::get('ome')->model('delivery_order');
        $this->objectReship = app::get('ome')->model('reship');
        $this->objectproducts = app::get('ome')->model('products');
        $this->objectgoods = app::get('ome')->model('goods');

    }

    //处理奇门请求OMS消息
    public function qmToOms($content,$params){

        try{
            $format = '';
            if(!empty($params['format'])){
                $format = strtolower($params['format']);
                if(!in_array($format, array('xml'))){
                    $format = '';
                }
            }

            if(empty($format) && !empty($content)){
                $format = $this->get_request_format($content);
            }

            //header('content-type:text/xml;charset=utf-8');

            //检查传参是否完整
            $pass = true;
            $fields = array('method', 'timestamp', 'sign_method', 'app_key', 'v', 'sign', 'customerId');
            foreach($fields as $field){
                if(empty($params[$field])){
                    $pass = false;
                    break;
                }
            }

            if($pass == false){
                throw new Exception('INVALID_PARAMS');
            }elseif(empty($content)){
                throw new Exception('INVALID_REQUEST_CONTENT');
            }

            //请求日志
            $data = array();
            $log_id = $this->log_request(array(
                'method' => $params['method'],
                'customerId' => $params['customerId'],
                'content' => $content,
                'format' => $format
            ), $data);

            //检查请求method
            $method = strtolower($params['method']);
            if(!isset($this->method[$method])){
                throw new Exception('INVALID_METHOD');
            }elseif(empty($data)){
                throw new Exception('INVALID_REQUEST_CONTENT');
            }

            //检查WMS颁发给用户的ID及签名信息是否正确
            $qmwmsApi = app::get('qmwms')->model('qmwms_api');
            $apiData = $qmwmsApi->getList('*',array(),0,1);
            $apiParams = unserialize($apiData[0]['api_params']);

            $is_valid = false;
            if(!empty($apiParams) && isset($apiParams['app_key']) && $apiParams['app_key'] == $params['app_key'] && isset($apiParams['customerId']) && $apiParams['customerId'] == $params['customerId']){
                $is_valid = true;
            }

            if(!$is_valid){
                throw new Exception('INVALID_WMS_CUSTOMERID');
            }
            //签名校验
            $sign = $params['sign'];
            $gue_sign = '' ;
            unset($params['sign']);
            ksort($params);
            foreach($params as $k=>$v){
                $gue_sign .= $k . $v;
            }
            $gue_sign = $apiParams['app_secret'].$gue_sign.$content.$apiParams['app_secret'];
            $gue_sign = strtoupper(md5($gue_sign));

            if($gue_sign !=$sign){
                throw new Exception('INVALID_SIGNATURE');
            }
            if(isset($apiParams['wms_code']) && !empty($apiParams['wms_code'])){
                $this->wms_code = strtoupper($apiParams['wms_code']);
            }

            //对各接口请求数据进行处理
            //$method = 'returnorder.confirm';
            //$content = '' ;
            $this->handle_wms_request($this->method[$method],$content);
            $result = array('status' => 'success','code'=>'200 OK', 'message' => '请求成功');

            $res = $this->response($result, 'xml');
            $this->writeLog($log_id, $result, $res);

        }catch(Exception $e){
            $message = $e->getMessage();

            if(isset($this->exception_code[$message])){
                $code = $message;
                $message = $this->exception_code[$message];
            }else{
                $code = 'FAILURE';
            }

            $result = array('status' => 'failure', 'code' => $code, 'message' => $message);
            $res = $this->response($result, 'xml');

            if(isset($log_id)){
                $this->writeLog($log_id, $result, $res);
            }
            //发送报警邮件
            //$this->send_erro_email($this->method[$method],$content,$log_id);
        }
        //error_log(date('Y-m-d H:i:s').'############$res返回##########:'."\r\n".var_export($res,true)."\r\n", 3, ROOT_DIR.'/data/logs/wmstoms'.date('Y-m-d').'.xml');
        return $res;
    }

    /**
     * 发送报警邮件
     * @param $method
     * @param $content
     */
    public function send_erro_email($method,$content,$log_id){
        $request_log = app::get('qmwms')->model('qmrequest_log')->getList('*',array('log_id'=>$log_id));
        $original_params = htmlspecialchars($request_log[0]['original_params']);
        $response_params = htmlspecialchars($request_log[0]['response']);
        $res_msg         = $request_log[0]['res_msg'];
        $acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');

        if($method == 'deliveryOrderConfirm'){//发货单确认
            $deliveryConfirm = $this->xmlToArray($content);
            $orderBn = $deliveryConfirm['deliveryOrder']['deliveryOrderCode'];
            $subject = '【Dior-PROD】ByPass订单#'.$orderBn.'发货确认失败';
        }
        elseif($method == 'returnOrderConfirm'){//退货入库单确认
            $reshipConfirm = $this->xmlToArray($content);
            $returnOrderCode = $reshipConfirm['returnOrder']['returnOrderCode'];
            $orderBn = substr($returnOrderCode,0,strrpos($returnOrderCode,'-R'));

            $newString = strstr($returnOrderCode, '-R');
            $length = strlen('-R');
            $nums =  substr($newString, $length);

            $orderData = $this->objectOrder->getList('*',array('order_bn'=>$orderBn));
            $orderId = $orderData[0]['order_id'];

            $reshipData = $this->objectReship->getList('*',array('order_id'=>$orderId));
            sort($reshipData);
            $reship     = $reshipData[$nums-1];
            $reship_bn = $reship['reship_bn'];

            $subject = '【Dior-PROD】ByPass退单#'.$reship_bn.'退货确认失败';
        }

        $bodys   = "<font face='微软雅黑' size=2>Hi All, <br/>下面是接口请求和返回信息。<br>WMS请求XML：<br>$original_params<br/><br>OMS响应XML：<br>$response_params<br/><br>失败信息：<br>$res_msg<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
        if(!empty($subject)) kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
    }

    /**
     * @param $request
     * @param $data
     * WMS—>OMS接口请求数据处理
     */
    public function handle_wms_request($method,$content){
        $queue = app::get('qmwms')->model('queue');
        $param['api_method'] = $method;
        $param['api_params'] = $content;
        $param['createtime'] = time();
        $queue->save($param);
    }
    /**
     * @param $content
     * @对发货单确认信息的处理
     */
    public function do_delivery($content){
        $deliveryConfirm = $this->xmlToArray($content);
        $orderBn  = $deliveryConfirm['deliveryOrder']['deliveryOrderCode'];
        $logiNo   = $deliveryConfirm['packages']['package']['expressCode'];
        $packages = $deliveryConfirm['packages']['package'];

        $orderData = $this->objectOrder->getList('*',array('order_bn'=>$orderBn));
        if(empty($orderData)){
            throw new Exception('INVALID_ORDER_CODE');
        }
        $orderId = $orderData[0]['order_id'];

        $deOrder = $this->deliveryOrder->getList('delivery_id',array('order_id'=>$orderId));
        $num = count($deOrder);
        $deliveryId = $deOrder[$num-1]['delivery_id'];
        $deliveryData = $this->objectDelivery->getList('*',array('delivery_id'=>$deliveryId),0,-1);

        if($orderData[0]['ship_status'] == 1){
           return true;
        }

        $status = 'delivery';
        //如果有发票，把发票号写入订单
        $invoice_number = $deliveryConfirm['packages']['package']['invoiceNo'];
        $order_confirm_time = strtotime($deliveryConfirm['deliveryOrder']['orderConfirmTime']);
        $sql_num = 'update sdb_ome_orders set tax_no="'.$invoice_number.'" where order_bn="'.$orderBn.'"';
        $sql_time = 'update sdb_ome_orders set order_confirm_time="'.$order_confirm_time.'" where order_bn="'.$orderBn.'"';

        if(!empty($invoice_number)) kernel::database()->exec($sql_num);
        if(!empty($order_confirm_time)) kernel::database()->exec($sql_time);

        $query_params = array (
            "method" => "wms.delivery.status_update",
            "date" => "",
            "format" => "json",
            "node_id" => "selfwms",
            "app_id" => "ecos.ome",
            "task" => md5(time().$orderBn),
            'delivery_bn'=>$deliveryData[0]['delivery_bn'],
            'invoice_number'=>$invoice_number,
            'logi_id'=>'SF',
            'logi_no'=>$logiNo,
            'warehouse'=>'001',//$delivery['deliveryOrder']['warehouseCode'],
            'status'=>$status,
            'volume'=>'156',
            'weight'=>$deliveryConfirm['packages']['package']['weight'],
            'remark'=>'发货回传',
            'operate_time'=>time(),
        );
        $items = array();
        if(empty($packages['items']['item'][0])){
            $packages['items']['item'] = array($packages['items']['item']);
        }
        foreach($packages['items']['item'] as $item){
            $items[] = array(
                'product_bn'=>$item['itemCode'],
                'nums'=>$item['quantity'],
            );
        }
        error_log(date('Y-m-d H:i:s').'订单'.$orderBn.'返回:'."\r\n".var_export($items,true)."\r\n", 3, __FILE__.'requestitems.txt');

        $query_params['item'] = json_encode($items);
        $query_params['sign'] = $this->_gen_sign($query_params,'');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/index.php/api');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_params);
        $output = curl_exec($ch);
        $info = json_decode($output,1);

        if ($info['rsp'] == 'succ') {
            //发送订单文件到AX
            kernel::single('omeftp_service_delivery')->delivery($deliveryId,'false');

            if($orderData[0]['is_mcd']=="true"&&$orderData[0]['createway']=="after"){
                $post=$arrReship=$arrOrders=array();
                $arrReship=$this->objectReship->getList("m_reship_bn,order_id",array('p_order_id'=>$orderData[0]['order_id']));

                $post['order_bn']=$orderData[0]['relate_order_bn'];//老的订单号
                $post['exchange_no']=$arrReship[0]['m_reship_bn'];
                $post['status']='shipped';
                $post['tracking_code']=$logiNo;
                $post['shipped_at']=date('Y-m-d H:i:s',time());
                kernel::single('omemagento_service_change')->updateStatus($post);
                //换出来的MCD订单判断是否还有其余未发货，如果有等到最后一笔发货后再开
                $arrOrders=$this->objectOrder->getList("order_id",array('relate_order_bn'=>$orderData[0]['relate_order_bn'],'ship_status'=>'0','is_mcd'=>'true','createway'=>'after'));
                if(empty($arrOrders[0]['order_id'])){
                    kernel::single('einvoice_request_invoice')->invoice_request($arrReship[0]['order_id'],'getApplyInvoiceData');
                }
            }else{
                //状态更新到magento
                kernel::single('omemagento_service_order')->update_status($orderBn,'shipped',$logiNo);
                 kernel::single('einvoice_request_invoice')->invoice_request($orderData[0]['order_id'],'getApplyInvoiceData');
            }
            return  true;
        }else{
            //error_log(date('Y-m-d H:i:s').'订单'.$orderBn.'返回:'."\r\n".var_export($info,true)."\r\n", 3, __FILE__.'fail.txt');
            throw new Exception('Order：'.$orderBn.' '.$info['msg']);
        }
    }

    /**
     * @param $content
     * @退货入库单确认信息的处理
     */
    public function do_finish($content){
        $returnPro = app::get('ome')->model('return_process');
        $reshipConfirm = $this->xmlToArray($content);

        $returnOrderCode = $reshipConfirm['returnOrder']['returnOrderCode'];
        $orderBn = substr($returnOrderCode,0,strrpos($returnOrderCode,'-R'));

        $newString = strstr($returnOrderCode, '-R');
        $length = strlen('-R');
        $nums =  substr($newString, $length);

        $orderData = $this->objectOrder->getList('*',array('order_bn'=>$orderBn));
        $orderBn = $orderData[0]['order_bn'];
        $orderId = $orderData[0]['order_id'];

        $reshipData = $this->objectReship->getList('*',array('order_id'=>$orderId));
        sort($reshipData);
        $reship     = $reshipData[$nums-1];

        if(empty($reship)){
            throw new Exception('INVALID_ORDER_CODE');
        }

        $reshipId = $reship['reship_id'];
        $isCheck  = $reship['is_check'];
        $reshipBn = $reship['reship_bn'];
        $returnType = $reship['return_type'];
        $deliveryOrder = $this->deliveryOrder->getList('*',array('order_id'=>$orderId));
        $deliveryId = $deliveryOrder[0]['delivery_id'];

        //单据确认时间
        $order_confirm_time = strtotime($reshipConfirm['returnOrder']['orderConfirmTime']);
        $sql_time = 'update sdb_ome_reship set order_confirm_time="'.$order_confirm_time.'" where reship_bn="'.$reshipBn.'"';
        if(!empty($order_confirm_time)) kernel::database()->exec($sql_time);

        //退货回传判断如果是 拒收直接返回成功  并生成SO文件
        if($returnType =='refuse'){
            //更新到AX
            kernel::single('omeftp_service_back')->delivery($deliveryId,'拒收',$reshipId);

            kernel::single('einvoice_request_invoice')->invoice_request($orderId,'getCancelInvoiceData');//@todo 暂时注释
            return true;
        }

        if(!in_array($isCheck,array('1','3','13'))){
            error_log(var_export($reshipBn,true),3,__FILE__.'error.txt');//记录无法更新的退货单
            throw new Exception('INVALID_CHECK_STATUS');
        }
        if($isCheck == '1'){
            kernel::single('ome_return_rchange')->accept_returned($reshipId,'3',$error_msg);
        }

        $forNum = array();
        $product_process = array();
        //售后服务已处理商品详情
        $returnProDetail = $returnPro->product_detail($reshipId,$orderId);
        foreach($returnProDetail['items'] as $key => $val){
            if($val['return_type'] == 'change'){
                unset($returnProDetail['items'][$key]);
                break;
            }

            if (!isset($bnArr[$val['bn']])) {
                $bnArr[$val['bn']] = $this->objectproducts->dump(array('bn'=>$val['bn']), 'goods_id,barcode,spec_info');;
            }
            $p = $bnArr[$val['bn']];
            if (!isset($gArr[$p['goods_id']])) {
                $gArr[$p['goods_id']] = $this->objectgoods->dump($p['goods_id'], 'serial_number');
            }

            $g = $gArr[$p['goods_id']];
            $mixed_array['bn_'.$val['bn']] = $val['bn'];
            //判断条形码是否为空
            if(!empty($p['barcode'])){
                $mixed_array['barcode_'.$p['barcode']] = $val['bn'];
            }

            /* 退货数量 */
            if($product_process['items'][$val['bn']]){
                $product_process['items'][$val['bn']]['num'] += $val['num'];
            }else{
                $product_process['items'][$val['bn']] = $val;
            }

            if(!empty($serial_product['serial_number'])){
                $product_process['items'][$val['bn']]['serial_number'] = $serial_product['serial_number'];
            }

            $product_process['items'][$val['bn']]['barcode'] = $p['barcode'];

            /* 校验数量 */
            if($val['is_check'] == 'true'){
                $product_process['items'][$val['bn']]['checknum'] += $val['num'];
                $oProduct_pro_detail['items'][$key]['checknum'] = $val['num'];
            }

            $product_process['items'][$val['bn']]['itemIds'][] = $val['item_id'];

            if($val['is_check'] == 'false'){
                /* 退货数量 */
                if($forNum[$val['bn']]){
                    $forNum[$val['bn']] += 1;
                    $oProduct_pro_detail['items'][$key]['fornum'] = $forNum[$val['bn']];
                }else{
                    $oProduct_pro_detail['items'][$key]['fornum'] = 1;
                    $forNum[$val['bn']] = 1;
                }
            }
            $product_process['items'][$val['bn']]['spec_info'] = $p['spec_info'];
            unset($oProduct_pro_detail['items'][$key]);
            $product_process['por_id'] = $val['por_id'];
        }

        $items = array();
        if($reshipConfirm['orderLines']['orderLine'][0]){
        }else{
            $reshipConfirm['orderLines']['orderLine']= array($reshipConfirm['orderLines']['orderLine']);
        }

        foreach($reshipConfirm['orderLines']['orderLine'] as $item){
            $items[] = array(
                'product_bn'=>$item['itemCode'],
                'num'=>$item['actualQty'],
            );
        }

        foreach($items as $val){
            $_POST['bn_'.$val['product_bn']] = $val['product_bn'];
        }
        foreach($product_process['items'] as $key=>$val){
            foreach($val['itemIds'] as $itemId){
                $_POST['instock_branch'][$key.$itemId] = 1;
                $_POST['process_id'][$itemId] = $key;
                $_POST['memo'][$key.$itemId] = '自动质检';
                $_POST['store_type'][$key.$itemId] = 0;
                $_POST['check_num'][$key.$itemId] = 1;
            }
        }

        $_POST['check_type'] = 'bn';
        $_POST['reship_id'] = $reshipId;
        $_POST['por_id'] = $product_process['por_id'];

        $sign = kernel::single('ome_return')->toQC($reshipId,$_POST,$msg);
        if($sign){
            //更新到AX
            kernel::single('omeftp_service_reship')->delivery($deliveryId,$reshipId);

            $createway=$orderData[0]['createway'];
            //售后生成的新订单
            if($createway=="after"){
                $arrOriginalOrder=$this->objectReship->getOriginalOrder($orderBn);
                $orderBn=$arrOriginalOrder['relate_order_bn'];//老订单号
                $orderId=$arrOriginalOrder['relate_order_id'];//老订单ID
            }

            if($returnType=='change'){//状态传给magento
                $arrPostMagento=array();
                $arrPostMagento['status']='exchanging';
                $arrPostMagento['order_bn']=$orderBn;
                $arrPostMagento['exchange_no']=$reship['m_reship_bn'];
                kernel::single('omemagento_service_change')->updateStatus($arrPostMagento);
            }

            if($returnType=='return'){
                $magento_type=NULL;
                $arrRefundApply=$arrOriginalOrder=array();
                $objRefundApply = app::get('ome')->model('refund_apply');
                $arrRefundApply=$objRefundApply->dump(array('reship_id'=>$reshipId),'apply_id,payment,money');
                if($arrRefundApply['payment']=="4"){
                    $magento_type='refund_required';
                }else{
                    $magento_type='refunding';
                }

                kernel::single('omemagento_service_order')->update_status($orderBn,$magento_type,'',time(),array('oms_rma_id'=>$reshipId));
                app::get('ome')->model('refund_apply')->sendRefundToM($arrRefundApply['apply_id'],$orderBn,$arrRefundApply['money'],$reshipId);
            }

            kernel::single('einvoice_request_invoice')->invoice_request($orderId,'getCancelInvoiceData');
            return true;
        }else{
            //error_log(var_export($reshipBn,true),3,__FILE__.'error.txt');//记录无法更新的退货单
            error_log(date('Y-m-d H:i:s').$reshipBn.'质检失败:'."\r\n".var_export($msg,true)."\r\n", 3, __FILE__.'fail.txt');
            throw new Exception('TO_QC_FAILED');
        }
    }

    /**
     * qm2oms接口请求日志
     */
    public function log_request($request,&$data){
        $content = !empty($request['content']) ? $request['content'] : '';
        $data = $this->xmlToArray($content);

        if(!is_array($data)){
            $data = array();
        }
        $original_bn = '';
        if(!empty($data)){
            $fields = array('order', 'returnOrder', 'deliveryOrder');
            foreach($fields as $field){
                if(isset($data[$field])){
                    $original_bn = is_array($data[$field]) ? $data[$field][$field.'Code'] : $data[$field];
                    break;
                }elseif(isset($data[$field.'Code'])){
                    $original_bn = $data[$field.'Code'];
                    break;
                }
            }
        }

        $method = !empty($request['method']) ? strtolower($request['method']) : '';
        if(!empty($method) && isset($this->method[$method])){
            $dj_type = $this->method[$method];
            switch($dj_type){
                case 'returnOrderConfirm': //退货入库单确认（B2C）
                    $original_bn = trim($original_bn);
                    $original_bn = substr($original_bn,0,strrpos($original_bn,'-R'));
                    $msg = '退货入库单确认';
                    break;
                case 'deliveryOrderConfirm': //发货单确认（B2C）
                    $original_bn = trim($original_bn);
                    $msg = '发货单确认';
                    break;
                case 'orderProcessReport': //订单流水通知接口
                    if(in_array($data['order']['orderType'], array('JYCK','HHCK','BFCK','XTRK','HHRK'))){
                        $original_bn = trim($original_bn);
                    }
                    $msg = '订单流水通知';
                    break;
                case 'itemLackReport': //发货单缺货通知（B2C）
                    $original_bn = trim($original_bn);
                    $msg = '发货单缺货通知';
                    break;
            }
        }

        $log = array(
            'original_bn'=>$original_bn,
            'task_name'=>$method,
            'original_params'=>$content,
            'log_type'=>'响应奇门请求',
            'msg'=>$msg,
            'createtime'=>time()
        );
        $insert_id = $this->log_mdl->insert($log);
        return $insert_id;
    }

    //处理OMS响应结果
    protected function response($ret, $type = 'xml', $tag = 'response'){
        $data = array(
            'flag' => !empty($ret['status']) ? strtolower($ret['status']) : 'failure',
            'code' => !empty($ret['code']) ? strtoupper($ret['code']) : '',
            'message' => !empty($ret['message']) ? $ret['message'] : '',
        );
        if(empty($data['code'])){
            $data['code'] = strtoupper($data['flag']);
        }

        if(empty($tag)){
            $tag = 'response';
        }
        if($type == 'xml'){
            $res = kernel::single('qmwms_request_xml')->_array2xml($data, $tag);
        }
        return $res;
    }

    //记录接口请求OMS端应日志
    public function writeLog($log_id, $res, $response){
        $log = array(
            'status'=>$res['status'],
            'response'=>$response,
            'res_msg'=>$res['message']
        );
        $this->log_mdl->update($log,array('log_id'=>$log_id));
    }

    public function get_request_format($str){
        //获取传过来的数据的格式
        $format = '';
        if(strpos($str, '<?xml') === 0) {
            $format = 'xml';
        }
        return $format;
    }

    function xmlToArray($xml){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $array = json_decode(json_encode($xmlstring),true);
        //error_log(date('Y-m-d H:i:s').'#########xmlToArray返回$array:'."\r\n".var_export($array,true)."\r\n", 3, ROOT_DIR.'/data/logs/xmltoarr'.date('Y-m-d').'.xml');
        return $array;
    }


    private function _gen_sign($params,$token){
        return strtoupper(md5(strtoupper(md5($this->_assemble($params))).$token));
    }

    private function _assemble($params){
        if(!is_array($params))  return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params as $key=>$val){
            if(is_null($val))   continue;
            if(is_bool($val))   $val = ($val) ? 1 : 0;
            $sign .= $key . (is_array($val) ? self::_assemble($val) : $val);
        }
        return $sign;
    }

}