<?php

class wms_ctl_admin_consign extends desktop_controller{

    var $name = "发货处理";
    var $workground = "wms_delivery";

    public function _views()
    {

        $mdl_order = app::get('ome')->model('orders');

        # 未发货分两部分：sync=none+线上店铺 OR ship_status=0+线下店铺
        $shops = app::get('ome')->model('shop')->getList('shop_id,node_id');

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

        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $mdl_order->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&flt=' . $_GET['flt'] . '&view=' . $k . $s;
        }

        return $sub_menu;
    }

    /**
     *
     * 逐单发货的入口展示页
     */
    function index(){
        $weightSet = &app::get('wms')->getConf('wms.delivery.weight');
        $minWeight = app::get('wms')->getConf('wms.delivery.minWeight');
        $deliveryObj  = &app::get('wms')->model('delivery');
        $this->pagedata['weightSet'] = $weightSet;
        $this->pagedata['minWeight'] = $minWeight;

        //称重报警max_weightwarn，weightpercent,min_weight,warnproblem_package
        $weightWarn = &app::get('wms')->getConf('wms.delivery.weightwarn');
        $min_weightwarn = &app::get('wms')->getConf('wms.delivery.min_weightwarn');
        $max_weightwarn = &app::get('wms')->getConf('wms.delivery.max_weightwarn');
        $minpercent = &app::get('wms')->getConf('wms.delivery.minpercent');
        $maxpercent = &app::get('wms')->getConf('wms.delivery.maxpercent');
        $problem_package = &app::get('wms')->getConf('wms.delivery.problem_package');

        $this->pagedata['weightWarn'] = $weightWarn;
        $this->pagedata['max_weightwarn'] = $max_weightwarn;
        $this->pagedata['min_weightwarn'] = $min_weightwarn;
        $this->pagedata['minpercent'] = $minpercent;
        $this->pagedata['maxpercent'] = $maxpercent;
        $this->pagedata['problem_package'] = $problem_package;

        $numShow = app::get('wms')->getConf('wms.delivery.consignnum.show');
        if ($numShow == 'false' || (cachecore::fetch('quick_access') !== false)) {
            $this->pagedata['deliverynum'] = '未知';
        } else {
            $this->pagedata['deliverynum'] = $deliveryObj->countNoProcessDeliveryBill();
        }

        $this->pagedata['logi_no_position'] = (0==($pos=app::get('wms')->getConf('wms.delivery.logi'))) ? 'up' : 'down';

        $this->page("admin/delivery/process_consign_index.html");
    }

    /**
     *
     * 批量发货的入口展示页
     */
    function batch(){
        $stock_confirm= &app::get('ome')->getConf('purchase.stock_confirm');
        $stock_cancel= &app::get('ome')->getConf('purchase.stock_cancel');
        $this->pagedata['stock_confirm'] = $stock_confirm;
        $this->pagedata['stock_cancel'] = $stock_cancel;
        $deliveryObj  = &app::get('wms')->model('delivery');
        $this->pagedata['deliverynum'] = $deliveryObj->countNoProcessDeliveryBill();
        $this->page("admin/delivery/process_consign_batch.html");
    }

    /**
     *
     * 分组发货的入口展示页
     */
    function group_consign(){
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $consign = $deliCfgLib->getValue('consign');
        if($consign == 1){
            $deliveryObj = &app::get('wms')->model('delivery');
            $orderTypeObj = &app::get('omeauto')->model('order_type');
            $groupFilter['tid'] = $deliCfgLib->getValue('wms_delivery_consign_group');
            $groupFilter['disabled'] = 'false';
            $groupFilter['delivery_group'] = 'true';
            $orderTypes = $orderTypeObj->getList('*',$groupFilter);

            $filter = array(
                'disabled' => 'false',
                'process_status' => 3,
                'status' => 0
            );
            $oBranch = &app::get('ome')->model('branch');
            $is_super = kernel::single('desktop_user')->is_super();
            if (!$is_super) {
                $branch_ids = $oBranch->getBranchByUser(true);
                if ($branch_ids) {
                    $filter['branch_id'] = $branch_ids;
                }
            }

            $deliverys = $deliveryObj->getList('delivery_id,delivery_group',$filter);

            $deliveryGroup = array();
            foreach($orderTypes as $key => $type){
                $deliveryGroup[$type['tid']] = $type;
                $deliveryGroup[$type['tid']]['deliverys'] = array();
                $deliveryGroup[$type['tid']]['dCount'] = 0;
            }
            foreach($deliverys as $key => $value){
                if($value['delivery_group']>0 && $deliveryGroup[$value['delivery_group']]){
                    $deliveryGroup[$value['delivery_group']]['deliverys'][] = $value['delivery_id'];
                    $deliveryGroup[$value['delivery_group']]['dCount']++;
                }
                $deliveryAll[] = $value['delivery_id'];
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
            $lastGroupDelivery = app::get('wms')->getConf('lastGroupDelivery'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
            $groupDeliveryIntervalTime = app::get('wms')->getConf('wms.groupDelivery.intervalTime'); //每次操作的时间间隔

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

    /**
     *
     * 发货前的发货单相关信息检查
     */
    function batchCheck(&$rs = false){
        $logi_nos        = urldecode($_POST['delivery_id']);
        $weight  = $_POST['weight'];
        if (empty($logi_nos)){
            $tmp = array(array('bn'=>'*','msg'=>'请扫描快递单号'));
            echo json_encode($tmp);
            die;
        }

        $dlyCheckLib = kernel::single('wms_delivery_check');

        //逐个发货：发货判断，批量发货不做此过滤
        if ($_GET['delivery_type'] == 'single'){
            $return_error = $dlyCheckLib->consignAllow('', $logi_nos, $weight);
            if ($return_error){
                $tmp = array('status'=>'error','msg'=>$return_error);
                echo json_encode($tmp);
                die;
            }
        }else{
            $logi_no_arr = array_unique(explode(',', $logi_nos));
            $delivery_list = array();

            if ($logi_no_arr){
                foreach ($logi_no_arr as $v){
                    //过滤空值
                    if (empty($v)) continue;

                    //验证
                    $return_error = $dlyCheckLib->consignAllow('', $v, false);
                    if ($return_error) {
                        $tmp[] = array('bn'=>$v,'msg'=>$return_error);
                        continue;
                    }
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
        $bldObj  = &app::get('wms')->model('batch_detail_log');
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

    /**
     * 发货处理
     *
     */
    function consign(){
        $this->begin("index.php?app=wms&ctl=admin_consign");

        $logi_no = $_POST['logi_no'];
        $weight  = $_POST['weight'];

        $dlyObj = &app::get('wms')->model('delivery');
        $dlyBillObj = &app::get('wms')->model('delivery_bill');
        $deliveryBillLib = kernel::single('wms_delivery_bill');
        $opObj = &app::get('ome')->model('operation_log');
        $wmsCommonLib = kernel::single('wms_common');
        $dlyProcessLib = kernel::single('wms_delivery_process');

        $primary = false;
        $secondary = false;
        //如果没有发货单信息，则根据物流单号识别是主单还是次单,并获取相关信息

        $delivery_id = $deliveryBillLib->getDeliveryIdByPrimaryLogi($logi_no);
        if(!is_null($delivery_id)){
            $primary = true;
            $dly = $dlyObj->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
        }else{
            $delivery_id = $deliveryBillLib->getDeliveryIdBySecondaryLogi($logi_no);
            if(!is_null($delivery_id)){
                $secondary = true;
                $dly = $dlyObj->dump(array('delivery_id' => $delivery_id),'*',array('delivery_items'=>array('*')));
            }
        }

        $logi_number = $dly['logi_number'];
        $delivery_logi_number =$dly['delivery_logi_number'];

        //检查前端订单是否退款,原有逻辑是否需要?

        //danny_freeze_stock_log
        define('FRST_TRIGGER_OBJECT_TYPE','发货单：逐单发货');
        define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_consign：consign');

        //报警发货处理
        /*
        if($_POST['warn_status']=='1'){
            $opObj->write_log('delivery_weightwarn@wms', $dly["delivery_id"],'物流单号:'.$logi_no.',仍然发货（称重为：'.$weight.'g）');
        }
		*/

        //获取物流费用
        $area = $dly['consignee']['area'];
        $arrArea = explode(':', $area);
        $area_id = $arrArea[2];
        $delivery_cost_actual = $wmsCommonLib->getDeliveryFreight($area_id,$dly['logi_id'],$weight);

        //如果是次物流单号
        if($secondary){
            $data = array(
                'status'=>'1',
                'weight'=>$weight,
                'delivery_cost_actual'=>$delivery_cost_actual,
                'delivery_time'=>time(),
                'type' => 2,
            );
            $filter = array('logi_no'=>$logi_no);
            $dlyBillObj->update($data,$filter);

            $logstr = '快递单号:'.$logi_no.' 发货';
            $opObj->write_log('delivery_bill_express@wms', $dly["delivery_id"], $logstr);

            if(($logi_number==$delivery_logi_number)&&$dly['status'] != 3){
                if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                     $this->end(true, '发货处理完成');
                }else {
                     $msg['delivery_bn'] = $dly['delivery_bn'];
                     $this->end(false, '发货未完成', '', array('msg'=>$msg));
                }
            }else{
                $data = array('delivery_logi_number'=>$delivery_logi_number+1,'weight'=>$dly['weight']+$weight,'delivery_cost_actual'=>$dly['delivery_cost_actual']+$delivery_cost_actual);
                $filter = array('delivery_id'=>$dly['delivery_id']);
                $dlyObj->update($data,$filter);

                if($logi_number==($delivery_logi_number+1)){
                     if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
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
            //判断这个主物流单有没有对应的次物流单,等于1的时候只有一个包裹单
            if($logi_number == 1){
                if(($logi_number==$delivery_logi_number)&&$dly['status'] != 3){
                     if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                        $this->end(true, '发货处理完成');
                     }else {
                        $msg['delivery_bn'] = $dly['delivery_bn'];
                        $this->end(false, '发货未完成', '', array('msg'=>$msg));
                     }
                }else{
                    $data = array(
                        'status'=>'1',
                        'weight'=>$weight,
                        'delivery_cost_actual'=>$delivery_cost_actual,
                        'delivery_time'=>time(),
                        'type' => 1,
                    );
                    $filter = array('logi_no'=>$logi_no);
                    $dlyBillObj->update($data,$filter);

                    $data = array('delivery_logi_number'=>$delivery_logi_number+1,'weight'=>$dly['weight']+$weight,'delivery_cost_actual'=>$dly['delivery_cost_actual']+$delivery_cost_actual);
                    $filter = array('delivery_id'=>$dly['delivery_id']);
                    $dlyObj->update($data,$filter);

                    if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
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
                if(($logi_number > $delivery_logi_number)){
                    $data = array(
                        'status'=>'1',
                        'weight'=>$weight,
                        'delivery_cost_actual'=>$delivery_cost_actual,
                        'delivery_time'=>time(),
                        'type' => 1,
                    );
                    $filter = array('logi_no'=>$logi_no);
                    $dlyBillObj->update($data,$filter);

                    $data = array(
    					'delivery_logi_number'=>$delivery_logi_number+1,
    					'weight'=>$dly['weight']+$weight,
    					'delivery_cost_actual'=>$dly['delivery_cost_actual']+$delivery_cost_actual,
                    );
                    $filter = array('delivery_id'=>$dly['delivery_id']);
                    $dlyObj->update($data,$filter);

                    if($logi_number==($delivery_logi_number+1)){
                        if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                            $this->end(true, '发货处理完成');
                        }else {
                            $msg['delivery_bn'] = $dly['delivery_bn'];
                            $this->end(false, '发货未完成', '', array('msg'=>$msg));
                        }
                    }
                    $this->end(true, '发货处理完成');
                //加入如果$logi_number==$delivery_logi_number 但是发货状态没有改变的判断
                }elseif(($delivery_logi_number == $logi_number) && $dly['status'] != 3){
                    if ($dlyProcessLib->consignDelivery($dly['delivery_id'])){
                        $this->end(true, '发货处理完成');
                    }else {
                        $msg['delivery_bn'] = $dly['delivery_bn'];
                        $this->end(false, '发货未完成', '', array('msg'=>$msg));
                    }
                }else{
                    $this->end(false, '此物流运单已发货');
                }
            }
        }
    }

    /**
     *
     * 分组发货执行方法
     */
    function ajaxDoGroupConsign(){
        $tmp = explode('||', $_POST['ajaxParams']);
        $group = $tmp[0];
        $delivery = explode(';', $tmp[1]);
        $minWeight = app::get('wms')->getConf('wms.delivery.minWeight');
        if(count($delivery)>0 && $group>0){
            /* 执行时间判断 start */
            $pageBn = intval($_POST['pageBn']);
            $lastGroupDelivery = app::get('wms')->getConf('lastGroupDelivery'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
            $groupDeliveryIntervalTime = app::get('wms')->getConf('ome.groupDelivery.intervalTime'); //每次操作的时间间隔

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

            $deliCfgLib = kernel::single('wms_delivery_cfg');
            $weightGroup = $deliCfgLib->getValue('wms_delivery_consign_weight');
            $group_weight = $weightGroup[$group]  ? intval($weightGroup[$group]) : 0;
            #获取商品重量
            $productsObj = &app::get('ome')->model('products');


            $deliveryObj = &app::get('wms')->model('delivery');
            $deliveryBillObj = &app::get('wms')->model('delivery_bill');
            $dlyBillLib = kernel::single('wms_delivery_bill');
            $opObj = &app::get('ome')->model('operation_log');
            $wmsCommonLib = kernel::single('wms_common');
            $dlyCheckLib = kernel::single('wms_delivery_check');
            $dlyProcessLib = kernel::single('wms_delivery_process');

            $filter = array(
                'delivery_id'=>$delivery,
            );
            $deliverys = $deliveryObj->getList('*',$filter);
            $succ = 0;
            $fail = 0;
            $failInfo = array();
            foreach($deliverys as $value){
                
                $weight = $value['net_weight']>0 ? $value['net_weight'] : $group_weight;
                $weight = $weight ? $weight : $minWeight;

                //获取物流费用
                $area = $value['ship_area'];
                $arrArea = explode(':', $area);
                $area_id = $arrArea[2];
                $delivery_cost_actual = $wmsCommonLib->getDeliveryFreight($area_id,$value['logi_id'],$weight);

                $logi_no = $dlyBillLib->getPrimaryLogiNoById($value['delivery_id']);
                $return_error = $dlyCheckLib->consignAllow('', $logi_no, $weight);
                if(empty($return_error)) {

                    $billInfo = $dlyCheckLib->unDlyChildBills($value['delivery_id']);
                    if($billInfo){

                        $flag = true;
                        foreach($billInfo as $v){
                            if(empty($v['logi_no'])){
                                $flag = false;
                                break;
                            }
                        }

                        if($flag){
                            $data = array(
                                'status'=> 1,
                                'weight'=>0.00,
                                'delivery_cost_actual'=> 0.00,
                                'delivery_time'=>time(),
                            );
                            $filter = array('delivery_id'=>$value['delivery_id'],'status'=> 0, 'type'=>2);
                            $num = $deliveryBillObj->update($data,$filter);

                            $logstr = '分组发货('.$num.')份';
                            $opObj->write_log('delivery_bill_express@wms', $value['delivery_id'], $logstr);

                            $data = array(
                                'status'=> 1,
                                'weight'=>$weight,
                                'delivery_cost_actual'=>$delivery_cost_actual,
                                'delivery_time'=>time(),
                            );
                            $filter = array('delivery_id'=>$value['delivery_id'],'status'=> 0, 'type'=>1);
                            $deliveryBillObj->update($data,$filter);

                            $numdata = array('delivery_logi_number'=>$value['logi_number'],'weight'=>$weight,'delivery_cost_actual'=>$delivery_cost_actual);
                            $numfilter = array('delivery_id'=>$value['delivery_id']);
                            $deliveryObj->update($numdata,$numfilter);

                            if($dlyProcessLib->consignDelivery($value['delivery_id'])){
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
                        $data = array(
                            'status'=> 1,
                            'weight'=>$weight,
                            'delivery_cost_actual'=>$delivery_cost_actual,
                            'delivery_time'=>time(),
                        );
                        $filter = array('delivery_id'=>$value['delivery_id'],'status'=> 0, 'type'=>1);
                        $deliveryBillObj->update($data,$filter);

                        $numdata = array('delivery_logi_number'=>$value['logi_number'],'weight'=>$weight,'delivery_cost_actual'=>$delivery_cost_actual);
                        $numfilter = array('delivery_id'=>$value['delivery_id']);
                        $deliveryObj->update($numdata,$numfilter);

                        if($dlyProcessLib->consignDelivery($value['delivery_id'])){
                            //分组发货日志
                            $logstr = '分组发货完成';
                            $opObj->write_log('delivery_process@wms', $value['delivery_id'], $logstr);
                            $succ++;
                        }else{
                            $fail++;
                            $failInfo[] = $value['delivery_bn'];
                        }
                    }
                }else{
                    $fail++;
                    $failInfo[] = $value['delivery_bn'].$return_error;
                }
                usleep(200000);
            }
            echo json_encode(array('total' => count($delivery), 'succ' => $succ, 'fail' => $fail, 'failInfo'=>$failInfo));
        }else{
            echo json_encode(array('total' => 0, 'succ' => 0, 'fail' => 0, 'failInfo'=>$failInfo));
        }
    }

    /**
     *
     * 补打物流单入口页
     */
    public function fill_delivery(){
        $this->page("admin/delivery/fill_delivery.html");
    }

    /**
     *
     * 补打物流单的详细页
     */
    public function fill_delivery_confirm(){
        $str = array();
        $logi_no = $_POST['logi_no'];

        $dlyObj = &app::get('wms')->model('delivery');
        $dlyBillObj = &app::get('wms')->model('delivery_bill');
        $dlyCheckLib = kernel::single('wms_delivery_check');

        //识别是主单还是次单
        $dlyBillInfo = $dlyBillObj->dump(array('logi_no'=>$logi_no),'delivery_id,type');
        $delivery_id = $dlyBillInfo['delivery_id'];
        if($delivery_id && $dlyBillInfo['type'] == 2){
            $dlyInfo = $dlyObj->dump(array('delivery_id'=>$delivery_id),'*');
            $return_error = $dlyCheckLib->extLogiNoAllow($dlyInfo, $logi_no);
            if ($return_error){
                $str['str'] = $return_error;
                $tmp = array('status'=>'error','msg'=>$str);
                echo json_encode($tmp);
                die;
            }
        }elseif($delivery_id && $dlyBillInfo['type'] == 1){
            $dlyInfo = $dlyObj->dump(array('delivery_id'=>$delivery_id),'*');
            $return_error = $dlyCheckLib->extLogiNoAllow($dlyInfo, $logi_no);
            if($return_error){
                $str['str'] = $return_error;
                $tmp = array('status'=>'error','msg'=>$str);
                echo json_encode($tmp);
                die;
            }
        }else{
            $str['str'] = '物流单号不存在';
            $tmp = array('status'=>'error','msg'=>$str);
            echo json_encode($tmp);
            die;
        }

        $dlyBillAllInfo = $dlyBillObj->getList('*',array('delivery_id'=>$dlyInfo['delivery_id']));
        foreach($dlyBillAllInfo as $item){
            if($item['type'] == 1){
                $str['str'] = '<tr style="background-color:#f6f6f6; color:red; font-weight:bold;height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
                $str['str'] .= '<td>'.$item['logi_no'].'</td>';
                $str['str'] .= '<td>'.$item['weight'].'</td>';
                $str['str'] .= '<td></td>';
                $str['str'] .= '<td>'.$this->statusinfo($item['status']).'</td>';
                $str['str'] .= '<td></td></tr>';
            }else{
                $str['str'] .= '<tr style="height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
                $str['str'] .= '<td>'.$item['logi_no'].'</td>';
                $str['str'] .= '<td>'.$item['weight'].'</td>';
                $str['str'] .= '<td>'.date('Y-m-d H:s:m',$item['create_time']).'</td>';
                $str['str'] .= '<td>'.$this->statusinfo($item['status'],0).'</td>';
                if($item['status']==1){
                    $str['str'] .= '<td></td></tr>';
                }else{
                    $str['str'] .= '<td><button type="button" onclick="del_deliveryBill('.$item['b_id'].','.$dlyInfo['delivery_id'].')" style="height:28px;width:40px;">删除</button></td></tr>';
                }
            }
        }

        $str['delivery_id'] = $dlyInfo['delivery_id'];
        $tmp = array('status'=>'success','msg'=>$str);
        echo json_encode($tmp);
        die;
    }

	/**
	 *
	 * 删除子快递单
	 */
    function del_deliveryBill(){
        $b_id = $_POST['b_id'];
        $delivery_id = $_POST['delivery_id'];

        $dlyBillObj = app::get('wms')->model('delivery_bill');
        if($dbo=$dlyBillObj->dump(array('b_id'=>$b_id))){
            $dlyCorpObj = &app::get('ome')->model('dly_corp');
            $delivery = app::get('wms')->model('delivery');
            $dlyInfo = $delivery->dump(array('delivery_id'=>$delivery_id));
            $status = false;
            if($dbo['status'] == 1){
                $status = true;
            }

            //写入日志
            $opObj = &app::get('ome')->model('operation_log');
            if(empty($dbo['logi_no'])){
                $logstr = '删除快递单,单号为空';
            }else{
                $dlyCorp = $dlyCorpObj->dump($dlyInfo['logi_id'], 'tmpl_type,channel_id');
                //回收电子面单
                
                if ($dlyCorp['tmpl_type'] == 'electron') {
                    $waybillObj = kernel::single('logisticsmanager_service_waybill');
                    
                    $waybillObj->recycle_waybill($dbo['logi_no'],$dlyCorp['channel_id'],$delivery_id);
                    //如果是申通发送取消

                }
                $logstr = '删除快递单,单号:'.$dbo['logi_no'];

            }
            $opObj->write_log('delivery_bill_delete@wms', $delivery_id, $logstr);

            $result = $dlyBillObj->delete(array('b_id'=>$b_id));
            if($result){
                $deliveryObj = app::get('wms')->model('delivery');
                if($status){
                    $sql = "update sdb_wms_delivery set logi_number=logi_number-1,delivery_logi_number=delivery_logi_number-1 where delivery_id=".$delivery_id;
                }else{
                    $sql = "update sdb_wms_delivery set logi_number=logi_number-1 where delivery_id=".$delivery_id;
                }
                $deliveryObj->db->exec($sql);
                //京东电子面单补打
                $channel_info = kernel::single('wms_delivery_print_ship')->getWaybillType($delivery_id);
                if (in_array($channel_info['channel_type'],array('360buy'))) {
                    $logi_total = $dlyBillObj->count(array('delivery_id'=>$delivery_id,'type'=>'2'));
                    $delivery_data = $dlyBillObj->dump(array('delivery_id' => $delivery_id,'type'=>'1'),'logi_no');
                    $logi_no_total = $logi_total+1;
                    $logi_no_num = 1;
                    if ($logi_total>0) {
                        $dlyBillObj->db->exec("DELETE FROM sdb_wms_delivery_bill WHERE delivery_id=".$delivery_id." AND `type`='2'");
                        for($i=0;$i<$logi_total;$i++) {
                            $data = array('delivery_id' => $delivery_id,'create_time'=>time(),'type'=>2);
                            $logi_no_num++;
                            $data['logi_no'] = $delivery_data['logi_no'].'-'.$logi_no_num.'-'.$logi_no_total.'-';
                            $dlyBillObj->insert($data);
                        }
                    }
                }
                //
                $dlyInfo = $deliveryObj->dump(array('delivery_id'=>$delivery_id));
                $dlyBillAllInfo = $dlyBillObj->getList('*',array('delivery_id'=>$delivery_id));
                foreach($dlyBillAllInfo as $item){
                    if($item['type'] == 1){
                        $str['str'] = '<tr style="background-color:#f6f6f6; color:red;font-weight:bold;height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
                        $str['str'] .= '<td>'.$item['logi_no'].'</td>';
                        $str['str'] .= '<td>'.$item['weight'].'</td>';
                        $str['str'] .= '<td></td>';
                        $str['str'] .= '<td>'.$this->statusinfo($item['status']).'</td>';
                        $str['str'] .= '<td></td></tr>';
                    }else{
                        $str['str'] .= '<tr style="height:35px;"><td>'.$dlyInfo['logi_name'].'</td>';
                        $str['str'] .= '<td>'.$item['logi_no'].'</td>';
                        $str['str'] .= '<td>'.$item['weight'].'</td>';
                        $str['str'] .= '<td>'.date('Y-m-d H:s:m',$item['create_time']).'</td>';
                        $str['str'] .= '<td>'.$this->statusinfo($item['status'],0).'</td>';
                        if($item['status']==1){
                            $str['str'] .= '<td></td></tr>';
                        }else{
                            $str['str'] .= '<td><button type="button" onclick="del_deliveryBill('.$item['b_id'].','.$dlyInfo['delivery_id'].')" style="height:28px;width:40px;">删除</button></td></tr>';
                        }
                    }
                }

                $str['delivery_id'] = $delivery_id;

                //判断总包裹是否等于包裹数如果等于执行发货操作
                if ($dlyInfo['logi_number'] ==$dlyInfo['delivery_logi_number'] ) {
                    if($dlyInfo['status'] != 3){
                        $dlyProcessLib = kernel::single('wms_delivery_process');
                        $dlyProcessLib->consignDelivery($dlyInfo['delivery_id']);
                    
                    }
                }
                //
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
	 *
     * 快递单状态翻译显示
     */
    function statusinfo($status,$style=1){
        switch($style){
            case 1:
                switch($status){
                    case 0:
                        return '未发货';
                    case 1:
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
     *
     * 保存批量发货至记录队列表中
	 */
    function doBatchConsign(){
        $goto_url = 'index.php?app=wms&ctl=admin_consign&act=batch';
        $ids = $_POST['delivery_id'];

        $delivery_ids = explode(',', $ids);
        $delivery_ids = array_filter($delivery_ids);

        if ( !$delivery_ids ) {
            $this->splash('success',$goto_url ,'快递单列表为空！');
        }

        $batch_number = count($delivery_ids);
        $blObj  = app::get('wms')->model('batch_log');

        $bldata = array(
          'op_id' => kernel::single('desktop_user')->get_id(),
          'op_name' => kernel::single('desktop_user')->get_name(),
          'createtime' => time(),
          'batch_number' => $batch_number,
          'log_type'=>'consign',
          'log_text'=>serialize($delivery_ids)
         );
        $result = $blObj->save($bldata);

        //发货任务加队列
        $push_params = array(
            'data' => array(
                'log_text' => $bldata['log_text'],
                'log_id' => $bldata['log_id'],
                'task_type' => 'autodly'
            ),
            'url' => kernel::openapi_url('openapi.autotask','service')
        );
        kernel::single('taskmgr_interface_connecter')->push($push_params);

        $this->splash('success',$goto_url ,'发货完成');
    }

    /**
     *获取发货记录历史
    */
    function batchConsignLog(){
        $blObj  = &app::get('wms')->model('batch_log');
        $batchLogLib = kernel::single('wms_batch_log');

        $dayBegin = mktime(0,0,0,date("m"),date("d"),date("Y"));//当天开始时间戳
        $dayEnd = mktime(23,59,59,date("m"),date("d"),date("Y"));//当天结束时间戳

        $blResult = $blObj->getList('*', array('log_type'=>'consign','createtime|than'=>$dayBegin,'createtime|lthan'=>$dayEnd), 0, -1,'createtime desc');
        foreach($blResult as $k=>$v){
            $blResult[$k]['status_value'] = $batchLogLib->getStatus($v['status']);
            $blResult[$k]['fail_number'] = $v['fail_number'];
            $blResult[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }

        if($blResult){
            echo json_encode($blResult);
        }
    }

    /**
     *
     * 更新处理中发货记录值
     */
    function updateBatchConsignLog(){
        $log_id = urldecode($_POST['log_id']);
        if($log_id){
            $batchLogLib = kernel::single('wms_batch_log');
            $log_id = explode(',',$log_id);
            $status="'0','2'";

            $blResult = $batchLogLib->get_List('consign',$log_id,$status);
            foreach($blResult as $k=>$v){
                $status = $v['status'];
                $fail_number = $v['fail_number'];
                $blResult[$k]['status_value'] = $batchLogLib->getStatus($status);
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
            $delivery = &app::get('ome')->model('delivery')->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
            $dlyBillObj = &app::get('ome')->model('delivery_bill');
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

            $product_weight = app::get('ome')->model('delivery')->getWeightbydelivery_id($logi_no);

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

            $product_weight = app::get('ome')->model('delivery')->getWeightbydelivery_id($logi_no);
            $this->pagedata['product_weight'] = $product_weight;

            unset($product_weight);
            $this->pagedata['logi_no'] = $logi_no;
            $this->pagedata['weight'] = $weight;
            $this->page('admin/delivery/delivery_noweight.html');
        }else if($type=='weightwarn'){
            $this->pagedata['logi_no'] = $logi_no;
            $this->pagedata['weight'] = $weight;
            $problem_package = &app::get('wms')->getConf('wms.delivery.problem_package');
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
            $dlyObj = &app::get('ome')->model('delivery');
            $productObj= &app::get('ome')->model('products');
            $dlyBillObj = &app::get('ome')->model('delivery_bill');
            $dlyBill = $dlyBillObj->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
            $dlyfather = $dlyObj->dump(array('logi_no|nequal' => $logi_no),'delivery_id');
            if($dlyBill){
                $delivery_id = $dlyBill['delivery_id'];
            }elseif($dlyfather){
                $delivery_id = $dlyfather['delivery_id'];

            }

            $opObj = &app::get('ome')->model('operation_log');
            if($logerror=='1'){
                $logstr = '运单号:'.$logi_no.',检查后再发货(包裹内有商品未录入重量)';
            }else{
                $logstr = '运单号:'.$logi_no.',检查后再发货(称重为:'.$weight.'g)';
            }
            echo $logstr;
            $opObj->write_log('delivery_weightwarn@wms', $delivery_id, $logstr);
            return true;

        }
    }

    /**
     * 极速发货
     *
     * @param void
     * @return void
     */
    function fast_consign() {
        $this->app = app::get('ome');
        $op_id = kernel::single('desktop_user')->get_id();
        switch ($_GET['view']) {
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
                $action = array(
                    array('label' => '批量发货', 'submit' => 'index.php?app=wms&ctl=admin_consign&act=batch_sync', 'confirm' => '你确定要对勾选的订单进行发货操作吗？', 'target' => 'refresh'),
                    array('label' => '已回写成功', 'submit' => 'index.php?app=wms&ctl=admin_consign&act=batch_sync_succ', 'confirm' => "这些订单系统认为都是在前台(淘宝、京东等)已经发货，请确认这些订单前端已经发货！！！\n\n警告：本操作不会再同步发货状态，并不可恢复，请谨慎使用！！！", 'target' => 'refresh'),
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

    function getFilters() {

        $base_filter = array();
        $base_filter['status'] = array('active', 'finish');
        //$base_filter['order_confirm_filter'] = "(sdb_ome_orders.op_id is not null AND sdb_ome_orders.group_id is not null ) AND (sdb_ome_orders.is_cod='true' OR sdb_ome_orders.pay_status='1' OR sdb_ome_orders.pay_status='4' OR sdb_ome_orders.pay_status='5') and logi_no <> ''";
        $base_filter['order_confirm_filter'] = "(sdb_ome_orders.op_id is not null OR sdb_ome_orders.group_id is not null ) AND (sdb_ome_orders.is_cod='true' OR sdb_ome_orders.pay_status='1' OR sdb_ome_orders.pay_status='4' OR sdb_ome_orders.pay_status='5') and logi_no <> ''";
        $base_filter['process_status'] = array('splited', 'confirmed', 'splitting');

        return $base_filter;
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
     * 批量消除冲突
     *
     * @param void
     * @return void
     */
    function batch_sync_succ() {
        $this->begin('');
        $ids = $_REQUEST['order_id'];

        if (!empty($ids)) {
            $orderObj = &app::get('ome')->model('orders');
            $data = array('sync'=>'succ','sync_fail_type'=>'none');
            $filter = array('order_id'=>$ids,'createway' => 'matrix');
            $orderObj->update($data,$filter);

            //记录日志
            $logObj = &app::get('ome')->model('operation_log');
            $logObj->batch_write_log('order_modify@ome','手动设为同步成功',time(),$filter);
        }
        $this->end(true, '命令已经被成功发送！');
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
            $deliveryObj = app::get('ome')->model('delivery');
            $deliveryIds = $deliveryObj->getDeliverIdByOrderId($order_id);
            //$deliveryIds = app::get('ome')->model('delivery')->getDeliverIdByOrderId($order_id);
            if (!empty($deliveryIds)) {
                $delivery_id = $deliveryIds[0];
            }
            if (!empty($order['order_id']) && !empty($delivery_id)) {
                if ($order['sync'] <> 'succ' ) {

                    if ($this->syncChangeStatus($delivery_id)) {
                        // 电子面单
                        if ($order['logi_id'] > 0) {
                            $dlyCorp = $dlyCorpObj->dump($order['logi_id'],'channel_id,tmpl_type,shop_id');
                            // $channel = $channelObj->dump($dlyCorp['channel_id']);
                            // $channel_type = $channel['channel_type'];
                            if (app::get('logisticsmanager')->is_installed() && $dlyCorp['tmpl_type'] == 'electron') {
                                kernel::single('logisticsmanager_waybill_router')->setChannelId($dlyCorp['channel_id'])
                                                                                 ->setShopId($order['shop_id'])
                                                                                 ->delivery($delivery_id);
                            }
                        }

                        if ($order['shop_type'] == 'vjia' && $order['createway'] == 'matrix') {
                            kernel::single('ome_delivery_vjia')->logistics_modify($order_id);
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

    function getProcutInfo(){
        $logi_no = $_POST['logi_no'];
        $deliveryBillLib = kernel::single('wms_delivery_bill');
        $delivery_id = $deliveryBillLib->getDeliveryIdByPrimaryLogi($logi_no);
        if($delivery_id){
            $procut_info = &app::get('wms')->model('delivery')->getProcutInfo($delivery_id);
            $str = '';
            foreach($procut_info as $v){
                if(isset($v['bn']) && isset($v['product_name']) && isset($v['number'])){
                    $str .= "<tr><td width='40%' align='center'>".$v['bn'].'</td>'."<td width='50%' align='center'>".$v['product_name']."</td>"."<td  width='10%' align='center'>".$v['number']."</td></tr>";
                }
            }
        }
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

    //校验直接发货检查方法
    function doCheck(&$rs = false){
        $autohide = array('autohide'=>2000);
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
                    $this->end(false, $message, '', $autohide);
                }
            }
        }

        $dly_id   = $_POST['delivery_id'];
        $count    = $_POST['count'];
        $number   = $_POST['number'];
        $logi_no  = $_POST['logi_no'];

        //检查订单的当前状态
        $dlyCheckLib = kernel::single('wms_delivery_check');
        if(!$dlyCheckLib->checkOrderStatus($dly_id, true, $errmsg)){
            $tmp = array('result'=>false,'msg'=>$errmsg);
            echo json_encode($tmp);die;
        }

        if ($count == 0 || $number == 0){
            $tmp = array('result'=>false,'msg'=>'对不起，校验提交的数据错误');
            echo json_encode($tmp);die;
        }


        $deliveryObj  = &app::get('wms')->model('delivery');
        $dly          = $deliveryObj->dump($dly_id,'*', array('delivery_items'=>array('*')));

        //检查运单号是否属于同一个处理的发货单
        $deliveryBillLib = kernel::single('wms_delivery_bill');
        $db_logi_no = $deliveryBillLib->getPrimaryLogiNoById($dly_id);
        if ($db_logi_no != $logi_no){
            $tmp = array('result'=>false,'msg'=>'扫描的快递单号与系统中的快递单号不对应');
            echo json_encode($tmp);die;
        }

        //合计发货单明细的总数
        $total = 0;
        foreach ($dly['delivery_items'] as $i){
            $total += $i['number'];
        }

        $opObj        = &app::get('ome')->model('operation_log');
        $deliveryLib = kernel::single('wms_delivery_process');

        if ($count === $number) {

            //对发货单详情进行校验完成处理
            if ($deliveryLib->verifyDelivery($dly_id)){
                if(is_array($_POST['serial_data']) && count($_POST['serial_data'])>0){
                    $productObj = &app::get('ome')->model('products');
                    $productSerialObj = &app::get('ome')->model('product_serial');
                    $serialLogObj = &app::get('ome')->model('product_serial_log');
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

                $rs = true;
                return true;
            }else {
                $tmp = array('result'=>false,'msg'=>'发货单校验未完成，请重新校验');
                echo json_encode($tmp);die;
            }
        } else {
            //保存部分校验结果
            /*
            $flag = $deliveryLib->verifyItemsByDeliveryIdFromPost($dly_id);
            if ($flag){
                $opObj->write_log('delivery_check@wms', $dly_id, '发货单部分检验数据保存完成');
                $this->begin('index.php?app=wms&ctl=admin_check&act=index&type='.$checkType);
                $this->end(true, '发货单部分检验数据保存完成', '', $autohide);
            }else {
                $this->begin('index.php?app=wms&ctl=admin_check&act=index&type='.$checkType);
                $this->end(false, '发货单校验未完成，请重新校验', '', $autohide);
            }
            */
            $tmp = array('result'=>false,'msg'=>'发货单校验未完成，请重新校验');
            echo json_encode($tmp);die;
        }
    }

    #校验完成即发货：开始发货
    function doCheckDelivery(){
        $goto_url = 'index.php?app=wms&ctl=admin_check&act=index&type=all';
        $ids = $_POST['delivery_id'];
    
        $delivery_ids = explode(',', $ids);
        $delivery_ids = array_filter($delivery_ids);
         
        if ( !$delivery_ids ) {
            $this->splash('success',$goto_url ,'快递单列表为空！');
        }
        $delivery_id = $_POST['delivery_id'];
        $msg = '校验完成即发货：开始发货';
        $ObjOp        = &app::get('ome')->model('operation_log');
        $ObjOp->write_log('delivery_checkdelivery@wms',$delivery_id,$msg,time());
        #走原来发货流程
        return $this->consign();
    }
}

?>
