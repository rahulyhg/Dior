<?php

class console_mdl_delivery extends dbeav_model{
    var $defaultOrder = array('delivery_id',' ASC');
   public function table_name($real = false){
        if($real){
           $table_name = 'sdb_ome_delivery';
        }else{
           $table_name = 'delivery';
        }
        return $table_name;
	}

    public function get_schema(){
        return app::get('ome')->model('delivery')->get_schema();
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'delivery_bn'=>app::get('base')->_('发货单号'),
            'order_bn'=>app::get('base')->_('订单号'),
            'member_uname'=>app::get('base')->_('用户名'),
            'ship_name'=>app::get('base')->_('收货人'),
            'ship_tel_mobile'=>app::get('base')->_('联系电话'),
            'product_bn'=>app::get('base')->_('货号'),
            'product_barcode'=>app::get('base')->_('条形码'),
            'delivery_ident'=>app::get('base')->_('打印批次号'),
        );
        return array_merge($childOptions,$parentOptions);
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        if(isset($filter['extend_delivery_id'])){
            $where .= ' OR delivery_id IN ('.implode(',', $filter['extend_delivery_id']).')';
            unset($filter['extend_delivery_id']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = &app::get('ome')->model("members");
            $rows = $memberObj->getList('member_id',array('uname|has'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }
            $where .= '  AND member_id IN ('.implode(',', $memberId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['order_bn'])){
            $orderObj = &app::get('ome')->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }

            $deliOrderObj = &app::get('ome')->model("delivery_order");
            $rows = $deliOrderObj->getList('delivery_id',array('order_id'=>$orderId));
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }

            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['order_bn']);
        }
        if(isset($filter['product_bn'])){
            $itemsObj = &app::get('ome')->model("delivery_items");
            #$rows = $itemsObj->getDeliveryIdByPbn($filter['product_bn']);
            $rows = $itemsObj->getDeliveryIdByFilter($filter);
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['product_bn']);
        }
        if(isset($filter['product_barcode'])){
            $itemsObj = &app::get('ome')->model("delivery_items");
            $rows = $itemsObj->getDeliveryIdByPbarcode($filter['product_barcode']);
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['product_barcode']);
        }
        if(isset($filter['logi_no_ext'])){
            $logObj = &app::get('ome')->model("delivery_log");
            $rows = $logObj->getDeliveryIdByLogiNO($filter['logi_no_ext']);
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['logi_no_ext']);
        }
         if(isset($filter['addonSQL'])){
            $where .= ' AND '.$filter['addonSQL'];
            unset($filter['addonSQL']);
        }
        if(isset($filter['delivery_ident'])){
            $arr_delivery_ident = explode('_',$filter['delivery_ident']);
            $mdl_queue = app::get('ome')->model("print_queue");
            if(count($arr_delivery_ident) == 2){
                $ident_dly = array_pop($arr_delivery_ident);
                $ident = implode('-',$arr_delivery_ident);
                $queueItem = $mdl_queue->findQueueItem($ident,$ident_dly);
                if($queueItem){
                    $where .= '  AND delivery_id ='.$queueItem['delivery_id'].'';
                }else{
                    $where .= '  AND delivery_id IN (0)';
                }
            }else{
                if (1 == substr_count($filter['delivery_ident'], '-')) {
                    $queues = $mdl_queue->getList('dly_bns',array('ident|head'=>$filter['delivery_ident']));
                    if ($queues) $queue['dly_bns'] = implode(',', array_map('current', $queues));

                } else {
                    //$queue = $mdl_queue->findQueue($filter['delivery_ident'],'dly_bns');
                    #获取实际的打印批次号
                    $delivery_id = $mdl_queue->findQueueDeliveryId($filter['delivery_ident'],'delivery_id');
                    if($delivery_id){
                        $queue['dly_bns'] = $delivery_id;
                    }
                }

                if($queue){
                    $where .= '  AND delivery_id IN ('.$queue['dly_bns'].')';
                }else{
                    $where .= '  AND delivery_id IN (0)';
                }
            }

            unset($filter['delivery_ident']);
        }
        if(isset($filter['ship_tel_mobile'])){
            $where .= ' AND (ship_tel=\''.$filter['ship_tel_mobile'].'\' or ship_mobile=\''.$filter['ship_tel_mobile'].'\')';
            unset($filter['ship_tel_mobile']);
        }
        if($filter['todo']==1){
            $where .= " AND (stock_status='false' or expre_status='false' or deliv_status='false')";
            unset($filter['todo']);
        }
        if($filter['todo']==2){
            $where .= " AND (stock_status='false' or expre_status='false')";
            unset($filter['todo']);
        }
        if($filter['todo']==3){
            $where .= " AND (expre_status='false' or deliv_status='false')";
            unset($filter['todo']);
        }
        if($filter['todo']==4){
            $where .= " AND expre_status='false'";
            unset($filter['todo']);
        }

        if (isset($filter['print_finish'])) {
            $where_or = array();
            foreach((array)$filter['print_finish'] as $key=> $value){
                $or = "(deli_cfg='".$key."'";
                switch($value) {
                    case '1_1':
                        $or .= " AND stock_status='true' AND deliv_status='true' ";
                        break;
                    case '1_0':
                        $or .= " AND stock_status='true' ";
                        break;
                    case '0_1':
                        $or .= " AND deliv_status='true' ";
                        break;
                    case '0_0':
                        break;
                }
                $or .= ')';
                $where_or[] = $or;
            }
            if($where_or){
                $where .= ' AND ('.implode(' OR ',$where_or).')';
            }
            unset($filter['print_finish']);
        }
        if (isset($filter['ext_branch_id'])) {
            if (isset($filter['branch_id'])){
                $filter['branch_id'] = array_intersect((array)$filter['branch_id'],(array)$filter['ext_branch_id']);
                $filter['branch_id'] = $filter['branch_id'] ? $filter['branch_id'] : 'false';
            }else{
                $filter['branch_id'] = $filter['ext_branch_id'];
            }
            unset($filter['ext_branch_id']);
        }
        if(isset($filter['logi_no'])){
            $obj_delivery_bill = $deliOrderObj = &app::get('ome')->model("delivery_bill");
            #获取子表物流单号
            $delivery_id = $obj_delivery_bill->dump(array('logi_no'=>$filter['logi_no']),'delivery_id');
            if(!empty($delivery_id['delivery_id'])){
                unset($filter['logi_no']);
                $where .= "AND delivery_id = '{$delivery_id['delivery_id']}'";
            }
        }
        #客服备注
        if(isset($filter['mark_text'])){
            $mark_text = $filter['mark_text'];
            $sql = "SELECT do.delivery_id FROM sdb_ome_delivery_order do JOIN sdb_ome_orders o ON do.order_id=o.order_id  and o.process_status='splited' and  o.mark_text like "."'%{$mark_text}%'";
            $_rows = $this->db->select($sql);
            if(!empty($_rows)){
                foreach($_rows as $_orders){
                    $_delivery[] = $_orders['delivery_id'];
                }
                $where .= ' AND delivery_id IN ('.implode(',', $_delivery).')';
                unset($filter['mark_text']);
            }

        }
        #买家留言
        if(isset($filter['custom_mark'])){
            $custom_mark = $filter['custom_mark'];
            $sql = "SELECT do.delivery_id FROM sdb_ome_delivery_order do JOIN sdb_ome_orders o ON do.order_id=o.order_id  and o.process_status='splited' and  o.custom_mark like "."'%{$custom_mark}%'";
            $_rows = $this->db->select($sql);
            if(!empty($_rows)){
                foreach($_rows as $_orders){
                    $_delivery[] = $_orders['delivery_id'];
                }
                $where .= ' AND delivery_id IN ('.implode(',', $_delivery).')';
                unset($filter['custom_mark']);
            }
        
        } 

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    /*
     * 根据订单id获取是否撤销失败发货单
     * 
     *
     * @param bigint $order_id
     *
     * @return array $ids
     */

    function getDeliveryByOrderId($order_id){
        $delivery_ids = $this->db->select("SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id={$order_id} AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' AND d.status NOT IN('failed','cancel','back','return_back','succ') AND sync='fail'");
        $ids = array();
        if($delivery_ids){
            foreach($delivery_ids as $v){
                $ids[] = $v['delivery_id'];
            }
        }

        return $ids;
    }
}
?>
