<?php

class ome_ctl_admin_consign extends desktop_controller{

    var $name = "发货处理";
    var $workground = "delivery_center";

    function _views() {

        $mdl_order = $this->app->model('orders');

        # 未发货分两部分：sync=none+线上店铺 OR ship_status=0+线下店铺
        $shops = $this->app->model('shop')->getList('shop_id,node_id');
        $bindShop = $unbindShop = array();
        foreach ($shops as $key=>$shop) {
            if ($shop['node_id']) {
                $bindShop[] = $shop['shop_id'];
            } else {
                $unbindShop[] = $shop['shop_id'];
            }
        }
        $sync_none_filter = array('ship_status' => '0');
        if ($bindShop && $unbindShop) {
            $sync_none_filter['filter_sql'] = '(({table}sync="none" AND {table}shop_id in("'.implode('","',$bindShop).'"))'.' OR '.'({table}ship_status="0" AND shop_id in("'.implode('","',$unbindShop).'")))';
        } elseif ($bindShop) {
            $sync_none_filter['filter_sql'] = '{table}sync="none" AND {table}shop_id in("'.implode('","',$bindShop).'")';
        } elseif ($unbindShop) {
            $sync_none_filter['filter_sql'] = '{table}ship_status="0" AND {table}shop_id in("'.implode('","',$unbindShop).'")';
        }

        $base_filter = $this->getFilters();

        $sub_menu[0] = array('label' => app::get('base')->_('全部'), 'filter' => $base_filter, 'optional' => false);
        $sub_menu[1] = array('label' => app::get('base')->_('待发货'), 'filter' => array_merge($base_filter, $sync_none_filter), 'optional' => false);
        $sub_menu[2] = array('label' => app::get('base')->_('回写未发起'),'filter' => array_merge($base_filter,array('createway' => 'matrix','sync' => 'none' ,'ship_status' => '1')),'optional' => false);
        $sub_menu[3] = array('label' => app::get('base')->_('发货中'), 'filter' => array_merge($base_filter, array('createway'=>'matrix','sync' => 'run')), 'optional' => false);
        $sub_menu[4] = array('label' => app::get('base')->_('发货失败'), 'filter' => array_merge($base_filter, array('createway'=>'matrix','sync' => 'fail','sync_fail_type'=>array('none','unbind'))), 'optional' => false);
        $sub_menu[5] = array('label' => app::get('base')->_('京东发货失败'), 'filter' => array_merge($base_filter, array('createway'=>'matrix','sync' => 'fail','shop_type'=>'360buy','sync_fail_type' => array('none','unbind'))), 'optional' => false);
        $sub_menu[6] = array('label' => app::get('base')->_('回写参数错误'), 'filter' => array_merge($base_filter, array('createway' => 'matrix','sync' => 'fail','sync_fail_type' => 'params')), 'optional' => false);
        $sub_menu[7] = array('label' => app::get('base')->_('前端已发货'), 'filter' => array_merge($base_filter, array('createway' => 'matrix','sync' => 'fail','sync_fail_type' => 'shipped')), 'optional' => false);
        $sub_menu[8] = array('label' => app::get('base')->_('发货成功'), 'filter' => array_merge($base_filter, array('createway' => 'matrix','sync' => 'succ')), 'optional' => false);
        $sub_menu[9] = array('label' => app::get('base')->_('不予回写'),'filter' => array_merge($base_filter, array('createway' => array('local','import','after'))),'optional' => false);
        //$sub_menu[5] = array('label' => '<em style="font-size:14px;color:red;" title="此处订单前台已经发货，请及时检查，切勿重复发货！！！">发货冲突</em>', 'filter' => array_merge($base_filter, array('sync' => array('none', 'fail'), 'f_ship_status' => '1')), 'optional' => false);

        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $mdl_order->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&flt=' . $_GET['flt'] . '&view=' . $k . $s;
        }

        return $sub_menu;
    }

    function index(){
        $logi = &app::get('ome')->getConf('ome.delivery.logi');//扫描快递单与称重的顺序
        if(!isset($logi)){
            $logi = '0';
        }
        $this->pagedata['logi'] = $logi;
//        $consign = &app::get('ome')->getConf('ome.delivery.consign');
        $weightSet = &app::get('ome')->getConf('ome.delivery.weight');
        $minWeight = $this->app->getConf('ome.delivery.minWeight');
        $deliveryObj  = &$this->app->model('delivery');
        $this->pagedata['weightSet'] = $weightSet;
        $this->pagedata['minWeight'] = $minWeight;
        #称重报警max_weightwarn，weightpercent,min_weight,warnproblem_package
        $weightWarn = &app::get('ome')->getConf('ome.delivery.weightwarn');
        $max_weightwarn = &app::get('ome')->getConf('ome.delivery.max_weightwarn');
        $minpercent = &app::get('ome')->getConf('ome.delivery.minpercent');
        $maxpercent = &app::get('ome')->getConf('ome.delivery.maxpercent');
        $min_weightwarn = &app::get('ome')->getConf('ome.delivery.min_weightwarn');
        $problem_package = &app::get('ome')->getConf('ome.delivery.problem_package');
        //$min_weight = &app::get('ome')->getConf('ome.delivery.min_weight');
        $this->pagedata['min_weight'] = $min_weight;
        $this->pagedata['weightWarn'] = $weightWarn;
        $this->pagedata['max_weightwarn'] = $max_weightwarn;
        $this->pagedata['minpercent'] = $minpercent;
        $this->pagedata['maxpercent'] = $maxpercent;
        $this->pagedata['min_weightwarn'] = $min_weightwarn;
        $this->pagedata['problem_package'] = $problem_package;

        $numShow = app::get('ome')->getConf('ome.delivery.consignnum.show');
        if ($numShow == 'false') {
            $this->pagedata['deliverynum'] = '未知';
        } else {
            // $this->pagedata['num'] = $deliveryObj->countNoProcessDelivery();
            $this->pagedata['deliverynum'] = $deliveryObj->countNoProcessDeliveryBill();
        }
        
        $this->page("admin/delivery/process_consign_index.html");
    }

    function batch(){
        //$consign = &app::get('ome')->getConf('ome.delivery.consign');
        $stock_confirm= &app::get('ome')->getConf('purchase.stock_confirm');
        $stock_cancel= &app::get('ome')->getConf('purchase.stock_cancel');
        $this->pagedata['stock_confirm'] = $stock_confirm;
        $this->pagedata['stock_cancel'] = $stock_cancel;
        $deliveryObj  = &$this->app->model('delivery');
        $this->pagedata['num'] = $deliveryObj->countNoProcessDelivery();
        $this->pagedata['deliverynum'] = $deliveryObj->countNoProcessDeliveryBill();

        $blObj  = &$this->app->model('batch_log');

        $dayBegin = mktime(0,0,0,date("m"),date("d"),date("Y"));//当天开始时间戳
        $dayEnd = mktime(23,59,59,date("m"),date("d"),date("Y"));//当天结束时间戳

        $blResult = $blObj->getList('*', array('createtime|than'=>$dayBegin,'createtime|lthan'=>$dayEnd), 0, -1,'createtime desc');
        foreach($blResult as $k=>$v){
            $blResult[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }
        $this->pagedata['bldata'] = $blResult;
        $this->pagedata['app_dir'] = kernel::base_url()."/app/".$this->app->app_id;

        $this->page("admin/delivery/process_consign_batch.html");
    }

    /**
     * 极速发货
     *
     * @param void
     * @return void
     */
    function fast_consign() {
        $op_id = kernel::single('desktop_user')->get_id();
        switch ($_GET['view']) {
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
                $action = array(
                    array('label' => '批量发货', 'submit' => 'index.php?app=ome&ctl=admin_consign&act=batch_sync', 'confirm' => '你确定要对勾选的订单进行发货操作吗？', 'target' => 'refresh'),
                    array('label' => '已回写成功', 'submit' => 'index.php?app=ome&ctl=admin_consign&act=batch_sync_succ', 'confirm' => "这些订单系统认为都是在前台(淘宝、京东等)已经发货，请确认这些订单前端已经发货！！！\n\n警告：本操作不会再同步发货状态，并不可恢复，请谨慎使用！！！", 'target' => 'refresh'),
                );
                break;
            default:
                break;
        }

        $params = array(
            'title' => '需发货订单',
            'actions' => $action,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => false,
            'use_view_tab' => true,
            'finder_aliasname' => 'order_consign_fast'.$op_id,
            'finder_cols' => '_func_0,column_confirm,order_bn,column_sync_status,column_print_status,logi_id,logi_no,column_deff_time,member_id, ship_name,ship_area,total_amount',
            'base_filter' => $this->getFilters(),
        );
        $this->finder('ome_mdl_orders', $params);
    }

    /**
     * 单个发货
     *
     * @param Integer $orderId
     * @return void
     */
    function do_sync($orderId) {

        $this->begin('');
        $this->syncOrder($orderId);
        $this->end(true, '命令已经被成功发送！！');
    }

    /**
     * 批量消除冲突
     *
     * @param void
     * @return void
     */
    function batch_sync_succ() {
        $this->begin('');
        $ids = $_REQUEST['order_id'];

        if (!empty($ids)) {
            $orderObj = &$this->app->model('orders');
            $data = array('sync'=>'succ','sync_fail_type'=>'none');
            $filter = array('order_id'=>$ids,'createway' => 'matrix');
            $orderObj->update($data,$filter);

            //记录日志
            $logObj = &$this->app->model('operation_log');
            $logObj->batch_write_log('order_modify@ome','手动设为同步成功',time(),$filter);
        }
        $this->end(true, '命令已经被成功发送！');
    }
    
    /**
     * 批量发货
     *
     * @param void
     * @return void
     */
    function batch_sync() {

        $this->begin('');
        kernel::database()->exec('commit');
        $ids = $_REQUEST['order_id'];

        if (!empty($ids)) {

            $info = kernel::database()->select("SELECT order_id, sync from sdb_ome_orders where order_id in (" . join(',', $ids) . ") and sync not in ('succ') and logi_no <>''");
            foreach ((array) $info as $one) {//&& $one['sync'] <> 'run'
                if ($one['sync'] <> 'succ' ) {
                   $this->syncOrder($one['order_id']);
                }
            }
        }
        $this->end(true, '命令已经被成功发送！！');
    }

    /**
     * 对指定订单进行发货处理
     *
     * @param Integer $orderId
     * @return boolean
     */
    private function syncOrder($order_id) {

        $order = app::get('ome')->model('orders')->dump(array('order_id' => $order_id), '*');
        if ($order) {
            $dlyCorpObj = app::get('ome')->model('dly_corp');
            $channelObj = app::get('logisticsmanager')->model('channel');
            $deliveryObj = app::get('ome')->model('delivery');
            $deliveryIds = $deliveryObj->getDeliverIdByOrderId($order_id);
            if (!empty($deliveryIds)) {
                $delivery_id = $deliveryIds[0];
            }
            if (!empty($order['order_id']) && !empty($delivery_id)) {
                if ($order['sync'] <> 'succ' ) {
                    if ($this->syncChangeStatus($delivery_id)) {
                        $channel_type = '';
                        //电子面单
                        if ($order['logi_id'] > 0) {
                            $dlyCorp = $dlyCorpObj->dump($order['logi_id'],'channel_id,tmpl_type,shop_id');
                            $channel = $channelObj->dump($dlyCorp['channel_id']);
                            $channel_type = $channel['channel_type'];
                        }

                        if ($order['shop_type'] == 'vjia' && $order['createway'] == 'matrix') {
                            kernel::single('ome_delivery_vjia')->logistics_modify($order_id);
                        }
                        elseif (app::get('logisticsmanager')->is_installed() && ($channel_type == '360buy' || $channel_type == 'ems')) {
                            if ($channel_type && class_exists('logisticsmanager_service_' . $channel_type)) {
                                $channelTypeObj = kernel::single('logisticsmanager_service_' . $channel_type);
                                $channelTypeObj->delivery($delivery_id);
                            }
                        }
                        
                        //自有前端发货回写时如果是合并发货单，用子单回写
                        $foreground_shop_list = ome_shop_type::shop_list();
                        $shopObj = &app::get('ome')->model('shop');
                        $shop = $shopObj->dump($order['shop_id'], 'node_type');
                        $delivery = $deliveryObj->dump($delivery_id,'is_bind');
                        if($delivery['is_bind'] == 'true' && !in_array($shop['node_type'],$foreground_shop_list)){
                            //查找本订单对应的发货单
                            $rows = $odeliveryIds = array();
                            $deliveryOrderObj = &app::get('ome')->model('delivery_order');
                            $rows = $deliveryOrderObj->getList('delivery_id',array('order_id'=>$order_id));
                            foreach($rows as $val){
                                $odeliveryIds[] = $val['delivery_id'];
                            }

                            $delivery_ids = $deliveryObj->getItemsByParentId($delivery_id,'array');
                            foreach($delivery_ids as $v){
                                //只对本订单进行回写
                                if($v && in_array($v,$odeliveryIds)) {
                                    kernel::single('ome_service_delivery')->delivery($v);
                                    kernel::single('ome_service_delivery')->update_logistics_info($v); //更新发货物流信息
                                    kernel::single('ome_service_delivery')->update_status($v, '', true);
                                }
                                unset($v);
                            }
                        }else{
                            kernel::single('ome_service_delivery')->delivery($delivery_id);
                            kernel::single('ome_service_delivery')->update_logistics_info($delivery_id); //更新发货物流信息
                            kernel::single('ome_service_delivery')->update_status($delivery_id, '', true);
                        }
                        unset($shop,$delivery);
                    }
                }
            }
            unset($order,$deliveryIds);
        }
    }

    /**
     * 更新发货状态
     *
     * @param Integer $delivery_id
     * @return string
     */
    private function syncChangeStatus($delivery_id) {

        $dlyobj = &app::get('ome')->model('delivery');
        $delivery_id = intval($delivery_id);
        $delivery = $dlyobj->dump($delivery_id);
        if (is_array($delivery) && !empty($delivery)) {

            if (!in_array($delivery['status'], array('back', 'failed', 'cancel','return_back'))) {
                //检验操作
                if ($delivery['verify'] == 'false') {

                    if (!$dlyobj->verifyDelivery($delivery)) {
                        return false;
                    }
                }
                //出库操作
                if ($delivery['process'] == 'false') {
                    $msg = '';
                    kernel::database()->exec('begin');
                    //danny_freeze_stock_log
                    define('FRST_TRIGGER_OBJECT_TYPE','发货单：发货失败状态回写重试未发货发货');
                    define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：syncChangeStatus');
                    if (!$dlyobj->consignDelivery($delivery_id, 0, $msg, true)) {
                        kernel::database()->exec('rollback');
                        return false;
                    } else {
                        kernel::database()->exec('commit');
                    }
                }
            }
        } else {

            return false;
        }
        return true;
    }

//    /**
//     * 校验过程
//     *
//     * @param Array $deliver
//     * @return void
//     */
//    private function verifyDelivery($delivery) {
//
//        $deliver_id = $delivery['deliver_id'];
//        $dly_itemObj = &app::get('ome')->model('delivery_items');
//        $opObj = &app::get('ome')->model('operation_log');
//        //对发货单详情进行校验完成处理
//        if ($dly_itemObj->verifyItemsByDeliveryId($delivery_id)) {
//            $sdf['delivery_id'] = $delivery_id;
//            $sdf['verify'] = 'true';
//
//            if (!app::get('ome')->model('delivery')->save($sdf))
//                return false;
//
//            if ($delivery['is_bind'] == 'true') {
//                $ids = app::get('ome')->model('delivery')->getItemsByParentId($delivery_id, 'array');
//                foreach ($ids as $i) {
//                    app::get('ome')->model('delivery')->verifyItemsByDeliveryId($i);
//                }
//            }
//
//            if (kernel::single('desktop_user')->get_id())
//                $opObj->write_log('delivery_check@ome', $delivery_id, '发货单校验完成');
//            return true;
//        }else {
//
//            if (kernel::single('desktop_user')->get_id())
//                $opObj->write_log('delivery_check@ome', $delivery_id, '发货单校验未完成');
//            return false;
//        }
//    }

    function getFilters() {

        $base_filter = array();
        $base_filter['status'] = array('active', 'finish');
        //$base_filter['order_confirm_filter'] = "(sdb_ome_orders.op_id is not null AND sdb_ome_orders.group_id is not null ) AND (sdb_ome_orders.is_cod='true' OR sdb_ome_orders.pay_status='1' OR sdb_ome_orders.pay_status='4' OR sdb_ome_orders.pay_status='5') and logi_no <> ''";
        $base_filter['order_confirm_filter'] = "(sdb_ome_orders.op_id is not null OR sdb_ome_orders.group_id is not null ) AND (sdb_ome_orders.is_cod='true' OR sdb_ome_orders.pay_status='1' OR sdb_ome_orders.pay_status='4' OR sdb_ome_orders.pay_status='5') and logi_no <> ''";
        $base_filter['process_status'] = array('splited', 'confirmed', 'splitting');

        return $base_filter;
    }

    function batchCheck(&$rs = false){
        $ids        = urldecode($_POST['delivery_id']);
        $weight  = sprintf('%.2f',$_POST['weight']);
        if (empty($ids)){
            $tmp = array(array('bn'=>'*','msg'=>'请扫描快递单号'));
            echo json_encode($tmp);
            die;
        }

        $delivery_ids = array_unique(explode(',', $ids));

        //因数据库原因，导致部分sql执行不了，例如校验主单，明细没有校验成功
        $dlyObj = &$this->app->model('delivery');
        $dlyObj->repairCheck($delivery_ids);
        $dlyBillObj = &$this->app->model('delivery_bill');
        //逐个发货：发货判断，批量发货不做此过滤
        if ($_GET['delivery_type'] == 'single'){
            $return_error = $this->consign_filter('', $ids, $weight);
            if ($return_error){
                $tmp = array('status'=>'error','msg'=>$return_error);
                echo json_encode($tmp);
                die;
            }

            $minWeight = $this->app->getConf('ome.delivery.minWeight');
            $maxWeight = $this->app->getConf('ome.delivery.maxWeight');
            if($weight<$minWeight || $weight>$maxWeight){
                $tmp = array('status'=>'error','msg'=>'提交重量:'.$weight.',最小'.$minWeight.',最大'.$maxWeight.'包裹重量超出系统设置范围！');
                echo json_encode($tmp);
                die;
            }
        }

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if ($is_super) {
            $branch_ids = array('_ALL_');
        } else {
            $branch_ids = $oBranch->getBranchByUser(true);
        }


        $delivery_list = array();

        if ($delivery_ids){
            foreach ($delivery_ids as $v){
                # 过滤空值
                 if (empty($v)) continue;

                # 验证
                $delivery = kernel::single('ome_delivery_consign')->deliAllow($v,$branch_ids,$msg,$patch);
                if (!$delivery) {
                    $tmp[] = array('bn'=>$v,'msg'=>$msg);
                    continue;
                }

                $delivery_list[] = $delivery['delivery_id'];
            }

            // 获取申请过退款或已退款的订单号
            if (empty($tmp)){
                $order_exists_refund = ome_order_func::get_refund_orders($delivery_list, true);
                #属于是校验完成即发货的
                if($rs = 'CheckDelivery'){
                    #虽然有退款申请，但订单是已支付的,放过
                    if($order_exists_refund[0]['pay_status'] == '1'){
                        unset($order_exists_refund);
                    }
                }
                if ($order_exists_refund){
                    $tmp[]['order_exists_refund'] = json_encode($order_exists_refund);
                }
            }
        }

        if ($tmp){
            echo json_encode($tmp); die;
        }
        $rs = true;#发货判断完成的标示,这个对校验完成即发货有用
        echo "";
    }

    function batch_log_detail(){
        $log_id = $_GET['log_id'];
        $filter = array('log_id'=>$log_id);
        if ($_GET['status']) {
            $filter['status'] = $_GET['status'];
        }
        $bldObj  = &$this->app->model('batch_detail_log');
        $bldData = $bldObj->getList('*',$filter,0,-1);
        foreach($bldData as $k=>$v){
            $bldData[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }
        $this->pagedata['bldData'] = $bldData;
        $this->display('admin/delivery/batch_log_detail.html');
    }

 /**
     * 批量发货确认
     * 显示批量发货订单中有退款申请或已退款的订单，让管理员确认是否发货或不发货
     * @param json $data
     * @return null
     */
    function batch_delivery_confirm($data=null){
        $this->pagedata['delivery_type'] = $_GET['delivery_type'];
        $this->display('admin/order/batch_delivery_confirm.html');
    }

    function batchConsign(){

        $goto_url = 'index.php?app=ome&ctl=admin_consign&act=batch';
        $ids = $_POST['delivery_id'];
        $dlyObj = &$this->app->model('delivery');
		/*************************************************/
		$dlyBillObj = &$this->app->model('delivery_bill');
		$opObj = &app::get('ome')->model('operation_log');
		/*************************************************/
        $delivery_ids = explode(',', $ids);

        $batch_number = count($delivery_ids);
        $fail_number = 0;
        $batch_fail_logi_no = array();

        $checkInfo = $this->checkDeliveryStatus($delivery_ids);
        if(! empty($checkInfo)) {
            $info = '';
            foreach ($checkInfo as $v) {
                $info .= $v['bn'] . ': ' . $v['msg'];
            }

            $this->splash('error', $goto_url, $info,'', array('msg'=>$info));
            exit;
        }

        $delivery_result = true;
        $delivery_fail_bns = array();
        $delivery_succ = 'fail';
        if ($delivery_ids)
        foreach ($delivery_ids as $id){
                if (empty($id))
                    continue;

			/*************************************************/
			//检测单号存在于主物流单中还是子物流单
			//wujian@shopex.cn
			//2012年3月21日
			/*************************************************/
			$tempId = $id;
			$id = $dlyObj->checkDeliveryOrBill($id);

			$isBill = false;
			if($tempId!=$id){
				$isBill = true;
			}
			if($isBill){
				$dlyBill = $dlyBillObj->dump(array('logi_no|nequal' => $tempId),'*');
			}
			$dly = $dlyObj->dump(array('logi_no|nequal'=>$id));

			$logi_number = $dly['logi_number'];
			$delivery_logi_number =$dly['delivery_logi_number'];
			/*************************************************/

					kernel::database()->exec('begin');
            if ($dly && $dly['process']=='false'){
                /*************************************************/
				if($isBill){
					/*************************************************************************/
					//获取物流费用
					$area = $dly['consignee']['area'];
					$arrArea = explode(':', $area);
					$area_id = $arrArea[2];
     $delivery_cost_actual = $dlyObj->getDeliveryFreight($area_id,$dly['logi_id'],0);
					/*************************************************************************/
					$data = array(
						'status'=>'1',
						'weight'=>0,
						'delivery_cost_actual'=>$delivery_cost_actual,
						'delivery_time'=>time(),
					);
					$filter = array('logi_no'=>$tempId);
					$dlyBillObj->update($data,$filter);

					/*************************************************************************/
					$logstr = '批量发货,单号:'.$tempId;
					$opObj->write_log('delivery_bill_express@ome', $dly['delivery_id'], $logstr);
					/*************************************************************************/

					//加入如果$logi_number==$delivery_logi_number 但是发货状态没有改变的判
					/*$data = array(
						'delivery_logi_number'=>$delivery_logi_number+1,
					);
					$filter = array('delivery_id'=>$dly['delivery_id']);
					$dlyObj->update($data,$filter);

					if($logi_number==($delivery_logi_number+1)){
						//danny_freeze_stock_log
						define('FRST_TRIGGER_OBJECT_TYPE','发货单：批量发货');
						define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：batchConsign');
						if (!$dlyObj->consignDelivery($dly['delivery_id'], 0, $msg)) {
							$delivery_result = false;
							$delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
							kernel::database()->exec('rollback');
						}else{
							kernel::database()->exec('commit');
							$delivery_succ = 'succ';
						}
					}else{
						kernel::database()->exec('commit');
						$delivery_succ = 'succ';
					}*/
					if(($logi_number==$delivery_logi_number)&&$dly['status']<>'succ'){
						//danny_freeze_stock_log
						define('FRST_TRIGGER_OBJECT_TYPE','发货单：批量发货');
						define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：batchConsign');
						if (!$dlyObj->consignDelivery($dly['delivery_id'], 0, $msg)) {
							$delivery_result = false;
							$delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
							kernel::database()->exec('rollback');
						}else{
							kernel::database()->exec('commit');
							$delivery_succ = 'succ';
						}
					}else{
						$data = array(
							'delivery_logi_number'=>$delivery_logi_number+1,
						);
						$filter = array('delivery_id'=>$dly['delivery_id']);
						$dlyObj->update($data,$filter);

						if($logi_number==($delivery_logi_number+1)){
							//danny_freeze_stock_log
							define('FRST_TRIGGER_OBJECT_TYPE','发货单：批量发货');
							define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：batchConsign');
							if (!$dlyObj->consignDelivery($dly['delivery_id'], 0, $msg)) {
								$delivery_result = false;
								$delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
								kernel::database()->exec('rollback');
							}else{
								kernel::database()->exec('commit');
								$delivery_succ = 'succ';
							}
						}else{
							kernel::database()->exec('commit');
							$delivery_succ = 'succ';
						}
					}
				}else{
					$dlyBill = $dlyBillObj->dump(array('delivery_id|nequal' => $dly['delivery_id']),'*');
					if(empty($dlyBill)){
						//加入如果$logi_number==$delivery_logi_number 但是发货状态没有改变的判断
						if(($logi_number==$delivery_logi_number)&&$dly['status']<>'succ'){
							if (!$dlyObj->consignDelivery($dly['delivery_id'], 0, $msg)) {
								$delivery_result = false;
								$delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
								kernel::database()->exec('rollback');
							}else{
								kernel::database()->exec('commit');
								$delivery_succ = 'succ';
							}
						}else{
							$data = array(
								'delivery_logi_number'=>$delivery_logi_number+1,
							);
							$filter = array('delivery_id'=>$dly['delivery_id']);
							$dlyObj->update($data,$filter);

							if (!$dlyObj->consignDelivery($dly['delivery_id'], 0, $msg)) {
								$delivery_result = false;
								$delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
								kernel::database()->exec('rollback');
							}else{
								kernel::database()->exec('commit');
								$delivery_succ = 'succ';
							}
						}
					}else{
						$billfilter = array(
							'status'=>1,
							'delivery_id'=>$dly['delivery_id'],
						);
						$num = $dlyBillObj->count($billfilter);
						if($dly['delivery_logi_number']<($num+1)){
							$data = array(
								'delivery_logi_number'=>$delivery_logi_number+1,
								'weight'=>0
							);
							$filter = array('delivery_id'=>$dly['delivery_id']);
							$dlyObj->update($data,$filter);

							if($logi_number==($delivery_logi_number+1)){
								if (!$dlyObj->consignDelivery($dly['delivery_id'], 0, $msg)) {
									$delivery_result = false;
									$delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
									kernel::database()->exec('rollback');
								}else{
									kernel::database()->exec('commit');
									$delivery_succ = 'succ';
								}
							}else{
								kernel::database()->exec('commit');
								$delivery_succ = 'succ';
							}
						//加入如果$dly['delivery_logi_number']==($num+1)但是发货状态没有改变的判断
						}elseif(($dly['delivery_logi_number']==($num+1))&&$dly['status']<>'succ'){
							if (!$dlyObj->consignDelivery($dly['delivery_id'], 0, $msg)) {
								$delivery_result = false;
								$delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
								kernel::database()->exec('rollback');
							}else{
								kernel::database()->exec('commit');
								$delivery_succ = 'succ';
							}
						}else{
							kernel::database()->exec('commit');
							$delivery_succ = 'succ';
						}
					}
				}
				/*************************************************/
				/*if (!$dlyObj->consignDelivery($dly['delivery_id'], 0, $msg)) {
                    $delivery_result = false;
                    $delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
                    kernel::database()->exec('rollback');
                }else{
                    kernel::database()->exec('commit');
                    $delivery_succ = 'succ';
                }*/

            }else{
                $delivery_result = false;
            }
        }
  /*********************************************/
  $blObj  = &$this->app->model('batch_log');
  $bldObj  = &$this->app->model('batch_detail_log');
  $bldata = array(
      'op_id' => kernel::single('desktop_user')->get_id(),
      'op_name' => kernel::single('desktop_user')->get_name(),
      'createtime' => time(),
      'batch_number' => $batch_number,
      'fail_number' => $fail_number,
     );
  $blLogid = $blObj->save($bldata);

  if(count($batch_fail_logi_no)){
   foreach($batch_fail_logi_no as $logi_no_value){
    $bldetaildata = array(
      'log_id' => $blLogid,
      'createtime' => time(),
      'logi_no' => $logi_no_value,
      'status' => 'fail',
     );
    $bldObj->save($bldetaildata);
   }
  }
  /*********************************************/
        if ($delivery_result){
            $this->splash('success',$goto_url ,'发货完成');
            exit;
        }else{
            $msg['delivery_bn'] = implode("<br/>",$delivery_fail_bns);
            $msg['delivery_succ'] = $delivery_succ;
            if ($delivery_succ == 'succ'){
                $error_msg = '部分发货单发货失败';
            }else{
                $error_msg = '发货失败';
            }
            $this->splash('error', $goto_url, $error_msg,'', array('msg'=>$msg));
            exit;
        }
    }

    /**
     * 发货判断
     * @param array $dly 发货单dump标准结构数据
     * @param string $logi_no 物流单号
     * @param number $weight 重量
  *
  * update 加入子表信息
  * wujian@shopex.cn
  * 2012年3月19日
     */
    function consign_filter($dly,$logi_no,$weight='0'){
        if (empty($logi_no)){
            return '请输入快递单号';
        }
        $weightSet = &app::get('ome')->getConf('ome.delivery.weight');
        if (empty($weight) && $weightSet=='on'){
            return '请输入重量信息';
        }

        $dlyObj = &$this->app->model('delivery');

  //加入子表判断 wujian@shopex.cn
  $dlyObjBill = &$this->app->model('delivery_bill');
        if (empty($dly)){
            $dly = $dlyObj->dump(array('logi_no|nequal' => $logi_no),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));
        }
       if($dly['delivery_items']){
            foreach($dly['delivery_items'] as $ik=>$iv){

                if(app::get('taoguaninventory')->is_installed()){

                $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($iv['product_id'],$dly['branch_id']);

                    if(!$check_inventory){
                        return '货号:'.$iv['bn'].'正在盘点,请将该货物放回指定区域';


                    }
               }
            }
        }

  if (empty($dly)){
   $dlyBill = $dlyObjBill->dump(array('logi_no|nequal' => $logi_no),'*');
        }else{
				$billfilter = array(
					'status'=>1,
					'delivery_id'=>$dly['delivery_id'],
				);
				$num = $dlyObjBill->count($billfilter);
				if($dly['delivery_logi_number']>=($num+1)&&$dly['status']=='succ'){
					return '此物流运单已发货';
				}
		}


        if (!$dly&&!$dlyBill){
            return '无此物流运单号';
        }
        /*
   * 如果这个物流单号是子表中的物流单号的话
   * 找到该单号的主表的物流单号
   */
  if($dlyBill){
   $dly = $dlyObj->dump(array('delivery_id|nequal' => $dlyBill["delivery_id"]),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));
   $bill_logi_no = $logi_no;
   $logi_no = $dly["logi_no"];

   if($dlyBill['status']=='1'){
    return '此物流运单已发货';
   }
  }

        /*
         * 获取操作员管辖仓库
         */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_ids = $oBranch->getBranchByUser(true);
           if (!in_array($dly['branch_id'],$branch_ids))
               return '快递单号不在您管辖的仓库范围内';
        }

        //判断发货单相应订单是否有问题
        if (!$this->checkOrderStatus($dly['delivery_id'], true, $msg)){
            return $msg;
        }
        if ($dly['verify'] == 'false'){
            return '此物流运单号对应的发货单未校验';
        }
        if ($dly['process'] == 'true'){
            return '此物流运单号对应的发货单已发货';
        }
        foreach ($dly['delivery_items'] as $item){
            if ($item['verify'] == 'false'){
                return '此物流运单号对应的发货单详情未校验完成';
            }

            # 库存验证(除了原样寄回发货单)
            if ($dly['type'] == 'normal') {
                $re = $dlyObj->existStockIsPlus($item['product_id'],$item['number'],$item['item_id'],$dly['branch_id'],$err,$item['bn']);
                if (!$re){ return $err; }
            }
        }
    }

    /**
     * 发货处理
     *
     */
    function consign(){ 
        #[发货配置]是否启动拆单 ExBOY
        $dlyObj         = &$this->app->model('delivery');
        $split_seting   = $dlyObj->get_delivery_seting();
        
        $delivery_weight =  &app::get('ome')->getConf('ome.delivery.weight'); #发货配置，开启称重
        $check_delivery = &app::get('ome')->getConf('ome.delivery.check_delivery'); #发货配置，检验完即发货
        
        #开启称重时，不能使用校验完即发货功能
        if($delivery_weight == 'on'){
            $check_delivery = 'off';
        }
        if(!isset($check_delivery)||empty($check_delivery)){
            $check_delivery = 'off';
        }
        #配置开启校验完即发货，同时，前端，是来自校验即发货页面
        if($check_delivery && $_POST['check_delivery']){
            $check_delivery = 'on';
        }else{
            $check_delivery = 'off';
        }
        $this->begin("index.php?app=ome&ctl=admin_consign");

        $logi_no = $_POST['logi_no'];
        $weight  = sprintf('%.2f',$_POST['weight']);

        $minWeight = $this->app->getConf('ome.delivery.minWeight');
        $maxWeight = $this->app->getConf('ome.delivery.maxWeight');
        if($weight<$minWeight || $weight>$maxWeight){
            $this->end(false,'提交重量:'.$weight.',最小'.$minWeight.',最大'.$maxWeight.'包裹重量超出系统设置范围');
        }
        //$dlyObj = &$this->app->model('delivery');
        $dly = $dlyObj->dump(array('logi_no|nequal' => $logi_no),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));
        if($dly['delivery_items']){
            foreach($dly['delivery_items'] as $ik=>$iv){
                if(app::get('taoguaninventory')->is_installed()){
                    $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($iv['product_id'],$dly['branch_id']);
                    if(!$check_inventory){
                       $this->end(false,'货号:'.$iv['bn'].'正在盘点,请将该货物放回指定区域');
                    }
               }
            }
        }
        $return_error = $this->consign_filter($dly, $logi_no, $weight);
        if ($return_error){
            $this->end(false, app::get('base')->_($return_error));
        }
        /*
        * 如果物流单存在在子表中，用子表信息返回主表信息
        * wujian@shoepx.cn
        * 2012年3月19日
        */
        $dlyObjBill = &$this->app->model('delivery_bill');
        $opObj = &app::get('ome')->model('operation_log');
        if (empty($dly)){
            //加入子表判断 wujian@shopex.cn
            $dlyBill = $dlyObjBill->dump(array('logi_no|nequal' => $logi_no),'*');
        }

        /*
        * 如果这个物流单号是子表中的物流单号的话
        * 找到该单号的主表的物流单号
        */
        if($dlyBill){
            $dly = $dlyObj->dump(array('delivery_id|nequal' => $dlyBill["delivery_id"]),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));
            $bill_logi_no = $logi_no;
            $logi_no = $dly["logi_no"];
            $bill_flag = true;
        }
        $oObj = $this->app->model('orders');
        /**如果关闭称重时商品重量计算*/

        #如果明细下有一个商品重量为0全为0,否则为商品明细累加
        $weightSet = &app::get('ome')->getConf('ome.delivery.weight');
        if ($weightSet=='off') {
            $product_weight = 0;
            foreach($dly['delivery_order'] as $item){
                
                #[拆单]根据发货单中货品详细读取重量 ExBOY
                if(!empty($split_seting))
                {
                    $orderWeight  = $dlyObj->getDeliveryWeight($item['order_id'], array(), $dly['delivery_id']);
                }
                else 
                {
                    $orderWeight = $oObj->getOrderWeight($item['order_id']);
                }
                
                if($orderWeight==0){
                    $product_weight=0;
                    break;
                }else{
                    $product_weight+=$orderWeight;
                }
            }

            #商品重量有取商品重量
           if($product_weight>0){
               $weight = $product_weight;
           }else{
               $weight = $minWeight;//取最小商品重量
           }
        }
        /**/
        $logi_number = $dly['logi_number'];
        $delivery_logi_number =$dly['delivery_logi_number'];

        /****************************************************/
        

        foreach($dly['delivery_order'] as $order_id ){
            $orders = $order_id['order_id'];
            $order_detial = $oObj->dump(array('order_id'=>$orders),'pay_status');
			/*
            if($order_detial['pay_status'] == 4){
                $this->end('false','对应订单已部分退款，无法发货');
            }
			*/
            if($order_detial['pay_status'] == 5){
               $this->end('false','对应订单已全额退款，无法发货');
            }
        }
        //danny_freeze_stock_log
        define('FRST_TRIGGER_OBJECT_TYPE','发货单：逐单发货');
        define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：consign');

   #报警发货处理
   if($_POST['warn_status']=='1'){
        $opObj->write_log('delivery_weightwarn@ome', $dly["delivery_id"],'物流单号:'.$logi_no.',仍然发货（称重为：'.$weight.'g）');
   }
        if($bill_flag){
            //如果物流单号是在子物流单中的处理区域
            /*************************************************************************/
            //获取物流费用
            $area = $dly['consignee']['area'];
            $arrArea = explode(':', $area);
            $area_id = $arrArea[2];
            $delivery_cost_actual = $dlyObj->getDeliveryFreight($area_id,$dly['logi_id'],$weight);
            /*************************************************************************/

            $data = array(
                'status'=>'1',
                'weight'=>$weight,
                'delivery_cost_actual'=>$delivery_cost_actual,
                'delivery_time'=>time(),
            );
            $filter = array('logi_no'=>$bill_logi_no);
            $dlyObjBill->update($data,$filter);

            $logstr = '快递单号:'.$bill_logi_no.' 发货';
            $opObj->write_log('delivery_bill_express@ome', $dly["delivery_id"], $logstr);

            if(($logi_number==$delivery_logi_number)&&$dly['status']<>'succ'){
                if ($dlyObj->consignDelivery($dly['delivery_id'], $dly['weight'], $msg)){
                     $this->end(true, '发货处理完成');
                }else {
                     $msg['delivery_bn'] = $dly['delivery_bn'];
                     $this->end(false, '发货未完成', '', array('msg'=>$msg));
                }
            }else{
                $data = array('delivery_logi_number'=>$delivery_logi_number+1,);
                $filter = array('delivery_id'=>$dly['delivery_id']);
                $dlyObj->update($data,$filter);

                if($logi_number==($delivery_logi_number+1)){
                     if ($dlyObj->consignDelivery($dly['delivery_id'], $dly['weight'], $msg)){
                          $this->end(true, '发货处理完成');
                     }else {
                          $msg['delivery_bn'] = $dly['delivery_bn'];
                          $this->end(false, '发货未完成', '', array('msg'=>$msg));
                     }
                }else{
                    $this->end(true, '发货处理完成');
                }
            }
        }else{
            //如果物流单号是在主物流单中的处理区域
            $dlyBill = $dlyObjBill->dump(array('delivery_id|nequal' => $dly['delivery_id']),'*');
            //判断这个主物流单有没有对应的子物流单
            if(empty($dlyBill)){
                //加入如果$logi_number==$delivery_logi_number 但是发货状态没有改变的判断
                if(($logi_number==$delivery_logi_number)&&$dly['status']<>'succ'){
                     if ($dlyObj->consignDelivery($dly['delivery_id'], $weight, $msg)){
                        $this->end(true, '发货处理完成');
                     }else {
                        $msg['delivery_bn'] = $dly['delivery_bn'];
                        $this->end(false, '发货未完成', '', array('msg'=>$msg));
                     }
                }else{
                     $data = array('delivery_logi_number'=>$delivery_logi_number+1,);
                     $filter = array('delivery_id'=>$dly['delivery_id']);
                     $dlyObj->update($data,$filter);

                     if ($dlyObj->consignDelivery($dly['delivery_id'], $weight, $msg)){
                        $this->end(true, '发货处理完成');
                     }else {
                        $msg['delivery_bn'] = $dly['delivery_bn'];
                        $this->end(false, '发货未完成', '', array('msg'=>$msg));
                     }
                }
            }else{
                //如果存在子物流单
                //计算已经发货的子物流单、总共的物流单
                //1,查询实际的发货数量，和总物流数量
                $billfilter = array(
                     'status'=>1,
                     'delivery_id'=>$dly['delivery_id'],
                );
                $num = $dlyObjBill->count($billfilter);
                if($dly['delivery_logi_number']<($num+1)){
                     $data = array(
                          'delivery_logi_number'=>$delivery_logi_number+1,
                          'weight'=>$weight
                     );
                     $filter = array('delivery_id'=>$dly['delivery_id']);
                     $dlyObj->update($data,$filter);

                     if($logi_number==($delivery_logi_number+1)){
                          if ($dlyObj->consignDelivery($dly['delivery_id'], $weight, $msg)){
                                $this->end(true, '发货处理完成');
                          }else {
                                $msg['delivery_bn'] = $dly['delivery_bn'];
                                $this->end(false, '发货未完成', '', array('msg'=>$msg));
                          }
                     }
                     $this->end(true, '发货处理完成');
                    //加入如果$logi_number==$delivery_logi_number 但是发货状态没有改变的判断
                }elseif(($dly['delivery_logi_number']==$dly['logi_number'])&&$dly['status']<>'succ'){
                     if ($dlyObj->consignDelivery($dly['delivery_id'], $weight, $msg)){
                            $this->end(true, '发货处理完成');
                     }else {
                              $msg['delivery_bn'] = $dly['delivery_bn'];
                              $this->end(false, '发货未完成', '', array('msg'=>$msg));
                     }
                }else{
                    $this->end(false, '此物流运单已发货');
                }
            }
            $this->end(false, '发货未完成');
        }
    }

    /**
     * 判断发货单号相关订单处理状态是否处于取消或异常
     *
     * @param bigint $dly_id
     * @param String $msg 返回消息提示
     * @param boolean $msg_flag 默认false终止并提示,true不终止返回消息
     * @return null
     */
    function checkOrderStatus($dly_id, $msg_flag=false, &$msg){
        if (!$dly_id)
            return false;
        $Objdly = &$this->app->model('delivery');
        $delivery = $Objdly->dump($dly_id);
        if (!$Objdly->existOrderStatus($dly_id, $delivery['is_bind'])){
            $msg = "发货单已无法操作，请到订单处理中心处理";
            if ($msg_flag == false){
                echo $msg;
                exit("<script>parent.MessageBox.error('发货单已无法操作，请到订单处理中心处理!');</script>");
            }else{
                return false;
            }
        }
        if (!$Objdly->existOrderPause($dly_id, $delivery['is_bind'])){
            $msg = "发货单相关订单存在异常，请到订单处理中心处理";
            if ($msg_flag == false){
                echo $msg;
                exit("<script>parent.MessageBox.error('发货单相关订单存在异常，请到订单处理中心处理!');</script>");
            }else{
                return false;
            }
        }
        return true;
    }

    private function checkDeliveryStatus($delivery_ids) {
        $dlyObj = &$this->app->model('delivery');
  $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = $oBranch->getBranchByUser(true);

        if ($delivery_ids && is_array($delivery_ids)){
            foreach ($delivery_ids as $v){
                if (empty($v))
                    continue;
    //wujian@shopex.cn 2012年3月20日
    /************************************/
    $a = $v;
    $v = $dlyObj->checkDeliveryOrBill($v);
    /*************************************/
                $dly = $dlyObj->dump(array('logi_no|nequal' => $v),'*',array('delivery_items'=>array('*')));
                $delivery_list[] = $dly['delivery_id'];
                if (!$dly){
                    $tmp[] = array('bn'=>$a,'msg'=>'无此快递单号');
                    continue;
                }
                if (!$is_super){
                   if (!in_array($dly['branch_id'],$branch_ids)){
                       $tmp[] = array('bn'=>$a,'msg'=>'你无权对此快递单进行发货');
                       continue;
                   }
                }

                if (!$dlyObj->existOrderStatus($dly['delivery_id'], $dly['is_bind'])){
                    $tmp[] = array('bn'=>$a,'msg'=>'此快递单号对应发货单不处于可发货状态');
                    continue;
                }
                if (!$dlyObj->existOrderPause($dly['delivery_id'], $dly['is_bind'])){
                    $tmp[] = array('bn'=>$a,'msg'=>'对应发货单订单存在异常');
                    continue;
                }
                if ($dly['status'] == 'back'){
                    $tmp[] = array('bn'=>$a,'msg'=>'对应发货单已打回');
                    continue;
                }
                if ($dly['verify'] == 'false'){
                    $tmp[] = array('bn'=>$a,'msg'=>'对应发货单未校验');
                    continue;
                }
                if ($dly['process'] == 'true'){
                    $tmp[] = array('bn'=>$a,'msg'=>'对应发货单已发货');
                    continue;
                }
                foreach ($dly['delivery_items'] as $item){
                    if ($item['verify'] == 'false'){
                        $tmp[] = array('bn'=>$a,'msg'=>'对应发货单已发货');
                        break;
                    }
                    $re = $dlyObj->existStockIsPlus($item['product_id'],$item['number'],$item['item_id'],$dly['branch_id'],$err,$item['bn']);
                    if (!$re){
                        $tmp[] = array('bn'=>$a,'msg'=>$err);
                        break;
                    }
                     if(app::get('taoguaninventory')->is_installed()){
                         $check_inventory = kernel::single('taoguaninventory_inventorylist')->checkproductoper($item['product_id'],$dly['branch_id']);

                        if(!$check_inventory){
                           $tmp[] = array('bn'=>$a,'msg'=>'正在盘点,请将该货物放回指定区域');
                            break;
                        }
                    }
                }
                $orderInfo = $dlyObj->getOrderByDeliveryId($dly['delivery_id']);
                if($orderInfo['pay_status'] == '5'){
                    $tmp[] = array('bn'=>$a,'msg'=>'对应订单 '.$orderInfo['order_bn'].' 已退款');
                    continue;
                }

            }
            //获取申请过退款或已退款的订单号
            /*if (empty($tmp)){
                $order_exists_refund = ome_order_func::get_refund_orders($delivery_list, true);
                if ($order_exists_refund){
                    $tmp[]['order_exists_refund'] = json_encode($order_exists_refund);
                }
            }此处已经不需要判断*/
        }

        return $tmp;
    }

    function group_consign(){
        $deliCfgLib = kernel::single('ome_delivery_cfg');
        $consign = $deliCfgLib->getValue('consign');
        if($consign==1){
            $deliveryObj = &$this->app->model('delivery');
            $orderTypeObj = &app::get('omeauto')->model('order_type');
            $groupFilter['tid'] = $deliCfgLib->getValue('ome_delivery_consign_group');
            $groupFilter['disabled'] = 'false';
            $groupFilter['delivery_group'] = 'true';
            $orderTypes = $orderTypeObj->getList('*',$groupFilter);

            $filter = array(
                'parent_id' => 0,
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

            $deliverys = $deliveryObj->getList('delivery_id,delivery_group,logi_no',$filter);

            $deliveryGroup = array();
            foreach($orderTypes as $key => $type){
                $deliveryGroup[$type['tid']] = $type;
                $deliveryGroup[$type['tid']]['deliverys'] = array();
                $deliveryGroup[$type['tid']]['dCount'] = 0;
            }
            foreach($deliverys as $key => $value){
                if($value['logi_no'] && $value['logi_no'] != ''){
                    if($value['delivery_group']>0 && $deliveryGroup[$value['delivery_group']]){
                        $deliveryGroup[$value['delivery_group']]['deliverys'][] = $value['delivery_id'];
                        $deliveryGroup[$value['delivery_group']]['dCount']++;
                    }
                    $deliveryAll[] = $value['delivery_id'];
                }
            }
            /*$deliveryGroup[8388607] = array(
                'tid' => 8388607,
                'name' => '全部分组',
                'deliverys' => $deliveryAll,
                'dCount' => count($deliveryAll),
            );*/

            $this->pagedata['num'] = $deliveryObj->countNoProcessDelivery();
            $this->pagedata['deliveryGroup'] = $deliveryGroup;
            $this->pagedata['jsonDeliveryGroup'] = json_encode($deliveryGroup);

            /* 操作时间间隔 start */
            $lastGroupDelivery = app::get('ome')->getConf('lastGroupDelivery'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
            $groupDeliveryIntervalTime = app::get('ome')->getConf('ome.groupDelivery.intervalTime'); //每次操作的时间间隔

            if(($lastGroupDelivery['execTime']+60*$groupDeliveryIntervalTime)<time()){
                $this->pagedata['is_allow'] = true;
            }else{
                $this->pagedata['is_allow'] = false;
            }
            $this->pagedata['lastGroupDeliveryTime'] = !empty($lastGroupDelivery['execTime']) ? date('Y-m-d H:i:s',$lastGroupDelivery['execTime']) : '';
            $this->pagedata['groupDeliveryIntervalTime'] = $groupDeliveryIntervalTime;
            $this->pagedata['currentTime'] = time();
            /* 操作时间间隔 end */

            $this->page("admin/delivery/process_group_consign.html");
        }else{
            echo "未开启分组发货！";
        }
    }

    function ajaxDoGroupConsign(){
        #[发货配置]是否启动拆单 ExBOY
        $deliveryObj    = &$this->app->model('delivery');
        $split_seting   = $deliveryObj->get_delivery_seting();
        
        $tmp = explode('||', $_POST['ajaxParams']);
        $group = $tmp[0];
        $delivery = explode(';', $tmp[1]);
        $minWeight = $this->app->getConf('ome.delivery.minWeight');
        if(count($delivery)>0 && $group>0){
            /* 执行时间判断 start */
            $pageBn = intval($_POST['pageBn']);
            $lastGroupDelivery = app::get('ome')->getConf('lastGroupDelivery'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
            $groupDeliveryIntervalTime = app::get('ome')->getConf('ome.groupDelivery.intervalTime'); //每次操作的时间间隔

            if($pageBn !=$lastGroupDelivery['pageBn'] && ($lastGroupDelivery['execTime']+60*$groupDeliveryIntervalTime)>time()){
                echo json_encode(array('total' => count($delivery), 'succ' => 0, 'fail' => count($delivery), 'failInfo'=>array('执行时间不合法')));
                exit;
            }
            if($pageBn !=$lastGroupDelivery['pageBn'] && $pageBn<$lastGroupDelivery['execTime']){
                echo json_encode(array('total' => count($delivery), 'succ' => 0, 'fail' => count($delivery), 'failInfo'=>array('提交参数过期')));
                exit;
            }

            //记录本次获取订单时间
            $currentGroupDelivery = array(
                'execTime'=>time(),
                'pageBn'=>$pageBn,
            );
            app::get('ome')->setconf('lastGroupDelivery',$currentGroupDelivery);
            /* 执行时间判断 end */

            $deliCfgLib = kernel::single('ome_delivery_cfg');
            $weightGroup = $deliCfgLib->getValue('ome_delivery_consign_weight');
            $group_weight = $weightGroup[$group]  ? intval($weightGroup[$group]) : 0;
            #获取商品重量
            $productsObj = &$this->app->model('products');


            //$deliveryObj = &$this->app->model('delivery');
            $deliveryBillObj = &$this->app->model('delivery_bill');
            $opObj = &app::get('ome')->model('operation_log');
            $filter = array(
                'delivery_id'=>$delivery,
            );
            $deliverys = $deliveryObj->getList('*',$filter);
            $succ = 0;
            $fail = 0;
            $failInfo = array();
            #计算商品重量
            $orderObj = &$this->app->model('orders');
            $deliveryOrderObj = &$this->app->model('delivery_order');
            foreach($deliverys as $value){

                #如果明细下有一个商品重量为0重量取系统设置重量,否则为商品明细累加
                $delivery_order = $deliveryOrderObj->getList('order_id',array('delivery_id'=>$value['delivery_id']));
                $product_weight = 0;
                foreach($delivery_order as $item){
                      
                  #[拆单]根据发货单中货品详细读取重量 ExBOY
                  if(!empty($split_seting))
                  {
                      $orderWeight  = $deliveryObj->getDeliveryWeight($item['order_id'], array(), $value['delivery_id']);
                  }
                  else 
                  {
                    $orderWeight = $orderObj->getOrderWeight($item['order_id']);
                  }
                    if($orderWeight==0){
                        $product_weight=0;
                        break;
                    }else{
                        $product_weight+=$orderWeight;
                    }
                }

                #商品重量有取商品重量
               if($product_weight>0){
                   $weight = $product_weight;
               }else{
                   $weight = $group_weight;
               }

                $checkInfo = $this->checkDeliveryStatus(array($value['logi_no']));
                if(empty($checkInfo) && $value['process'] == 'false' && $value['logi_no'] != '') {
                    /*******************************************************************/
                    $billInfo = $deliveryObj->checkDeliveryHaveBill($value['delivery_id']);
                    /*******************************************************************/
                    if($billInfo){
                        $flag = true;
                        foreach($billInfo as $v){
                            if(empty($v['logi_no'])){
                                $flag = false;
                                break;
                            }
                        }
                        if($flag){
                            /*************************************************************************/
                            //获取物流费用
                            $area = $value['consignee']['area'];
                            $arrArea = explode(':', $area);
                            $area_id = $arrArea[2];

                            $delivery_cost_actual = $deliveryObj->getDeliveryFreight($area_id,$value['logi_id'],$minWeight);
                            /*************************************************************************/
                            $data = array(
                                'status'=>'1',
                                'weight'=>$minWeight,
                                'delivery_cost_actual'=>$delivery_cost_actual,
                                'delivery_time'=>time(),
                            );
                            $filter = array('delivery_id'=>$value['delivery_id'],'status'=>'0');
                            $num = $deliveryBillObj->update($data,$filter);

                            /*************************************************************************/
                            $logstr = '分组发货('.$num.')份';
                            $opObj->write_log('delivery_bill_express@ome', $value['delivery_id'], $logstr);
                            /*************************************************************************/

                            $numdata = array('delivery_logi_number'=>$value['logi_number']);
                            $numfilter = array('delivery_id'=>$value['delivery_id']);
                            $deliveryObj->update($numdata,$numfilter);

                            $patchWeight = kernel::single('eccommon_math')->number_multiple(array(floatval($minWeight),($value['logi_number']-1)));
                            $weight = kernel::single('eccommon_math')->number_minus(array($weight,$patchWeight));
                            $weight = $weight > 0 ? $weight : floatval($minWeight);

                            //danny_freeze_stock_log
                            define('FRST_TRIGGER_OBJECT_TYPE','发货单：分组发货');
                            define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：ajaxDoGroupConsign');
                            if($deliveryObj->consignDelivery($value['delivery_id'], $weight, $msg)){
                                $succ++;
                            }else{
                                $fail++;
                                $failInfo[] = $value['delivery_bn'];
                            }
                        }else{
                            $fail++;
                            $failInfo[] = $value['delivery_bn'];
                        }
                    }else{
                        if($deliveryObj->consignDelivery($value['delivery_id'], $weight, $msg)){
                            $succ++;
                        }else{
                            $fail++;
                            $failInfo[] = $value['delivery_bn'];
                        }
                    }
                }else{
                    $fail++;
                    $failInfo[] = $value['delivery_bn'];
                }
                usleep(200000);
            }
            echo json_encode(array('total' => count($delivery), 'succ' => $succ, 'fail' => $fail, 'failInfo'=>$failInfo));
        }else{
            echo json_encode(array('total' => 0, 'succ' => 0, 'fail' => 0, 'failInfo'=>$failInfo));
        }
    }

 /*
  * 补打物流单
  * wujian@shopex.cn
  * 2012年3月9日
  */
 public function fill_delivery(){
  $this->page("admin/delivery/fill_delivery.html");
 }

 public function fill_delivery_confirm(){
   $str = array();
   $logi_no = $_POST['logi_no'];

   $dlyObj = &$this->app->model('delivery');
   $dlyBillObj = &$this->app->model('delivery_bill');

   //快递单是否存在delivery_bill中,那从delivery_bill中获取delivery_id，通过delivery_id去delivery匹配信息
   $dlyBillInfo = $dlyBillObj->dump(array('logi_no'=>$logi_no),'*');
   $dlyBillInfoId = $dlyBillInfo['delivery_id'];
   if($dlyBillInfoId){
    $dlyInfo = $dlyObj->dump(array('delivery_id'=>$dlyBillInfoId),'*');
    $return_error = $this->consign_filterc('', $dlyInfo['logi_no']);
    if ($return_error){
     $str['str'] = $return_error;
     $tmp = array('status'=>'error','msg'=>$str);
     echo json_encode($tmp);
     die;
             }else{
     $str['str'] = '<tr style="background-color:#f6f6f6; color:red; font-weight:bold;height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
     $str['str'] .= '<td>'.$dlyInfo['logi_no'].'</td>';
     $str['str'] .= '<td>'.$dlyInfo['weight'].'</td>';
     $str['str'] .= '<td></td>';
     $str['str'] .= '<td>'.$this->statusinfo($dlyInfo['process']).'</td>';
     $str['str'] .= '<td></td></tr>';

     $dlyBillAllInfo = $dlyBillObj->getList('*',array('delivery_id'=>$dlyBillInfoId));
     foreach($dlyBillAllInfo as $item){
      $str['str'] .= '<tr style="height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
      $str['str'] .= '<td>'.$item['logi_no'].'</td>';
      $str['str'] .= '<td>'.$item['weight'].'</td>';
      $str['str'] .= '<td>'.date('Y-m-d H:s:m',$item['create_time']).'</td>';
      $str['str'] .= '<td>'.$this->statusinfo($item['status'],0).'</td>';
      if($item['status']==1){
       $str['str'] .= '<td></td></tr>';
      }else{
       $str['str'] .= '<td><button type="button" onclick="del_deliveryBill('.$item['log_id'].','.$dlyInfo['delivery_id'].')" style="height:28px;width:40px;">删除</button></td></tr>';
      }
     }
     $str['delivery_id'] = $dlyBillInfoId;
     $tmp = array('status'=>'success','msg'=>$str);
     echo json_encode($tmp);
     die;
     //$dlyInfo = $dlyObj->dump(array('delivery_id'=>$dlyBillInfoId),'*');
     //$return_error = $this->consign_filterc('', $dlyInfo['logi_no']);
    }
   }else{
    $return_error = $this->consign_filterc('', $logi_no);
    if ($return_error){
     $str['str'] = $return_error;
     $tmp = array('status'=>'error','msg'=>$str);
     echo json_encode($tmp);
     die;
             }else{
     $dlyInfo = $dlyObj->dump(array('logi_no|nequal'=>$logi_no),'*');
     $str['str'] = '<tr style="background-color:#f6f6f6; color:red;font-weight:bold;height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
     $str['str'] .= '<td>'.$dlyInfo['logi_no'].'</td>';
     $str['str'] .= '<td>'.$dlyInfo['weight'].'</td>';
     $str['str'] .= '<td></td>';
     $str['str'] .= '<td>'.$this->statusinfo($dlyInfo['process']).'</td>';
     $str['str'] .= '<td></td></tr>';

     $dlyBillAllInfo = $dlyBillObj->getList('*',array('delivery_id'=>$dlyInfo['delivery_id']));
     foreach($dlyBillAllInfo as $item){
      $str['str'] .= '<tr style="height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
      $str['str'] .= '<td>'.$item['logi_no'].'</td>';
      $str['str'] .= '<td>'.$item['weight'].'</td>';
      $str['str'] .= '<td>'.date('Y-m-d H:s:m',$item['create_time']).'</td>';
      $str['str'] .= '<td>'.$this->statusinfo($item['status'],0).'</td>';
      if($item['status']==1){
       $str['str'] .= '<td></td></tr>';
      }else{
       $str['str'] .= '<td><button type="button" onclick="del_deliveryBill('.$item['log_id'].','.$dlyInfo['delivery_id'].')" style="height:28px;width:40px;">删除</button></td></tr>';
      }
     }
     $str['delivery_id'] = $dlyInfo['delivery_id'];
     $tmp = array('status'=>'success','msg'=>$str);
     echo json_encode($tmp);
     die;
    }
   }

 }
 /**
     * 快递单状态翻译显示
     * wujian@shopex.cn
  * 2012年3月13日
     */
 function statusinfo($status,$style=1){
  switch($style){
   case 1:
    switch($status){
     case 'false':
      return '未发货';
     case 'true':
      return '已发货';
    }
   case 0:
    switch($status){
     case 0:
      return '未发货';
     case 1:
      return '<font color=red>已发货</font>';
     case 2:
      return '已取消';
    }
  }
 }

 /**
     * 快递单判断判断
     * @param array $dly 发货单dump标准结构数据
     * @param string $logi_no 物流单号
     * @param number $weight 重量
     */
    function consign_filterc($dly,$logi_no){
        if (empty($logi_no)){
            return '请输入快递单号';
        }
        $dlyObj = &$this->app->model('delivery');
        if (empty($dly)){
            $dly = $dlyObj->dump(array('logi_no|nequal' => $logi_no),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*')));
        }

        if (!$dly){
            return '无此物流运单号';
        }
        /*
         * 获取操作员管辖仓库
         */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_ids = $oBranch->getBranchByUser(true);
           if (!in_array($dly['branch_id'],$branch_ids))
               return '快递单号不在您管辖的仓库范围内';
        }

        //判断发货单相应订单是否有问题
        if (!$this->checkOrderStatus($dly['delivery_id'], true, $msg)){
            return $msg;
        }
        if ($dly['verify'] == 'false'){
            return '此物流运单号对应的发货单未校验';
        }
        if ($dly['process'] == 'true'){
            return '此物流运单号对应的发货单已发货';
        }
        foreach ($dly['delivery_items'] as $item){

            if ($item['verify'] == 'false'){
                return '此物流运单号对应的发货单详情未校验完成';
            }
            $re = $dlyObj->existStockIsPlus($item['product_id'],$item['number'],$item['item_id'],$dly['branch_id'],$err,$item['bn']);
            if (!$re){
                return $err;
            }
        }
    }

    /*
    * 删除子快递单
    * wujian@shoepx.cn
    * 2012年3月15日
    */
    function del_deliveryBill(){
        $log_id = $_POST['log_id'];
        $delivery_id = $_POST['delivery_id'];

        $dlyBillObj = app::get('ome')->model('delivery_bill');
        if($dbo=$dlyBillObj->dump(array('log_id'=>$log_id))){
            $dlyCorpObj = &app::get('ome')->model('dly_corp');
            $delivery = app::get('ome')->model('delivery');
            $dlyInfo = $delivery->dump(array('delivery_id'=>$delivery_id));
            $status = false;
            if($dbo['status']=="1"){
                $status = true;
            }
            //写入日志
            $opObj = &$this->app->model('operation_log');
            if(empty($dbo['logi_no'])){
                $logstr = '删除快递单,单号为空';
            }else{
                $dlyCorp = $dlyCorpObj->dump($dlyInfo['logi_id'], 'tmpl_type');
                //回收电子面单
                if ($dlyCorp['tmpl_type'] == 'electron') {
                    $waybillObj = kernel::single('logisticsmanager_service_waybill');
                    $waybillObj->recycle_waybill($dbo['logi_no']);
                }
                $logstr = '删除快递单,单号:'.$dbo['logi_no'];
            }
            $opObj->write_log('delivery_bill_delete@ome', $delivery_id, $logstr);

            $result = $dlyBillObj->delete(array('log_id'=>$log_id));
            if($result){
                if($status){
                    $sql = "update sdb_ome_delivery set logi_number=logi_number-1,delivery_logi_number=delivery_logi_number-1 where delivery_id=".$delivery_id;
                }else{
                    $sql = "update sdb_ome_delivery set logi_number=logi_number-1 where delivery_id=".$delivery_id;
                }
                $delivery->db->exec($sql);


                $str['str'] = '<tr style="background-color:#f6f6f6; color:red;font-weight:bold;height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
                $str['str'] .= '<td>'.$dlyInfo['logi_no'].'</td>';
                $str['str'] .= '<td>'.$dlyInfo['weight'].'</td>';
                $str['str'] .= '<td></td>';
                $str['str'] .= '<td>'.$this->statusinfo($dlyInfo['status']).'</td>';
                $str['str'] .= '<td></td></tr>';

                $dlyBillAllInfo = $dlyBillObj->getList('*',array('delivery_id'=>$delivery_id));
                foreach($dlyBillAllInfo as $item){
                    $str['str'] .= '<tr style="height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
                    $str['str'] .= '<td>'.$item['logi_no'].'</td>';
                    $str['str'] .= '<td>'.$item['weight'].'</td>';
                    $str['str'] .= '<td>'.date('Y-m-d H:s:m',$item['create_time']).'</td>';
                    $str['str'] .= '<td>'.$this->statusinfo($item['status'],0).'</td>';
                    if($item['status']==1){
                        $str['str'] .= '<td></td></tr>';
                    }else{
                        $str['str'] .= '<td><button type="button" onclick="del_deliveryBill('.$item['log_id'].','.$dlyInfo['delivery_id'].')" style="height:28px;width:40px;">删除</button></td></tr>';
                    }
                }

                $str['delivery_id'] = $delivery_id;
                $tmp = array('status'=>'success','msg'=>$str);
                echo json_encode($tmp);
            }else{
                $str['str'] = '删除失败';
                $tmp = array('status'=>'error','msg'=>$str);
                echo json_encode($tmp);
                die;
            }
        }else{
            $str['str'] = '删除失败';
            $tmp = array('status'=>'error','msg'=>$str);
            echo json_encode($tmp);
            die;
        }
    }

    /**
     *保存批量发货至记录队列表中
     */
    function doBatchConsign(){
        $goto_url = 'index.php?app=ome&ctl=admin_consign&act=batch';
        $ids = $_POST['delivery_id'];

        $delivery_ids = explode(',', $ids);
        $delivery_ids = array_filter($delivery_ids);

        if ( !$delivery_ids ) {
            $this->splash('success',$goto_url ,'快递单列表为空！');
        }

        $batch_number = count($delivery_ids);
        $blObj  = $this->app->model('batch_log');

        $bldata = array(
          'op_id' => kernel::single('desktop_user')->get_id(),
          'op_name' => kernel::single('desktop_user')->get_name(),
          'createtime' => time(),
          'batch_number' => $batch_number,
          'log_type'=>'consign',
          'log_text'=>serialize($delivery_ids)
         );
        $result = $blObj->save($bldata);

        $this->splash('success',$goto_url ,'发货完成');
    }

    /**
     *获取发货记录历史
    */
    function batchConsignLog(){
        $blObj  = &$this->app->model('batch_log');

        $dayBegin = mktime(0,0,0,date("m"),date("d"),date("Y"));//当天开始时间戳
        $dayEnd = mktime(23,59,59,date("m"),date("d"),date("Y"));//当天结束时间戳

        $blResult = $blObj->getList('*', array('log_type'=>'consign','createtime|than'=>$dayBegin,'createtime|lthan'=>$dayEnd), 0, -1,'createtime desc');
        //base_kvstore::instance('ome/delivery/consign/batch')->fetch('result',$logi_status);
        foreach($blResult as $k=>$v){
            $blResult[$k]['status_value'] = kernel::single('ome_batch_log')->getStatus($v['status']);
            $blResult[$k]['fail_number'] = $v['fail_number'];
            $blResult[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }
        if($blResult){
            echo json_encode($blResult);
        }
    }
    /**
     *更新处理中发货记录值
    */
    function updateBatchConsignLog(){

        $log_id =urldecode($_POST['log_id']);

        if($log_id){
            $log_id = explode(',',$log_id);

            $status="'0','2'";
            $blResult = kernel::single('ome_batch_log')->get_List('consign',$log_id,$status);
            base_kvstore::instance('ome/delivery/consign/batch')->fetch('result',$logiNoBatchdata);

            foreach($blResult as $k=>$v){
                $status = $v['status'];
                $fail_number = $v['fail_number'];
                $blResult[$k]['status_value'] = kernel::single('ome_batch_log')->getStatus($status);
                $blResult[$k]['fail_number'] = $fail_number;

            }
            if($blResult){
                echo json_encode($blResult);
            }
        }
    }


    /**
    * 商品重量报警判断
    */
    function weightWarn(){
        $type = $_GET['type'];
        $logi_no = $_POST['logi_no'];
        if($type=='countpackage'){
            #判断发货单是否多包裹
            $delivery = &$this->app->model('delivery')->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
            $dlyBillObj = &$this->app->model('delivery_bill');
            $dlyBill = $dlyBillObj->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
            if($delivery){
                $delivery_id = $delivery['delivery_id'];
            }else{
                $delivery_id = $dlyBill['delivery_id'];
            }
            $billfilter = array(

              'delivery_id'=>$delivery_id,
             );
            $num = $dlyBillObj->count($billfilter);
            echo $num;


        }else{

            $weight = $_POST['weight'];

            $product_weight = $this->app->model('delivery')->getWeightbydelivery_id($logi_no);

            echo json_encode($product_weight);
        }



    }

    /**
    * 发货重量报警页面提醒
    */
    function showWeightWarn(){

        $logi_no = $_GET['logi_no'];
        $weight = $_GET['weight'];
        $type = $_GET['type'];
        #确认取消条码
        $stock_confirm= &app::get('ome')->getConf('purchase.stock_confirm');
        $stock_cancel= &app::get('ome')->getConf('purchase.stock_cancel');
        $this->pagedata['stock_confirm'] = $stock_confirm;
        $this->pagedata['stock_cancel'] = $stock_cancel;
        if($type=='unweight'){

            $product_weight = $this->app->model('delivery')->getWeightbydelivery_id($logi_no);
            $this->pagedata['product_weight'] = $product_weight;

            unset($product_weight);
            $this->pagedata['logi_no'] = $logi_no;
            $this->pagedata['weight'] = $weight;
            $this->page('admin/delivery/delivery_noweight.html');
        }else if($type=='weightwarn'){
            $this->pagedata['logi_no'] = $logi_no;
            $this->pagedata['weight'] = $weight;
            $problem_package = &app::get('ome')->getConf('ome.delivery.problem_package');
            $this->pagedata['problem_package'] = $problem_package;
            #如果是按退回去检查
            if($problem_package==0){
                $this->page('admin/delivery/delivery_weightwarnback.html');
            }else{
                $this->page('admin/delivery/delivery_weightwarn.html');
            }
            #两种情况时

        }else if($type=='addLog'){
             //写入日志
             $logi_no = $_POST['logi_no'];
             $weight = $_POST['weight'];
             $logerror = $_POST['logerror'];
            $dlyObj = &$this->app->model('delivery');
            $productObj= &$this->app->model('products');
            $dlyBillObj = &$this->app->model('delivery_bill');
            $dlyBill = $dlyBillObj->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
            $dlyfather = $dlyObj->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
            if($dlyBill){
                $delivery_id = $dlyBill['delivery_id'];
            }elseif($dlyfather){
                $delivery_id = $dlyfather['delivery_id'];

            }

            $opObj = &$this->app->model('operation_log');
            if($logerror=='1'){
                $logstr = '运单号:'.$logi_no.',检查后再发货(包裹内有商品未录入重量)';
            }else{
                $logstr = '运单号:'.$logi_no.',检查后再发货(称重为:'.$weight.'g)';
            }
            echo $logstr;
            $opObj->write_log('delivery_weightwarn@ome', $delivery_id, $logstr);
            return true;

        }
    }
    #逐单发货时，根据物流单号，获取货号、货品名称
    function getProcutInfo(){
        $logi_no = $_POST['logi_no'];
        $deliveryObj = $this->app->model('delivery');
        $procut_info = $deliveryObj->getProcutInfo($logi_no);
        $productObj= &$this->app->model('products');
        $str = '';
        $product_weight = $deliveryObj->getWeightbydelivery_id($logi_no);
        $total_weight = 0;
        foreach($procut_info as $v){
            if(isset($v['bn']) && isset($v['product_name']) && isset($v['number'])){
                $bn = $v['bn'];
                $products = $productObj->dump(array('bn'=>$bn),'weight');
                $weight = sprintf('%.2f',$products['weight']*$v['number']);
                $total_weight+=$weight;
                $str .= "<tr><td width='40%' align='center'>".$v['bn'].'</td>'."<td width='40%' align='center'>".$v['product_name']."</td>"."<td  width='10%' align='center'>".$v['number']."</td><td align='center'>".$weight."g</td></tr>";
            }
        }
        $str.="<tr ><td style='font-weight:bold;' align='right'>合计:</td><td colspan='3' align='left;' style='font-weight:bold;padding-left:10px;'>".$total_weight."g</td></tr>";
        echo $str;
    }



    #校验完成即发货：准备校验
    function CheckDelivery(){
        $db = kernel::database();
        $transaction =  $db->beginTransaction();
        $result = $this->doCheck($_rs);
        #校验已完成
        if($_rs){
            $_POST['delivery_id']  = urldecode($_POST['logi_no']);
            $_GET['delivery_type'] = 'single';
            $rs2 = 'CheckDelivery';#校验完即发货类型
            #继续判断发货条件
            $result = $this->batchCheck($rs2);
            if($rs2 !== 'CheckDelivery'){
                if($rs2){
                    $db->commit($transaction);
                }else{
                    $db->rollBack();
                }
            }
            return $result;
        }else{
            $db->rollBack();
            #校验未完成，返回校验错误
            return $result;
        }
    }    
    #校验完成即发货：开始校验
    function doCheck(&$rs = false){
        $logi_no  = urldecode($_POST['logi_no']);
        $checkType = in_array($_POST['checkType'],array('barcode','all')) ? $_POST['checkType'] : 'barcode';
        if (empty($_POST['delivery_id'])){
            $tmp = array('result'=>false,'msg'=>'发货单ID传入错误');
            echo json_encode($tmp);die;
        }
        if ($_POST['logi_no'] == ''){
            $tmp = array('result'=>false,'msg'=>'请扫描快递单号');
            echo json_encode($tmp);die;
        }
        foreach(kernel::servicelist('ome.delivery') as $o){
            if(method_exists($o,'pre_docheck')){
                $message = "";
                $result = $o->pre_docheck($_POST,$message);
                if(!$result){
                    $tmp = array('result'=>false,'msg'=>$message);
                    echo json_encode($tmp);die;
                }
            }
        }
        $dly_id   = $_POST['delivery_id'];
        $count    = $_POST['count'];
        $number   = $_POST['number'];
    
        $_rs = $this->checkOrderStatus2($dly_id);//判断发货单相应订单是否有问题
        if($_rs['result'] == false){
            echo json_encode($_rs);die;
        }
        $count == 0;
        if ($count == 0 || $number == 0){
            $tmp = array('result'=>false,'msg'=>'对不起，校验提交的数据错误');
            echo json_encode($tmp);die;
        }
        $deliveryObj  = &$this->app->model('delivery');
        $dly          = $deliveryObj->dump($dly_id,'*', array('delivery_items'=>array('*')));
        $verify = $dly['verify'];
        
        if ($dly['logi_no'] != $logi_no){
            $tmp = array('result'=>false,'msg'=>'扫描的快递单号与系统中的快递单号不对应');
            echo json_encode($tmp);die;
        }
        $total = 0;
        foreach ($dly['delivery_items'] as $i){
            $total += $i['number'];
        }
        /* if ($number != $total){
        $this->end(false, '对不起，校验提交的数据与发货单数据不对应', '', $autohide);
        } */
        $opObj        = &$this->app->model('operation_log');
        $dly_itemObj  = &app::get('ome')->model('delivery_items');
    
    
        if ($count === $number) {
            #对发货单详情进行校验完成处理
            if ($deliveryObj->verifyDelivery($dly)){
                if(is_array($_POST['serial_data']) && count($_POST['serial_data'])>0){
                    $productObj = &$this->app->model('products');
                    $productSerialObj = &$this->app->model('product_serial');
                    $serialLogObj = &$this->app->model('product_serial_log');
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    foreach($_POST['serial_data'] as $key=>$val){
                        foreach($val as $serial){
                            $serialData['branch_id'] = $dly['branch_id'];
                            $serialData['product_id'] = $_POST['product'][$key];
                            $serialData['bn'] = $key;
                            $serialData['serial_number'] = $serial;
                            $serialData['status'] = 1;
                            $productSerialObj->save($serialData);
    
                            $logData['item_id'] = $serialData['item_id'];
                            $logData['act_type'] = 0;
                            $logData['act_time'] = time();
                            $logData['act_owner'] = $opInfo['op_id'];
                            $logData['bill_type'] = 0;
                            $logData['bill_no'] = $dly['delivery_id'];
                            $logData['serial_status'] = 1;
                            $serialLogObj->save($logData);
                            unset($serialData,$logData);
                        }
                   }
               }
                //增加发货单校验把保存后的扩展
                foreach(kernel::servicelist('ome.delivery') as $o){
                    if(method_exists($o,'after_docheck')){
                        $data = $_POST;
                        $o->after_docheck($data);
                    }
                }
                $rs = true;#校验完成
                return true;
            }else {
                $tmp = array('result'=>false,'msg'=>'发货单校验未完成，请重新校验');
                echo json_encode($tmp);die;
            }
         }
         #以下是部分校验
         else {
            #保存部分校验结果
            $flag = $dly_itemObj->verifyItemsByDeliveryIdFromPost($dly_id);
            if ($flag){
                $opObj->write_log('delivery_check@ome', $dly_id, '发货单部分检验数据保存完成');
                $this->end(true, '发货单部分检验数据保存完成', '', $autohide);
            }else {
                $this->end(false, '发货单校验未完成，请重新校验', '', $autohide);
            }
         }
    }   
    #校验完成即发货：开始发货
    function doCheckDelivery(){
        $goto_url = 'index.php?app=ome&ctl=admin_check&act=index&type=all';
        $ids = $_POST['delivery_id'];
    
        $delivery_ids = explode(',', $ids);
        $delivery_ids = array_filter($delivery_ids);
    
         
        if ( !$delivery_ids ) {
            $this->splash('success',$goto_url ,'快递单列表为空！');
        }
        $delivery_id = $_POST['delivery_id'];
        $msg = '校验完成即发货：开始发货';
        $ObjOp        = &$this->app->model('operation_log');
        $ObjOp->write_log('delivery_checkdelivery@ome',$delivery_id,$msg,time());
        #走原来发货流程
        return $this->consign();
    }
    #校验发货单状态
    function checkOrderStatus2($dly_id, $msg_flag=false, &$msg=NULL){
        $Objdly = &$this->app->model('delivery');
        $delivery = $Objdly->dump($dly_id);
        if (!$Objdly->existOrderStatus($dly_id, $delivery['is_bind'])){
            $msg = "发货单已无法操作，请到订单处理中心处理";
            return array('result'=>false,'msg'=>$msg);
        }
        if (!$Objdly->existOrderPause($dly_id, $delivery['is_bind'])){
            $msg = "发货单相关订单存在异常，请到订单处理中心处理";
            return array('result'=>false,'msg'=>$msg);
        }
        return array('result'=>true);
    }        
}

?>
