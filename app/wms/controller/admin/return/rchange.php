<?php
class wms_ctl_admin_return_rchange extends desktop_controller {
    var $name = "退换货单";
    var $workground = "wms_center";

    function index(){

        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
        );
        
        if($_GET['flt'] == 'process_list'){
            $oBranch = &app::get('ome')->model('branch');
           $params['title'] = '售后收货';
           //$params['base_filter'] = array('status|noequal'=>'succ');
           if(!$_GET['view']){
              $params['base_filter']['is_check'] = array('1','3','4','7','8','9','13');
           }
           $params['use_buildin_export'] = false;
           #过滤自有仓储退货单
           $wms_id = kernel::single('wms_branch')->getBranchByselfwms();
           $branch_list = $oBranch->getList('branch_id', array('wms_id'=>$wms_id), 0, -1);
           $branch_list[] = 0;

            if ($branch_list)
            $branch_ids = array();
            foreach ($branch_list as $branch_list) {
                $branch_ids[] = $branch_list['branch_id'];

            }
            $params['base_filter']['branch_id'] = $branch_ids;
        }else{
           $params['use_buildin_export'] = true;
           $params['title'] = '退换货单';
           //$params['base_filter'] = array('status|noequal'=>'succ');
           $params['actions'] = array(
                  array(
                    'label' => '新建退换货单',
                    'href' => 'index.php?app=ome&ctl=admin_return_rchange&act=rchange',
                    'target' => "dialog::{width:1200,height:546,title:'新建退换货单'}",
                  ),
           );
           
        }

        if(isset($_POST['return_type'])){
            $params['base_filter']['return_type'] = $_POST['return_type'];
        }else{
            //过滤拒收退货
            $params['base_filter']['return_type'] = array('return','change');
        }


        # 权限判定
        if(!$this->user->is_super()){
           $returnLib = kernel::single('ome_return');
           foreach ($params['actions'] as $key=>$action) {
               $url = parse_url($action['href']);
               parse_str($url['query'],$url_params);
                $has_permission = $returnLib->chkground($this->workground,$url_params);
                if (!$has_permission) {
                    unset($params['actions'][$key]);
                }
           }
        }
        
        $this->finder ( 'ome_mdl_reship' , $params );
    }

    function _views(){
        if($_GET['flt'] == 'process_list'){
            #$this->workground = "wms_center";
            $sub_menu = $this->_view_process();
        }else{
            #$this->workground = "aftersale_center";
            $sub_menu = $this->_view_all();
        }
        return $sub_menu;
    }

    function _view_all(){
        $mdl_reship = &app::get('ome')->model('reship');
        $sub_menu = array(
            0 => array('label'=>__('全部'),'optional'=>false),
            1 => array('label'=>__('未审核'),'filter'=>array('is_check'=>'0'),'optional'=>false),
            2 => array('label'=>__('审核成功'),'filter'=>array('is_check'=>'1'),'optional'=>false),
            3 => array('label'=>__('审核失败'),'filter'=>array('is_check'=>'2'),'optional'=>false),
            //4 => array('label'=>__('收货成功'),'filter'=>array('is_check'=>'3'),'optional'=>false),
            //5 => array('label'=>__('拒绝收货'),'filter'=>array('is_check'=>'4'),'optional'=>false),
            6 => array('label'=>__('拒绝'),'filter'=>array('is_check'=>'5'),'optional'=>false),
            7 => array('label'=>__('补差价'),'filter'=>array('is_check'=>'6'),'optional'=>false),
            8 => array('label'=>__('完成'),'filter'=>array('is_check'=>'7'),'optional'=>false),
            9 => array('label'=>__('质检通过'),'filter'=>array('is_check'=>'8'),'optional'=>false),
            10 => array('label'=>__('拒绝质检'),'filter'=>array('is_check'=>'9'),'optional'=>false),
            11=> array('label'=>__('未录入退回物流号'),'filter'=>array('filter_sql'=>'({table}return_logi_no is null or {table}return_logi_no="")'),'optional'=>false),
            12 => array('label'=>__('质检异常'),'filter'=>array('filter_sql'=>'({table}is_check="10" or ({table}need_sv="false" and {table}is_check="0"))','optional'=>false)),
        );

        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_reship->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_return_rchange&act=index&view='.$k;
        }

        return $sub_menu;
    }
    function _view_process(){
        $mdl_reship = &app::get('ome')->model('reship');
        $is_check = array('1','3','4','7','8','9');
        $sub_menu = array(
            0 => array('label'=>__('全部'),'filter'=>array('is_check'=>$is_check),'optional'=>false),
            1 => array('label'=>__('审核成功'),'filter'=>array('is_check'=>'1'),'optional'=>false),
            //2 => array('label'=>__('收货成功'),'filter'=>array('is_check'=>'3'),'optional'=>false),
            //3 => array('label'=>__('拒绝收货'),'filter'=>array('is_check'=>'4'),'optional'=>false),
            4 => array('label'=>__('完成'),'filter'=>array('is_check'=>'7'),'optional'=>false),
            5 => array('label'=>__('质检通过'),'filter'=>array('is_check'=>'8'),'optional'=>false),
            6 => array('label'=>__('拒绝质检'),'filter'=>array('is_check'=>'9'),'optional'=>false),
        );


        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_reship->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=wms&ctl=admin_return_rchange&act=index&flt=process_list&view='.$k;
        }

        return $sub_menu;
    }


    function rchange(){
        $this->pagedata['return_type'] = array('return'=>'退货','change'=>'换货');
        $this->pagedata['order_filter'] = array('pay_status'=>'1','ship_status'=>'1');
        $oProblem = &app::get('ome')->model('return_product_problem');
        $list = $oProblem->getList('problem_id,problem_name',array('disabled'=>'false'));
        $this->pagedata['problem_type'] = $list;

        $this->display('admin/return_product/rchange/rchange.html');
    }

    function add_rchange(){

        $this->begin();
        $post = kernel::single('base_component_request')->get_params(true);
        $Oreship = &app::get('ome')->model('reship');
        if(!$Oreship->validate($post,$v_msg)){
            $this->end(false,$v_msg);
        }

        # 生成退换货单
        $reship_bn = $Oreship->create_treship($post,$msg);

        if($reship_bn == false) {
            $this->end(false,$msg);
        }

        # 补差价
        if($reship_bn){
            $money = kernel::single('ome_return_rchange')->calDiffAmount($post);
            $refund['totalmoney'] = $money['totalmoney'];
            $refund['tmoney'] = $money['tmoney'];
            $refund['bmoney'] = $money['bmoney'];
            $refund['diff_money'] = $money['diff_money'];
            $refund['change_amount'] = $money['change_amount'];
            $refund['diff_order_bn'] = $post['diff_order_bn'] ? $post['diff_order_bn'] : '';
            $refund['cost_freight_money'] = $money['cost_freight_money'];

            $Oreship->update($refund,array('reship_bn'=>$reship_bn));
        }

        $reship = $Oreship->getList('reship_id',array('reship_bn'=>$reship_bn),0,1);

        $params['reship_id'] = $reship[0]['reship_id'];

        $this->end(true,$msg,null,$params);
    }


    /**
     * 根据order_bn快速获取订单信息
     *
     * @return void
     * @author yangminsheng
     **/
    function getOrderinfo()
    {
        $order_bn = trim($_GET['order_bn']);

        if ($order_bn){
            //已支付部分退款并且已发货或部分退货的款到发货订单或货到付款已发货或部分退货的订单
            $base_filter = array('disabled'=>'false','is_fail'=>'false','ship_status'=>array('1','3'),'pay_status'=>array('1','4'),'order_bn|has'=>$order_bn);

            $order = &app::get('ome')->model('orders');
            $data = $order->getList('order_id,order_bn',$base_filter);

            echo "window.autocompleter_json=".json_encode($data);
        }
    }

    function getOdersById(){

        $order_id = $_POST['id'];

        if ($order_id){
            $orders = &app::get('ome')->model('orders');
            $base_filter = array('disabled'=>'false','is_fail'=>'false','order_id'=>$order_id,'ship_status'=>array('1','3'),'pay_status'=>array('1','4'));
            //$base_filter['order_confirm_filter'] = "(sdb_ome_orders.is_cod='true' OR sdb_ome_orders.pay_status='1' OR sdb_ome_orders.pay_status='4')";
            $data = $orders->dump($base_filter, 'order_id,order_bn');
            $res['name'] = $data['order_bn'];
            $res['id'] = $data['order_bn'];
            echo json_encode($res);
            exit;
        }
    }

    /**
     * 获取订单信息
     *
     * @return void
     * @author
     **/
    function ajax_getOrderinfo()
    {
        $json_data = array('rsp'=>'fail','msg'=>'');
        $post = kernel::single('base_component_request')->get_params(true);

        $oOrders = &app::get('ome')->model ( 'orders' );
        $oProduct = &app::get('ome')->model ( 'return_product' );
        $oDelivery = &app::get('ome')->model ( 'delivery' );
        $order = $oOrders->dump ( array ('order_id' => $post['order_id'] ),'*' );


        if($order){
            $member = &app::get('ome')->model('members')->dump(array('member_id'=>$order['member_id']));

            $json_data['rsp'] = 'succ';
            $this->pagedata ['order_id'] = $order_id;

            $delivery = $oDelivery->getDeliveryByOrder('*',$post['order_id']);
            $order = array_merge($order,$delivery[0]);
            $order['member_id'] = $member['account']['uname'];
            $order['createtime'] = date('Y-m-d H:i:s',$order['createtime']);
            $this->pagedata['ship_area'] = $order['ship_area'];
            $order['ship_area'] = $this->fetch('admin/return_product/rchange/show_area.html');
            $json_data['msg'] = $order;
        }

        echo json_encode($json_data);
        exit;
    }

    /**
     * @description
     * @access public
     * @param Int $order_id 订单ID
     * @param String $type 数据类型 return:退货、change:换货
     * @return void
     */
    function get_data($order_id,$type){
        //获取仓库模式
        $newItems = array();
        $tmp_product = array();
        $oOrders_item = &app::get('ome')->model ( 'order_items' );
        $oReship_item = &app::get('ome')->model ( 'reship_items' );
        $items = $oOrders_item->getList ( '*', array ('order_id' => $order_id ),0,-1,'obj_id desc' );
        $order_object = &app::get('ome')->model('order_objects')->getList('*',array('order_id'=>$order_id,'obj_type'=>'pkg'));

        foreach($order_object as $object){
            $table = '<table><caption>捆绑信息</caption><thead><tr><th>货号</th><th>商品名称</th><th>价格</th><th>数量</th></tr></thead><tbody><tr>';
            $table .= '<td>'.$object['bn'].'</td><td>'.$object['name'].'</td><td>'.$object['price'].'</td><td>'.$object['quantity'].'</td>';
            $table .= '</tr></tbody></table>';
            $object['ref'] = $table;
            $oObject[$object['obj_id']] = $object;
        }

        $color = array('red','blue');
        foreach ( $items as $k => $v ) {
            if (!$objColor[$v['obj_id']]) {
                $objColor[$v['obj_id']] = $c = array_shift($color);
                array_push($color,$c);
            }

            if($newItems[$v['bn']] && $newItems[$v['bn']]['bn'] !=''){
                    $newItems[$v['bn']]['nums'] += $items[$k]['nums'];
                    $newItems[$v['bn']]['sendnum'] += $items[$k]['sendnum'];
            }else{
                $refund = $oReship_item->Get_refund_count ( $order_id, $v ['bn'] );
                $items [$k] ['effective'] = $refund;

                $items [$k]['obj_type'] = $oObject[$v['obj_id']]['obj_type'];

                if ($oObject[$v['obj_id']]['ref']) {
                    $items [$k]['ref'] = $oObject[$v['obj_id']]['ref'];
                    $items [$k]['color'] = $objColor[$v['obj_id']];
                }

                $items [$k] ['branch'] = $oReship_item->getBranchCodeByBnAndOd ( $v ['bn'], $order_id );
                $newItems[$v['bn']] = $items[$k];
            }
            $tmp_product[] = $items[$k]['product_id'];
        }

        $items = $newItems;
        if($type == 'return'){
            $this->pagedata['total_return_filter'] = implode(',',$tmp_product);
        }else{
            $this->pagedata['total_change_filter'] = implode(',',$tmp_product);
        }

        $branch_mode = &app::get ( 'ome' )->getConf ( 'ome.branch.mode' );
        $this->pagedata ['branch_mode'] = $branch_mode;
        $this->pagedata ['items'] = $items;

    }

    /**
     * 获取退入商品信息
     *
     * @return void
     * @author
     **/
    function ajax_getProductinfo_one(){
        $html = '';
        $this->get_data($_POST['order_id'],'return');
        $html = $this->fetch('admin/return_product/rchange/rc_html_t.html');
        echo $html;exit;
    }

    /**
     * 获取换出商品信息
     *
     * @return void
     * @author
     **/
    function ajax_getProductinfo_two(){
        $html = '';
        $this->get_data($_POST['order_id'],'change');
        $html = $this->fetch('admin/return_product/rchange/rc_html_c.html');
        echo $html;exit;
    }


    /**
     * 返回支付/退款明细
     *
     * @return void
     * @author
     **/
    function ajax_paydetail()
    {
        $html = '';
        $this->paydetail($_POST['order_id']);
        $html = $this->fetch('admin/return_product/rchange/paydetail.html');
        echo $html;exit;
    }

    function paydetail($order_id){
        $Orefunds = &app::get('ome')->model('refunds');
        $Opayments = &app::get('ome')->model('payments');
        $refunds = $Orefunds->getList('t_ready,refund_bn,money,paymethod,refund_refer',array('order_id'=>$order_id));
        $payments = $Opayments->getList('t_begin,payment_bn,money,paymethod,payment_refer',array('order_id'=>$order_id));
        $this->pagedata['payments'] = $payments;
        $this->pagedata['refunds'] = $refunds;
    }
    /**
     * 退换货单审核
     *
     * @return void
     * @author
     **/
    function check($reship_id){
       $obj_return_process = &app::get('ome')->model('return_process');
       $por_id = $obj_return_process->getList('por_id',array('reship_id'=>$reship_id));
       $this->pagedata['por_id'] = $por_id[0]['por_id'];
       $this->pagedata['act'] = 'save_check';
       $this->common_html(__FUNCTION__,$reship_id);
    }

    /**
     * 保存审核信息
     *
     * @return void
     * @author
     **/
    function save_check($reship_id,$status,$is_anti = false)
    {
       $this->begin();
       if($reship_id){
            $Oreship = &app::get('ome')->model('reship');
            $reship = $Oreship->dump(array('reship_id'=>$reship_id),'is_check,reship_bn,return_type,reason,need_sv');
            if($reship['is_check'] == '1' && !$is_anti){
                $this->end(false,'改单据已审核过!');
            }
            if($status == '1' && $reship['return_type'] == 'change'){
                $oReship_item = &app::get('ome')->model('reship_items');
                $oReship_item->Get_items_count($reship_id,$result);
                if($result['return'] == '0'||$result['change'] == '0'){
                    $this->end(false,'由于提交信息有误，审核请求失败! 请确认后再提交。');
                }
                //生成一张虚拟的换货单，并锁定相应的商品库存
                /*=========*/

                /*=========*/
            }
            //$check_memo = '#审核原因#'.$_POST['reason'];
            $reason = unserialize($reship['reason']);
            if(isset($_POST['reason'])) {
                $reason['check'] = $_POST['reason'];
            }


            $updateData = array('is_check'=>$status,'reason'=>serialize($reason));
            if(isset($_POST['need_sv'])) {
                $updateData['need_sv'] = $_POST['need_sv'];
            }
            $Oreship->update($updateData,array('reship_id'=>$reship_id));
            $oOperation_log = &app::get('ome')->model('operation_log');
            $schema = &app::get('ome')->model('reship')->schema['columns'];
            $memo = '审核状态:'.$schema['is_check']['type'][$status];
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);

            if($reship['need_sv'] == 'false') {
                if($Oreship->finish_aftersale($reship_id)){
                    $result = kernel::single('ome_return_process')->do_iostock($_POST['por_id'],1,$msg);
                    if(!$result){
                        $this->end(false,'没有生成出入库明细!');
                    }
                }
            }
       }

       $this->end(true,'操作成功！');
    }

    /**
     * 退发货单编辑
     *
     * @return void
     * @author
     **/
    function edit($reship_id)
    {
        $return_type = array('return'=>'退货','change'=>'换货');
        $this->pagedata['return_type'] = $return_type;
        $this->pagedata['order_filter'] = array('pay_status'=>'1','ship_status'=>'1');
        $Oreship = &app::get('ome')->model('reship');
        $oOrder_pmt = &app::get('ome')->model('order_pmt');
        $reship_data = $Oreship->getCheckinfo($reship_id,false);
        $this->paydetail($reship_data['order_id']);
        $oProblem = &app::get('ome')->model('return_product_problem');
        $list = $oProblem->getList('problem_id,problem_name');
        $this->pagedata['problem_type'] = $list;
        $reship_data['return_type_name'] = $return_type[$reship_data['return_type']];
        $this->pagedata['reship_data'] = $reship_data;
        $pmts = $oOrder_pmt->getList('pmt_amount,pmt_describe',array('order_id'=>$reship_data['order_id']));
        $this->pagedata['pmts'] = $pmts;
        $this->pagedata['total_return_filter'] = $reship_data['total_return_filter'];
        $this->pagedata['total_change_filter'] = $reship_data['total_change_filter'];

        # 计算差价
        $this->pagedata['diffmoney'] = $reship_data['diff_money'];

        # 订单的支付明细
        $oPayments = &app::get('ome')->model('payments');
        $this->pagedata['payments'] = $oPayments->getList('payment_id,payment_bn,t_begin,download_time,money,paymethod',array('order_id'=>$reship_data['order_id']));

        # 订单的退款明细
        $oRefunds = &app::get('ome')->model('refunds');
        $this->pagedata['refunds'] = $oRefunds->getList('refund_bn,t_ready,download_time,money,paymethod',array('order_id'=>$reship_data['order_id']));

        $this->display('admin/return_product/rchange/rchange.html');
    }

    /**
     * 反审核
     *
     * @return void
     * @author
     **/
    function anti_check($reship_id)
    {
       if(!$reship_id)
        die("单据号传递错误！");
       $Oreship = &app::get('ome')->model('reship');
       $reship = $Oreship->dump(array('reship_id'=>$reship_id),'reship_bn');
       $this->pagedata['reship_id'] = $reship_id;
       $this->pagedata['reship_bn'] = $reship['reship_bn'];
       $oOperation_log = &app::get('ome')->model('operation_log');
       $memo = '进行反审核';
       $oOperation_log->write_log('reship@ome',$reship_id,$memo);
       $this->display('admin/return_product/rchange/anti_check.html');
    }

    /**
     * 入库单
     *
     * @return void
     * @author
     **/
    function ruku($reship_id){
        $this->pagedata['act'] = 'save_ruku';
        $this->pagedata['ruku_html'] = true;
        $Oreship = &app::get('ome')->model('reship');
        $reships = $Oreship->dump(array('reship_id'=>$reship_id),'order_id');
        $this->paydetail($reships['order_id']);
        $this->common_html(__FUNCTION__,$reship_id);
    }

    /**
     * 保存入库单信息
     * status 状态：
     *       5: 拒绝 生成一张发货单 商品明细为退入商品中的商品信息
     *       6：补差价，生成一张未付款的支付单
     *       8: 完成
     * @return void
     * @author
     **/
    function save_ruku($reship_id,$status){
        if(!$reship_id)
            die('单据号传递错误!');
        $this->begin();
        $data = kernel::single('base_component_request')->get_params(true);

        $Oreship = &app::get('ome')->model('reship');
        $Oreship->saveinfo($reship_id,$data,$status);
        $this->end(true,'操作成功！');
    }

    /**
     * 保存验收退换货单状态
     *
     * @return void
     * @author
     **/
    function save_accept_returned($reship_id,$status){
       $this->begin();
       if($reship_id){
            $oOperation_log = &app::get('ome')->model('operation_log');
            $Oreship = &app::get('ome')->model('reship');
            $oProduct_pro = &app::get('ome')->model('return_process');
            $oProduct_pro_detail = $oProduct_pro->product_detail($reship_id);
            $reship = $Oreship->dump(array('reship_id'=>$reship_id),'is_check,return_id,reason');
            if($reship['is_check'] == '3'){
                $this->end(false,'改单据已验收过!');
            }

            //增加售后收货前的扩展
            foreach(kernel::servicelist('ome.aftersale') as $o){
                if(method_exists($o,'pre_sv_charge')){
                    if(!$o->pre_sv_charge($_POST,$memo)){
                        $this->end(false, app::get('base')->_($memo));
                    }
                }
            }

            $data['branch_name'] = $oProduct_pro_detail['branch_name'];
            $data['memo'] = $_POST['info']['memo'];
            $data['shipcompany'] = $_POST['info']['shipcompany'];
            $data['shiplogino'] = $_POST['info']['shiplogino'];
            $data['shipmoney'] = $_POST['info']['shipmoney'];
            $data['shipdaofu'] = $_POST['info']['daofu'] == 1 ? 1 : 0;
            $data['shiptime'] = time();



            if($status == '4'){
                $addmemo = ',拒绝收货';
                $refuse_memo = unserialize($reship['reason']);
                $refuse_memo .= '#收货原因#'.$_POST['info']['refuse_memo'];
                $prodata = array('reship_id'=>$reship_id,'reason'=>serialize($refuse_memo));
                $oProduct_pro->cancel_process($prodata);
            }elseif($status == '3'){
                $prodata = array('reship_id'=>$reship_id,'process_data'=>serialize($data));
                $addmemo = ',收货成功';
                $oProduct_pro->save_return_process($prodata);
            }
            $filter = array(
                'is_check'=>$status,
                'return_logi_name'=>$data['shipcompany'],
                'return_logi_no'=>$data['shiplogino'],
            );
            $Oreship->update($filter,array('reship_id'=>$reship_id));

            if($reship['return_id']){
                $Oproduct = &app::get('ome')->model('return_product');
                $recieved = 'false';
                if($status == '3'){
                   $recieved = 'true';
                }
                $Oproduct->update(array('process_data'=>serialize($data),'recieved'=>$recieved),array('return_id'=>$reship['return_id']));
            }


            $Oreship_items = &app::get('ome')->model('reship_items');
            $oBranch = &app::get('ome')->model('branch');
            $reship_items = $Oreship_items->getList('branch_id',array('reship_id'=>$reship_id,'return_type'=>'return'));
            $branch_name = array();
            foreach($reship_items as $k=>$v){
                $branch_name[] = $oBranch->Get_name($v['branch_id']);
            }
            $add_name = array_unique($branch_name);
            $memo='仓库:'.implode(',', $add_name).$addmemo;
            $oOperation_log = &app::get('ome')->model('operation_log');
            if($reship['return_id']){
                $oOperation_log->write_log('return@ome',$reship['return_id'],$memo);
            }
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);

           if($oProduct_pro_detail['return_id']){
               //售后申请状态更新
                foreach(kernel::servicelist('service.aftersale') as $object=>$instance){
                    if(method_exists($instance,'update_status')){
                        $instance->update_status($oProduct_pro_detail['return_id']);
                    }
                }
           }


           //增加售后收货前的扩展
            foreach(kernel::servicelist('ome.aftersale') as $o){
                if(method_exists($o,'after_sv_charge')){
                    $o->after_sv_charge($_POST);
                }
            }
       }

       $this->end(true,'操作成功！');
    }


    /**
     * 收货拒绝理由
     *
     * @return void
     * @author
     **/
    function refuse_reason($reship_id,$status,$type){
        if($type == 'returned'){
           $refuse_memo = '收货拒绝';
           $ctl = 'admin_return_rchange';
           $act = 'save_accept_returned';
        }else{
           $refuse_memo = '质检拒绝';
           $ctl = 'admin_return_sv';
           $act = 'tosave';
        }
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->pagedata['refuse_memo'] = $refuse_memo;
        $this->pagedata['from_type'] = $_GET['from_type'];
        $this->pagedata['ctl'] = $ctl;
        $this->pagedata['act'] = $act;
        $this->pagedata['reship_id'] = $reship_id;
        $this->pagedata['status'] = $status;
        $this->display('admin/return_product/rchange/refuse_reason.html');
    }

    /**
     * 验收退换货单
     *
     * @return void
     * @author
     **/
    function accept_returned($reship_id){
        $this->pagedata['act'] = 'save_accept_returned';
        $this->common_html(__FUNCTION__,$reship_id);
    }

    /**
     * 验收退换货和审核公共页面
     *
     * @return void
     * @author
     **/
    function common_html($display_button = 'check',$reship_id){
        if(!$reship_id) die('单据号传递错误!');
        $Oreship = &app::get('ome')->model('reship');
        $reship_data = $Oreship->getCheckinfo($reship_id);
        $reship_data['reason'] = unserialize($reship_data['reason']);
        $oProblem = &app::get('ome')->model('return_product_problem');
        $list = $oProblem->dump(array('problem_id'=>$reship_data['problem_id']),'problem_name');

        # 支付单
        $this->pagedata['payments'] = &app::get('ome')->model('payments')->getList('*',array('order_id'=>$reship_data['order_id']));

        # 退款单
        $this->pagedata['refunds'] = &app::get('ome')->model('refunds')->getList('*',array('order_id'=>$reship_data['order_id']));

        $reship_data['problem_type'] = $list['problem_name'];
        $this->pagedata['reship_data'] = $reship_data;
        $this->pagedata['display_button'] = $display_button;

        $this->display('admin/return_product/rchange/check.html');
    }

    function getProducts(){

        $pro_id = $_POST['product_id'];
        $type = $_GET['type'];
        if (is_array($pro_id)){
            $filter['product_id'] = $pro_id;
        }
        $Obranch = &app::get('ome')->model('branch');
        $pObj = &app::get('ome')->model('products');
        $pObj->filter_use_like = true;
        $data = $pObj->getList('visibility,product_id,bn,name,price,store,store_freeze',$filter,0,-1);
        $pObj->filter_use_like = false;
        $branchlist = $Obranch->Get_branchlist();
        if (!empty($data)){
            foreach ($data as $k => $item){
                $item['num'] = 1;

                //$item['price'] = app::get('purchase')->model('po')->getPurchsePrice($item['product_id'], 'asc');
                if (!$item['price']){
                    $item['price'] = 0;
                }
                $branch_info = '';
                foreach ($branchlist as $k => $v) {
                    if($v['is_deliv_branch'] == 'false' && $type == 'change') continue;

                    $checked = '';
                    if($k==0){
                       $checked = 'checked="checked"';
                       $item['sale_store'] = $pObj->get_product_store($v['branch_id'],$item['product_id']);
                    }
                    $branch_info .= '<input type="radio" '.$checked.' bn="'.$item['bn'].'" onclick="choose_branch(this,'.$v['branch_id'].','.$item['product_id'].');" name="'.$type.'[product][branch_id]['.$item['bn'].']" value="'.$v['branch_id'].'">'.$v['name'].'&nbsp;';
                }
                $item['branch_info'] = $branch_info;
                $item['type'] = $type;

                $rows[] = $item;
            }
        }
        echo "window.autocompleter_json=".json_encode($rows);exit;
    }

    /**
     * 构造一个商品列表
     *
     * @return void
     * @author
     **/
    function getGoods($product_id)
    {
        $base_filter['visibility'] = 'true';
        $base_filter['product_id|notin'] = explode(',',$product_id);

        $params = array(
           'title'=>'商品列表',
           'base_filter' => $base_filter,
           'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_setcol'=>true,
            'use_buildin_refresh'=>true,
            'orderBy' =>'product_id DESC',
            'alertpage_finder'=>true,
            'use_view_tab' => false,
        );
        $this->finder('ome_mdl_products',$params);
    }

    /**
     * 取消退换货单
     *
     * @return void
     * @author
     **/
    function do_cancel($reship_id)
    {
        if(!$reship_id)
            die("单据号传递错误！");
        $Oreship = app::get('ome')->model('reship');
        $reship = $Oreship->dump(array('reship_id'=>$reship_id),'reship_bn,return_id,return_type');
        if($_POST){
            $reship_id = $_POST['reship_id'];

            $Oreship->update(array('is_check'=>'5'),array('reship_id'=>$reship_id));

            $memo = '状态:拒绝';
            $oOperation_log = app::get('ome')->model('operation_log');//写日志
            if($reship['return_id']){
                $oOperation_log->write_log('return@ome',$reship['return_id'],$memo);
                $data = array ('return_id' => $reship['return_id'], 'status' => '5', 'last_modified' => time () );
                $oProduct = app::get('ome')->model ( 'return_product' );
                $oProduct->update_status ( $data );
            }
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);
        }
        $this->pagedata['reship_bn'] = $reship['reship_bn'];
        $this->pagedata['reship_id'] = $reship_id;
        $this->display('admin/return_product/rchange/do_cancel.html');

    }

    /**
     * 打回发货单方法
     *
     * @return void
     * @author
     **/
    function do_back($reship_id)
    {
        if(!$reship_id)
            die("单据号传递错误！");
        $Oreship = &app::get('ome')->model('reship');
        $Oreturn_products = &app::get('ome')->model('return_product');
        if($_POST){
            $reship_id = $_POST['reship_id'];
            $memo = '';
            $delivery_num=0;//发货单数量
            $oReship = &app::get('ome')->model('reship');
            $oDelivery = &app::get('ome')->model('delivery');
            $oDelivery_order= &app::get('ome')->model('delivery_order');
            $oDelivery_items = &app::get('ome')->model('delivery_items');
            $oOrder_items = &app::get('ome')->model('order_items');
            $reshipinfo = $oReship->dump(array('reship_id'=>$reship_id));
            $order_items = $oOrder_items->getList('*',array('order_id'=>$reshipinfo['order_id']));
            $order_id = $reshipinfo['order_id'];
            $delivery_order = $oDelivery_order->dump(array('order_id'=>$order_id),'delivery_id');
            $delivery_id = $delivery_order['delivery_id'];
            $delivery_items = &app::get('ome')->model('reship_items')->getList('product_id,bn,product_name,num as number',array('reship_id'=>$reship_id,'return_type'=>'return'));

            $deliveryinfo = $oDelivery->dump($delivery_id,'branch_id,delivery_id,logi_id,delivery_cost_actual');
            $Process_data = array_merge($deliveryinfo,$reshipinfo);
            $Process_data['logi_id'] = $reshipinfo['logi_id']!='' ? $reshipinfo['logi_id'] :$deliveryinfo['logi_id'];
            $Process_data['logi_name'] = $reshipinfo['logi_name']!='' ? $reshipinfo['logi_name'] :$deliveryinfo['logi_name'];
            $Process_data['delivery_items'] = $delivery_items;

            define('FRST_TRIGGER_OBJECT_TYPE','发货单：售后申请原样寄回生成发货单');
            define('FRST_TRIGGER_ACTION_TYPE','ome_mdl_return_product：saveinfo');

            $new_delivery_bn = $oReship->create_delivery($Process_data);
            $delivery_memo = '，发货单号为:'.$new_delivery_bn;
            $delivery_num = 1;
            $oReship->update(array('is_check'=>'5'),array('reship_id'=>$reship_id));
            if($reshipinfo['return_id']){
                $Oreturn_products->update(array('status'=>'5'),array('return_id'=>$reshipinfo['return_id']));
            }

            if($delivery_num!=0){
                $memo.='   生成了'.$delivery_num.'张发货单'.$delivery_memo;
            }

            //售后原样寄回埋点
            $new_delivery_info = $oDelivery->dump(array('delivery_bn'=>$new_delivery_bn),'delivery_id');
            $original_data = kernel::single('ome_event_data_delivery')->generate($new_delivery_info['delivery_id']);
            $wms_id = kernel::single('ome_branch')->getWmsIdById($original_data['branch_id']);
            kernel::single('ome_event_trigger_delivery')->create($wms_id, $original_data, true);

            $oOperation_log = &app::get('ome')->model('operation_log');//写日志
            if($reshipinfo['return_id']){
               $oOperation_log->write_log('return@ome',$reshipinfo['return_id'],$memo);
            }
            $oOperation_log->write_log('reship@ome',$reship_id,$memo);
            echo '生成退回发货单成功!';exit;
        }
        $reship = $Oreship->dump(array('reship_id'=>$reship_id),'reship_bn');
        $this->pagedata['reship_bn'] = $reship['reship_bn'];
        $this->pagedata['reship_id'] = $reship_id;
        $this->display('admin/return_product/rchange/do_back.html');

    }

    /**
     * 根据仓库ID和货品ID 获取相应的库存数量
     *
     * @return void
     * @author
     **/
    function ajax_showStore()
    {
        $branch_id = $_POST['branch_id'];
        $product_id = $_POST['product_id'];
        $result = array('res'=>'fail','msg'=>'product_id is empty');
        if($product_id && $branch_id){
            $Oproduct = &app::get('ome')->model('products');
            $store = $Oproduct->get_product_store($branch_id,$product_id);
            $result = array('res'=>'succ','msg'=>$store);
        }

        echo json_encode($result);exit;
    }

    /**
     * 根据订单ID 获取相应的优惠方案信息
     *
     * @return void
     * @author
     **/
    function ajax_getPmts(){

        $order_id = $_POST['order_id'];
        if($order_id){
            $oOrder_pmt = &app::get('ome')->model('order_pmt');
            $pmts = $oOrder_pmt->getList('pmt_amount,pmt_describe',array('order_id'=>$order_id));
            $this->pagedata['pmts'] = $pmts;
            $html = $this->fetch('admin/return_product/rchange/show_pmt.html');
            echo $html;exit;
        }
    }


    /**
     * 计算补差价金额
     *
     * @return void
     * @author chenping<chenping@shopex.cn>
     **/
    public function calDiffAmount($reship_id,$return_type)
    {
        $post = kernel::single('base_component_request')->get_post();
        # 进行数量判断
        if (isset($post['return']['goods_bn']) && is_array($post['return']['goods_bn'])) {
            foreach ($post['return']['goods_bn'] as $pbn) {
                if ($post['return']['num'][$pbn] > $post['return']['effective'][$pbn]) {
                    $error = array(
                        'error' => "货品【{$pbn}】的申请数量大于可退入数量!",
                    );
                    break;
                }
            }
            if ($error) {
                echo json_encode($error);exit;
            }
        }

        $money = kernel::single('ome_return_rchange')->calDiffAmount($post);
        $moneyValue = $money;
        $curModel = app::get('eccommon')->model('currency');
        foreach ($money as &$value) {
            $value = $curModel->changer($value);
        }
        $money['mvalue'] = $moneyValue;

        # 判断退款金额是否大于订单金额
        if ($post['order_id']) {
            $orderInfo = &app::get('ome')->model('orders')->getList('total_amount,payed',array('order_id'=>$post['order_id']),0,1);
            if ($moneyValue['totalmoney']>$orderInfo[0]['payed']) {
                $error = array(
                    'error' => "退款金额不能大于订单的已支付金额!",
                );
                echo json_encode($error);exit;
            }

            if ($return_type == 'return' && $moneyValue['totalmoney']<0) {
                $error = array(
                    'error' => "退款金额不能小于零!",
                );
                echo json_encode($error);exit;
            }
        }


        echo json_encode($money);exit;

    }

    /**
     * 更新
     *
     * @return void
     * @author
     **/
    public function update_reship($reship_id)
    {
        $finder_id = $_GET['finder_id'];
        $this->begin();
        if (!$reship_id) {
            $this->end(false,app::get('ome')->_('退换货单不存在!'));
        }

        $post = kernel::single('base_component_request')->get_post();
        if (empty($post['return_logi_no'])) {
            $this->end(false,app::get('ome')->_('物流单号不能为空!'));
        }
        if (empty($post['return_logi_name'])) {
            $this->end(false,app::get('ome')->_('物流公司不能为空!'));
        }

        $reshipModel = &app::get('ome')->model('reship');
        $isExit = $reshipModel->getList('reship_id',array('return_logi_no'=>$post['return_logi_no'],'reship_id|noequal'=>$reship_id,'is_check|noequal'=>'5'));
        if ($isExit) {
            $this->end(false,app::get('ome')->_('物流单号已经存在!'));
        }

        $post = kernel::single('base_component_request')->get_post();
        $reshipUpdate = array(
            'return_logi_no' => $post['return_logi_no'],
            'return_logi_name' => $post['return_logi_name'],
        );
        $result = $reshipModel->update($reshipUpdate,array('reship_id'=>$reship_id));

        # 记LOG
        $corp = &app::get('ome')->model('dly_corp')->getList('name',array('corp_id'=>$post['return_logi_name']),0,1);
        $oOperation_log = &app::get('ome')->model('operation_log');
        $memo = '更改退回物流单号('.$post['return_logi_no'].'),退回物流公司('.$corp[0]['name'].')';
        $oOperation_log->write_log('reship@ome',$reship_id,$memo);

        $msg = $result ? app::get('ome')->_('更新成功!') : app::get('ome')->_('更新失败');
        $this->end($result,$msg);
    }

    /**
     * @description 选择补差价订单
     * @access public
     * @param Int $order_id 原订单ID
     * @param Int $page 页码
     * @return void
     */
    public function selectDiffOrder($order_id,$page=1)
    {
        if(empty($order_id)){
            $result = array(
                'error' => app::get('ome')->_('退货订单不存在!'),
            );
            echo '退货订单不存在!';exit;
        }

        $pagelimit = 20;

        $orderModel = &app::get('ome')->model('orders');
        $order = $orderModel->getList('member_id,total_amount,shop_id',array('order_id'=>$order_id),0,1);
        if (empty($order_id)) {
            $result = array(
                'error' => app::get('ome')->_('退货订单不存在!'),
            );
            echo '退货订单不存在!';exit;
        }

        # 查询该会员的所有订单
        $filter = array(
            'member_id' => $order[0]['member_id'],
            //'total_amount|lthan' => $order[0][''],
            'pay_status' => '1',
            'ship_status' => '0',
            'status'  => 'active',
            'process_status' => 'unconfirmed',
            'shop_id' => $order[0]['shop_id'],
            'order_id|noequal' => $order_id,
        );
        $diffOrders = $orderModel->getList('order_id,order_bn,total_amount,cost_freight,cost_protect,tostr,shop_id,member_id,createtime,pay_status,ship_status',$filter,($page-1)*$pagelimit,$pagelimit,'createtime desc');

        if ($diffOrders) {
            foreach ($diffOrders as $key=>$diffOrder) {
                $diffOrders[$key]['uname'] = &$members[$diffOrder['member_id']];
                $diffOrders[$key]['shop_name'] = &$shops[$diffOrder['shop_id']];
                $diffOrders[$key]['pay_status'] = $orderModel->schema['columns']['pay_status']['type'][$diffOrder['pay_status']];
                $diffOrders[$key]['ship_status'] = $orderModel->schema['columns']['ship_status']['type'][$diffOrder['ship_status']];

                $member_ids[] = $diffOrder['member_id'];
                $shop_ids[] = $diffOrder['shop_id'];
            }

            $memberModel = &app::get('ome')->model('members');
            $memberList = $memberModel->getList('member_id,uname,name',array('member_id'=>$member_ids));
            foreach ($memberList as $key=>$member) {
                $members[$member['member_id']] = $member['uname'];
            }

            $shopModel = &app::get('ome')->model('shop');
            $shopList = $shopModel->getList('shop_id,name',array('shop_id' => $shop_ids));
            foreach ($shopList as $key=>$shop) {
                $shops[$shop['shop_id']] = $shop['name'];
            }

            $this->pagedata['diffOrders'] = $diffOrders;

            $count = $orderModel->count($filter);

            $totalpage = ceil($count/$pagelimit);
            $pager = $this->ui()->pager(array(
                'current'=>$page,
                'total'=>$totalpage,
                'link'=>'javascript:gotopage(%d);',
            ));
            $this->pagedata['pager'] = $pager;
        }

        $this->pagedata['order_id'] = $order_id;

        if ($page==1) {
            $view = 'admin/return_product/rchange/diff_orders.html';
        }else{
            $view = 'admin/return_product/rchange/diff_orders_container.html';
        }
        $this->display($view);
    }

    /**
     * @description 补差价订单选定确认
     * @access public
     * @param void
     * @return void
     */
    public function diffOrderSelected()
    {

        $post = kernel::single('base_component_request')->get_post();

        $order = kernel::single('ome_return_rchange')->diffOrderValidate($post,$errormsg);

        if ($order === false) {
            $this->pagedata['errormsg'] = $errormsg;
        }

        $this->pagedata['order'] = $order;

        $this->display('admin/return_product/rchange/diff_orders_confirm.html');
    }

    /**
     * @description 质检异常处理页
     * @access public
     * @param void
     * @return void
     */
    public function processException($reship_id)
    {
        $reshipModel = &app::get('ome')->model('reship');
        $reason = $reshipModel->select()->columns('reason')->where('reship_id=?',$reship_id)->instance()->fetch_one();
        $reason = unserialize($reason);
        $this->pagedata['reason'] = $reason['sv'];
        $this->pagedata['reship_id'] = $reship_id;
        $this->display('admin/return_product/rchange/process_exception.html');
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function doException()
    {
        $this->begin();
        $reship_id = $_POST['reship_id'];
        if (!$reship_id) {
            $this->end(falseapp::get('ome')->_('退换货单据不存在!'));
        }

        $op_info = kernel::single('ome_func')->getDesktopUser();

        $reshipModel = &app::get('ome')->model('reship');
        $row = $reshipModel->select()->columns('reason,is_check')->where('reship_id=?',$reship_id)->instance()->fetch_row();

        if ($row['is_check'] == '10') {
            $this->end(false,'对不起！该单据已经置为异常!');
        }

        $reason = $row['reason'];

        $reason = (array)unserialize($reason);

        $reason['sv'][] = array(
            'op_id' => $op_info['op_id'],
            'op_name' => $op_info['op_name'],
            'reason' => $_POST['reason'],
            'createtime' => time(),
        );

        $updateData = array(
            'reason' => serialize($reason),
            'is_check' => '10',
        );

        $reshipModel->update($updateData,array('reship_id'=>$reship_id));

        # 写日志
        app::get('ome')->model('operation_log')->write_log('reship@ome',$reship_id,'质检异常，重新审核');

        $this->end(true);
    }

	public function uplode_qa($reship_id){
		$this->pagedata['reship_id'] = $reship_id;
		$this->pagedata['finder_id'] = $_GET['finder_id'];

		$objReship = app::get('ome')->model('reship');
		$info = $objReship->dump($reship_id,'qa_memo,image2,image1,image3');
		//echo "<pre>";print_r($info);exit;
		$this->pagedata['info'] = $info;
		$this->display('admin/return_product/rchange/uplode_qa.html');
	}

	public function check_qa($reship_id){
		$this->pagedata['reship_id'] = $reship_id;
		$this->pagedata['finder_id'] = $_GET['finder_id'];

		$objReship = app::get('ome')->model('reship');
		$info = $objReship->dump($reship_id,'qa_memo,image2,image1,image3');
		//echo "<pre>";print_r($info);exit;
		$this->pagedata['info'] = $info;
		$this->display('admin/return_product/rchange/check_qa.html');
	}

	public function do_upload_qa(){
		//$this->begin('index.php?app=wms&ctl=admin_return_rchange&act=index&flt=process_list');
		$objReship = app::get('ome')->model('reship');
		$data=$_POST;
		$sign = $objReship->save($data);
		 echo "<script>$$('.dialog').getLast().retrieve('instance').close();</script>";
		//$this->end(true);
		
	}

}