<?php
class ome_event_receive_delivery extends ome_event_response{

    public function update($data){
        //参数检查
        if(!isset($data['status'])){
            return $this->send_error('必要参数缺失', $msg_code, $data);
        }
       
        $type = $data['status'];
        unset($data['status']);
        switch ($type){
            case 'delivery':
                return $this->consign($data);
                break;
            case 'print':
                return $this->setPrint($data);
                break;
            case 'check':
                return $this->setCheck($data);
                break;
            case 'cancel':
                return $this->rebackDly($data);
                break;
            case 'update':
                return $this->updateDetail($data);
                break;
            default:
                return $this->send_succ('未知的发货单操作通知行为', $msg_code, $data);
                break;
        }
    }


    /**
     *
     * 发货通知单发货事件处理
     * @param array $data
     */
    public function consign($data){
      
        #[拆单]配置
        $deliveryObj    = &app::get('ome')->model('delivery');
        $split_seting   = $deliveryObj->get_delivery_seting();
        $split_type     = intval($split_seting['split_type']);
        
        $deliveryOrderObj = app::get('ome')->model('delivery_order');
        $opObj = app::get('ome')->model('operation_log');
        
        if(!isset($data['delivery_bn']) || empty($data['delivery_bn'])){
            return $this->send_error('发货单通知单编号参数没有定义', $msg_code, $data);
        }else{
            $delivery_bn = $data['delivery_bn'];
        }
        #确认有没外部发货单号如果有更新
        if ($data['out_delivery_bn']) {
            $oDelivery_ext = app::get('console')->model('delivery_extension');
            $delivery_ext = $oDelivery_ext->dump(array('delivery_bn'=>$delivery_bn),'original_delivery_bn');
            if (!$delivery_ext) {
                $ext_data = array(
                    'delivery_bn'=>$delivery_bn,
                    'original_delivery_bn'=>$data['out_delivery_bn'],
                );
                $oDelivery_ext->save($ext_data);
            }
        }
        if (empty($data['logi_no'])) {
            //return $this->send_error('物流单号不可为空', $msg_code, $data);
        }
        #
        $delivery_data = $deliveryObj->dump(array('delivery_bn'=>$delivery_bn),'status,delivery_id');
         //判断是否发货单已取消如果已取消不更新
        if (in_array($delivery_data['status'],array('cancel','back','return_back'))) {
            //
            $delivery_orderRow = $deliveryOrderObj->getList('order_id',array('delivery_id'=>$delivery_data['delivery_id']),0,-1);
           
            foreach ($delivery_orderRow as $orderrow){
                 
                $opObj->write_log('order_modify@ome',$orderrow['order_id'],'第三方仓库回写:已发货状态,因发货单目前状态为已取消或打回,不更新');
            }
            return $this->send_error('发货单状态为已取消不更新发货状态!', $msg_code, $data);
        }
        if ($delivery_data['status'] == 'succ' ) {
		//	echo 'ii';exit;
            return $this->send_succ('发货单已发货!', $msg_code, $data);
        }

        unset($delivery_data);
        // 加入事务防并发
        kernel::database()->exec('begin');
        $order_fundObj = kernel::single('ome_func');
        $delivery_time        = isset($data['delivery_time']) ? $order_fundObj->date2time($data['delivery_time']) : time();
        $weight               = isset($data['weight']) ? $data['weight'] : 0.00;
        $delivery_cost_actual = isset($data['delivery_cost_actual']) ? $data['delivery_cost_actual'] : 0.00;
       
        //第三方回写发货要更新物流相关信息
        $is_thirdparty = false;
        if(isset($data['logi_id']) && isset($data['logi_no'])){
            // 验证运单号是否存在
            if ($deliveryObj->dump(array('logi_no'=>$data['logi_no'],'delivery_id|noequal'=>$delivery_data['delivery_id']))) {
                return $this->send_error('运单号重复!', $msg_code, $data);
            }

            $dlyCorpObj = app::get('ome')->model('dly_corp');
            $dlyInfo = $dlyCorpObj->dump(array('type'=>$data['logi_id']),'corp_id,name');
            $logi_id = empty($dlyInfo['corp_id']) ? '' : $dlyInfo['corp_id'];
            $logi_name = empty($dlyInfo['name']) ? '' : $dlyInfo['name'];
            $logi_no = $data['logi_no'];
            $is_thirdparty = true;
        }

        //到时候抽出来,包成方法
        $orderObj = app::get('ome')->model('orders');
        //$deliveryObj = app::get('ome')->model('delivery');
        
        $productObj = app::get('ome')->model('products');
        $branch_productObj = app::get('ome')->model('branch_product');
        
        
        #[拆单]发货单状态回写记录表 ExBOY
        $delivery_sync     = &app::get('ome')->model('delivery_sync');

        $deliveryInfo = $deliveryObj->dump(array('delivery_bn'=>$delivery_bn),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));
        if(!isset($deliveryInfo['delivery_id'])){
            return $this->send_error('发货单通知单编号不存在', $msg_code, $data);
        }
         //日志
        $dlyObj = &app::get('wms')->model('delivery');
        $wmsdly = $dlyObj->dump(array('outer_delivery_bn' => $delivery_bn),'delivery_id');
        //
        $order_ids = array();
        $deliveryOrderInfo = $deliveryOrderObj->getList('order_id',array('delivery_id'=>$deliveryInfo['delivery_id']),0,-1);
        if(count($deliveryOrderInfo) > 1){
            $is_bind = true;
            foreach($deliveryOrderInfo as $info){
                $order_ids[] = $info['order_id'];
            }
        }else{
            $is_bind = false;
            $order_ids[] = $deliveryOrderInfo[0]['order_id'];
        }
        foreach ($order_ids as $order_id){
            //检查当前的订单状态
        }

        //检查当前的发货通知单状态


        //发货通知单状态、内容更新(单据状态、打印状态、发货、校验等)
        //库存扣减
        //订单状态、内容变更
        //合并发货单
        if($is_bind){
            $ids = $deliveryObj->getItemsByParentId($deliveryInfo['delivery_id'],'array');
            foreach ($ids as $item){
                $delivery = $deliveryObj->dump($item,'delivery_id,type,is_cod',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));

                $de = $delivery['delivery_order'];
                $or = array_shift($de);
                $ord_id = $or['order_id'];
                if ($delivery['type'] == 'normal'){//如果不为售后生成的发货单，才进行订单发货数量的更新
                    $deliveryObj->consignOrderItem($delivery);
                }
                $dlydata['delivery_id'] = $delivery['delivery_id'];
                $dlydata['process']     = 'true';
                $dlydata['status'] = 'succ';
                $dlydata['delivery_time'] = $delivery_time;
                $deliveryObj->save($dlydata);//更新子发货单发货状态为已发货
                $item_num = $deliveryObj->countOrderSendNumber($ord_id);
                
                #[拆单]判断订单是否全部发完货时,并过滤合并发货单中的父发货单  ExBOY
                $get_dly_process    = $deliveryObj->get_delivery_process($delivery['delivery_id'], 'false', $deliveryInfo['delivery_id']);
                if(empty($get_dly_process['delivery']) && empty($get_dly_process['order_items']))
                {
                    $orderdata['archive'] = 1;//订单归档
                }
                else 
                {
                    $orderdata['archive'] = 0;//有未发货的发货单或未拆单完成
                }

                $orderdata['order_id'] = $ord_id;
                if ($item_num == 0){//已发货
                    if ($delivery['is_cod'] == 'false') {
                        $orderdata['status'] ='finish';
                    }
                    $orderdata['ship_status'] = '1';
                    
                    $orderObj->save($orderdata);//更新订单发货状态
                    //如果是货到付款发货退款中变更为未支付
                    if ($delivery['is_cod'] == 'true') {
                        $orderObj->db->exec("UPDATE sdb_ome_orders set pay_status='0' WHERE order_id=".$ord_id." AND pay_status='7' AND shop_type='360buy' AND source='matrix'");
                    }
                }else {//部分发货
                    $orderdata['ship_status'] = '2';
                    $orderObj->save($orderdata);//更新订单发货状态
                }
                
                #[拆单]新增_发货单状态回写记录  ExBOY
                $dly_data       = array();
                $frst_info      = $orderObj->dump(array('order_id'=>$ord_id), 'shop_id, shop_type, order_bn');
                
                if(!empty($split_seting))
                {
                    $dly_data['order_id']       = $ord_id;
                    $dly_data['order_bn']       = $frst_info['order_bn'];
                    $dly_data['delivery_id']    = $deliveryInfo['delivery_id'];
                    $dly_data['delivery_bn']    = $deliveryInfo['delivery_bn'];
                    $dly_data['logi_no']        = $deliveryInfo['logi_no'];
                    $dly_data['logi_id']        = $deliveryInfo['logi_id'];
                    $dly_data['branch_id']      = $deliveryInfo['branch_id'];
                    $dly_data['status']         = $dlydata['status'];//发货状态
                    $dly_data['shop_id']        = $deliveryInfo['shop_id'];
                    $dly_data['delivery_time']  = $delivery_time;
                    $dly_data['dateline']       = $delivery_time;
                    $dly_data['split_model']    = intval($split_seting['split_model']);//拆单方式
                    $dly_data['split_type']     = intval($split_seting['split_type']);//回写方式
                    
                    $delivery_sync->save($dly_data);
                }
                
                unset($delivery,$dlydata,$orderdata);
            }

            if ($deliveryInfo['type'] == 'normal'){//如果不为售后生成的发货单，才进行货品发货的冻结释放 fix by danny 2012-4-26
                //扣减库存
                $stock = array();
                foreach ($deliveryInfo['delivery_items'] as $dly_item){ //循环大发货单的items数据
                    $product_id = $dly_item['product_id'];
                    $branch_id = $deliveryInfo['branch_id'];
                    $num = $dly_item['number'];//需要扣减的数量
                    //增加branch_product库存的数量改变
                    $branch_productObj->unfreez($branch_id,$product_id,$num);
                    $productObj->chg_product_store_freeze($product_id,$num,"-");
                    //记录商品发货数量日志
                    $deliveryObj->createStockChangeLog($branch_id,$num,$product_id);
                }
            }

            if($is_thirdparty == true){
                if ($logi_id) {
                    $datadly['logi_id'] = $logi_id;
                }
                if ($logi_name) {
                    $datadly['logi_name'] = $logi_name;
                }
                
                $datadly['logi_no'] = $logi_no;
            }

            // 更新主发货单 
            $datadly['delivery_id']          = $deliveryInfo['delivery_id'];
            $datadly['process']              = 'true';
            $datadly['status']               = 'succ';
            $datadly['weight']               = $weight;
            $datadly['delivery_time']        = $delivery_time;
            $datadly['delivery_cost_actual'] = $delivery_cost_actual;
            //打印状态

            if ($deliveryInfo['expre_status'] == 'false' && $deliveryInfo['deliv_status'] == 'false' && $deliveryInfo['stock_status'] == 'false') {
                $datadly['expre_status'] = 'true';
                $datadly['deliv_status'] = 'true';
                $datadly['stock_status'] = 'true';
            }
            // $deliveryObj->save($datadly);

            $affect_row = $deliveryObj->update($datadly,array('delivery_id' => $datadly['delivery_id'],'process' => 'false','change_type'=>'wms'));
            if ( !(is_numeric($affect_row) && $affect_row > 0) ) {
                // 更新失败，回滚事务
                kernel::database()->exec('rollback');

                return $this->send_succ('发货单已发货');
            }
            $op_id = kernel::single('desktop_user')->get_id();
            $opinfo = array();
            if (!$op_id) {
                $opinfo = array(
                    'op_id'   =>16777215,
                    'op_name' =>'system',
                );
            }
            
            #[拆单]日志增加_发货单号 ExBOY
            $delivery_bn_str    = (empty($deliveryInfo['delivery_bn']) ? '' : '（发货单号：'.$deliveryInfo['delivery_bn'].'）');
            
            if ($wmsdly['delivery_id']) {
                $opObj->write_log('delivery_process@wms', $wmsdly['delivery_id'], '发货单发货完成,'.$delivery_bn_str,'',$opinfo);
            }
            $opObj->write_log('delivery_process@ome', $deliveryInfo['delivery_id'], '发货单发货完成,'.$delivery_bn_str,'',$opinfo);
        }else{
            $de = $deliveryInfo['delivery_order'];
            $or = array_shift($de);
            $ord_id = $or['order_id'];
            if ($deliveryInfo['type'] == 'normal'){//如果不为售后生成的发货单，才进行订单发货数量的更新
                $deliveryObj->consignOrderItem($deliveryInfo);
            }

            if($is_thirdparty == true){
                if ($logi_id) {
                    $dlydata['logi_id'] = $logi_id;
                }
                if ($logi_name) {
                    $dlydata['logi_name'] = $logi_name;
                }
                
                $dlydata['logi_no'] = $logi_no;
            }

            // 更新主发货单
            $dlydata['delivery_id']          = $deliveryInfo['delivery_id'];
            $dlydata['process']              = 'true';
            $dlydata['status']               = 'succ';
            $dlydata['weight']               = $weight;
            $dlydata['delivery_time']        = $delivery_time;
            $dlydata['delivery_cost_actual'] = $delivery_cost_actual;
            // $deliveryObj->save($dlydata);
            //打印状态

            if ($deliveryInfo['expre_status'] == 'false' && $deliveryInfo['deliv_status'] == 'false' && $deliveryInfo['stock_status'] == 'false') {
                $dlydata['expre_status'] = 'true';
                $dlydata['deliv_status'] = 'true';
                $dlydata['stock_status'] = 'true';
            }
            $affect_row = $deliveryObj->update($dlydata,array('delivery_id' => $dlydata['delivery_id'],'process' => 'false','change_type'=>'wms'));
            if ( !(is_numeric($affect_row) && $affect_row > 0) ) {
                // 更新失败，回滚事务
                kernel::database()->exec('rollback');

                return $this->send_succ('发货单已发货');
            }

            $item_num = $deliveryObj->countOrderSendNumber($ord_id);
            
            #[拆单]判断订单是否全部发完货 ExBOY
            $get_dly_process    = $deliveryObj->get_delivery_process($deliveryInfo['delivery_id'], 'false');
            if(empty($get_dly_process['delivery']) && empty($get_dly_process['order_items']))
            {
                $orderdata['archive'] = 1;//订单归档
            }
            else
            {
                $orderdata['archive'] = 0;//有未发货的发货单或未拆单完成
            }

            $orderdata['order_id'] = $ord_id;
            if ($item_num == 0){//已发货
                $orderdata['ship_status'] = '1';
                if ($deliveryInfo['is_cod'] == 'false') {
                    $orderdata['status'] ='finish';
                }
                
                $orderObj->save($orderdata);//更新订单发货状态
            }else {//部分发货
                $orderdata['ship_status'] = '2';
                $orderObj->save($orderdata);//更新订单发货状态
            }

            //danny_freeze_stock_log
            $frst_info = $orderObj->dump(array('order_id'=>$ord_id),'shop_id,shop_type,order_bn');
            $GLOBALS['frst_shop_id'] = $frst_info['shop_id'];
            $GLOBALS['frst_shop_type'] = $frst_info['shop_type'];
            $GLOBALS['frst_order_bn'] = $frst_info['order_bn'];
            $GLOBALS['frst_delivery_bn'] = $deliveryInfo['delivery_bn'];
            if ($deliveryInfo['type'] == 'normal'){//如果不为售后生成的发货单，才进行货品发货的冻结释放 fix by danny 2012-4-26
                //扣减库存
                $stock = array();
                foreach ($deliveryInfo['delivery_items'] as $dly_item){ //循环发货单的items数据
                    $product_id = $dly_item['product_id'];
                    $branch_id = $deliveryInfo['branch_id'];
                    $num = $dly_item['number'];//需要扣减的数量
                    //增加branch_product库存的数量改变
                    $branch_productObj->unfreez($branch_id,$product_id,$num);
                    $productObj->chg_product_store_freeze($product_id,$num,"-");
                    //记录商品发货数量日志
                    $deliveryObj->createStockChangeLog($branch_id,$num,$product_id);
                }
            }

            #[拆单]新增_发货单状态回写记录  ExBOY
            if(!empty($split_seting))
            {
                $dly_data       = array();
                $dly_data['order_id']       = $ord_id;
                $dly_data['order_bn']       = $frst_info['order_bn'];
                $dly_data['delivery_id']    = $deliveryInfo['delivery_id'];
                $dly_data['delivery_bn']    = $deliveryInfo['delivery_bn'];
                $dly_data['logi_no']        = $deliveryInfo['logi_no'];
                $dly_data['logi_id']        = $deliveryInfo['logi_id'];
                $dly_data['branch_id']      = $deliveryInfo['branch_id'];
                $dly_data['status']         = $dlydata['status'];//发货状态
                $dly_data['shop_id']        = $deliveryInfo['shop_id'];
                $dly_data['delivery_time']  = $delivery_time;
                $dly_data['dateline']       = $delivery_time;
                $dly_data['split_model']    = intval($split_seting['split_model']);//拆单方式
                $dly_data['split_type']     = intval($split_seting['split_type']);//回写方式
                
                $delivery_sync->save($dly_data);
            }
            
            $op_id = kernel::single('desktop_user')->get_id();
            $opinfo = array();
            if (!$op_id) {
                $opinfo = array(
                    'op_id'   =>16777215,
                    'op_name' =>'system',
                );
            }
            
            #[拆单]日志增加_发货单号 ExBOY
            $delivery_bn_str    = (empty($deliveryInfo['delivery_bn']) ? '' : '（发货单号：'.$deliveryInfo['delivery_bn'].'）');
            
            if ($wmsdly['delivery_id']) {
                $opObj->write_log('delivery_process@wms', $wmsdly['delivery_id'], '发货单发货完成,'.$delivery_bn_str,'',$opinfo);
            }
            $opObj->write_log('delivery_process@ome', $deliveryInfo['delivery_id'], '发货单发货完成,'.$delivery_bn_str,'',$opinfo);
        }
        $fastConsign = false;
        //如果有KPI考核插件，会增加发货人的考核
        if (!$fastConsign) {
            if($oKpi = kernel::service('omekpi_deliverier_incremental')){
                if (kernel::single('desktop_user')->get_id()){
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    $op_id = $opInfo['op_id'];
                    if(method_exists($oKpi,'deliveryIncremental')){
                        $oKpi->deliveryIncremental($op_id,$deliveryInfo['delivery_id']);
                    }
                }
            }
        }
        //如果taoexlib存在，发货短信开启的 发货的时候就发送短信
        if(kernel::service('message_setting')&&defined('APP_TOKEN')&&defined('APP_SOURCE')){
            kernel::single('taoexlib_delivery_sms')->deliverySendMessage($deliveryInfo['logi_no']);
        }

        $soldIoLib = kernel::single('siso_receipt_iostock_sold');
        $soldSalesLib = kernel::single('siso_receipt_sales_sold');

        if($soldIoLib->create(array('delivery_id'=>$deliveryInfo['delivery_id']), $data, $msg)){
            $soldSalesLib->create(array('delivery_id'=>$deliveryInfo['delivery_id'],'iostock'=>$data), $msg);
        }

		

        // 事务提交
        kernel::database()->exec('commit');
        
        //对EMS直联电子面单作处理（以及京东360buy）(京东先回写运单号）
        if (app::get('logisticsmanager')->is_installed()) {
            $channel_type = $deliveryObj->getChannelType($deliveryInfo['logi_id']);
            if ($channel_type  && (in_array($channel_type,array('360buy','taobao'))) && class_exists('logisticsmanager_service_' . $channel_type)) {
                $channelTypeObj = kernel::single('logisticsmanager_service_' . $channel_type);
                $channelTypeObj->delivery($deliveryInfo['delivery_id']);
            }
        }

        //调用发货相关api，比如订单的发货状态，库存的回写，发货单的回写
        $deliveryObj->call_delivery_api($deliveryInfo['delivery_id'], $fastConsign);

        //对EMS直联电子面单作处理（以及京东360buy）
        if (app::get('logisticsmanager')->is_installed()) {
            $channel_type = $deliveryObj->getChannelType($deliveryInfo['logi_id']);
            if ($channel_type && $channel_type == 'ems' && class_exists('logisticsmanager_service_' . $channel_type)) {
                $channelTypeObj = kernel::single('logisticsmanager_service_' . $channel_type);
                $channelTypeObj->delivery($deliveryInfo['delivery_id']);
            }
        }
       //全链路
        $deliveryObj->sendMessageProduce($dly_id, array(10, 11, 12));#淘宝全链路 已打包，已称重，已出库

        return $this->send_succ('发货成功');
    }

    /**
     *
     * 更新接收发货单已打印
     * @param array $data
     */
    public function setPrint($data){
        if(!isset($data['delivery_bn']) || empty($data['delivery_bn'])){
            return $this->send_error('发货单通知单编号参数没有定义', $msg_code, $data);
        }else{
            $delivery_bn = $data['delivery_bn'];
        }

        $dlyObj  = &app::get('ome')->model('delivery');
        $deliveryInfo = $dlyObj->dump(array('delivery_bn'=>$data['delivery_bn']),'*');

        //检查该物流单号是否存在
        if(!isset($deliveryInfo['delivery_id'])){
            return $this->send_error('发货单通知单编号不存在', $msg_code, $data);
        }

        // 发货单状态判断
        if (in_array($deliveryInfo['status'], array('succ','back','stop','cancel','failed','timeout','return_back'))) {
            return $this->send_error('发货单状态异常:'.$deliveryInfo['status'],$msg_code,$data);
        }
        
        //更新发货单打印状态
        if(!isset($data['stock_status'])) $data['stock_status'] = 'true';
        if(!isset($data['deliv_status'])) $data['deliv_status'] = 'true';
        if(!isset($data['expre_status'])) $data['expre_status'] = 'true';

        $dlyObj->update(array('print_status'=>1,'stock_status'=>$data['stock_status'],'deliv_status'=>$data['deliv_status'],'expre_status'=>$data['expre_status'],'status'=>'progress'),array('delivery_id'=>$deliveryInfo['delivery_id']));

        //更新订单打印状态
        $dlyObj->updateOrderPrintFinish($deliveryInfo['delivery_id']);

        //请求前端发货单进行更新
        foreach (kernel::servicelist('service.delivery') as $object => $instance) {
            if (method_exists($instance, 'update_status')) {
                $instance->update_status($deliveryInfo['delivery_id'], 'progress');
            }
        }

        return $this->send_succ();
    }

    /**
     *
     * 更新接收发货单已校验
     * @param array $data
     */
    public function setCheck($data){
        if(!isset($data['delivery_bn']) || empty($data['delivery_bn'])){
            return $this->send_error('发货单通知单编号参数没有定义', $msg_code, $data);
        }else{
            $delivery_bn = $data['delivery_bn'];
        }

        $dlyObj  = &app::get('ome')->model('delivery');
        $dlyItemObj = &app::get('ome')->model('delivery_items');
        $deliveryInfo = $dlyObj->dump(array('delivery_bn'=>$data['delivery_bn']),'*');

        //检查该物流单号是否存在
        if(!isset($deliveryInfo['delivery_id'])){
            return $this->send_error('发货单通知单编号不存在', $msg_code, $data);
        }

        //判断是否已经校验过、单据是否取消或暂停

        if ($dlyItemObj->verifyItemsByDeliveryId($deliveryInfo['delivery_id'])){
            $delivery['delivery_id'] = $deliveryInfo['delivery_id'];
            $delivery['verify'] = 'true';

            if (!$dlyObj->save($delivery)){
                return false;
            }

            if($deliveryInfo['is_bind'] == 'true'){
                $ids = $dlyObj->getItemsByParentId($delivery['delivery_id'], 'array');
                foreach ($ids as $i){
                    $dlyItemObj->verifyItemsByDeliveryId($i);
                }
            }
            #淘宝全链路 已捡货，已验货
            $dlyObj->sendMessageProduce($delivery['delivery_id'], array(8, 9));
        }
        return $this->send_succ();
    }

    /**
     *
     * 更新接收发货单信息变更
     * @param array $data
     */
    public function updateDetail($data){
        if(!isset($data['delivery_bn']) || empty($data['delivery_bn'])){
            return $this->send_error('发货单通知单编号参数没有定义', $msg_code, $data);
        }else{
            $delivery_bn = $data['delivery_bn'];
        }

        $dlyObj  = &app::get('ome')->model('delivery');
        $dlyItemObj = &app::get('ome')->model('delivery_items');
        $deliveryInfo = $dlyObj->dump(array('delivery_bn'=>$data['delivery_bn']),'*');

        //检查该物流单号是否存在
        if(!isset($deliveryInfo['delivery_id'])){
            return $this->send_error('发货单通知单编号不存在', $msg_code, $data);
        }

        //判断单据是否取消或暂停

        //保存发货单变更信息
        $dlyObj->updateDelivery($data, array('delivery_id' => $deliveryInfo['delivery_id']));

        //根据动作类型记录相关日志
        if($data['action']){
            $opObj = app::get('ome')->model('operation_log');
            switch ($data['action']){
                case 'updateDetail':
                    $opObj->write_log('delivery_modify@ome', $deliveryInfo['delivery_id'], '修改发货单详情');
                    break;
                case 'addLogiNo':
                    $opObj->write_log('delivery_logi_no@ome', $deliveryInfo['delivery_id'], '录入快递单号:'.$data['logi_no']);
                    break;
            }
        }

        return $this->send_succ();
    }

    /**
     *
     * 撤销发货单
     * @param array $data
     */
    public function rebackDly($data){
        if(!isset($data['delivery_bn']) || empty($data['delivery_bn'])){
            return $this->send_error('发货单通知单编号参数没有定义', $msg_code, $data);
        }else{
            $delivery_bn = $data['delivery_bn'];
        }

        $dlyObj  = &app::get('ome')->model('delivery');
        $opObj = &app::get('ome')->model('operation_log');
        $dlyOrdObj = &app::get('ome')->model('delivery_order');
        $delivery_itemsObj = &app::get('ome')->model('delivery_items');
        $branch_productObj = &app::get('ome')->model('branch_product');
        $orderObj = &app::get('ome')->model('orders');
        $combineObj = new omeauto_auto_combine();
        $dispatchObj = app::get('omeauto')->model('autodispatch');

        $dlyInfo = $dlyObj->dump(array('delivery_bn'=>$delivery_bn),'*');
        if(!$dlyInfo){
            return $this->send_error('找不到该发货单单号', $msg_code, $data);
        }

        //检查发货单状态
        if($dlyInfo['status'] == 'back'){
            return $this->send_error('该发货单已经被打回，无法继续操作', $msg_code, $data);
        }
        if($dlyInfo['delivery_logi_number'] > 0){
            return $this->send_error('该发货单已部分发货，无法继续操作', $msg_code, $data);
        }
        if($dlyInfo['pause'] == 'true'){
            return $this->send_error('该发货单已暂停，无法继续操作', $msg_code, $data);
        }
        if($dlyInfo['process'] == 'true'){
            return $this->send_error('该发货单已经发货，无法继续操作', $msg_code, $data);
        }

        //检查订单状态


        $tmp['memo']        = $data['memo'];
        $tmp['status']      = 'back';
        $tmp['logi_no'] = null;
        $tmp['delivery_id'] = $dlyInfo['delivery_id'];
        $dlyObj->save($tmp);
        //增加branch_product释放冻结库存
        $branch_id = $dlyObj->getList('branch_id',array('delivery_id'=>$dlyInfo['delivery_id']),0,-1);
        $product_ids = $delivery_itemsObj->getList('product_id,number',array('delivery_id'=>$dlyInfo['delivery_id']),0,-1);
        foreach($product_ids as $key=>$v){
            $branch_productObj->unfreez($branch_id['0']['branch_id'],$v['product_id'],$v['number']);
        }
        $serialFilter['delivery_id'][] = $dlyInfo['delivery_id'];
        $this->rebackSerial($serialFilter);
        $delivery_bn = $dlyObj->dump(array('delivery_id'=>$dlyInfo['delivery_id']),'delivery_bn,logi_no');
        $logi_info = $delivery_bn['logi_no'] ? ',物流单号'.$delivery_bn['logi_no'] : '';
        
        $opObj->write_log('delivery_back@ome', $dlyInfo['delivery_id'], '发货单打回'.$logi_info);
        //释放主单库存
        if($dlyInfo['is_bind'] == 'true'){
            $ids = $dlyObj->getItemsByParentId($dlyInfo['delivery_id'],'array');
            foreach ($ids as $i){
               $tmpdly = array(
                    'delivery_id' => $i,
                    'status' => 'cancel',
                    'logi_id' => '',
                    'logi_name' => '',
                    'logi_no' => NULL,
                );
                $dlyObj->save($tmpdly);
                $opObj->write_log('delivery_back@ome', $i,'发货单打回');
                $dlyObj->updateOrderPrintFinish($i, 1);
               
            }

        }
        //单个发货单的对应订单号
        $order_ids = $dlyObj->getOrderIdByDeliveryId($dlyInfo['delivery_id']);
        foreach ($order_ids as $order_id ) {
            $orderInfo = $orderObj->dump($order_id, 'order_id,order_bn,order_combine_idx,order_combine_hash');
            $params[] = array(
                'idx' => $orderInfo['idx'],
                'hash' => $orderInfo['hash'],
                'orders' => array (
                    0 => $orderInfo['order_id'],
                ),
            );
            //开始处理订单分派
            $result = $combineObj->dispatch($params);
            if($result['did'] && $result['did']>0){
                $opData = $dispatchObj->dump($result['did'],'group_id,op_id');
            }else{
                $dispatchData = $dispatchObj->getList('group_id,op_id',array('defaulted'=>'true'));
                if($dispatchData && is_array($dispatchData[0])){
                    $opData = $dispatchData[0];
                }else{
                    $opData = array('group_id'=>'0','op_id'=>'0');
                }
            }
            //修改订单确认状态
            $opData['confirm'] = 'N';
            $opData['process_status'] = 'unconfirmed';
            $opData['pause'] = 'true';

            #[拆单]判断有部分拆分的有效发货单存在(确认状态为splitting) ExBOY
            if ($dlyObj->validDeiveryByOrderId($order_id))
            {
                $opData['process_status']   = 'splitting';
                $opData['pause']            = 'false';//因部分拆分后订单"基本信息"Tab没有操作按扭
                unset($opData['group_id'], $opData['op_id']);//部分拆分不重新分派
            }

            $orderObj->update($opData,array('order_id'=>$order_id));
            $opObj->write_log('order_back@ome', $order_id, '发货单'.$dlyInfo['delivery_bn'].$logi_info.'打回+'.'备注:'.$tmp['memo'].',订单重新分派');
        }
            
        $dlyObj->updateOrderPrintFinish($dlyInfo['delivery_id'], 1);
            
        
        return $this->send_succ();
    }

    private function rebackSerial($filter){
        $serialObj = app::get('ome')->model('product_serial');
        $serialLogObj = app::get('ome')->model('product_serial_log');
        if($filter['delivery_id'] && count($filter['delivery_id'])>0){
            $logFilter['act_type'] = 0;
            $logFilter['bill_type'] = 0;
            $logFilter['bill_no'] = $filter['delivery_id'];
            $serialLogs = $serialLogObj->getList('item_id',$logFilter);
            foreach($serialLogs as $key=>$val){
                $itemIds[] = $val['item_id'];
            }
            if(count($itemIds)>0 && $serialObj->update(array('status'=>0),array('item_id'=>$itemIds,'status'=>1))){
                return true;
            }
        }
        return false;
    }

}