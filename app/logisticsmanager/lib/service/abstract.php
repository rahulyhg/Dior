<?php
/**
 * 电子面单服务信息
 * @author liaoyu
 *
 */
abstract class logisticsmanager_service_abstract {

    protected $currentLogiNo = '';
    protected $currentDeliveryBn = '';
    public static $delivery = array();
    public static $deliveryOrder = array();
    public static $channel = array();
    public static $shop = array();
    public static $orders = array();
    public static $orderItems = array();
    public static $deliveryItems = array();
    public static $goods = array();
    public static $dlyCorp = array();

    public static $deliveryModel = null;
    public static $dlyBillModel = null;
    public static $channelModel = null;
    public static $waybillModel = null;
    public static $shopModel = null;
    public static $deliveryOrderModel = null;
    public static $ordersModel = null;
    public static $orderItemsModel = null;
    public static $waybillLogModel = null;
    public static $deliveryItemsModel = null;
    public static $goodsModel = null;
    public static $dlyCorpModel = null;
    public static $waybillExtendModel = null;

    public $isChildBill = false;
    public $childBill_id = '';

    public function setCurrChildBill($cid){
        $this->isChildBill = true;
        $this->childBill_id = $cid;
    }

    /**
     * 是否存在运单号
     * @param Array $params 参数
     */
    public function isExistlogino($params) {
        $channel = $this->getChannel($params['channel_id']);
        $delivery = $this->getDelivery($params['delivery_id']);
        $status = false;
        if ($delivery['logi_no'] && $this->isChildBill == false) {
            //检查运单是否存在
            $status = $this->checkWaybillNumber($params['channel_id'], $delivery['logi_no']);
            if ($status) {
                $this->currentLogiNo = $delivery['logi_no'];
                $this->currentDeliveryBn = $delivery['delivery_bn'];
            }
        }elseif($this->isChildBill == true && isset($this->childBill_id) && ($bill_logi_no = $delivery['child_bills'][$this->childBill_id])){
            //检查运单是否存在
            $status = $this->checkWaybillNumber($params['channel_id'], $bill_logi_no);
            if ($status) {
                $this->currentLogiNo = $bill_logi_no;
                $this->currentDeliveryBn = $delivery['delivery_bn'];
            }
        }
        return $status;
    }

    /**
     * 设置发货单信息
     * @param Int $delivery_id 发货单ID
     */
    public function setDelivery($delivery_id) {
        if (self::$deliveryModel === null) {
            self::$deliveryModel = app::get('wms')->model('delivery');
        }

        if (self::$dlyBillModel === null) {
            self::$dlyBillModel = app::get('wms')->model('delivery_bill');
        }

        $result = self::$deliveryModel->getList('*', array('delivery_id' => $delivery_id));
        if ($result) {
            self::$delivery[$delivery_id] = $result[0];
            //取主运单号
            $tmp_main_info = self::$dlyBillModel->getList('*', array('delivery_id' => $delivery_id, 'type'=> 1));
            if($tmp_main_info){
                self::$delivery[$delivery_id]['logi_no'] = $tmp_main_info[0]['logi_no'];
            }
        }

        if($this->isChildBill == true){
            $result_bill = self::$dlyBillModel->getList('*', array('delivery_id' => $delivery_id, 'type'=> 2));
            if ($result_bill) {
                foreach($result_bill as $k =>$bill)
                self::$delivery[$delivery_id]['child_bills'][$bill['b_id']] = $bill['logi_no'];
            }
        }
    }

    /**
     * 获取发货信息
     * @param Int $delivery_id 发货单ID
     */
    public function getDelivery($delivery_id = '') {
        if (!self::$delivery[$delivery_id]) {
            $this->setDelivery($delivery_id);
        }
        return self::$delivery[$delivery_id];
    }

    /**
     * 设置电子面单信息
     * @param Array $channelInfo 电子面单信息
     */
    public function setChannel($channel_id) {
        if (self::$channelModel === null) {
            self::$channelModel = app::get("logisticsmanager")->model("channel");
        }
        $result = self::$channelModel->dump($channel_id);
        if ($result) {
            self::$channel[$channel_id] = $result;
        }
    }

    /**
     * 获取电子面单信息
     * @param Int $channel_id 电子面单号
     */
    public function getChannel($channel_id) {
        if (!self::$channel[$channel_id]) {
            $this->setChannel($channel_id);
        }
        return self::$channel[$channel_id];
    }

    /**
     * 检查运单号是否存在
     * @param Int $channel_id 电子面单ID
     * @param String $waybill_number 运单号
     */
    public function checkWaybillNumber($channel_id, $waybill_number) {
        if (self::$waybillModel === null) {
            self::$waybillModel = app::get('logisticsmanager')->model('waybill');
        }
        $filter = array('channel_id' => $channel_id, 'waybill_number' => $waybill_number);
        $count = self::$waybillModel->count($filter);
        $status = $count > 0 ? true : false;
        return $status;
    }

    /**
     * 获取运单信息
     * @param Int null 电子面单来源ID
     * @param String $waybill_number 面单号
     */
    public function getWaybill($channel_id, $waybill_number) {
        if (self::$waybillModel === null) {
            self::$waybillModel = app::get('logisticsmanager')->model('waybill');
        }
        $filter = array('channel_id' => $channel_id, 'waybill_number' => $waybill_number);
        $result = self::$waybillModel->dump($filter);
        return $result;
    }
    /**
     * 检查物流单是否使用
     * @param Int $channel_id 电子面单ID
     */
    public function checkWaybillNumberIsUse($channel_id) {
        if (self::$waybillModel === null) {
            self::$waybillModel = app::get('logisticsmanager')->model('waybill');
        }
        $filter = array('channel_id' => $channel_id, 'status' => 0);
        $count = self::$waybillModel->count($filter);
        $status = $count > 0 ? true : false;
        return $status;
    }

    /**
     * 获取缓存池中物流单
     * @param Int $channel_id 电子面单ID
     */
    public function getBufferPoolWayBillNumber($channel_id) {
        $status = $this->checkWaybillNumberIsUse($channel_id);
        $waybillNumber = '';
        if ($status) {
            $filter = array('channel_id' => $channel_id, 'status' => 0);
            $result = self::$waybillModel->getList('*', $filter);
            if ($result) {
                $waybillNumber = $result[0]['waybill_number'];
            } 
        }
        return $waybillNumber;
    }

    /**
     * 设置店铺信息
     * @param String $shop_id 店铺ID
     */
    public function setShop($shop_id) {
        if (self::$shopModel === null) {
            self::$shopModel = app::get('ome')->model('shop');
        }
        $result = self::$shopModel->dump(array('shop_id' => $shop_id));
        if ($result) {
            self::$shop[$shop_id] = $result;
        }
    }
    /**
     * 获得店铺信息
     * @param String $shop_id 店铺ID
     */
    public function getShop($shop_id) {
        if (!self::$shop[$shop_id]) {
            $this->setShop($shop_id);
        }
        return self::$shop[$shop_id];
    }


    /**
     * 设置订单
     * @param Int $order_id 订单ID
     */
    public function setOrders($order_id) {
        if (self::$ordersModel === null) {
            self::$ordersModel = app::get('ome')->model('orders');
        }
        $result = self::$ordersModel->dump(array('order_id' => $order_id));
        if ($result) {
            self::$orders[$order_id] = $result;
        }
    }
    /**
     * 获取订单
     * @param Int $order_id 订单ID
     */
    public function getOrders($order_id) {
        if (!self::$orders[$order_id]) {
            $this->setOrders($order_id);
        }
        return self::$orders[$order_id];
    }

    /**
     * 设置发货订单
     * @param Int $delivery_id 发货单ID
     */
    public function setDeliveryOrder($delivery_id) {
        //根据wms发货id查找ome发货id
        $wms_dly_id = app::get('wms')->model('delivery')->getOuterIdById($delivery_id);

        if (self::$deliveryOrderModel === null) {
            self::$deliveryOrderModel = app::get('ome')->model('delivery_order');
        }
        $result = self::$deliveryOrderModel->getList('order_id', array('delivery_id' => $wms_dly_id));
        $data = array();
        foreach ($result as $k => $v) {
            $data['order_id'][] = $v['order_id'];
            $order = $this->getOrders($v['order_id']);
            $data['order_info'][$v['order_id']] = $order;
        }
        if ($data) {
            self::$deliveryOrder[$delivery_id] = $data;
        }
    }
    /**
     * 获取发货订单
     * @param Int $delivery_id 发货单ID
     */
    public function getDeliveryOrder($delivery_id) {
        if (!self::$deliveryOrder[$delivery_id]) {
            $this->setDeliveryOrder($delivery_id);
        }
        return self::$deliveryOrder[$delivery_id];
    }

    /**
     * 设置订单明细
     * @param Int $order_id 订单ID
     */
    public function setOrderItems($order_id) {
        if (self::$orderItemsModel === null) {
            self::$orderItemsModel = app::get('ome')->model('order_items');
        }
        $result = self::$orderItemsModel->getList('*', array('order_id' => $order_id));
        if ($result) {
            self::$orderItems[$order_id] = $result;
        }
    }

    /**
     * 获取订单明细
     * @param Int $order_id 订单ID
     */
    public function getOrderItems($order_id) {
        if (!self::$orderItems[$order_id]) {
            $this->setOrderItems($order_id);
        }
        return self::$orderItems[$order_id];
    }

    /**
     * 获取编号
     */
    public function getGenId() {
        if (self::$waybillLogModel === null) {
            self::$waybillLogModel = app::get('logisticsmanager')->model('waybill_log');
        }
        return self::$waybillLogModel->gen_id();
    }

    /**
     * 插入物流单获取日志
     * @param Array $data
     */
    public function insertWaybillLog($data) {
        if (self::$waybillLogModel === null) {
            self::$waybillLogModel = app::get('logisticsmanager')->model('waybill_log');
        }
        return self::$waybillLogModel->insert($data);
    }

    /**
     * 更新物流单日志
     * @param Array $data 更新数据
     * @param Array $filter 过滤器
     */
    public function updateWaybillLog($updata, $filter) {
        if (self::$waybillLogModel === null) {
            self::$waybillLogModel = app::get('logisticsmanager')->model('waybill_log');
        }
        return self::$waybillLogModel->update($updata, $filter);
    }

    /**
     * 更新发货单物流单
     * @param Int $delivery_id 发货单
     * @param String $waybill_code 物流单
     */
    public function updateDeliveryLogino($delivery_id, $waybill_code) {
        $wmsDLyModel = app::get('wms')->model('delivery');
        $dlyBillObj         = app::get('wms')->model('delivery_bill');
        $omeDlyModel = app::get('ome')->model('delivery');
        //更新主物流单号
        $result = $dlyBillObj->update(array('logi_no'=>$waybill_code), array('delivery_id'=>$delivery_id,'type'=>1));

        //获取ome的发货单号和仓库id
        $wms_dly_info =$wmsDLyModel->dump(array('delivery_id'=>$delivery_id),'outer_delivery_bn,branch_id');

        //电子面单获取后顺便请求ome模块更新物流单号
        $wms_id = kernel::single('ome_branch')->getWmsIdById($wms_dly_info['branch_id']);
        $tmp_data = array(
            'delivery_bn' => $wms_dly_info['outer_delivery_bn'],
            'logi_no' => $waybill_code,
            'action' => 'addLogiNo',
        );
        $res = kernel::single('wms_event_trigger_delivery')->doUpdate($wms_id, $tmp_data, true);

        return $result;
    }

    /**
     * 更新发货单子单物流单
     * @param Int $delivery_id 发货单
     * @param Int $b_id 发货子单
     * @param String $waybill_code 物流单
     */
    public function updateDlyBillLogino($delivery_id, $b_id, $waybill_code) {
        if (self::$dlyBillModel === null) {
            self::$dlyBillModel = app::get('wms')->model('delivery_bill');
        }
        return self::$dlyBillModel->update(array('logi_no' => $waybill_code), array('delivery_id' => $delivery_id, 'b_id'=>$b_id, 'type'=>2));
    }

    /**
     * 获取订单列表
     * @param Int $delivery_id 发货单ID
     */
    public function getTradeOrderList($delivery_id)
    {
        /*
        $deliveryOrder = $this->getDeliveryOrder($delivery_id);
        $trade_order_list = array();
        foreach ($deliveryOrder['order_info'] as $order_id => $orders) {
            $trade_order_list[] = $orders['order_bn'];
        }
        */
        
        /*------------------------------------------------------ */
        //-- [拆单]订单拆分多个发货单时_回写发货单号  ExBOY
        /*------------------------------------------------------ */
        $trade_order_list   = array();
        $delivery           = $this->getDelivery($delivery_id);
        $trade_order_list[] = $delivery['delivery_bn'];
        
        return $trade_order_list;
    }

    /**
     * 检查订单编号是否存在
     * @param String $order_bn 订单号
     */
    public function checkOrderBnIsExist($order_bn) {
        if (self::$ordersModel === null) {
            self::$orderItemsModel = app::get('ome')->model('orders');
        }
        $result = self::$ordersModel->dump(array('order_bn' => $order_bn));
        $status = false;
        if ($result) {
            $status = true;
        }
        return $status;
    }

    /**
     * 是否直辖市
     * @param String $province 省
     */
    public function isMunicipality($province) {
        $municipality = array('北京市', '上海市', '天津市', '重庆市');
        $status = false;
        foreach ($municipality as $zxs) {
            if (substr($zxs, 0, strlen($province)) == $province) {
                $status = true;
                break;
            }
        }
        return $status;
    }

    /**
     * 设置发货单明细
     * @param Int $delivery_id 发货单ID
     */
    public function setDeliveryItems($delivery_id) {
        if (self::$deliveryItemsModel === null) {
            self::$deliveryItemsModel = app::get('wms')->model('delivery_items');
        }
        $result = self::$deliveryItemsModel->getList('*', array('delivery_id' => $delivery_id),0,2);
        if ($result) {
            self::$deliveryItems[$delivery_id] = $result;
        }
    }

    /**
     * 获取发货单明细
     * @param Int $delivery_id 发货单ID
     */
    public function getDeliveryItems($delivery_id) {
        if (!self::$deliveryItems[$delivery_id]) {
            $this->setDeliveryItems($delivery_id);
        }
        return self::$deliveryItems[$delivery_id];
    }

    public function setGoods($bn) {
        if (self::$goodsModel === null) {
            self::$goodsModel = app::get('ome')->model('goods');
        }
        $result = self::$goodsModel->getList('*', array('bn' => $bn));
        if ($result) {
            self::$goods[$bn] = $result[0];
        }
    }

    /**
     * 设置商品信息
     * @param String $bn 商品货号
     */
    public function getGoods($bn) {
        if (!self::$goods[$bn]) {
            $this->setGoods($bn);
        }
        return self::$goods[$bn];
    }

    /**
     * 格式化卖家省市区
     * @param String $area 区域
     */
    public function formatSenderArea($area) {
        $first = strpos($area, ':');
        $last = strrpos($area, ':');
        $pca = substr($area, $first + 1, $last - $first - 1);
        list($province, $city, $district) = explode('/', $pca);
        $data = array(
            'province' => $province,
            'city' => $city,
            'district' => $district
        );
        return $data;
    }

    /**
     * 设置物流公司
     * @param Int $corp_id 物流公司ID
     */
    public function setDlyCorp($corp_id) {
        if (self::$dlyCorpModel === null) {
            self::$dlyCorpModel = app::get('ome')->model('dly_corp');
        }
        $result = self::$dlyCorpModel->getList('*', array('corp_id' => $corp_id));
        if ($result) {
            self::$dlyCorp[$corp_id] = $result[0];
        }
    }

    /**
     * 获取物流公司信息
     * @param Int $corp_id 物流公司ID
     */
    public function getDlyCorp($corp_id) {
        if (!self::$dlyCorp[$corp_id]) {
            $this->setDlyCorp($corp_id);
        }
        return self::$dlyCorp[$corp_id];
    }

    /**
     * 获取面单扩展信息
     * @param Int $waybill_id 面单序号
     */
    public function getWaybillExentInfo($waybill_id) {
        if (self::$waybillExtendModel === null) {
            self::$waybillExtendModel = app::get('logisticsmanager')->model('waybill_extend');
        }
        $filter = array('waybill_id' => $waybill_id);;
        return self::$waybillExtendModel->dump($filter);
    }

    /**
     * 获取电子面单扩展
     */
    public function getWaybillExtend($params) {
        if(empty($params['logi_no'])){
            $delivery = $this->getDelivery($params['delivery_id']);
            if (empty($delivery['logi_no'])) {
                return array();
            }
        }else{
            $delivery['logi_no'] = $params['logi_no'];
        }

        $waybill = $this->getWaybill($params['channel_id'], $delivery['logi_no']);
        $waybillExtend = array();
        if ($waybill) {
            $waybillExtend = $this->getWaybillExentInfo($waybill['id']);
        }
        return $waybillExtend;
    }

    /**
    * 设置子单的请求的订单号
    */
    public function setChildRqOrdNo($order_bn){
        if(is_array($order_bn)){
            foreach((array)$order_bn as $k => $val){
                $order_bn[$k] = $val."cldordno".$this->childBill_id;
            }
        }else{
            $order_bn = $order_bn."cldordno".$this->childBill_id;
        }
        return $order_bn;
    }

    /**
    * 检查是否是子单的请求的订单号
    */
    public function checkChildRqOrdNo($order_bn, &$main_order_no, &$waybill_cid){
        $pos = strpos($order_bn,'cldordno');
        if( $pos !== false){
            $main_order_no = substr($order_bn,0,$pos);
            $waybill_cid = substr($order_bn,$pos+8);
            $this->setCurrChildBill($waybill_cid);
            return true;
        }else{
            return false;
        }
    }

    
    /**
     * 面单来源扩展
     * @param 
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function getChannelExtend($channel_id)
    {
        $extendObj = app::get('logisticsmanager')->model('channel_extend');
        $extend = $extendObj->dump(array('channel_id'=>$channel_id),'province,city,area,address_detail,seller_id,default_sender,mobile,tel');
        $shop_address = array(
            'province' => $extend['province'],
            'city' => $extend['city'],
            'area' => $extend['area'],
            'address_detail' => $extend['address_detail'],
            'seller_id' => $extend['seller_id'],
            'default_sender'=>$extend['default_sender'],
            'mobile'=>$extend['mobile'],
            'tel'=>$extend['tel'],
        );
        return $shop_address;
    }
}