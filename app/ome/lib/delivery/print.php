<?php
/**
 * 发货单打印调用
 *
 * @author kami
 * @package ome_delivery_print
 * @copyright www.shopex.cn 2014.08.05
 *
 */
class ome_delivery_print{

    const __CURR_APP = 'ome';

    const __PRINT_SHIP ='ship';

    private $__deliverys = array();

    private $__dly_ids = array();

    private $__valid_dly_ids = array();

    private $__filter = array();

    private $__mode_list = array('single','multi');

    private $_mode ='';

    private $__type_list = array('stock','delivery','merge','ship');

    private $_type ='';

    private $_sort = true;

    private $__printDlyObj = '';

    private $__msg = array();

    function __construct()
    {
        $this->__printDlyObj = app::get(self::__CURR_APP)->model('delivery');
    }

    public function getPrintDatas($filter, $type='stock', $mode='', $sort=true,&$msg){
        //设置发货单过滤条件
        $this->_setFilter($filter);

        //设置当前打印类型
        if(!in_array($type,$this->__type_list)){
            $msg = $this->__msg['error_msg'] = '非法的打印类型';
        }else{
            $this->__type = $type;
        }

        //设置当前打印模式
        if(in_array($mode,$this->__mode_list) || empty($mode)){
            $this->_mode = $mode;
        }else{
            $msg = $this->__msg['error_msg'] = '非法的打印模式';
        }

        //是否需要打印排序、批次号
        $this->_sort = $sort;

        //没有选中发货单货打印模式非法直接退出提醒
        if(isset($this->__msg['error_msg']) && $this->__msg['error_msg'] ){
            $msg = $this->__msg;
            return false;
        }

        //检查是否同一个仓库的单子以及快递单打印的时候是否是同一个物流公司
        if(!$this->_checkProcessIds()){
            $msg = $this->__msg;
            return false;
        }

        //补打快递单不用排序和打印批次
        if($this->_sort){
            //打印排序
            $this->__dlyIds = $this->__printDlyObj->printOrderByByIds($this->__dlyIds);

            //打印批次处理
            if(!$this->_getPrintQueue()){
                $msg = $this->__msg;
                return false;
            }
        }

        //初始化发货单及相关订单的数据
        $this->_initData();

        //检查发货单相关的订单状态
        if(!$this->_parsePrintIds()){
            $msg = $this->__msg;
            return false;
        }

        //根据不同单据扩展必要的使用内容信息
        $this->_extendData();

        //标记有效发货单的打印模式
        $this->_updateDeliCfg();

        return $this->__deliverys;
    }

    private function _setFilter($filter){
        if(isset($filter['filter']) && count($filter['filter']) > 0){
            $this->__filter = $filter['filter'];
        }else{
            $this->__msg['error_msg'] = '没有发货单号被选中打印';
        }
    }

    private function _checkProcessIds(){
        $branch_num = array();
        $logi_num = array();

        $dly_arr = $this->__printDlyObj->getList('*', $this->__filter, 0, -1);
        foreach($dly_arr as $k =>$dly){
            if($this->__type == self::__PRINT_SHIP){
                $logi_num[$dly['logi_id']]++;
            }
            $branch_num[$dly['branch_id']] = $dly['delivery_id'];
            $this->__dlyIds[] = $dly['delivery_id'];
            $this->__deliverys['deliverys'][$dly['delivery_id']] = $dly;
        }

        if (count($logi_num) > 1){
            $this->__msg['error_msg'] ="当前系统不支持同时打印二种不同快递类型的单据，请重新选择后再试。";
            return false;
        }

        if (count($branch_num) > 1){
            $this->__msg['error_msg'] ="当前系统不支持同时打印二个仓库的单据，请重新选择后再试。";
            return false;
        }

        return true;
    }

    //获取打印批次
    private function _getPrintQueue() {
        if (!$result = $this->_checkPrintQueue()) {
            return false;
        }

        $queueObj = kernel::single('ome_queue');
        $this->__deliverys['identInfo'] = $queueObj->fetchPrintQueue($this->__dlyIds);
        foreach($this->__deliverys['identInfo']['items'] as $k => $ident){
            $this->__deliverys['identInfo']['ids'][$k] = str_replace($this->__deliverys['identInfo']['idents'][0].'_','',$ident);
        }

        return true;
    }

    //检查是否能同批次打印
    private function _checkPrintQueue() {
        //修正这里的排序方法内部排序，不然会影响整体的打印正常排序
        if (!empty($this->__dlyIds)){
            $ids = $this->__dlyIds;
            sort($ids);
        }

        //检查批量打印数量限制
        $deliCfgLib = kernel::single('ome_delivery_cfg');
        $batch_print_nums = $deliCfgLib->getValue('ome_batch_print_nums',$this->_mode);
        if (count($ids) > $batch_print_nums) {
            $this->__msg['warn_msg'] = "所选发货单号数量已超过批量打印数量 (" . $batch_print_nums . ")！";
            return false;
        }

        $delivery_check_ident = &app::get('ome')->getConf('ome.delivery.check_ident');
        $delivery_check_ident = $delivery_check_ident ? $delivery_check_ident : 'on';
        $queueObj = kernel::single('ome_queue');
        if ($queueObj->isExistsQueueItems($ids, $existsQueueItems)) {
            if (count($ids) != count($existsQueueItems)) {
                $this->__msg['warn_msg'] = "已生成批次号的发货单不能和未生成的发货单一起打印！";
            } else {
                $error = array();
                foreach ($existsQueueItems as $k => $v) {
                    if (!in_array($v, $error)) {
                        $error[] = $v;
                    }
                }
                $this->__msg['warn_msg'] = "发货单号已存在有不相同的批次号：<br/>" . join('<br/>',$error);
            }
            if ($delivery_check_ident == 'on') {
                return false;
            } else {
                $this->__deliverys['existsIdents'] = str_replace($this->__msg['warn_msg'],'<br/>','&nbsp;&nbsp;');
            }
        }

        return true;
    }

    private function _initData(){
        //发货主单sdfpath字段转移
        foreach($this->__deliverys['deliverys'] as $k => $dly){
            $this->__deliverys['deliverys'][$k]['consignee']['name'] = $dly['ship_name'];
            $this->__deliverys['deliverys'][$k]['consignee']['area'] = $dly['ship_area'];
            $this->__deliverys['deliverys'][$k]['consignee']['province'] = $dly['ship_province'];
            $this->__deliverys['deliverys'][$k]['consignee']['city'] = $dly['ship_city'];
            $this->__deliverys['deliverys'][$k]['consignee']['district'] = $dly['ship_district'];
            $this->__deliverys['deliverys'][$k]['consignee']['addr'] = $dly['ship_addr'];
            $this->__deliverys['deliverys'][$k]['consignee']['zip'] = $dly['ship_zip'];
            $this->__deliverys['deliverys'][$k]['consignee']['telephone'] = $dly['ship_tel'];
            $this->__deliverys['deliverys'][$k]['consignee']['mobile'] = $dly['ship_mobile'];
            $this->__deliverys['deliverys'][$k]['consignee']['email'] = $dly['ship_email'];
            $this->__deliverys['deliverys'][$k]['consignee']['r_time'] = $dly['ship_time'];
        }

        //获取发货单明细
        $dlyItemObj = app::get(self::__CURR_APP)->model('delivery_items');
        $dlyItem_arr = $dlyItemObj->getList('*',array('delivery_id' => $this->__dlyIds), 0, -1);
        foreach($dlyItem_arr as $k =>$dlyItem){
            if(isset($this->__deliverys['deliverys'][$dlyItem['delivery_id']])){
                $this->__deliverys['deliverys'][$dlyItem['delivery_id']]['delivery_items'][$dlyItem['item_id']] = $dlyItem;
            }
        }

        //获取发货单子单号
        $dlyChild_arr = $this->__printDlyObj->getList('*', array('parent_id'=> $this->__dlyIds), 0, -1);
        if(count($dlyChild_arr) > 0){
            //$tmp_dlyChildIds = array();
            foreach($dlyChild_arr as $k =>$dlyChild){
                //$tmp_dlyChildIds[] = $dlyChild['delivery_id'];
                if(isset($this->__deliverys['deliverys'][$dlyChild['parent_id']])){
                    $this->__deliverys['deliverys'][$dlyChild['parent_id']]['deliveryChildIds'][] = $dlyChild['delivery_id'];
                    $this->__deliverys['deliverys'][$dlyChild['parent_id']]['deliveryChildItems'][$dlyChild['delivery_id']] = $dlyChild;
                }
            }
        }

        //获取发货单关联订单信息
        $dlyOrderObj = app::get(self::__CURR_APP)->model('delivery_order');
        $dlyOrder_arr = $dlyOrderObj->getList('*',array('delivery_id' => $this->__dlyIds), 0, -1);
        $tmp_orderIds = array();
        foreach($dlyOrder_arr as $k =>$dlyOrder){
            $tmp_orderIds[] = $dlyOrder['order_id'];
            if(isset($this->__deliverys['deliverys'][$dlyOrder['delivery_id']])){
                $this->__deliverys['deliverys'][$dlyOrder['delivery_id']]['delivery_order'][$dlyOrder['order_id']] = array('order_id'=>$dlyOrder['order_id'],'delivery_id'=>$dlyOrder['delivery_id']);
            }
        }

        //根据订单号获取订单主表信息
        $orderObj = app::get(self::__CURR_APP)->model('orders');
        $orders = array();
        $order_arr = $orderObj->getList('*',array('order_id' => $tmp_orderIds), 0, -1);
        foreach($order_arr as $k =>$order){
            $tmp_order_shipping = $order['shipping'];
            $tmp_cost_payment = $order['cost_payment'];
            unset($order['cost_payment'],$order['shipping']);
            $order['shipping']['shipping_name'] = $tmp_order_shipping;
            $order['shipping']['cost_shipping'] = $order['cost_freight'];
            $order['shipping']['is_protect'] = $order['is_protect'];
            $order['shipping']['cost_protect'] = $order['cost_protect'];
            $order['shipping']['is_cod'] = $order['is_cod'];
            $order['payinfo']['cost_payment'] = $tmp_cost_payment;
            $order['tax_title'] = $order['tax_company'];
            
            $orders[$order['order_id']] = $order;
        }
        $ids = array_keys($orders);

        //获取订单明细表信息
        $items = app::get(self::__CURR_APP)->model('order_items')->getList('*', array('order_id' => $ids));
        foreach ($items as $item) {
            $item['addon'] = ome_order_func::format_order_items_addon($item['addon']);
            $orders[$item['order_id']]['order_items'][$item['item_id']] = $item;
        }

        //获取订单对象表信息
        $objects = app::get(self::__CURR_APP)->model('order_objects')->getList('*', array('order_id' => $ids));
        foreach ($objects as $object) {
            $orders[$object['order_id']]['order_objects'][$object['obj_id']] = $object;
        }

        //合并明细信息到对象数组信息中
        foreach($orders as $ok => $order){
            foreach($order['order_items'] as $oik => $orditem){
                if(isset($orders[$ok]['order_objects'][$orditem['obj_id']])){
                    $orders[$ok]['order_objects'][$orditem['obj_id']]['order_items'][$oik] = $orditem;
                }
            }
            unset($orders[$ok]['order_items']);
        }

        //合并订单相关信息到具体的发货单中
        foreach($dlyOrder_arr as $k =>$dlyOrder){
            if(isset($this->__deliverys['deliverys'][$dlyOrder['delivery_id']]) && isset($orders[$dlyOrder['order_id']])){
                $this->__deliverys['deliverys'][$dlyOrder['delivery_id']]['orders'][$dlyOrder['order_id']] = $orders[$dlyOrder['order_id']];
            }
        }

        //获取发货单订单关联结构表
        $dlyItemsDetailObj = app::get(self::__CURR_APP)->model('delivery_items_detail');
        $dlyItemsDetail_arr = $dlyItemsDetailObj->getList('*',array('delivery_id' => $this->__dlyIds), 0, -1);
        foreach($dlyItemsDetail_arr as $didk => $dlyItemsDetail){
            if(isset($this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']])){
                $this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']]['item_type'] = $dlyItemsDetail['item_type'];
                $this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']]['order_obj_id'] = $dlyItemsDetail['order_obj_id'];
                $this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']]['order_item_id'] = $dlyItemsDetail['order_item_id'];
                $this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']]['price'] = $dlyItemsDetail['price'];
                $this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']]['amount'] = $dlyItemsDetail['amount'];
                $this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']]['shop_type'] = isset($orders[$dlyItemsDetail['order_id']]['shop_type']) ? $orders[$dlyItemsDetail['order_id']]['shop_type'] : '';
                $this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']]['order_source'] = isset($orders[$dlyItemsDetail['order_id']]['order_source']) ? $orders[$dlyItemsDetail['order_id']]['order_source'] : '';
                $this->__deliverys['deliverys'][$dlyItemsDetail['delivery_id']]['delivery_items'][$dlyItemsDetail['delivery_item_id']]['shop_id'] = isset($orders[$dlyItemsDetail['order_id']]['shop_id']) ? $orders[$dlyItemsDetail['order_id']]['shop_id'] : '';
            }
        }

        foreach((array)$this->__dlyIds as $k => $dly_id){
            $tmp_dlys['deliverys'][$dly_id] = $this->__deliverys['deliverys'][$dly_id];
        }
        unset($this->__deliverys['deliverys']);
        $this->__deliverys['deliverys'] = $tmp_dlys['deliverys'];
        unset($tmp_dlys['deliverys']);

        unset($dlyItem_arr,$dlyOrder_arr,$tmp_orderIds,$orders,$order_arr,$items,$objects,$dlyItemsDetail_arr);
        //echo "<pre>";print_r($this->__deliverys);exit;
    }

    private function _extendData(){
        $class_name = sprintf('ome_delivery_print_%s',$this->__type);
        try{
            if(class_exists($class_name)){
                $now_printLib = kernel::single($class_name);
                if(is_object($now_printLib) && method_exists($now_printLib,'appendExtData')){
                    $now_printLib->appendExtData($this->__deliverys['deliverys']);
                }
            }
        }catch (Exception $e) {
        }
    }

    private function _parsePrintIds() {
        $result = array(
            'ids' => array(), //可用于打印的ID
            'errIds' => array(), //不能用于打印的数据
            'errInfo' => array(), //所有错误信息
            'errBns' => array(), //错误发货单id的发货单号
        );

        foreach ($this->__deliverys['deliverys'] as $dk => $dly) {
            $hasError = false;
            
            //如果是合并发货单，检查子发货单状态
            if($dly['is_bind'] == 'true'){
                foreach($dly['deliveryChildItems'] as $dck =>$dlyChild){
                    if(!$this->_checkDlyStatus($dlyChild,$errMsg)){
                        $result['errIds'][] = $dly['delivery_id'];
                        $result['errBns'][$dly['delivery_id']] = $dly['delivery_bn'];
                        $result['errInfo'][$dly['delivery_id']] = $errMsg;
                        $hasError = true;
                        break;
                    }
                }
            }else{
                if(!$this->_checkDlyStatus($dly,$errMsg)){
                    $result['errIds'][] = $dly['delivery_id'];
                    $result['errBns'][$dly['delivery_id']] = $dly['delivery_bn'];
                    $result['errInfo'][$dly['delivery_id']] = $errMsg;
                    $hasError = true;
                }
            }

            //检查发货单相关的订单状态
            if (!$hasError) {
                foreach($dly['orders'] as $ok => $order){
                    //检查当前订单的状态是不是可以打印
                    if (!$this->_checkOrdStatus($order, $errMsg)) {
                        //状态有问题的订单是肯定不要打印的
                        $result['errIds'][] = $dly['delivery_id'];
                        $result['errBns'][$dly['delivery_id']] = $dly['delivery_bn'];
                        $result['errInfo'][$dly['delivery_id']] = $errMsg;
                        $hasError = true;
                        break;
                    }
                }
            }

            //库存有问题的单据认为是要打印的
            if (!$hasError) {
                $result['ids'][] = $dly['delivery_id'];
                $this->__valid_dly_ids[] = $dly['delivery_id'];
            }else{
                unset($this->__deliverys['deliverys'][$dly['delivery_id']]);
            }

            if (!$hasError) {
                //检查库存(除原样寄回发货单)
                if ($dly['type'] == 'normal') {
                    foreach ($dly['deliveryItems'] as $item) {
                        $re = $this->__printDlyObj->existStockIsPlus($item['product_id'], $item['number'], $item['item_id'], $dly['branch_id'], $err, $item['bn']);
                        if (!$re) {
                            $result['errIds'][] = $dly['delivery_id'];
                            $result['errBns'][$dly['delivery_id']] = $dly['delivery_bn'];
                            $result['errInfo'][$dly['delivery_id']] .= $err . "&nbsp;,&nbsp;";
                            $hasError = true;
                        }
                    }
                }
            }
        }

        if (empty($result['ids'])) {
            if (!empty($result['errIds'])) {
                $this->__msg['warn_msg'] = sprintf("你所选择的 %d 张单据状态异常，无法打印，本次操作中止！", count($result['errIds']));
            } else {
                $this->__msg['warn_msg'] = '你没有选择要打印的单据，请重新选择后再试！';
            }
            return false;
        }

        $this->__deliverys = array_merge($this->__deliverys,$result);
        return true;
    }

    private function _checkDlyStatus($dly, &$errMsg){
        if(in_array($dly['status'],array('cancel','back','timeout','failed','return_back')) || $dly['pause'] == 'true' || $dly['disabled'] == 'true'){
            $errMsg = "发货单已无法操作，请到订单处理中心处理";
            return false;
        }
        return true;
    }

    private function _checkOrdStatus($order, &$errMsg){
        if(in_array($order['pay_status'],array('5','6','7')) || $order['pause'] == 'true' || $order['disabled'] == 'true' || $order['process_status'] == 'cancel' || $order['abnormal'] == 'true'){
            $errMsg = "发货单相关订单存在异常，请到订单处理中心处理";
            return false;
        }
        return true;
    }

    private function _updateDeliCfg() {
        $filter = array(
            'delivery_id' => $this->__valid_dly_ids,
            'stock_status' => 'false',
            'deliv_status' => 'false',
            'expre_status' => 'false',
        );
        $data = array(
            'deli_cfg' => $this->_mode,
        );

        $this->__printDlyObj->update($data,$filter);
    }
}