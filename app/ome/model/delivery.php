<?php

class ome_mdl_delivery extends dbeav_model{
    //public $filter_use_like = true;
    var $has_many = array(
        'delivery_items' => 'delivery_items',
        'delivery_order' => 'delivery_order',
        //'dly_items_pos'  => 'dly_items_pos', TODO:和zhangxu确认，是否需要
    );
    var $defaultOrder = array('delivery_id',' ASC');
    public $deliveryOrderModel = null;

    function __construct($app){
        if($_GET['status'] == '0'){
            $opInfo = kernel::single('ome_func')->getDesktopUser();
            if(app::get('ome')->getConf('delivery.bycreatetime'.$opInfo['op_id']) == 1){
                $this->defaultOrder = array('order_createtime',' ASC');
            }else{
                $this->defaultOrder = array('idx_split',' ASC');
            }
        }else{
            $this->defaultOrder = array('delivery_id',' DESC');
        }
        parent::__construct($app);
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        
        if(isset($filter['extend_delivery_id'])){
            $where .= ' OR delivery_id IN ('.implode(',', $filter['extend_delivery_id']).')';
            unset($filter['extend_delivery_id']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = &$this->app->model("members");
            $rows = $memberObj->getList('member_id',array('uname|has'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }
            $where .= '  AND member_id IN ('.implode(',', $memberId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id',array('order_bn'=>$filter['order_bn']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }

            $deliOrderObj = &$this->app->model("delivery_order");
            $rows = $deliOrderObj->getList('delivery_id',array('order_id'=>$orderId));
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }

            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['order_bn']);
        }
        if(isset($filter['product_bn'])){
            $itemsObj = &$this->app->model("delivery_items");
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
            $itemsObj = &$this->app->model("delivery_items");
            $rows = $itemsObj->getDeliveryIdByPbarcode($filter['product_barcode']);
            $deliveryId[] = 0;
            foreach($rows as $row){
                $deliveryId[] = $row['delivery_id'];
            }
            $where .= '  AND delivery_id IN ('.implode(',', $deliveryId).')';
            unset($filter['product_barcode']);
        }
        if(isset($filter['logi_no_ext'])){
            $logObj = &$this->app->model("delivery_log");
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

        if(isset($filter['no_logi_no']) && $filter['no_logi_no'] == 'NULL'){
            $where .= "AND logi_no is null";
            unset($filter['no_logi_no']);
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

    public function getParentIdBybn($delivery_bn){
        $sql = 'SELECT parent_id from sdb_ome_delivery WHERE parent_id>0 and delivery_bn like \'%'.$delivery_bn.'%\' GROUP BY parent_id';
        $rows = $this->db->select($sql);
        return $rows;
    }

    /**
     * 取得物流公司名称
     */
    function getLogi_name(){
        $sql = " SELECT logi_id,logi_name FROM sdb_ome_delivery GROUP BY logi_id ";
        $row = $this->db->select($sql);
        return $row;
    }

    /**
     * 判断是否已有此物流单号，检验前物流单号可以任意修改
     *
     * @param string $logi_no
     * @param int $dly_id
     * @return boolean
     */
    function existExpressNo($logi_no, $dly_id=0){
        
        if($logi_no){
            $count = $this->db->selectRow('select delivery_id from sdb_ome_delivery where  logi_no="'.$logi_no.'" AND `status` in(\'progress\',\'succ\',\'progress\',\'ready\',\'stop\',\'failed\')');

            //检测delivery_bill是否存在快递单号 wujian@shopex.cn 2012年3月13日
            $billrow = $this->db->selectRow('select delivery_id from sdb_ome_delivery_bill where logi_no="'.$logi_no.'"');
            
            if (($count && $count['delivery_id']!=$dly_id) || $billrow) {
                unset($count);
                unset($billrow);
                return true;
            }
        }
    }

    /**
     * 判断是否已有此物流单号，检验前物流单号可以任意修改(反向检测)
     * wujian@shopex.cn
     * 2012年3月22日
     */
    function existExpressNoBill($logi_no, $dly_id=0, $billid=0){
        //更新，conut走架构
        $filter['logi_no'] = $logi_no;
        $filter['delivery_id|noequal'] = $dly_id;//不等于，见dbeav：filter
        $filter['verify'] = 'true';
        $filter['status'] = array('progress','succ');

        $count = $this->count($filter);
        //检测delivery_bill是否存在快递单号 wujian@shopex.cn 2012年3月13日
        $billrow = $this->db->selectRow('select * from sdb_ome_delivery_bill where log_id!='.$billid.' and logi_no="'.$logi_no.'"');
        if ($count > 0 || $billrow) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 判断发货单是否已合并过，
     *
     * @param array() $dly_ids
     * @return boolean
     */
    function existIsMerge($dly_ids){
        $ids = implode(',', $dly_ids);
        //更新，conut走架构
        $filter['delivery_id|in'] = $ids;
        $filter['parent_id|noequal'] = 0;

        $count = $this->count($filter);
        if ($count > 0)
            return true;
        return false;
    }

    /**
     * 判断发货单是否为合并后的发货单
     *
     * @param array() $dly_ids
     * @return boolean
     */
    function existIsMerge_parent($dly_id){
        $sql = "SELECT is_bind FROM sdb_ome_delivery where delivery_id = {$dly_id}";
        $row = $this->db->select($sql);
        if($row[0]['is_bind'] == 'true'){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新发货单详情
     *
     * @param array() $dly
     * @param array() $delivery_id
     * @return boolean
     */
    function updateDelivery($dly, $delivery_id){
        $result = $this->update($dly, $delivery_id);
        if ($result){
            $tmp_deliveryInfo = $this->dump($delivery_id,'is_bind');
            if($tmp_deliveryInfo['is_bind'] == 'true'){
                $delivery_ids = $this->getItemsByParentId($delivery_id,'array');
                if ($delivery_ids){
                    $service_object = kernel::servicelist('service.delivery');
                    foreach($delivery_ids as $v){
                        foreach($service_object as $object=>$instance){
                            if(method_exists($instance,'update_logistics_info')){
                                $instance->update_logistics_info($v);
                            }
                        }
                    }
                }
            }else{
                //更新发货物流信息
                foreach(kernel::servicelist('service.delivery') as $object=>$instance){
                    if(method_exists($instance,'update_logistics_info')){
                        $instance->update_logistics_info($delivery_id['delivery_id']);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 记录商品当天仓库的发货数量
     *
     * @param int $branch_id
     * @param int $number
     * @param int $product_id
     *
     * @return boolean
     */
    function createStockChangeLog($branch_id,$number,$product_id){
        $branchObj = &$this->app->model('branch');
        $stock_clObj = &$this->app->model('stock_change_log');
        $productObj = &$this->app->model('products');
        $branch = $branchObj->dump($branch_id);
        $day = $branch['stock_safe_day'];
        $time = $branch['stock_safe_time'];

        $log_bn = date('Ymd');
        $now = time();
        $todaylog = $stock_clObj->dump(array('log_bn'=>$log_bn,'product_id'=>$product_id,'branch_id'=>$branch_id));
        if ($todaylog){
            $log['log_id'] = $todaylog['log_id'];
            $log['store'] = $todaylog['store']+$number;

            $stock_clObj->save($log);
        }else {
            $log['product_id'] = $product_id;
            $log['branch_id'] = $branch_id;
            $log['log_bn'] = $log_bn;
            $log['create_time'] = time();
            $log['store'] = $number;

            $pro_info = $productObj->dump($product_id);
            $log['bn'] = $pro_info['bn'];
            $log['product_name'] = $pro_info['name'].($pro_info['spec_desc']?"(".$pro_info['spec_desc'].")":"");

            $stock_clObj->save($log);
        }
        unset($branch,$todaylog,$pro_info,$log);
        return true;
    }

    /**
     * 判断发货单对应的订单处理状态是否为取消或异常
     *
     * @param bigint $dly_id
     * @param string $is_bind
     *
     * @return boolean
     */
    function existOrderStatus($dly_id, $is_bind){
        if ($is_bind == 'true'){
            $ids = $this->getItemsByParentId($dly_id);
        }else {
            $ids = $dly_id;
        }
        //$sql = "SELECT COUNT(*) AS '_count'  FROM sdb_ome_delivery_order dord JOIN sdb_ome_orders o ON dord.order_id=o.order_id WHERE dord.delivery_id in ($ids) AND (o.process_status='cancel' OR o.abnormal='true' OR o.disabled='true') ";
        $sql = "SELECT COUNT(*) AS '_count'  FROM sdb_ome_delivery WHERE delivery_id in ($ids) AND (status='cancel' OR status='back' OR status='timeout' OR status='failed' OR disabled='true' OR pause='true' OR status='return_back') ";
        $row = $this->db->select($sql);
        if ($row[0]['_count'] > 0){
            return false;
        }else {
            return true;
        }
    }

    function existOrderPause($dly_id, $is_bind){
        if ($is_bind == 'true'){
            $ids = $this->getItemsByParentId($dly_id);
        }else {
            $ids = $dly_id;
        }
        $sql = "SELECT COUNT(*) AS '_count'  FROM sdb_ome_delivery_order dord JOIN sdb_ome_orders o ON dord.order_id=o.order_id WHERE dord.delivery_id in ($ids) AND (o.process_status='cancel' OR o.abnormal='true' OR o.disabled='true' OR o.pause='true' OR pay_status='6' OR pay_status='7' OR pay_status='5') ";
        $row = $this->db->select($sql);
        if ($row[0]['_count'] > 0){
            return false;
        }else {
            return true;
        }
    }

    /**
     * 获取与本发货单配送信息(bind_key)相同的发货单列表(父id为0)
     *
     * @param bigint $dly_id
     *
     * @return array()
     */
    function getSameKeyList($dly_id){
        $dly = $this->dump($dly_id,'bind_key');
        $filter['bind_key'] = $dly['bind_key'];
        $filter['process']  = 'false';
        $filter['status']   = array('ready','progress');
        $filter['type']     = 'normal';
        $filter['parent_id'] = '0';
        $data = $this->getList('*', $filter, 0, -1);
        foreach ($data as $key => $item){
            if ($this->existOrderStatus($item['delivery_id'], $item['is_bind']) && $this->existOrderPause($item['delivery_id'], $item['is_bind'])){
                $data[$key]['order_status'] = 'OK';
            }else{
                $data[$key]['order_status'] = 'ERROR';
            }
            if ($item['is_bind'] == 'true'){
                $data[$key]['ids'] = $this->getItemsByParentId($item['delivery_id'],'array','*');
            }
        }
        return $data;
    }

    /**
     * 利用父ID获取子发货单的ID
     *
     * @param bigint $parent_id
     *
     * @return string/array         id字符串或id数组或者id对应所有数据
     */
    function getItemsByParentId($parent_id, $return='string', $column='delivery_id'){
        $filter['parent_id'] = $parent_id;
        $rows = $this->getList($column, $filter, 0, -1);
        if (empty($rows)){
            $ids = '0';
            return $ids;
        }
        foreach ($rows as $item){
            $data[] = $item['delivery_id'];
        }
        if ($return == 'string'){
            $ids = implode(',', $data);
        }elseif ($column == 'delivery_id'){
            $ids = $data;
        }else {
            foreach ($rows as $key => $item){
                if ($this->existOrderStatus($item['delivery_id'], $item['is_bind'])  && $this->existOrderPause($item['delivery_id'], $item['is_bind'])){
                    $rows[$key]['order_status'] = 'OK';
                }else {
                    $rows[$key]['order_status'] = 'ERROR';
                }
            }
            $ids = $rows;
        }
        
        return $ids;
    }

    /**
     * 拆分发货单
     * 作用：先将大的发货单拆分掉，再将不需要拆分的子发货单合并
     *
     * @param bigint $parent_id
     * @param array  $items            需要拆分的items
     *
     * @return boolean
     */
    function splitDelivery($parent_id, $items='',$cancel_items=''){
        $filter['parent_id'] = $parent_id;
        $filter2['delivery_id'] = $parent_id;
        $dly = $this->dump($filter2);

        $branchLib = kernel::single('ome_branch');
        $eventLib = kernel::single('ome_event_trigger_delivery');
        $opObj = &$this->app->model('operation_log');
        //请求wms取消发货单
        $wms_id = $branchLib->getWmsIdById($dly['branch_id']);
        $res = $eventLib->cancel($wms_id,array('outer_delivery_bn'=>$dly['delivery_bn']),true);
        $delivery_back_log = '成功';
        if ($res['rsp'] == 'fail') {
            $delivery_back_log="失败,原因:".$res['msg'];
            $this->update_sync_cancel($parent_id,'fail');
        }
        $opObj->write_log('delivery_back@ome',$parent_id,'发货单取消:'.$delivery_back_log);
        if($res['rsp'] == 'success' || $res['rsp'] == 'succ'){
            $this->update_sync_cancel($parent_id,'succ');
            $ids = $this->getItemsByParentId($parent_id, 'array');
            
            if ($this->resumeDelivery($filter,$cancel_items)){//重置发货单
                $data['logi_no'] = null;
                $data['status'] = 'back';
                $data['delivery_id'] = $parent_id;
                $this->save($data);

                
                $logi_info = $dly['logi_no'] ? ',物流单号'.$dly['logi_no'] : '';
                if (empty($items)){
                    $opObj->write_log('delivery_split@ome', $parent_id, '拆分发货单('.$parent_id.')'.$logi_info);
                }else {
                    foreach ($items as $i){
                        $delivery = $this->dump($i, 'delivery_bn');
                        $arr_idd[] = $delivery['delivery_bn'];
                    }
                    $idd = implode(',',$arr_idd);
                    $opObj->write_log('delivery_split@ome', $parent_id, '拆分发货单('.$idd.')'.$logi_info);
                }

                if (is_array($items)) {
                    $id = array_diff($ids, $items);//获取不需要拆分的子发货单ID
                    
                    sort($id);
                    if ($id) {
                        $dly['logi_no'] = null;
                        $dly['delivery_bn'] = $this->gen_id();
                        $dly['status']          = 'ready';
                        $dly['stock_status']    = 'false';
                        $dly['deliv_status']    = 'false';
                        $dly['expre_status']    = 'false';
                        $dly['create_time'] = time();

                        if (count($id) < 2) {
                            $newdelivery_id = $id[0];
                        }else{
                            $newdelivery_id = $this->mergeDelivery($id, $dly);//重新合并不需要拆分的子发货单
                        }
                        $this->wmsdelivery_create($newdelivery_id);
                    }
                    
                }
                return true;
            }else {
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 合并发货单处理
     *
     * @param array() $dly_ids
     * @param array() $delivery => array(
                                        'logi_id' => '',
                                        'logi_name' => '',

                                        'delivery_bn' => '',
                                        'logi_no' => '',
                                        'status' => '',
                                        'stock_status' => '',
                                        'deliv_status' => '',
                                        'expre_status' => '',
     *                             );
     * $delivery的键值可以只传前两个或者都传
     *
     * @return boolean
     */
    function merge($dly_ids, $delivery=array()){
        if (!is_array($dly_ids))
            return false;
        if (count($dly_ids) < 2)
            return false;
        if ($delivery){
            if (!$delivery['logi_id'])
                return false;
            $tmp = $delivery;
            unset($delivery['logi_id']);
            unset($delivery['logi_name']);
            unset($delivery['memo']);
            if ($delivery){
                if (!$delivery['delivery_bn'])
                    return false;
                return $this->mergeDelivery($dly_ids, $tmp);
            }else {
                $dly['logi_id'] = $tmp['logi_id'];
                $dly['logi_name'] = $tmp['logi_name'];
                $dly['memo'] = $tmp['memo'];
                $dly['logi_no'] = null;
                $dly['delivery_bn'] = $this->gen_id();
                $dly['status']          = 'ready';
                $dly['stock_status']    = 'false';
                $dly['deliv_status']    = 'false';
                $dly['expre_status']    = 'false';
                return $this->mergeDelivery($dly_ids, $dly);
            }
        }else {
            $tmp = $dly_ids;
            $id = array_shift($tmp);
            unset($tmp);
            $delivery = $this->dump($id, 'logi_id,logi_name');

            $dly['logi_id'] = $delivery['logi_id'];
            $dly['logi_name'] = $delivery['logi_name'];
            $dly['logi_no'] = null;
            $dly['delivery_bn'] = $this->gen_id();
            $dly['status']          = 'ready';
            $dly['stock_status']    = 'false';
            $dly['deliv_status']    = 'false';
            $dly['expre_status']    = 'false';

            return $this->mergeDelivery($dly_ids, $dly);
        }
    }

    /**
     * 合并发货单
     *
     * @param array() $dly_ids
     *
     * @return boolean
     */
    function mergeDelivery($dly_ids, $delivery){
        if ($dly_ids && is_array($dly_ids)){
            foreach ($dly_ids as $key => $_id){
                $_dly = $this->db->selectrow("SELECT delivery_id,delivery_bn,parent_id FROM sdb_ome_delivery WHERE delivery_id=".$_id);
                if ($_dly['parent_id'] != 0){
                    trigger_error("发货单:".$_dly['delivery_bn']."已合并过", E_USER_ERROR);
                    return false;
                    //unset($dly_ids[$key]);
                }
            }
        }
        if (count($dly_ids) < 2)
            return false;

        $dly_corpObj = app::get('ome')->model('dly_corp');
        $opObj = &$this->app->model('operation_log');
        if (!is_array($dly_ids))
            return false;
        $new_ids = array();//单个小发货单ID数组
        $net_weight = 0;
        $weight = 0;
        foreach ($dly_ids as $item){
            $dly = $this->dump($item);
            //$net_weight += $dly['net_weight'];
            #合并发货单计算净重
            $weight += $dly['weight'];
            if ($dly['is_bind'] == 'true'){
                $ids = $this->getItemsByParentId($item, 'array');
                if (is_array($ids)){
                    $parents[] = $item;//大发货单ID数组
                    $new_ids = array_merge($new_ids, $ids);
                }
            }else {
                $new_ids[] = $item;
            }
        }

        #获取发货单累计重量
        foreach ($dly_ids as $dk=>$dv){
            $dlys = $this->dump($dv,'net_weight');

            if($dlys['net_weight']>0){
                $net_weight+=$dlys['net_weight'];
            }else{
                $net_weight=0;
                break;
            }
        }

        if (count($new_ids) < 2)
            return false;
        unset($dly['net_weight']);
        unset($dly['delivery_id']);
        unset($dly['verify']);
        unset($dly['cost_protect']);

        //计算合并后的大发货单的预计物流费用
        $area = $dly['consignee']['area'];
        $arrArea = explode(':', $area);
        $area_id = $arrArea[2];
        $price = $this->getDeliveryFreight($area_id,$delivery['logi_id'],$net_weight);

        if($delivery['logi_id']){
            $dly_corp = $dly_corpObj->dump($delivery['logi_id']);
            $logi_name = $dly_corp['name'];
            //计算保价费用
            $protect = $dly_corp['protect'];
            if($protect == 'true'){
                $is_protect = 'true';
                $protect_rate = $dly_corp['protect_rate'];//保价费率
                $protect_price = $protect_rate * $net_weight;
                $minprice = $dly_corp['minprice'];//最低报价费用
                if($protect_price < $minprice){
                    $cost_protect = $minprice;
                }else{
                    $cost_protect = $protect_price;
                }
            }
        }
        $dly['cost_protect'] = $cost_protect;
        $dly['is_protect'] = $is_protect ? $is_protect : 'false';
        $new_dly = $dly;//新的大发货单sdf结构
        $new_dly['memo']            = $delivery['memo'];
        $new_dly['delivery_bn']     = $delivery['delivery_bn'];
        $new_dly['net_weight']      = $net_weight;
        $new_dly['weight']          = $weight;
        $new_dly['is_bind']         = 'true';
        $new_dly['logi_no']         = $delivery['logi_no'];
        $new_dly['logi_id']         = $delivery['logi_id'];
        $new_dly['logi_name']       = $delivery['logi_name'];
        $new_dly['parent_id']       = 0;
        $new_dly['status']          = $delivery['status'];
        $new_dly['stock_status']    = $delivery['stock_status'];
        $new_dly['deliv_status']    = $delivery['deliv_status'];
        $new_dly['expre_status']    = $delivery['expre_status'];
        $new_dly['delivery_cost_expect'] = $price;

    //获取大发货单的订单创建时间 取各个发货单最小的订单创建时间
    $order_createtime = $this->getDeliveryOrderCreateTime($dly_ids);
    if($order_createtime){
        $new_dly['order_createtime'] = $order_createtime;
    }

        if ($this->save($new_dly)){//创建大发货单
            if ($parents && is_array($parents))
            foreach ($parents as $p_id){
                $this->splitDelivery($p_id);
            }
            foreach ($new_ids as $id){
                $tmp_dly = array(
                    'delivery_id'=>$id,
                    'logi_no'=>null,
                    'parent_id'=>$new_dly['delivery_id']
                );
                $this->save($tmp_dly);
            }
            $this->insertParentItemByItems($new_dly['delivery_id'], $new_ids, $dly['branch_id']);//新增大发货单与小发货单的明细关联
            $this->insertParentOrderByItems($new_dly['delivery_id'], $new_ids);
            $this->insertParentItemDetailByItemsDetail($new_dly['delivery_id'], $new_ids);//2011.03.15新增（为发货单详情绑定订单商品关联）
            ////////////////////////////////为合并后的发货单生成统计字段///////////////////////////////////
            $bns = array();
            $totalNum = 0;
            $diObj = &$this->app->model('delivery_items');
            $dis = $diObj->getList('*', array('delivery_id'=>$new_dly['delivery_id']), 0, -1);
            foreach($dis as $v){
                $totalNum += $v['number'];
                $bns[$v['product_id']] = $v['bn'];
            }
            ksort($bns);
            //11.25新增
            $data['skuNum']     = count($dis);
            $data['itemNum']    = $totalNum;
            $data['bnsContent'] = serialize($bns);
            $data['idx_split']  = $data['skuNum'] * 10000000000 + sprintf("%u", crc32($data['bnsContent']));

            $data['delivery_id']     = $new_dly['delivery_id'];
            $this->save($data);
            /////////////////////////////////////////////////////////////////////////////////////////////////////

            $merge_dly = $this->getList("delivery_bn",array('delivery_id'=>$new_ids),0,-1);
            foreach($merge_dly as $v){
                $tmp_idd[] = $v['delivery_bn'];
            }
            $idd = implode(",",$tmp_idd);

            $opObj->write_log('delivery_merge@ome', $new_dly['delivery_id'], '5合并发货单('.$idd.')');
            return $new_dly['delivery_id'];
        }
        return false;
    }

    /**
     * 重置发货单
     * 使用：将发货单状态设置为初始状态 ，物流运单号设置为空
     *
     * @param array() $filter
     *
     * @return boolean
     */
    function resumeDelivery($filter,$cancel_items){
        $rows = $this->getList('*', $filter, 0, -1);
        $oOperation_log = &app::get('ome')->model('operation_log');
        if (empty($rows))
            return false;

        $dly_itemObj = &$this->app->model('delivery_items');
        
        foreach ($rows as $r){
            $data['parent_id']  = 0;
            $data['logi_no']    = NULL;
            if ($r['status'] == 'cancel' || $r['status'] == 'back'){
                $data['status'] = $r['status'];
            }else {
                if (empty($cancel_items) || ($cancel_items && !in_array($r['delivery_id'],$cancel_items))) {
                    $data['status']         = 'ready';
                    $data['verify']         = 'false';
                    $data['stock_status']   = 'false';
                    $data['deliv_status']   = 'false';
                    $data['expre_status']   = 'false';
                    $dly_itemObj->resumeItemsByDeliveryId($r['delivery_id']);//重置每个发货单的校验
                    $filter2['delivery_id'] = $r['delivery_id'];
                }
            }
             if ($cancel_items && in_array($r['delivery_id'],$cancel_items)) {
                unset($data['parent_id']);
            }
            if ($r['delivery_id']) {
                $filter2['delivery_id'] = $r['delivery_id'];
                $this->update($data, $filter2);

            }            


        }
        $data = null;
        return true;
    }

    /**
     * 调用model:delivery_items中的insertParentItemByItems方法
     * 作用：将子发货单的关联items复制给大发货单
     *
     * @param bigint $parent_id
     * @param array() $items
     *
     * @return boolean
     */
    function insertParentItemByItems($parent_id, $items, $branch_id){
        $dly_itemObj = &$this->app->model('delivery_items');
        return $dly_itemObj->insertParentItemByItems($parent_id, $items, $branch_id);
    }

    /**
     * 调用model:delivery_order中的insertParentOrderByItems方法
     * 作用：将子发货单的关联order复制给大发货单
     *
     * @param bigint $parent_id
     * @param array() $items
     *
     * @return boolean
     */
    function insertParentOrderByItems($parent_id, $items){
        $dly_orderObj = &$this->app->model('delivery_order');
        return $dly_orderObj->insertParentOrderByItems($parent_id, $items);
    }

    function insertParentItemDetailByItemsDetail($parent_id, $items){
        $didObj = &$this->app->model('delivery_items_detail');
        return $didObj->insertParentItemDetailByItemsDetail($parent_id, $items);
    }

    /**
     * 通过一个发货单号或一个发货单号数组，获取这些发货单号对应的订单号
     *
     * @param string/array() $dly_ids
     *
     * @return array()                  订单ID的数组(自然下标)
     */
    function getOrderIdByDeliveryId($dly_ids){
        $dly_orderObj = &$this->app->model('delivery_order');
        $filter['delivery_id'] = $dly_ids;

        $data = $dly_orderObj->getList('order_id', $filter);
        foreach ($data as $item){
            $ids[] = $item['order_id'];
        }
        return $ids;
    }
    /**
     * 根据发货单id获取订对应的订单号
     *
     * @param  void
     * @return void
     * @author
     **/
    public function getOrderBnbyDeliveryId($delivery_id)
    {
        $sql = 'SELECT order_bn,pay_status
                FROM sdb_ome_orders AS o
                LEFT JOIN  sdb_ome_delivery_order AS deli ON o.order_id = deli.order_id
                WHERE delivery_id='.$delivery_id;
        $delivery = kernel::database()->select($sql);
        return $delivery[0];
    }
    /**
     * 通过发货单号获取发货单详情关联表的对应记录
     *
     * @param bigint $dly_id
     *
     * @return array()
     */
    function getItemsByDeliveryId($dly_id){
        $dly_itemObj = &$this->app->model('delivery_items');
        $rows = $dly_itemObj->getList('*', array('delivery_id' => $dly_id),0,-1);

        return $rows;
    }

    /**
     * 通过仓库ID和货品ID，获取其所有货位的商品数量信息
     *
     * @param int $branch_id
     * @param int $product_id
     *
     * @return array()
     * ss备注：些方法已经没用，可以删除
     */
    function getBranchProductPosNum($branch_id, $product_id){
        //$branch_pposObj = &$this->app->model('branch_product_pos');
        //$rows = $branch_pposObj->getList('*', array('branch_id' => $branch_id, 'product_id' => $product_id));
        $sql = "SELECT * FROM sdb_ome_branch_product_pos dpp
                               JOIN sdb_ome_branch_pos dp
                                   ON dpp.pos_id=dp.pos_id
                               WHERE dp.branch_id='$branch_id' AND dpp.product_id='$product_id'";

        return $this->db->select($sql);
    }

    /**
     * 通过发货单的itemId获取其相应的item货位商品数量信息
     *
     * @param int $item_id
     *
     * @return array()
     * ss备注：些方法已经没用，可以删除
     */
    function getItemPosByItemId($item_id){
        $dly_iposObj = &$this->app->model('dly_items_pos');
        $branch_posObj = &$this->app->model('branch_pos');
        $rows = $dly_iposObj->getList('*', array('item_id' => $item_id));
        foreach ($rows as $key => $item){//循环取出货位名称
            $pos = $branch_posObj->dump($item['pos_id']);
            $rows[$key]['store_position'] = $pos['store_position'];
        }
        return $rows;
    }

    /**
     * 通过delivery_id(可以为数组)删除发货单订单关联表中的记录
     *
     * @param bigint/array() $dly_id
     *
     * @return boolean
     */
    function deleteDeliveryItemDetailByDeliveryId($dly_id){
        if ($dly_id){
            $didObj = &$this->app->model('delivery_items_detail');
            $filter['delivery_id'] = $dly_id;
            return $didObj->delete($filter);
        }
        return false;
    }

    /**
     * 通过delivery_id(可以为数组)删除发货单订单关联表中的记录
     *
     * @param bigint/array() $dly_id
     *
     * @return boolean
     */
    function deleteDeliveryOrderByDeliveryId($dly_id){
        if ($dly_id){
            $dly_orderObj = &$this->app->model('delivery_order');
            $filter['delivery_id'] = $dly_id;
            return $dly_orderObj->delete($filter);
        }
        return false;
    }

    /**
     * 通过delivery_id(可以为数组)删除发货单明细关联表中的记录
     *
     * @param bigint/array() $dly_id
     *
     * @return boolean
     */
    function deleteDeliveryItemsByDeliveryId($dly_id){
        if ($dly_id){
            $dly_itemObj = &$this->app->model('delivery_items');
            $filter['delivery_id'] = $dly_id;
            return $dly_itemObj->delete($filter);
        }
        return false;
    }

    /**
     * 删除发货单相关的货位信息（只有合并过的发货单才会触发此方法）
     *
     * @param int $dly_id
     *
     * @return boolean
     * ss备注：此方法可以被删除
     */
    function deleteDeliveryItemsPosByDeliveryId($dly_id){
        if ($dly_id){
            $dly_itemObj = &$this->app->model('delivery_items');
            $dly_itemPosObj = &$this->app->model('dly_items_pos');
            foreach ($dly_id as $id){
                $rows = $dly_itemObj->getList('*', array('delivery_id'=>$id), 0, -1);
                if (empty($rows))
                    return false;
                foreach ($rows as $row){
                    $filter['item_id'] = $row['item_id'];
                    $dly_itemPosObj->delete($filter);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 打回发货单操作
     *
     * @param array() $dly_ids
     * @param string $memo
     * @param boolean $reback_status 打回状态，默认为false:打回所有发货单;true：只打回未发货的发货单
     * @return boolean
     */
    function rebackDelivery($dly_ids, $memo, $dly_status=false){
        #[发货配置]是否启动拆单 ExBOY
        $dlyObj = &app::get('ome')->model("delivery");
        $split_seting   = $dlyObj->get_delivery_seting();
        
        $flag   = true;//[拆单]发货单打回_成功标志 ExBOY
        
        if (is_array($dly_ids)){
            $ids = $dly_ids;
        }else {
            $ids[] = $dly_ids;
        }
        $data['memo']        = $memo;
        $data['status']      = 'back';
        $data['logi_no'] = null;
        $orderObj = &app::get('ome')->model('orders');
        $delivery_itemsObj = &app::get('ome')->model('delivery_items');
        $branch_productObj = &app::get('ome')->model('branch_product');
        $deliveryBillObj = &app::get('ome')->model('delivery_bill');
        $combineObj = new omeauto_auto_combine();
        $dispatchObj = app::get('omeauto')->model('autodispatch');
        $oOperation_log = &app::get('ome')->model('operation_log');
        $branchLib = kernel::single('ome_branch');
        $eventLib = kernel::single('ome_event_trigger_delivery');
 
        foreach ($ids as $item){
            $res = array('rsp'=>'false');

            $deliveryInfo = $this->dump($item,'process, status, delivery_bn, branch_id, is_bind, parent_id');

            //本地先检查是否可操作
            if ($deliveryInfo['process'] == 'true' || in_array($deliveryInfo['status'],array('failed', 'cancel', 'back', 'succ','return_back'))){
                continue;
            }
            
            //如果是主发货单
            if($deliveryInfo['parent_id'] == 0){
                //请求wms取消发货单
                $wms_id = $branchLib->getWmsIdById($deliveryInfo['branch_id']);
                $res = $eventLib->cancel($wms_id,array('outer_delivery_bn'=>$deliveryInfo['delivery_bn']),true);
                //发货单取消失败
                if ($res['rsp'] == 'fail') {
                    $oOperation_log->write_log('delivery_back@ome',$item,'发货单取消失败,原因'.$res['msg']);
                    $this->update_sync_cancel($item,'fail');

                }else{
                    $this->update_sync_cancel($item,'succ');
                    $oOperation_log->write_log('delivery_back@ome',$item,'发货单取消成功');
                }
            }else{
                $res['rsp'] = 'success';
            }
            
            if($res['rsp'] == 'success' || $res['rsp'] == 'succ'){
                
                $data['delivery_id'] = $item;

                //增加branch_product释放冻结库存
                $branch_id = $this->getList('branch_id',array('delivery_id'=>$data['delivery_id']),0,-1);
                $product_ids = $delivery_itemsObj->getList('product_id,number',array('delivery_id'=>$data['delivery_id']),0,-1);
                foreach($product_ids as $key=>$v){
                    $branch_productObj->unfreez($branch_id['0']['branch_id'],$v['product_id'],$v['number']);
                }

                //将发货单状态更新为打回并记录备注
                if ($this->save($data)){
                    if (!$dly_status){
                        $order = $this->getOrderIdByDeliveryId($item);
                        $orderInfo = $orderObj->dump($order[0], 'order_id,order_bn,order_combine_idx,order_combine_hash');
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
                        if ($this->validDeiveryByOrderId($order[0]))
                        {
                            $opData['process_status']   = 'splitting';
                            $opData['pause']            = 'false';//因部分拆分后订单"基本信息"Tab没有操作按扭
                            unset($opData['group_id'], $opData['op_id']);//部分拆分不重新分派
                        }
                        
                        $orderObj->update($opData,array('order_id'=>$order[0]));
                    }
                }

                //打回发货单状态同步
                if ($service_delivery = kernel::servicelist('service.delivery')){
                    foreach($service_delivery as $object=>$instance){
                        if(method_exists($instance, 'update_status')){
                            $instance->update_status($data['delivery_id'], 'cancel');
                        }
                    }
                }
            }else{
                $flag   = false;//打回发货单失败 ExBOY
                
                continue;
            }
        }
        
        return (!empty($split_seting) ? $flag : true);//[拆单]返回$flag
    }

    //判断订单确认状态是否为部分拆分，现拆单已使用!
    function validDeiveryByOrderId($order_id){
        $order_id = intval($order_id);
        if($order_id==0) return false;
        $sql = "SELECT COUNT(*) AS _count FROM sdb_ome_delivery_order do
                                JOIN sdb_ome_delivery d
                                    ON do.delivery_id=d.delivery_id
                                WHERE do.order_id=$order_id
                                    AND d.parent_id=0
                                    AND d.status IN ('ready','progress','succ', 'stop')
                                    AND d.disabled='false'";
        $row = $this->db->selectrow($sql);
        if ($row['_count'] > 0)
            return true;
        return false;
    }

    function getCorpsByBranchId(){

    }

    /**
     * 获取发货人信息
     *
     * @param int $dly_id
     *
     * @return array()
     */
    function getShopInfo($shop_id){
        static $shops;

        if ($shops[$shop_id]) return $shops[$shop_id];

        $shopObj = &$this->app->model("shop");
        $shops[$shop_id] = $shopObj->dump($shop_id);

        return $shops[$shop_id];
    }

    /**
     * 统计商品数量
     *
     * @param array() $dly_ids
     *
     * @return array()
     */
    function countProduct($dly_ids=null){
        if ($dly_ids){
        $sql = "SELECT bn,product_name,SUM(number) AS 'count' FROM sdb_ome_delivery_items
                                                              WHERE delivery_id IN ($dly_ids) AND number!=0 GROUP BY bn";

        $data = $this->db->select($sql);
        }
        return $data;
    }

    /**
     * 统计商品在每个货位上的数量
     *
     * @param array() $dly_ids
     * @param string $bn
     *
     * @return array()
     * ss备注：此方法已经没用，可以删除
     */
    function countProductPos($dly_ids=null, $bn=null){
        if ($dly_ids){
        $sql = "SELECT bp.store_position,SUM(dip.num) AS 'count' FROM sdb_ome_delivery_items di
                                JOIN sdb_ome_dly_items_pos dip
                                    ON di.item_id=dip.item_id
                                JOIN sdb_ome_branch_pos bp
                                    ON dip.pos_id=bp.pos_id
                                WHERE di.delivery_id IN ($dly_ids)
                                    AND di.number!=0
                                    AND dip.num!=0 AND di.bn='$bn'
                                GROUP BY di.bn,bp.store_position";

        $rows = $this->db->select($sql);
        }
        return $rows;
    }

    function getProductPosByDeliveryId($dly_id=null){
        if ($dly_id){
            // 发货单对应的仓库id
            $branch_id = $this->dump($dly_id,'branch_id');
        $sql = "SELECT di.bn,di.product_name,di.product_id,p.barcode,p.weight,p.unit,
                        di.number,delivery_id,p.price,p.goods_id,
                        p.spec_info,p.name,p.picurl, bp.store_position FROM sdb_ome_delivery_items di
                                JOIN sdb_ome_products p
                                    ON p.product_id=di.product_id
                                LEFT JOIN (
                                SELECT bpp.*
                                FROM (
                                SELECT ss.pos_id,ss.product_id
                                FROM sdb_ome_branch_product_pos as ss LEFT JOIN sdb_ome_branch_pos bss on ss.pos_id=bss.pos_id
                                        WHERE ss.branch_id=".$branch_id['branch_id']." AND bss.pos_id!=''
                                )bpp
                                GROUP BY bpp.product_id
                                )bb
                                ON bb.product_id = di.product_id
                                LEFT JOIN sdb_ome_branch_pos bp ON bp.pos_id = bb.pos_id
                                WHERE di.delivery_id = $dly_id
                                    AND di.number != 0";

        $rows = $this->db->select($sql);
        }
        return $rows;
    }

    function getProductPosInfo($dly_id='',$branch_id=''){
        if ($dly_id && $branch_id){
            $sql = "SELECT di.bn,di.product_name,di.product_id,p.barcode,p.weight,p.unit,
                        di.number,delivery_id,p.price,p.goods_id,
                        p.spec_info,p.name,p.picurl, bp.store_position FROM sdb_ome_delivery_items di
                                JOIN sdb_ome_products p
                                    ON p.product_id=di.product_id
                                LEFT JOIN (
                                SELECT bpp.*
                                FROM (
                                SELECT ss.pos_id,ss.product_id
                                FROM sdb_ome_branch_product_pos as ss LEFT JOIN sdb_ome_branch_pos bss on ss.pos_id=bss.pos_id
                                        WHERE ss.branch_id=".$branch_id." AND bss.pos_id!=''
                                )bpp
                                GROUP BY bpp.product_id
                                )bb
                                ON bb.product_id = di.product_id
                                LEFT JOIN sdb_ome_branch_pos bp ON bp.pos_id = bb.pos_id
                                WHERE di.delivery_id = $dly_id
                                    AND di.number != 0";
            $rows = $this->db->select($sql);
        }
        return $rows;
    }

    function getOrderMemoByDeliveryId($dly_ids=null){
        if ($dly_ids){
            $sql = "SELECT o.custom_mark FROM sdb_ome_delivery_order do
                                    JOIN sdb_ome_orders o
                                        ON do.order_id=o.order_id
                                    WHERE do.delivery_id IN ($dly_ids)
                                        GROUP BY do.order_id ";
            $rows = $this->db->select($sql);
            $memo = array();
            if ($rows){
                foreach ($rows as $v)
                $memo[] = unserialize($v['custom_mark']);
            }
            return serialize($memo);
        }
    }

    /**
     * 统计订单商品的发货数量
     *
     * @param int $order_id
     *
     * @return int
     */
    function countOrderSendNumber($order_id){
        $sql = "SELECT COUNT(*) AS 'total' FROM sdb_ome_order_items WHERE order_id = '$order_id' AND nums!=sendnum AND `delete`='false'";
        $item_num = $this->db->selectrow($sql);
        return $item_num['total'];
    }

    /**
     * 更新商品表商品数量
     *
     * @param int $num
     * @param int $product_id
     *
     * @return boolean
     *
    function updateProduct($num, $product_id){
        $oProducts = &$this->app->model("products");
        $oProducts->chg_product_store($product_id,$num,"-");
        $oProducts->chg_product_store_freeze($product_id,$num,"-");
    }

    /**
     * 更新仓库商品表商品数量
     *
     * @param int $num
     * @param int $product_id
     * @param int $branch_id
     *
     * @return boolean
     *
    function updateBranchProduct($num, $product_id, $branch_id){
        $now = time();
        //$sql = "UPDATE sdb_ome_branch_product SET store=store-$num,store_freeze=store_freeze-$num,last_modified=$now WHERE branch_id='$branch_id' AND product_id='$product_id'";
        //暂时不在branch_product上使用冻结库存
        $sql = "UPDATE sdb_ome_branch_product SET store=store-$num,last_modified=$now WHERE branch_id='$branch_id' AND product_id='$product_id'";
        //echo $sql;
        return $this->db->exec($sql);//扣减branch_product表
    }

    /**
     * 更新仓库商品货位表商品数量
     *
     * @param int $num
     * @param int $product_id
     * @param int $pos_id
     *
     * @return boolean
     *
    function updateBranchProductPos($num, $product_id, $pos_id){
         $sql = "UPDATE sdb_ome_branch_product_pos SET store=store-$num WHERE pos_id='$pos_id' AND product_id='$product_id'";
         //echo $sql;
         return $this->db->exec($sql);//扣减branch_product_pos表
    }/*

     /*
     * 生成发货单号
     *
     *
     * @return bigint           发货单号
     */
    function gen_id(){
        $cManage = &$this->app->model("concurrent");
        $prefix = date("ymd").'11';
        $sqlString = "SELECT MAX(delivery_bn) AS maxno FROM sdb_ome_delivery WHERE delivery_bn LIKE '".$prefix."%'";
        $aRet = $this->db->selectrow($sqlString);
        if(is_null($aRet['maxno'])){
            $aRet['maxno'] = 0;
            $maxno = 0;
        }else
            $maxno = substr($aRet['maxno'], -5);

        do{
            $maxno += 1;
            if ($maxno==100000){
                break;
            }
            $maxno = str_pad($maxno,5,'0',STR_PAD_LEFT);

            $sign = $prefix.$maxno;

            if($cManage->is_pass($sign,'delivery')){
                break;
            }
        }while(true);

        return $sign;
    }

    /*
     * 根据订单id获取发货单信息
     *
     * @param string $cols
     * @param bigint $order_id 订单id
     *
     * @return array $delivery 发货单数组
     */

    function getDeliveryByOrder($cols="*",$order_id){
        $delivery_ids = $this->getDeliverIdByOrderId($order_id);
        if($delivery_ids){
            $f_status = array('ready','progress','succ');
            $delivery = $this->getList($cols,array('delivery_id'=>$delivery_ids,'status'=>$f_status),0,-1);
            if($delivery){
                foreach($delivery as $k=>$v){
                    if(isset($v['logi_id'])){
                        $dly_corp = $this->db->selectrow("SELECT * FROM sdb_ome_dly_corp WHERE disabled='false' AND corp_id=".intval($v['logi_id']));
                        $delivery[$k]['request_url'] = $dly_corp['website'];//TODO: 等request_url完善后使用request_url
                        $delivery[$k]['logi_code'] = $dly_corp['type'];
                    }

                    if(isset($v['branch_id'])){
                      $branch = $this->db->selectrow("SELECT * FROM sdb_ome_branch WHERE disabled='false' AND branch_id=".intval($v['branch_id']));
                      $delivery[$k]['branch_name'] = $branch['name'];
                    }

                    if(isset($v['status'])){
                        if(empty($status_text))
                            $status_text = array ('succ' => '已发货','failed' => '发货失败','cancel' => '已取消','progress' => '等待配货','timeout' => '超时','ready' => '等待配货','stop' => '暂停','back' => '打回');

                        $delivery[$k]['status_text'] = $status_text[$v['status']];
                    }

                    if(isset($v['stock_status']) || isset($v['deliv_status']) || isset($v['expre_status'])){
                        if($v['stock_status'] == 'ture' && $v['deliv_status'] == 'true' && $v['expre_status'] == 'true'){
                            $delivery[$k]['print_statis'] = '已完成打印';
                        }else if($v['stock_status'] == 'false' && $v['deliv_status'] == 'false' && $v['expre_status'] == 'false'){
                            $delivery[$k]['print_status'] = '未打印';
                        }else{
                            
                            $print_status  = array();//ExBOY退发货记录[打印状态]
                            
                            if($v['stock_status'] == 'true'){
                                $print_status[] = '备货单';
                            }
                            if($v['deliv_status'] == 'true'){
                                $print_status[] = '清单';
                            }
                            if($v['expre_status'] == 'true'){
                                $print_status[] = '快递单';
                            }
                            $delivery[$k]['print_status'] = implode("/",$print_status)."已打印";
                        }
                    }
                }
                return $delivery;
            }else{
                return array();
            }
        }else{
            return array();
        }
    }

    /*
     * 根据订单id获取发货单id
     * 只获取父id是0的发货单
     *
     * @param bigint $order_id
     *
     * @return array $ids
     */

    function getDeliverIdByOrderId($order_id){
        $delivery_ids = $this->db->select("SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id={$order_id} AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' AND d.status NOT IN('failed','cancel','back','return_back')");
        $ids = array();
        if($delivery_ids){
            foreach($delivery_ids as $v){
                $ids[] = $v['delivery_id'];
            }
        }

        return $ids;
    }

    /*
     * 根据订单id获取“失败”、“取消”、“打回”的发货单id
     * 只获取父id是0的发货单
     *
     * @param bigint $order_id
     *
     * @return array $ids
     */

    function getHistoryIdByOrderId($order_id){
        $delivery_ids = $this->db->select("SELECT dord.delivery_id FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id={$order_id} AND (d.parent_id=0 OR d.is_bind='true') AND d.disabled='false' AND d.status IN('failed','cancel','back','return_back')");
        $ids = array();
        if($delivery_ids){
            foreach($delivery_ids as $v){
                $ids[] = $v['delivery_id'];
            }
        }
        return $ids;
    }

    /**
     * 根据发货单ID判断相关订单ID所有相关联的发货单是否都已打印，如果都打印，更新订单print_finish为TRUE
     * $type为0 是否更新，为1时，更新为FALSE
     *
     * @param bigint $dly_id
     * @param int $type
     *
     */
    function updateOrderPrintFinish($dly_id, $type=0){
        $orderIds = $this->getOrderIdByDeliveryId($dly_id);
        foreach ($orderIds as $id){
            if ($type == 0){
                $flag = 0;
                $dlyIds = $this->getDeliverIdByOrderId($id);
                foreach ($dlyIds as $i){
                    $dly = $this->dump($i);
                    if ($dly['stock_status'] == 'false')
                        break;
                    if ($dly['deliv_status'] == 'false')
                        break;
                    if ($dly['expre_status'] == 'false')
                        break;
                    $flag++;
                }
                if ($flag > 0){
                    $ordObj = &$this->app->model('orders');
                    $data['order_id'] = $id;
                    $data['print_finish'] = 'true';
                    $ordObj->save($data);
                }
            }elseif ($type == 1){
                $ordObj = &$this->app->model('orders');
                $data['order_id'] = $id;
                $data['print_finish'] = 'false';
                $data['print_status'] = 0;
                $data['logi_no'] = null;
                $ordObj->save($data);
            }
        }
        return true;
    }

    /*
     * 根据发货单来还原货品的冻结库存
     *
     * @param int $delivery_id 发货单id
     *
     * @return bool
     */

    function unfreez($delivery_id){
        $sdf_delivery = $this->dump($delivery_id,"delivery_id,branch_id",array('delivery_items'=>array("product_id,number")));
        if($sdf_delivery['delivery_items']){
            $oProduct = &$this->app->model("products");
            foreach($sdf_delivery['delivery_items'] as $v){
                $oProduct->unfreez($sdf_delivery['branch_id'],$v['product_id'],$v['number']);
            }
        }
    }

    /*
     * 设置发货单状态
     *
     * @param int $delivery_id
     * @param string $status 状态
     *
     * @return bool
     */

    function set_status($delivery_id,$status){
        $data = array(
           'delivery_id' => $delivery_id,
           'status' => $status,
        );
        if ($status=='cancel') {
            $data['logi_no'] = null;
        }
        return $this->save($data);
    }

    /*
     * 获取订单已生成发货单的货品数量
     *
     * @param bigint $order_id 订单id
     * @param int $product_id 货品id
     *
     * @return int
     */

    function getDeliveryFreez($order_id,$product_id){
        $sql = "SELECT SUM(di.number) AS nums FROM sdb_ome_delivery_order AS dord
                    LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                    LEFT JOIN sdb_ome_delivery_items AS di ON(di.delivery_id=d.delivery_id)
                    WHERE dord.order_id={$order_id} AND di.product_id={$product_id} AND d.parent_id=0 AND d.disabled='false' AND d.status IN('succ','failed','progress','timeout','ready','stop')";

        $ret = $this->db->selectrow($sql);

        return $ret['nums'];
    }

    /*
     * 获取发货单可以合并的依据
     *
     * @param int $shop_id 前端店铺id
     * @param int $branch_id 仓库id
     * @param string $ship_addr 收货地址
     * @param int $member_id 会员id
     * @param string $is_cod 是否活到付款 true/false
     * @param string $is_protect 是否保价 true/false
     *
     * @return string
     */

    function getBindKey($data){
        $bindkey = md5($data['shop_id'].$data['branch_id'].$data['consignee']['addr'].$data['member_id'].$data['is_cod'].$data['is_protect']);
        if ($service = kernel::service('ome.service.delivery.bindkey')){
            if (method_exists($service, 'get_bindkey')){
                $bindkey = $service->get_bindkey($data);
            }
        }

        return $bindkey;
    }

    /*
     * 新建发货单
     *
     * @param bigint $order_id 订单id
     * $order
     * @param array $ship_info 收货人相关信息
     *     array(
     *         'name' => xxx, 'area' => xxx, 'addr' => xxx, 'zip' => xxx,
     *         'telephone' => xxx, 'mobile' => xxx, 'email' => xxx,
     *     )
     * @param $split_status  拆单后订单状态  ExBOY
     *
     * @return $int $delivery_id 发货单id
     */
    function addDelivery($order_id,$delivery,$ship_info=array(),$order_items=array(),&$split_status){
        $branch_productObj = &$this->app->model("branch_product");
        $oOrder            = &$this->app->model("orders");
        $oDly_corp         = &$this->app->model("dly_corp");
        $branchObj         = &$this->app->model("branch");

        //开启添加发货单事务,锁定当前订单记录
        $this->db->exec('begin');
        // 拆分锁定
        // if ($delivery['type']!='reject') {
        //     $affect_row = $oOrder->update(array('process_status' => 'splited'),array('order_id'=>$order_id,'process_status|noequal'=>'splited'));
        //     if ($affect_row === false || $affect_row === true ) {
        //         trigger_error("发货单已生成", E_USER_ERROR);
        //         return false;
        //     }
        // }

        // $this->db->exec('select order_id from sdb_ome_orders where order_id='.$order_id.' for update');

        // $is_null = true;
        // $item_list = $oOrder->getItemBranchStore($order_id);
        // if ($item_list) {
        //     foreach ($item_list as $il){
        //         if ($il){
        //             foreach ($il as $var){
        //                 if ($var){
        //                     foreach ($var['order_items'] as $v){
        //                         if ($v['left_nums'] > 0){
        //                             $is_null = false;
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }

        //判断了是发货单的拆分还是售后(1月4日_luolongjie)
        // if ($is_null == true && $delivery['type']!='reject'){
        //     trigger_error("无商品需要拆分", E_USER_ERROR);
        //     return false;
        // }

        $order = $oOrder->dump($order_id);

        $ship_info = $delivery['consignee'] ? $delivery['consignee'] : $order['consignee'];
        // if($delivery['consignee']){
        //     $ship_info = $delivery['consignee'];
        // }else if($order_id){
        //    if (empty($ship_info)){
        //        $ship_info = $order['consignee'];
        //    }
        // }

        // $t_area = explode(":",$ship_info['area']);
        // $m_area = explode("/",$t_area[1]);

        $delivery_bn = $delivery['delivery_bn'] ? $delivery['delivery_bn'] : $this->gen_id();

        //danny_freeze_stock_log
        $GLOBALS['frst_shop_id'] = $order['shop_id'];
        $GLOBALS['frst_shop_type'] = $order['shop_type'];
        $GLOBALS['frst_order_bn'] = $order['order_bn'];
        $GLOBALS['frst_delivery_bn'] = $delivery_bn;

        if($delivery['type']!='reject'){//如果不为售后生成的发货单，才进行仓库货品的冻结 fix by danny 2012-4-26
            $delivBranch = $branchObj->getDelivBranch($delivery['branch_id']);
            $branchIds = $delivBranch[$delivery['branch_id']]['bind_conf'];
            $branchIds[] = $delivery['branch_id'];
            //增加branch_product的冻结库存
            foreach($delivery['delivery_items'] as $key=>$items){
                $sql = "SELECT sum( IF( store < store_freeze, 0, store - store_freeze ) ) AS 'has' FROM sdb_ome_branch_product WHERE product_id=".$items['product_id']." AND branch_id IN (".implode(',', $branchIds).") group by product_id";
                $branch_p = $this->db->selectrow($sql);
                if (!is_numeric($items['number'])){
                    trigger_error($items['product_name'].":请输入正确数量", E_USER_ERROR);
                    return false;
                }
                if (empty($branch_p['has']) || $branch_p['has'] == 0 ||  $branch_p['has'] < $items['number']) {
                    trigger_error($items['product_name'].":商品库存不足", E_USER_ERROR);
                    return false;
                }
                $branch_productObj->freez($delivery['branch_id'],$items['product_id'],$items['number']);
            }
        }

        $data['delivery_bn'] = $delivery_bn;
        $is_protect = $delivery['is_protect'] ? $delivery['is_protect'] : $order['shipping']['is_protect'];

        $is_cod = $delivery['is_cod'] ? $delivery['is_cod'] : $order['shipping']['is_cod'];
        if($is_cod){
            $data['is_cod'] = $is_cod;
        }

        $data['delivery'] = $delivery['delivery']?$delivery['delivery']:$order['shipping']['shipping_name'];
        $data['logi_id'] = $delivery['logi_id'];
        $data['memo'] = $delivery['memo'];
        $data['delivery_group'] = $delivery['delivery_group'];
        $data['sms_group'] = $delivery['sms_group'];
        $data['branch_id'] = $delivery['branch_id'];
        if($delivery['type']){
            $data['type'] = $delivery['type'];
        }

        //计算预计物流费用
        $weight = 0;
        if (isset($delivery['weight'])) {
            $weight = $delivery['weight'];
        } else {
            //[拆单]根据发货单中货品详细读取重量 ExBOY
            $split_seting      = $this->get_delivery_seting();
            if($split_seting)
            {
                $weight   = $this->getDeliveryWeight($order_id, $order_items);
            }
            else 
            {
                $weight   = $this->app->model('orders')->getOrderWeight($order_id);
            }
        }

        list($area_prefix,$area_chs,$area_id) = explode(':',$ship_info['area']);
        $price = $this->getDeliveryFreight($area_id,$delivery['logi_id'],$weight);
        $logi_name = "";
        if($delivery['logi_id']){
            $dly_corp = $oDly_corp->dump($delivery['logi_id']);
            $logi_name = $dly_corp['name'];
            //计算保价费用
            $protect = $dly_corp['protect'];
            if($protect == 'true'){
                $is_protect    = 'true';
                $protect_price = $dly_corp['protect_rate'] * $weight;

                $cost_protect = max($protect_price,$dly_corp['minprice']);
            }
        }

        $data['logi_name']             = $logi_name;
        $data['is_protect']            = $is_protect ? $is_protect : 'false';
        $data['create_time']           = time();
        $data['cost_protect']          = $cost_protect ? $cost_protect :'0';
        $data['net_weight']            = $weight;
        $data['delivery_cost_expect']  = $price;
        $data['member_id']             = $delivery['member_id']?$delivery['member_id']:$order['member_id'];
        $data['shop_id']               = $order['shop_id'];

        $data['delivery_items'] = $delivery['delivery_items'];
        $data['consignee'] = $ship_info;
        list($data['consignee']['province'],$data['consignee']['city'],$data['consignee']['district']) = explode('/',$area_chs);
        // $data['consignee']['province'] = $m_area[0];
        // $data['consignee']['city']     = $m_area[1];
        // $data['consignee']['district'] = $m_area[2];

        $data['order_createtime'] = ($order['paytime'] && $is_cod == 'false') ? $order['paytime'] : $order['createtime'];#付款时间为空时取创建时间
        // $data['order_createtime']      = ($is_cod == 'true') ? $order['createtime'] : $createtime;

        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $data['op_id']   = $opInfo['op_id'];
        $data['op_name'] = $opInfo['op_name'];

        $bns = array();
        $totalNum = 0;
        foreach($data['delivery_items'] as $v){
            $totalNum += $v['number'];
            $bns[$v['product_id']] = $v['bn'];
        }
        ksort($bns);
        //11.25新增
        $data['skuNum']     = count($delivery['delivery_items']);
        $data['itemNum']    = $totalNum;
        $data['bnsContent'] = serialize($bns);
        $data['idx_split']  = $data['skuNum'] * 10000000000 + sprintf("%u", crc32($data['bnsContent']));

        $data['bind_key'] = $this->getBindKey($data);
        $result = $this->save($data);

        if (!$result || !$data['delivery_id']) {
            $this->db->rollBack();
            return false;
        }


        // if($data['delivery_id']){
        //     $this->db->commit();
        // }else{
        //     $this->db->rollBack();
        //     return false;
        // }

        if ($data['delivery_id'] && !empty($order_items) && is_array($order_items)){
            $this->create_delivery_items_detail($data['delivery_id'], $order_items);
        }

        //插关联表
        if($order_id){
            $rs  = $this->db->exec('SELECT * FROM sdb_ome_delivery_order WHERE 0=1');
            $ins = array('order_id'=>$order_id,'delivery_id'=>$data['delivery_id']);
            $sql = kernel::single("base_db_tools")->getinsertsql($rs,$ins);
            $this->db->exec($sql);
        }

        //更新订单相应状态
        $this->updateOrderLogi($data['delivery_id'], $data);

        $split_error = false; 
        // 普通发货单数据进行验证
        if ($delivery['type'] != 'reject') {
            $split_status = 'splited';
            $item_list = $oOrder->getItemBranchStore($order_id);
            foreach ((array) $item_list as $il) {
                foreach ((array) $il as $var) {
                    foreach ((array) $var['order_items'] as $v)
                    {
                        #[拆单]新增"过滤已删除的商品" ExBOY
                        if ($v['left_nums'] > 0 && $v['delete'] == 'false')
                        {
                            $split_status = 'splitting';
                        }
                        if ($v['left_nums'] < 0) {
                            $split_error = true;
                            trigger_error('货号'.$v['bn'].':商品发货数量不正确left_nums为'.$v['left_nums'], E_USER_ERROR);
                        }
                    }
                }
            }
        }

        if ($split_error == true) {
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();

        //发货单创建
        foreach(kernel::servicelist('service.delivery') as $object=>$instance){
            if(method_exists($instance,'delivery')){
                $instance->delivery($data['delivery_id']);
            }
        }

        return $data['delivery_id'];
    }

    function create_delivery_items_detail($delivery_id,$order_items){
        $didObj = &$this->app->model('delivery_items_detail');
        $diObj = &$this->app->model('delivery_items');
        $oiObj = &$this->app->model('order_items');
        foreach ($order_items as $item){
            $oi = $di = $di_item = $did = array();
            $oi = $oiObj->dump($item['item_id']);
            $di = $diObj->dump(array('delivery_id'=>$delivery_id,'product_id'=>$item['product_id'],'number'=>$item['number']));
            $item_id = $di['item_id'];
            //查询是否已有

            $di_item = $this->db->selectrow('SELECT delivery_item_id FROM sdb_ome_delivery_items_detail WHERE delivery_id='.$delivery_id.' AND product_id='.$item['product_id'].' AND number='.$item['number']);
            if($di_item){
                $di_item1 = $this->db->selectrow('SELECT item_id FROM sdb_ome_delivery_items WHERE delivery_id='.$delivery_id.' AND product_id='.$item['product_id'].' AND number='.$item['number'].' AND item_id!='.$di_item['delivery_item_id']);
                $item_id = $di_item1['item_id'];
            }
            $item_price = 0;
            $item_price = $oi['sale_price']/$oi['quantity'];
            $did = array(
                'delivery_id'       => $delivery_id,
                'delivery_item_id'  => $item_id ,
                'order_id'          => $oi['order_id'],
                'order_item_id'     => $oi['item_id'],
                'order_obj_id'      => $oi['obj_id'],
                'item_type'         => $oi['item_type'],
                'product_id'        => $oi['product_id'],
                'bn'                => $oi['bn'],
                'number'            => $item['number'],
                'price'             => $item_price,
                'amount'            => $item['number']*$item_price,
            );
            $didObj->save($did);
        }
    }

    function call_delivery_api($delivery_id, $fastConsign = false) {

        $shopObj = &app::get('ome')->model('shop');
        $delivery_info = $this->dump($delivery_id,'shop_id,is_bind,logi_id');
        
        $shop_detail = $shopObj->dump($delivery_info['shop_id'], 'node_type');
        $foreground_shop_list = ome_shop_type::shop_list();
        // 第三方平台合并单，也一单一单发
        //&& !in_array($shop_detail['node_type'],$foreground_shop_list) 
        if($delivery_info['is_bind'] == 'true' ){
            
            $delivery_ids = $this->getItemsByParentId($delivery_id,'array');
            
            foreach($delivery_ids as $v){
                
                $this->format_call_delivery_api($v, $fastConsign);
            }
        }else{
            $this->format_call_delivery_api($delivery_id, $fastConsign);
        }
    }

    /**
     +----------------------------------------------------------
     * [格式化执行]根据发货配置进行回写  ExBOY 2014.07.18
     +----------------------------------------------------------
     * return   
     +----------------------------------------------------------
     */
    function format_call_delivery_api($delivery_id, $fastConsign)
    {
        /*------------------------------------------------------ */
        //-- 判断订单是否已拆单
        /*------------------------------------------------------ */
        $chk_split = $this->check_order_split($delivery_id);
        
        if($chk_split == false)
        {
            $this->_call_shipping_api($delivery_id, $fastConsign);
        }
        else
        {
            #[拆单配置]是否启动拆单_发货单回写方式  ExBOY
            $split_seting   = $this->get_delivery_seting();
            $split_type     = intval($split_seting['split_type']);
            $split_model    = intval($split_seting['split_model']);
            
            #订单所属店铺
            $orderObj          = &app::get('ome')->model('orders');
            $delivery_orderObj = &app::get('ome')->model('delivery_order');

            $dly_order  = $delivery_orderObj->getList('order_id', array('delivery_id'=>$delivery_id) ,0 ,1);
            $order_id   = $dly_order[0]['order_id'];
            
            $order_info = $orderObj->dump(array('order_id'=>$order_id), 'shop_id, shop_type');
            $shop_type  = $order_info['shop_type'];
            
            #[启动拆单]获取 已发货(未发货)的发货单  ExBOY 2014.06.26
            if($split_type)
            {
                //店铺支持发货单[随意拆分]拆单回写[支持回写多次]
                if(in_array($shop_type, array('ecos.b2c', 'ecos.dzg')))
                {
                    if($split_type == 1)
                    {
                        $get_dly_process    = $this->get_delivery_process($delivery_id, 'true');//第一次发货时,回写发货单
                    }
                    else
                    {
                        $get_dly_process    = $this->get_delivery_process($delivery_id, 'false');//全部发完货时,回写发货单
                    }
                    
                    if(empty($get_dly_process['delivery']) && empty($get_dly_process['order_items']))
                    {
                        $this->_call_shipping_api($delivery_id, $fastConsign);
                    }
                    else
                    {
                        kernel::single('ome_service_delivery')->update_status($delivery_id, 'succ', true);//单独更新发货单状态07.10
                    }
                }
                //店铺仅支持[整购买数量]拆单回写[支持回写多次]
                elseif(in_array($shop_type, array('shopex_b2b', 'taobao')))
                {
                    //按淘宝规定物流订单的子订单方式进行拆单[每次发货都回写]
                    if($split_model == 1)
                    {
                        $this->_call_shipping_api($delivery_id, $fastConsign);
                    }
                    //按sku进行拆单[回写一次]
                    else
                    {
                        if($split_type == 1)
                        {
                            $get_dly_process    = $this->get_delivery_process($delivery_id, 'true');//第一次发货时,回写发货单
                        }
                        else
                        {
                            $get_dly_process    = $this->get_delivery_process($delivery_id, 'false');//全部发完货时,回写发货单
                        }
                        
                        if(empty($get_dly_process['delivery']) && empty($get_dly_process['order_items']))
                        {
                            $this->_call_shipping_api($delivery_id, $fastConsign);
                        }
                    }
                }
                //其它平台仅支持拆单[回写一次]
                else
                {
                    if($split_type == 1)
                    {
                       $get_dly_process    = $this->get_delivery_process($delivery_id, 'true');//第一次发货时,回写发货单
                    }
                    else
                    {
                       $get_dly_process    = $this->get_delivery_process($delivery_id, 'false');//全部发完货时,回写发货单
                    }
                        
                    if(empty($get_dly_process['delivery']) && empty($get_dly_process['order_items']))
                    {
                        $this->_call_shipping_api($delivery_id, $fastConsign);
                    }
                }
            }
            else
            {
                $this->_call_shipping_api($delivery_id, $fastConsign);
            }
        }
    }

    function _call_shipping_api($delivery_id, $fastConsign) {
        $rows = $this->db->select("SELECT order_id FROM sdb_ome_delivery_order WHERE delivery_id=".$delivery_id);

        if(is_array($rows) && !empty($rows)) {
            $orderObj = &$this->app->model('orders');
            $drmInstall = app::get('drm')->is_installed();
            $delivery = $this->dump($delivery_id);
            foreach($rows as $row){
                //订单生成类型为'手工新建'、'批量导入'和'售后自建'的不作后续操作
                $order = array();
                $order = $orderObj->dump($row['order_id']);
                if($order['createway'] && in_array($order['createway'],array('local','import','after'))) {
                    return '';
                }

                //如果安装DRM模块，则发送交易库存调整请求
                if($drmInstall) {
                    $dataObj = kernel::single('drm_inventory_router','data');
                    $dataObj->save_trade($row['order_id'],$delivery['branch_id']);

                    $inventorySyncObj = kernel::single("drm_inventory_sync");
                    $inventorySyncObj->adjust_trade($row['order_id']);
                }
            }
        }

        //发货API, 如是快速发货，不产生回写操作
        if (!$fastConsign) {
            // 初步猜测由于没办法读到service 导致无法回写前端
            /*
            foreach(kernel::servicelist('service.delivery') as $object=>$instance){
                if(method_exists($instance,'delivery')){
                    $instance->delivery($delivery_id);
                }
            }*/
            kernel::single('ome_service_delivery')->delivery($delivery_id);
        }

        //更新发货物流信息
        kernel::single('ome_service_delivery')->update_logistics_info($delivery_id);

        //更新发货单状态 API
        /*
        foreach(kernel::servicelist('service.delivery') as $object=>$instance){
            if(method_exists($instance,'update_status')){
                $instance->update_status($delivery_id, '', true);
            }
        }*/
        kernel::single('ome_service_delivery')->update_status($delivery_id, '', true);
    }

    function array2xml2($data,$root='root'){
        $xml='<'.$root.'>';
        $this->_array2xml($data,$xml);
        $xml.='</'.$root.'>';
        return $xml;
    }

    function _array2xml(&$data,&$xml){
        if(is_array($data)){
            foreach($data as $k=>$v){
                if(is_numeric($k)){
                    $xml.='<item>';
                    $xml.=$this->_array2xml($v,$xml);
                    $xml.='</item>';
                }else{
                    $xml.='<'.$k.'>';
                    $xml.=$this->_array2xml($v,$xml);
                    $xml.='</'.$k.'>';
                }
            }
        }elseif(is_numeric($data)){
            $xml.=$data;
        }elseif(is_string($data)){
            $xml.='<![CDATA['.$data.']]>';
        }
    }

    function existStockIsPlus($product_id, $store, $item_id, $branch_id, &$err_msg, $bn){
        $err_msg = '';
        $branch_pObj = &$this->app->model("branch_product");
        /*$dly_ipObj = &$this->app->model("dly_items_pos");
        $branch_ppObj = &$this->app->model("branch_product_pos");
        $pObj = &$this->app->model("products");
        $dly_ip = $dly_ipObj->getList('*',array('item_id'=>$item_id),0,-1);
        if (empty($dly_ip)){
            $err_msg = $bn."：此货号商品货位关联无效";
            return false;
        }*/
        $bp = $branch_pObj->dump(array('branch_id'=>$branch_id,'product_id'=>$product_id),'store');
        if ($bp['store'] < $store){
            $err_msg = $bn."：此货号商品仓库商品数量不足";
            return false;
        }
        return true;
    }

    /*
     * 校验发货单
     */

    function verifyDelivery($dly,$auto=false){
        $dly_id = $dly['delivery_id'];
        $dly_itemObj  = &$this->app->model('delivery_items');
        $opObj        = &$this->app->model('operation_log');
        //对发货单详情进行校验完成处理
        if ($dly_itemObj->verifyItemsByDeliveryId($dly_id)){
            $delivery['delivery_id'] = $dly_id;
            $delivery['verify'] = 'true';

            if (!$this->save($delivery))
                return false;

            if($dly['is_bind'] == 'true'){
                $ids = $this->getItemsByParentId($dly_id, 'array');
                foreach ($ids as $i){
                    $dly_itemObj->verifyItemsByDeliveryId($i);
                }
            }

            //增加捡货绩效
            foreach(kernel::servicelist('tgkpi.pick') as $o){
                if(method_exists($o,'finish_pick')){
                    $o->finish_pick($dly_id);
                }
            }

            if($auto){
                $msg = '发货单校验完成(免校验)';
            }else{
                $msg = '发货单校验完成';
            }

            if (kernel::single('desktop_user')->get_id())
                $opObj->write_log('delivery_check@ome', $dly_id, $msg);
            #淘宝全链路 已捡货，已验货
            $this->sendMessageProduce($dly_id, array(8, 9));
            return true;
        }else {

            if (kernel::single('desktop_user')->get_id())
                $opObj->write_log('delivery_check@ome', $dly_id, '发货单校验未完成');
            return false;
        }
    }

    /**
     * 淘宝全链路 已验货
     */
    public function sendMessageProduce($delivery_id, $statusArr) {
        if (empty($this->deliveryOrderModel)) {
            $this->getDeliveryOrderModel();
        }
        $deliveryOrderInfo = $this->deliveryOrderModel->getList('*', array('delivery_id' => $delivery_id));
        foreach ($deliveryOrderInfo as $delivery_order) {
            foreach ($statusArr as $status) {
                kernel::single('ome_order')->sendMessageProduce($status, $delivery_order['order_id']);
            }
        }
    }
    /**
     * 发送CRM赠品日志
     * @param Int $delivery_id
     */
    public function crmSendGiftLog($delivery_id) {
        if (empty($this->deliveryOrderModel)) {
            $this->getDeliveryOrderModel();
        }
        $obj_crm_rpc = kernel::single('crm_rpc_gift');
        $deliveryOrderInfo = $this->deliveryOrderModel->getList('*', array('delivery_id' => $delivery_id));
        foreach ($deliveryOrderInfo as $delivery_order) {
            $logData = $this->getCrmGiftLog($delivery_order['order_id']);
            if ($logData) {
                $obj_crm_rpc->getGiftRuleLog($logData);
            }
        }
    }
    /**
     * 验证CRM赠品是否发送
     * @param Int $delivery_id
     */
    public function getCrmGiftLog($order_id) {
        if (!$order_id) {
            return false;
        }
        $app_type = channel_ctl_admin_channel::$appType;
        $obj_channel = app::get('channel')->model('channel');
        $node_info = $obj_channel->getList('node_id', array('channel_type' => $app_type['crm']));
        if (empty($node_info) || strlen($node_info[0]['node_id']) <= 0) {
            return false;
        }
        $crm_cfg = app::get('crm')->getConf('crm.setting.cfg');
        if (empty($crm_cfg)) {
            return false;
        }
        if ($crm_cfg['gift'] != 'on') {
            return false;
        }
        $orderObj = &app::get('ome')->model('orders');
        $order_info = $obj_channel->getOrderInfo($order_id);
        if (empty($order_info)) {
            return false;
        }
        $shop_id = $order_info['shop_id'];#店铺节点
        if (empty($crm_cfg['name'][$shop_id])) {
            return false;
        }
        #赠品数据
        $order_item_info = $obj_channel->getOrderItemInfo($order_id, 'gift');
#        $order_item_info = $obj_channel->getOrderItemInfo($order_id);
        if (empty($order_item_info)) {
            return false;
        }
        $mobile = $order_info['ship_mobile'] ? $order_info['ship_mobile'] : '';
        $tel = $order_info['ship_tel'] ? $order_info['ship_tel'] : '';
        $ship_area     = $order_info['ship_area'];
        $ship_area_arr = '';
        if ($ship_area && is_string($ship_area)) {
            $firstPos = strpos($ship_area, ':');
            $lastPos = strrpos($ship_area, ':');
            $newShipArea = substr($ship_area, $firstPos + 1, ($lastPos - $firstPos - 1));
            $ship_area_arr = explode('/', $newShipArea);
        }
        $payed = $order_info['payed'] ?  $order_info['payed'] : 0;#付款金额
        $isCod = $order_info['is_cod'] == 'true' ? 1 : 0;#是否货到付款
        $logData = array(
            'buyer_nick' => $order_info['uname'],
            'receiver_name' => $order_info['ship_name'],
            'mobile' => $mobile,
            'tel' => $tel,
            'shop_id' => $shop_id,
            'order_bn' => $order_info['order_bn'],
            'province' => $ship_area_arr[0],
            'city' => $ship_area_arr[1],
            'district' => $ship_area_arr[2],
            'total_amount' => $order_info['total_amount'],
            'payed' => $payed,
            'is_cod' => $isCod,
            'addon' => $order_item_info
        );
        return $logData;
    }

    /**
     * 获得发货单订单关联模式
     * Enter description here ...
     */
    public function getDeliveryOrderModel() {
        $this->deliveryOrderModel = &$this->app->model('delivery_order');
    }

    function queueConsign($delivery_id){
        $oQueue = &app::get('base')->model('queue');
        $queueData = array(
            'queue_title'=>'订单导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$delivery_id,
                'app' => 'ome',
                'mdl' => 'delivery'
            ),
            'worker'=>'ome_delivery_consign.run',
        );
        $oQueue->save($queueData);
    }

    /*
     * 处理订单发货数量
     */

    function consignOrderItem($delivery){
        $ord_itemObj = &$this->app->model('order_items');
        $didObj = &$this->app->model('delivery_items_detail');
        if (!empty($delivery['delivery_items'])){
            foreach ($delivery['delivery_items'] as $r){
                $filter = array(
                    'delivery_item_id' => $r['item_id'],
                    'product_id' => $r['product_id'],
                );

                $rows = $didObj->getList('item_detail_id,order_item_id,number', $filter, 0, -1);
                if ($rows){
                    foreach ($rows as $row){
                        $num = (int)$row['number'];
                        $sql = "UPDATE sdb_ome_order_items SET sendnum = IFNULL(sendnum,0)+".$num." WHERE item_id=".$row['order_item_id'];
                        $this->db->exec($sql);
                    }
                }
                unset($rows);
            }
        }
    }

    /*
     * 完成发货
     */

    function consignDelivery($dly_id, $weight=0, &$msg, $fastConsign = false)
    {
        #[拆单配置]是否启动拆单 ExBOY
        $split_seting   = $this->get_delivery_seting();
        $split_type     = intval($split_seting['split_type']);
        $ship_status    = 0;//发货状态
        
        $delivery_time = time();
        //出入库及销售单记录
        $iostock_sales_set_result = true;
        $iostock_sales_data = array();

        //获取物流费用
        $dly = $this->dump($dly_id,'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));
        $area = $dly['consignee']['area'];
        $arrArea = explode(':', $area);
        $area_id = $arrArea[2];
        $delivery_cost_actual = $this->getDeliveryFreight($area_id,$dly['logi_id'],$weight);
        $this->update(array('delivery_cost_actual'=>$delivery_cost_actual),array('delivery_id'=>$dly_id));

        $iostock_data = kernel::single('ome_iostocksales')->get_iostock_data($dly_id);
        $sales_data = kernel::single('ome_iostocksales')->get_sales_data($dly_id);

        $iostock_sales_data['iostock'] = $iostock_data;
        $iostock_sales_data['sales'] = $sales_data;
        
        #[拆单方式]过滤部分发货时,不存储销售记录  ExBOY
        if($split_type)
        {
            $iostock_sales_data['split_type']   = $split_type;
        }
        
        // 数据库事务开启
        $this->db->exec('begin');

        if ($dly['type'] == 'normal'){//如果是普通发货单走出库，售后原样寄回不走出库 dannt 2012-4-26
            $io = '0';//出入库类型：0出库1入库
            $iostock_sales_set_result = kernel::single('ome_iostocksales')->set($iostock_sales_data, $io, $msg);
        }else{
            $iostock_sales_set_result = true;
        }

        if ( $iostock_sales_set_result )
        {
            $orderObj          = &$this->app->model('orders');
            $ord_itemObj       = &$this->app->model('order_items');
            $opObj             = &$this->app->model('operation_log');
            $productObj        = &$this->app->model('products');
            $branch_productObj = &$this->app->model('branch_product');
            
            $delivery_sync     = &$this->app->model('delivery_sync');//发货单状态回写记录表  ExBOY

            if ($dly['is_bind'] == 'true'){ 
                //合并发货单，发货处理

                $ids = $this->getItemsByParentId($dly['delivery_id'],'array');
                foreach ($ids as $item){
                    $delivery = $this->dump($item,'delivery_id,type,is_cod',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));

                    $de = $delivery['delivery_order'];
                    $or = array_shift($de);
                    $ord_id = $or['order_id'];
                    if ($delivery['type'] == 'normal'){//如果不为售后生成的发货单，才进行订单发货数量的更新
                        $this->consignOrderItem($delivery);
                    }
                    $dlydata['delivery_id'] = $delivery['delivery_id'];
                    $dlydata['process']     = 'true';
                    $dlydata['status'] = 'succ';
                    $dlydata['delivery_time'] = $delivery_time;
                    $this->save($dlydata);//更新子发货单发货状态为已发货
                    $item_num = $this->countOrderSendNumber($ord_id);
                    
                    //判断订单是否全部发完货时,并过滤合并发货单中的父发货单  ExBOY
                    $get_dly_process    = $this->get_delivery_process($delivery['delivery_id'], 'false', $dly['delivery_id']);
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
                    }else {//部分发货
                        $orderdata['ship_status'] = '2';
                        $orderObj->save($orderdata);//更新订单发货状态
                    }
                    
                    //新增_发货单状态回写记录  ExBOY
                    $dly_data       = array();
                    $frst_info      = $orderObj->dump(array('order_id'=>$ord_id), 'shop_id, shop_type, order_bn');
                    
                    if(!empty($split_seting))
                    {
                        $dly_data['order_id']       = $ord_id;
                        $dly_data['order_bn']       = $frst_info['order_bn'];
                        $dly_data['delivery_id']    = $dly['delivery_id'];
                        $dly_data['delivery_bn']    = $dly['delivery_bn'];
                        $dly_data['logi_no']        = $dly['logi_no'];
                        $dly_data['logi_id']        = $dly['logi_id'];
                        $dly_data['branch_id']      = $dly['branch_id'];
                        $dly_data['status']         = $dlydata['status'];//发货状态
                        $dly_data['shop_id']        = $dly['shop_id'];
                        $dly_data['delivery_time']  = $delivery_time;
                        $dly_data['dateline']       = $delivery_time;
                        $dly_data['split_model']    = intval($split_seting['split_model']);//拆单方式
                        $dly_data['split_type']     = intval($split_seting['split_type']);//回写方式
                        
                        $delivery_sync->save($dly_data);
                    }
                    
                    unset($delivery,$dlydata,$orderdata);
                }

                $GLOBALS['frst_delivery_bn'] = $dly['delivery_bn'];
                if ($dly['type'] == 'normal'){//如果不为售后生成的发货单，才进行货品发货的冻结释放 fix by danny 2012-4-26
                    //扣减库存
                    $stock = array();
                    foreach ($dly['delivery_items'] as $dly_item){ //循环大发货单的items数据
                        $product_id = $dly_item['product_id'];
                        $branch_id = $dly['branch_id'];
                        $num = $dly_item['number'];//需要扣减的数量
                        //增加branch_product库存的数量改变
                        $branch_productObj->unfreez($branch_id,$product_id,$num);
                        $productObj->chg_product_store_freeze($product_id,$num,"-");
                        //记录商品发货数量日志
                        $this->createStockChangeLog($branch_id,$num,$product_id);
                    }
                }

                $datadly = array();
                $datadly['delivery_id']          = $dly['delivery_id'];
                $datadly['process']              = 'true';
                $datadly['status']               = 'succ';
                $datadly['weight']               = $weight;
                $datadly['delivery_time'] = $delivery_time;

                // 更新发货单发货状态为已发货
                // $this->save($datadly);
                $affect_row = $this->update($datadly,array('delivery_id' => $datadly['delivery_id'],'process' => 'false'));
                if (is_numeric($affect_row) && $affect_row > 0) {
                    $this->db->commit();
                } else {
                    $msg = '发货单[' . $dly['delivery_bn'] . ']已发货';
                    $this->db->rollBack();

                    return false;
                }

                if (kernel::single('desktop_user')->get_id())
                {
                    $delivery_bn_str    = (empty($dly['delivery_bn']) ? '' : '（发货单号：'.$dly['delivery_bn'].'）');//日志增加_发货单号 ExBOY 2014.07.15
                    $opObj->write_log('delivery_process@ome', $dly['delivery_id'], '发货单发货完成:'.$delivery_bn_str);
                }
            }else { //单个发货单处理
                $de = $dly['delivery_order'];
                $or = array_shift($de);
                $ord_id = $or['order_id'];
                if ($dly['type'] == 'normal'){//如果不为售后生成的发货单，才进行订单发货数量的更新
                    $this->consignOrderItem($dly);
                }

                $item_num = $this->countOrderSendNumber($ord_id);
                
                //判断订单是否全部发完货时 ExBOY
                $get_dly_process    = $this->get_delivery_process($dly['delivery_id'], 'false');
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
                    if ($dly['is_cod'] == 'false') {
                        $orderdata['status'] ='finish';
                    }
                    $orderdata['ship_status'] = '1';
                    
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
                $GLOBALS['frst_delivery_bn'] = $dly['delivery_bn'];
                if ($dly['type'] == 'normal'){//如果不为售后生成的发货单，才进行货品发货的冻结释放 fix by danny 2012-4-26
                    //扣减库存
                    $stock = array();
                    foreach ($dly['delivery_items'] as $dly_item){ //循环发货单的items数据
                        $product_id = $dly_item['product_id'];
                        $branch_id = $dly['branch_id'];
                        $num = $dly_item['number'];//需要扣减的数量
                        //增加branch_product库存的数量改变
                        $branch_productObj->unfreez($branch_id,$product_id,$num);
                        $productObj->chg_product_store_freeze($product_id,$num,"-");
                        //记录商品发货数量日志
                        $this->createStockChangeLog($branch_id,$num,$product_id);
                    }
                }

                $dlydata = array();
                $dlydata['delivery_id']          = $dly['delivery_id'];
                $dlydata['process']              = 'true';
                $dlydata['status']               = 'succ';
                $dlydata['weight']               = $weight;
                $dlydata['delivery_time'] = $delivery_time;


                // $this->save($dlydata);
                $affect_row = $this->update($dlydata,array('delivery_id' => $dlydata['delivery_id'],'process' => 'false'));
                if (is_numeric($affect_row) && $affect_row > 0) {
                    $this->db->commit();
                } else {
                    $msg = '发货单[' . $dly['delivery_bn'] . ']已发货';
                    $this->db->rollBack();

                    return false;
                }
                
                //新增_发货单状态回写记录  ExBOY
                if(!empty($split_seting))
                {
                    $dly_data       = array();
                    $dly_data['order_id']       = $ord_id;
                    $dly_data['order_bn']       = $frst_info['order_bn'];
                    $dly_data['delivery_id']    = $dly['delivery_id'];
                    $dly_data['delivery_bn']    = $dly['delivery_bn'];
                    $dly_data['logi_no']        = $dly['logi_no'];
                    $dly_data['logi_id']        = $dly['logi_id'];
                    $dly_data['branch_id']      = $dly['branch_id'];
                    $dly_data['status']         = $dlydata['status'];//发货状态
                    $dly_data['shop_id']        = $dly['shop_id'];
                    $dly_data['delivery_time']  = $delivery_time;
                    $dly_data['dateline']       = $delivery_time;
                    $dly_data['split_model']    = intval($split_seting['split_model']);//拆单方式
                    $dly_data['split_type']     = intval($split_seting['split_type']);//回写方式
                    
                    $delivery_sync->save($dly_data);
                }
                
                if (kernel::single('desktop_user')->get_id())
                {
                    $delivery_bn_str    = (empty($dly['delivery_bn']) ? '' : '（发货单号：'.$dly['delivery_bn'].'）');//日志增加_发货单号 ExBOY 2014.07.15
                    $opObj->write_log('delivery_process@ome', $dly['delivery_id'], '发货单发货完成:'.$delivery_bn_str);
                }
            }

            //删除批次号码段
            if ($service = kernel::servicelist('service.order')) {
                foreach ($service as $object => $instance){
                    if (method_exists($instance, 'destroy_running_no')){
                        $username = $dly['member_id'] ? $dly['member_id'] : 0;
                        $md5 = md5($dly['consignee']['addr'].$dly['consignee']['name'].$dly['consignee']['mobile']);
                        $instance->destroy_running_no($dly['shop_id'],$username,$md5);
                    }
                }
            }
            //对EMS直联电子面单作处理（以及京东360buy）(京东先回写运单号）
            if (app::get('logisticsmanager')->is_installed()) {
                $channel_type = $this->getChannelType($dly['logi_id']);
                if ($channel_type  && $channel_type == '360buy' && class_exists('logisticsmanager_service_' . $channel_type)) {
                    $channelTypeObj = kernel::single('logisticsmanager_service_' . $channel_type);
                    $channelTypeObj->delivery($dly['delivery_id']);
                }
            }
            
            //调用发货相关api，比如订单的发货状态，库存的回写，发货单的回写
            $this->call_delivery_api($dly['delivery_id'], $fastConsign);

            //对EMS直联电子面单作处理（以及京东360buy）
            if (app::get('logisticsmanager')->is_installed()) {
                $channel_type = $this->getChannelType($dly['logi_id']);
                if ($channel_type && $channel_type == 'ems' && class_exists('logisticsmanager_service_' . $channel_type)) {
                    $channelTypeObj = kernel::single('logisticsmanager_service_' . $channel_type);
                    $channelTypeObj->delivery($dly['delivery_id']);
                }
            }

            //如果有KPI考核插件，会增加发货人的考核
            if (!$fastConsign) {
                if($oKpi = kernel::service('omekpi_deliverier_incremental')){
                    if (kernel::single('desktop_user')->get_id()){
                        $opInfo = kernel::single('ome_func')->getDesktopUser();
                        $op_id = $opInfo['op_id'];
                        if(method_exists($oKpi,'deliveryIncremental')){
                            $oKpi->deliveryIncremental($op_id,$dly['delivery_id']);
                        }
                    }
                }
            }
            //如果taoexlib存在，发货短信开启的 发货的时候就发送短信
            if(kernel::service('message_setting')&&defined('APP_TOKEN')&&defined('APP_SOURCE')){
                kernel::single('taoexlib_delivery_sms')->deliverySendMessage($dly['logi_no']);
            }
            #更新拣货绩效中相关数据
            if(app::get('tgkpi')->is_installed()){
                $sql = 'select count(*) count from sdb_tgkpi_pick WHERE delivery_id ='.$dly_id;
                $_rows = $this->db->selectRow($sql);
                if($_rows['count']>0){
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    $sql= "update sdb_tgkpi_pick set pick_status='deliveryed',op_name="."'{$opInfo['op_name']}'"."  WHERE delivery_id=".$dly_id;
                    kernel::database()->exec($sql);
                }
            }
            #订阅华强宝物流信息
            $this->get_hqepay_logistics($dly_id);
            #CRM赠品日志
            $this->crmSendGiftLog($dly_id);
            #淘宝全链路 已打包，已称重，已出库
            $this->sendMessageProduce($dly_id, array(10, 11, 12));
            return true;

        }else{
            // 出入库失败，事务回滚
            $this->db->rollBack();
            return false;
        }
    }
    /**
     * 获取电子面单类型
     */
    public function getChannelType($logi_id) {
        $channel_type = '';
        if ($logi_id > 0) {
            $dlyCorpObj = &app::get('ome')->model('dly_corp');
            $dlyCorp = $dlyCorpObj->dump($logi_id,'channel_id,tmpl_type,shop_id');
            if ($dlyCorp['channel_id'] > 0) {
                $channelObj = &app::get('logisticsmanager')->model('channel');
                $channel = $channelObj->dump($dlyCorp['channel_id']);
                $channel_type = $channel['channel_type'];
            }
        }
        return $channel_type;
    }
    function getShopType($delivery_id){
        if(!$delivery_id){
            return false;
        }
        $deliveryObj       = &app::get('ome')->model('delivery');
        $delivery_orderObj = &app::get('ome')->model('delivery_order');
        $orderObj          = &app::get('ome')->model('orders');
        $shopObj           = &app::get('ome')->model('shop');
        $delivery_detail  = $deliveryObj->dump($delivery_id, 'is_bind,parent_id');
        $delivery_order   = $delivery_orderObj->dump(array('delivery_id'=>$delivery_id));
        $order_detail     = $orderObj->dump($delivery_order['order_id'], 'ship_status,shop_id');
        $shop_detail      = $shopObj->dump($order_detail['shop_id'], 'node_type');
        $shop_type        = $shop_detail['node_type'];
        return $shop_type;
    }

    function getDeliveryCost($area_id=0,$logi_id=0,$weight=0){
        if($logi_id && $logi_id>0){
            $dlyCorpObj = &$this->app->model('dly_corp');
            $corp  = $dlyCorpObj->dump($logi_id);//物流公司信息
        }

        //物流预算费用计算
        if($area_id && $area_id>0){
            $regionObj = kernel::single('eccommon_regions');
            $region = $regionObj->getOneById($area_id);
            $regionIds = explode(',', $region['region_path']);
            foreach($regionIds as $key=>$val){
                if($regionIds[$key] == '' || empty($regionIds[$key])){
                    unset($regionIds[$key]);
                }
            }
        }

        if ($corp['area_fee_conf'] && $regionIds){
            $area_fee_conf = unserialize($corp['area_fee_conf']);
            foreach($area_fee_conf as $k=>$v){
                $areaIds = array();
                $areaIds = explode(',',$v['areaGroupId']);

                if(array_intersect($areaIds, $regionIds)){
                    //如果配送地区匹配，优先使用地区设置的配送费用，及公式
                    $corp['firstprice'] = $v['firstprice'];
                    $corp['continueprice'] = $v['continueprice'];
                    $corp['dt_expressions'] = $v['dt_expressions'];
                    break;
                }
            }
        }

        if($corp['dt_expressions'] && $corp['dt_expressions'] != ''){
            $price = utils::cal_fee($corp['dt_expressions'], $weight, 0,$corp['firstprice'],$corp['continueprice']); //TODO 生成快递费用
        }else{
            $price = 0;
        }
        return $price;
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

    function getDeliveryOrderCreateTime($dly_ids){
        $str_dly_ids = implode(',', $dly_ids);
        $sql = 'SELECT order_createtime FROM sdb_ome_delivery  WHERE delivery_id IN('.$str_dly_ids.')';
        $rows = $this->db->select($sql);

        if($rows){
            $lenOrder = count($rows);
            $order_createtime = $rows[0]['order_createtime'];
            for($i=1;$i<$lenOrder;$i++){
                if(isset($rows[$i])){
                    if($order_createtime > $row[$i]['order_createtime']){
                        $order_createtime = $row[$i]['order_createtime'];
                    }
                }
            }
            return $order_createtime;
        }else{
            return false;
        }
    }

    function getAllTotalAmountByDelivery($delivery_order){
        $order_total_amount = 0;
        if(count($delivery_order)>1){//合并
            $is_vaild = true;
            foreach($delivery_order as $deli_order){
                $total_amount = $this->getTotalAmountByDelivery($deli_order['order_id'],$deli_order['delivery_id']);
                if($total_amount){
                    $order_total_amount += $total_amount;
                }else{
                     $is_vaild = false;
                     break;
                }
            }

            if(!$is_vaild){
                $order_total_amount = 0;
            }
        }else{//单张
            $delivery_order = current($delivery_order);
            $order_total_amount = $this->getTotalAmountByDelivery($delivery_order['order_id'],$delivery_order['delivery_id']);
        }

        return $order_total_amount;
    }

    function getTotalAmountByDelivery($order_id,$delivery_id){
        $order_total_amount = 0;
        $objOrders = &$this->app->model('orders');
        $order = $objOrders->order_detail($order_id);

        if($order['process_status'] == 'splited'){//已拆分
            $ids = $this->getDeliverIdByOrderId($order_id);
            if(count($ids) == 1){//发货单必须是一张
                 $order_total_amount = $order['total_amount'];
            }
        }

        return $order_total_amount;
    }

    /**
     * 根据订单ID获取发货单的商品
     *
     * @param $order_id
     * @return array
     */
    function getDeliverItemByOrderId($order_id){
        $list = $this->db->select("SELECT dt.* FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            LEFT JOIN sdb_ome_delivery_items AS dt ON d.delivery_id = dt.delivery_id
                                            WHERE dord.order_id={$order_id} AND d.is_bind='false' AND d.disabled='false' AND d.status IN ('ready','progress','succ') AND d.pause='false'");
        $new_list = array();
        foreach($list as $item){
            if (!isset($new_list[$item['delivery_id']]))
                $new_list[$item['delivery_id']] = array();
            $new_list[$item['delivery_id']][] = $item;
        }

        return $new_list;
    }

    /**
     * 根据订单ID获取发货单列表
     *
     * @param $order_id
     * @return array
     */
    function getDeliversByOrderId($order_id){
        return $this->db->select("SELECT d.* FROM sdb_ome_delivery_order AS dord
                                            LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id)
                                            WHERE dord.order_id={$order_id} AND d.is_bind='false' AND d.disabled='false' AND d.status IN ('ready','progress','succ') AND d.pause='false'");
    }

    /**
     * 统计已打印完成待校验的发货单总数
     */
    function countNoVerifyDelivery(){
        //$status_cfg = $this->app->getConf('ome.delivery.status.cfg');
        $deliCfgLib = kernel::single('ome_delivery_cfg');
        $btncombi_single = $deliCfgLib->btnCombi('single');
        $btncombi_multi = $deliCfgLib->btnCombi('multi');
        $btncombi_basic = $deliCfgLib->btnCombi();
        $filter = array(
            'parent_id' => 0,
            'expre_status' => 'true',
            'verify' => 'false',
            'disabled' => 'false',
            'pause' => 'false',
            'status' => 'progress',
        );
        $filter['print_finish'] = array(
            ''=> $btncombi_basic,
            'single' => $btncombi_single,
            'multi' => $btncombi_multi,
        );
        if($deliCfgLib->deliveryCfg == '') {
            $filter['addonSQL'] = ' logi_no IS NOT NULL ';
        }

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids) {
                $filter['branch_id'] = $branch_ids;
            }
        }

        $num = $this->count_logi_no($filter);
        return $num;
    }

    /**
     * 统计已校验待发货的发货单总数
     */
    function countNoProcessDelivery(){
        $filter = array(
            'parent_id' => 0,
//            'stock_status' => 'true',
//            'deliv_status' => 'true',
//            'expre_status' => 'true',
            'verify' => 'true',
            'disabled' => 'false',
            'pause' => 'false',
            'process' => 'false',
            'status' => 'progress'
        );

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids) {
                $filter['branch_id'] = $branch_ids;
            }
        }

        $num = $this->count($filter);
        return $num;
    }

    /**
     * 统计子物流表待发货的发货单总数
     * wujian@shopex.cn
     * 2012年3月19日
     */
    function countNoProcessDeliveryBill(){
        $filter = array(
            'parent_id' => 0,
//            'stock_status' => 'true',
//            'deliv_status' => 'true',
//            'expre_status' => 'true',
            'verify' => 'true',
            'disabled' => 'false',
            'pause' => 'false',
            'process' => 'false',
            'status' => 'progress'
        );

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids) {
                $filter['branch_id'] = $branch_ids;
            }
        }
        /*$num =0;
        //$num = $this->count($filter);
        $numarr = $this->getList('logi_number, delivery_logi_number', $filter, 0, -1);
        for($i=0;$i<=count($numarr);$i++){
            $num += $numarr[$i]['logi_number']-$numarr[$i]['delivery_logi_number'];
        }*/
        $num = $this->count($filter);

        $dataDly = $this->getList('delivery_id', $filter, 0, -1);
        $billObj = app::get('ome')->model('delivery_bill');
        foreach($dataDly as $v){
            $billFilter = array(
                            'status' => 0,
                            'delivery_id'=> $v['delivery_id']
                            );
            $num += $billObj->count($billFilter);
        }
        return $num;
    }


    function getOrderByDeliveryId($delivery_id){
        if ($delivery_id){
            $sql = "SELECT O.pay_status,O.order_bn FROM `sdb_ome_orders` as O LEFT JOIN
                `sdb_ome_delivery_order` as DO ON DO.order_id=O.order_id
                WHERE DO.delivery_id ='".$delivery_id."'";

            $rows = $this->db->selectrow($sql);
            return $rows;
        }
    }

    //从载方法 以解决 发货中未录入快递单号不能过滤的bug
//    function getlist_logi_no($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
//        if(!$cols){
//            $cols = $this->defaultCols;
//        }
//        if(!empty($this->appendCols)){
//            $cols.=','.$this->appendCols;
//        }
//        if($this->use_meta){
//             $meta_info = $this->prepare_select($cols);
//        }
//        $orderType = $orderType?$orderType:$this->defaultOrder;
//        if($filter['logi_no'] == 'NULL'){
//            unset($filter['logi_no']);
//            $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` WHERE '.$this->_filter($filter).' AND `logi_no` IS NULL';
//        }else{
//            $sql = 'SELECT '.$cols.' FROM `'.$this->table_name(true).'` WHERE '.$this->_filter($filter);
//        }
//        if ($orderType)
//            $sql.=' ORDER BY ' . (is_array($orderType) ? implode($orderType, ' ') : $orderType);
//        $data = $this->db->selectLimit($sql,$limit,$offset);
//        $this->tidy_data($data, $cols);
//        if($this->use_meta && count($meta_info['metacols']) && $data){
//            foreach($meta_info['metacols'] as $col){
//                $obj_meta = new dbeav_meta($this->table_name(true),$col,$meta_info['has_pk']);
//                $obj_meta->select($data);
//            }
//        }
//        return $data;
//    }

    //从载方法 以解决 发货中未录入快递单号不能过滤的bug
    function getlist_logi_no($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null) {
        if (!$cols) {
            $cols = $this->defaultCols;
        }
        if (!empty($this->appendCols)) {
            $cols.=',' . $this->appendCols;
        }
        if ($this->use_meta) {
            $meta_info = $this->prepare_select($cols);
        }
        $orderType = $orderType ? $orderType : $this->defaultOrder;

        if ($filter['logi_no'] == 'NULL') {
            unset($filter['logi_no']);
            $where = $this->_filter($filter) . ' AND `logi_no` IS NULL';
        } else {
            $where = $this->_filter($filter);
        }

        //增加对 idx_split 的 order 排序的支持
        $orderType = (is_array($orderType) ? implode($orderType, ' ') : $orderType);
        $table = $this->table_name(true);

        if (strpos($orderType, 'idx_split') !== false) {

            $table .= " LEFT JOIN (SELECT COUNT(idx_split) AS iNum, idx_split AS idx FROM {$table} WHERE {$where} GROUP BY idx_split ) AS i ON i.idx=idx_split";
            $orderType = str_replace('idx_split', 'skuNum, itemNum, iNum DESC, idx_split,delivery_id', $orderType);
        }

        $sql = 'SELECT ' . $cols . ' FROM ' . $table . ' WHERE ' . $where;

        if ($orderType
        )
            $sql.=' ORDER BY ' . $orderType;

        $data = $this->db->selectLimit($sql, $limit, $offset);
        $this->tidy_data($data, $cols);
        if ($this->use_meta && count($meta_info['metacols']) && $data) {
            foreach ($meta_info['metacols'] as $col) {
                $obj_meta = new dbeav_meta($this->table_name(true), $col, $meta_info['has_pk']);
                $obj_meta->select($data);
            }
        }
        return $data;
    }

    public function count_logi_no($filter=null){
        if($filter['logi_no'] == 'NULL'){
            unset($filter['logi_no']);
            $row = $this->db->select('SELECT count(*) as _count FROM `'.$this->table_name(1).'` WHERE '.$this->_filter($filter).' AND `logi_no` IS NULL');
        }else{
            $row = $this->db->select('SELECT count(*) as _count FROM `'.$this->table_name(1).'` WHERE '.$this->_filter($filter));
        }
        return intval($row[0]['_count']);
    }

    public function getPrintStockPrice($ids){
        $data = array();
        $sql = 'SELECT did.bn,SUM(did.amount) AS _amount FROM sdb_ome_delivery_items_detail AS did WHERE did.delivery_id IN('.implode(',',$ids).') GROUP BY did.bn';
        $rows = $this->db->select($sql);
        foreach ($rows as $row) {
            $data[strtoupper($row['bn'])] = $row['_amount'];
        }
        return $data;
    }

    /**
     * 根据物流ID获取站内对应的物流公司
     * @param array $logi_ids
     *
     */
    function getOMELogiName($logi_ids){
        if (!$logi_ids)
            return false;
        $logi_names = $this->db->select('SELECT corp_id,name FROM sdb_ome_dly_corp WHERE corp_id IN('.implode(',',$logi_ids).')');
        $new_logi_names = array();
        if($logi_names){
            foreach($logi_names as $l){
                $new_logi_names[$l['corp_id']] = $l['name'];
            }
            return $new_logi_names;
        }else{
            return false;
        }
    }

    public function getPrintProductName($ids){
        $printProductNames = array();
        $sql = 'SELECT distinct oi.order_id,oi.name,oi.bn,oi.addon,bp.store_position
                    FROM sdb_ome_delivery_order AS d2o
                LEFT JOIN sdb_ome_order_items AS oi
                    ON d2o.order_id = oi.order_id
                LEFT JOIN (
                    SELECT bpp.*
                        FROM (
                            SELECT pos_id,product_id
                            FROM sdb_ome_branch_product_pos
                            ORDER BY create_time DESC
                        )bpp
                    GROUP BY bpp.product_id
                 )bb
                    ON bb.product_id = oi.product_id
                 LEFT JOIN sdb_ome_branch_pos bp
                    ON bp.pos_id = bb.pos_id
                WHERE d2o.delivery_id IN('.implode(',',$ids).') ORDER BY d2o.order_id';
        $rows = $this->db->select($sql);
        foreach($rows as $row){
            $row['bn'] = trim($row['bn']);

            if (isset($printProductNames[$row['bn']]))
                continue;
            $row['addon'] = ome_order_func::format_order_items_addon($row['addon']);

            $printProductNames[$row['bn']] = $row;
        }

        return $printProductNames;
    }

    function getOrderIdsByDeliveryIds($delivery_ids){
        $rows = $this->db->select('select order_id from sdb_ome_delivery_order where delivery_id in('.implode(',', $delivery_ids).')');
        $order_ids = array();
        foreach($rows as $row){
            $order_ids[] = $row['order_id'];
        }

        return $order_ids;
    }

   public function update($data, $filter, $mustUpdate = null) {

        //调用原有处理
        $result = parent::update($data, $filter, $mustUpdate);

        //获取更新的列表
        $deliveryIds = $this->getList('delivery_id',$filter);
        if (!empty($deliveryIds)) {

            foreach ($deliveryIds as $row) {

                $this->updateOrderLogi($row['delivery_id'], $data);
            }
        }else{

            if ($filter['delivery_id'] && $filter['change_type'] == 'wms') {
                $deliveryIds = $this->getList('delivery_id', array('delivery_id'=>$filter['delivery_id']));

                if ($deliveryIds) {
                    foreach ($deliveryIds as $row) {

                        $this->updateOrderLogi($row['delivery_id'], $data);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 重载 save 方法，发现有打印状态的更新及 logi_no 的更新，就回传至对应订单
     */
    function save(&$data, $mustUpdate = null) {

        //调用原有处理
        $result = parent::save($data, $mustUpdate);

        if ($data['delivery_id'] > 0) {
            $this->updateOrderLogi($data['delivery_id'], $data);
        }
        return $result;
    }

    /**
     * 更新发货单对应的订单发货物流信息
     *
     * @param Integer $delivery_id
     * @param Array $data
     * @return void
     */
    function updateOrderLogi($delivery_id, $data) {

        //执行订单更新过程
        $updatebody = array();
        //检查打印状态
        if (key_exists('status', $data) && in_array($data['status'], array('back', 'cancel', 'failed'))) {

            //发货单取消，对应订单数据中的物流信息也清除 （如有分折订单，需做更多检查）
            $updatebody = array("logi_no = ''", 'print_status = 0');
        } else {
            $chkField = array('expre_status' => 0x01, 'stock_status' => 0x02, 'deliv_status' => 0x04);
            $addPrintStatus = 0;
            $removeStatus = 0x7;
            if (key_exists('expre_status', $data) || key_exists('stock_status', $data) || key_exists('deliv_status', $data)) {
                foreach ($chkField as $fieldName => $state) {
                    if (isset($data[$fieldName])) {
                        if ($data[$fieldName] == 'true') {
                            $addPrintStatus = $addPrintStatus | $state;
                        } else {
                            $removeStatus = $removeStatus & (~ $state);
                        }
                    }
                }
                $updatebody[] = "print_status = print_status | $addPrintStatus & $removeStatus";
            }

            //检查快递单号
            if (key_exists('logi_no', $data)) {
                if (empty($data['logi_no'])) {
                    $updatebody[] = "logi_no = ''";
                } else {
                    $updatebody[] = "logi_no = '{$data[logi_no]}'";
                }
            }
            //检查快递公司
            if (key_exists('logi_id', $data)) {
                $updatebody[] = "logi_id = '{$data[logi_id]}'";
            }
        }
        //有更新内容，则更新订单
        if (!empty($updatebody)) {
            $d2o = app::get('ome')->model('delivery_order')->getList('order_id', array('delivery_id' => $delivery_id));
            if (!empty($d2o)) {
                $ids = array();
                foreach ($d2o as $oId) {
                    $ids[] = $oId['order_id'];
                }
                
                kernel::database()->exec("UPDATE sdb_ome_orders SET " . join(',', $updatebody) . " WHERE order_id IN (" . join(',', $ids) . ")");
            }
        }
    }

    function repairCheck($delivery_ids){
        $rows = $this->db->select('select delivery_id from sdb_ome_delivery where process ="false" and verify="true" and logi_no in("'.implode('","', $delivery_ids).'")');
        if($rows){
            foreach($rows as $row){
                $this->db->exec('update sdb_ome_delivery_items set verify="true",verify_num=number where delivery_id='.$row['delivery_id']);
            }
        }
    }

    //根据发货单id调整打印排序
    function printOrderByByIds($ids) {
        if(!$ids)return false;
        $table = $this->table_name(true);
        $where = 'delivery_id in('.implode(',', $ids).')';
        $table .= " LEFT JOIN (SELECT COUNT(idx_split) AS iNum, idx_split AS idx FROM {$table} WHERE {$where} GROUP BY idx_split ) AS i ON i.idx=idx_split";
        $orderType =  'skuNum, itemNum, iNum DESC, idx_split,delivery_id';
        $sql = 'SELECT delivery_id FROM ' . $table . ' WHERE ' . $where.' ORDER BY ' . $orderType;
        $list = $this->db->select($sql);
        $delivery_ids = array();
        foreach($list as $row){
            $delivery_ids[] = $row['delivery_id'];
        }

        return $delivery_ids;
    }
    /* 检测快递单是主快递单 还是子表中的快递单
     * wujian@shopex.cn
     * 2012年3月20日
     */
    function checkDeliveryOrBill($logi_no){
        $dlyObj = &$this->app->model('delivery');
        $dly = $dlyObj->dump(array('logi_no|nequal' => $logi_no),'*');

        if (empty($dly)){
            $dlyObjBill = &$this->app->model('delivery_bill');
            $dlyBill = $dlyObjBill->dump(array('logi_no|nequal' => $logi_no),'*');
            if($dlyBill){
                $dly = $dlyObj->dump(array('delivery_id|nequal' => $dlyBill["delivery_id"]),'*');
                return $dly["logi_no"];
            }else{
                return false;
            }
        }else{
            return $logi_no;
        }
    }

    /* 检测主快递单是否有子快递单
     * wujian@shopex.cn
     * 2012年3月22日
     */
    function checkDeliveryHaveBill($delivery_id){
        $dlyBillObj = &$this->app->model('delivery_bill');
        $dlyBill = $dlyBillObj->getList('*',array('delivery_id|nequal'=>$delivery_id,'status'=>0));
        if($dlyBill){
            return $dlyBill;
        }else{
            return false;
        }
    }

    /*
    *获取发货单优惠金额
    *
    *@date 2012-04-26
    */

    function getPmt_price($data){
        $orderObj = &$this->app->model('orders');
        $pmt_order = array();

        foreach($data as $key=>$val){
            $order = $orderObj->dump($val['order_id'],"order_id",array("order_objects"=>array("*",array("order_items"=>array('bn,pmt_price,nums,price')))));
            foreach($order['order_objects'] as $k=>$v){
                if($v['obj_type']!='goods'){
                    $item_total = $this->db->selectRow("select sum(nums) as nums from sdb_ome_order_items where obj_id=".$v['obj_id']." AND `delete`='false'");
                    $pvg_price = round($v['pmt_price']/$item_total['nums'],2);
                }

                foreach($v['order_items'] as $k1=>$v1){
                    $item_pmt_price=0;
                    $item_pmt_price=$pvg_price*$v1['quantity'];
                    if(isset($pmt_order[$v1['bn']])){
                        $pmt_order[$v1['bn']]['pmt_price'] += $v1['pmt_price']+$item_pmt_price;
                    }else{
                        $pmt_order[$v1['bn']]['pmt_price'] = $v1['pmt_price']+$item_pmt_price;
                    }
                }
            }
        }
        return $pmt_order;
    }

    /*
    *获取商品销售单价
    *@date 2012-04-26
    */

    function getsale_price($data){
        $orderObj = &$this->app->model('orders');
        $sale_order = array();
        foreach($data as $key=>$val){
            $order = $orderObj->dump($val['order_id'],"order_id",array("order_objects"=>array("*",array("order_items"=>array('bn,pmt_price,sale_price,nums,price')))));
            foreach($order['order_objects'] as $k=>$v){
                if($v['obj_type']=='pkg' || $v['obj_type']=='gift' || $v['obj_type']=='giftpackage'){
                    $item_amount = $this->db->selectrow('SELECT sum(nums) as nums FROM sdb_ome_order_items WHERE obj_id='.$v['obj_id'].'');
                    $pvg_price = round($v['sale_price']/$item_amount['nums'],2);
                    foreach($v['order_items'] as $k1=>$v1){
                        if(isset($sale_order[$v1['bn']])){
                            $sale_order[$v1['bn']]['obj_quantity'] += $v1['quantity'];
                            $sale_order[$v1['bn']]['obj_sale_price'] += ($v1['quantity']*$pvg_price);
                        }else{
                            $sale_order[$v1['bn']]['obj_quantity'] = $v1['quantity'];
                            $sale_order[$v1['bn']]['obj_sale_price'] = ($v1['quantity']*$pvg_price);
                        }
                    }
                } else {
                    foreach( $v['order_items'] as $k1=>$v1 ){
                         if ( isset( $sale_order[$v1['bn']]) ){
                            $sale_order[$v1['bn']]['quantity'] += $v1['quantity'];
                            $sale_order[$v1['bn']]['sale_price'] += $v1['sale_price'];
                        }else{
                            $sale_order[$v1['bn']]['quantity'] = $v1['quantity'];
                            $sale_order[$v1['bn']]['sale_price'] = $v1['sale_price'];
                        }
                    }
                }
            }
        }

        $sale_price = array();
        foreach($sale_order as $k=>$v){
            $price = ($v['obj_sale_price']+$v['sale_price']);
            $quantity = $v['quantity']+$v['obj_quantity'];
            $sale_price[$k]=round($price/$quantity,2);
        }

        return $sale_price;

    }
    /**
     * 根据订单bn获取发货单信息
     *
     * @param  void
     * @return void
     * @author
     **/
    public function getDeliveryByOrderBn($order_bn, $col='*')
    {
        $order_info = app::get('ome')->model('orders')->select()->columns('order_id')->where('order_bn=?',$order_bn)->instance()->fetch_row();
        $sql = "SELECT *
                FROM sdb_ome_delivery_order as deo
                LEFT JOIN sdb_ome_delivery AS d ON deo.delivery_id = d.delivery_id
                WHERE deo.order_id={$order_info['order_id']}
                AND (d.parent_id=0 OR d.is_bind='true')
                AND d.disabled='false'
                AND d.status NOT IN('failed','cancel','back','return_back')";
        $delivery = kernel::database()->select($sql);
        if(isset($delivery[0])&&$delivery){
            return $delivery[0];
        }else{
            return array();
        }
    }
    /**
     * 检查发货单是否已经打印完成
     *
     * @author chenping<chenping@shopex.cn>
     * @version 2012-5-15 00:14
     * @param Array $dly 发货单信息 $dly
     * @param Array $msg 错误信息
     * @return TRUE:打印完成、FALSE:打印未完成
     **/
    public function checkPrintFinish($dly,&$msg){
        $deliCfgLib = kernel::single('ome_delivery_cfg');
        if($deliCfgLib->deliveryCfg != '') {
            $btncombi = $deliCfgLib->btnCombi($dly['deli_cfg']);
            list($stock,$delie) = explode('_',$btncombi);
            if(1 == $stock){
                if($dly['stock_status'] == 'false') {
                    $msg[] = array('bn'=>$dly['logi_no'],'msg' => $this->app->_('备货单未打印'));
                    return false;
                }
            }
            if(1 == $delie){
                if($dly['deliv_status'] == 'false'){
                    $msg[] = array('bn' => $dly['logi_no'],'msg'=> $this->app->_('发货单未打印'));
                    return false;
                }
            }
        }else{
            # 默认情况全部开启
            if($dly['stock_status'] == 'false'){      // 备货单未打印
                $msg[] = array('bn'=> $dly['logi_no'], 'msg'=>$this->app->_('备货单未打印'));
                return false;
            }
            if($dly['deliv_status'] == 'false'){     // 发货单未打印
                $msg[] = array('bn' => $dly['logi_no'], 'msg'=>$this->app->_('发货单未打印'));
                return false;
            }
        }
        if($dly['expre_status'] == 'false'){   // 快递单未打印
            $msg[] = array('bn'=> $dly['logi_no'], 'msg'=>$this->app->_('快递单未打印'));
            return false;
        }
        return true;
    }

    /**
    * 计算物流费用
    */
    function getDeliveryFreight($area_id=0,$logi_id=0,$weight=0){

        if($logi_id && $logi_id>0){
            $dlyCorpObj = &$this->app->model('dly_corp');
            $corp  = $dlyCorpObj->dump($logi_id);//物流公司信息
        }
        if($corp['setting']=='1'){
            $firstunit = $corp['firstunit'];
            $continueunit = $corp['continueunit'];
            $firstprice = $corp['firstprice'];
            $continueprice = $corp['continueprice'];
            $dt_expressions = $corp['dt_expressions'];
        }else{
            //物流预算费用计算
            if($area_id && $area_id>0){
                $regionObj = kernel::single('eccommon_regions');
                $region = $regionObj->getOneById($area_id);
                $regionIds = explode(',', $region['region_path']);
                foreach($regionIds as $key=>$val){
                    if($regionIds[$key] == '' || empty($regionIds[$key])){
                        unset($regionIds[$key]);
                    }
                }
            }
            $regionIds = implode(',',$regionIds);
            #物流公式设置明细表
            $sql = 'SELECT firstunit,continueunit,firstprice,continueprice,dt_expressions,dt_useexp FROM sdb_ome_dly_corp_items WHERE corp_id='.$logi_id.' AND region_id in ('.$regionIds.') ORDER BY region_id DESC';

            $corp_items = $this->db->selectrow($sql);
            $firstunit = $corp_items['firstunit'];
            $continueunit = $corp_items['continueunit'];
            $firstprice = $corp_items['firstprice'];
            $continueprice = $corp_items['continueprice'];
            $dt_expressions = $corp_items['dt_expressions'];
        }

        if($dt_expressions && $dt_expressions != ''){

            $price = utils::cal_fee($dt_expressions, $weight, 0,$firstprice,$continueprice); //TODO 生成快递费用
        }else{
            if($continueunit>0 && bccomp($weight,$firstunit,3) == 1 ){
                $continue_price = (($weight-$firstunit)/$continueunit)*$continueprice;
            }else{
                $continue_price = 0;
            }
            $price = $firstprice+$continue_price;
        }
        return $price;
    }


    /**
    * 根据物流单号获取商品和重量信息
    */
    function getWeightbydelivery_id($logi_no) {
        $orderObj = &$this->app->model('orders');
        $dlyObj = &$this->app->model('delivery');
        $productObj= &$this->app->model('products');
        $dlyBillObj = &$this->app->model('delivery_bill');
        $pkgObj = app::get('omepkg')->model('pkg_goods');
        $dlyBill = $dlyBillObj->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
        $dlyfather = $dlyObj->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
        if($dlyBill){
            $delivery_id = $dlyBill['delivery_id'];
        }elseif($dlyfather){
            $delivery_id = $dlyfather['delivery_id'];

        }
        $dly = $dlyObj->dump($delivery_id,'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));
        
        //[拆单]根据发货单中货品详细读取重量 ExBOY
        $split_seting      = $this->get_delivery_seting();
        if($split_seting && $dly['is_bind'] == 'false')
        {
            $orderItemObj   = &app::get('ome')->model('order_items');
            $objectsObj     = &app::get('ome')->model('order_objects');
            $pkgPobj        = &app::get('omepkg')->model('pkg_product');
            $dlyItemDetail  = &app::get('ome')->model('delivery_items_detail');
            
            $product_weight = array();
            $item_list      = $item_ids = array();
            
            $temp_data      = $dlyItemDetail->getList('*', array('delivery_id'=>$delivery_id));
            foreach ($temp_data as $key => $val) 
            {
                $item_id        = $val['order_item_id'];
                $item_list[$item_id]    = array(
                                            'item_id' => $item_id,
                                            'obj_id' => $val['order_obj_id'],
                                            'item_type' => $val['item_type'],
                                            'product_id' => $val['product_id'],
                                            'bn' => $val['bn'],
                                            'number' => $val['number'],
                                          );
                
                $item_ids[]     = $item_id;
            }
            
            #获取本次发货单关联的订单明细
            $obj_list = array();
            $flag     = true;//重量累加标记
            
            $filter     = array('item_id'=>$item_ids, '`delete`'=>'false');        
            $item_data  = $orderItemObj->getList('item_id, obj_id, product_id, bn, item_type, nums, name', $filter);
            foreach ($item_data as $key => $val) 
            {
                $item_type   = $val['item_type'];
                $item_id     = $val['item_id'];
                $obj_id      = $val['obj_id'];
                $product_id  = $val['product_id'];
                $bn          = $val['bn'];
                
                $val['send_num']   = $item_list[$item_id]['number'];//发货数量
                
                if($item_type == 'pkg') 
                {
                    $obj_list[$obj_id]['items'][$item_id]  = $val;
                    
                    //[捆绑商品]货号bn
                    if(empty($obj_list[$obj_id]['bn'])) 
                    {
                        $obj_item     = $objectsObj->getList('obj_id, bn', array('obj_id'=>$obj_id), 0, 1);
                        $obj_list[$obj_id]['bn']  = $obj_item[0]['bn'];
                        
                        //单个[捆绑商品]重量
                        $pkg_goods    = $pkgObj->dump(array('pkg_bn'=>$obj_item[0]['bn']),'goods_id, weight');
                        $obj_list[$obj_id]['net_weight']  = floatval($pkg_goods['weight']);
                        
                        //[捆绑商品]发货数量
                        $pkg_product   = $pkgPobj->dump(array('goods_id'=>$pkg_goods['goods_id'], 'product_id'=>$product_id), 'pkgnum');
                        $obj_list[$obj_id]['send_num']    = intval($val['send_num'] / $pkg_product['pkgnum']);
                        
                        $obj_list[$obj_id]['weight']  = 0;
                        if($obj_list[$obj_id]['net_weight'] > 0)
                        {
                            $obj_list[$obj_id]['weight']     = ($obj_list[$obj_id]['net_weight'] * $obj_list[$obj_id]['send_num']);
                        }
                    }
                    
                    //items_list
                    $products = $productObj->dump(array('bn'=>$bn),'weight');
                    $product_weight[$obj_id]['items'][$item_id]     = array(
                                                                    'weight' => $products['weight'],
                                                                    'number' => $val['send_num'],
                                                                    'total' => ($products['weight'] * $val['send_num']),
                                                                    'bn' => $bn,
                                                                    'product_name' => $val['name'],
                                                                );
                }
                else 
                {
                    //普通商品直接计算重量
                    $weight   = 0;
                    $products = $productObj->dump(array('bn'=>$bn),'weight');
                    if($products['weight'] > 0)
                    {
                      $weight = ($products['weight'] * $val['send_num']);
                    }
                    
                    //items_list
                    $product_weight[$obj_id]['obj_type']            = $item_type;
                    $product_weight[$obj_id]['weight']              = $weight;
                    $product_weight[$obj_id]['items'][$item_id]     = array(
                                                                    'weight' => $products['weight'],
                                                                    'number' => $val['send_num'],
                                                                    'total' => ($products['weight'] * $val['send_num']),
                                                                    'bn' => $bn,
                                                                    'product_name' => $val['name'],
                                                                );
                }
            }
            
            #捆绑商品无重量的重新计算
            if(!empty($obj_list))
            {
                foreach ($obj_list as $obj_id => $obj_item) 
                {
                    $weight     = 0;
                    if($obj_item['weight'] > 0 && $flag == true) 
                    {
                        $weight += $obj_item['weight'];
                    }
                    else 
                    {
                        foreach ($product_weight[$obj_id] as $item_id => $item) 
                        {
                            if($item['total'] == 0)
                            {
                                $weight     = 0;
                                break;
                            }
                             $weight += $item['total'];
                        }
                    }
                    
                    $product_weight[$obj_id]['obj_type']    = 'pkg';
                    $product_weight[$obj_id]['weight']      = $weight;
                }
            }
            
            sort($product_weight);
            return $product_weight;
        }
        elseif($dly)
        {
            $delivery_order = $dly['delivery_order'];
            $product_weight = array();
            foreach ($delivery_order as $items) {
                $order = $orderObj->dump($items['order_id'],"order_id",array("order_objects"=>array("*",array("order_items"=>array('product_id,nums,bn,name,`delete`')))));
                foreach ($order['order_objects'] as $k=>$v) {
                    $bn = $v['bn'];
                    $item_weight_total = 0;
                    $items_list = array();
                    foreach($v['order_items'] as $k1=>$v1){
                        if($v1['delete'] == 'true') continue;

                        $products = $productObj->dump(array('bn'=>$v1['bn']),'weight');
                        $items_list[] = array(
                            'weight'=>$products['weight'],
                            'number'=>$v1['quantity'],
                            'total'=>$products['weight']*$v1['quantity'],
                            'bn'=>$v1['bn'],
                            'product_name'=>$v1['name'],

                        );
                        $item_weight_total+=$products['weight']*$v1['quantity'];
                    }

                    if(empty($items_list)) continue;

                    foreach($items_list as $list){
                        if($list['total']==0){
                            $item_weight_total = 0;
                            break;
                        }
                    }
                    if($v['obj_type']=='pkg'){

                        $pkg = $pkgObj->dump(array('pkg_bn'=>$bn),'weight');
                        $weight = $pkg['weight'] * $v['quantity'];
                        if($weight==0){
                            $weight = $item_weight_total;
                        }


                    }else{
                        $weight = $item_weight_total;
                    }
                    $product_weight[$k]['items'] = $items_list;
                    $product_weight[$k]['weight'] = $weight;
                    $product_weight[$k]['obj_type'] =$v['obj_type'];
                }

            }
            sort($product_weight);
            return $product_weight;
        }
    }

    function modifier_is_cod($row){
        if($row == 'true'){
            return "<div style='width:48px;padding:2px;height:16px;background-color:green;float:left;'><span style='color:#eeeeee;'>货到付款</span></div>";
        }else{
            return '款到发货';
        }
    }
    /**
    * 获取订单备注
    */
    function getOrderMarktextByDeliveryId($dly_ids=null){
        if ($dly_ids){
            $sql = "SELECT o.mark_text FROM sdb_ome_delivery_order do
                                    JOIN sdb_ome_orders o
                                        ON do.order_id=o.order_id
                                    WHERE do.delivery_id IN ($dly_ids)
                                        GROUP BY do.order_id ";
            $rows = $this->db->select($sql);

            $memo = array();
            if ($rows){
                foreach ($rows as $v)

                $memo[] = unserialize($v['mark_text']);
            }
            return serialize($memo);
        }
    }

    /**
    * 获取可合并发货单
    */
    function fetchCombineDelivery($order_id){
        $combine_member_id = &app::get('ome')->getConf('ome.combine.member_id');
        $combine_shop_id = &app::get('ome')->getConf('ome.combine.shop_id');
        $combine_member_id = !isset($combine_member_id) ? 1:$combine_member_id;
        $combine_shop_id = !isset($combine_shop_id) ? 1: $combine_shop_id;
        $memberidconf = intval(app::get('ome')->getConf('ome.combine.memberidconf'));
        $memberidconf = $memberidconf=='1' ? '1' : '0';
        $orders = $this->app->model('orders')->getlist('order_id, shop_type,member_id,shop_id,ship_name,ship_mobile,ship_area,ship_addr,is_cod',array('order_id'=>$order_id),0,1);

        $orders = $orders[0];

        $filter = array('process' => 'false','status' => array('ready', 'progress'), 'parent_id' => '0','is_cod'=>$orders['is_cod']);
        if ($orders['shop_type'] == 'shopex_b2b') {

             if (empty($orders['member_id'])) {
                    return false;
             }else{
                    $filter['member_id'] = $orders['member_id'];

             }
             $filter['shop_id'] = $orders['shop_id'];
        } else if($orders['shop_type'] == 'dangdang' && $orders['is_cod'] == 'true'){
            return false;
        } else if( $orders['shop_type'] == 'amazon' && $orders['self_delivery']=='false' ){
            return false;        } else if($orders['shop_type'] == 'taobao' && $orders['order_source'] == 'tbdx'){
            return false;
        }else {        //直销单
            if ($combine_member_id) {
                if(empty($orders['member_id'])){
                    if ($memberidconf == '0') {
                        return false;
                    }

                }else{
                    $filter['member_id'] = $orders['member_id'];
                }
            }
            if ($combine_shop_id) {
                $filter['shop_id'] = $orders['shop_id'];
            }
        }

        $filter = array_merge($filter, kernel::single('omeauto_auto_combine')->_getAddrFilter($orders));

        $delivery = $this->getlist('delivery_bn',$filter);

        $combine_delivery = array();
        foreach ((array)$delivery as $deli){
            $combine_delivery[] = $deli['delivery_bn'];
        }

        return $combine_delivery;


    }
    /**
    * 找印时获取前端名称
    *
    */
    public function getPrintFrontProductName($ids){
        $ordersObj = &$this->app->model('orders');
        $printProductNames = array();
        $sql = 'SELECT distinct oi.order_id,oi.name,oi.bn,oi.addon,bp.store_position
                    FROM sdb_ome_delivery_order AS d2o
                LEFT JOIN sdb_ome_order_items AS oi
                    ON d2o.order_id = oi.order_id
                LEFT JOIN (
                    SELECT bpp.*
                        FROM (
                            SELECT pos_id,product_id
                            FROM sdb_ome_branch_product_pos
                            ORDER BY create_time DESC
                        )bpp
                    GROUP BY bpp.product_id
                 )bb
                    ON bb.product_id = oi.product_id
                 LEFT JOIN sdb_ome_branch_pos bp
                    ON bp.pos_id = bb.pos_id
                WHERE d2o.delivery_id IN('.implode(',',$ids).') ORDER BY d2o.order_id';
        $rows = $this->db->select($sql);
        foreach($rows as $row){
            $orders = $ordersObj->dump($row['order_id'],'shop_id');
            
            $bncode = md5($orders['shop_id'].trim($row['bn']));
            $row['bn'] = $bncode;
        
            if (isset($printProductNames[$row['bn']]))
                continue;
            $row['addon'] = ome_order_func::format_order_items_addon($row['addon']);

            $printProductNames[$row['bn']] = $row;
        }
        
        return $printProductNames;
    }
    #逐单发货时，根据物流单号，获取货号、货品名称
    function getProcutInfo($logi_no = null){
        $sql = 'select
	                items.bn,items.product_name,items.number,delivery.delivery_id
                from sdb_ome_delivery as delivery
                left join sdb_ome_delivery_items items on items.delivery_id=delivery.delivery_id
                where delivery.logi_no='."'$logi_no'";
        $rows = $this->db->select($sql);
        return $rows;
    }
    
    /**
     * 获取打印前端商品名
     *
     * @param Array $deliverys 发货单集合
     * @return Array
     * @author 
     **/
    public function getPrintOrderName($deliverys)
    {
        $data = array();

        $order_ids = array();  
        foreach ((array) $deliverys as $delivery) {
            foreach ($delivery['delivery_order'] as $delivery_order) {
                $order_ids[] = $delivery_order['order_id'];
            }
        }

        $orderItemModel = app::get('ome')->model('order_items');
        $orderItemList = $orderItemModel->getList('order_id,name,bn,addon',array('order_id' => $order_ids,'delete' => 'false'));

        $re_orderItemList = array();
        foreach ((array) $orderItemList as $order_item) {
            $order_item['addon'] = ome_order_func::format_order_items_addon($order_item['addon']);
            $re_orderItemList[$order_item['order_id']][$order_item['bn']] = $order_item;
        }
        unset($orderItemList);

        foreach ((array) $deliverys as $delivery) {
            $arr = array();
            foreach ($delivery['delivery_order'] as $delivery_order) {
                //$arr = array_merge((array) $arr,(array) $re_orderItemList[$delivery_order['order_id']]);
                $arr = $arr + (array) $re_orderItemList[$delivery_order['order_id']];
            }

            $data[$delivery['delivery_id']] = $arr;
        }
        unset($re_orderItemList);

        return $data;
    }

    /**
     * 获取打印货品位
     *
     * @param Array $deliverys 发货单集合
     * @return void
     * @author 
     **/
    public function getPrintProductPos($deliverys)
    {
        $data = array();

        $product_ids = array();
        foreach ($deliverys as $delivery) {
            foreach ($delivery['delivery_items'] as $delivery_item) {
                $product_ids[] = $delivery_item['product_id'];
                
                $bpro_key = $delivery['branch_id'].$delivery_item['product_id'];
                $data[$delivery_item['product_id']] = &$bpro[$bpro_key];
            }
        }

        // 货品货位有关系
        $bppModel = app::get('ome')->model('branch_product_pos');
        $bppList = $bppModel->getList('product_id,pos_id,branch_id',array('product_id'=>$product_ids));

        // 如果货位存在
        if ($bppList) {
            // 货位信息
            $pos_ids = array();
            foreach ($bppList as $key=>$value) {
                $pos_ids[] = $value['pos_id'];
            }
            
            $posModel = app::get('ome')->model('branch_pos');
            $posList = $posModel->getList('pos_id,branch_id,store_position',array('pos_id'=>$pos_ids));

            foreach ($posList as $key=>$value) {
                $bpos_key = $value['branch_id'].$value['pos_id'];
                
                $bpos[$bpos_key] = $value['store_position'];
            }
            unset($posList);

            foreach ($bppList as $key=>$value) {
                $bpro_key = $value['branch_id'].$value['product_id'];
                $bpos_key = $value['branch_id'].$value['pos_id'];
                $bpro[$bpro_key] = $bpos[$bpos_key];
            }
            unset($bppList);
        }
        
        return $data;    
    }
    
     /**
     * 获取发货商品序列号
     * @param  
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getProductserial($dly_id)
    {
        $order_objectsObj = app::get('ome')->model('order_objects');
        $order_itemsObj = app::get('ome')->model('order_items');
        $product_serialObj = app::get('ome')->model('product_serial');
        $deliveryObj = $this->app->model('delivery');
        $orderIds = $deliveryObj->getOrderIdByDeliveryId($dly_id);

        $order_objects = $order_objectsObj->getlist('oid,order_id,obj_id',array('order_id'=>$orderIds,'oid|than'=>'0'));
        $product = array();
        $product_serial = $product_serialObj->getSerialByproduct_id($dly_id);
        if ($product_serial) {
            foreach ($order_objects as $objects ) {
                $obj_id = $objects['obj_id'];
                $order_id = $objects['order_id'];
                $oid = $objects['oid'];
                $order_items = $order_itemsObj->getlist('product_id,nums',array('obj_id'=>$obj_id,'order_id'=>$order_id,'delete'=>'false'));
                $serial_list = array();
                foreach ($order_items as $items ) {
                    $nums = $items['nums'];
                    $product_id = $items['product_id'];
                    if ($product_serial[$product_id]) {
                        $serial = array_slice($product_serial[$product_id],0,$nums);//取数组
                        if ($serial) {
                            $serial_list[] = implode(',',$serial);
                            array_splice($product_serial[$product_id],0,$nums);//删除
                        }
                    }                }
                if ($serial_list) {
                    $product[]= $oid.':'.implode('|',$serial_list);
                }
                
            
            }
        }
        
       if ($product) {
           $product = "identCode=".implode('|',$product);
       }
  
        return $product;

    }

    /**
     +----------------------------------------------------------
     * [拆单]判断订单是否进行了拆单操作  ExBOY
     +----------------------------------------------------------
     * @param   Number    $delivery_id 发货单id
     * return   Boolean
     +----------------------------------------------------------
     */
    function check_order_split($delivery_id)
    {
        #获取订单order_id
        $order_ids     = $this->getOrderIdByDeliveryId($delivery_id);
        foreach ($order_ids as $key => $val)
        {
            $order_id    = $val;
        }
        
        #获取关联的发货单id
        $temp_ids       = $this->getDeliverIdByOrderId($order_id);
        
        #获取订单是否有未生成的发货单的商品
        $sql   = "SELECT item_id FROM sdb_ome_order_items WHERE order_id = '".$order_id."' AND nums != sendnum AND `delete` = 'false'";
        $row   = kernel::database()->selectrow($sql);
        
        if(count($temp_ids) > 1 || !empty($row))
        {
            return true;
        }
        return false;
    }
    /**
     +----------------------------------------------------------
     * [拆单]获取关联的成功发货或未发货的发货单  ExBOY
     +----------------------------------------------------------
     * @param   Number    $delivery_id  发货单id
     * @param   Flag      $status       all全部、true已发货、false未发货
     * @param   Number    $parent_id    合并发货单中的父发货单
     * return   Array
     +----------------------------------------------------------
     */
    function get_delivery_process($delivery_id, $status='all', $parent_id=0)
    {
        $result     = array();
        $order_id   = 0;
        
        #获取订单order_id
        $order_ids     = $this->getOrderIdByDeliveryId($delivery_id);
        foreach ($order_ids as $key => $val)
        {
            $order_id    = $val;
        }
        
        #判断"拆单方式"配置是否变更
        $change_split   = $this->get_split_setup_change($order_id);
        if(!empty($change_split))
        {
            return '';//配置变更，直接回写
        }
        
        #关联的发货单[根据订单order_id获取发货单信息]
        $temp_ids       = $this->getDeliverIdByOrderId($order_id);
        if(!empty($temp_ids))
        {
            //去除现操作的delivery_id发货单
            $delivery_ids     = array();
            foreach ($temp_ids as $key => $val)
            {
                if($val == $delivery_id)  continue;
                
                //过滤合并发货单中的父发货单
                if($parent_id && $val == $parent_id)
                {
                    continue;
                }
                
                $delivery_ids[]  = $val;
            }
            
            if(!empty($delivery_ids))
            {
                $cols       = 'delivery_id, delivery_bn, is_cod, logi_id, logi_no, status, branch_id, 
                                 stock_status, deliv_status, expre_status, verify, process, type';
                
                $filter     = array('delivery_id'=>$delivery_ids);
                if($status == 'true')
                {
                    $filter['process'] = 'true';//已发货
                }
                elseif($status == 'false')
                {
                    $filter['process'] = 'false';
                }
                
                $result['delivery']     = $this->getList($cols, $filter, 0, -1);
            }
        }
        
        #获取订单是否有未生成的发货单
        if($status == 'false')
        {
            $sql   = "SELECT item_id, order_id, nums, sendnum FROM sdb_ome_order_items WHERE order_id = '".$order_id."' AND nums != sendnum AND `delete` = 'false'";
            $row   = kernel::database()->selectrow($sql);
            $result['order_items'] = $row;
        }
        
        return $result;
    }
    /**
     +----------------------------------------------------------
     * [拆单配置]获取拆单后回写发货单方式  ExBOY
     +----------------------------------------------------------
     * return   Array
     +----------------------------------------------------------
     */
    public function get_delivery_seting()
    {
        //$split_config   = &app::get('ome')->getConf('ome.delivery.status.cfg');
        $split_config   = &app::get('wms')->getConf('wms.delivery.status.cfg');
        
        $split_seting   = array('split'=>intval($split_config['set']['split']), 'split_model'=>intval($split_config['set']['split_model']), 
                        'split_type'=>intval($split_config['set']['split_type']));
        if(empty($split_seting['split']) || empty($split_seting['split_model']) || empty($split_seting['split_type']))
        {
            return '';
        }
        
        return $split_seting;
    }
    /**
     +----------------------------------------------------------
     * [拆单]判断"拆单方式"配置是否变更  ExBOY
     +----------------------------------------------------------
     * return   Array
     +----------------------------------------------------------
     */
    public function get_split_setup_change($order_id)
    {
        $sql    = "SELECT syn_id, sync, split_model, split_type FROM sdb_ome_delivery_sync WHERE order_id = '".intval($order_id)."' AND sync='succ' ORDER BY dateline DESC";
        $row    = kernel::database()->selectrow($sql);
        
        if(empty($row) || $row['split_model'] == 0)
        {
            return '';//上次未开启拆单或无发货记录
        }
        
        #拆单配置
        $split_seting   = $this->get_delivery_seting();
        
        if($row['split_model'] != $split_seting['split_model'] || $row['split_type'] != $split_seting['split_type'])
        {
            $split_seting['old_split_model']    = $row['split_model'];
            $split_seting['old_split_type']     = $row['split_type'];
            
            return $split_seting;
        }
        
        return '';
    }
    /**
     * 发货单列表项扩展字段
     */
    function extra_cols(){
        return array(
            'column_custom_mark' => array('label'=>'买家留言','width'=>'180','func_suffix'=>'custom_mark'),
            'column_mark_text' => array('label'=>'客服备注','width'=>'180','func_suffix'=>'mark_text'),
            'column_tax_no' => array('label'=>'发票号','width'=>'180','func_suffix'=>'tax_no'),
            'column_ident' => array('label'=>'批次号','width'=>'160','func_suffix'=>'ident','order_field'=>'idx_split'),
        );
    }

    /**
     * 买家备注扩展字段格式化
     */
    function extra_custom_mark($rows){
        return kernel::single('ome_extracolumn_delivery_custommark')->process($rows);
    }

    /**
     * 客服备注扩展字段格式化
     */
    function extra_mark_text($rows){
        return kernel::single('ome_extracolumn_delivery_marktext')->process($rows);
    }

    /**
     * 发票号扩展字段格式化
     */
    function extra_tax_no($rows){
        return kernel::single('ome_extracolumn_delivery_taxno')->process($rows);
    }

    /**
     * 批次号扩展字段格式化
     */
    function extra_ident($rows){
        return kernel::single('ome_extracolumn_delivery_ident')->process($rows);
    }

    /**
     +----------------------------------------------------------
     * [拆单]根据发货单统计订单商品重量  ExBOY
     +----------------------------------------------------------
     * @param   Intval $order_id
     * @param   Array  $order_items
     * @return  Number
     +----------------------------------------------------------
     */
    public function getDeliveryWeight($order_id, $order_items = array(), $delivery_id = 0)
    {
        $orderItemObj  = &app::get('ome')->model('order_items');
        $objectsObj    = &app::get('ome')->model('order_objects');
        $productObj    = &app::get('ome')->model('products');
        
        $pkgGobj    = &app::get('omepkg')->model('pkg_goods');
        $pkgPobj    = &app::get('omepkg')->model('pkg_product');
        
        $weight        = 0;
        
        if(empty($order_items) && !empty($delivery_id)) 
        {
            $didObj = &app::get('ome')->model('delivery_items_detail');
            $dly_itemlist   = $didObj->getList('delivery_id, order_item_id, product_id, number', array('delivery_id'=>$delivery_id, 'order_id'=>$order_id));
            foreach ($dly_itemlist as $key => $val)
            {
                $order_items[$key]  = array('item_id'=>$val['order_item_id'], 'product_id'=>$val['product_id'], 'number'=>$val['number']);
            }
            unset($dly_itemlist);
        }
        elseif(empty($order_items))
        {
            $weight   = $this->app->model('orders')->getOrderWeight($order_id);
            return $weight;
        }
        
        #[部分拆分]订单计算本次发货商品重量
        $item_list   = $item_ids = array();
        foreach ($order_items as $key => $val) 
        {
            $item_id     = $val['item_id'];
            $product_id  = $val['product_id'];
            
            $item_list[$item_id]    = $val;            
            $item_ids[]             = $item_id;
        }
        
        #获取本次发货单关联的订单明细
        $obj_list = array();
        $flag     = true;
        
        $filter     = array('item_id'=>$item_ids, '`delete`'=>'false');        
        $item_data  = $orderItemObj->getList('item_id, obj_id, product_id, bn, item_type, nums', $filter);
        foreach ($item_data as $key => $val) 
        {
            $item_type   = $val['item_type'];
            $item_id     = $val['item_id'];
            $obj_id      = $val['obj_id'];
            $product_id  = $val['product_id'];
            $bn          = $val['bn'];
            
            $val['send_num']   = $item_list[$item_id]['number'];//发货数量
            
            if($item_type == 'pkg') 
            {
                $obj_list[$obj_id]['items'][$item_id]  = $val;
                
                //[捆绑商品]货号bn
                if(empty($obj_list[$obj_id]['bn'])) 
                {
                    $obj_item     = $objectsObj->getList('obj_id, bn', array('obj_id'=>$obj_id), 0, 1);
                    $obj_list[$obj_id]['bn']  = $obj_item[0]['bn'];
                    
                    //[捆绑商品]重量
                    $pkg_goods    = $pkgGobj->dump(array('pkg_bn'=>$obj_item[0]['bn']),'goods_id, weight');
                    $obj_list[$obj_id]['net_weight']  = floatval($pkg_goods['weight']);
                    
                    //[捆绑商品]发货数量
                    $pkg_product   = $pkgPobj->dump(array('goods_id'=>$pkg_goods['goods_id'], 'product_id'=>$product_id), 'pkgnum');
                    $obj_list[$obj_id]['send_num']    = intval($val['send_num'] / $pkg_product['pkgnum']);
                    
                    $obj_list[$obj_id]['weight']  = 0;
                    if($obj_list[$obj_id]['net_weight'] > 0)
                    {
                        $obj_list[$obj_id]['weight']     = ($obj_list[$obj_id]['net_weight'] * $obj_list[$obj_id]['send_num']);
                    }
                }
            }
            else 
            {
                //普通商品直接计算重量
                $products = $productObj->dump(array('bn'=>$bn),'weight');
                if($products['weight'] > 0)
                {
                  $weight += ($products['weight'] * $val['send_num']);
                }
                else 
                {
                    $weight    = 0;//有一个商品重量为0,就返回
                    $flag      = false;
                    break;
                }
            }
        }
        
        #捆绑商品无重量的重新计算
        if(!empty($obj_list) && $flag)
        {
            foreach ($obj_list as $obj_id => $obj_item) 
            {
                if($obj_item['weight'] > 0) 
                {
                    $weight += $obj_item['weight'];
                }
                else 
                {
                    foreach ($obj_item['items'] as $item_id => $item)
                    {
                        $products = $productObj->dump(array('bn'=>$item['bn']),'weight');
                        if($products['weight'] > 0)
                        {
                            $weight += ($products['weight'] * $item['send_num']);
                        }
                        else 
                        {
                            $weight    = 0;
                            break 2;
                        }
                    }
                }
            }
        }
        
        return $weight;
    }
    public function getDlyId($logi_nos = false){
        $all_logi_no = array();
        foreach($logi_nos as $logi_no){
            $all_logi_no[] = "'".$logi_no."'";
        }
        $str_logi_no = implode(',', $all_logi_no);
        $sql = "select delivery_id from sdb_ome_delivery where logi_no in ( ".$str_logi_no." )
                union
                select delivery_id from sdb_ome_delivery_bill where logi_no in(".$str_logi_no." )";
        $rows = $this->db->select($sql);
        $data = array();
        if(!empty($rows)){
            foreach($rows as $row){
                $data[] = $row['delivery_id'];
            }
        }
        return $data;
    }

    
    /**
     * 更新请求同步sync状态
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function update_sync_cancel($delivery_id,$status='fail')
    {
        $sql = "UPDATE sdb_ome_delivery SET sync='".$status."' WHERE delivery_id=".$delivery_id;

        $this->db->exec($sql);
    }
    #订阅华强宝物流信息
    public function get_hqepay_logistics($delivery_id) {
        #检测是否开启华强宝物流
        $is_hqepay_on =  &app::get('ome')->getConf('ome.delivery.hqepay');
        if($is_hqepay_on == 'false'){
            return true;
        }
        #订阅物流信息
        kernel::single('ome_service_delivery')->get_hqepay_logistics($delivery_id);
        return true;
    }
    
    
    /**
     * 发货单向wms发送.
     * @
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function wmsdelivery_create($delivery_id)
    {
        $oOperation_log = &app::get('ome')->model('operation_log');
        $original_data = kernel::single('ome_event_data_delivery')->generate($delivery_id);
        $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
        $result = kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, false);
        $result = json_encode($result);
        $oOperation_log->write_log('delivery_modify@ome',$delivery_id,"发货单开始发送第三方结果".$result,NULL,$opInfo);
    }
}
?>
