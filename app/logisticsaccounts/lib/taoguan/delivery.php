<?php
class logisticsaccounts_taoguan_delivery implements logisticsaccounts_interface_estimate{
    
    /**
    * 发货单对应订单号
    */
    static $orderidList = array();
    /**
    * 发货单相关信息
    */
    static $deliveryInfo = array();


    function get_total($now_time,$last_time){
        $sqlstr = '';
        if($last_time){
           $sqlstr.=' AND delivery_time>='.$last_time;
        }
        $sqlstr.=' AND delivery_time<='.$now_time;
        $db=kernel::database();
        $sql = 'SELECT delivery_id,delivery_time FROM `sdb_ome_delivery`  WHERE parent_id=0 AND process=\'true\' '.$sqlstr.' UNION ALL SELECT delivery_id,delivery_time FROM sdb_ome_delivery_bill WHERE `status` =\'1\''.$sqlstr.' ORDER BY delivery_time DESC';

        $delivery = $db->select($sql);
        return count($delivery);
    }

    function delivery_list($now_time,$last_time,$offset,$limit){
        
        $deliOrderObj = &app::get('ome')->model("delivery_order");
        $orderObj = &app::get('ome')->model("orders");
        $sqlstr = '';
        if($last_time){
           $sqlstr.=' AND delivery_time>='.$last_time;
        }
        $sqlstr.=' AND delivery_time<='.$now_time;
        $db=kernel::database();
        $sql = '(SELECT delivery_id,logi_no, weight, delivery_cost_expect ,delivery_cost_actual,delivery_time FROM `sdb_ome_delivery` as d WHERE parent_id=0 AND process=\'true\' '.$sqlstr.' )  UNION ALL (SELECT delivery_id,logi_no, weight, delivery_cost_expect ,delivery_cost_actual,delivery_time FROM sdb_ome_delivery_bill WHERE  `status` =\'1\''.$sqlstr.'  ) ORDER by delivery_time ASC LIMIT '.$offset.','.$limit;
        $rows = $db->select($sql);

        foreach($rows as $k=>$v){
            
            $delivery = $this->get_delivery($v['delivery_id']);
            $rows[$k]['branch_id'] = $delivery['branch_id'];
            $rows[$k]['shop_id'] = $delivery['shop_id'];
            $rows[$k]['logi_name'] = $delivery['logi_name'];
            $rows[$k]['cost_protect'] = $delivery['cost_protect'];
            $rows[$k]['ship_name'] = $delivery['ship_name'];
            $rows[$k]['ship_area'] = $delivery['ship_area'];
            $rows[$k]['delivery_bn'] = $delivery['delivery_bn'];
            $rows[$k]['logi_id'] = $delivery['logi_id'];
            $rows[$k]['ship_province'] = $delivery['ship_province'];
            $rows[$k]['ship_city'] = $delivery['ship_city'];
            $rows[$k]['ship_district'] = $delivery['ship_district'];
            $rows[$k]['ship_addr'] = $delivery['ship_addr'];
            
            $rows[$k]['order_bn'] = $this->get_order_list($v['delivery_id']);
            $rows[$k]['delivery_cost_expect'] = $v['delivery_cost_actual'];

        }

     return $rows;

 }

    /**
    * 仓库列表
    */
    function branch_list(){
        $branchObj = &app::get('ome')->model('branch');
        $branchList = $branchObj->getList('branch_id,name');
        return $branchList;

    }
    /**
    *获取仓库详情
    */
    function get_branch($branch_id){
        $branchObj = &app::get('ome')->model('branch');
        $branch = $branchObj->dump($branch_id,'branch_id,name');
        return $branch;
    }
    /**
    *店铺列表
    */
    function shop_list(){
        $shopObj = &app::get('ome')->model("shop");
        $shopList = $shopObj->getList('shop_id,name,shop_type');
        return $shopList;

    }
    /**
    *获取店铺详情
    */
    function get_shop($shop_id){
        $shopObj = &app::get('ome')->model("shop");
        $shop = $shopObj->dump(array('shop_id'=>$shop_id),'shop_id,name,shop_type');
        return $shop;
    }
     /**
     *物流公司列表
     */
     function logi_list(){

      $dly_corpObj = &app::get('ome')->model("dly_corp");
      $dly_corpList = $dly_corpObj->getList('type,name,corp_id');
      return $dly_corpList;

     }
    /**
    *获取物流公司详情
    */
    function get_logi($logi_id){
        $dly_corpObj = &app::get('ome')->model("dly_corp");
        $dly_corp = $dly_corpObj->dump(array('corp_id'=>$logi_id),'type,name,corp_id');
        return $dly_corp;
    }

     /**
    *获取物流公司详情
    */
    function get_loginame($logi_name){
        $db=kernel::database();
        $sql = "SELECT corp_id ,name FROM sdb_ome_dly_corp WHERE name='$logi_name'";
        $row = $db->selectrow($sql);
        return $row;
    }

    protected function get_order_list($delivery_id){
        if (!self::$orderidList[$delivery_id]) {
            $orderidList = self::$orderidList[$delivery_id];
        
            $db=kernel::database();
            $sql = 'SELECT O.order_bn FROM sdb_ome_delivery_order as DO LEFT JOIN sdb_ome_orders as O ON DO.order_id=O.order_id WHERE DO.delivery_id='.$delivery_id;
            $order = $db->select($sql);
            $order_bn=array();
            foreach($order as $k=>$v){
                $order_bn[]= $v['order_bn'];
            }
            if($order_bn){
               $order_bn = implode(',',$order_bn);
               self::$orderidList[$delivery_id] = $order_bn;
               
            }
        }
        return self::$orderidList[$delivery_id];

    }

    protected function get_delivery($delivery_id){
        if ( !self::$deliveryInfo[$delivery_id] ){
            $db=kernel::database();
            $sql = 'SELECT d.is_cod,d.branch_id,d.shop_id,d.logi_name,d.cost_protect,d.ship_name,d.ship_area,d.delivery_bn ,d.logi_id,d.ship_province,d.ship_city,d.ship_district,d.ship_addr FROM sdb_ome_delivery as d WHERE d.delivery_id='.$delivery_id;
            $delivery = $db->selectrow($sql);
            self::$deliveryInfo[$delivery_id] = $delivery;
        }
        return self::$deliveryInfo[$delivery_id];
    }

}

?>