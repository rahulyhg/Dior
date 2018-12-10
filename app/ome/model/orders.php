<?php

class ome_mdl_orders extends dbeav_model{

    //是否有导出配置
    var $has_export_cnf = true;
    //所有组用户信息
    static $__GROUPS = null;
    //所用户信息
    static $__USERS = null;

    var $has_many = array(
       //'delivery' => 'delivery', TODO:非标准写法，去掉后有报错需要修改代码
       'order_objects' => 'order_objects',
    );

    var $defaultOrder = array('createtime DESC ,order_id DESC');
    var $export_name = '订单';
    var $export_flag = false;

    static $order_source = array(
                    'local' => '分销王本地订单',
                    'fxjl' => '抓抓',
                    'taofenxiao' => '淘分销',
                    'tbjx' => '淘宝经销',
                    'tbdx' => '淘宝代销',
                    'secondbuy' => '分销王秒批订单',
                    'direct' => '直销订单',
                  );

    function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        if(isset($filter['archive'])){
            $where = ' archive='.$filter['archive'].' ';
            unset($filter['archive']);
        }else{
            $where = " 1 ";
        }

        if(isset($filter['tax_company'])){
            $where = ' tax_company like "'.$filter['tax_company'].'%" ';
            unset($filter['tax_company']);
        }

        if(isset($filter['order_confirm_filter'])){
            $where .= ' AND '.$filter['order_confirm_filter'];
            unset($filter['order_confirm_filter']);
        }
        if (isset($filter['assigned']))
        {
            if ($filter['assigned'] == 'notassigned')
            {
                $where .= ' AND (group_id=0 AND op_id=0)';
            }
            elseif($filter['assigned'] == 'buffer')//ExBOY加入SQl判断
            {
                
            }
            else
            {
                $where .= '  AND (op_id > 0 OR group_id > 0)';
            }
            
            $where  .= ' AND IF(process_status=\'is_retrial\', abnormal=\'true\', abnormal=\'false\')';//ExBOY加入SQl判断
            unset ($filter['assigned'], $filter['abnormal']);//ExBOY
        }
        if (isset($filter['balance'])){
            if ($filter['balance'])
                $where .= " AND `old_amount` != 0 AND `total_amount` != `old_amount` ";
            else
                $where .= " AND `old_amount` = 0 ";
        }
        //自动取消订单过滤条件
        if (isset($filter['auto_cancel_order_filter'])){
            $where .= '  AND '.$filter['auto_cancel_order_filter'];
        }

        if (isset($filter['custom_process_status'])){
            if(is_array($filter['custom_process_status'])){
                $where .= '  AND process_status IN (\''.implode('\',\'', $filter['custom_process_status']).'\')';
            }elseif($filter['custom_process_status']){
                $where .= '  AND process_status ='.$filter['custom_process_status'];
            }
        }

        if(isset($filter['product_bn'])){
            $itemsObj = &$this->app->model("order_items");
            $rows = $itemsObj->getOrderIdByFilterbn($filter);
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $pkjrows = $itemsObj->getOrderIdByPkgbn($filter['product_bn']);
            foreach($pkjrows as $pkjrow){
                $orderId[] = $pkjrow['order_id'];
            }

            $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['product_bn']);
        }

        //支付失败
        if(isset($filter['payment_fail']) && $filter['payment_fail'] == true){
            $api_fail = &$this->app->model("api_fail");
            $payment_fail_list = $api_fail->getList('order_id', array('type'=>'payment'), 0, -1);
            $payment_order_id = array();
            if ($payment_fail_list){
                foreach($payment_fail_list as $val){
                    $payment_order_id[] = $val['order_id'];
                }
            }
            $payment_order_id = implode(',', $payment_order_id);
            $payment_order_id =  $payment_order_id ? $payment_order_id : '\'\'';
            $where .= '  AND order_id IN ('.$payment_order_id.')';
            unset($filter['payment_fail']);
        }

        if(isset($filter['product_barcode'])){
            $itemsObj = &$this->app->model("order_items");
            $rows = $itemsObj->getOrderIdByPbarcode($filter['product_barcode']);
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $where .= '  AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['product_barcode']);
        }
        //判断是否录入发票号
        if(isset($filter['is_tax_no'])){
            if($filter['is_tax_no']==1){
                $where .= '  AND tax_no IS NOT NULL';

            }else{
                $where .= '  AND tax_no IS NULL';
            }
            unset($filter['is_tax_no']);
        }
        //付款确认
        if (isset($filter['pay_confirm'])){
            $where .= ' AND '.$filter['pay_confirm'];
            unset($filter['pay_confirm']);
        }
        //确认状态
        if (isset($filter['process_status_noequal'])){
            $value = '';
            foreach($filter['process_status_noequal'] as $k=>$v){
                $value .= "'".$v."',";
            }
            $len = strlen($value);
            $value_last = substr($value,0,($len-1));
            $where .= ' AND process_status not in ( '.$value_last.")";
            unset($filter['process_status_noequal']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = &$this->app->model("members");
            $rows = $memberObj->getList('member_id',array('uname|head'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }
            $where .= '  AND member_id IN ('.implode(',', $memberId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['pay_type'])){
            $cfgObj = &app::get('ome')->model('payment_cfg');
            $rows = $cfgObj->getList('pay_bn',array('pay_type'=>$filter['pay_type']));
            $pay_bn[] = 0;
            foreach($rows as $row){
                $pay_bn[] = $row['pay_bn'];
            }
            $where .= '  AND pay_bn IN (\''.implode('\',\'', $pay_bn).'\')';
            unset($filter['pay_type']);
        }
        if(isset($filter['ship_tel_mobile'])){
            $where .= ' AND (ship_tel=\''.$filter['ship_tel_mobile'].'\' or ship_mobile=\''.$filter['ship_tel_mobile'].'\')';
            unset($filter['ship_tel_mobile']);
        }
        //部分支付 包含部分退款 部分支付
        if(isset($filter['pay_status_part'])){
            $where .= ' AND (pay_status = \'3\' or (pay_status = \'4\' and ship_status = \'0\'))';
            unset($filter['pay_status_part']);
        }
        //付款确认时，部分退款的只有未发货的才能继续支付
        if(isset($filter['pay_status_set'])){
            if($filter['pay_status_set'] == 2){
                $where .= ' AND (pay_status in (\'0\',\'3\') or (pay_status = \'4\' and ship_status = \'0\'))';
            }else{
                $where .= ' AND (pay_status in (\'0\',\'3\',\'8\') or (pay_status = \'4\' and ship_status = \'0\'))';
            }
            unset($filter['pay_status_set']);
        }
        #支付方式搜索
        if (isset($filter['pay_bn'])){
            $cfgObj = &app::get('ome')->model('payment_cfg');
            $_rows = $cfgObj->getList('custom_name',array('pay_bn'=>$filter['pay_bn']));
            $where .= '  AND payment='."'{$_rows[0]['custom_name']}' ";
            unset($_rows);
            unset($filter['pay_bn']);
        }

        if( isset($filter['order_source']) && $filter['order_source'] ){

            $order_source = array_keys(self::$order_source);

            if(in_array($filter['order_source'], $order_source) && $filter['order_source']!='direct'){
                $where .=' AND order_source = \''.$filter['order_source'].'\'';
            }else{
                $where .=' AND order_source not in ( \''.implode('\',\'', $order_source).'\')';
                
            }

            unset($filter['order_source']);
        }
        if(isset($filter['logi_no'])&&$filter['logi_no']){
            #使用子表物流单号进行搜索
            $sql = 'select 
	                    orders.order_id
                    from sdb_ome_delivery_bill bill 
                    join   sdb_ome_delivery  delivery  
                    on bill.delivery_id=delivery.delivery_id and bill.logi_no='."'{$filter['logi_no']}'".'
                    join  sdb_ome_delivery_order  orders on  delivery.delivery_id=orders.delivery_id';
            $_row = $this->db->selectrow($sql);
            if(!empty($_row['order_id'])){
                unset($filter['logi_no']);
                $where .= ' AND order_id ='.$_row['order_id'];
            }
        }
        #客服备注
        if(isset($filter['mark_text'])){
            $mark_text = trim($filter['mark_text']);
            $sql = "SELECT order_id,mark_text FROM   sdb_ome_orders where  mark_text like "."'%{$mark_text}%'";
            $_rows = $this->db->select($sql);
            if(!empty($_rows)){
                foreach($_rows as $_orders){
                    $_order_id[] = $_orders['order_id'];
                };
                $where .= ' AND order_id IN ('.implode(',', $_order_id).')';
                unset($filter['mark_text']);
            }
        }
        #是否签收
        if(isset($filter['is_received'])){
            $_order_id[] = 0;
            $sql = 'select
	                    orders.order_id
                    from sdb_ome_delivery  delivery
                    left join  sdb_ome_delivery_order  orders on  delivery.delivery_id=orders.delivery_id
                    where delivery.is_received='."'".$filter['is_received']."'";
            $_row = $this->db->select($sql);
            if(!empty($_row)){
                foreach($_row as $_orders){
                    $_order_id[] = $_orders['order_id'];
                };
            }
            $where .= ' AND order_id IN ('.implode(',', $_order_id).')';
            unset($filter['is_received']);
        }
        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }

    /**
    * 快速查询订单主表信息
    * @access public
    * @param mixed $filter 过滤条件,也可以直接是订单主键ID,如:$order_id
    * @param String $cols 字段名
    * @return Array 订单信息
    */
    function getRow($filter,$cols='*'){
        if (empty($filter)) return array();

        $wheresql = '';
        if (is_array($filter)){
            foreach ($filter as $col=>$value){
                $wheresql[] = '`'.$col.'`=\''.$value.'\'';
            }
            $wheresql = implode(' AND ', $wheresql);
        }else{
            $wheresql = '`order_id`='.$filter;
        }
        $sql = sprintf('SELECT %s FROM `sdb_ome_orders` WHERE %s',$cols,$wheresql);
        $row = $this->db->selectrow($sql);
        return $row;
    }

    /**
    * 获取订单商品明细
    * @access public
    * @param Number $order_id 订单ID
    * @return Array 订单商品明细
    */
    function order_objects($order_id){
        if (empty($order_id)) return array();

        $order_objects = array();
        $wheresql = 'order_id='.$order_id;
        #objects
        $sql = sprintf('SELECT * FROM `sdb_ome_order_objects` WHERE %s',$wheresql);
        $order_objects = $this->db->select($sql);

        #items
        $sql = sprintf('SELECT * FROM `sdb_ome_order_items` WHERE %s',$wheresql);
        $items_list = $this->db->select($sql);
        if ($items_list){
            $tmp_items = array();
            foreach ($items_list as $i_key=>$i_val){
                $tmp_items[$i_val['obj_id']][] = $i_val;
            }
            $items_list = NULL;
        }

        if ($order_objects){
            foreach ($order_objects as $o_key=>&$o_val){
                $o_val['order_items'] = $tmp_items[$o_val['obj_id']];
            }
        }

        return $order_objects;
    }

    function modifier_mark_type($row){
        if($row){
            $tmp = ome_order_func::order_mark_type($row);
            if($tmp){
                $tmp = "<img src='".$tmp."' width='20' height='20'>";
                return $tmp;
            }
        }
    }

    function modifier_order_source($row){
        if($row){
            $tmp = ome_order_func::get_order_source($row);
            if($tmp){
                return $tmp;
            }
        }
    }

    function modifier_is_cod($row){
        if($row == 'true'){
            return "<div style='width:48px;padding:2px;height:16px;background-color:green;float:left;'><span style='color:#eeeeee;'>货到付款</span></div>";
        }else{
            return '在线支付';
        }
    }

    /**
     * 订单暂停
     */
    function pauseOrder($order_id, $must_update = 'false'){

        #[发货配置]是否启动拆单 ExBOY
        $dlyObj = &app::get('ome')->model("delivery");
        $split_seting   = $dlyObj->get_delivery_seting();
        
        $rs = array('rsp'=>'succ','msg'=>'');
        if ($order_id){
            $o = $this->dump($order_id,'pause, process_status, ship_status');//[拆单]确认状态_发货状态 ExBOY

            #[拆单]部分拆分_部分发货_订单暂停操作(存在多个发货单)
            if(!empty($split_seting) && ($o['process_status'] == 'splitting' || $o['ship_status'] == '2'))
            {
                $rs     = $this->pauseOrder_split($order_id, $must_update);
                return $rs;
            }
            
            if ($o['pause'] == 'false' || $must_update == 'true'){
                //$dlyObj = &app::get('ome')->model("delivery");
                $oOperation_log = &app::get('ome')->model('operation_log');
                $branchLib = kernel::single('ome_branch');
                $channelLib = kernel::single('channel_func');
                $eventLib = kernel::single('ome_event_trigger_delivery');
                $delivery_itemsObj = &app::get('ome')->model('delivery_items');
                $branch_productObj = &app::get('ome')->model('branch_product');
                //查询订单是否有发货单
                $delivery_id = $dlyObj->getDeliverIdByOrderId($order_id);
                if($delivery_id){
                    //目前一个订单只会有一个对应的主发货单
                    $delivery_id = $delivery_id[0];

                    //取仓库信息
                    $deliveryInfo = $dlyObj->dump($delivery_id,'*');
                    if($deliveryInfo['status'] == 'succ') {
                        return $rs;
                    }
                    $wms_id = $branchLib->getWmsIdById($deliveryInfo['branch_id']);
                    if($wms_id){
                        $is_selfWms = $channelLib->isSelfWms($wms_id);
                        if($is_selfWms){
                            $res = $eventLib->pause($wms_id,array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']),true);

                            if($res['rsp'] == 'success' || $res['rsp'] == 'succ'){
                                //wms暂停发货单成功，暂停本地主发货单
                                $tmpdly = array(
                                    'delivery_id' => $deliveryInfo['delivery_id'],
                                    'pause' => 'true'
                                );
                                $dlyObj->save($tmpdly);
                                $oOperation_log->write_log('delivery_modify@ome',$deliveryInfo['delivery_id'],'发货单暂停');

                                //是否是合并发货单
                                if($deliveryInfo['is_bind'] == 'true'){
                                    //取关联发货单号进行暂停
                                    $delivery_ids = $dlyObj->getItemsByParentId($deliveryInfo['delivery_id'],'array');
                                    if($delivery_ids){
                                        foreach ($delivery_ids as $id){
                                            $tmpdly = array(
                                                'delivery_id' => $id,
                                                'pause' => 'true'
                                            );
                                            $dlyObj->save($tmpdly);
                                            $oOperation_log->write_log('delivery_modify@ome',$id,'发货单暂停');
                                        }
                                    }

                                    //取关联订单号进行暂停
                                    $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
                                    if($order_ids){
                                        foreach ($order_ids as $id){
                                            $order['order_id'] = $id;
                                            $order['pause'] = 'true';
                                            $this->save($order);
                                            $oOperation_log->write_log('order_modify@ome',$id,'订单暂停');



                                        }
                                    }
                                }else{
                                    //暂停当前订单
                                    $order['order_id'] = $order_id;
                                    $order['pause'] = 'true';
                                    $this->save($order);
                                    $oOperation_log->write_log('order_modify@ome',$order_id,'订单暂停');
                                }

                                //订单暂停状态同步
                                if ($service_order = kernel::servicelist('service.order')){
                                    foreach($service_order as $object=>$instance){
                                        if(method_exists($instance, 'update_order_pause_status')){
                                           if($order_ids){
                                               foreach ($order_ids as $id){
                                                   $instance->update_order_pause_status($id);
                                               }
                                           }else{
                                               $instance->update_order_pause_status($order_id);
                                           }
                                        }
                                    }
                                }
                                //$rs['rsp'] = 'succ';
                            }else{
                                $rs['msg'] = $res['msg'];
                                $rs['rsp']= 'fail';
                                $rs['delivery_id'] = $deliveryInfo['delivery_id'];
                                //return false;
                            }
                        }else{
                            $res = $eventLib->cancel($wms_id,array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']),true);
                           
                            if($res['rsp'] == 'success' || $res['rsp'] == 'succ'){
                                //wms第三方仓储取消发货单成功，本地主发货单取消
                                $tmpdly = array(
                                    'delivery_id' => $deliveryInfo['delivery_id'],
                                    'status' => 'cancel',
                                    'logi_id' => '',
                                    'logi_name' => '',
                                    'logi_no' => NULL,
                                );
                                $dlyObj->save($tmpdly);
                                $oOperation_log->write_log('delivery_modify@ome',$deliveryInfo['delivery_id'],'发货单撤销成功');

                                //是否是合并发货单
                                if($deliveryInfo['is_bind'] == 'true'){
                                    //取关联发货单号进行暂停
                                    $delivery_ids = $dlyObj->getItemsByParentId($deliveryInfo['delivery_id'],'array');
                                    if($delivery_ids){
                                        foreach ($delivery_ids as $id){
                                            $tmpdly = array(
                                                'delivery_id' => $id,
                                                'status' => 'cancel',
                                                'logi_id' => '',
                                                'logi_name' => '',
                                                'logi_no' => NULL,
                                            );
                                            $dlyObj->save($tmpdly);
                                            $oOperation_log->write_log('delivery_modify@ome',$id,'发货单撤销成功');
                                        }
                                    }

                                    //取关联订单号进行还原
                                    $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
                                    if($order_ids){
                                        foreach ($order_ids as $id){
                                            
                                            $order['order_id'] = $id;
                                            $order['confirm'] = 'N';
                                            $order['process_status'] = 'unconfirmed';
                                            $this->save($order);
                                            //取对应组
                                            $this->updateDispatchinfo($id);
                                            $oOperation_log->write_log('order_modify@ome',$id,'发货单撤销,订单还原需重新审核');
                                        }
                                    }
     
                                }else{
                                    //还原当前订单
                                    $order['order_id'] = $order_id;
                                    $order['confirm'] = 'N';
                                    $order['process_status'] = 'unconfirmed';
                                    $this->save($order);
                                    //取对应组
                                    $this->updateDispatchinfo($order_id);
                                    $oOperation_log->write_log('order_modify@ome',$order_id,'发货单撤销,订单还原需重新审核');
                                }

                                //释放冻结库存
                                 //增加branch_product释放冻结库存
                                $branch_id = $deliveryInfo['branch_id'];
                                $product_ids = $delivery_itemsObj->getList('product_id,number',array('delivery_id'=>$deliveryInfo['delivery_id']),0,-1);
                                foreach($product_ids as $key=>$v){
                                    $branch_productObj->unfreez($branch_id,$v['product_id'],$v['number']);
                                }
                                //
                            }else{
                                $rs['rsp'] = 'fail';
                                $rs['msg'] = $res['msg'];
                                $rs['delivery_id'] = $deliveryInfo['delivery_id'];
                                //return false;
                                $dlyObj->update_sync_cancel($deliveryInfo['delivery_id'],'fail'); 
                                $oOperation_log->write_log('delivery_back@ome',$deliveryInfo['delivery_id'],'发货单取消失败,原因:'.$rs['msg']);
                            }
                            
                            
                        }
                    }else{
                        $rs['rsp'] = 'fail';
                        //return false;
                    }
                }else{
                    //没有发货单的情况，直接暂停当前订单
                    $order['order_id'] = $order_id;
                    $order['pause'] = 'true';
                    $this->save($order);
                    $oOperation_log->write_log('order_modify@ome',$order_id,'订单暂停');

                    //订单暂停状态同步
                    if ($service_order = kernel::servicelist('service.order')){
                        foreach($service_order as $object=>$instance){
                            if(method_exists($instance, 'update_order_pause_status')){
                                $instance->update_order_pause_status($order_id);
                            }
                        }
                    }
                }
                //return true;
            }
        }
        return $rs;
    }

    /**
     * 订单恢复
     */
    function renewOrder($order_id){
        #[发货配置]是否启动拆单 ExBOY
        $dlyObj = &app::get('ome')->model("delivery");
        $split_seting   = $dlyObj->get_delivery_seting();
        
        if ($order_id){
            $o = $this->dump($order_id,'pause, process_status, ship_status');//[拆单]确认状态_发货状态 ExBOY
            
            #[拆单]部分拆分_部分发货_订单暂停操作(存在多个发货单)
            if(!empty($split_seting) && ($o['process_status'] == 'splitting' || $o['ship_status'] == '2'))
            {
                $rs     = $this->renewOrder_split($order_id);
                return $rs;
            }

            if ($o['pause'] == 'true'){
                //$dlyObj = &app::get('ome')->model("delivery");
                $oOperation_log = &app::get('ome')->model('operation_log');
                $branchLib = kernel::single('ome_branch');
                $channelLib = kernel::single('channel_func');
                $eventLib = kernel::single('ome_event_trigger_delivery');

                //查询订单是否有发货单
                $delivery_id = $dlyObj->getDeliverIdByOrderId($order_id);

                if($delivery_id){
                    //目前一个订单只会有一个对应的主发货单
                    $delivery_id = $delivery_id[0];

                    //取仓库信息
                    $deliveryInfo = $dlyObj->dump($delivery_id,'*');

                    $wms_id = $branchLib->getWmsIdById($deliveryInfo['branch_id']);
                    if($wms_id){
                        $is_selfWms = $channelLib->isSelfWms($wms_id);
                        if($is_selfWms){
                            $res = $eventLib->renew($wms_id,array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']),true);
                            if($res['rsp'] == 'success' || $res['rsp'] == 'succ'){
                                //wms恢复发货单成功，恢复本地主发货单
                                $tmpdly = array(
                                    'delivery_id' => $deliveryInfo['delivery_id'],
                                    'pause' => 'false'
                                );
                                $dlyObj->save($tmpdly);
                                $oOperation_log->write_log('delivery_modify@ome',$deliveryInfo['delivery_id'],'发货单恢复');

                                //是否是合并发货单
                                if($deliveryInfo['is_bind'] == 'true'){
                                    //取关联发货单号进行暂停
                                    $delivery_ids = $dlyObj->getItemsByParentId($deliveryInfo['delivery_id'],'array');
                                    if($delivery_ids){
                                        foreach ($delivery_ids as $id){
                                            $tmpdly = array(
                                                'delivery_id' => $id,
                                                'pause' => 'false'
                                            );
                                            $dlyObj->save($tmpdly);
                                            $oOperation_log->write_log('delivery_modify@ome',$id,'发货单恢复');
                                        }
                                    }

                                    //取关联订单号进行暂停
                                    $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
                                    if($order_ids){
                                        foreach ($order_ids as $id){
                                            $order['order_id'] = $id;
                                            $order['pause'] = 'false';
                                            $this->save($order);
                                            $oOperation_log->write_log('order_modify@ome',$id,'订单恢复');


                                        }
                                    }
                                }else{
                                    //暂停当前订单
                                    $order['order_id'] = $order_id;
                                    $order['pause'] = 'false';
                                    $this->save($order);
                                    $oOperation_log->write_log('order_modify@ome',$order_id,'订单恢复');
                                }

                                //订单暂停状态同步
                                if ($service_order = kernel::servicelist('service.order')){
                                    foreach($service_order as $object=>$instance){
                                        if(method_exists($instance, 'update_order_pause_status')){
                                           if($order_ids){
                                               foreach ($order_ids as $id){
                                                   $instance->update_order_pause_status($id, 'false');
                                               }
                                           }else{
                                               $instance->update_order_pause_status($order_id, 'false');
                                           }
                                        }
                                    }
                                }
                            }else{
                                return false;
                            }
                        }
                    }else{
                        return false;
                    }
                }else{
                    $order['order_id'] = $order_id;
                    $order['pause'] = 'false';
                    $this->save($order);
                    $oOperation_log->write_log('order_modify@ome',$order_id,'订单恢复');

                    //订单恢复状态同步
                    if ($service_order = kernel::servicelist('service.order')){
                        foreach($service_order as $object=>$instance){
                            if(method_exists($instance, 'update_order_pause_status')){
                               $instance->update_order_pause_status($order_id, 'false');
                            }
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }

    //分派时间
    function modifier_dispatch_time($row){
       if ($row){
           $tmp = date('Y年m月d日 H点',$row);
           return $tmp;
       }
    }

    /**
     * 确认组
     *
     * @param Integer $row 组ID
     * @return void
     */
    function modifier_group_id($row) {

        switch ($row) {
            case 0:
                $ret = '无';
                break;
            case 16777215:
                $ret = '系统';
                break;
            default:
                $ret = $this->_getGroupName($row);
                break;
        }

        return $ret;
    }

    /**
     * 获取用户名
     *
     * @param Integer $gid
     * @return String;
     */
    private function _getUserName($uid) {
        if (self::$__USERS === null) {

            self::$__USERS = array();
            $rows = app::get('desktop')->model('users')->getList('*');
            foreach((array) $rows as $row) {
                self::$__USERS[$row['user_id']] = $row['name'];
            }
        }

        if (isset(self::$__USERS[$uid])) {

            return self::$__USERS[$uid];
        } else {

            return '未知';
        }
    }

    /**
     * 获取组名
     *
     * @param Integer $gid
     * @return String;
     */
    private function _getGroupName($gid) {

        if (self::$__GROUPS === null) {

            self::$__GROUPS = array();
            $rows = app::get('ome')->model('groups')->getList('*');
            foreach((array) $rows as $row) {
                self::$__GROUPS[$row['group_id']] = $row['name'];
            }
        }

        if (isset(self::$__GROUPS[$gid])) {

            return self::$__GROUPS[$gid];
        } else {

            return '未知';
        }
    }

    /**
     * 确认人
     *
     * @param Integer $row 确认人ID
     * @return void
     */
    function modifier_op_id($row) {

        switch ($row) {
            case 0:
                $ret = '无';
                break;
            case 16777215:
                $ret = '系统';
                break;
            default:
                $ret = $this->_getUserName($row);
                break;
        }

        return $ret;
    }

    /**
     * 增加旺旺联系方式
     *
     * @param integre $row 用户ID
     * @return String
     */
    function modifier_member_id($row) {

        $member = app::get('ome')->model('shop_members')->dump(array('member_id' => $row));
        if ($member) {
            $type = app::get('ome')->model('shop')->dump(array('shop_id' => $member['shop_id']), 'shop_type');
            if ($type['shop_type'] == 'taobao') {
                return sprintf("<a href='http://amos.im.alisoft.com/msg.aw?v=2&amp;uid=%s&amp;site=cntaobao&amp;s=1&amp;charset=utf-8' target='_blank'><img border=0 title='点击这给 %s 发送消息' src='http://amos.im.alisoft.com/online.aw?v=2&amp;uid=%s&amp;site=cntaobao&amp;s=2&amp;charset=utf-8'></a>%s", $member['shop_member_id'], $member['shop_member_id'], $member['shop_member_id'], $member['shop_member_id']);
            } else {
                return $member['shop_member_id'];
            }
        } else {

            $member = app::get('ome')->model('members')->dump(array('member_id' => $row));
            return $member['account']['uname'];
        }
    }

    /**
     * 打回订单的发货单
     * @param int $order_id 订单号
     * @param boolean $reback_status 打回状态，默认为false:打回所有发货单;true：只打回未发货的发货单
     */
    function rebackDeliveryByOrderId($order_id,$dly_status=false){
        #[发货配置]是否启动拆单 ExBOY
        $dlyObj = &app::get('ome')->model("delivery");
        $split_seting   = $dlyObj->get_delivery_seting();
        
        #[拆单]打回发货单操作(存在多个发货单)
        if(!empty($split_seting))
        {
            //包含_部分拆分_部分发货的订单
            $in_order_id    = (is_array($order_id) ? implode(',', $order_id) : $order_id);
            $order_row      = $this->db->selectRow("SELECT order_id FROM sdb_ome_orders 
                                                    WHERE order_id in(".$in_order_id.") AND (process_status='splitting' OR ship_status='2')");
            if(!empty($order_row))
            {
                $result     = $this->rebackDeliveryByOrderId_split($order_id, $dly_status);
                return $result;
            }
        }
        
        //$dlyObj = &$this->app->model("delivery");
        $dly_oObj = &$this->app->model("delivery_order");
        $opObj = &$this->app->model('operation_log');
        $data = $dly_oObj->getList('*',array('order_id'=>$order_id),0,-1);
        $bind = array();
        $dlyos = array();
        $mergedly = array();
        if ($data)
        foreach ($data as $v){
            $dly = $dlyObj->dump($v['delivery_id'],'process,status,parent_id,is_bind');
            //只打回未发货的发货单
            if ($dly_status == true){
                if ($dly['process'] == 'true' || in_array($dly['status'],array('failed', 'cancel', 'back', 'succ','return_back'))) continue;
            }
            if ($dly['parent_id'] == 0 && $dly['is_bind'] == 'true'){
                $bind[$v['delivery_id']]['delivery_id'] = $v['delivery_id'];
            }elseif ($dly['parent_id'] == 0){
                $dlyos[$v['delivery_id']][] = $v['delivery_id'];
            }else{
                $mergedly[$v['delivery_id']] = $v['delivery_id'];
                $bind[$dly['parent_id']]['items'][] = $v['delivery_id'];
            }
        }
       
        //如果是合并发货单的话
        if ($bind)
        foreach ($bind as $k => $i){
            $items = $dlyObj->getItemsByParentId($i['delivery_id'], 'array', 'delivery_id');
             
                
            if (sizeof($items) - sizeof($i['items']) < 2){
                $result = $dlyObj->splitDelivery($i['delivery_id'],'',$i['items']);
            }else {
                $result = $dlyObj->splitDelivery($i['delivery_id'], $i['items'],$i['items']);
            }
            if ($result){
                $dlyObj->rebackDelivery($i['items'], '', $dly_status);
                foreach ($i['items'] as $i){
                    $opObj->write_log('delivery_back@ome', $i ,'发货单打回');
                    $dlyObj->updateOrderPrintFinish($i, 1);
                }
            }
        }

        //单个发货单
        if ($dlyos)
        foreach ($dlyos as $v){
            $dlyObj->rebackDelivery($v, '', $dly_status);
            $opObj->write_log('delivery_back@ome', $v ,'发货单打回');
            $dlyObj->updateOrderPrintFinish($v, 1);
        }
        return true;
    }

    /**
     * 获得总数量
     *
     * @param string $where
     *
     * @return array()
     */
    function get_all($where){
        $minute = $this->app->getConf('ome.order.unconfirmtime');
        $time = time() - $minute*60;

        $sql = "SELECT COUNT(o.order_id) AS 'TOTAL' FROM sdb_ome_orders o
                                        WHERE 1 $where ";
        $re4 = $this->db->selectrow($sql);
        $sql = "SELECT COUNT(o.order_id) AS 'TOTAL' FROM sdb_ome_orders o
                                        WHERE (is_cod='true' OR pay_status='1') $where
                                            AND (`op_id` is null and `group_id` is null)";
        $re1 = $this->db->selectrow($sql);
        $sql = "SELECT COUNT(o.order_id) AS 'TOTAL' FROM sdb_ome_orders o
                                        WHERE (`op_id` is not null or `group_id` is not null) $where
                                            AND o.confirm='N'";
        $re2 = $this->db->selectrow($sql);
        $sql = "SELECT COUNT(o.order_id) AS 'TOTAL' FROM sdb_ome_orders o
                                        WHERE (`op_id` is not null or `group_id` is not null) $where
                                            AND o.confirm='N'
                                            AND o.dt_begin < $time ";
        $re3 = $this->db->selectrow($sql);

        $re['all'] = $re4['TOTAL'];
        $re['a'] = $re1['TOTAL'];
        $re['b'] = $re2['TOTAL'];
        $re['c'] = $re3['TOTAL'];
        return $re;
    }


    /**
     * 获得确认组订单数量
     *
     * @param string $where
     *
     * @return array
     */
    function get_group($where){
        $sql = "SELECT o.group_id,g.name FROM sdb_ome_orders o
                                JOIN sdb_ome_groups g
                                    ON o.group_id=g.group_id
                                WHERE g.g_type='confirm' $where GROUP BY o.group_id ";
        $data = $this->db->select($sql);
        $result = array();
        if ($data){
            $minute = $this->app->getConf('ome.order.unconfirmtime');
            $time = time() - $minute*60;
            foreach ($data as $v){
                $group_id = $v['group_id'];
                $result[$group_id]['name'] = $v['name'];

                $sql = "SELECT COUNT(order_id) AS 'TOTAL' FROM sdb_ome_orders  as o
                                        WHERE group_id=$group_id
                                            AND (`op_id` is not null or `group_id` is not null)
                                            AND confirm='N' $where";
                $re = $this->db->selectrow($sql);
                $result[$group_id]['b'] = $re['TOTAL'];

                $sql = "SELECT COUNT(order_id) AS 'TOTAL' FROM sdb_ome_orders
                                        WHERE group_id=$group_id
                                            AND (`op_id` is not null or `group_id` is not null)
                                            AND confirm='N'
                                            AND dt_begin < $time ";
                $re = $this->db->selectrow($sql);
                $result[$group_id]['c'] = $re['TOTAL'];
            }
        }

        return $result;
    }


    /**
     * 获得已分派但未确认时间超过设定时间的订单数量
     *
     * @param string $where
     * @param string $type
     *
     * @return number
     */
    function get_operator($where){
        $sql = "SELECT o.group_id,g.name as 'g_name',o.op_id,u.name as 'u_name' FROM sdb_ome_orders o
                                JOIN sdb_ome_groups g
                                    ON o.group_id=g.group_id
                                JOIN sdb_desktop_users u
                                    ON u.user_id=o.op_id
                                WHERE g.g_type='confirm' $where GROUP BY o.op_id ";
        $data = $this->db->select($sql);
        $result = array();
        if ($data){
            $minute = $this->app->getConf('ome.order.unconfirmtime');
            $time = time() - $minute*60;
            foreach ($data as $v){
                $op_id = $v['op_id'];
                $result[$op_id]['g_name'] = $v['g_name'];
                $result[$op_id]['u_name'] = $v['u_name'];

                $sql = "SELECT COUNT(order_id) AS 'TOTAL' FROM sdb_ome_orders as o
                                        WHERE op_id=$op_id
                                            AND (`op_id` is not null or `group_id` is not null)
                                            AND confirm='N' $where";
                $re = $this->db->selectrow($sql);
                $result[$op_id]['b'] = $re['TOTAL'];

                $sql = "SELECT COUNT(order_id) AS 'TOTAL' FROM sdb_ome_orders
                                        WHERE op_id=$op_id
                                            AND (`op_id` is not null or `group_id` is not null)
                                            AND confirm='N'
                                            AND dt_begin < $time ";
                $re = $this->db->selectrow($sql);
                $result[$op_id]['c'] = $re['TOTAL'];
            }
        }

        return $result;
    }

    function get_confirm_ops(){
        $sql = "SELECT go.op_id,u.name FROM sdb_ome_group_ops go
                            JOIN sdb_ome_groups g
                                ON g.group_id = go.group_id
                            JOIN sdb_desktop_users u
                                ON go.op_id = u.user_id
                            WHERE g.g_type = 'confirm' GROUP BY go.op_id ";
        $re = $this->db->select($sql);
        return $re;
    }
    /*
     * 根据订单来恢复预占的冻结库存
     * 比如在订单被取消时，就需要恢复冻结库存
     *
     * @param int $order_id
     *
     */
    function unfreez($order_id){
        $delivery_ids = $this->db->select("SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id={$order_id} AND d.is_bind='false' AND d.disabled='false'");

        $oDelivery = &$this->app->model("delivery");
        //danny_freeze_stock_log
        if($delivery_ids){
            foreach($delivery_ids as $v){
                //danny_freeze_stock_log
                $frst_info = $this->dump(array('order_id'=>$order_id),'shop_id,shop_type,order_bn');
                $GLOBALS['frst_shop_id'] = $frst_info['shop_id'];
                $GLOBALS['frst_shop_type'] = $frst_info['shop_type'];
                $GLOBALS['frst_order_bn'] = $frst_info['order_bn'];

                $oDelivery->unfreez($v['delivery_id']);
            }
        }

        //unfreeze剩余未生成发货单的货品
        $items = $this->db->select("SELECT product_id,nums FROM sdb_ome_order_items WHERE order_id=".$order_id);
        $oProduct = &$this->app->model("products");
        foreach($items as $v){
            $num = $v['nums'] - $oDelivery->getDeliveryFreez($order_id,$v['product_id']);
            $oProduct->chg_product_store_freeze($v['product_id'],$num,"-");
        }

        return true;
    }

    /**
     * 更新订单状态
     *
     * @param bigint $order_id
     * @param string $status
     *
     * @return boolean
     */
    function set_status($order_id, $status){
        $data['order_id'] = $order_id;
        $data['process_status'] = $status['order_status'];
        if(isset($status['pause'])){
            $data['pause'] = $status['pause'];
        }


        return $this->save($data);
    }

    /*
     * 取消发货单
     *
     * @param int $order_id
     *
     * @return bool
     */
    function cancel_delivery($order_id){
        $rs = array('rsp'=>'succ','msg'=>'');
        $delivery = $this->db->selectrow("SELECT dord.delivery_id,d.is_bind,d.status,d.delivery_bn,d.branch_id FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id={$order_id} AND d.disabled='false' AND d.parent_id=0 AND d.status not in('cancel','back','succ','return_back')");
                                   

        if($delivery){
            $oDelivery = &$this->app->model("delivery");
            $delivery_itemsObj = &$this->app->model('delivery_items');
            $itemsObj = &$this->app->model("order_items");
            $branch_productObj = $this->app->model('branch_product');
            $oOperation_log = &app::get('ome')->model('operation_log');
            $eventLib = kernel::single('ome_event_trigger_delivery');
            $branchLib = kernel::single('ome_branch');
            $dlyObj = &app::get('ome')->model("delivery");
            //
            $is_bind = $delivery['is_bind'];
            $branch_id = $delivery['branch_id'];
            $delivery_bn = $delivery['delivery_bn'];
            $delivery_id = $delivery['delivery_id'];
            $wms_id = $branchLib->getWmsIdById($branch_id);
           
            if ($is_bind == 'false') {
                $res = $eventLib->cancel($wms_id,array('outer_delivery_bn'=>$delivery_bn),true);
                //更新取消状态
                
                if ( $res['rsp'] == 'success' || $res['rsp'] == 'succ' ) {
                    $tmpdly = array(
                        'delivery_id' => $delivery_id,
                        'status' => 'cancel',
                        'logi_id' => '',
                        'logi_name' => '',
                        'logi_no' => NULL,
                    );
                    $dlyObj->save($tmpdly);
                    $oOperation_log->write_log('delivery_modify@ome',$delivery_id,'发货单撤销成功');
                    //更新发货单状态 API
                    foreach(kernel::servicelist('service.delivery') as $object=>$instance){
                        if(method_exists($instance,'update_status')){
                            $instance->update_status($delivery_id, 'cancel', false);
                        }
                    }
                    $product_ids = $delivery_itemsObj->getList('product_id,number',array('delivery_id'=>$deliveryInfo['delivery_id']),0,-1);
                    foreach($product_ids as $key=>$v){
                        $branch_productObj->unfreez($branch_id,$v['product_id'],$v['number']);
                    }
                    $dlyObj->update_sync_cancel($delivery_id,'succ');
                }else{
                    $rs['rsp'] = 'fail';
                    $rs['msg'] = $res['msg'];
                    $oOperation_log->write_log('delivery_back@ome',$delivery_id,'发货单取消失败,原因'.$rs['msg']);
                    $dlyObj->update_sync_cancel($delivery_id,'fail');
                }
                
                
            }else{

                //合并发货单,请求的
                $SQL = "SELECT dord.delivery_id,dord.order_id FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id={$order_id} AND d.disabled in ('false') AND d.parent_id!=0 AND d.status not in('cancel','back','succ','return_back') AND d.parent_id=".$delivery_id;
                
                $delivery_order = $this->db->selectrow($SQL);
                if ($delivery_order) {
                    $order_items = $itemsObj->getlist('product_id,nums',array('order_id'=>$delivery_orde['order_id']));
                     foreach ( $order_items as $item ) {
                         $branch_productObj->unfreez($branch_id,$item['product_id'],$item['nums']);
                     }
                    $tmpdly = array(
                        'delivery_id' => $delivery_order['delivery_id'],
                        'status' => 'cancel',
                        'logi_id' => '',
                        'logi_name' => '',
                        'logi_no' => NULL,
                    );
                    
                    $dlyObj->save($tmpdly);
                }
               
                $split_result = $oDelivery->splitDelivery($delivery_id,(array)$delivery_order['delivery_id']);
                if (!$split_result) {
                    $rs['rsp'] = 'fail';
                    $rs['msg'] = '可能WMS订单不存在!';
                }
                
            }
            
        }
        return $rs;
    }

    function order_detail($order_id){
        $order_detail = $this->dump($order_id);
        return $order_detail;
    }
    /*
     * 设置订单异常，并保存异常类型和备注
     *
     * @param array $data abnormal对象的sdf结构数据
     * @param string $log_memo 日志备注
     *
     */
    function set_abnormal($data){
        //组织 分派的数组 $data_dispatch 跟filter(跟dispatch 的参数形式保持一致)

        $data_dispatch = array(
            'group_id' =>$data['group_id'],
            'op_id' =>$data['op_id'],
            'dt_begin' =>time(),
            'dispatch_time' =>time(),
        );
        //组织 set_abnormal的数组
        $data = array(
            'abnormal_id'=>$data['abnormal_id'],
            'order_id'=>$data['order_id'],
            'is_done'=>$data['is_done'],
            'abnormal_memo'=>$data['abnormal_memo'],
            'abnormal_type_id' => $data['abnormal_type_id']
        );

        
        $memo = "";
        $oAbnormal = &$this->app->model('abnormal');
        #订单异常名称
        //echo $data['abnormal_type_id'];exit;
        $type_name = &$this->app->model('abnormal_type')->dump(array('type_id'=>$data['abnormal_type_id']),'type_name');
        $data['abnormal_type_name'] = $type_name['type_name'];

        //备注信息
        $oldmemo = $oAbnormal->dump(array('abnormal_id'=>$data['abnormal_id']), 'abnormal_memo');
        $oldmemo= unserialize($oldmemo['abnormal_memo']);
        if ($oldmemo)
        foreach($oldmemo as $k=>$v){
            $memo[] = $v;
        }
        $op_name = kernel::single('desktop_user')->get_name();
        $newmemo =  htmlspecialchars($data['abnormal_memo']);
        $memo[] = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$newmemo);
        $data['abnormal_memo'] = serialize($memo);
        $oAbnormal->save($data);

        switch ($data['is_done']){
            case 'false':
                $order_data = array('order_id'=>$data['order_id'],'abnormal'=>'true');
                $this->save($order_data);
                $memo = "设置订单异常";
                break;
            case 'true' :
                $order = $this->dump($data['order_id']);
                if ($order['ship_status'] == 2){
                    $filter = array(
                        'order_id'=>$data['order_id'],
                        'abnormal'=>'false',
                        'confirm'=>'Y',
                        'process_status'=>'splitting',
                        //'dispatch_time'=>0, #部分发货_保留分派时间 
                        'print_finish'=>'false'
                    );
                }elseif ($order['process_status'] == 'cancel'){
                    $filter = array(
                        'order_id'=>$data['order_id'],
                        'abnormal'=>'false',
                        'confirm'=>'N',
                        'group_id'=>NULL,
                        'op_id'=>NULL,
                        'dispatch_time'=>0,
                        'print_finish'=>'false'
                    );
                }
                #[拆单]部分拆分OR已拆分完_部分退货订单异常处理 ExBOY
                elseif(($order['process_status'] == 'splitting' || $order['process_status'] == 'splited') && $order['ship_status'] == 3)
                {
                    $filter = array(
                        'order_id'=>$data['order_id'],
                        'abnormal'=>'false',
                        'confirm'=>'N',
                        'process_status'=>'unconfirmed',
                        'group_id'=>NULL,
                        'op_id'=>NULL,
                        'dispatch_time'=>0,
                        'print_finish'=>'false'
                    );
                    
                    #获取订单对应的有效发货单
                    $dlyObj         = &app::get('ome')->model('delivery');
                    $get_delivery   = $dlyObj->getDeliverIdByOrderId($data['order_id']);
                    if(!empty($get_delivery))
                    {
                        $filter['process_status']    = 'splitting';
                        unset($filter['group_id'], $filter['op_id'], $filter['dispatch_time']);
                    }
                }else {
                    $filter = array(
                        'order_id'=>$data['order_id'],
                        'abnormal'=>'false',
                        'confirm'=>'N',
                        'process_status'=>'unconfirmed',
                        'group_id'=>NULL,
                        'op_id'=>NULL,
                        'dispatch_time'=>0,
                        'print_finish'=>'false'
                    );
                }
                $order_data = $filter;
                $this->save($order_data);

                $memo = "修改订单异常备注";
                break;
        }

        //写操作日志
        $oOperation_log = &$this->app->model('operation_log');
        $oOperation_log->write_log('order_modify@ome',$data['order_id'],$memo);
    }

    /*
     * 获取订单明细列表
     *
     * @param int $order_id 订单id
     * @param bool $sort 是否要排序，默认不要。排序后的结果会按照普通商品、捆绑商品、赠品、配件等排列
     *
     * @return array
     */
    function getItemList($order_id,$sort=false){
        $order_items = array();

        if($sort){
            $items = $this->dump($order_id,"order_id",array("order_objects"=>array("*")));
            foreach($items['order_objects'] as $k=>$v){
                $order_items[$v['obj_type']][$k] = $v;
                foreach($this->db->select("SELECT *,nums AS quantity FROM sdb_ome_order_items WHERE obj_id=".$v['obj_id']." AND item_type='product' ORDER BY item_type") as $it){
                    $order_items[$v['obj_type']][$k]['order_items'][$it['item_id']] = $it;
                }
                foreach($this->db->select("SELECT *,nums AS quantity FROM sdb_ome_order_items WHERE obj_id=".$v['obj_id']." AND item_type<>'product' ORDER BY item_type") as $it){
                    $order_items[$v['obj_type']][$k]['order_items'][$it['item_id']] = $it;
                }
            }

        }else{
            $items = $this->dump($order_id,"order_id",array("order_objects"=>array("*",array("order_items"=>array("*")))));
            foreach($items['order_objects'] as $oneobj)
            {
                foreach ($oneobj['order_items'] as $objitems)
                $order_items[] = $objitems;
            }
        }
        return $order_items;
    }

    /*
     * 获取订单明细以及明细商品在各个仓库中的库存
     *
     * @param int $order_id
     *
     * @return array
     */
    function getItemBranchStore($order_id){
        $order_items = $this->getItemList($order_id,true);
        $oProduct = &$this->app->model("products");
        $tmp = array();
        if($order_items){
            $oDelivery = &$this->app->model("delivery");
            $branchObj = &$this->app->model("branch");
            $delivBranch = $branchObj->getDelivBranch();
            foreach($order_items as $obj_type=>$object_type){
                foreach($object_type as $obj_id=>$obj){

                    $i = 1;
                    foreach($obj['order_items'] as $k=>$item){
                        $branch_store = $oProduct->get_branch_store($item['product_id']);
                        /* 货品库存 = 发货仓库存+绑定的备货仓库存 */
                        foreach($delivBranch as $branch_id=>$branch){
                            if (array_key_exists($branch_id, $branch_store)) {
                                foreach((array)$branch['bind_conf'] as $bindBid){
                                    $branch_store[$branch_id] += $branch_store[$bindBid];
                                }
                            }
                        }

                        $order_items[$obj_type][$obj_id]['order_items'][$k]['branch_store'] = $branch_store;

                        $sql = "SELECT SUM(number) AS 'num' FROM `sdb_ome_delivery_items_detail` did
                                                    JOIN `sdb_ome_delivery` d
                                                        ON d.delivery_id=did.delivery_id
                                                    WHERE order_item_id='".$item['item_id']."'
                                                        AND product_id='".$item['product_id']."'
                                                        AND d.status != 'back'
                                                        AND d.status != 'cancel' AND d.status!='return_back'
                                                        AND d.is_bind = 'false'";
                        $oi = $this->db->selectrow($sql);

                        $tmpNum = $item['quantity']-intval($oi['num']);
                        $order_items[$obj_type][$obj_id]['left_nums'] = $tmpNum;
                        $order_items[$obj_type][$obj_id]['order_items'][$k]['left_nums'] = $tmpNum;
                        if ($obj_type == 'pkg' || $obj_type == 'giftpackage'){
                            $order_items[$obj_type][$obj_id]['left_nums'] = intval($obj['quantity'] / $item['quantity'] * $tmpNum);
                            $order_items[$obj_type][$obj_id]['sendnum'] = intval($obj['quantity'] / $item['quantity'] * $item['sendnum']);

                            foreach ((array) $branch_store as $bk => $bv) {

                                $bstore = intval($obj['quantity'] / $item['quantity'] * $bv);

                                if ($i==1) {
                                   $order_items[$obj_type][$obj_id]['branch_store'][$bk] = $bstore;
                                } else {
                                    $order_items[$obj_type][$obj_id]['branch_store'][$bk] = min(intval($order_items[$obj_type][$obj_id]['branch_store'][$bk]),$bstore);
                                }
                            }
                        }
                         $i++;
                        // if ($item['quantity'] <= intval($oi['num'])){

                        //     $order_items[$obj_type][$obj_id]['left_nums'] = 0;
                        //     $order_items[$obj_type][$obj_id]['order_items'][$k]['left_nums'] = 0;


                        // } else {
                        //     $tmp_num = $item['quantity']-intval($oi['num']);
                        //     $order_items[$obj_type][$obj_id]['order_items'][$k]['left_nums'] = $tmp_num;
                        //     if ($obj_type == 'pkg' || $obj_type == 'giftpackage'){
                        //         $order_items[$obj_type][$obj_id]['left_nums'] = intval($obj['quantity'] / $item['quantity'] * $tmp_num);
                        //     }
                        // }
                    }

                }
            }
            
            #[拆单]重新计算捆绑商品仓库库存数量  ExBOY
            if(!empty($order_items['pkg']))
            {
                foreach($order_items['pkg'] as $obj_id => $obj_li) 
                {
                    if(!empty($obj_li['branch_store'])) 
                    {
                        foreach ($obj_li['branch_store'] as $brand_id => $branch_num) 
                        {
                            foreach($obj_li['order_items'] as $item_id => $item) 
                            {
                                $order_items['pkg'][$obj_id]['order_items'][$item_id]['branch_store'][$brand_id]    = intval($item['branch_store'][$brand_id]);
                            }
                        }
                    }
                }
                
                foreach($order_items['pkg'] as $obj_id => $obj_li) 
                {
                    if(!empty($obj_li['branch_store'])) 
                    {
                        foreach ($obj_li['branch_store'] as $brand_id => $branch_num) 
                        {
                            foreach($obj_li['order_items'] as $item_id => $item) 
                            {
                                $get_branch_num     = intval($order_items['pkg'][$obj_id]['branch_store'][$brand_id]);
                                $branch_store_num   = intval($item['branch_store'][$brand_id]);
                                
                                $order_items['pkg'][$obj_id]['branch_store'][$brand_id]     = min($get_branch_num, $branch_store_num);
                            }
                        }
                    }
                }
            }
        }

        return $order_items;
    }

    function getItemsNum($order_id, $product_id){
        $sql = "SELECT SUM(nums) AS '_count' FROM sdb_ome_order_items WHERE order_id='".$order_id."' AND product_id='".$product_id."'";
        $row = $this->db->selectrow($sql);
        return $row['_count'];
    }

    /*
     * 获取本订单的order_object的对象别名
     *
     * @param bigint $order_id
     *
     * @return array
     */
    function getOrderObjectAlias($order_id){
        $ret = array();
        $order_object = $this->db->select("SELECT DISTINCT(obj_type),obj_alias FROM sdb_ome_order_objects WHERE order_id={$order_id} ORDER BY obj_type");
        foreach($order_object as $v){
            $ret[$v['obj_type']] = $v['obj_alias'];
        }

        return $ret;
    }

    /*
     * 获取订单商品可能会使用到的仓库
     *
     * @param int $order_id
     *
     * @return array
     */
    function getBranchByOrder($order_id){
        $order_id = '('.implode(',',$order_id).')';
        $branch = $this->db->select("SELECT distinct(b.branch_id),b.name,b.uname,b.phone,b.mobile,b.stock_threshold,b.weight FROM sdb_ome_branch AS b
                                           LEFT JOIN sdb_ome_branch_product AS bp ON(b.branch_id=bp.branch_id)
                                           LEFT JOIN sdb_ome_order_items AS oi ON(bp.product_id=oi.product_id)
                                           WHERE b.disabled='false' AND b.is_deliv_branch='true' AND oi.order_id in {$order_id} ORDER BY b.branch_id");
                                           

        return $branch;
    }

     /*
     * 生成订单号
     */
    function gen_id() {
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $order_bn = date('YmdH').'10'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('SELECT order_id from sdb_ome_orders where order_bn =\''.$order_bn.'\'');
        }while($row);
        return $order_bn;
    }

    /*
     * 订单确认操作
     *
     * @param bigint $order_id 订单id
     * @param array $ship_info 收货人信息
     *
     * @return bool
     */
    function confirm($order_id,$is_auto=false){
        if($order=$this->dump($order_id,"*")){
            if($order['confirm']=='Y' || $order['process_status'] == 'cancel'){
                return false;
            }
        }

        $data['order_id'] = $order_id;
        $data['process_status'] = 'confirmed';
        $data['confirm'] = 'Y';

        $this->save($data);
        $oOperation_log = &$this->app->model('operation_log');
        $opinfo = NULL;
        if ($is_auto) $opinfo = kernel::single('ome_func')->get_system();
        $oOperation_log->write_log('order_confirm@ome',$data['order_id'],"订单确认",NULL,$opinfo);

        return true;
    }

    /*
     * 拆分订单，生成发货单
     *
     * @param bigint $order_id
     */
    function mkDelivery($order_id,$delivery_info){
        $oDelivery = &$this->app->model("delivery");
        $dly_orderObj =  &$this->app->model("delivery_order");
        $delivery_itemObj = &$this->app->model("delivery_items");
        $order_itemObj = &$this->app->model("order_items");
        if(is_array($delivery_info) && count($delivery_info)>0){
            $ids = array();
            foreach($delivery_info as $delivery){
                $tmp_items = $delivery['order_items'];
                unset($delivery['order_items']);
                $ids[] = $oDelivery->addDelivery($order_id,$delivery,array(),$tmp_items);
            }
            //根据orderid找到delivery
            $dly_orderdata = $dly_orderObj->getList("*",array("order_id"=>$order_id));
            $dlyitemcount = 0;
            foreach ($dly_orderdata as $dly_order){
                $sql = "SELECT SUM(di.number) AS 'total' FROM sdb_ome_delivery_items di
                                        JOIN sdb_ome_delivery d
                                            ON di.delivery_id=d.delivery_id
                                        WHERE d.delivery_id ='".$dly_order['delivery_id']."'
                                        AND d.status != 'back'
                                        AND d.status != 'cancel'
                                        AND d.disabled = 'false'
                                        AND d.is_bind = 'false' AND d.status!='return_back'";

                $row = $this->db->selectrow($sql);
                $dlyitemcount += empty($row)?0:$row['total'];
            }
            $orderitemcount = 0;
            $orderitems = $order_itemObj->getList("*",array("order_id"=>$order_id));
            foreach ($orderitems as $oneitem){
                if ($oneitem['delete'] == 'false') $orderitemcount += $oneitem['nums'];
            }
            //如果delivery_item数量与order_item数量相等，则拆分完，否则部分拆分
            if ($orderitemcount <= $dlyitemcount)
            {
                $data['order_id'] = $order_id;
                $data['process_status'] = 'splited';
                $this->save($data);
            }
            else
            {
                $data['order_id'] = $order_id;
                $data['process_status'] = 'splitting';
                $this->save($data);
            }
            $oOperation_log = &$this->app->model('operation_log');
            $oOperation_log->write_log('order_split@ome',$data['order_id'],"订单拆分");
            return $ids;
        }
    }

   /*
     * 快速查找订单信息
     */
    public function getOrders($name=null)
    {
        $sql = " SELECT order_id,order_bn FROM `sdb_ome_orders`
        WHERE ship_status = '1' and order_bn regexp '".$name."'";
        $data = &$this->db->select($sql);
        $result = array();
        if ($data)
        foreach ($data as $v){
            $result[] = $v;
        }
        return $result;
    }


     /*根据过滤条件查询数据*/
     function getOrderId($finderResult){
        $where = $finderResult? $this->_filter($finderResult):'order_id in ('.implode(',',$finderResult['order_id']).')';
        $sql = 'select order_id  from  sdb_ome_orders   where '.$where;
        return $this->db->select($sql);
     }

     /*
      * 订单详情查询
      * @param order_bn string
      * @return array
      */
     function getOrderBybn($filter=null, $cols='*', $lim=0, $limit=1){
       $sql = 'select '.$cols.' FROM `sdb_ome_orders` ';
       $whereSql = '';
       $limitSql = '';
       if ($filter) $whereSql .= " WHERE ".$filter;
       $limitSql .= " limit $lim,$limit ";
       $rows =  $this->db->select($sql . $whereSql . $limitSql);

       $selectField = " SELECT count(*) as counts FROM (".$sql.$whereSql.") c";
       $count = $this->db->select($selectField);
       $rows['count'] = $count[0]['counts'];

       return $rows;
     }

    /*
     * 获取订单上下条
     * getOrderUpNext
     */
    function getOrderUpNext($id=null,$filter=null, $type='>'){
        if (!$id) return;
        $sql = "SELECT order_id,order_bn FROM `sdb_ome_orders` WHERE order_id $type '$id' ";
        $sql .= $filter;
        if ($type=='<') $desc = "desc";
        $sql .= " ORDER BY order_id $desc ";
        $tmp = $this->db->selectRow($sql);
        return $tmp;
    }

    /* create_order 订单创建
     * @param sdf $sdf
     * @return sdf
     */
    function create_order(&$sdf){
        //判断订单号是否重复
        if($this->dump(array('order_bn'=>$sdf['order_bn'],'shop_id'=>$sdf['shop_id']))){
            return false;
        }

        $regionLib = kernel::single('eccommon_regions');
        //收货人/发货人地区转换
        $area = $sdf['consignee']['area'];
        $regionLib->region_validate($area);
        $sdf['consignee']['area'] = $area;
        $consigner_area = $sdf['consigner']['area'];
        $regionLib->region_validate($consigner_area);
        $sdf['consigner']['area'] = $consigner_area;

        $oProducts = &$this->app->model('products');

        //如果有OME捆绑插件设定的捆绑商品，则自动拆分
        if($oPkg = kernel::service('omepkg_order_split')){
            if(method_exists($oPkg,'order_split')){
                $sdf = $oPkg->order_split($sdf);
            }
        }

        //去除货号空格
         foreach($sdf['order_objects'] as $key=>$object){
            $object['bn'] = trim($object['bn']);
            foreach($object['order_items'] as $k=>$item){
                $item['bn'] = trim($item['bn']);
                $object['order_items'][$k] = $item;
            }
            $sdf['order_objects'][$key] = $object;
         }

        foreach($sdf['order_objects'] as $key=>$object){
            foreach($object['order_items'] as $k=>$item){
                //货品属性
                $product_attr = array();
                $product_attr = $this->_format_productattr($item['product_attr'], $item['product_id'],$item['original_str']);
                $sdf['order_objects'][$key]['order_items'][$k]['addon'] = $product_attr;
                //danny_freeze_stock_log
                $GLOBALS['frst_shop_id'] = $sdf['shop_id'];
                $GLOBALS['frst_shop_type'] = $sdf['shop_type'];
                $GLOBALS['frst_order_bn'] = $sdf['order_bn'];
                //修改预占库存
                $oProducts->chg_product_store_freeze($item['product_id'],(intval($item['quantity'])-intval($item['sendnum'])),"+");
            }
        }

        if(app::get('replacesku')->is_installed()){
            $sku_tran = new replacesku_order;
            $taotrans_sku = $sku_tran->order_sku_filter($sdf['order_objects']);
            if(count($taotrans_sku)>=1){
                $sdf['is_fail'] = 'true';
                $sdf['auto_status'] =1;
            }

        }
        //注册service来对订单结构数据进行扩充和修改
        if($order_sdf_service = kernel::service('ome.service.order.sdfdata')){
            if(method_exists($order_sdf_service,'modify_sdfdata')){
                $sdf = $order_sdf_service->modify_sdfdata($sdf);
            }
        }
//echo "<pre>";print_r($sdf);exit();
        if(!$this->save($sdf)) return false;
		
		if($sdf['order_pmt']['0']['pmt_describe']!=""){
			$objOpmt=kernel::single("ome_mdl_order_pmt");
			foreach($sdf['order_pmt'] as $youhui){
				$arrOrderPmt['id']='';
				$arrOrderPmt['order_id']=$sdf['order_id'];
				$arrOrderPmt['pmt_amount']=$youhui['pmt_amount'];
				$arrOrderPmt['pmt_describe']=$youhui['pmt_describe'];
				// echo "<pre>"; print_r($arrOrderPmt);print_r($city2);
				if(!$objOpmt->save($arrOrderPmt)){
					return false;
				}
			}
		}
//exit();
        $c2c_shop_list = ome_shop_type::shop_list();
               
        //积分兑礼订单不直接生成支付单
        if($sdf['is_creditOrder']!='1'){
            if( !in_array($sdf['shop_type'], $c2c_shop_list) && (bccomp('0.000', ($sdf['total_amount']/1),3) == 0) ){ #0元订单是否需要财审.
                kernel::single('ome_order_order')->order_pay_confirm($sdf['shop_id'],$sdf['order_id'],$sdf['total_amount']);
            }
        }
        

        //增加订单创建日志
        $logObj = &app::get('ome')->model('operation_log');
        $logObj->write_log('order_create@ome',$sdf['order_id'],'订单创建成功');

        //创建订单后执行的操作
        if($oServiceOrder = kernel::servicelist('ome_create_order_after')){
              foreach($oServiceOrder as $object){
                  if(method_exists($object,'create_order_after')){
                      $object->create_order_after($sdf);
                   }
             }
        }

        //如果有KPI考核插件，会增加客服的考核
        if($oKpi = kernel::service('omekpi_servicer_incremental')){
            if(method_exists($oKpi,'getOrderIncremental')){
                $oKpi->getOrderIncremental($sdf);
            }
        }

        //订单创建api
        foreach(kernel::servicelist('service.order') as $object){
            if(method_exists($object, 'create_order')){
                $object->create_order($sdf);
            }
        }
        
        /*------------------------------------------------------ */
        //-- [复审]创建订单后执行"价格监控" ExBOY
        /*------------------------------------------------------ */
        $oRetrial   = &app::get('ome')->model('order_retrial');
        $retrial    = $oRetrial->order_monitor($sdf);
        
        /*------------------------------------------------------ */
        //-- [拆单]保存_淘宝平台_的原始属性值 ExBOY
        /*------------------------------------------------------ */
        $this->hold_order_delivery($sdf);
        
        //-- 系统自动审单[订单必须已支付OR货到付款] ExBOY
        if(($sdf['pay_status'] == '1' || $sdf['shipping']['is_cod'] == 'true') && $sdf['status'] == 'active')
        {
            $this->auto_order_consign($sdf['order_id']);
        }
        
        return true;
    }

    /**
     * 将前端店铺过来的货品规格属性值序列化
     * @access public
     * @param array $productattr 货品属性值
     * @return serialize 货品属性值
     */
    public function _format_productattr($productattr='',$product_id='',$original_str=''){
        if (!is_array($productattr) || empty($productattr)){
            $oSpecvalue = &$this->app->model('spec_values');
            $oSpec = &$this->app->model('specification');
            $oProducts = &$this->app->model('products');
            $productattr = array();
            $product_info = $oProducts->dump(array('product_id'=>$product_id),"spec_desc");
            $spec_desc = $product_info['spec_desc'];
            if ($spec_desc['spec_value_id'])
            foreach ($spec_desc['spec_value_id'] as $sk=>$sv){
                $tmp = array();
                $specval = $oSpecvalue->dump($sv,"spec_value,spec_id");
                //$tmp['value'] = $specval['spec_value'];
                $tmp['value'] = $spec_desc['spec_value'][$sk];
                $spec = $oSpec->dump($specval['spec_id'],"spec_name");
                $tmp['label'] = $spec['spec_name'];
                $productattr[] = $tmp;
            }
        }else{
            $productattr[0]['original_str'] = $original_str;//原始商品属性值
        }
        $addon['product_attr'] = $productattr;
        return serialize($addon);
    }


     function save(&$data,$mustUpdate = null){
         //外键 先执行save
        $this->_save_parent($data,$mustUpdate);
        $plainData = $this->sdf_to_plain($data);
        if(!$this->db_save($plainData,$mustUpdate )) return false;

        $order_id = $plainData['order_id'];
        if(isset($data['order_objects'])){
            foreach($data['order_objects'] as $k=>$v){
                if(isset($v['order_items'])){
                    foreach($v['order_items'] as $k2=>$item){
                        $data['order_objects'][$k]['order_items'][$k2]['order_id'] = $order_id;
                    }
                }else{
                    break;
                }
            }
        }

        if( !is_array($this->idColumn) ){
            $data[$this->idColumn] = $plainData[$this->idColumn];
            $this->_save_depends($data,$mustUpdate );
        }
        $plainData = null; //内存用完就放
        return true;
     }

     /**
     * 取消订单
     * @access public
     * @param Number $order_id 订单ID
     * @param String $memo 取消备注
     * @param Bool $is_request 是否询问请求
     * @param string $mode 请求类型:sync同步  async异步
     * @return Array
     */
     function cancel($order_id,$memo,$is_request=true,$mode='sync'){
        $rs = array('rsp'=>'fail','msg'=>'');

        //订单取消 API
        $instance = kernel::service('service.order');

        if($is_request == true && $instance && method_exists($instance, 'update_order_status')){
            $rs = $instance->update_order_status($order_id, 'dead', $memo, $mode);
        }

        if($mode == 'async'){#异步默认状态先置为成功。
            $rs['rsp'] = 'succ';
        }
        $rs['rsp'] = ($rs['rsp'] == 'succ')?'success':'fail';

        if ($mode == 'async' || $rs['rsp'] == 'success'){
            $oOperation_log = &$this->app->model('operation_log');
            $result = $this->cancel_delivery($order_id);
            if ($result['rsp'] == 'succ') {
                $savedata = array();
                $savedata['order_id'] = $order_id;
                $savedata['process_status'] = 'cancel';
                $savedata['status'] = 'dead';
                $savedata['archive'] = 1;//订单归档
                $this->save($savedata);

                //TODO: 订单取消作为单独的日志记录

                ### 订单状态回传kafka august.yao 已取消 start ###
                $orderRes   = $this->dump($order_id);
                $kafkaQueue = app::get('ome')->model('kafka_queue');
                $queueData = array(
                    'queue_title' => '订单已取消状态推送',
                    'worker'      => 'ome_kafka_api.sendOrderStatus',
                    'start_time'  => time(),
                    'params'      => array(
                        'status'   => 'cancel',
                        'order_bn' => $orderRes['order_bn'],
                        'logi_bn'  => '',
                        'shop_id'  => $orderRes['shop_id'],
                        'item_info'=> array(),
                        'bill_info'=> array(),
                    ),
                );
                $kafkaQueue->save($queueData);
                ### 订单状态回传kafka august.yao 已取消 end ###
                
                $this->unfreez($order_id);
                $oOperation_log->write_log('order_modify@ome',$order_id,$memo);
            }else{
              $rs['rsp'] = 'fail';
              $rs['msg']=$result['msg'] ? $result['msg'] : '发货单取消失败';
            }
        }

        return $rs;
     }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'product_bn'=>app::get('ome')->_('货号'),
            'product_barcode'=>app::get('ome')->_('条形码'),
            'member_uname'=>app::get('ome')->_('注册人手机'),
            'ship_tel_mobile'=>app::get('ome')->_('收货人电话'),
            'logi_no'=>app::get('ome')->_('物流单号')
        );
        return $Options = array_merge($parentOptions,$childOptions);
    }

    /*
     * $order_ids id数组
     */
    function dispatch($data,$filter,$order_ids,$is_auto=false){
        $data['is_auto'] = 'false';//手动分派，改变自动处理标示
        if(empty($data['op_id'])){
            $data['op_id'] = 0;
        }
        $this->update($data,$filter);
        //写日志
        $oOperation_log = &$this->app->model('operation_log');
        $oGroup = &$this->app->model('groups');
        $oOperator = &app::get('desktop')->model('users');
        $memo = "";

        if($data['group_id']){
            $group = $oGroup->dump(intval($data['group_id']));
            $memo = '订单分派给'.$group['name'];

            if($data['op_id']){
                $operator = $oOperator->dump(intval($data['op_id']));
                $memo .= '的'.$operator['name'];
            }
        }else{
            $memo = "订单撤销分派";
        }

        if($order_ids[0] == '_ALL_'){
            $opinfo = NULL;
            if ($is_auto) $opinfo = kernel::single('ome_func')->get_system();
            unset($filter['filter_sql']);
            $oOperation_log->batch_write_log('order_dispatch@ome',$memo,time(),$filter,$opinfo);
        }else{
            foreach($order_ids as $order_id){
                $opinfo = NULL;
                if ($is_auto){
                    $opinfo = kernel::single('ome_func')->get_system();
                }
                $oOperation_log->write_log('order_dispatch@ome',$order_id,$memo,NULL,$opinfo);
            }
        }


         //创建订单后执行的操作
        if($data['group_id'] && $oServiceOrder = kernel::servicelist('ome_dispatch_after')){
            if($order_ids[0] == '_ALL_'){
                $order_ids = array();
                $rows = $this->getList("order_id",$filter,0,-1);
                foreach($rows as $v){
                    $order_ids[] = $v['order_id'];
                }
            }
            foreach($order_ids as $v){
                 foreach($oServiceOrder as $object){
                    if(method_exists($object,'dispatch_after')){
                        $object->dispatch_after($v);
                     }
                 }
            }
        }
        return true;
    }

    /*
     * 订单退回
     * $order_ids id数组
     *
     */
    function goback($data,$filter,$remark,$act){
        $this->update($data,$filter);
        //写日志
        $oOperation_log = &$this->app->model('operation_log');
        $memo = "";
        $op_name = kernel::single('desktop_user')->get_name();
        $memo = $op_name.$act.'，原因：'.$remark;
        $oOperation_log->write_log('order_dispatch@ome',$filter['order_id'],$memo,NULL,NULL);
        return true;
    }

    function exportTemplate($filter){
        foreach ($this->io_title($filter) as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }

     function io_title( $filter=null,$ioType='csv' ){
        switch( $ioType ){
            case 'csv':
            default:
                $this->oSchema['csv']['order'] = array(
                    '*:订单号' => 'order_bn',
                    '*:支付方式' => 'payinfo/pay_name',
                    '*:下单时间' => 'createtime',
                    '*:付款时间' => 'paytime',
                    '*:配送方式' => 'shipping/shipping_name',
                    '*:配送费用' => 'shipping/cost_shipping',
                    '*:来源店铺编号' => 'shop_id',
                    '*:来源店铺' => 'shop_name',
                    '*:订单附言' => 'custom_mark',
                    '*:收货人姓名' => 'consignee/name',
                    '*:收货地址省份' => 'consignee/area/province',
                    '*:收货地址城市' => 'consignee/area/city',
                    '*:收货地址区/县' => 'consignee/area/county',
                    '*:收货详细地址' => 'consignee/addr',
                    '*:收货人固定电话' => 'consignee/telephone',
                    '*:电子邮箱' => 'consignee/email',
                    '*:收货人移动电话' => 'consignee/mobile',
                    '*:邮编' => 'consignee/zip',
                    '*:货到付款' => 'shipping/is_cod',
                    '*:是否开发票' => 'is_tax',
                    '*:发票抬头' => 'tax_title',
                    '*:发票金额' => 'cost_tax',
                    '*:优惠方案' => 'order_pmt',
                    '*:订单优惠金额' => 'pmt_order',
                    '*:商品优惠金额' => 'pmt_goods',
                    '*:折扣' => 'discount',
                    '*:返点积分' => 'score_g',
                    '*:商品总额' => 'cost_item',
                    '*:订单总额' => 'total_amount',
                    '*:买家会员名' => 'account/uname',
                    '*:订单类型' => 'order_source',
                    '*:订单备注' => 'mark_text',
                    '*:商品重量' =>'weight',
                    '*:发票号'=>'tax_no',
                    '*:发票抬头'=>'tax_title',
                    '*:周期购'=>'createway',
                    '*:关联订单号'=>'relate_order_bn',
                );
                $this->oSchema['csv']['obj'] = array(
                    '*:订单号' => '',
                    '*:商品货号' => '',
                    '*:商品名称' => '',
                    '*:购买单位' => '',
                    '*:商品规格' => '',
                    '*:购买数量' => '',
                    '*:商品原价' => '',
                    '*:销售价' =>'',
                    '*:商品优惠金额' => '',
                    '*:商品类型' => '',
                    '*:商品品牌' => '',
                );
                break;
        }
        #新增导出字段
        if($this->export_flag){
            $title = array(
                        '*:发货状态'=>'ship_status',
                        '*:付款状态'=>'pay_status'
                    );
            $this->oSchema['csv']['order'] = array_merge($this->oSchema['csv']['order'],$title);
        }
        #导出模板时，将不需要的字段从这里清除
        if(!$this->export_flag){
            unset($this->oSchema['csv']['order']['*:来源店铺']);
        }
        $this->ioTitle[$ioType]['order'] = array_keys( $this->oSchema[$ioType]['order'] );
        $this->ioTitle[$ioType]['obj'] = array_keys( $this->oSchema[$ioType]['obj'] );
        return $this->ioTitle[$ioType][$filter];
     }

    /**
     * 统计导出数据
     *
     * @param Array $filter 过滤条件
     * @return void
     * @author 
     **/
    public function fcount_csv($filter)
    {
        $count = $this->count($filter);
        if ($count < 500 && $count > 0) {
            $orderidList = array();

            $orderList = $this->getList('order_id',$filter);
            foreach ($orderList as $order) {
                $orderidList[] = $order['order_id'];
            }

            if ($orderidList) {
                $orderItemModel = app::get('ome')->model('order_items');
                $itemCount = $orderItemModel->count(array('order_id'=>$orderidList));

                if ($itemCount > 2500) {
                    $count = 600;
                }
            }
        }

        return $count;
    }

     //csv导出
     function fgetlist_csv( &$data,$filter,$offset,$exportType = 1 ){
        @ini_set('memory_limit','1024M'); set_time_limit(0); 
        
        $this->export_flag = true;
        $max_offset = 1000; // 最多一次导出10w条记录
        if ($offset>$max_offset) return false;// 限制导出的最大页码数

        if( !$data['title']['order'] ){
            $title = array();
            foreach( $this->io_title('order') as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['order'] = '"'.implode('","',$title).'"';
        }
        if( !$data['title']['obj'] ){
            $title = array();
            foreach( $this->io_title('obj') as $k => $v )
                $title[] = $this->charset->utf2local($v);
            $data['title']['obj'] = '"'.implode('","',$title).'"';
        }
        $limit = 100;

        if( !$list=$this->getList('order_id',$filter,$offset*$limit,$limit) )return false;
        foreach( $list as $aFilter ){
            $aOrder = $this->dump($aFilter['order_id']);
            if($aOrder['order_bn']){
                $aOrder['order_bn'] = "=\"\"".$aOrder['order_bn']."\"\"";//"\t".$aOrder['order_bn'];#解决订单号科学计数法的问题
            }
            if( !$aOrder )continue;
            $objects = $this->db->select("SELECT * FROM sdb_ome_order_objects WHERE order_id=".$aFilter['order_id']);

            if ($objects){
                foreach ($objects as $obj){
                    if ($service = kernel::service("ome.service.order.objtype.".strtolower($obj['obj_type']))){
                        $item_data = $service->process($obj);
                        if ($item_data)
                        foreach ($item_data as $itemv){
                            $orderObjRow = array();
                            $orderObjRow['*:订单号']   = $aOrder['order_bn'];
                            $orderObjRow['*:商品货号'] = "\t".$itemv['bn'];
                            $orderObjRow['*:商品名称'] = "\t".str_replace("\n"," ",$itemv['name']);
                            $orderObjRow['*:购买单位'] = $itemv['unit'];
                            $orderObjRow['*:商品规格'] = $itemv['spec_info'] ? str_replace("\n"," ",$itemv['spec_info']):"-";
                            $orderObjRow['*:购买数量'] = $itemv['nums'];
                            $orderObjRow['*:商品原价'] = $itemv['price'];
                            $orderObjRow['*:销售价'] = $itemv['sale_price'] / $itemv['nums'];
                            $orderObjRow['*:商品优惠金额'] = $itemv['pmt_price'];

                            $data['content']['obj'][] = $this->charset->utf2local('"'.implode( '","', $orderObjRow ).'"');
                        }
                    }else {
                        $aOrder['order_items'] = $this->db->select("SELECT * FROM sdb_ome_order_items WHERE obj_id=".$obj['obj_id']." AND `delete`='false' AND order_id=".$aFilter['order_id']);
                        $aOrder['order_items'] = ome_order_func::add_items_colum($aOrder['order_items']);
                        $orderRow = array();
                        $orderObjRow = array();
                        $k = 0;
                        if ($aOrder['order_items'])
                        foreach( $aOrder['order_items'] as $itemk => $itemv ){
                            $addon = unserialize($itemv['addon']);
                            $spec_info = null;
                            if(!empty($addon)){
                                foreach($addon as $val){
                                    foreach ($val as $v){
                                        $spec_info[] = $v['value'];
                                    }
                                }
                            }
                            $_typeName = $this->getTypeName($itemv['product_id']);
                            $orderObjRow = array();
                            $orderObjRow['*:订单号']   = $aOrder['order_bn'];
                            $orderObjRow['*:商品货号'] = "\t".$itemv['bn'];
                            $orderObjRow['*:商品名称'] = "\t".str_replace("\n"," ",$itemv['name']);
                            $orderObjRow['*:购买单位'] = $itemv['unit'];
                            $orderObjRow['*:商品规格'] = $spec_info?implode('||', $spec_info):'-';//$itemv['spec_info'] ? str_replace("\n"," ",$itemv['spec_info']):"-";
                            $orderObjRow['*:购买数量'] = $itemv['nums'];
                            $orderObjRow['*:商品原价'] = $itemv['price'];
                            $orderObjRow['*:销售价'] = $itemv['sale_price'] / $itemv['nums'];
                            $orderObjRow['*:商品优惠金额'] = $itemv['pmt_price'];
                            $orderObjRow['*:商品类型'] = $_typeName['type_name'];
                            $orderObjRow['*:商品品牌'] = $_typeName['brand_name'];
                            

                            $data['content']['obj'][] = $this->charset->utf2local('"'.implode( '","', $orderObjRow ).'"');
                        }
                    }
                }
            }

            //处理地区数据
            $area = explode('/',$aOrder['consignee']['area'] );
            if(strpos($area[0],":")){
                $tmp_province = explode(":",$area[0]);
                $province = $tmp_province[1];
            }else{
                $province = $area[0];
            }
            #付款状态
            switch($aOrder['pay_status']){
                case 0:
                    $aOrder['pay_status'] = '未支付';
                    break;
                case 1:
                    $aOrder['pay_status'] = '已支付';
                    break;
                case 2:
                    $aOrder['pay_status'] = '处理中';
                    break;
                case 3:
                    $aOrder['pay_status'] = '部分付款';
                    break;
                case 4:
                    $aOrder['pay_status'] = '部分退款';
                    break;
                case 5:
                    $aOrder['pay_status'] = '全额退款';
                    break;
                case 6:
                    $aOrder['pay_status'] = '退款申请中';
                    break;
                case 7:
                    $aOrder['pay_status'] = '退款中';
                    break;
                case 8:
                    $aOrder['pay_status'] = '支付中';
                    break;
            }
            #发货状态
            switch($aOrder['ship_status']){
                case 0:
                    $aOrder['ship_status'] = '未发货';
                    break;
                case 1:
                    $aOrder['ship_status'] = '已发货';
                    break;
                case 2:
                    $aOrder['ship_status'] = '部分发货';
                    break;
                case 3:
                    $aOrder['ship_status'] = '部分退货';
                    break;
                case 4:
                    $aOrder['ship_status'] = '已退货';
                    break;
            }
            $city = $area[1];
            if(strpos($area[2],":")){
                $tmp_county = explode(":",$area[2]);
                $county = $tmp_county[0];
            }else{
                $county = $area[2];
            }
            $aOrder['consignee']['area'] = array(
                'province' => $province,
                'city' => $city,
                'county' => $county,
            );

            $tmp_remark = kernel::single('ome_func')->format_memo($aOrder['custom_mark']);
            $tmp = '';
            if ($tmp_remark)
            foreach ($tmp_remark as $v){
                $tmp .= $v['op_content'].'-'.$v['op_time'].'-by-'.$v['op_name'].';';
            }
            $aOrder['custom_mark'] = str_replace("\n"," ",$tmp);
            //订单备注
            $tmp_mark_text = kernel::single('ome_func')->format_memo($aOrder['mark_text']);
            $tmp_mark = '';
            if ($tmp_mark_text) {
                foreach ($tmp_mark_text as $tv) {
                    $tmp_mark.=$tv['op_content'].'-'.$tv['op_time'].'-by-'.$tv['op_name'].';';
                }
            }
            $aOrder['mark_text'] = str_replace("\n"," ",$tmp_mark);
            $aOrder['consignee']['addr'] = str_replace("\n"," ",$aOrder['consignee']['addr']);
            //处理店铺信息
            $shop = $this->app->model('shop')->dump($aOrder['shop_id']);
            $aOrder['shop_id'] = $shop['shop_bn'];
            $aOrder['shop_name'] = $shop['name'];
            $aOrder['createtime'] = date('Y-m-d H:i:s',$aOrder['createtime']);
            $aOrder['paytime'] = $aOrder['paytime'] ? date('Y-m-d H:i:s',$aOrder['paytime']) : '尚未付款';

            $member = $this->app->model('members')->dump($aOrder['member_id']);

            #订单类型
            $aOrder['order_source'] = ome_order_func::get_order_source($aOrder['order_source']);

            $aOrder['account']['uname'] = $member['account']['uname'];
            $aOrder['shipping']['is_cod'] = $aOrder['shipping']['is_cod'] == 'true' ? '是':'否';
            $aOrder['is_tax'] = $aOrder['is_tax'] == 'true' ? '是':'否';            #会员邮箱
            $aOrder['consignee']['email'] = $member['contact']['email'];
            //处理订单优惠方案
            $order_pmtObj = $this->app->model('order_pmt');
            $pmt = $order_pmtObj->getList('pmt_describe',array('order_id'=>$aOrder['order_id']));
            foreach($pmt as $k=>$v){
                $pmt_tmp .= $v['pmt_describe'].";";
            }
            $aOrder['order_pmt']  = $pmt_tmp;
            $aOrder['createway']  = '';
             $aOrder['relate_order_bn'] = "=\"\"".$aOrder['relate_order_bn']."\"\"";
            unset($pmt_tmp);
            foreach( $this->oSchema['csv']['order'] as $k => $v ){
                $orderRow[$k] = $this->charset->utf2local(utils::apath( $aOrder,explode('/',$v) ));
            }
            $data['content']['order'][] = '"'.implode('","',$orderRow).'"';
        }
        return true;
    }

    function export_csv($data,$exportType = 1 ){
        $output = array();
      //  if( $exportType == 2 ){
            foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
      //  }
        echo implode("\n",$output);
    }

    function prepared_import_csv(){
        $this->ioObj->cacheTime = time();
    }

    function finish_import_csv(){
        header("Content-type: text/html; charset=utf-8");
        $data = $this->import_data;
        unset($this->import_data);
        $orderTitle = array_flip( $this->io_title('order') );
        $objTitle = array_flip( $this->io_title('obj') );
        $orderSchema = $this->oSchema['csv']['order'];
        $objSchema =$this->oSchema['csv']['obj'];
        $oQueue = &app::get('base')->model('queue');

        $count = 0;
        $limit = 50;
        $page = 0;
        $orderSdfs = array();
        foreach( $data as $ordre_id => $aOrder ){
            $orderSdf = array();
            $orderSdf = $this->ioObj->csv2sdf( $aOrder['order']['contents'][0] ,$orderTitle,$orderSchema  );
            $orderObjectItem = 0;
            foreach( $aOrder['obj']['contents'] as $k => $v ){
                    $product_info = $this->app->model('products')->dump(array('bn'=>$v[$objTitle['*:商品货号']]));
                    if (!$product_info){
                        foreach(kernel::servicelist('ome.product') as $name=>$object){
                            if(method_exists($object, 'getProductInfoByBn')){
                                $product_data = $object->getProductInfoByBn($v[$objTitle['*:商品货号']]);
                                if($product_data){
                                    $orderSdf['order_objects'][$k]['bn']        = $v[$objTitle['*:商品货号']];
                                    $orderSdf['order_objects'][$k]['name']      = $v[$objTitle['*:商品名称']];
                                    $orderSdf['order_objects'][$k]['quantity']  = $v[$objTitle['*:购买数量']];
                                    $orderSdf['order_objects'][$k]['price']     = $v[$objTitle['*:商品原价']];
                                    $orderSdf['order_objects'][$k]['amount']    = $v[$objTitle['*:商品原价']]*$v[$objTitle['*:购买数量']];
                                    $orderSdf['order_objects'][$k]['sale_price']  = $v[$objTitle['*:销售价']]*$v[$objTitle['*:购买数量']];
                                    $orderSdf['order_objects'][$k]['obj_type']  = $product_data['product_type'];
                                    $orderSdf['order_objects'][$k]['obj_alias'] = $product_data['product_desc'];
                                    $orderSdf['order_objects'][$k]['goods_id']  = $product_data['goods_id'];
                                    //$orderSdf['order_objects'][$k]['pmt_price'] = $v[$objTitle['*:商品优惠金额']]?$v[$objTitle['*:商品优惠金额']]:0;
                                    $orderSdf['order_objects'][$k]['pmt_price'] = $orderSdf['order_objects'][$k]['amount'] - $orderSdf['order_objects'][$k]['sale_price'] ;
                                    if ($product_data['items']){
                                        $orderObjectItem = $k;
                                        foreach ($product_data['items'] as $inc => $iv){
                                            $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['bn']          = $iv['bn'];
                                            $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['name']        = $iv['name'];
                                            $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['quantity']    = $iv['nums'] * $v[$objTitle['*:购买数量']];
                                            $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['price']       = $iv['price']?$iv['price']:0;
                                            $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['amount']      = $iv['nums'] * ($iv['price']?$iv['price']:0);
                                            $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['item_type']    = $product_data['product_type'];
                                            $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$inc]['product_id']  = $iv['product_id'];
                                        }
                                    }else {
                                        $orderObjectItem = $k;
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['bn']          = $v[$objTitle['*:商品货号']];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['name']        = $v[$objTitle['*:商品名称']];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['quantity']    = $v[$objTitle['*:购买数量']];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['price']       = $v[$objTitle['*:商品原价']];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['amount']      = $v[$objTitle['*:商品原价']]*$v[$objTitle['*:购买数量']];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['item_type']    = $product_data['product_type'];
                                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['product_id']  = $product_data['product_id'];
                                    }
                                    break;
                                }
                            }
                        }
                    }else {
                        $orderSdf['order_objects'][$k]['bn']         = $v[$objTitle['*:商品货号']];
                        $orderSdf['order_objects'][$k]['name']       = $v[$objTitle['*:商品名称']];
                        $orderSdf['order_objects'][$k]['quantity']   = 1; //写死一个object一个item
                        $orderSdf['order_objects'][$k]['price']      = $v[$objTitle['*:商品原价']];
                        $orderSdf['order_objects'][$k]['amount']     = $v[$objTitle['*:商品原价']] * $v[$objTitle['*:购买数量']];
                        $orderSdf['order_objects'][$k]['obj_type']   = 'goods';   //写死一个object一个item，并且类型是goods
                        $orderSdf['order_objects'][$k]['obj_alias']  = '商品';    //写死一个object一个item，并且类型是商品
                        $orderSdf['order_objects'][$k]['goods_id']   = $product_info['goods_id'];
                        $orderSdf['order_objects'][$k]['sale_price'] = $orderSdf['order_objects'][$k]['amount'];

                        $orderObjectItem = $k;
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['bn'] = $v[$objTitle['*:商品货号']];
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['name'] = $v[$objTitle['*:商品名称']];
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['quantity'] = $v[$objTitle['*:购买数量']];
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['price'] = $v[$objTitle['*:商品原价']];
                        //$orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['pmt_price'] = $v[$objTitle['*:商品优惠金额']]?$v[$objTitle['*:商品优惠金额']]:0;
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['sale_price'] = $v[$objTitle['*:销售价']] * $v[$objTitle['*:购买数量']];
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['amount'] = $v[$objTitle['*:商品原价']]*$v[$objTitle['*:购买数量']];
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['obj_type'] = 'product';   //写死product
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['product_id'] = $product_info['product_id'];
                        $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['pmt_price'] = $orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['amount']-$orderSdf['order_objects'][ $orderObjectItem]['order_items'][$k]['sale_price'] ; 
                    }

            }
            //处理店铺信息
            $shop = $this->app->model('shop')->dump(array('shop_bn'=>$orderSdf['shop_id']));
            if(!$shop) continue;
            $is_code = strtolower($orderSdf['shipping']['is_cod']);
            #检测货到付款
            if( ($is_code == '是') || ($is_code == 'true')){
                $is_code = 'true';
            }else{
                $is_code = 'false';
            }
            $is_tax = strtolower($orderSdf['is_tax']);
            #检测货到付款
            if( ($is_tax == '是') || ($is_tax == 'true')){
                $is_tax = 'true';
            }else{
                $is_tax = 'false';
            }
            $createway = strtolower($orderSdf['createway']);
            #检测货到付款
            if( ($createway == '是') || ($createway == 'true')){
                $createway = 'matrix';
            }else{
                $createway = 'import';
            }

            $orderSdf['shop_id']            = $shop['shop_id'];
            $orderSdf['shop_type']          = $shop['shop_type'];
            $orderSdf['createtime']         = strtotime($orderSdf['createtime']);
            $orderSdf['paytime']            = strtotime($orderSdf['paytime']);
            $orderSdf['consignee']['area']  = $orderSdf['consignee']['area']['province']."/".$orderSdf['consignee']['area']['city']."/".$orderSdf['consignee']['area']['county'];
            $orderSdf['shipping']['is_cod'] = $is_code;#$orderSdf['shipping']['is_cod']?strtolower($orderSdf['shipping']['is_cod']):'false';
            $orderSdf['is_tax']             = $is_tax;
            $orderSdf['cost_tax']           = $orderSdf['cost_tax'] ? $orderSdf['cost_tax'] : '0';
            $orderSdf['discount']           = $orderSdf['discount'] ? $orderSdf['discount'] : '0';
            $orderSdf['score_g']            = $orderSdf['score_g'] ? $orderSdf['score_g'] : '0';
            $orderSdf['cost_item']          = $orderSdf['cost_item'] ? $orderSdf['cost_item'] : '0';
            $orderSdf['total_amount']       = $orderSdf['total_amount'] ? $orderSdf['total_amount'] : '0';
            $orderSdf['pmt_order']          = $orderSdf['pmt_order'] ? $orderSdf['pmt_order'] : '0';
            $orderSdf['pmt_goods']          = $orderSdf['pmt_goods'] ? $orderSdf['pmt_goods'] : '0';
            $tmp_order_source               = ome_order_func::get_order_source();
            $tmp_order_source               = array_flip($tmp_order_source);
            $orderSdf['order_source']       = $tmp_order_source[$orderSdf['order_source']]?$tmp_order_source[$orderSdf['order_source']]:'direct';
            $orderSdf['custom_mark']        = kernel::single('ome_func')->append_memo($orderSdf['custom_mark']);
            $orderSdf['mark_text']          = kernel::single('ome_func')->append_memo($orderSdf['mark_text']);
            $orderSdf['createway']          = $createway;
            $orderSdf['source']             = 'local';
            //增加会员判断逻辑
            $memberObj = &app::get('ome')->model('members');
            $tmp_member_name = trim($orderSdf['account']['uname']);
            $memberInfo = $memberObj->dump(array('uname'=>$tmp_member_name),'member_id');
            if($memberInfo){
                $orderSdf['member_id'] = $memberInfo['member_id'];
            }else{
                $members_data = array(
                    'account' => array(
                        'uname' => $tmp_member_name,
                    ),
                    'contact' => array(
                        'name' => $tmp_member_name,
                    ),
                );
                if($memberObj->save($members_data)){
                    $orderSdf['member_id'] = $members_data['member_id'];
                }
            }

            if($count < $limit){
                $count ++;
            }else{
                $count = 0;
                $page ++;
            }

            $orderSdfs[$page][] = $orderSdf;
        }
        //error_log(var_export($orderSdfs,1),3,'e:/nnew.txt');exit;

        foreach($orderSdfs as $v){
            $queueData = array(
                'queue_title'=>'订单导入',
                'start_time'=>time(),
                'params'=>array(
                    'sdfdata'=>$v,
                    'app' => 'ome',
                    'mdl' => 'orders'
                ),
                'worker'=>'ome_order_import.run',
            );
            $oQueue->save($queueData);

        }
        app::get('base')->model('queue')->flush();
    }

    //导入
    function prepared_import_csv_row($row,$title,&$tmpl,&$mark,&$newObjFlag,&$msg){

        //定义一个商品货号状态，为的是区别商品明细是否有值(2011_12_21_luolongjie)
        static $has_products = 0;
        if(empty($row)){
            $error_msg = array();
            //当商品没有货号时候，停止导入（有其他商品明细，却没货号，或者货号不对）
            if(isset($this->not_exist_product_bn)){
                if(count($this->not_exist_product_bn) > 10){
                    for($i=0;$i<10;$i++){
                        $not_exist_product_bn[] = current($this->not_exist_product_bn);
                        next($this->not_exist_product_bn);
                    }
                    $more = "...";
                }else{
                    $not_exist_product_bn = $this->not_exist_product_bn;
                    $more = "";
                }
                $error_msg[] = "不存在的货号：".implode(",",$not_exist_product_bn).$more;
            }elseif($has_products == 0){ //没有任何商品明细的时候
                $error_msg[] = "缺少商品明细";
            }

            if(isset($this->duplicate_order_bn_in_file)){
                if(count($this->duplicate_order_bn_in_file) > 10){
                    for($i=0;$i<10;$i++){
                        $duplicate_order_bn_in_file[] = current($this->duplicate_order_bn_in_file);
                        next($this->duplicate_order_bn_in_file);
                    }
                    $more = "...";
                }else{
                    $more = "";
                }
                $error_msg[] = "文件中以下订单号重复：".implode(",",$this->duplicate_order_bn_in_file).$more;
            }
            if(isset($this->duplicate_order_bn_in_db)){
                if(count($this->duplicate_order_bn_in_db) > 10){
                    for($i=0;$i<10;$i++){
                        $duplicate_order_bn_in_db[] = current($this->duplicate_order_bn_in_db);
                        next($this->duplicate_order_bn_in_db);
                    }
                    $more = "...";
                }else{
                    $more = "";
                }
                $error_msg[] = "以下订单号在系统中已经存在：".implode(",",$this->duplicate_order_bn_in_db).$more;
            }
            if(!empty($error_msg)){
                unset($this->import_data);
                $msg['error'] = implode("     ",$error_msg);
                return false;
            }
        }


        $mark = false;
        $fileData = $this->import_data;

        if( !$fileData )
            $fileData = array();

        if( substr($row[0],0,1) == '*' ){
            $titleRs =  array_flip($row);

            $mark = 'title';

            return $titleRs;
        }else{

            if( $row[0] ){
                $row[0] = trim($row[0]);
                if( array_key_exists( '*:商品货号',$title )  ) {
                    if(!$this->app->model('products')->dump(array('bn'=>$row[1]))){
                        $product_status = false;
                        foreach(kernel::servicelist('ome.product') as $name=>$object){
                            if(method_exists($object, 'checkProductByBn')){
                                $product_info = $object->checkProductByBn($row[1]);
                                if($product_info){
                                    $product_status = true;
                                    break;
                                }
                            }
                        }
                        if ($product_status==false) $this->not_exist_product_bn = isset($this->not_exist_product_bn)?array_merge($this->not_exist_product_bn,array($row[1])):array($row[1]);
                    }
                    //说明商品明细有过值，并非为空(2011_12_21_luolongjie)
                    $has_products = 1;
                    $fileData[$row[0]]['obj']['contents'][] = $row;
                }else{
                    //计数判断，是否超过10000条记录，超过就提示数据过多
                    if(isset($this->order_nums)){
                        kernel::log($this->order_nums);
                        $this->order_nums ++;
                        if($this->order_nums > 5000){
                            unset($this->import_data);
                            $msg['error'] = "导入的数据量过大，请减少到5000单以下！";
                            return false;
                        }
                    }else{
                        $this->order_nums = 0;
                    }

                    if(isset($fileData[$row[0]])){
                        $this->duplicate_order_bn_in_file = isset($this->duplicate_order_bn_in_file)?array_merge($this->duplicate_order_bn_in_file,array($row[0])):array($row[0]);
                    }
                    if($this->dump(array('order_bn'=>$row[0]))){
                        $this->duplicate_order_bn_in_db = isset($this->duplicate_order_bn_in_db)?array_merge($this->duplicate_order_bn_in_db,array($row[0])):array($row[0]);
                    }

                    if(empty($row[6])){
                        unset($this->import_data);
                        $msg['error'] = "来源店铺编号不能为空";
                        return false;
                    }

                    $shopModel = app::get('ome')->model('shop');
                    $shop = $shopModel->getList('shop_bn',array('shop_bn'=>$row[6]),0,1);
                    if (!$shop) {
                            unset($this->import_data);
                            $msg['error'] = "来源店铺【".$row[6]."】不存在";
                            return false;
                    }

                    $fileData[$row[0]]['order']['contents'][] = $row;
                }

                $this->import_data = $fileData;
            }
        }


        return null;
    }

    function prepared_import_csv_obj($data,$mark,$tmpl,&$msg = ''){
        return null;
    }

    function counter_dispatch($filter=null){
        $table_name = app::get('ome')->model('orders')->table_name(1);
        $strWhere = '';

        $sql = 'SELECT count(*) as _count FROM `'.$this->table_name(1).'` WHERE '.$this->_filter($filter) . $strWhere;
        $row = $this->db->select($sql);

        return intval($row[0]['_count']);
    }

    function countAbnormal($filter=null){
        $abnormal_table_name = app::get('ome')->model('abnormal')->table_name(1);
        $strWhere = '';
        if(isset($filter['abnormal_type_id'])){
            $strWhere = ' AND '.$abnormal_table_name.'.abnormal_type_id ='.$filter['abnormal_type_id'];
        }

        $row = $this->db->select('SELECT count(*) as _count FROM `'.$this->table_name(1).'` LEFT JOIN  '.$abnormal_table_name.'  ON '.$this->table_name(1).'.order_id = '.$abnormal_table_name.'.order_id WHERE '.$this->_abnormalFilter($filter,$this->table_name(1)) . $strWhere);

        return intval($row[0]['_count']);
    }

    function getlistAbnormal($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(!$cols){
            $cols = $this->defaultCols;
        }
        if(!empty($this->appendCols)){
            $cols.=','.$this->appendCols;
        }
        if($this->use_meta){
             $meta_info = $this->prepare_select($cols);
        }

        $abnormal_table_name = app::get('ome')->model('abnormal')->table_name(1);
        $strWhere = '';
        if(isset($filter['abnormal_type_id'])){
            $strWhere = ' AND '.$abnormal_table_name.'.abnormal_type_id ='.$filter['abnormal_type_id'];
        }

        $this->defaultOrder[0] = $this->table_name(true).'.createtime';
        $tmpCols = array();
        foreach(explode(',',$cols) as $col){
            if($col == 1){
                $tmpCols[] = $col;
            }else{
                $tmpCols[] = $this->table_name(true).'.'.$col;
            }
        }
        $cols = implode(',',$tmpCols);
        unset($tmpCols);

        $orderType = $orderType?$orderType:$this->defaultOrder;
        $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` LEFT JOIN  '.$abnormal_table_name.'  ON '.$this->table_name(1).'.order_id = '.$abnormal_table_name.'.order_id WHERE '.$this->_abnormalFilter($filter,$this->table_name(1)) . $strWhere;

        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);

        $data = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($data, $cols);
        if($this->use_meta && count($meta_info['metacols']) && $data){
            foreach($meta_info['metacols'] as $col){
                $obj_meta = new dbeav_meta($this->table_name(true),$col,$meta_info['has_pk']);
                $obj_meta->select($data);
            }
        }
        return $data;
    }

    function getColumns(){
        $columns = array();
        foreach( $this->_columns() as $k=>$v ){
            $columns[] = $k;
        }

        return $columns;
    }


    /**如果是订单编辑，保存订单的原始数据
     * @param int $log_id
     */
    function write_log_detail($log_id,$detail){
        $ooObj = $this->app->model('operations_order');
        $data = array(
           'log_id'=>$log_id,
           'order_id' => $detail['order_id'],
           'order_detail' =>$detail,
        );

        $ooObj->save($data);
    }

    /**
     * 读取订单编辑前的详情
     */
    function read_log_detail($order_id,$log_id){
        $ooObj = $this->app->model('operations_order');
        $sObj = $this->app->model('shop');
        $oodetail = $ooObj->dump(array('order_id'=>$order_id,'log_id'=>$log_id),'*');

        $detail = unserialize($oodetail['order_detail']);

        $oodetail['order_detail'] = $detail;
        foreach($detail['order_objects'] as $key=>$value){
            foreach($value['order_items'] as $k=>$v){
                $addon[$key][$k] = unserialize($v['addon']);
                foreach((array)$addon[$key][$k]['product_attr'] as $vl){
                    $add[$key][$k] .= $vl['label'].":".$vl['value'].";";
                }
                $detail['order_objects'][$key]['order_items'][$k]['addon'] = $add[$key][$k];
                $detail['order_objects'][$key]['order_items'][$k]['quantity'] = $v['quantity'] ? $v['quantity'] : $v['nums'];

            }
        }

        //发货人信息
        if(empty($detail['consigner']['name'])){
            $shop_info = $sObj->getList('*',array('shop_id'=>$detail['shop_id']));
            $shop_info = $shop_info[0];
            $detail['consigner']['name'] = $shop_info['default_sender'];
            $detail['consigner']['area'] = $shop_info['area'];
            $detail['consigner']['addr'] = $shop_info['addr'];
            $detail['consigner']['zip'] = $shop_info['zip'];
            $detail['consigner']['email'] = $shop_info['email'];
            $detail['consigner']['tel'] = $shop_info['tel'];
        }
        if($detail['shop_type'] == 'shopex_b2b'){
            //代销人信息
            $osaObj = &app::get('ome')->model('order_selling_agent');
            $agent = $osaObj->dump(array('order_id'=>$detail['order_id']),'*');
            $detail['agent'] = $agent;
        }
        //买家留言
        $custom_mark = unserialize($detail['custom_mark']);
        if ($custom_mark){
            foreach ($custom_mark as $k=>$v){
                $custom_mark[$k] = $v;
                if (!strstr($v['op_time'], "-")){
                    $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                    $custom_mark[$k]['op_time'] = $v['op_time'];
                }
            }
        }

        //订单备注
        $mark_text = unserialize($detail['mark_text']);
        if ($mark_text)
        foreach ($mark_text as $k=>$v){
            $mark_text[$k] = $v;
            if (!strstr($v['op_time'], "-")){
                $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                $mark_text[$k]['op_time'] = $v['op_time'];
            }
        }
        $detail['mark_type_arr'] = ome_order_func::order_mark_type();//订单备注旗标

        $detail['custom_mark'] = $custom_mark;
        $detail['mark_text'] = $mark_text;
        $oodetail['order_detail'] = $detail;
        return $oodetail;
    }


    //不能进行订单编辑的状态判断
   public function not_allow_edit($order_id){
        $order = $this->dump($order_id);
        //已取消的订单不允许编辑
        if($order['process_status'] == 'cancel'){
            $data['msg'] = '该订单已取消，不能进行编辑';
            $data['res'] = 'false';
            return $data;
        }
        //退款申请中的订单不允许编辑
        if($order['pay_status'] == '6'){
            $data['msg'] = '退款申请中的订单不允许编辑';
            $data['res'] = 'false';
            return $data;
        }
        //退款中的订单不允许编辑
        if($order['pay_status'] == '7'){
            $data['msg'] = '退款中的订单不允许编辑';
            $data['res'] = 'false';
            return $data;
        }
        //支付中的订单不允许编辑
        if($order['pay_status'] == '8'){
            $data['msg'] = '支付中的订单不允许编辑';
            $data['res'] = 'false';
            return $data;
        }
        //已发货订单不允许编辑
        if($order['ship_status'] == '1'){
            $data['msg'] = '已发货订单不允许编辑';
            $data['res'] = 'false';
            return $data;
        }
        //部分发货订单不允许编辑
        if($order['ship_status'] == '2'){
            $data['msg'] = '部分发货订单不允许编辑';
            $data['res'] = 'false';
            return $data;
        }
        //部分退货订单不允许编辑
        if($order['ship_status'] == '3'){
            $data['msg'] = '部分退货订单不允许编辑';
            $data['res'] = 'false';
            return $data;
        }
        //已退货订单不允许编辑
        if($order['ship_status'] == '4'){
            $data['msg'] = '已退货订单不允许编辑';
            $data['res'] = 'false';
            return $data;
        }
        //余单撤销订单不允许编辑
        if($order['process_status'] == 'remain_cancel'){
            $data['msg'] = '余单撤销订单不允许编辑';
            $data['res'] = 'false';
            return $data;
        }
        return true;
   }

   /*根据发货单号获取订单运费总额*/
   function get_costfreight($delivery_id = array()){
        $Odelivery_order = $this->app->model('delivery_order');
        $getOrders = $Odelivery_order->getList('order_id',array('delivery_id|in'=>$delivery_id));
        $costfreight = 0;
        if($getOrders){
            foreach ($getOrders as $k => $v) {
                $orderid[$k] = $v['order_id'];
            }

            $costfreight = $this->getList('sum(cost_freight) as cost_freight',array('order_id|in'=>$orderid));
            $costfreight = $costfreight[0]['cost_freight'];
        }

        return $costfreight;
   }


   /**
   * 统计订单商品重量
   * @param  order_id
   * @return void
   */
    function getOrderWeight($order_id,$type='',$additional=''){
        $orderObj = &$this->app->model('orders');
        $productObj = &$this->app->model('products');
        $pkgObj = app::get('omepkg')->model('pkg_goods');
        $weight = 0;
        $order = $orderObj->dump($order_id,"order_id",array("order_objects"=>array("*",array("order_items"=>array("*")))));

        foreach ($order['order_objects'] as $k=>$v) {
            if($v['obj_type']=='pkg'){
                $bn = $v['bn'];
                $pkg = $pkgObj->dump(array('pkg_bn'=>$bn),'weight');
                if ($pkg['weight']>0){

                    //捆绑是一个删全删除的，所以取一个看状态是否是删除
                    $order_items_flag = array_pop($v['order_items']);
                
                    if ($order_items_flag['delete']=='false') {
                     
                        $weight+=$pkg['weight']*$v['quantity'];

                    }
                }else {
              
                    foreach($v['order_items'] as $k1=>$v1){
                        if ($v1['delete'] == 'false') {
                            $products = $productObj->dump(array('bn'=>$v1['bn']),'weight');
                            if($products['weight']>0){
                                $weight+=$products['weight']*$v1['quantity'];
                            }else{
                                $weight=0;
                                break 2;
                            }
                        }
                    }
                }

            }else{

                foreach($v['order_items'] as $k1=>$v1){

                    if ($v1['delete'] == 'false') {
                        $products = $productObj->dump(array('bn'=>$v1['bn']),'weight');

                        if($products['weight']>0){
                            $weight+=$products['weight']*$v1['quantity'];
                        }else{

                            $weight=0;

                            break 2;
                        }
                    }

                }

            }
        }
        $weight = round($weight,3);
        return $weight;
    }
    function getOrdersBnById($original_id = null){
       $sql="
            select
               do.delivery_id,o.order_bn
            from sdb_ome_delivery_order as do
            join sdb_ome_orders as o on do.order_id=o.order_id and do.delivery_id in ($original_id)";
        $_value = $this->db->select($sql);
        return $_value?$_value:null;
    }

    /**
    * 异常订单过滤条件
    *
    */

    function _abnormalFilter($filter,$tableAlias=null,$baseWhere=null){
        $table_name = $this->table_name(true);
        if(isset($filter['archive'])){
            $where = ' '.$table_name.'.archive='.$filter['archive'].' ';
            unset($filter['archive']);
        }else{
            $where = "1";
        }

        if(isset($filter['order_confirm_filter'])){
            $where .= ' AND '.$table_name.'.'.$filter['order_confirm_filter'];
            unset($filter['order_confirm_filter']);
        }
        if (isset($filter['assigned']))
        {
            if ($filter['assigned'] == 'notassigned')
            {
                $where .= ' AND ('.$table_name.'.group_id=0 AND '.$table_name.'.op_id=0)';
            }
            else
            {
                $where .= '  AND ('.$table_name.'.op_id > 0 OR '.$table_name.'.group_id > 0)';
            }
            unset ($filter['assigned']);
        }
        if (isset($filter['balance'])){
            if ($filter['balance'])
                $where .= " AND ".$table_name.".`old_amount` != 0 AND ".$table_name.".`total_amount` != `old_amount` ";
            else
                $where .= " AND ".$table_name.".`old_amount` = 0 ";
        }
        //自动取消订单过滤条件
        if (isset($filter['auto_cancel_order_filter'])){
            $where .= '  AND '.$table_name.'.'.$filter['auto_cancel_order_filter'];
        }

        if(isset($filter['product_bn'])){
            $itemsObj = &$this->app->model("order_items");
            $rows = $itemsObj->getOrderIdByPbn($filter['product_bn']);
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $pkjrows = $itemsObj->getOrderIdByPkgbn($filter['product_bn']);
            foreach($pkjrows as $pkjrow){
                $orderId[] = $pkjrow['order_id'];
            }

            $where .= '  AND '.$table_name.'.order_id IN ('.implode(',', $orderId).')';
            unset($filter['product_bn']);
        }

        //支付失败
        if(isset($filter['payment_fail']) && $filter['payment_fail'] == true){
            $api_fail = &$this->app->model("api_fail");
            $payment_fail_list = $api_fail->getList('order_id', array('type'=>'payment'), 0, -1);
            $payment_order_id = array();
            if ($payment_fail_list){
                foreach($payment_fail_list as $val){
                    $payment_order_id[] = $val['order_id'];
                }
            }
            $payment_order_id = implode(',', $payment_order_id);
            $payment_order_id =  $payment_order_id ? $payment_order_id : '\'\'';
            $where .= '  AND '.$table_name.'.order_id IN ('.$payment_order_id.')';
            unset($filter['payment_fail']);
        }

        if(isset($filter['product_barcode'])){
            $itemsObj = &$this->app->model("order_items");
            $rows = $itemsObj->getOrderIdByPbarcode($filter['product_barcode']);
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $where .= '  AND '.$table_name.'.order_id IN ('.implode(',', $orderId).')';
            unset($filter['product_barcode']);
        }
        //判断是否录入发票号
        if(isset($filter['is_tax_no'])){
            if($filter['is_tax_no']==1){
                $where .= '  AND '.$table_name.'.tax_no IS NOT NULL';

            }else{
                $where .= '  AND '.$table_name.'.tax_no IS NULL';
            }
            unset($filter['is_tax_no']);
        }
        //付款确认
        if (isset($filter['pay_confirm'])){
            $where .= ' AND '.$table_name.'.'.$filter['pay_confirm'];
            unset($filter['pay_confirm']);
        }
        //确认状态
        if (isset($filter['process_status_noequal'])){
            $value = '';
            foreach($filter['process_status_noequal'] as $k=>$v){
                $value .= "'".$v."',";
            }
            $len = strlen($value);
            $value_last = substr($value,0,($len-1));
            $where .= ' AND '.$table_name.'.process_status not in ( '.$value_last.")";
            unset($filter['process_status_noequal']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = &$this->app->model("members");
            $rows = $memberObj->getList('member_id',array('uname|head'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }
            $where .= '  AND '.$table_name.'.member_id IN ('.implode(',', $memberId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['pay_type'])){
            $cfgObj = &app::get('ome')->model('payment_cfg');
            $rows = $cfgObj->getList('pay_bn',array('pay_type'=>$filter['pay_type']));
            $pay_bn[] = 0;
            foreach($rows as $row){
                $pay_bn[] = $row['pay_bn'];
            }
            $where .= '  AND '.$table_name.'.pay_bn IN (\''.implode('\',\'', $pay_bn).'\')';
            unset($filter['pay_type']);
        }
        if(isset($filter['ship_tel_mobile'])){
            $where .= ' AND (ship_tel=\''.$filter['ship_tel_mobile'].'\' or ship_mobile=\''.$filter['ship_tel_mobile'].'\')';
            unset($filter['ship_tel_mobile']);
        }
        //部分支付 包含部分退款 部分支付
        if(isset($filter['pay_status_part'])){
            $where .= ' AND ('.$table_name.'.pay_status = \'3\' or ('.$table_name.'.pay_status = \'4\' and '.$table_name.'.ship_status = \'0\'))';
            unset($filter['pay_status_part']);
        }
        //付款确认时，部分退款的只有未发货的才能继续支付
        if(isset($filter['pay_status_set'])){
            if($filter['pay_status_set'] == 2){
                $where .= ' AND ('.$table_name.'.pay_status in (\'0\',\'3\') or ('.$table_name.'.pay_status = \'4\' and '.$table_name.'.ship_status = \'0\'))';
            }else{
                $where .= ' AND ('.$table_name.'.pay_status in (\'0\',\'3\',\'8\') or ('.$table_name.'.pay_status = \'4\' and '.$table_name.'.ship_status = \'0\'))';
            }
            unset($filter['pay_status_set']);
        }

        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }
    #获取发货单上捆绑商品item_id
    function getPkgItemId($delivery_id = null){
        $sql = "select delivery_item_id  from sdb_ome_delivery_items_detail where item_type='pkg' and delivery_id=".$delivery_id;
        $_value = $this->db->select($sql);
        if(!empty($_value)){
            foreach( $_value as $id){
                $item_id[] = $id['delivery_item_id'];
            }
            return $item_id;
        }
        return false;
        
        
    }
    #根据product_id，获取商品类型、品牌类型
    function getTypeName($product_id =null){
        $sql = 'select  
                    type.name type_name,
                    brand.brand_name
                from sdb_ome_products product
                left join sdb_ome_goods goods on product.goods_id=goods.goods_id
                left join sdb_ome_goods_type  type on  goods.type_id=type.type_id
                left join sdb_ome_brand brand  on goods.brand_id=brand.brand_id where product.product_id='.$product_id;
        $_name = $this->db->selectRow($sql);
        if(empty($_name)){
            return false;
        }
        return $_name;
    }
    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'order';
        if ($params['disabled'] == 'false' && $params['is_fail'] == 'false' || $params['archive'] && $params['filter_sql']['process_status'] != 'cancel') {
            //当前订单
            $type .= '_current';
        }
        elseif ($params['disabled'] == 'false' && $params['order_confirm_filter'] == '(is_fail=\'false\' OR (is_fail=\'true\' AND status!=\'active\'))') {
            //历史订单
            $type .= '_history';
        }
        elseif ($params['is_fail'] == 'true' && $params['status'] == 'active') {
            //失败订单
            $type .= '_fail';
        }
        $type .= '_export';
        return $type;
    }

    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'order';
        $type .= '_import';
        return $type;
    }

    /**
     * 发货单列表项扩展字段
     */
    function extra_cols(){
        return array(
            'column_abnormal_type_name' => array('label'=>'异常类型','width'=>'80','func_suffix'=>'abnormal_type_name'),
        );
    }

    /**
     * 买家备注扩展字段格式化
     */
    function extra_abnormal_type_name($rows){
        return kernel::single('ome_extracolumn_order_abnormaltypename')->process($rows);
    }

    /**
     * 订单导出列表扩展字段
     */
    function export_extra_cols(){
        return array(
            'column_discount_plan' => array('label'=>'优惠方案','width'=>'100','func_suffix'=>'discount_plan'),
            'column_mark_type_colour' => array('label'=>'订单备注图标颜色','width'=>'100','func_suffix'=>'mark_type_colour'),
        );
    }

    /**
     * 买家备注扩展字段格式化
     */
    function export_extra_discount_plan($rows){
        return kernel::single('ome_exportextracolumn_order_discountplan')->process($rows);
    }
    /**
     * 订单备注图标颜色扩展字段格式化
     */
    function export_extra_mark_type_colour($rows){
        return kernel::single('ome_exportextracolumn_order_marktypecolour')->process($rows);
    }
    
    /**
     +----------------------------------------------------------
     * 保存_淘宝平台_的原始属性值[bn、oid、quantity、promotion_id]
     +----------------------------------------------------------
     * Author: ExBOY
     * Time: 2014-07-25 $
     * [Ecos!] (C)2003-2014 Shopex Inc.
     +----------------------------------------------------------
     */
    public function hold_order_delivery($sdf)
    {
       $data                = array();
       $data['order_bn']    = $sdf['order_bn'];
       
       #现只保存_淘宝平台
       if($sdf['shop_type'] != 'taobao' || empty($sdf['order_objects']))
       {
           return false;
       }
       
       foreach ($sdf['order_objects'] as $key => $obj_val)
       {            
            foreach ($obj_val['order_items'] as $key_j => $item)
            {
                $data['oid'][]   = $obj_val['oid'];
                
                $data['bn'][]              = $item['bn'];               
                $data['quantity'][]        = $item['quantity'];
                $data['promotion_id'][]    = $item['promotion_id'];
            }
       }
       
       $save_data   = array();
       $save_data['order_bn']   = $data['order_bn'];
       $save_data['oid']            = implode(',', $data['oid']);
       $save_data['quantity']       = implode(',', $data['quantity']);
       $save_data['promotion_id']   = implode(',', $data['promotion_id']);
       
       $save_data['bn']         = serialize($data['bn']);//序列化存储防止有,逗号
       $save_data['dateline']   = time();
       
       $mdl_orddly  = &app::get('ome')->model('order_delivery');
       $mdl_orddly->save($save_data);
       
       return true;
    }
    
    /**
     +----------------------------------------------------------
     * [拆单]订单暂停(部分拆分、部分发货)对应多个发货单
     +----------------------------------------------------------
     * Author: ExBOY
     * [Ecos!] (C)2003-2014 Shopex Inc.
     +----------------------------------------------------------
     */
    function pauseOrder_split($order_id, $must_update = 'false')
    {
        $flag       = false;//标记有未能暂停的发货单
        $pause_dly  = array();//已暂停OR取消的发货单
        $rs         = array();
        
        if ($order_id){
            $o = $this->dump($order_id,'pause');
            
            if ($o['pause'] == 'false' || $must_update == 'true'){
                $dlyObj = &app::get('ome')->model("delivery");
                $oOperation_log = &app::get('ome')->model('operation_log');
                $branchLib = kernel::single('ome_branch');
                $channelLib = kernel::single('channel_func');
                $eventLib = kernel::single('ome_event_trigger_delivery');
                $delivery_itemsObj = &app::get('ome')->model('delivery_items');
                $branch_productObj = &app::get('ome')->model('branch_product');
                
                //查询订单是否有发货单
                $dly_ids    = $dlyObj->getDeliverIdByOrderId($order_id);
                if($dly_ids)
                {
                    //处理订单对应多个发货单
                    foreach ($dly_ids as $key => $delivery_id)
                    {
                        //取仓库信息
                        $deliveryInfo = $dlyObj->dump($delivery_id,'*');
                        
                        #[自有仓]OR[第三方仓]已发货的发货单不执行
                        if($deliveryInfo['status'] == 'succ')
                        {
                            continue;
                        }
                        
                        $wms_id = $branchLib->getWmsIdById($deliveryInfo['branch_id']);
                        if($wms_id){
                            $is_selfWms = $channelLib->isSelfWms($wms_id);//是否自有仓储
                            if($is_selfWms)
                            {
                                $res = $eventLib->pause($wms_id,array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']),true);

                                if($res['rsp'] == 'success' || $res['rsp'] == 'succ')
                                {
                                    #[拆单]保存成功暂停的发货单 ExBOY
                                    $deliveryInfo['is_selfwms'] = true;
                                    $pause_dly[]                = $deliveryInfo;
                                }else{
                                    $rs[$delivery_id]['msg'] = $res['msg'];
                                    $rs[$delivery_id]['rsp']= 'fail';
                                    
                                    $rs[$delivery_id]['bn']   = $deliveryInfo['delivery_bn'];
                                    $rs[$delivery_id]['flag'] = 'self_wms';
                                    $flag   = true;//标记
                                }
                            }else{
                                $res = $eventLib->cancel($wms_id,array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']),true);
                                
                                if($res['rsp'] == 'success' || $res['rsp'] == 'succ')
                                {
                                    #[拆单]保存成功取消的发货单 ExBOY
                                    $deliveryInfo['is_selfwms'] = false;
                                    $pause_dly[]                = $deliveryInfo;
                                    $oOperation_log->write_log('delivery_back@ome',$deliveryInfo['delivery_id'],'发货单取消成功');
                                }else{
                                    $rs[$delivery_id]['rsp'] = 'fail';
                                    $rs[$delivery_id]['msg'] = $res['msg'];
                                    
                                    $oOperation_log->write_log('delivery_back@ome',$deliveryInfo['delivery_id'],'发货单取消失败,原因:'.$rs['msg']);
                                    //$dlyObj->update_sync_cancel($deliveryInfo['delivery_id'],'fail');
                                    $rs[$delivery_id]['bn']   = $deliveryInfo['delivery_bn'];
                                    $rs[$delivery_id]['flag'] = 'wms';
                                    $flag   = true;//标记
                                    $dlyObj->update_sync_cancel($deliveryInfo['delivery_id'],'fail'); 
                                }
                            }
                        }else{
                            $rs[$delivery_id]['rsp'] = 'fail';
                            
                            $rs[$delivery_id]['bn']   = $deliveryInfo['delivery_bn'];
                            $rs[$delivery_id]['flag'] = 'none_wms';
                            $flag   = true;//标记
                        }
                    }
                }else{
                    //没有发货单的情况，直接暂停当前订单
                    $order['order_id'] = $order_id;
                    $order['pause'] = 'true';
                    $this->save($order);
                    $oOperation_log->write_log('order_modify@ome',$order_id,'订单暂停');

                    //订单暂停状态同步
                    if ($service_order = kernel::servicelist('service.order')){
                        foreach($service_order as $object=>$instance){
                            if(method_exists($instance, 'update_order_pause_status')){
                                $instance->update_order_pause_status($order_id);
                            }
                        }
                    }
                    
                    $rs = array('rsp'=>'succ','msg'=>'');
                    return $rs;
                }
            }
        }
        
        #[拆单]发货单全部成功发货
        if(!empty($dly_ids) && empty($pause_dly) && $flag == false)
        {
            $order_id_list    = array();
            
            //处理订单对应多个发货单
            foreach ($dly_ids as $key => $delivery_id)
            {
                //取仓库信息
                $deliveryInfo = $dlyObj->dump($delivery_id,'delivery_id, is_bind');
                
                //是否是合并发货单
                if($deliveryInfo['is_bind'] == 'true')
                {
                    //取关联订单号进行暂停
                    $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
                    if($order_ids){
                        foreach ($order_ids as $id){
                            $order_id_list[]    = $id;
                        }
                    }
                }
            }
            $order_id_list[]    = $order_id;
            $order_id_list      = array_unique($order_id_list);
            
            foreach ($order_id_list as $id){
                $order['order_id'] = $id;
                $order['pause'] = 'true';
                $this->save($order);
                $oOperation_log->write_log('order_modify@ome',$id,'订单暂停');
            }
            
            
            //订单暂停状态同步
            if ($service_order = kernel::servicelist('service.order'))
            {
                foreach($service_order as $object=>$instance)
                {
                    if(method_exists($instance, 'update_order_pause_status'))
                    {
                        foreach ($order_id_list as $id)
                        {
                            $instance->update_order_pause_status($id);
                        }
                    }
                }
            }
            
            $rs = array('rsp'=>'succ','msg'=>'');
            return $rs;
        }
        
        #[全部]成功暂停OR取消的发货单_则执行
        if($pause_dly && $flag == false)
        {
            $deliveryInfo   = array();
            foreach ($pause_dly as $key => $val)
            {
                $deliveryInfo   = $val;
                
                //自有仓储
                if($deliveryInfo['is_selfwms'] == true)
                {
                    //wms暂停发货单成功，暂停本地主发货单
                    $tmpdly = array(
                        'delivery_id' => $deliveryInfo['delivery_id'],
                        'pause' => 'true'
                    );
                    $dlyObj->save($tmpdly);
                    $oOperation_log->write_log('delivery_modify@ome',$deliveryInfo['delivery_id'],'发货单暂停');

                    //是否是合并发货单
                    if($deliveryInfo['is_bind'] == 'true'){
                        //取关联发货单号进行暂停
                        $delivery_ids = $dlyObj->getItemsByParentId($deliveryInfo['delivery_id'],'array');
                        if($delivery_ids){
                            foreach ($delivery_ids as $id){
                                $tmpdly = array(
                                    'delivery_id' => $id,
                                    'pause' => 'true'
                                );
                                $dlyObj->save($tmpdly);
                                $oOperation_log->write_log('delivery_modify@ome',$id,'发货单暂停');
                            }
                        }
    
                        //取关联订单号进行暂停
                        $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
                        if($order_ids){
                            foreach ($order_ids as $id){
                                $order['order_id'] = $id;
                                $order['pause'] = 'true';
                                $this->save($order);
                                $oOperation_log->write_log('order_modify@ome',$id,'订单暂停');
                            }
                        }
                    }else{
                        //暂停当前订单
                        $order['order_id'] = $order_id;
                        $order['pause'] = 'true';
                        $this->save($order);
                        $oOperation_log->write_log('order_modify@ome',$order_id,'订单暂停');
                    }
    
                    //订单暂停状态同步
                    if ($service_order = kernel::servicelist('service.order')){
                        foreach($service_order as $object=>$instance){
                            if(method_exists($instance, 'update_order_pause_status')){
                               if($order_ids){
                                   foreach ($order_ids as $id){
                                       $instance->update_order_pause_status($id);
                                   }
                               }else{
                                   $instance->update_order_pause_status($order_id);
                               }
                            }
                        }
                    }
                }
                //第三方仓储
                else 
                {
                    //wms第三方仓储取消发货单成功，本地主发货单取消
                    $tmpdly = array(
                        'delivery_id' => $deliveryInfo['delivery_id'],
                        'status' => 'cancel',
                        'logi_id' => '',
                        'logi_name' => '',
                        'logi_no' => NULL,
                    );
                    $dlyObj->save($tmpdly);
                    $oOperation_log->write_log('delivery_modify@ome',$deliveryInfo['delivery_id'],'发货单撤销成功');
    
                    //是否是合并发货单
                    if($deliveryInfo['is_bind'] == 'true'){
                        //取关联发货单号进行暂停
                        $delivery_ids = $dlyObj->getItemsByParentId($deliveryInfo['delivery_id'],'array');
                        if($delivery_ids){
                            foreach ($delivery_ids as $id){
                                $tmpdly = array(
                                    'delivery_id' => $id,
                                    'status' => 'cancel',
                                    'logi_id' => '',
                                    'logi_name' => '',
                                    'logi_no' => NULL,
                                );
                                $dlyObj->save($tmpdly);
                                $oOperation_log->write_log('delivery_modify@ome',$id,'发货单撤销成功');
                            }
                        }
    
                        //取关联订单号进行还原
                        $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
                        if($order_ids){
                            foreach ($order_ids as $id){
                                $order['order_id'] = $id;
                                $order['confirm'] = 'N';
                                $order['process_status'] = 'unconfirmed';
                                
                                #[拆单]获取订单对应有效的发货单
                                $temp_dlyid     = $dlyObj->getDeliverIdByOrderId($id);
                                if(!empty($temp_dlyid))
                                {
                                    $order['process_status'] = 'splitting';//部分拆分
                                }
                                
                                $this->save($order);
                                $oOperation_log->write_log('order_modify@ome',$id,'发货单撤销,订单还原需重新审核');
                            }
                        }
                    }else{
                        //还原当前订单
                        $order['order_id'] = $order_id;
                        $order['confirm'] = 'N';
                        $order['process_status'] = 'unconfirmed';
                        
                        #[拆单]获取订单对应有效的发货单
                        $temp_dlyid     = $dlyObj->getDeliverIdByOrderId($order_id);
                        if(!empty($temp_dlyid))
                        {
                            $order['process_status'] = 'splitting';//部分拆分
                        }
                        
                        $this->save($order);
                        $oOperation_log->write_log('order_modify@ome',$order_id,'发货单撤销,订单还原需重新审核');
                    }
    
                    //释放冻结库存
                     //增加branch_product释放冻结库存
                    $branch_id = $deliveryInfo['branch_id'];
                    $product_ids = $delivery_itemsObj->getList('product_id,number',array('delivery_id'=>$deliveryInfo['delivery_id']),0,-1);
                    foreach($product_ids as $key=>$v){
                        $branch_productObj->unfreez($branch_id,$v['product_id'],$v['number']);
                    }
                }
            }   
        }
        
        #失败结果处理 
        if($flag)
        {
            //if(!empty($pause_dly)) $result = $this->renewOrder_split($order_id);#有部分成功暂停的发货单_则renewOrder恢复
            
            $temp_rs    = array('rsp'=>'fail', 'is_split'=>'true');
            foreach ($rs as $key => $val)
            {
                $temp_rs['msg']     .= '发货单'.$val['bn'].' '.str_replace('数字校验失败', '撤销失败', $val['msg']).'<br>';
            }
            
            #成功暂停或取消的发货单
            if(!empty($pause_dly))
            {
                $temp_msg   = array();
                foreach ($pause_dly as $key => $val)
                {
                    if($val['is_selfwms'] == true)
                    {
                        $temp_msg['is_selfwms'][]   = $val['delivery_bn'];
                    }
                    else 
                    {
                        $temp_msg['other'][]   = $val['delivery_bn'];
                    }
                }
                
                if(!empty($temp_msg['is_selfwms']))
                {
                    $temp_rs['msg'] .= '<br><br>自有仓储,成功暂停的发货单：'.implode(',', $temp_msg['is_selfwms']);
                }
                if(!empty($temp_msg['other']))
                {
                    $temp_rs['msg'] .= '<br><br>第三方仓储,成功取消的发货单：'.implode(',', $temp_msg['is_selfwms']);
                }
            }
            
            $rs = $temp_rs;
            unset($temp_rs);
        }
        else 
        {
            $rs = array('rsp'=>'succ','msg'=>'');
        }
        
        return $rs;
    }
    
    /**
     +----------------------------------------------------------
     * [拆单]订单恢复(部分拆分、部分发货)对应多个发货单
     +----------------------------------------------------------
     * Author: ExBOY
     * [Ecos!] (C)2003-2014 Shopex Inc.
     +----------------------------------------------------------
     */
    function renewOrder_split($order_id)
    {
        $flag   = false;//标记有未能暂停的发货单
        $pause_dly  = array();//需要恢复的发货单
        
        if ($order_id){
            $o = $this->dump($order_id,'pause');

            if ($o['pause'] == 'true'){
                $dlyObj = &app::get('ome')->model("delivery");
                $oOperation_log = &app::get('ome')->model('operation_log');
                $branchLib = kernel::single('ome_branch');
                $channelLib = kernel::single('channel_func');
                $eventLib = kernel::single('ome_event_trigger_delivery');

                //查询订单是否有发货单
                $dly_ids = $dlyObj->getDeliverIdByOrderId($order_id);
                if($dly_ids)
                {
                    //处理订单对应多个发货单
                    foreach ($dly_ids as $key => $delivery_id)
                    {
                        //取仓库信息
                        $deliveryInfo = $dlyObj->dump($delivery_id,'*');
                        
                        #[自有仓]OR[第三方仓]已发货的发货单不执行
                        if($deliveryInfo['status'] == 'succ')
                        {
                            continue;
                        }
                        $pause_dly[]    = $deliveryInfo;
                        
                        $wms_id = $branchLib->getWmsIdById($deliveryInfo['branch_id']);
                        if($wms_id){
                            $is_selfWms = $channelLib->isSelfWms($wms_id);
                            if($is_selfWms){
                                $res = $eventLib->renew($wms_id,array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']),true);
                                if($res['rsp'] == 'success' || $res['rsp'] == 'succ'){
                                    //wms恢复发货单成功，恢复本地主发货单
                                    $tmpdly = array(
                                        'delivery_id' => $deliveryInfo['delivery_id'],
                                        'pause' => 'false'
                                    );
                                    $dlyObj->save($tmpdly);
                                    $oOperation_log->write_log('delivery_modify@ome',$deliveryInfo['delivery_id'],'发货单恢复');
    
                                    //是否是合并发货单
                                    if($deliveryInfo['is_bind'] == 'true'){
                                        //取关联发货单号进行暂停
                                        $delivery_ids = $dlyObj->getItemsByParentId($deliveryInfo['delivery_id'],'array');
                                        if($delivery_ids){
                                            foreach ($delivery_ids as $id){
                                                $tmpdly = array(
                                                    'delivery_id' => $id,
                                                    'pause' => 'false'
                                                );
                                                $dlyObj->save($tmpdly);
                                                $oOperation_log->write_log('delivery_modify@ome',$id,'发货单恢复');
                                            }
                                        }
    
                                        //取关联订单号进行暂停
                                        $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
                                        if($order_ids){
                                            foreach ($order_ids as $id){
                                                $order['order_id'] = $id;
                                                $order['pause'] = 'false';
                                                $this->save($order);
                                                $oOperation_log->write_log('order_modify@ome',$id,'订单恢复');
    
    
                                            }
                                        }
                                    }else{
                                        //暂停当前订单
                                        $order['order_id'] = $order_id;
                                        $order['pause'] = 'false';
                                        $this->save($order);
                                        $oOperation_log->write_log('order_modify@ome',$order_id,'订单恢复');
                                    }
    
                                    //订单暂停状态同步
                                    if ($service_order = kernel::servicelist('service.order')){
                                        foreach($service_order as $object=>$instance){
                                            if(method_exists($instance, 'update_order_pause_status')){
                                               if($order_ids){
                                                   foreach ($order_ids as $id){
                                                       $instance->update_order_pause_status($id, 'false');
                                                   }
                                               }else{
                                                   $instance->update_order_pause_status($order_id, 'false');
                                               }
                                            }
                                        }
                                    }
                                }else{
                                    $flag   = true;
                                }
                            }
                        }else{
                            $flag   = true;
                        }
                    }
                }else{
                    $order['order_id'] = $order_id;
                    $order['pause'] = 'false';
                    $this->save($order);
                    $oOperation_log->write_log('order_modify@ome',$order_id,'订单恢复');

                    //订单恢复状态同步
                    if ($service_order = kernel::servicelist('service.order')){
                        foreach($service_order as $object=>$instance){
                            if(method_exists($instance, 'update_order_pause_status')){
                               $instance->update_order_pause_status($order_id, 'false');
                            }
                        }
                    }
                }
                
                #[拆单]发货单全部成功发货
                if(!empty($dly_ids) && empty($pause_dly) && $flag == false)
                {
                    $order_id_list    = array();
                    
                    //处理订单对应多个发货单
                    foreach ($dly_ids as $key => $delivery_id)
                    {
                        //取仓库信息
                        $deliveryInfo = $dlyObj->dump($delivery_id,'delivery_id, is_bind');
                    
                        //是否是合并发货单
                        if($deliveryInfo['is_bind'] == 'true')
                        {
                            //取关联订单号进行暂停
                            $order_ids = $dlyObj->getOrderIdByDeliveryId($deliveryInfo['delivery_id']);
                            if($order_ids){
                                foreach ($order_ids as $id){
                                    $order_id_list[]    = $id;
                                }
                            }
                        }
                    }
                    $order_id_list[]    = $order_id;
                    $order_id_list      = array_unique($order_id_list);
                    
                    foreach ($order_id_list as $id){
                        $order['order_id'] = $id;
                        $order['pause'] = 'false';
                        $this->save($order);
                        $oOperation_log->write_log('order_modify@ome',$id,'订单恢复');
                    }
                    
                    //订单恢复状态同步
                    if ($service_order = kernel::servicelist('service.order'))
                    {
                        foreach($service_order as $object=>$instance)
                        {
                            if(method_exists($instance, 'update_order_pause_status'))
                            {
                                foreach ($order_id_list as $id)
                                {
                                    $instance->update_order_pause_status($id);
                                }
                            }
                        }
                    }
                }
                
                return ($flag ? false : true);
            }
        }
        return false;
    }
    
    /**
     +----------------------------------------------------------
     * [拆单]打回订单的发货单
     +----------------------------------------------------------
     * Author: ExBOY
     * [Ecos!] (C)2003-2014 Shopex Inc.
     +----------------------------------------------------------
     */
    function rebackDeliveryByOrderId_split($order_id, $dly_status=false)
    {
        $flag   = true;//[拆单]发货单打回_成功标志 ExBOY
        
        $dlyObj = &$this->app->model("delivery");
        $dly_oObj = &$this->app->model("delivery_order");
        $opObj = &$this->app->model('operation_log');
        $data = $dly_oObj->getList('*',array('order_id'=>$order_id),0,-1);
        $bind = array();
        $dlyos = array();
        $mergedly = array();
        if ($data)
        foreach ($data as $v){
            $dly = $dlyObj->dump($v['delivery_id'],'process,status,parent_id,is_bind');
            //只打回未发货的发货单
            if ($dly_status == true){
                if ($dly['process'] == 'true' || in_array($dly['status'],array('failed', 'cancel', 'back', 'succ','return_back'))) continue;
            }
            if ($dly['parent_id'] == 0 && $dly['is_bind'] == 'true'){
                $bind[$v['delivery_id']]['delivery_id'] = $v['delivery_id'];
            }elseif ($dly['parent_id'] == 0){
                $dlyos[$v['delivery_id']][] = $v['delivery_id'];
            }else{
                $mergedly[$v['delivery_id']] = $v['delivery_id'];
                $bind[$dly['parent_id']]['items'][] = $v['delivery_id'];
            }
        }
       
        //如果是合并发货单的话
        if ($bind)
        foreach ($bind as $k => $i){
            $items = $dlyObj->getItemsByParentId($i['delivery_id'], 'array', 'delivery_id');
             
                
            if (sizeof($items) - sizeof($i['items']) < 2){
                $result = $dlyObj->splitDelivery($i['delivery_id'],'',$i['items']);
            }else {
                $result = $dlyObj->splitDelivery($i['delivery_id'], $i['items'],$i['items']);
            }
            if ($result){
                $flag   = $dlyObj->rebackDelivery($i['items'], '', $dly_status);
                
                #打回发货单失败_退出
                if($flag == false)
                {
                    foreach ($i['items'] as $i){
                        $opObj->write_log('delivery_back@ome', $i ,'发货单打回失败');
                    }
                    return false;
                }
                
                foreach ($i['items'] as $i){
                    $opObj->write_log('delivery_back@ome', $i ,'发货单打回');
                    $dlyObj->updateOrderPrintFinish($i, 1);
                }
            }
        }

        //单个发货单
        if ($dlyos)
        foreach ($dlyos as $v){
            $flag   = $dlyObj->rebackDelivery($v, '', $dly_status);
            
            #打回发货单失败_退出
            if($flag == false)
            {
                $opObj->write_log('delivery_back@ome', $v ,'发货单打回失败');
                return false;
            }
            
            $opObj->write_log('delivery_back@ome', $v ,'发货单打回');
            $dlyObj->updateOrderPrintFinish($v, 1);
        }
        return true;
    }

    public function getexportdetail($fields,$filter,$offset=0,$limit=1,$has_title=false)
    {
        //获取订单号信息
        $orders = $this->db->select("SELECT order_id,order_bn FROM sdb_ome_orders WHERE order_id in(".implode(',', $filter['order_id']).")");
        $aOrder = array();
        if($orders){
            foreach($orders as $order){
                $aOrder[$order['order_id']] = $order['order_bn'];
            }
        }

        $row_num = 1;
        foreach($filter['order_id'] as $oid){
            $objects = $this->db->select("SELECT * FROM sdb_ome_order_objects WHERE order_id =".$oid);
            if ($objects){
                foreach ($objects as $obj){
                    if ($service = kernel::service("ome.service.order.objtype.".strtolower($obj['obj_type']))){
                        $item_data = $service->process($obj);
                        if ($item_data){
                            foreach ($item_data as $itemv){
                                $orderObjRow = array();
                                $orderObjRow['*:订单号']   = mb_convert_encoding($aOrder[$obj['order_id']], 'GBK', 'UTF-8');
                                $orderObjRow['*:商品货号'] = mb_convert_encoding("\t".$itemv['bn'], 'GBK', 'UTF-8');
                                $orderObjRow['*:商品名称'] = mb_convert_encoding("\t".str_replace("\n"," ",$itemv['name']), 'GBK', 'UTF-8');
                                $orderObjRow['*:购买单位'] = mb_convert_encoding($itemv['unit']);
                                $orderObjRow['*:商品规格'] = $itemv['spec_info'] ? mb_convert_encoding(str_replace("\n"," ",$itemv['spec_info']), 'GBK', 'UTF-8'):"-";
                                $orderObjRow['*:购买数量'] = $itemv['nums'];
                                $orderObjRow['*:商品原价'] = $itemv['price'];
                                $orderObjRow['*:销售价'] = $itemv['sale_price'] / $itemv['nums'];
                                $orderObjRow['*:商品优惠金额'] = $itemv['pmt_price'];
								
								$orderObjRow['*:商品类型'] = '';
								$orderObjRow['*:商品品牌'] = '';
								$orderObjRow['*:刻字内容'] = mb_convert_encoding($itemv['message1'], 'GBK', 'UTF-8');
                                $orderObjRow['*:刻字类型'] = mb_convert_encoding($itemv['lettering_type'], 'GBK', 'UTF-8');

                                $data[$row_num] = implode(',', $orderObjRow );
                                $row_num++;
                            }
                        }
                    }else {
                        $aOrder['order_items'] = $this->db->select("SELECT * FROM sdb_ome_order_items WHERE obj_id=".$obj['obj_id']." AND `delete`='false' AND order_id =".$obj['order_id']);
                        $aOrder['order_items'] = ome_order_func::add_items_colum($aOrder['order_items']);
                        $orderRow = array();
                        $orderObjRow = array();
                        $k = 0;
                        if ($aOrder['order_items'])
                        foreach( $aOrder['order_items'] as $itemk => $itemv ){
                            $addon = unserialize($itemv['addon']);
                            $spec_info = null;
                            if(!empty($addon)){
                                foreach($addon as $val){
                                    foreach ($val as $v){
                                        $spec_info[] = $v['value'];
                                    }
                                }
                            }
                            $_typeName = $this->getTypeName($itemv['product_id']);
                            $orderObjRow = array();
                            $orderObjRow['*:订单号']   = mb_convert_encoding($aOrder[$obj['order_id']], 'GBK', 'UTF-8');
                            $orderObjRow['*:商品货号'] = mb_convert_encoding("\t".$itemv['bn'], 'GBK', 'UTF-8');
                            $orderObjRow['*:商品名称'] = mb_convert_encoding("\t".str_replace("\n"," ",$itemv['name']), 'GBK', 'UTF-8');
                            $orderObjRow['*:购买单位'] = mb_convert_encoding($itemv['unit']);
                            $orderObjRow['*:商品规格'] = $spec_info ? mb_convert_encoding(implode('||', $spec_info), 'GBK', 'UTF-8'):'-';
                            $orderObjRow['*:购买数量'] = $itemv['nums'];
                            $orderObjRow['*:商品原价'] = $itemv['price'];
                            $orderObjRow['*:销售价'] = $itemv['sale_price'] / $itemv['nums'];
                            $orderObjRow['*:商品优惠金额'] = $itemv['pmt_price'];
                            $orderObjRow['*:商品类型'] = mb_convert_encoding($_typeName['type_name'], 'GBK', 'UTF-8');
                            $orderObjRow['*:商品品牌'] = mb_convert_encoding($_typeName['brand_name'], 'GBK', 'UTF-8');
							$orderObjRow['*:刻字内容'] = mb_convert_encoding($itemv['message1'], 'GBK', 'UTF-8');
                            $orderObjRow['*:刻字类型'] = mb_convert_encoding($itemv['lettering_type'], 'GBK', 'UTF-8');
                            

                            $data[$row_num] = implode(',', $orderObjRow );
                            $row_num++;
                        }
                    }
                }
            }
        }

        //明细标题处理
        if($data && $has_title){
            $title = array(
                '*:订单号',
                '*:商品货号',
                '*:商品名称',
                '*:购买单位',
                '*:商品规格',
                '*:购买数量',
                '*:商品原价',
                '*:销售价',
                '*:商品优惠金额',
                '*:商品类型',
                '*:商品品牌',
				'*:刻字内容',
                '*:刻字类型',
            );

            foreach ((array)$title as $key => $value) {
                $title[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }

            $data[0] = implode(',', $title);
        }

        ksort($data);
        return $data;
    }
    
    /**
     +----------------------------------------------------------
     * 系统自动审单
     +----------------------------------------------------------
     * Author: ExBOY
     * Time: 2015-03-09 $
     +----------------------------------------------------------
     */
    public function auto_order_consign($order_id)
    {
        $order_ids      = array();
        $order_ids[]    = $order_id;
        
        if (empty($order_ids))
        {
            return false;
        }
        
        #是否开启_系统自动审单[默认:忽略可合并的订单 ]
        $cfg_combine    = app::get('ome')->getConf('ome.order.is_auto_combine');
        $cfg_merge      = app::get('ome')->getConf('ome.order.is_merge_order');
        if($cfg_combine != 'true')
        {
            return false;
        }
        
        //获取system账号信息
        $opinfo = kernel::single('ome_func')->get_system();
        
        //自动审单_批量日志
        $blObj  = $this->app->model('batch_log');
        
        $batch_number = count($order_ids);
        $bldata = array(
                'op_id' => $opinfo['op_id'],
                'op_name' => $opinfo['op_name'],
                'createtime' => time(),
                'batch_number' => $batch_number,
                'log_type'=> 'combine',
                'log_text'=> serialize($order_ids)
        );
        $result = $blObj->save($bldata);
        
        //自动审单_任务队列
        $push_params = array(
                'data' => array(
                        'log_text' => $bldata['log_text'],
                        'log_id' => $bldata['log_id'],
                        'task_type' => 'autorder',
                ),
                'url' => kernel::openapi_url('openapi.autotask','service')
        );
        
        kernel::single('taskmgr_interface_connecter')->push($push_params);
        
        return true;
    }

    
    /**
     * 获取分派信息
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function updateDispatchinfo($order_id)
    {
        $combineObj = new omeauto_auto_combine();

        $dispatchObj = app::get('omeauto')->model('autodispatch');
        $params = array();
        $params[] = array(
            'orders' => array (
                0 => $order_id,
            ),
        );
        $result = $combineObj->dispatch($params);

        if ($result['did'] && $result['did']>0) {
            $opData = $dispatchObj->dump($result['did'],'group_id,op_id');
            if($opData) $this->update($opData,array('order_id'=>$order_id));
        }
        
        
    }
}
?>
