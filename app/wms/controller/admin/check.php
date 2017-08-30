<?php
class wms_ctl_admin_check extends desktop_controller{
    var $name = "货物校验";
    var $workground = "wms_delivery";

    /**
     *
     * 逐单校验/整单校验的入口展示页
     */
    function index(){
        $deliveryObj  = &app::get('wms')->model('delivery');
        
        $numShow = app::get('wms')->getConf('wms.delivery.checknum.show');
        if($numShow == 'false' || (cachecore::fetch('quick_access') !== false)){
            $this->pagedata['num'] = '未知';
        }else{
            $this->pagedata['num'] = $deliveryObj->countNoVerifyDelivery();
        }

        $this->pagedata['checkType'] = $_GET['type'];
        $this->page("admin/delivery/process_check_index.html");
    }

    /**
     *
     * 批量校验的入口展示页
     */
    function batchIndex(){
        $stock_confirm= &app::get('ome')->getConf('purchase.stock_confirm');
        $stock_cancel= &app::get('ome')->getConf('purchase.stock_cancel');
        $this->pagedata['stock_confirm'] = $stock_confirm;
        $this->pagedata['stock_cancel'] = $stock_cancel;
        $deliveryObj  = &app::get('wms')->model('delivery');
        $this->pagedata['num'] = $deliveryObj->countNoVerifyDelivery();
        $this->page("admin/delivery/process_batch_check_index.html");
    }

    /**
     *
     * 分组校验的入口展示页
     */
    function group_check(){
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $verfy = $deliCfgLib->getValue('verify');
        if($verfy == 1){
            $deliveryObj = &app::get('wms')->model('delivery');
            $orderTypeObj = &app::get('omeauto')->model('order_type');
            $groupFilter['tid'] = $deliCfgLib->getValue('wms_delivery_verify_group');
            $groupFilter['disabled'] = 'false';
            $groupFilter['delivery_group'] = 'true';
            $orderTypes = $orderTypeObj->getList('*',$groupFilter);

            $filter = array(
            	'status'=> 0,
                'process_status'=> 1,
                'disabled'=>'false',
                //'type'=>'normal',
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

            /*
            $deliveryGroup[8388607] = array(
                'tid' => 8388607,
                'name' => '全部分组',
                'deliverys' => $deliveryAll,
                'dCount' => count($deliveryAll),
            );
            */

            $this->pagedata['num_available'] = count($deliveryAll);
            $this->pagedata['deliveryGroup'] = $deliveryGroup;
            $this->pagedata['jsonDeliveryGroup'] = json_encode($deliveryGroup);

            /* 操作时间间隔 start */
            $lastGroupCalibration = app::get('wms')->getConf('lastGroupCalibration'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
            $groupCalibrationIntervalTime = app::get('wms')->getConf('wms.groupCalibration.intervalTime'); //每次操作的时间间隔

            if(($lastGroupCalibration['execTime']+60*$groupCalibrationIntervalTime)<time()){
                $this->pagedata['is_allow'] = true;
            }else{
                $this->pagedata['is_allow'] = false;
            }
            $this->pagedata['lastGroupCalibrationTime'] = !empty($lastGroupCalibration['execTime']) ? date('Y-m-d H:i:s',$lastGroupCalibration['execTime']) : '';
            $this->pagedata['groupCalibrationIntervalTime'] = $groupCalibrationIntervalTime;
            $this->pagedata['currentTime'] = time();
            /* 操作时间间隔 end */

            $this->page("admin/delivery/process_group_check.html");
        }else{
            echo "未开启分组效验！";
        }
    }

    /**
     * @description 校验成功
     * @access public
     * @param void
     * @return void
     */
    public function check_pass()
    {
        $pass = $_POST['pass'];
        if ($pass == 'false') {
            echo 'check fail!!!'; exit;
        }

        $checkType = $_POST['checkType'];
        $logi_no = $_POST['logi_no'];

        $deliveryBillLib = kernel::single('wms_delivery_bill');
        $delivery_id = $deliveryBillLib->getDeliveryIdByPrimaryLogi($logi_no);

        $deliveryObj = app::get('wms')->model('delivery');

        //参数赋值
        $dly = array(
        	'logi_no' => $logi_no,
        	'delivery_id' => $delivery_id,
        );

        //捡货绩效开始记录点
        foreach(kernel::servicelist('tgkpi.pick') as $o){
            if(method_exists($o,'begin_check')){
                $o->begin_check($delivery_id);
                $this->pagedata['tgkpi_status'] = 'true';
            }
        }

        //获取相关订单的备注信息，走接口
        $deliveryInfo = $deliveryObj->dump($delivery_id, 'outer_delivery_bn,ship_name');
        $res = kernel::single('ome_extint_order')->getMemoByDlyId($deliveryInfo['outer_delivery_bn']);

        $this->pagedata['ship_name'] = $deliveryInfo['consignee']['name'];

        $order_bns = array();
        foreach ($res['mark_text'] as $k =>$v){
            if(!in_array($k, $order_bns)){
                $order_bns[] = $k;
            }
        }

        foreach ($res['custom_mark'] as $k =>$v){
            if(!in_array($k, $order_bns)){
                $order_bns[] = $k;
            }
        }

        foreach ($order_bns as $k => $order_bn){
            $markandtext[$k]['order_bn'] = $order_bn;
            $markandtext[$k]['_mark'] = isset($res['custom_mark'][$order_bn]) ? $res['custom_mark'][$order_bn] : '';
            $markandtext[$k]['_mark_text'] = isset($res['mark_text'][$order_bn]) ? $res['mark_text'][$order_bn] : '';
        }

        $this->pagedata['markandtext']  = $markandtext;

        # 货品名显示方式(stock:后台,front:前台)
        $product_name_show_type = app::get('wms')->getConf('wms.delivery.check_show_type');
        $product_name_show_type = empty($product_name_show_type) ? 'stock' : $product_name_show_type;

        $goods = 0; $newItems = array();
        $pObj = app::get('ome')->model('products');
        $gObj = app::get('ome')->model('goods');
        $items = $deliveryObj->getItemsByDeliveryId($delivery_id);
        foreach ($items as $k => $i){
            $p = $pObj->getList('barcode,spec_info,goods_id',array('product_id'=>$i['product_id']),0,1);
            $p = $p[0];
            $g = $gObj->getList('serial_number',array('goods_id'=>$p['goods_id']));
            $g = $g[0];

            $count += $i['number'];
            $goods ++;
            $verify_num += $i['verify_num'];
            $items[$k]['barcode'] = trim($p['barcode']);
            $items[$k]['spec_info'] = trim($p['spec_info']);
            $items[$k]['bn'] = trim($items[$k]['bn']);
            $items[$k]['serial_number'] = $g['serial_number'];
            if ($i['verify_num'] == $i['number']) {
                $items[$k]['nameColor'] =  '#eeeeee';
            } elseif ($i['verify_num'] > 0) {
                $items[$k]['nameColor'] ='red';
            } else {
                $items[$k]['nameColor'] = 'black';
            }
            $verify += $i['verify_num'];

            if($newItems[$i['bn']] && $newItems[$i['bn']]['bn'] !=''){
                $newItems[$i['bn']]['number'] += $items[$k]['number'];
                $newItems[$i['bn']]['verify_num'] += $items[$k]['verify_num'];
            }else{
                $newItems[$i['bn']] = $items[$k];
            }
        }
        $items = $newItems;

        //增加发货单校验显示前的扩展
        foreach(kernel::servicelist('ome.delivery') as $o){
            if(method_exists($o,'pre_check_display')){
                $o->pre_check_display($items);
            }
        }

        //增加日志
        $opObj = &app::get('ome')->model('operation_log');
        $msg= "发货单开始校验";
        $opObj->write_log('delivery_check@wms', $delivery_id, $msg);

        if($product_name_show_type == 'stock') {
            $this->toGoodsName($items);
        }
        $serial['merge'] = app::get('ome')->getConf('ome.product.serial.merge');
        $serial['separate'] = app::get('ome')->getConf('ome.product.serial.separate');
        $this->pagedata['serial'] = $serial;

        $conf = &app::get('wms')->getConf('wms.delivery.check');
        $this->pagedata['normal'] = 0;
        $this->pagedata['conf'] = $conf;
        $this->pagedata['count'] = $count;
        $this->pagedata['number'] = $verify;
        $this->pagedata['goodsNum'] = $goods;
        $this->pagedata['items'] = $items;
        $this->pagedata['dly'] = $dly;
        $this->pagedata['verify_num'] = $verify_num;
        $this->pagedata['remain'] = $count - $verify_num;
        $this->pagedata['userName'] = kernel::single('desktop_user')->get_name();
        $this->pagedata['date'] = date('Y-m-d H:i');
        $this->pagedata['checkType'] = $checkType;
        //获取
        if (app::get('tgkpi')->is_installed()) {
            $pickInfo = kernel::database()->selectrow("SELECT pick_owner FROM sdb_tgkpi_pick WHERE delivery_id={$delivery_id} LIMIT 1");
            if (!empty($pickInfo['pick_owner'])){
                $pickUser = app::get('desktop')->model('users')->dump(array('op_no'=>$pickInfo['pick_owner']), 'name');
                if ($pickUser) {
                    $this->pagedata['picktName']= $pickUser['name'];
                }
            }
        }

        if ($checkType == 'all') {
            $view = 'admin/delivery/delivery_checkout2.html';
            $delivery_weight =  &app::get('wms')->getConf('wms.delivery.weight'); #发货配置，开启称重
            $check_delivery = &app::get('wms')->getConf('wms.delivery.check_delivery'); #发货配置，检验完即发货

            #开启称重时，不能使用校验完即发货功能
            if($delivery_weight == 'on'){
                $check_delivery = 'off';
            }
            if(!isset($check_delivery)||empty($check_delivery)){
                $check_delivery = 'off';
            }
            $this->pagedata['check_delivery'] = $check_delivery;
            #逐单发货，如果不称重，且,开启了校验后直接发货
            if($delivery_weight == 'off' && $check_delivery == 'on'){
                $minWeight = app::get('wms')->getConf('wms.delivery.minWeight');
                $this->pagedata['weight'] = $minWeight;
                $this->pagedata['check_delivery'] = $check_delivery;
                #校验后,直接发货的view页面
                $view = 'admin/delivery/delivery_checkout3.html';
            }
        } else {
            $view = 'admin/delivery/delivery_checkout.html';
        }

        $this->display($view);
    }

    /**
     * 校验发货单是否可打印
     *
     */
    function check(){
        if ($_POST['pass'] == 'true') {
            $this->check_pass();exit;
        }

        $autohide = array('autohide'=>3000);
        $this->begin('index.php?app=wms&ctl=admin_check');
        $checkType = $_POST['checkType'];
        $logi_no = $_POST['delivery']['logi_no'];

        # barcode:逐单 all:整单
        if (!in_array($checkType,array('barcode','all'))) {
            $this->end(false, '参数传递错误', '', $autohide);
        }

        $dlyCheckLib = kernel::single('wms_delivery_check');

        $check_result = $dlyCheckLib->checkAllow($logi_no, $msg);
        if (!$check_result){
            $this->end(false, $msg, '', $autohide);
        }else{
            $this->end(true,'快递单合法，开始校验。','',array('pass'=>'true','checkType'=>$checkType,'logi_no'=>$logi_no));
        }
    }

    /**
     *
     * 批量校验的校验验证检查方法
     */
    function batchCheck(){
        $ids = urldecode($_POST['delivery_id']);

        if (empty($ids)){
            $tmp = array(array('bn'=>'*','msg'=>'请扫描快递单号'));
            echo json_encode($tmp);die;
        }

        $deliveryObj  = &app::get('wms')->model('delivery');
        $dlyCheckLib = kernel::single('wms_delivery_check');
        $delivery_ids = array_unique(explode(',', $ids));
        $tmp = array();
        foreach($delivery_ids as $logi_no){
            if(!$logi_no)continue;

            $delivery = $dlyCheckLib->checkAllow($logi_no, $msg);
            if (!$delivery) {
                $tmp[] = array('bn'=>$logi_no,'msg'=>$msg);
                continue;
            }
        }

        if ($tmp){ echo json_encode($tmp);die;}

        echo "";
    }

    private function toGoodsName(& $items) {
        $productObj = &app::get('ome')->model('products');

        $bn_string = '';
        foreach($items as $k=>$v){
            $sql = 'SELECT name FROM sdb_ome_products WHERE bn="'.$items[$k]['bn'].'" LIMIT 1';
            $product = $productObj->db->select($sql);
            if($product) {
                $items[$k]['product_name'] = $product[0]['name'];
            }
        }
    }

    /**
     *
     * 逐单校验/整单校验提交处理方法
     */
    function doCheck(){
        $autohide = array('autohide'=>2000);
        $checkType = in_array($_POST['checkType'],array('barcode','all')) ? $_POST['checkType'] : 'barcode';
        $this->begin('index.php?app=wms&ctl=admin_check&act=index&type='.$checkType);
        if (empty($_POST['delivery_id'])){
            $this->end(false, '发货单ID传入错误', '', $autohide);
        }
        if ($_POST['logi_no'] == ''){
            $this->end(false, '请扫描快递单号', '', $autohide);
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
            $this->end(false, $errmsg, '', $autohide);
        }

        if ($count == 0 || $number == 0){
            $this->end(false, '对不起，校验提交的数据错误', '', $autohide);
        }


        $deliveryObj  = &app::get('wms')->model('delivery');
        $dly          = $deliveryObj->dump($dly_id,'*', array('delivery_items'=>array('*')));

        //检查运单号是否属于同一个处理的发货单
        $deliveryBillLib = kernel::single('wms_delivery_bill');
        $db_logi_no = $deliveryBillLib->getPrimaryLogiNoById($dly_id);
        if ($db_logi_no != $logi_no){
            $this->end(false, '扫描的快递单号与系统中的快递单号不对应', '', $autohide);
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

                $this->end(true, '发货单校验完成');
            }else {
                $this->end(false, '发货单校验未完成，请重新校验', 'index.php?app=wms&ctl=admin_check', $autohide);
            }
        } else {
            //保存部分校验结果
            $flag = $deliveryLib->verifyItemsByDeliveryIdFromPost($dly_id);
            if ($flag){
                $opObj->write_log('delivery_check@wms', $dly_id, '发货单部分检验数据保存完成');
                $this->end(true, '发货单部分检验数据保存完成', '', $autohide);
            }else {
                $this->end(false, '发货单校验未完成，请重新校验', '', $autohide);
            }
        }
    }

    function ajaxDoGroupCheck(){
        $tmp = explode('||', $_POST['ajaxParams']);
        $group = $tmp[0];
        $delivery = explode(';', $tmp[1]);

        if($delivery && count($delivery)>0){
            /* 执行时间判断 start */
            $pageBn = intval($_POST['pageBn']);
            $lastGroupCalibration = app::get('wms')->getConf('lastGroupCalibration'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
            $groupCalibrationIntervalTime = app::get('wms')->getConf('wms.groupCalibration.intervalTime'); //每次操作的时间间隔

            if($pageBn !=$lastGroupCalibration['pageBn'] && ($lastGroupCalibration['execTime']+60*$groupCalibrationIntervalTime)>time()){
                echo json_encode(array('total' => count($delivery), 'succ' => 0, 'fail' => count($delivery), 'failInfo'=>array('执行时间不合法')));
                exit;
            }
            if($pageBn !=$lastGroupCalibration['pageBn'] && $pageBn<$lastGroupCalibration['execTime']){
                echo json_encode(array('total' => count($delivery), 'succ' => 0, 'fail' => count($delivery), 'failInfo'=>array('提交参数过期')));
                exit;
            }

            //记录本次获取订单时间
            $currentGroupCalibration = array(
                'execTime'=>time(),
                'pageBn'=>$pageBn,
            );
            app::get('ome')->setconf('lastGroupCalibration',$currentGroupCalibration);
            /* 执行时间判断 end */

            $deliveryObj = &app::get('wms')->model('delivery');
            $deliveryLib = kernel::single('wms_delivery_process');
            $dlyCheckLib = kernel::single('wms_delivery_check');

            $filter = array(
                'delivery_id'=>$delivery,
            );
            $deliverys = $deliveryObj->getList('delivery_id,delivery_bn',$filter);
            $succ = 0;
            $fail = 0;
            $failInfo = array();
            foreach($deliverys as $value){
                $checkInfo = $dlyCheckLib->checkOrderStatus($value['delivery_id'], true);
                if ($checkInfo){
                    if($deliveryLib->verifyDelivery($value['delivery_id'],true)){
                        $succ++;
                    }else{
                        $fail++;
                        $failInfo[] = $value['delivery_bn'];
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

	/**
	 *
     * 获取发货记录历史
     */
    function batchConsignLog(){
        $blObj  = &app::get('wms')->model('batch_log');
        $batchLogLib = kernel::single('wms_batch_log');

        $dayBegin = mktime(0,0,0,date("m"),date("d"),date("Y"));//当天开始时间戳
        $dayEnd = mktime(23,59,59,date("m"),date("d"),date("Y"));//当天结束时间戳
        $blResult = $blObj->getList('*', array('log_type'=>'check','createtime|than'=>$dayBegin,'createtime|lthan'=>$dayEnd), 0, -1,'createtime desc');

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
    function updateBatchCheckLog(){
        $log_id = $_POST['log_id'];
        if($log_id){
            $status="'0','2'";
            $batchLogLib = kernel::single('wms_batch_log');
            $blResult = $batchLogLib->get_List('check',$log_id,$status);
            foreach($blResult as $k=>$v){
                $blResult[$k]['status_value'] = $batchLogLib->getStatus($v['status']);
                $blResult[$k]['fail_number'] = $v['fail_number'];
                $blResult[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            }

            echo json_encode($blResult);
        }

    }

    /**
     *
     * 保存批量发货至记录队列表中
	 */
    function saveBatchCheck(){
        $goto_url = 'index.php?app=wms&ctl=admin_check&act=batchIndex';
        $ids = $_POST['delivery_id'];
        $delivery_ids = explode(',', $ids);
        $delivery_ids = array_filter($delivery_ids);

        if ( !$delivery_ids ) {
            $this->splash('success',$goto_url ,'快递单列表为空！');
        }

        $batch_number = count($delivery_ids);
        $blObj  = &app::get('wms')->model('batch_log');

        $bldata = array(
          'op_id' => kernel::single('desktop_user')->get_id(),
          'op_name' => kernel::single('desktop_user')->get_name(),
          'createtime' => time(),
          'batch_number' => $batch_number,
          'log_type'=>'check',
          'log_text'=>serialize($delivery_ids)
         );
        $result = $blObj->save($bldata);

        //校验任务加队列
        $push_params = array(
            'data' => array(
                'log_text' => $bldata['log_text'],
                'log_id' => $bldata['log_id'],
                'task_type' => 'autochk'
            ),
            'url' => kernel::openapi_url('openapi.autotask','service')
        );
        kernel::single('taskmgr_interface_connecter')->push($push_params);

        $this->splash('success',$goto_url ,'已提交至队列');
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
        $this->display('admin/delivery/batch_chklog_detail.html');
    }
}
