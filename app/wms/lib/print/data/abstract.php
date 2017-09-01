<?php

abstract class wms_print_data_abstract {
    protected $currentDeliveryId = ''; 
    //批次号 
    public $identsItems = array();
    public static $delivery = array();
    public static $shop = array();
    public static $memebers = array();
    public static $deliveryOrder = array();
    public static $orders = array();
    public static $orderItems = array();
    public static $orderObject = array();
    public static $deliveryModel = null;
    public static $shopModel = null;
    public static $membersModel = null;
    public static $deliveryOrderModel = null;
    public static $ordersModel = null;
    public static $orderItemsModel = null;
    public static $orderObjectModel = null;

    /**
     * 获得发货单数据
     * @param Int $delivery_id 发货单ID
     */
    public function getDelivery($delivery_id) {
        if (!self::$delivery[$delivery_id]) {
            $this->setDelivery($delivery_id);
        }
        return self::$delivery[$delivery_id];
    }

    /**
     * 设置发货单
     * @param Int $delivery_id 发货单ID
     */
    public function setDelivery($delivery_id) {
        if (self::$deliveryModel === null) {
            self::$deliveryModel = app::get('ome')->model('delivery');
        }
        $result = self::$deliveryModel->getList('*', array('delivery_id' => $delivery_id));
        if ($result) {
            $this->currentDeliveryId = $delivery_id;
            self::$delivery[$delivery_id] = $result[0];
        }
    }

    /**
     * 设置店铺信息
     * @param String $shop_id
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
     * 获取店铺信息
     * @param String $shop_id
     */
    public function getShop($shop_id) {
        if (!self::$shop[$shop_id]) {
            $this->setShop($shop_id);
        }
        return self::$shop[$shop_id];
    }

    /**
     * 设置会员信息
     * @param Int $member_id 会员ID
     */
    public function setMembers($member_id) {
        if (self::$membersModel === null) {
            self::$membersModel = app::get('ome')->model('members');
        }
        $result = self::$membersModel->dump(array('member_id' => $member_id));
        if ($result) {
            self::$memebers[$member_id] = $result;
        }
    }

    /**
     * 获取会员信息
     * @param Int $member_id 会员ID
     */
    public function getMembers($member_id) {
        if (!self::$memebers[$member_id]) {
            $this->setMembers($member_id);
        }
        return self::$memebers[$member_id];
    }

    /**
     * 设置发货对应的订单
     * @param Int $delivery_id 发货单
     */
    public function setDeliveryOrder($delivery_id) {
        if (self::$deliveryOrderModel === null) {
            self::$deliveryOrderModel = app::get('ome')->model('delivery_order');
        }
        $result = self::$deliveryOrderModel->getList('*', array('delivery_id' => $delivery_id));
        if ($result) {
            self::$deliveryOrder[$delivery_id] = $result;
        }
    }

    /**
     * 获得发货订单
     * @param Int $delivery_id 发货单
     */
    public function getDeliveryOrder($delivery_id) {
        if (!self::$deliveryOrder[$delivery_id]) {
            $this->setDeliveryOrder($delivery_id);
        }
        return self::$deliveryOrder[$delivery_id];
    }


    /**
     * 获取订单信息
     * @param Int $delivery_id 发货单ID
     */
    public function getOrderByDeliveryId($delivery_id) {
        $orderIds = $this->getDeliveryOrder($delivery_id);
        if (empty($orderIds)) {
            return array();
        }
        $orderArr = array();
        foreach ($orderIds as $v) {
            $orderArr[] = $v['order_id'];
        }
        return $orderArr;
    }

    /**
     * 获取订单信息
     * @param Int $delivery_id 发货单ID
     */
    public function getOrderInfoByDeliverId($delivery_id) {
        $orderIdArr = $this->getOrderByDeliveryId($delivery_id);
        if (empty($orderIdArr)) {
            return array();
        }
        $orders = array();
        foreach ($orderIdArr as $order_id) {
            $order = $this->getOrders($order_id);
            if ($order) {
                $orders[] = $order;
            }
        }
        return $orders;
    }

    /**
     * 设置订单信息
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
     * 获取订单信息
     * @param Int $order_id 订单ID
     */
    public function getOrders($order_id) {
        if (!self::$orders[$order_id]) {
            $this->setOrders($order_id);
        }
        return self::$orders[$order_id];
    }

    /**
     * 设置订单明细
     * @param Int $obj_id 对象ID号
     */
    public function setOrderItems($obj_id) {
        if (self::$orderItemsModel === null) {
            self::$orderItemsModel = app::get('ome')->model('order_items');
        }
        $result = self::$orderItemsModel->getList('*', array('obj_id' => $obj_id, 'delete' => 'false'));//过滤删除的订单商品  ExBOy
        if ($result) {
            self::$orderItems[$obj_id] = $result;
        }
    }

    /**
     * 获取订单明细
     * @param Int $obj_id 对象ID号
     */
    public function getOrderItems($obj_id) {
        if (!self::$orderItems[$obj_id]) {
            $this->setOrderItems($obj_id);
        }
        return self::$orderItems[$obj_id];
    }
    /**
     * 设置订单
     * Enter description here ...
     * @param unknown_type $order_id
     */
    public function setOrderObject($order_id) {
        if (self::$orderObjectModel === null) {
            self::$orderObjectModel = app::get('ome')->model('order_objects');
        }
        //$result = self::$orderObjectModel->getList('*', array('order_id' => $order_id));//未过滤删除的订单商品
        
        #过滤删除的订单商品 ExBOY
        $sql    = "select b.* from sdb_ome_order_items AS a LEFT JOIN sdb_ome_order_objects AS b ON a.obj_id=b.obj_id 
                    where a.order_id='".$order_id."' AND a.delete='false' GROUP BY a.obj_id";
        $result = kernel::database()->select($sql);
        
        if ($result) {
            self::$orderObject[$order_id] = $result;
        }
    }
    /**
     * 获取订单对象数据
     * Enter description here ...
     */
    public function getOrderObject($order_id) {
        if (!self::$orderObject[$order_id]) {
            $this->setOrderObject($order_id);
        }
        return self::$orderObject[$order_id];
    }
}