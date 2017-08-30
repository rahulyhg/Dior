<?php
class ome_ctl_admin_check extends desktop_controller{
    var $name = "货物校验";
    var $workground = "delivery_center";

    function index(){
        $deliveryObj  = &$this->app->model('delivery');

        $numShow = app::get('ome')->getConf('ome.delivery.checknum.show');
        if($numShow == 'false'){
            $this->pagedata['num'] = '未知';
        }else{
            $this->pagedata['num'] = $deliveryObj->countNoVerifyDelivery();
        }
        $this->pagedata['checkType'] = $_GET['type'];
        $this->page("admin/delivery/process_check_index.html");
    }

     function batchIndex(){
        $stock_confirm= &app::get('ome')->getConf('purchase.stock_confirm');
        $stock_cancel= &app::get('ome')->getConf('purchase.stock_cancel');
        $this->pagedata['stock_confirm'] = $stock_confirm;
        $this->pagedata['stock_cancel'] = $stock_cancel;
        $deliveryObj  = &$this->app->model('delivery');
        $this->pagedata['num'] = $deliveryObj->countNoVerifyDelivery();
        $blObj  = &$this->app->model('batch_log');
        $dayBegin = mktime(0,0,0,date("m"),date("d"),date("Y"));//当天开始时间戳
        $dayEnd = mktime(23,59,59,date("m"),date("d"),date("Y"));//当天结束时间戳
        $blResult = $blObj->getList('*', array('log_type'=>'check','createtime|than'=>$dayBegin,'createtime|lthan'=>$dayEnd), 0, -1,'createtime desc');

        foreach($blResult as $k=>$v){
            $blResult[$k]['status_value'] = kernel::single('ome_batch_log')->getStatus($v['status']);

            $blResult[$k]['fail_number'] = $v['fail_number'];
            $blResult[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }
        $this->pagedata['blResult'] = $blResult;
        $this->pagedata['app_dir'] = kernel::base_url()."/app/".$this->app->app_id;
        $this->page("admin/delivery/process_batch_check_index.html");
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

        $deliveryObj  = $this->app->model('delivery');
        $dly          = $deliveryObj->dump(array('logi_no' => $logi_no),'*',array('delivery_order' => array('*')));

        //增加捡货绩效
        foreach(kernel::servicelist('tgkpi.pick') as $o){
            if(method_exists($o,'begin_check')){
                $o->begin_check($dly['delivery_id']);
                $this->pagedata['tgkpi_status'] = 'true';
            }
        }

        // 备注显示方式
        $markShowMethod = &app::get('ome')->getConf('ome.order.mark');
        $oObj  = $this->app->model('orders');
        if ($dly['delivery_order']){
            foreach ($dly['delivery_order'] as $k => $v) {
                # order info
                $order_detail = $oObj->dump(array('order_id'=>$v['order_id']),'order_bn,pay_status,ship_name,custom_mark,mark_text');

                $this->pagedata['ship_name'] = $order_detail['consignee']['name'];

                $markandtext[$k]['order_bn'] = $order_detail['order_bn'];
                if ($order_detail['custom_mark']) {
                    $mark = unserialize($order_detail['custom_mark']);
                    if (is_array($mark) || !empty($mark)){
                        if($markShowMethod == 'all'){
                            foreach ($mark as $im) {
                                $markandtext[$k]['_mark'][] = $im;
                            }
                        }else{
                            $markandtext[$k]['_mark'][] = array_pop($mark);
                        }
                    }
                }

                if ($order_detail['mark_text']) {
                    $mark = unserialize($order_detail['mark_text']);
                    if (is_array($mark) || !empty($mark)){
                        if($markShowMethod == 'all'){
                            foreach ($mark as $im) {
                                $markandtext[$k]['_mark_text'][] = $im;
                            }
                        }else{
                            $markandtext[$k]['_mark_text'][] = array_pop($mark);
                        }
                    }
                }
            }
        }
        $this->pagedata['markandtext']  = $markandtext;

        # 货品名显示方式(stock:后台,front:前台)
        $product_name_show_type = $this->app->getConf('ome.delivery.check_show_type');
        $product_name_show_type = empty($product_name_show_type) ? 'stock' : $product_name_show_type;

        $goods = 0; $newItems = array();
        $pObj = $this->app->model('products');
        $gObj = $this->app->model('goods');
        $items = $deliveryObj->getItemsByDeliveryId($dly['delivery_id']);
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
        $opObj = &$this->app->model('operation_log');
        $msg= "发货单开始校验";
        $opObj->write_log('delivery_check@ome', $dly['delivery_id'], $msg);

        if($product_name_show_type == 'stock') {
            $this->toGoodsName($items);
        }
        $serial['merge'] = $this->app->getConf('ome.product.serial.merge');
        $serial['separate'] = $this->app->getConf('ome.product.serial.separate');
        $this->pagedata['serial'] = $serial;

        $conf = &app::get('ome')->getConf('ome.delivery.check');
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
            $pickInfo = kernel::database()->selectrow("SELECT pick_owner FROM sdb_tgkpi_pick WHERE delivery_id={$dly['delivery_id']}");
            if (!empty($pickInfo['pick_owner'])){
                $pickUser = app::get('desktop')->model('users')->dump(array('op_no'=>$pickInfo['pick_owner']), 'name');
                if ($pickUser) {
                    $this->pagedata['picktName']= $pickUser['name'];
                }
            }
        }

        //$checkType = $this->app->getConf('ome.delivery.check_type');
        if ($checkType == 'all') {
            $view = 'admin/delivery/delivery_checkout2.html';
            $delivery_weight =  &app::get('ome')->getConf('ome.delivery.weight'); #发货配置，开启称重
            $check_delivery = &app::get('ome')->getConf('ome.delivery.check_delivery'); #发货配置，检验完即发货

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
                $minWeight = $this->app->getConf('ome.delivery.minWeight');
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
        $this->begin('index.php?app=ome&ctl=admin_check');
        $checkType = $_POST['checkType'];
        $logi_no = $_POST['delivery']['logi_no'];

        # barcode:逐单 all:整单
        if (!in_array($checkType,array('barcode','all'))) {
            $this->end(false, '参数传递错误', '', $autohide);
        }

        if (!$logi_no){
            $this->end(false, '请输入快递单号', '', $autohide);
        }

        $deliveryObj  = $this->app->model('delivery');
        $dly          = $deliveryObj->dump(array('logi_no' => $logi_no),'*',array('delivery_order' => array('*')));
        if (!$dly){
            $this->end(false, '无此快递单号', '', $autohide);
        }

        //判断发货单相应订单是否有问题
        if (!$this->checkOrderStatus($dly['delivery_id'], true, $msg)){
            $this->end(false, $msg, '', $autohide);
        }
        if ($dly['verify'] == 'true'){
            $this->end(false, '发货单已校验完成', '', $autohide);
        }
        if ($dly['status'] != 'progress'){
            $this->end(false, '此发货单不满足校验需求', '', $autohide);
        }
        if ($dly['pause'] == 'true'){
            $this->end(false, '此发货单已暂停', '', $autohide);
        }

        //增加配置打印配置后的判定修改
        $printFinish = $deliveryObj->checkPrintFinish($dly,$errorMsg);
        if($printFinish == false){
            $this->end(false,$errorMsg[0]['msg'],$autohide);
        }

         # 获取操作员管辖仓库
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $oBranch = $this->app->model('branch');
            $branch_ids = $oBranch->getBranchByUser(true);
            if (!is_array($branch_ids) || !in_array($dly['branch_id'],$branch_ids)){
                $this->end(false, $delivery['delivery_bn'].':发货单号不在您管辖的仓库范围内！', '', $autohide);
            }
        }

        //备注显示方式
        $markShowMethod = &app::get('ome')->getConf('ome.order.mark');
        $oObj  = $this->app->model('orders');
        if ($dly['delivery_order']){
            foreach ($dly['delivery_order'] as $k => $v) {
                # order info
                $order_detail = $oObj->dump(array('order_id'=>$v['order_id']),'order_bn,pay_status,ship_name,custom_mark,mark_text');

                //增加部分退款，全额退款无法校验的约束
				/*
                if($order_detail['pay_status'] == 4){
                        $this->end(false, '订单已部分退款，无法校验。', '', $autohide);
                }
				*/
                if($order_detail['pay_status'] == 5){
                    $this->end(false, '订单已全额退款，无法校验。', '', $autohide);
                }
            }
        }

        $this->end(true,'快递单合法，开始校验。','',array('pass'=>'true','checkType'=>$checkType,'logi_no'=>$logi_no));
    }

     function batchCheck(){
        $ids = urldecode($_POST['delivery_id']);

        if (empty($ids)){
            $tmp = array(array('bn'=>'*','msg'=>'请扫描快递单号'));
            echo json_encode($tmp);die;
        }

        $deliveryObj  = &$this->app->model('delivery');
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if ($is_super) {
            $branch_ids = array('_ALL_');
        } else {
            $branch_ids = $oBranch->getBranchByUser(true);
        }

        $delivery_ids = array_unique(explode(',', $ids));
        $tmp = array();
        foreach($delivery_ids as $logi_no){
            if(!$logi_no)continue;

            $delivery = kernel::single('ome_delivery_check')->checkAllow($logi_no,$branch_ids,$msg);
            if (!$delivery) {
                $tmp[] = array('bn'=>$logi_no,'msg'=>$msg);
                continue;
            }
        }

        if ($tmp){ echo json_encode($tmp);die;}

        echo "";
    }

    private function toGoodsName(& $items) {
        $productObj = &$this->app->model('products');

        $bn_string = '';
        foreach($items as $k=>$v){
            //$goods = $goodsObj->dump(array('bn'=>$items[$i]['bn']));
            //$items[$i]['product_name'] = $goods['name'];
            $sql = 'SELECT name FROM sdb_ome_products WHERE bn="'.$items[$k]['bn'].'" LIMIT 1';
            $product = $productObj->db->select($sql);
            if($product) {
                $items[$k]['product_name'] = $product[0]['name'];
            }
        }
    }

    /**
     * 发货单内容校验
     *
     */
    function doCheck(){
        $autohide = array('autohide'=>2000);
        $checkType = in_array($_POST['checkType'],array('barcode','all')) ? $_POST['checkType'] : 'barcode';
        $this->begin('index.php?app=ome&ctl=admin_check&act=index&type='.$checkType);
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
        $this->checkOrderStatus($dly_id);//判断发货单相应订单是否有问题
        if ($count == 0 || $number == 0){
            $this->end(false, '对不起，校验提交的数据错误', '', $autohide);
        }
        $deliveryObj  = &$this->app->model('delivery');
        $dly          = $deliveryObj->dump($dly_id,'*', array('delivery_items'=>array('*')));
        $verify = $dly['verify'];
        if ($dly['logi_no'] != $logi_no){
            $this->end(false, '扫描的快递单号与系统中的快递单号不对应', '', $autohide);
        }
        $total = 0;
        foreach ($dly['delivery_items'] as $i){
            $total += $i['number'];
        }
        /*if ($number != $total){
            $this->end(false, '对不起，校验提交的数据与发货单数据不对应', '', $autohide);
        }*/
        $opObj        = &$this->app->model('operation_log');
        $dly_itemObj  = &app::get('ome')->model('delivery_items');

        if ($count === $number) {

            //对发货单详情进行校验完成处理
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

                $this->end(true, '发货单校验完成');
            }else {
                $this->end(false, '发货单校验未完成，请重新校验', 'index.php?app=ome&ctl=admin_check', $autohide);
            }
        } else {
            //保存部分校验结果
            $flag = $dly_itemObj->verifyItemsByDeliveryIdFromPost($dly_id);
            if ($flag){
                $opObj->write_log('delivery_check@ome', $dly_id, '发货单部分检验数据保存完成');
                $this->end(true, '发货单部分检验数据保存完成', '', $autohide);
            }else {
                $this->end(false, '发货单校验未完成，请重新校验', '', $autohide);
            }
        }
    }

     function doBatchCheck(){
          $ids = urldecode($_POST['delivery_id']);
          echo $ids;
          $goto_url = 'index.php?app=ome&ctl=admin_check&act=batchIndex';

        if (empty($ids)){
            $info = '请扫描快递单号';
            $this->splash('error', $goto_url, $info,'', array('msg'=>$info));
            exit;
        }

        $delivery_ids = explode(',', $ids);
        $delivery_result = true;
        $delivery_fail_bns = array();
        $delivery_succ = 'fail';
        $deliveryObj  = &$this->app->model('delivery');

        foreach($delivery_ids as $logi_no){
            if(!$logi_no)continue;
             $dly = $deliveryObj->dump(array('logi_no'=>$logi_no));

             kernel::database()->exec('begin');
            if ($dly && $dly['process']=='false'){
                if ( !$deliveryObj->verifyDelivery($dly) ){
                    $delivery_result = false;
                    $delivery_fail_bns[] = '物流单号:'.$dly['logi_no'].'-发货单号:'.$dly['delivery_bn'];
                    kernel::database()->exec('rollback');
                }else{
                    kernel::database()->exec('commit');
                    $delivery_succ = 'succ';
                }
            }else{
                $delivery_result = false;
            }
        }

          if ($delivery_result){
            $this->splash('success',$goto_url ,'发货完成');
            exit;
        }else{
            $msg = array();
            $msg['delivery_bn'] = implode("<br/>",$delivery_fail_bns);
            $msg['delivery_succ'] = $delivery_succ;
            if ($delivery_succ == 'succ'){
                $error_msg = '部分发货单校验失败';
            }else{
                $error_msg = '校验失败';
            }
            $this->splash('error', $goto_url, $error_msg,'', array('msg'=>$msg));
            exit;
        }
    }

    /**
     * 判断发货单号相关订单处理状态是否处于取消或异常
     *
     * @param bigint $dly_id
     * @return null
     */
    function checkOrderStatus($dly_id, $msg_flag=false, &$msg=NULL){
        if (!$dly_id) return false;
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

    function group_check(){
        $deliCfgLib = kernel::single('ome_delivery_cfg');
        $verfy = $deliCfgLib->getValue('verify');
        if($verfy==1){
            $deliveryObj = &$this->app->model('delivery');
            $orderTypeObj = &app::get('omeauto')->model('order_type');
            $groupFilter['tid'] = $deliCfgLib->getValue('ome_delivery_verify_group');
            $groupFilter['disabled'] = 'false';
            $groupFilter['delivery_group'] = 'true';
            $orderTypes = $orderTypeObj->getList('*',$groupFilter);

            $filter = array(
                'pause'=>'false',
                'verify'=>'false',
                'process'=>'false',
                'parent_id'=>0,
                'disabled'=>'false',
                'type'=>'normal',
                'status'=>array('ready','progress','succ'),
                'expre_status' => 'true',
            );

            # 三种打印配置 三种完成打印可能
            $btncombi_single = $deliCfgLib->btnCombi('single');
            $btncombi_multi = $deliCfgLib->btnCombi('multi');
            $btncombi_basic = $deliCfgLib->btnCombi();
            $filter['print_finish'] = array(
                ''=> $btncombi_basic,
                'single' => $btncombi_single,
                'multi' => $btncombi_multi,
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

            $this->pagedata['num'] = $deliveryObj->countNoVerifyDelivery();
            $this->pagedata['num_available'] = count($deliveryAll);
            $this->pagedata['deliveryGroup'] = $deliveryGroup;
            $this->pagedata['jsonDeliveryGroup'] = json_encode($deliveryGroup);

            /* 操作时间间隔 start */
            $lastGroupCalibration = app::get('ome')->getConf('lastGroupCalibration'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
            $groupCalibrationIntervalTime = app::get('ome')->getConf('ome.groupCalibration.intervalTime'); //每次操作的时间间隔

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

    function ajaxDoGroupCheck(){
        $tmp = explode('||', $_POST['ajaxParams']);
        $group = $tmp[0];
        $delivery = explode(';', $tmp[1]);

        if($delivery && count($delivery)>0){
            /* 执行时间判断 start */
            $pageBn = intval($_POST['pageBn']);
            $lastGroupCalibration = app::get('ome')->getConf('lastGroupCalibration'); //上次获取订单信息(key为execTime表示执行时间，key为pageBn表示页面编号),
            $groupCalibrationIntervalTime = app::get('ome')->getConf('ome.groupCalibration.intervalTime'); //每次操作的时间间隔

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

            $deliveryObj = &$this->app->model('delivery');
            $filter = array(
                'delivery_id'=>$delivery,
            );
            $deliverys = $deliveryObj->getList('delivery_id,delivery_bn,verify,logi_no',$filter);
            $succ = 0;
            $fail = 0;
            $failInfo = array();
            foreach($deliverys as $value){
                $checkInfo = $this->checkOrderStatus($value['delivery_id'], true);
                if ($checkInfo && $value['verify'] == 'false' && $value['logi_no'] != ''){
                    if($deliveryObj->verifyDelivery($value,true)){
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
     *获取发货记录历史
    */
    function batchConsignLog(){
        $blObj  = &$this->app->model('batch_log');
        $dayBegin = mktime(0,0,0,date("m"),date("d"),date("Y"));//当天开始时间戳
        $dayEnd = mktime(23,59,59,date("m"),date("d"),date("Y"));//当天结束时间戳
        $blResult = $blObj->getList('*', array('log_type'=>'check','createtime|than'=>$dayBegin,'createtime|lthan'=>$dayEnd), 0, -1,'createtime desc');

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
    function updateBatchCheckLog(){
        $log_id = $_POST['log_id'];
        if($log_id){
            $status="'0','2'";
            $blResult = kernel::single('ome_batch_log')->get_List('check',$log_id,$status);
            foreach($blResult as $k=>$v){
                $blResult[$k]['status_value'] = kernel::single('ome_batch_log')->getStatus($v['status']);
                $blResult[$k]['fail_number'] = $v['fail_number'];
                $blResult[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            }

            echo json_encode($blResult);
        }

    }
     /**
     *保存批量发货至记录队列表中
     */
    function saveBatchCheck(){
        $goto_url = 'index.php?app=ome&ctl=admin_check&act=batchIndex';
        $ids = $_POST['delivery_id'];
        $delivery_ids = explode(',', $ids);
        $delivery_ids = array_filter($delivery_ids);

        if ( !$delivery_ids ) {
            $this->splash('success',$goto_url ,'快递单列表为空！');
        }

        $batch_number = count($delivery_ids);
        $blObj  = &$this->app->model('batch_log');

        $bldata = array(
          'op_id' => kernel::single('desktop_user')->get_id(),
          'op_name' => kernel::single('desktop_user')->get_name(),
          'createtime' => time(),
          'batch_number' => $batch_number,
          'log_type'=>'check',
          'log_text'=>serialize($delivery_ids)
         );
        $result = $blObj->save($bldata);

        $this->splash('success',$goto_url ,'已提交至队列');
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
        $this->display('admin/delivery/batch_chklog_detail.html');
    }

}
?>
