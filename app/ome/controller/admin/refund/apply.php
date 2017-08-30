<?php
class ome_ctl_admin_refund_apply extends desktop_controller{
    var $name = "退款单";
    var $workground = "finance_center";

    function index(){
       
       #增加退款单 disabled='true'不显示 ExBOY 2014.08.13
       switch ($_GET['status']){
           case '0':
               $base_filter = array('status'=>'0', 'disabled'=>'false');
               $title = '未处理';
               break;
           case '1':
               $base_filter = array('status'=>'1', 'disabled'=>'false');
               $title = '审核中';
               break;
           case '2':
               $base_filter = array('status'=>'2', 'disabled'=>'false');
               $title = '已接受申请';
               break;
           case '3':
               $base_filter = array('status'=>'3', 'disabled'=>'false');
               $title = '已拒绝';
               break;
           case '4':
               $base_filter = array('status'=>'4', 'disabled'=>'false');
               $title = '已退款';
               break;
           case '5':
               $base_filter = array('status'=>'5', 'disabled'=>'false');
               $title = '退款中';
               break;
           case '6':
               $base_filter = array('status'=>'6', 'disabled'=>'false');
               $title = '退款失败';
               break;
           default:
               $base_filter = array('disabled'=>'false');
               $title = '全部';
       }
       $action = array();
       switch ($_GET['view']) {
            
            case '1':
            case '2':
            case '3':
            default:
                $action[] = array(
                        'label' => '批量接受申请',
                        'submit' => 'index.php?app=ome&ctl=admin_refund_apply&act=batch_Updatestatus&status_type=agree',
                        'target' => "dialog::{width:700,height:490,title:'批量接受申请'}",
                        
                      );    
                $action [] = array(
                        'label' => '批量拒绝',
                        'submit' => 'index.php?app=ome&ctl=admin_refund_apply&act=batch_Updatestatus&status_type=refuse',
                    'target' => "dialog::{width:700,height:490,title:'批量拒绝'}",
                        
                      );
                $action[] = array(
                        'label' => '批量同步天猫退款单',
                        'submit' => 'index.php?app=ome&ctl=admin_refund_apply&act=batch_get_refund_detial&type=batch',
                        'target' => "dialog::{width:700,height:490,title:'批量同步'}",
                );
                break;
                
                case '4':
                case '5':
                case '6':
                break;
                    
            
        }
       $this->finder('ome_mdl_refund_apply',array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'base_filter' => $base_filter,
            'title' => '退款确认',
            'actions' => $action,
       ));
    }

    function _views(){
        $mdl_refund_apply = $this->app->model('refund_apply');
        $sub_menu = array(
            0 => array('label'=>__('全部'),'filter'=>array('disabled'=>'false'),'optional'=>false),
            1 => array('label'=>__('未处理'),'filter'=>array('status'=>'0', 'disabled'=>'false'),'optional'=>false),
            2 => array('label'=>__('审核中'),'filter'=>array('status'=>'1', 'disabled'=>'false'),'optional'=>false),
            3 => array('label'=>__('已接受申请'),'filter'=>array('status'=>'2', 'disabled'=>'false'),'optional'=>false),
            4 => array('label'=>__('已拒绝'),'filter'=>array('status'=>'3', 'disabled'=>'false'),'optional'=>false),
            5 => array('label'=>__('退款中'),'filter'=>array('status'=>'5', 'disabled'=>'false'),'optional'=>false),
            6 => array('label'=>__('退款失败'),'filter'=>array('status'=>'6', 'disabled'=>'false'),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_refund_apply->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_refund_apply&act=index&view='.$i++;
        }
        return $sub_menu;
    }

    function request($order_id,$return_id=0){
        $result = kernel::single('ome_refund_apply')->show_refund_html($order_id, $return_id);
        if ($result['result'] == true){
            return $result;
        }else{
            exit($result['msg']);
        }
    }

    function accept($apply_id)
    {
      $url = "index.php?ctl=admin_refund_apply&act=accept&app=ome&p[0]=".$apply_id;
      if (!$apply_id) $this->splash('error',$url,'退款申请号传递出错');

       $oRefaccept = &$this->app->model('refund_apply');
       $oOrder = &$this->app->model('orders');
       $is_archive = kernel::single('archive_order')->is_archive($_GET['source']);

       if ($is_archive) {
           $oOrder = &app::get('archive')->model('orders');
       }
       $deoObj = &app::get('ome')->model('delivery_order');
       $finder_id = $_GET['finder_id'];
       if ($_POST)
       {
          
          $oRefund = &$this->app->model('refunds');
          $oLoger = &$this->app->model('operation_log');
          $objShop = &$this->app->model('shop');
            //只有已经接受申请的才能确认。
            $apply_detail = $oRefaccept->refund_apply_detail($apply_id);
            if (in_array($apply_detail['status'],array('2','5','6')))
            {
                $order_id = $apply_detail['order_id'];
                $order_detail = $oOrder->order_detail($order_id);
                $ids = $deoObj->getList('delivery_id',array('order_id'=>$order_id));
                //如果申请金额大于已付款金额，则报错、退出
                $money = $apply_detail['money']-$apply_detail['bcmoney'];
                if (round($money,3)>round(($order_detail['payed']),3))
                {
                    $this->splash('error',$url,'退款申请金额'.$money.'大于订单上的余额！'.$order_detail['payed']);
                }
                $fail_msg = '';
                $shop_detail = $objShop->dump($order_detail['shop_id'], 'node_id,node_type');
                $c2c_shop = ome_shop_type::shop_list();
                $refund_request = false;
                if ($_POST['api_fail_flag'] == 'false'){
                    if ($shop_detail['node_id'] && !in_array($shop_detail['node_type'],$c2c_shop) && $order_detail['source'] == 'matrix'){
                        if ($_POST['api_refund_request'] == 'true'){
                            $refund_request = true;
                        }else{
                            $fail_msg = '向前端退款失败,仅本地退款!';
                        }
                        
                    }
                }else{
                    if ($_POST['api_refund_request'] == 'true'){
                        $refund_request = true;
                    }
                }

                //退款金额为零将不发起前端同步
                if ($apply_detail['money'] <= 0){
                    $refund_request = false;
                }
                if ($is_archive) {
                    $refund_request = false;
                }
                //发起前端退款请求
                if ($refund_request == true){

                    if (!$_POST['pay_type']){
                      $this->splash('error',$url,'请选择付款类型。');
                    }
                    $_POST['order_id'] = $order_id;
                    $_POST['apply_id'] = $apply_id;
                    $_POST['refund_bn'] = $apply_detail['refund_apply_bn'];
                    $_POST['bcmoney'] = $apply_detail['bcmoney'];
                    if ($is_archive) {
                        $_POST['is_archive'] = '1';
                    }
                    if ($oRefund->refund_request($_POST)){
                      $this->splash('success',$url,'退款请求发起成功');
                    }else{
                      $this->splash('error',$url,'退款请求发起失败,请重试');
                    }
                }else{
                  $this->begin("index.php?ctl=admin_refund_apply&act=accept&app=ome&p[0]=".$apply_id);
                    //查找本申请是否是与售后相关的，如果相关，则检查并回写数据
                    $oretrun_refund_apply = &$this->app->model('return_refund_apply');
                    $return_refund_appinfo = $oretrun_refund_apply->dump(array('refund_apply_id'=>$apply_id));
                    if ($return_refund_appinfo['return_id'])
                    {
                        $oreturn = &$this->app->model('return_product');
                        $return_info = $oreturn->product_detail($return_refund_appinfo['return_id']);
                        if (($return_info['refundmoney']+$apply_detail['money'])>$return_info['tmoney'])
                        {
                            $this->end(false, '申请退款金额大于售后的退款金额！');
                        }
                        $return_info['refundmoney'] = $return_info['refundmoney']+$apply_detail['money'];

                        $oreturn->save($return_info);

                        $oLoger->write_log('return@ome',$return_info['return_id'],"售后退款成功。");
                    }
                    //订单信息更新
                    $orderdata = array();
                    if (round($apply_detail['money'],3)== round(($order_detail['payed']),3))
                    {
                        $orderdata['pay_status'] = 5;
                        //2011.12.13删除屏蔽
                        //将原来的全额退款的 未发货的订单取消 封装成一个方法check_iscancel
                        //$oRefaccept->check_iscancel($apply_detail['order_id'],$apply_detail['memo']); 下面更新订单状态的时候也会释放掉冻结库存
                    }
                    else
                    {
                        $orderdata['pay_status'] = 4;
//                        //部分退款时打回未发货的发货单
//                        $oOrder->rebackDelivery($ids,'',true);
                    }
                    $orderdata['order_id'] =  $apply_detail['order_id'];
                    $orderdata['payed'] = $order_detail['payed'] - ($apply_detail['money']-$apply_detail['bcmoney']);//需要将补偿运费减掉
                    $oOrder->save($orderdata);

                    $oLoger->write_log('order_modify@ome',$orderdata['order_id'],$fail_msg."退款成功，更新订单退款金额。");

                    //退款申请状态更新
                    $applydata = array();
                    $applydata['apply_id'] = $apply_id;
                    $applydata['status'] = 4;//已经退款
                    $applydata['refunded'] = $apply_detail['money'];// + $order_detail['payinfo']['cost_payment'];
                    $applydata['last_modified'] = time();
                    $applydata['account'] = $_POST['account'];
                    $applydata['pay_account'] = $_POST['pay_account'];
                    $applydata['pay_type'] = $_POST['pay_type'];//退款类型
                    $applydata['payment'] = $_POST['payment'];//退款支付方式
                    $oRefaccept->save($applydata,true);
                    $oLoger->write_log('refund_apply@ome',$applydata['apply_id'],"退款成功，更新退款申请状态。");

                    //更新售后退款金额
                    $return_id = intval($_POST['return_id']);
                    if(!empty($return_id)){
                       $sql = "UPDATE `sdb_ome_return_product` SET `refundmoney`=IFNULL(`refundmoney`,0)+{$apply_detail['money']} WHERE `return_id`='".$return_id."'";
                       kernel::database()->exec($sql);
                    }

                    //单据生成：生成退款单
                    $refunddata = array();
                    $refund_apply_bn = $apply_detail['refund_apply_bn'];
                    if ($refund_apply_bn){
                        $refund_bn = $refund_apply_bn;
                    }else{
                        $refund_bn = $oRefund->gen_id();
                    }
                    $refunddata['refund_bn'] = $refund_bn;
                    $refunddata['order_id'] = $apply_detail['order_id'];
                    $refunddata['shop_id'] = $order_detail['shop_id'];
                    $refunddata['account'] = $_POST['account'];
                    $refunddata['bank'] = $_POST['bank'];
                    $refunddata['pay_account'] = $apply_detail['pay_account'];
                    $refunddata['currency'] = $order_detail['currency'];
                    $refunddata['money'] = $apply_detail['money'];
                    $refunddata['paycost'] = 0;//没有第三方费用
                    $refunddata['cur_money'] = $apply_detail['money'];//汇率计算 TODO:应该为汇率后的金额，暂时是人民币金额
                    $refunddata['pay_type'] = $_POST['pay_type'];
                    $refunddata['payment'] = $_POST['payment'];
                    $paymethods = ome_payment_type::pay_type();
                    $refunddata['paymethod'] = $paymethods[$refunddata['pay_type']];
                    //Todo ：确认paymethod
                    $opInfo = kernel::single('ome_func')->getDesktopUser();
                    $refunddata['op_id'] = $opInfo['op_id'];

                    $refunddata['t_ready'] = time();
                    $refunddata['t_sent'] = time();
                    $refunddata['status'] = "succ";#支付状态
                    $refunddata['memo'] = $apply_detail['memo'];
                    $oRefund->save($refunddata);

                    //更新订单支付状态
                    if ($is_archive) {
                        kernel::single('archive_order_func')->update_order_pay_status($apply_detail['order_id']);
                    }else{
                        kernel::single('ome_order_func')->update_order_pay_status($apply_detail['order_id']);
                    }
                    //生成售后单
                    kernel::single('sales_aftersale')->generate_aftersale($apply_id,'refund');

                    $oLoger->write_log('refund_accept@ome',$refunddata['refund_id'],"退款成功，生成退款单".$refunddata['refund_bn']);
                    if(!empty($return_id)){
                      $return_data = array ('return_id' => $_POST['return_id'], 'status' => '4', 'refundmoney'=>$refunddata['money'], 'last_modified' => time () );
                      $Oreturn_product = $this->app->model('return_product');
                      $Oreturn_product->update_status ( $return_data );
                    }
                    $this->end(true, '申请退款成功', 'index.php?app=ome&ctl=admin_refund_apply&act=index');
                }
            }
       }
       else
       {
           //退款请求失败标识
           $refunds = $oRefaccept->refund_apply_detail($apply_id);
           $this->pagedata['refund'] = $refunds;
           if ($refunds['status'] == '6'){//退款失败
               $api_fail_flag = 'true';
           }else{
               $api_fail_flag = 'false';
           }
           $this->pagedata['api_fail_flag'] = $api_fail_flag;
           $order_detail = $oOrder->order_detail($this->pagedata['refund']['order_id']);
           $this->pagedata['order'] = $order_detail;
           $oPayment = &$this->app->model('payments');

           //前端店铺支付方式
           $payment_cfgObj = &$this->app->model('payment_cfg');
           $oShop = &$this->app->model('shop');
           $c2c_shop = ome_shop_type::shop_list();
           $shop_id = $order_detail['shop_id'];
           $shop_detail = $oShop->dump($shop_id,'node_type,node_id');
           if ($shop_id){
               $payment = kernel::single('ome_payment_type')->paymethod($shop_id);
           }else{
               $payment = $oPayment->getMethods();
           }
           $payment_cfg = $payment_cfgObj->dump(array('pay_bn'=>$order_detail['pay_bn']), 'id,pay_type');

           $this->pagedata['shop_id'] = $shop_id;
           $this->pagedata['node_id'] = $shop_detail['node_id'];
           $this->pagedata['payment'] = $payment;
           $this->pagedata['pay_type'] = $payment_cfg['pay_type'];
           if ($payment_cfg['id']){
               $order_paymentcfg = kernel::single('ome_payment_type')->paymethod($shop_id,$payment_cfg['pay_type']);
           }
           $this->pagedata['order_paymentcfg'] = $order_paymentcfg;
           $this->pagedata['payment_id'] = $payment_cfg['id'];
           $this->pagedata['typeList'] = ome_payment_type::pay_type();
           $this->pagedata['pay_type'] = $this->pagedata['pay_type'];
           $aRet = $oPayment->getAccount();
           $aAccount = array('--使用已存在帐户--');
            foreach ($aRet as $v){
                $aAccount[$v['bank']."-".$v['account']] = $v['bank']."-".$v['account'];
            }
           $addon = unserialize($refunds['addon']);
           $this->pagedata['return_id'] = $addon['return_id'];
           $this->pagedata['pay_status'] = kernel::single('ome_order_status')->pay_status();
           $this->pagedata['finder_id'] = $finder_id;
           $this->pagedata['pay_account'] = $aAccount;
           $memberid = $this->pagedata['order']['member_id'];
           $oMember = &$this->app->model('members');
           $this->pagedata['member'] = $oMember->member_detail($memberid);

           $this->display('admin/refund/refund_accept.html');
       }
    }

   /*add by hujie 添加退款申请*/
    function showRefund(){
        if ($_POST){
            if ($_POST['back_url'] != 'order_confirm'){
                $begin_url = "index.php?ctl=admin_refund_apply&act=request&app=ome&p[0]=".$_POST['order_id'];
            }
            $this->begin($begin_url);
            /*
            if (strval($_POST['refund_money']) <= 0){
                $this->end(false, app::get('base')->_('退款金额必须大于0'));
            }
            */
            $return = kernel::single('ome_refund_apply')->refund_apply_add($_POST);
            if ($return['result'] == true){
                $result  = true;
            }else{
                $result = false;
            }
            $msg = $return['msg'];
            $back_url = explode("|",$_POST['back_url']);
            if (count($back_url)){
                $back_url = 'index.php?app=ome&ctl='.$back_url[0].'&act='.$back_url[1].'&'.$back_url[2];
            }
            //将订单状态改为退款申请中
            kernel::single('ome_order_func')->update_order_pay_status($_POST['order_id']);
            if ($_POST['back_url'] != 'order_confirm'){
                $this->end($result, app::get('base')->_($msg), $back_url);
            }else{
                $this->end($result, app::get('base')->_($msg));
            }
        }
    }

    function do_export()
    {
        $selected = $_POST['apply_id'];
        $oRefaccept = &$this->app->model('refund_apply');
        foreach ($selected as $oneappid)
        {
            $export[] = $oRefaccept->refund_apply_detail($oneappid);
        }
        echo '<pre>';
        print_r($export);
        echo '</pre>';
    }

    
    

    
    /**
     * 上传凭证留言
     * @param   type taobao/tmall
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function refuse_message($apply_id,$type)
    {
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $op_name = kernel::single('desktop_user')->get_name();
        if ($_POST) {
            $this->begin();
            $apply_id = $_POST['apply_id'];
            $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
            if ($type == 'tmall') {
                $oRefund_apply_type = $this->app->model('refund_apply_tmall');
            }else{
                $oRefund_apply_type = $this->app->model('refund_apply_taobao');
            }
            $data = array(
                'apply_id'=>$apply_id,
                'shop_id'=>$refunddata['shop_id'],
                'refund_apply_bn'=>$refunddata['refund_apply_bn'],
            );
            $memo = array();
            
            $newmemo = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i:s',time()), 'op_content'=>htmlspecialchars($_POST['memo']));
            $upload_file = "";
            if($_FILES ['attachment']['size'] != 0){
                if ($_FILES ['attachment'] ['size'] > 512000) {
                    $this->end(false,'上传文件不能超过500K!');
                }
                $type = array ('gif','jpg','png');
                $imgext = strtolower ( $this->fileext ( $_FILES ['attachment'] ['name'] ) );
                if ($_FILES ['attachment'] ['name'])
                    if (! in_array ( $imgext, $type )) {
                        $text = implode ( ",", $type );
                        $this->end(false,"您只能上传以下类型文件{$text}!");
                    }
            
                $ss = kernel::single ( 'base_storager' );
                $id = $ss->save_upload ( $_FILES ['attachment'], "file", "", $msg ); //返回file_id;
                $newmemo['image'] = $ss->getUrl ( $id, "file" );
                $imagebinary = $newmemo['image'];
                //$imagebinary = &app::get('ome')->model('return_product')->imagetobinary($_FILES['attachment']['tmp_name']);;
            }
            $memo[] = $newmemo;
            $refund_apply = $oRefund_apply_type->dump(array('apply_id'=>$apply_id));

            if ($refund_apply ) {
                $oldmemo = $refund_apply['message_text'];
                if ($oldmemo) {
                    $oldmemo = unserialize($oldmemo);
                    foreach ($oldmemo as $oldmemo ) {
                        $memo[] = $oldmemo;
                    }
                    
                }
                if ($memo) {
                    $data['message_text'] = serialize($memo);
                }
                
                $oRefund_apply_type->update($data,array('apply_id'=>$apply_id));
                
            }else{
                if ($memo) {
                    $data['message_text'] = serialize($newmemo);
                }
                
                $oRefund_apply_type->save($data);
            }
            #回写
            foreach(kernel::servicelist('service.refund') as $object=>$instance){
                if(method_exists($instance, 'add_refundmemo')){
                    $data['newmemo'] = $newmemo;
                    if ($imagebinary) {
                        $data['imagebinary'] = $imagebinary;
                    }
                    $instance->add_refundmemo($data);
                }
            }
            $this->end(true,'上传成功');
        }
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->pagedata['apply_id'] = $apply_id;
        $this->display('admin/refund/plugin/refund_memo.html');
    }

    function fileext($filename) {
        return substr ( strrchr ( $filename, '.' ), 1 );
    }

    function file_download2($apply_id) {
        $oProduct = &$this->app->model ( 'return_product' );
        $oApply = &$this->app->model ( 'refund_apply_tmall' );
        $info = $oApply->dump ( $apply_id );
        $filename = $info ['refuse_proof'];
        if (is_numeric ( $filename )) {
            $ss = kernel::single ( 'base_storager' );
            $a = $ss->getUrl ( $filename, "file" );
            $oProduct->file_download ( $a );
        } else {
            header ( 'Location:' . $filename );
        }

    }

    
    /**
     * 拒绝
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function upload_refuse_message($apply_id,$type='taobao')
    {
        set_time_limit(0);
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $op_name = kernel::single('desktop_user')->get_name();
        $oLoger = &app::get('ome')->model('operation_log');
        if ($_POST) {
            $this->begin();
            $apply_id = $_POST['apply_id'];
            $shop_type = $_POST['type'];
            $refunddata = $oRefund_apply->refund_apply_detail($apply_id);
            
            if ($shop_type == 'tmall') {
                $oRefund_apply_type = $this->app->model('refund_apply_tmall');
                $refund_tmall = $oRefund_apply_type->dump(array('apply_id' => $apply_id));
                $operation_contraint = $refund_tmall['operation_contraint'];
                if ($operation_contraint) {
                    $operation_contraint = explode('|',$operation_contraint);
                    if ( in_array('cannot_refuse',$operation_contraint) ) {
                        $this->end(false,'此单据,不允许拒绝，必须同意');
                    }
                    if ( in_array('refund_onweb',$operation_contraint) ) {
                        $this->end(false,'此单据,回到web页面上操作');
                    }
                }
            }else{
                $oRefund_apply_type = $this->app->model('refund_apply_taobao');
            }
            $data = array(
                'apply_id'=>$apply_id,
                'shop_id'=>$refunddata['shop_id'],
                'refund_apply_bn'=>$refunddata['refund_apply_bn'],
                
            );
            $memo = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i:s',time()), 'op_content'=>htmlspecialchars($_POST['memo']));
            $upload_file = "";
            if($_FILES ['attachment']['size'] != 0){
                if ($_FILES ['attachment'] ['size'] > 512000) {
                    $this->end(false,'上传文件不能超过500K!');
                }

                $type = array ('gif','jpg','png');
                $imgext = strtolower ( $this->fileext ( $_FILES ['attachment'] ['name'] ) );
                if ($_FILES ['attachment'] ['name'])
                    if (! in_array ( $imgext, $type )) {
                        $text = implode ( ",", $type );
                        $this->end(false,"您只能上传以下类型文件{$text}!");
                    }
                $ss = kernel::single ( 'base_storager' );
                $id = $ss->save_upload ( $_FILES ['attachment'], "file", "", $msg ); //返回file_id;
                $memo['image'] = $ss->getUrl ( $id, "file" );
                if ($shop_type == 'tmall') {
                    $rh = fopen($_FILES['attachment']['tmp_name'],'rb');
                    $imagebinary = fread($rh, filesize($_FILES['attachment']['tmp_name']));
                    $imagebinary = base64_encode($imagebinary);
                    fclose($rh);
                }else{
                    $imagebinary = $memo['image'];
                }
           }else{
                $this->end(false,'请上传凭证图片!');
           }
            
            $refund_apply = $oRefund_apply_type->dump(array('apply_id'=>$apply_id));
            if ($memo) {
                $data['memo'] = serialize($memo);
            }
            if ($refund_apply ) {
                
                $oRefund_apply_type->update($data,array('apply_id'=>$apply_id));
                
            }else{
                $oRefund_apply_type->save($data);
            }
            #回写
            $refund_service = kernel::single('ome_service_refund_apply');

            if(method_exists($refund_service, 'update_status')){
                $adata = array(
                    'refuse_message'  => htmlspecialchars($_POST['memo']),
                    'refuse_proof'   =>$imagebinary,
                    'apply_id'     =>$apply_id,
                    'imgext'       =>$imgext,
                );
               
                $rs = $refund_service->update_status($adata,3,'sync');
                
                if ($rs['rsp'] == 'succ') {
                    kernel::single('ome_refund_apply')->update_refund_applyStatus('3',$refunddata);;
                }else{
                    $this->end(false,$rs['msg']);
                }
            }
            $this->end(true,'上传成功');
        }
        
        $this->pagedata['apply_id'] = $apply_id;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->pagedata['type'] = $type;
        $this->display('admin/refund/plugin/refuse_message.html');
    }
   
    
    /**
     * 批量变更退款申请单状态
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function batch_Updatestatus()
    {
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $oReturn_batch = &app::get('ome')->model('return_batch');
        $status_type = $_GET['status_type'];
        if (!in_array($status_type,array('agree','refuse'))) {
            echo '暂不支持此状态变更';
            exit;
        }
        $error_msg = array();
        $chk_msg = array();//检测
        $apply_list = $oRefund_apply->getlist('shop_id,refund_apply_bn,status,shop_type,source',array('apply_id'=>$_POST['apply_id']));
        if ($status_type == 'agree') {#同意
            foreach ( $apply_list as $apply ) {
                $apply_id = $apply['apply_id'];
                $status = $apply['status'];
                
                if (!in_array($status,array('0','1'))) {
                    $error_msg[] = '单据号:'.$apply['refund_apply_bn'].',的状态不可以接受申请';
                }
                if ($apply['shop_type'] == 'tmall' && $apply['source'] == 'matrix') {
                    $return_batch = $oReturn_batch->dump(array('shop_id'=>$apply['shop_id'],'batch_type'=>'accept_refund','is_default'=>'true'));
                    if (!$return_batch) {
                        $chk_msg[] = '此次提交包含天猫店铺,请设置默认信息!';
                        break;
                    }
                }
            }
        }elseif ( $status_type == 'refuse' ){
            foreach ( $apply_list as $apply ) {
                $apply_id = $apply['apply_id'];
                $status = $apply['status'];
                $msg = '';
                if (!in_array($status,array('0','1','2'))) {
                    $msg = '单据号:'.$apply['refund_apply_bn'].',的当前状态不可以拒绝';
                    
                }
                if ($apply['shop_type'] == 'tmall' && $apply['source'] == 'matrix') {
                    $return_batch = $oReturn_batch->dump(array('shop_id'=>$apply['shop_id'],'batch_type'=>'refuse','is_default'=>'true'));
                    if (!$return_batch) {
                        $chk_msg[] = '此次提交包含天猫店铺,请设置默认信息拒绝留言和凭证!';
                        break;
                    }
                }
                if ($msg) {
                    $error_msg[] = $msg;
                }
                
            }
        }
        //查询是否都是线上单据，是否淘宝和天猫
        $applyObj = kernel::single('ome_refund_apply');
        $this->pagedata['error_msg'] = $error_msg;
        $this->pagedata['chk_msg'] = $chk_msg;
        $need_refund_list = $applyObj->refund_list($status_type,$_POST['apply_id']);
        $this->pagedata['need_refund_list_count'] = count($need_refund_list);
        $need_refund_list = json_encode($need_refund_list);
        $this->pagedata['need_refund_list'] = $need_refund_list;
        $this->pagedata['status_type'] = $status_type;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/refund/plugin/batch_taobao.html');
    }

    
    /**
     * 批量更新
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function ajax_batch()
    {
        set_time_limit(0);
        $refundObj = kernel::single('ome_refund_apply');
        $data = $_POST;
        $ajaxParams = trim($data['ajaxParams']);
        if (strpos($ajaxParams, ';')) {

            $params = explode(';', $ajaxParams);
        } else {

            $params = array($ajaxParams);
        }
        $status_type = $data['status_type'];
        $refund_id = json_decode($data['refund_id'],true);
        $rs = $refundObj->batch_update($status_type,$params);
        echo json_encode(array('total' => count($params), 'succ' => $rs['succ'], 'fail' => $rs['fail'],'error_msg'=>$rs['error_msg']));
    }
    
    /**
     * 更新退款单状态
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function do_updateApply($apply_id,$status)
    {
        
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $applyObj = kernel::single('ome_refund_apply');
        $apply = $oRefund_apply->dump($apply_id);
        $apply['oper_memo'] = '向线上请求拒绝失败,本地拒绝';
        $applyObj->update_refund_applyStatus($status,$apply);
        $data = array('rsp'=>'succ');
        echo json_encode($data);
    }
    /**
     * 批量同步退款申请单状态
     */
    function batch_get_refund_detial(){
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $oReturn_batch = &app::get('ome')->model('return_batch');
        $error_msg = array();
        $chk_msg = array();//检测
        $apply_list = $oRefund_apply->getlist('apply_id,shop_id,refund_apply_bn,status,shop_type,source',array('apply_id'=>$_POST['apply_id']));
        $need_refund_list = array();
        foreach ( $apply_list as $key=>$apply ) {
            $apply_id = $apply['apply_id'];
            $status = $apply['status'];

            if (!in_array($status,array('0','1')) ||($apply['shop_type'] != 'tmall' )) {
                $error_msg[] = '单据号:'.$apply['refund_apply_bn'].',的状态或来源不可以批量同步！';
                unset($apply_list[$key]);
            }
            if ( $apply['source'] != 'matrix') {
                $error_msg[] = '单据号:'.$apply['refund_apply_bn'].',的不是线上订单！';
                unset($apply_list[$key]);
            }
            if(!empty($apply_list[$key])){
                $need_refund_list[] = $apply_list[$key]['apply_id'];
            } 
        }
        if(empty($apply_list)){
            $chk_msg[] = '没有符合更新条件的退款单！';
        }
        $this->pagedata['error_msg'] = $error_msg;
        $this->pagedata['chk_msg'] = $chk_msg;
        $this->pagedata['need_refund_list_count'] = count($need_refund_list);
        $need_refund_list = json_encode($need_refund_list);
        $this->pagedata['need_refund_list'] = $need_refund_list;
        $this->pagedata['ctl'] = 'refund_apply';
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/refund/plugin/batch_tmall.html');
    }

    /**
     * 天猫同步更新退款单
     */
    function ajax_get_refund_detial(){
        set_time_limit(0);
        $data = $_POST;
        $ajaxParams = trim($data['ajaxParams']);
        if (strpos($ajaxParams, ';')) {
            $params = explode(';', $ajaxParams);
        } else {
            $params = array($ajaxParams);
        }
        $rs = $this->get_refund_detial($params);
        echo json_encode(array('total' => count($params), 'succ' => $rs['succ'], 'fail' => $rs['fail'],'error_msg'=>$rs['error_msg']));
    }
    #重新更新退款单
    function get_refund_detial($all_apply_id){
        set_time_limit(0);
        $oRefund_apply = &app::get('ome')->model('refund_apply');
        $obj_orders = &app::get('ome')->model('orders');
        
        $error_msg = array();
        $need_apply_id = array();
        
        foreach ($all_apply_id as $_apply_id ) {
            $apply_id = explode('||',$_apply_id);
            $need_apply_id[] = $apply_id[1];
        }
        $sql = 'SELECT 
                    apply.source,apply.shop_type,apply.refund_apply_bn,apply.shop_id,orders.order_bn
                FROM sdb_ome_refund_apply  apply
                left join sdb_ome_orders orders
                on apply.order_id=orders.order_id
                WHERE apply_id in('.implode(',',$need_apply_id).')';
        $apply_list = $oRefund_apply->db->select($sql);
        foreach($apply_list as $apply){
            $shop_id = $apply['shop_id'];
            $refund_id = $apply['refund_apply_bn'];
            $refund_phase = 'onsale'; 
            $order_bn = $apply['order_bn'];
            $returnRsp = kernel::single('apibusiness_router_request')->setShopId($shop_id)->get_refund_detial($refund_id,$refund_phase,$order_bn);
            if ($returnRsp && $returnRsp['rsp'] == 'fail') {
                $fail++;
                $error_msg[] = '单号:'.$apply['refund_apply_bn'].",".$returnRsp['err_msg'];
            }else{
                if($returnRsp['rsp'] == 'succ') {
                    #在退款模块，只处理退款的,不处理售后
                    if($returnRsp['data']['has_good_return'] == false ){
                        if($returnRsp['data']['refund_fee'] >0 ){
                            $returnRsp['data']['refund_type'] = 'refund';#只退款
                            kernel::single('ome_return')->get_return_log($returnRsp['data'],$shop_id,$msg);
                        }else{
                            $fail++;
                            $error_msg[] = '单号:'.$apply['refund_apply_bn'].",".' error refund money';
                        }
                    }else{
                        #在退款这边，不处理售后的单子
                        $fail++;
                        $error_msg[] = '单号:'.$apply['refund_apply_bn'].",".$rs['msg'];
                    }
                }
            }
        }
        $result = array('error_msg'=>$error_msg,'fail'=>$fail);
        return $result;
    }
}
?>