<?php

/**
 * 订单组结构
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */
class omeauto_auto_group_item {
    /**
     * 检查通过
     */
    const __OPT_ALLOW = 0;
    /**
     * 需提示或其它
     */
    const __OPT_ALERT = 1;
    /**
     * 无法合并
     */
    const __OPT_HOLD = 2;

    /**
     * 发货单分组
     */
    static $_orderGroups = null;
    /**
     * 短信分组
     */
    static $_smsGroups = null;
    /**
     * 订单数据
     * @var Array
     */
    private $orders = array();
    /**
     * 要修正的订单状态
     * @var Array
     */
    private $orderStatus = array();
    /**
     * 检查结果
     * @var Array
     */
    private $status = array('opt' => 0, 'log' => array());

    /**
     * 析构
     *
     * @param Array $orders 订单组数据
     * @return void
     */
    function __construct($orders) {

        $this->orders = $orders;
        $this->orderNums = count($orders);
    }

    /**
     * 获取订单内容
     *
     * @param void
     * @return Array
     */
    public function & getOrders() {

        return $this->orders;
    }

    /**
     * 获取送货地址
     *
     * @param void
     * @return String
     */
    public function getShipArea() {

        foreach ($this->orders as $key => $order) {

            return $order['ship_area'];
            break;
        }
    }

    /**
     * 获取订单条数
     *
     * @param void
     * @return Integer
     */
    public function getOrderNum() {

        if (empty($this->orders)) {

            return 0;
        } else {

            return count($this->orders);
        }
    }

    /**
     * 获取指定字段的值的健的分布
     *
     * @param String $field 字段名称
     * @return array
     */
    public function getGroupByField($field) {

        $result = array();
        foreach ($this->orders as $order) {

            $result[$order[$field]][] = $order['order_id'];
        }

        return $result;
    }

    /**
     * 设置物流公司
     *
     * @param Array $corp 物流公司信息
     * @return void
     */
    public function setDlyCorp($corp) {

        $this->status['change']['dlyCorp'] = $corp;
    }
     /**
     * 得到物流公司
     *
     * @param Array $corp 物流公司信息
     * @return void
     */
    public function getDlyCorp() {

        return $this->status['change']['dlyCorp'];
    }

    /**
     * 设置仓库
     *
     * @param Integer $branchId
     * @return void
     */
    public function setBranchId($branchId) {

        $this->status['change']['branchId'] = $branchId;
    }

    /**
     * 获取已经设定的仓库编号
     *
     * @param void
     * @return Integer
     */
    public function getBranchId() {

        return $this->status['change']['branchId'];
    }

    /**
     * 设置指定订单的提示状态
     *
     * @param Integer $oId 订单ID
     * @param Integer $status 要设置的订单提示状态
     * @return void
     */
    public function setOrderStatus($oId, $status) {

        if ($oId == '*') {

            foreach ($this->orders as $oid => $order) {

                $this->setOrderStatus($oid, $status);
            }
        } else {
            if ($this->orders[$oId]['pay_status'] == 0) {

                return;
            }

            if (isset($this->orderStatus[$oId])) {

                $this->orderStatus[$oId] = $this->orderStatus[$oId] | $status;
            } else {

                $this->orderStatus[$oId] = $status;
            }
        }
    }

    /**
     * 设置指定插件的检查结果
     *
     * @param Integer $optStatus 检查结果
     * @param String $plugFix 插件名
     * @return void
     */
    public function setStatus($optStatus, $plugFix) {

        //$optStatus = intval($optStatus);
        $this->status['opt'] = $this->status['opt'] > $optStatus ? $this->status['opt'] : $optStatus;
        $this->status['log'][] = array('plug' => $plugFix, 'result' => $optStatus);
    }

    /**
     * 检查订单组内容是否有效
     *
     * @param Array $orders 订单组
     * @return Boolean
     */
    public function vaild($config) {

        $autoDelivery = $config['autoConfirm'];

        if (($this->status['opt'] == self::__OPT_ALLOW || empty($this->status['opt'])) && ($autoDelivery == '1')) {
            //增加对是否已有发货单进行判断，有就不生成，需手工操作
            foreach ($this->orders as $orderId => $items) {

                //检查是否已有发货单
                $hasDeriveryIds = app::get(omeauto_auto_combine::__ORDER_APP)->model('delivery_order')->getlist('delivery_id', array('order_id' => $items['order_id']));

                if ($hasDeriveryIds) {
                    $dIds = array();
                    foreach ($hasDeriveryIds as $item) {
                        $dIds[] = $item['delivery_id'];
                    }
                    $DeriveryIds = app::get(omeauto_auto_combine::__ORDER_APP)->model('delivery')->getList('delivery_id', array('delivery_id' => $dIds, 'disabled' => 'false', 'status|notin' => array('cancel', 'back','return_back')));
                    if (is_array($DeriveryIds) && $DeriveryIds) {

                        return false;
                    }
                }
            }
            return true;
        } else {

            return false;
        }
    }

    /**
     * 检查是否有效的缓存订单组内容
     *
     * @param Array $orders 订单组s
     * @return Boolean
     */
    public function vaildBufferGroup($bufferTime) {

        $payStatus = $this->getGroupByField('pay_status');
        $codStatus = $this->getGroupByField('is_cod');

        //多是未支付，且不为货到付款,返回 false
        if (count($payStatus) == 1 && (isset($payStatus[0]) || isset($payStatus[2]) || isset($payStatus[3])) && !isset($codStatus['true'])) {

            return false;
        }

        //检查时间，正确应为 cod 为 createtime, 非 cod 为 支付时间，目前没有支付时间,暂用 createtime 判断
        if (!isset($codStatus['true'])) {
            //款到发货，需有一个已付款订单的支付时间已大于缓冲时间
            foreach ($payStatus as $pay => $ordes) {
                if ($pay == 1 || $pay == 4 || $pay == 5) {
                    foreach ($ordes as $orderId) {
                        if ($this->orders[$orderId]['paytime'] < $bufferTime) {
                            return true;
                        }
                    }
                }
            }
        } else {
            //货到付款，需有一个订单创建时间已经过缓冲时间
            foreach ($this->orders as $order) {
                if ($order['createtime'] < $bufferTime) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 对当前结果进行处理
     *
     * @param void
     * @return boolean
     */
    public function process($config) {
        $curOpInfo = kernel::single('ome_func')->getDesktopUser();

        if ($this->vaild($config)) {

            $systemUser = omeauto_auto_dispatch::getSystemUser();
            $deliveryInfo = $this->fetchDeliveryFormat();

            $ids = array();
            $deliveryObj = app::get('ome')->model("delivery");
            $orderObj = app::get('ome')->model("orders");
            $oOperation_log = &app::get('ome')->model('operation_log');
            //此处要增加判断
            $sendObj = app::get('console')->model('delivery_send');
           
            foreach ($deliveryInfo as $orderId => $deliveryInfo) {
                if($orderId && $orderId>0){
                    $order_items = $deliveryInfo['order_items'];
                    unset($deliveryInfo['order_items']);
                    $ids[] = $deliveryObj->addDelivery($orderId, $deliveryInfo,array(),$order_items);
                    //更新订单信息
                    $sdf = array('order_id' => $orderId,
                        'process_status' => 'splited',
                        'confirm' => 'Y',
                        'dispatch_time' => time(),
                        'op_id' => $systemUser['op_id'],
                        'group_id' => $systemUser['group_id']);
                    $orderObj->save($sdf);

                    $opInfo = kernel::single('ome_func')->get_system();
                    $logMsg = "订单确认,操作员:".$curOpInfo['op_name'];
                    $oOperation_log->write_log('order_confirm@ome',$orderId,$logMsg,NULL,$opInfo);
                    unset($logMsg);
                }
            }
            //如果物流公司是当当物流不可以合并发货单

            $dly_corp = app::get(omeauto_auto_combine::__ORDER_APP)->model('dly_corp')->dump( array('corp_id' => $this->status['change']['dlyCorp']['corp_id']),'type');
            $combine_select = app::get('ome')->getConf('ome.combine.select');
            
            $_isCombine = true;
            if ($dly_corp['type'] =='DANGDANG' || $dly_corp['type'] =='AMAZON' || $combine_select == '1'){
                
                $_isCombine = false;
            }
            //合并发货单
            if (!empty($ids) && count($ids) > 1 && $_isCombine) {
                //自动审单普通发货单触发通知wms创建发货单
                $newdly_id = $deliveryObj->merge($ids, array('logi_id' => $this->status['change']['dlyCorp']['corp_id'],'logi_name' => $this->status['change']['dlyCorp']['name']));                //自动审单合并发货单触发通知wms创建发货单
                $oOperation_log->write_log('delivery_modify@ome',$newdly_id,"发货单开始发送第三方",NULL,$opInfo);
                $original_data = kernel::single('ome_event_data_delivery')->generate($newdly_id);
                $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
                $sendObj->update_send_status($newdly_id,'sending');
                $result = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, false);
                $result = json_encode($result);
                $oOperation_log->write_log('delivery_modify@ome',$newdly_id,"发货单开始发送第三方结果".$result,NULL,$opInfo);
            }else{
                //自动审单普通发货单触发通知wms创建发货单
                foreach($ids as $newdly_id) {
                    $oOperation_log->write_log('delivery_modify@ome',$newdly_id,"发货单开始发送第三方",NULL,$opInfo);
                    $original_data = kernel::single('ome_event_data_delivery')->generate($newdly_id);
                    $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
                    $sendObj->update_send_status($newdly_id,'sending');
                    $result = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, false);
                    $result = json_encode($result);
                    $oOperation_log->write_log('delivery_modify@ome',$newdly_id,"发货单发送第三方结果:".$result,NULL,$opInfo);
                }
            }
            return true;
        } else {
            $dispacthUser = omeauto_auto_dispatch::getAutoDispatchUser($this);

            $orderObj = app::get('ome')->model("orders");
            $oOperation_log = &app::get('ome')->model('operation_log');
            foreach ($this->orders as $order) {
                if ($order['pay_status'] == '1' || $order['pay_status'] == '4' || $order['pay_status'] == '5' || $order['is_cod'] == 'true') {
                    $sdf = array('order_id' => $order['order_id']);
                    if (isset($this->orderStatus[$order['order_id']])) {
                        $sdf['op_id'] = $dispacthUser['op_id'];
                        $sdf['group_id'] = $dispacthUser['group_id'];
                        $sdf['confirm'] = 'N';
                        $sdf['process_status'] = 'unconfirmed';
                        $sdf['dispatch_time'] = time();
                        $sdf['auto_status'] = $this->orderStatus[$order['order_id']];
                    } else {
                        $sdf['op_id'] = $dispacthUser['op_id'];
                        $sdf['group_id'] = $dispacthUser['group_id'];
                        $sdf['dispatch_time'] = time();
                        $sdf['confirm'] = 'Y';
                        $sdf['process_status'] = 'confirmed';
                    }
                    if($sdf['order_id'] && $sdf['order_id']>0){
                        $orderObj->save($sdf);
                        $opInfo = kernel::single('ome_func')->get_system();
                        $usersObj = app::get('desktop')->model('users');
                        $groupsObj = app::get('ome')->model('groups');
                        $confirm_opname = $usersObj->dump($dispacthUser['op_id'],'name');
                        $confirm_opgroup = $groupsObj->dump($dispacthUser['group_id'],'name');
                        $logMsg = "操作员:".$curOpInfo['op_name']."获取订单，订单自动分派给确认组:".$confirm_opgroup['name'].",确认人:".($confirm_opname?$confirm_opname['name']:'-');
                        $oOperation_log->write_log('order_dispatch@ome',$sdf['order_id'],$logMsg,NULL,$opInfo);
                        unset($logMsg);
                    }
                }
            }
            return false;
        }
    }

    /**
     * 获取发货单数据格式
     *
     * @param void
     * @return Array
     */
    private function fetchDeliveryFormat($consignee = null) {

        $result = array();
        $delivery_group = $this->getDeliveryGroup();
        $sms_gorup = $this->getSendSmsGroup();
        foreach ($this->orders as $order) {

            $delivery = array('branch_id' => $this->getBranchId(),
                'logi_id' => $this->status['change']['dlyCorp']['corp_id'],
                'delivery_group' => $delivery_group,
                'sms_group' => $sms_gorup,
                'consignee' => ($consignee ? $consignee : $this->getConsignee($order)),
                'delivery_items' => array());
            foreach ($order['items'] as $item) {

                if ($item['delete'] == 'false') {
                    $delivery['delivery_items'][] = array(
                        'item_type' => $item['item_type'],
                        'product_id' => $item['product_id'],
                        'shop_product_id' => $item['shop_product_id'],
                        'bn' => $item['bn'],
                        'number' => $item['nums'],
                        'product_name' => $item['name'],
                        'spec_info' => $item['addon'],
                        //'combine_hash' => md5($item['shop_product_id'] . $item['name'] . $item['addon']),
                        //'combine_hash' => md5($item['name'] . $item['addon']),
                    );

                    $delivery['order_items'][] = array(
                        'item_id' => $item['item_id'],
                        'product_id' => $item['product_id'],
                        'number' => $item['nums'],
                        'bn' => $item['bn'],
                        'product_name' => $item['name'],
                    );
                }
            }
            $result[$order['order_id']] = $delivery;
        }

        return $result;
    }

    /**
     * 获取发货单分组
     */
    public function getDeliveryGroup(){

        $this->initFilters();
        foreach ((array)self::$_orderGroups as $tId => $filter) {
            if ($filter->vaild($this)) {
                return $tId;
            }
        }
        return '';
    }

    /**
     * 检查涉及仓库选择的订单分组对像是否已经存在
     *
     * @param void
     * @return void
     */
    private function initFilters() {

        if (self::$_orderGroups === null) {

            $filters = kernel::single('omeauto_auto_type')->getDeliveryGroupTypes();
            self::$_orderGroups = array();
            if ($filters) {

                foreach ($filters as $config) {

                    $filter = new omeauto_auto_group();
                    $filter->setConfig($config);
                    self::$_orderGroups[$config['tid']] = $filter;
                }
            }
        }
    }
    /**
     * 获取短信发送分组
     *
     * @param  void
     * @return void
     * @author
     **/
    public function getSendSmsGroup()
    {
        $this->initSmsFilters();
        foreach ((array)self::$_smsGroups as $tId => $filter) {
            if ($filter->vaild($this)) {
                return $tId;
            }
        }
        return '';
    }
    /**
     * 检查涉短信发送分组
     *
     * @param void
     * @return void
     */
    private function initSmsFilters() {

        if (self::$_smsGroups === null) {

            $filters = kernel::single('omeauto_auto_type')->getAutoSendSmsTypes();
            self::$_smsGroups = array();
            if ($filters) {

                foreach ($filters as $config) {

                    $filter = new omeauto_auto_group();
                    $filter->setConfig($config);
                    self::$_smsGroups[$config['tid']] = $filter;
                }
            }
        }
    }
    /**
     * 获取发货地址信息
     *
     * @param Array $order 订单数据
     * @return Array
     */
    private function getConsignee($orders) {

        return array(
            'name' => $orders['ship_name'],
            'r_time' => $orders['ship_time'],
            'mobile' => $orders['ship_mobile'],
            'zip' => $orders['ship_zip'],
            'area' => $orders['ship_area'],
            'telephone' => $orders['ship_tel'],
            'email' => $orders['ship_email'],
            'addr' => $orders['ship_addr']
        );
    }

    /**
     * 检查是否能生成发货单
     *
     * @param Void
     * @return Boolean
     */
    public function canMkDelivery() {

        foreach ($this->orders as $order) {

            #ExBOY 增加 splitting、ship_status in('0', '2')
            if($order['status'] != 'active' || $order['pause'] != 'false' || $order['abnormal'] != 'false' || !in_array($order['process_status'], array('unconfirmed','confirmed','splitting')) || !in_array($order['ship_status'], array('0', '2'))){
                return false;
            }

            if ($order['is_cod'] == 'false' && !in_array($order['pay_status'], array('1', '4', '5'))) {
                return false;
            }

            //检查是否已有发货单
            //$hasDeriveryIds = app::get(omeauto_auto_combine::__ORDER_APP)->model('delivery_order')->getlist('delivery_id', array('order_id' => $order['order_id']));
            //if (count($hasDeriveryIds) > 0) {
            //    $dIds = array();
            //    foreach ($hasDeriveryIds as $item) {
            //        $dIds[] = $item['delivery_id'];
            //    }
            //    $DeriveryIds = app::get(omeauto_auto_combine::__ORDER_APP)->model('delivery')->getList('delivery_id', array('delivery_id' => $dIds, 'disabled' => 'false', 'status|notin' => array('cancel', 'back')));
            //    if (count($DeriveryIds) > 0) {
            //        return false;
            //    }
            //}
            
            #[拆单]检查是否还可生成发货单  chenping
            $oOrder = app::get('ome')->model('orders');
            
            $canSplit = false;
            $item_list = $oOrder->getItemBranchStore($order['order_id']);
            foreach ((array) $item_list as $il) {
                foreach ((array) $il as $var) {
                    foreach ((array) $var['order_items'] as $v) {
                        if ($v['left_nums'] > 0) {
                            $canSplit = true;
                        }
                    }
                }
            }
            
            if($canSplit == false) return false;
        }
        return true;
    }

    /**
     * 生成发货单位
     *
     * @param Array $consignee 收件人信息
     * @return boolean
     */
    public function mkDelivery($consignee,$sync=false) {

        if (isset($consignee['memo'])) {
            $remark = $consignee['memo'];
            unset($consignee['memo']);
        } else {

             $remark = '';
        }
        $deliveryInfos = $this->fetchDeliveryFormat($consignee);

        $ids = array();
        $deliveryObj = app::get('ome')->model("delivery");
        $orderObj = app::get('ome')->model("orders");
        $sendObj = app::get('console')->model('delivery_send');
        //此处要增加判断
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $user_id = $opInfo['op_id'];
        $oOperation_log = &app::get('ome')->model('operation_log');
        foreach ($deliveryInfos as $orderId => $deliveryInfo) {
            if($orderId && $orderId>0){
                $deliveryInfo['memo'] = $remark;

                $order_items = $deliveryInfo['order_items'];
                unset($deliveryInfo['order_items']);
                
                #[拆单]新增$split_status部分拆分 ExBOY
                $delivery_id = $deliveryObj->addDelivery($orderId, $deliveryInfo,array(),$order_items, $split_status,$sync);
                
                if ($delivery_id) {
                    $ids[] = $delivery_id;
                    
                    //更新订单信息
                    $sdf = array(
                        'order_id'       => $orderId,
                        'process_status' => $split_status,//ExBOY addDelivery()中引用值
                        'confirm'        => 'Y',
                        'dispatch_time'  => time(),
                        'refund_status'  => 0,
                        'op_id'          => $user_id,
                    );
                    $orderObj->save($sdf);
                    
                    //订单部分确认加入发货单号 ExBOY
                    $log_msg    = '订单部分确认';
                    if($split_status == 'splited')
                    {
                        $log_msg    = '订单确认';
                    }
                    $get_dly_bn    = $deliveryObj->getList('delivery_id, delivery_bn', array('delivery_id' => $delivery_id), 0, 1);
                    $get_dly_bn    = $get_dly_bn[0];
                    $log_msg       .= '（发货单号：<a href="index.php?app=ome&ctl=admin_receipts_print&act=show_delivery_items&id='.$delivery_id.'" target="_blank">'
                                   .$get_dly_bn['delivery_bn'].'</a>）';

                    $oOperation_log->write_log('order_confirm@ome',$orderId, $log_msg, NULL, $opInfo);
                }
            }
        }

        //如果物流公司是当当物流不可以合并发货单
        $dly_corp = app::get(omeauto_auto_combine::__ORDER_APP)->model('dly_corp')->dump( array('corp_id' => $this->status['change']['dlyCorp']['corp_id']),'type');
        $combine_select = app::get('ome')->getConf('ome.combine.select');
        $_isCombine = true;
        if ( $dly_corp['type']=='DANGDANG' || $dly_corp['type']=='AMAZON' || $combine_select == '1'){
            $_isCombine = false;
        }

        //合并发货单
        if (!empty($ids) && count($ids) > 1 && $_isCombine) {
            $newdly_id = $deliveryObj->merge($ids, array('logi_id' => $this->status['change']['dlyCorp']['corp_id'],'logi_name' => $this->status['change']['dlyCorp']['name'], 'memo' => $remark));
            $oOperation_log->write_log('delivery_modify@ome',$newdly_id,"发货单开始发送第三方",NULL,$opInfo);
            //人工审单合并发货单触发通知wms创建发货单
            $original_data = kernel::single('ome_event_data_delivery')->generate($newdly_id);
            $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
            $sendObj->update_send_status($newdly_id,'sending');
            $result = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, false);
            $result = json_encode($result);
            $oOperation_log->write_log('delivery_modify@ome',$newdly_id,"发货单开始发送第三方结果".$result,NULL,$opInfo);
            return true;
        }else{
            //人工审单合并发货单触发通知wms创建发货单
            foreach ($ids as $newdly_id) {
                $oOperation_log->write_log('delivery_modify@ome',$newdly_id,"发货单开始发送第三方",NULL,$opInfo);
                $original_data = kernel::single('ome_event_data_delivery')->generate($newdly_id);
                $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
                $sendObj->update_send_status($newdly_id,'sending');
                $result = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, false);
                $result = json_encode($result);
                $oOperation_log->write_log('delivery_modify@ome',$newdly_id,"发货单发送第三方结果:".$result,NULL,$opInfo);
            }
            return true;
        }
        
    }

    /**
     * 获取订单重量
     *
     * @param Array $order 订单信息
     * @return
     */
     function getWeight(){

         $weight = 0;
         foreach ($this->orders as $key => $order) {
             $order_weight = app::get('ome')->model('orders')->getOrderWeight($order['order_id']);
             if ($order_weight==0){
                 $weight=0;
                 break;
             }else{
                $weight+= $order_weight;
             }

        }
        return $weight;
     }

     /**
     * 获取店铺类型
     *
     * @param void
     * @return String
     */
    public function getShopType() {

        foreach ($this->orders as $key => $order) {

            return $order['shop_type'];
            break;
        }
    }

}