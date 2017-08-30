<?php
class logisticsaccounts_ocs_delivery implements logisticsaccounts_interface_estimate{

    function get_total($now_time,$last_time){
        $sqlstr = '';
        if($last_time){
            $sqlstr.=' AND delivery_time>='.$last_time;
        }
        $sqlstr.=' AND delivery_time<='.$now_time;
        $db=kernel::database();

       $sql = 'SELECT count(d.delivery_id) as total FROM sdb_ome_delivery as d WHERE d.parent_id=0  and d.process=\'true\' '.$sqlstr.' ORDER BY d.delivery_time DESC';

        $delivery = $db->selectrow($sql);

        return $delivery['total'];
    }

    function delivery_list($now_time,$last_time,$offset,$limit){
        set_time_limit(0);
        $sqlstr = '';
        if($last_time){
            $sqlstr.=' AND delivery_time>='.$last_time;
        }
        $sqlstr.=' AND delivery_time<='.$now_time;
        $db=kernel::database();
        $sql = 'SELECT d.delivery_id,d.is_cod,d.branch_id,d.shop_id,d.delivery_time,d.logi_name,d.cost_protect,d.ship_name,d.weight,d.delivery_cost_expect,d.delivery_cost_actual,d.ship_area,d.logi_no,d.delivery_bn ,d.logi_id,d.ship_province,d.ship_city,d.ship_district,d.ship_addr FROM sdb_ome_delivery as d WHERE d.parent_id=0  and d.process=\'true\' '.$sqlstr.' ORDER BY d.delivery_time DESC LIMIT '.$offset.','.$limit;
        $rows = $db->select($sql);

        foreach($rows as $k=>$v){
            $rows[$k]['order_bn'] = $this->get_order_list($v['delivery_id']);
            $rows[$k]['delivery_cost_expect'] = $v['delivery_cost_expect'];
//            if($v['is_cod']=='false'){
//                //$rows[$k]['money_expect'] = 0;
//                $rows[$k]['delivery_cost_expect'] = $v['delivery_cost_actual']+$v['cost_protect'];
//
//            }else{
//                $sql = 'SELECT O.total_amount FROM sdb_ome_delivery_order as DO LEFT JOIN sdb_ome_orders as O ON DO.order_id=O.order_id WHERE DO.delivery_id='.$v['delivery_id'];
//                $row = $this->db->select($sql);
//                //$rows[$k]['money_expect'] = $row[0]['total_amount'];
//                $rows[$k]['delivery_cost_expect'] = ($row[0]['total_amount']-$v['delivery_cost_actual']-$v['cost_protect']);
//            }
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
        $db=kernel::database();
        $sql = 'SELECT O.order_bn FROM sdb_ome_delivery_order as DO LEFT JOIN sdb_ome_orders as O ON DO.order_id=O.order_id WHERE DO.delivery_id='.$delivery_id;
        $order = $db->select($sql);
        $order_bn=array();
        foreach($order as $k=>$v){
            $order_bn[]= $v['order_bn'];
        }
        if($order_bn){
           return implode(',',$order_bn);
        }

    }




}


?>