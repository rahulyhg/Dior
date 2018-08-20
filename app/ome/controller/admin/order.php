
<?php

class ome_ctl_admin_order extends desktop_controller{

    var $name = "订单中心";
    var $workground = "order_center";
    var $order_type = 'all';

    function _views(){
        if($this->order_type == 'abnormal'){
           $sub_menu = $this->_viewsAbnormal();
        }elseif($this->order_type == 'assigned'){
           $sub_menu = $this->_views_assigned();
        }elseif($this->order_type == 'notassigned'){
           $sub_menu = $this->_views_notassigned();
        }elseif($this->order_type == 'unmyown'){
           $sub_menu = $this->_views_unmyown();
        }elseif($this->order_type == 'myown'){
           $sub_menu = $this->_views_myown();
        }elseif($this->order_type == 'ourgroup'){
           $sub_menu = $this->_views_ourgroup();
        } elseif ($this->order_type == 'buffer') {
            $sub_menu = $this->_view_buffer();
        }elseif ($this->order_type == 'active') {
            $sub_menu = $this->_view_active();
        }else{
           $sub_menu = $this->_viewsAll(); //去掉历史订单上面的tab
        }
        return $sub_menu;
    }

    function _viewsAll(){
        $mdl_order = $this->app->model('orders');
        $base_filter = array('disabled'=>'false','is_fail'=>'false');

		$start_time = strtotime(date('Y-m-d',time()));
		$end_time = $start_time+60*60*24;
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array('disabled'=>'false','is_fail'=>'false', 'process_status|noequal'=>'is_retrial'),'optional'=>false),
            1 => array('label'=>app::get('base')->_('无应答'),'filter'=>array('is_cod' => 'true','status' => 'active','process_status|noequal'=>'is_retrial','createtime|between'=>array($start_time,$end_time),'mark_text|has'=>'客户无应答'),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            //$v['filter']['archive'] = '0';

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }

    function _view_active(){
        $mdl_order = $this->app->model('orders');
        $base_filter = array('disabled'=>'false','is_fail'=>'false','archive'=>0, 'process_status|noequal'=>'is_declare');//跨境申报 ExBOY

		$start_time = strtotime(date('Y-m-d',time()));
		$end_time = $start_time+60*60*24;
		//echo "<pre>";print_r($start_time);
		///echo "<pre>";print_r($end_time);
		//echo "<pre>";print_r(date('Y-m-d H:i:s',$start_time));
		//echo "<pre>";print_r(date('Y-m-d H:i:s',$end_time));exit;
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'optional'=>false),
            1 => array('label'=>app::get('base')->_('货到付款'),'filter'=>array('is_cod'=>'true','status' => 'active', 'process_status|noequal'=>'is_retrial'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('待支付'),'filter'=>array('pay_status' => array('0','3'),'status' => 'active', 'process_status|noequal'=>'is_retrial'),'optional'=>false),
            3 => array('label'=>app::get('base')->_('已支付'),'filter'=>array('pay_status' => 1,'status' => 'active', 'process_status|noequal'=>'is_retrial'),'optional'=>false),
            4 => array(
                'label'=>app::get('base')->_('待处理'),
                'filter'=>array(
                    'abnormal'=>'false',
                    'order_confirm_filter'=>'group_id > 0',
                    'process_status'=>array('unconfirmed','confirmed','splitting'),
                    'status'=>'active'),
                'optional'=>false),
            5 => array(
                'label'=>app::get('base')->_('已处理'),
                'filter'=>array('abnormal'=>'false','order_confirm_filter'=>'group_id > 0','process_status'=>array('splited','remain_cancel'),'status' => 'active'),
                'optional'=>false),
            6 => array('label'=>app::get('base')->_('待发货'),'filter'=>array('ship_status' =>array('0','2'),'status' => 'active', 'process_status|noequal'=>'is_retrial'),'optional'=>false),
            7 => array('label'=>app::get('base')->_('已发货'),'filter'=>array('ship_status' =>'1','status' => 'active', 'process_status|noequal'=>'is_retrial'),'optional'=>false),
            8 => array('label'=>app::get('base')->_('取消'),'filter'=>array('process_status' => 'cancel'),'optional'=>false),
            9 => array('label'=>app::get('base')->_('暂停'),'filter'=>array('pause' => 'true'),'optional'=>false),
			10 => array('label'=>app::get('base')->_('无应答'),'filter'=>array('is_cod' => 'true','status' => 'active','process_status|noequal'=>'is_retrial','createtime|between'=>array($start_time,$end_time),'mark_text|has'=>'客户无应答'),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            //$v['filter']['archive'] = '0';

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
		//echo "<pre>";print_r($sub_menu);exit;
        return $sub_menu;
    }

    /*
     * 已分派订单标签
     */

    function _views_assigned(){
        $mdl_order = $this->app->model('orders');
        $base_filter = array(
            'assigned' => 'assigned',
            'abnormal'=>'false',
            'is_fail'=>'false',
            'process_status|noequal'=>'cancel',
            'is_auto' => 'false',
        );
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'optional'=>false),
        );
        $groupsObj = &$this->app->model("groups");
        $groups = $groupsObj->getList('*');
        foreach($groups as $group){
            $sub_menu[] = array(
                'label'=>$group['name'],
                'filter'=>array('group_id'=>$group['group_id']),
                'optional'=>false
            );
        }
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            $v['filter']['archive'] = '0';

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&flt=assigned&view='.$i++;
        }
        return $sub_menu;
    }

    /*
     * 未分派订单标签
     */

    function _views_notassigned(){
        $mdl_order = $this->app->model('orders');
        $base_filter = array(
            'assigned' => 'notassigned',
            'abnormal'=>'false',

            'is_fail'=>'false',
            'ship_status'=>array('0', '2'),//部分发货也显示  ExBOY
            'process_status|noequal'=>'cancel',
            'is_auto' => 'false',
        );
        $sub_menu[0] = array('label' => app::get('base')->_('全部'), 'filter' => $base_filter, 'optional' => false);
        $filterAttr = kernel::single('omeauto_auto_combine')->getErrorFlags();
        foreach ($filterAttr as $code => $tilte) {
            $filter = $base_filter;
            if (!empty($filter['order_confirm_filter']))
                $filter['order_confirm_filter'] .= " AND (auto_status & {$code} = {$code}) ";
            else
                $filter['order_confirm_filter'] = "(auto_status & {$code} = {$code})";
            $sub_menu[$code] = array('label' => app::get('base')->_($tilte), 'filter' => $filter, 'optional ' => false);
        }
        $sub_menu['989898'] = array('label' => app::get('base')->_('货到付款'), 'filter' => array_merge(array('is_cod' => 'true', 'status' => 'active'), $base_filter), 'optional' => false);

        //ExBOY加入Tab栏目
        $sub_menu['989900'] = array('label' => app::get('base')->_('价格异常待处理订单'), 'filter' => array_merge(array('process_status' => 'is_retrial'), $base_filter), 'optional' => false);

        foreach ($sub_menu as $k => $v) {

            $v['filter']['archive'] = '0';

            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&flt=notassigned&view=' . $k;
        }

        return $sub_menu;
    }

    /**
     * 缓存区订单标签
     */
    function _view_buffer() {
		$start_time = strtotime(date('Y-m-d',time()));
		$end_time = $start_time+60*60*24;
        $mdl_order = $this->app->model('orders');
        $base_filter = array(
            'assigned' => 'buffer',//ExBOY加入SQL判断
            'abnormal' => 'false',
            'ship_status' => '0',
            'is_fail' => 'false',
            'process_status' => array('unconfirmed','is_retrial'),//ExBOY加入SQL判断
            'status' => 'active',
            'is_auto' => 'false',
            'order_confirm_filter' => '( op_id IS NULL AND group_id IS NULL)');

        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'optional'=>false),
            1 => array('label'=>app::get('base')->_('货到付款'),'filter'=>array('is_cod'=>'true'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('待支付'),'filter'=>array('pay_status' => array('0','3')),'optional'=>false),
            3 => array('label'=>app::get('base')->_('已支付'),'filter'=>array('pay_status' => 1),'optional'=>false),
			4 => array('label'=>app::get('base')->_('无应答'),'filter'=>array('is_cod'=>'true','createtime|between'=>array($start_time,$end_time),'mark_text|has'=>'客户无应答'),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            $v['filter']['archive'] = '0';

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&flt=buffer&view=' . $i++;
        }
        return $sub_menu;
    }

    /*
     * 待处理订单标签
     */

    function _views_unmyown(){
        $mdl_order = $this->app->model('orders');
        $base_filter = array(
            'assigned'=>'assigned',
            'abnormal'=>'false',
            'is_fail' => 'false',
            'status' => 'active',
        );
        $base_filter['process_status'] = array('unconfirmed', 'confirmed', 'splitting');

        $base_filter['op_id'] = kernel::single('desktop_user')->get_id();
        // 超级管理员
        if(kernel::single('desktop_user')->is_super()){
            if(isset($base_filter['op_id'])){
                unset($base_filter['op_id']);
            }

            if (isset($base_filter['group_id'])) {
                unset($base_filter['group_id']);
            }
        }

        $sub_menu = array(
            0 => array('label' => app::get('base')->_('全部'), 'filter' => $base_filter, 'optional' => false),
        );
        $base_filter['pause'] = 'false';
        $filterAttr = kernel::single('omeauto_auto_combine')->getErrorFlags();
        foreach ($filterAttr as $code => $tilte) {
            $filter = $base_filter;
            $filter['is_cod'] = 'false';
            if (!empty($filter['order_confirm_filter']))
                $filter['order_confirm_filter'] .= " AND (auto_status & {$code} = {$code}) ";
            else
                $filter['order_confirm_filter'] = "(auto_status & {$code} = {$code})";
            $sub_menu[$code] = array('label' => app::get('base')->_($tilte), 'filter' => $filter, 'optional ' => false);
        }
        $sub_menu['989898'] = array('label' => app::get('base')->_('货到付款'), 'filter' => array_merge(array('is_cod' => 'true'), $base_filter), 'optional' => false);
        $sub_menu['989899'] = array('label' => app::get('base')->_('暂停'), 'filter' => array_merge($base_filter, array('pause' => 'true')), 'optional' => false);

        foreach($sub_menu as $k=>$v){
            //if (!IS_NULL($v['filter'])) {
            //    $v['filter'] = array_merge($v['filter'], $base_filter);
            //}
            $v['filter']['archive'] = '0';
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;

            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&flt=unmyown&view=' . $k;
        }
        return $sub_menu;
    }

    /*
     * 已处理订单标签
     */

    function _views_myown(){
        $mdl_order = $this->app->model('orders');
        $base_filter = array(
            'assigned'=>'assigned',
            'abnormal'=>'false',
            'is_fail'=>'false',);

        $base_filter['op_id'] = kernel::single('desktop_user')->get_id();
        // 超级管理员
        if(kernel::single('desktop_user')->is_super()){
            if(isset($base_filter['op_id'])){
                unset($base_filter['op_id']);
            }
        }
//        $base_filter['order_confirm_filter'] = "(is_cod='true' OR pay_status in ('1','4','5'))";

        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array('process_status'=>array('splited','remain_cancel','cancel')),'optional'=>false),
            1 => array('label'=>app::get('base')->_('余单撤销'),'filter'=>array('process_status' =>'remain_cancel'),'optional'=>false),
            2 => array(
                'label'=>app::get('base')->_('部分发货'),
                'filter'=>array('ship_status' =>'2','process_status'=>'splited'),
                'optional'=>false),
            3 => array('label'=>app::get('base')->_('已发货'),'filter'=>array('ship_status' =>'1','process_status'=>'splited'),'optional'=>false),
            4 => array('label'=>app::get('base')->_('部分退货'),'filter'=>array('ship_status' =>'3','process_status'=>'splited'),'optional'=>false),
            5 => array(
                'label'=>app::get('base')->_('已退货'),
                'filter'=>array('ship_status' =>'4','process_status'=>'splited'),
                'optional'=>false),
            6 => array(
                'label'=>app::get('base')->_('暂停'),
                'filter'=>array('pause' => 'true','process_status'=>'splited'),
                'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }

            $v['filter']['archive'] = '0';

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&flt=myown&view='.$i++;
        }
        return $sub_menu;
    }

    /*
     * 本组订单标签
     */

    function _views_ourgroup(){
        $mdl_order = $this->app->model('orders');
        $base_filter = array(
            'assigned'=>'assigned',
            'abnormal'=>'false',
            'is_fail'=>'false',
            'process_status' => array('unconfirmed', 'confirmed', 'splitting', 'splited', 'remain_cancel'),
        );

        $groupObj = &$this->app->model("groups");
        $group_id = "";
        $op_id = kernel::single('desktop_user')->get_id();
        $op_group = $groupObj->get_group($op_id);
        if($op_group && is_array($op_group)){
            foreach($op_group as $v){
                $group_id[] = $v['group_id'];
            }
        }
        $base_filter['group_id'] = $group_id;
        // 超级管理员
        if(kernel::single('desktop_user')->is_super()){
            if(isset($base_filter['group_id'])){
                unset($base_filter['group_id']);
            }
        }

        $sub_menu = array(
            0 => array(
                'label'=>app::get('base')->_('全部'),
                'filter'=>array(),
                'optional'=>false
            ),
            1 => array(
                'label'=>app::get('base')->_('待认领'),
                'filter' => array('order_confirm_filter' => '(op_id is null OR op_id=0)'),
                'optional'=>false
            ),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($base_filter,$v['filter']);
            }

            $v['filter']['archive'] = '0';

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&flt=ourgroup&view='.$i++;
        }
        return $sub_menu;
    }

    function _viewsAbnormal(){
        $mdl_order = $this->app->model('orders');
        $abnormal_type_list = app::get('ome')->model('abnormal_type')->getList('*',array('disabled'=>'false', 'type_id|noequal'=>998));//跨境申报 ExBOY

        $sub_menu = array();
        $sub_menu[] = array('label' => app::get('base')->_('全部'), 'filter' => array('abnormal' => 'true', 'is_fail' => 'false', 'process_status|noequal'=>'is_retrial', 'order_confirm_filter' => '( op_id IS NOT NULL OR group_id IS NOT NULL)'), 'optional' => false);
        foreach($abnormal_type_list as $abnormal_type){
            $sub_menu[] = array('label'=>$abnormal_type['type_name'],'filter'=>array('abnormal_type_id'=>$abnormal_type['type_id'],'abnormal'=>'true','is_fail'=>'false'),'optional'=>false);
        }

        foreach($sub_menu as $k=>$v){

            $v['filter']['archive'] = '0';

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->countAbnormal($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_order&act=abnormal&view='.$k;
        }
        return $sub_menu;
    }

    function index(){

        //base_certificate::register();
        $op_id = kernel::single('desktop_user')->get_id();
        $this->title = '订单查看';

        //$base_filter = array('disabled' => 'false', 'is_fail' => 'false', 'order_confirm_filter' => '( op_id IS NOT NULL OR group_id IS NOT NULL)');
        //$base_filter = array('disabled'=>'false','is_fail'=>'false');
        $base_filter = array('disabled'=>'false', 'process_status|noequal'=>'is_retrial', 'order_confirm_filter' => "(is_fail='false' OR (is_fail='true' AND status!='active'))");
        if($_GET['ship_status']&&$_GET['status']){
            $base_filter['ship_status'] = $_GET['ship_status'];
            $base_filter['status'] = $_GET['status'];
        }
        //$base_filter['archive'] ='0';

        $params = array(
            'title'=>$this->title,
            'actions' => array(
                    array('label'=>app::get('ome')->_('批量设置备注'),'submit'=>"index.php?app=ome&ctl=admin_order&act=BatchUpMemo",'target'=>'dialog::{width:690,height:200,title:\'批量设置备注\'}"'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'finder_aliasname' => 'order_view'.$op_id,
            'finder_cols' => 'order_bn,shop_id,total_amount,column_print_status,column_users_type,process_status,is_cod,pay_status,ship_status,payment,shipping,logi_id,logi_no,createtime,paytime,mark_type',
            'base_filter' => $base_filter,
       );

       $user = kernel::single('desktop_user');
       if($user->has_permission('order_export')){
               $params['use_buildin_export'] = true;
       }

       if ($servicelist = kernel::servicelist('ome.service.order.index.action_bar'))
        foreach ($servicelist as $object => $instance){
            if (method_exists($instance, 'getActionBar')){
                $actionBars = $instance->getActionBar();
                foreach($actionBars as $actionBar){
                    $params['actions'][] = $actionBar;
                }
            }
        }
       $this->finder('ome_mdl_orders',$params);
    }

    function active(){

        $op_id = kernel::single('desktop_user')->get_id();
        $this->title = '订单查看';
        $this->order_type = 'active';

        //$base_filter = array('disabled' => 'false', 'is_fail' => 'false', 'order_confirm_filter' => '( op_id IS NOT NULL OR group_id IS NOT NULL)');
        $base_filter = array('disabled'=>'false','is_fail'=>'false','archive'=>0,'filter_sql'=>"( process_status != 'cancel')");

        //$base_filter['archive'] ='0';

        $params = array(
            'title'=>$this->title,
            'actions' => array(
                    array('label'=>app::get('ome')->_('批量设置为跨境订单'),'submit'=>"index.php?app=ome&ctl=admin_order&act=BatchDeclare",'target'=>'dialog::{width:500,height:170,title:\'批量设置为跨境订单\'}"'),
                    array('label'=>app::get('ome')->_('批量设置备注'),'submit'=>"index.php?app=ome&ctl=admin_order&act=BatchUpMemo",'target'=>'dialog::{width:690,height:200,title:\'批量设置备注\'}"'),

            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'finder_aliasname' => 'order_view'.$op_id,
            'finder_cols' => 'order_bn,shop_id,total_amount,column_print_status,column_users_type,process_status,is_cod,pay_status,ship_status,payment,shipping,logi_id,logi_no,createtime,paytime,mark_type',
            'base_filter' => $base_filter,
       );

       $user = kernel::single('desktop_user');
       if($user->has_permission('order_export')){
               $params['use_buildin_export'] = true;
       }

       if ($servicelist = kernel::servicelist('ome.service.order.index.action_bar'))
        foreach ($servicelist as $object => $instance){
            if (method_exists($instance, 'getActionBar')){
                $actionBars = $instance->getActionBar();
                foreach($actionBars as $actionBar){
                    $params['actions'][] = $actionBar;
                }
            }
        }

        if ($params['use_buildin_export'] == true && $servicelist = kernel::servicelist('ietask.service.actionbar')) {
            foreach ($servicelist as $object => $instance){
                if (method_exists($instance, 'getOrders')){
                    $actionBars = $instance->getOrders();
                    foreach($actionBars as $actionBar){
                        $params['actions'][] = $actionBar;
                    }
                }
            }
        }

       $this->finder('ome_mdl_orders',$params);
    }

    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        //导出操作日志
        $logParams = array(
            'app' => $this->app->app_id,
            'ctl' => trim($_GET['ctl']),
            'act' => trim($_GET['act']),
            'modelFullName' => '',
            'type' => 'export',
            'params' => array(),
        );
        ome_operation_log::insert('order_template_export', $logParams);
        $oObj = &$this->app->model('orders');
        $title1 = $oObj->exportTemplate('order');
        $title2 = $oObj->exportTemplate('obj');
        unset($title2[8],$title2[9],$title2[10]);
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";
        echo '"'.implode('","',$title2).'"';
    }

    function dispatch(){

        $this->title = '订单调度';
        $op_id = kernel::single('desktop_user')->get_id();
        switch ($_GET['flt']) {
            case 'assigned':
                $this->order_type = 'assigned';
                $this->base_filter = array(
                    'assigned' => 'assigned',
                    'abnormal'=>'false',
                    'is_fail'=>'false',
                    'process_status|noequal'=>'cancel',
                    'is_auto' => 'false',
                );
                $this->action = array(
                    array(
                        'label' => '回收到未分派',
                         'submit' => 'index.php?app=ome&ctl=admin_order&act=order_recover&action=recover',
                         'target' => 'dialog::{width:400,height:200,title:\'回收到未分派\'}'
                         ),
					  array(
                        'label' => '退回暂存区',
                        'submit' => 'index.php?ctl=admin_order&app=ome&act=order_buffer&flag=dispatch',
                        'confirm' => '您确认将选择的待处理订单退回到“订单暂存区”吗？退回到“订单暂存区”后，需要您通过“未分派订单”栏目的“获取订单”功能重新获取！',)
                );
                $this->title = '已分派的订单';
                $finder_aliasname = "order_dispatch_assigned";
                $finder_cols = "order_bn,shop_id,member_id,ship_name,ship_area,total_amount,op_id,group_id,process_status,is_cod,pay_status,ship_status,createtime,paytime,dispatch_time";
                break;
            case 'notassigned':
                $this->order_type = 'notassigned';
                $this->base_filter = array(
                    'assigned' => 'notassigned',
                    'abnormal'=>'false',
                    'ship_status'=>array('0', '2'),//部分发货也显示  ExBOY
                    'is_fail'=>'false',
                    'process_status|noequal'=>'cancel',
                    'is_auto' => 'false',
                );
                if ($_GET['view']) {
                    $flag = $_GET['view'];
                    switch ($flag) {
                        case 999:
                            $this->base_filter['auto_status'] = '0';
                            break;
                        case 989898:
                            $this->base_filter['is_cod'] = 'true';
                            break;
                        case 989900:
                            $this->base_filter['process_status'] = 'is_retrial';//ExBOY加入Tab栏目
                            break;
                        default:
                            
                            if (!empty($this->base_filter['order_confirm_filter'])){
                                $this->base_filter['order_confirm_filter'] .= sprintf(" AND (sdb_ome_orders.auto_status & %s = %s) ", $flag, $flag);
                            }elseif($flag<999000){
                                $this->base_filter['order_confirm_filter'] = sprintf(" (sdb_ome_orders.auto_status & %s = %s) ", $flag, $flag);
                            }
                            break;
                    }
                }

                $this->title = '未分派的订单';
                $this->action = array(
                    array('label' => '获取订单', 'href' => 'index.php?app=ome&ctl=admin_order_auto&act=index', 'target' => 'dialog::{width:1000,height:550,title:\'获取订单\'}'),
                    array('label' => '订单分派', 'submit' => 'index.php?app=ome&ctl=admin_order&act=dispatching', 'target' => 'dialog::{width:400,height:200,title:\'订单分派\'}'),
                );
                $finder_aliasname = "order_dispatch_notassigned";
                $finder_cols = "order_bn,shop_id,column_fail_status,member_id,ship_name,ship_area,total_amount,is_cod,pay_status,ship_status,column_deff_time,createtime,paytime";
                break;
            case 'buffer':
                $this->order_type = 'buffer';
                $this->base_filter = array(
                    'assigned' => 'buffer',//ExBOY加入SQl判断
                    'abnormal' => 'false',
                    'ship_status' => '0',
                    'is_fail' => 'false',
                    'process_status' => array('unconfirmed','is_retrial'),//ExBOY加入SQl判断
                    'status' => 'active',
                    'is_auto' => 'false',
                    'order_confirm_filter' => '( op_id IS NULL AND group_id IS NULL)');
                $this->action = array(

                    array('label' => '订单分派', 'submit' => 'index.php?app=ome&ctl=admin_order&act=dispatching', 'target' => 'dialog::{width:400,height:200,title:\'订单分派\'}'),
                );
                $this->title = '订单暂存区';
                $finder_aliasname = "order_dispatch_buffer";
                $finder_cols = "column_confirm,order_bn,shop_id,member_id,ship_name,ship_area,total_amount,is_cod,pay_status,ship_status,column_deff_time,createtime,paytime";
                break;
        }

        $this->base_filter['archive'] ='0';
        $this->base_filter['process_status|noequal'] = 'is_declare';//跨境申报 ExBOY

        $this->finder('ome_mdl_orders',array(
           'title' => $this->title,
            'actions' => $this->action,
           'base_filter' => $this->base_filter,
           'use_buildin_new_dialog' => false,
           'use_buildin_set_tag'=>false,
           'use_buildin_recycle'=>false,
           'use_buildin_export'=>false,
           'use_buildin_import'=>false,
           'use_buildin_filter'=>true,
            'finder_aliasname' => $finder_aliasname.$op_id,
           'finder_cols'=>$finder_cols,
        ));
    }

    function confirm(){
        $op_id = kernel::single('desktop_user')->get_id();
        if ($_GET['flt'] == 'unmyown'){
            $this->order_type = 'unmyown';
            $this->title = '订单确认 - 我的待处理订单';
            $this->base_filter['op_id'] = kernel::single('desktop_user')->get_id();
            $this->base_filter['assigned'] = 'assigned';
            $this->base_filter['abnormal'] = "false";
            $this->base_filter['is_fail'] = 'false';
            $this->base_filter['status'] = 'active';
            $this->base_filter['custom_process_status'] = array('unconfirmed', 'confirmed', 'splitting');

            if ($_GET['view']) {
                $flag = $_GET['view'];
                switch ($flag) {
                    case 989899:
                        $this->base_filter['pause'] = 'true';
//                        $this->base_filter['is_cod'] = 'false';
                        break;
                    case 989898:
                        $this->base_filter['pause'] = 'false';
                        $this->base_filter['is_cod'] = 'true';
                        break;
                    /*case 64:
                        $this->base_filter['pause'] = 'false';
                        if (isset($this->base_filter['order_confirm_filter'])) {
                            $this->base_filter['order_confirm_filter'] .= sprintf(" AND (sdb_ome_orders.auto_status & %s = %s)", $flag, $flag);
                        } else {
                            $this->base_filter['order_confirm_filter'] = sprintf("(sdb_ome_orders.auto_status & %s = %s)", $flag, $flag);
                        }
                        $this->action = array(
                            array('label' => '批量审单', 'submit' => 'index.php?ctl=admin_order&app=ome&act=setDlyCorp', 'target' => 'dialog::{width:600,height:400,title:\'批量审核订单\'}'),
                        );
                        break;*/
                    default:
                        $this->base_filter['pause'] = 'false';
                        if($flag>=999000) break;
                        if (isset($this->base_filter['order_confirm_filter'])) {
                            $this->base_filter['order_confirm_filter'] .= sprintf(" AND (sdb_ome_orders.auto_status & %s = %s)", $flag, $flag);
                        } else {
                            $this->base_filter['order_confirm_filter'] = sprintf("(sdb_ome_orders.auto_status & %s = %s)", $flag, $flag);
                        }
                        break;
                }
            }
            //订单退回
            $isgoback = kernel::single('desktop_user')->has_permission('order_goback');
            if($isgoback){
                $this->action = array(
                    array(
                        'label' => '退回未分派',
                        'submit' => 'index.php?ctl=admin_order&app=ome&act=order_goback',
                        'target' => 'dialog::{width:400,height:400,title:\'退回未分派\'}'
                    ),
                    array(
                        'label' => '退回暂存区',
                        'submit' => 'index.php?ctl=admin_order&app=ome&act=order_buffer',
                        'confirm' => '您确认将选择的待处理订单退回到“订单暂存区”吗？退回到“订单暂存区”后，需要您通过“未分派订单”栏目的“获取订单”功能重新获取！',
                    ),
                    array(
                        'label'=>'获取指定的店铺订单',
                        'icon'=>'download.gif',
                        'href'=>'index.php?app=ome&ctl=admin_order&act=getShopOrder',
                        'target'=>'dialog::{width:400,height:170,title:\'获取指定的店铺订单\'}'
                    ),
                   array(
                       'label'=>'批量操作',
                       'group'=>array(
                           array(
                               'label'=>app::get('ome')->_('批量设置备注'),
                               'submit'=>"index.php?app=ome&ctl=admin_order&act=BatchUpMemo",
                               'target'=>'dialog::{width:690,height:200,title:\'批量设置备注\'}"'
                           ),
                           array(
                               'label'=>app::get('ome')->_('批量审单'),
                               'submit'=>'index.php?app=ome&ctl=admin_batch_order&act=batchConfirm',
                               'target'=>'dialog::{width:690,height:400,title:\'批量审单\'}"'
                           )
                       )
                   )
                );
                #重新获取CRM赠品
                if($_GET['view'] == omeauto_auto_const::__CRMGIFT_CODE){
                    $this->action[] = array(
                            'label' => '重新获取CRM赠品',
                            'submit' => 'index.php?app=ome&ctl=admin_order&act=doRequestCRM',
                            'confirm' => '您确认重新获取CRM赠品嘛？',
                    );
                }
            }
            // 超级管理员
            if(kernel::single('desktop_user')->is_super()){
                if(isset($this->base_filter['op_id']))
                    unset($this->base_filter['op_id']);

                if(isset($this->base_filter['group_id']))
                    unset($this->base_filter['group_id']);
            }

            $this->base_filter['archive'] = 0;
            $this->finder('ome_mdl_orders',array(
               'title'=>$this->title,
               'actions'=>$this->action,
               'base_filter' => $this->base_filter,
               'use_buildin_new_dialog' => false,
               'use_buildin_set_tag'=>false,
               'use_buildin_recycle'=>false,
               'use_buildin_export'=>true,
               'use_buildin_import'=>false,
               'use_buildin_filter'=>true,
               'orderBy' => 'paytime,createtime',
                'finder_aliasname' => 'order_confirm_unmyown'.$op_id,
                'finder_cols' => '_func_0,column_confirm,column_fail_status,order_bn,column_custom_add,column_customer_add,shop_id,member_id,ship_name,ship_area,total_amount,op_id,group_id,process_status,is_cod,pay_status,ship_status,column_deff_time,createtime,paytime,dispatch_time',
            ));
        }elseif($_GET['flt'] == 'myown'){
            $this->order_type = 'myown';
            $this->title = '订单确认 - 我的已处理订单';
            $this->base_filter = array('op_id'=>kernel::single('desktop_user')->get_id());
            $this->base_filter['assigned'] = 'assigned';
            $this->base_filter['abnormal'] = "false";
            $this->base_filter['is_fail'] = 'false';
//            $this->base_filter['order_confirm_filter'] = "(is_cod='true' OR pay_status in ('1','4','5'))";
			/*$this->action = array(
                    array(
                        'label' => 'COD路由推送',
                        'href' => 'index.php?app=ome&ctl=admin_order&act=doSendToWms',
                        'target' => "dialog::{width:500,height:300,title:'订单推送'}",
                    ),
					array(
                        'label' => 'COD模拟发货',
                        'href' => 'index.php?app=ome&ctl=admin_order&act=doSendFaHuo',
                        'target' => "dialog::{width:500,height:300,title:'模拟发货'}",
                    ),
                );*/
            if(!isset($_GET['view'])){
                $this->base_filter['process_status'] = array('splited','remain_cancel','cancel');
            }

            if(kernel::single('desktop_user')->is_super()){
                if(isset($this->base_filter['op_id']))
                    unset($this->base_filter['op_id']);
            }

            $this->base_filter['archive'] = '0';

            $this->finder('ome_mdl_orders',array(
               'title'=>$this->title,
               'actions'=>$this->action,
               'base_filter' => $this->base_filter,
               'use_buildin_new_dialog' => false,
               'use_buildin_set_tag'=>false,
               'use_buildin_recycle'=>false,
               'use_buildin_export'=>false,
               'use_buildin_import'=>false,
               'use_buildin_filter'=>true,
               'orderBy' => 'createtime desc',
                'finder_aliasname' => 'order_confirm_myown'.$op_id,
                'finder_cols' => '_func_0,order_bn,shop_id,member_id,column_users_type,column_print_status,ship_name,ship_area,total_amount,op_id,group_id,process_status,is_cod,pay_status,ship_status,logi_id,logi_no,createtime,paytime,dispatch_time',
            ));
        }elseif($_GET['flt'] == 'ourgroup'){
            $this->order_type = 'ourgroup';
            $this->title = '订单确认 - 本组的订单';
            $group_id = "";
            $oGroup = &$this->app->model("groups");
            $op_group = $oGroup->get_group(kernel::single('desktop_user')->get_id());
            if($op_group && is_array($op_group)){
                foreach($op_group as $v){
                    $group_id[] = $v['group_id'];
                }
            }
            $this->base_filter = array('group_id'=>$group_id);
            $this->base_filter['assigned'] = 'assigned';
            $this->base_filter['abnormal'] = "false";
            $this->base_filter['is_fail'] = 'false';
            $this->base_filter['process_status'] = array('unconfirmed','confirmed','splitting','splited','remain_cancel');
            #高级筛选过滤的确认状态
            if(isset($_POST['process_status']) && ($_POST['process_status']!='cancel')){
                $this->base_filter['process_status'] = $_POST['process_status'];
            }
            if(kernel::single('desktop_user')->is_super()){
                if(isset($this->base_filter['group_id']))
                    unset($this->base_filter['group_id']);
            }
            $this->base_filter['archive'] ='0';

            if ($_GET['view'] && $_GET['view']==1) {
                $this->action = array(
                    array(
                            'label' => '批量领取',
                            'submit' => 'index.php?app=ome&ctl=admin_order&act=batchClaim',
                            'confirm' => '您确认领取以下订单吗？',
                        )
                );
            }
            
            $this->finder('ome_mdl_orders',array(
               'title'=>$this->title,
               'actions'=>$this->action,
               'base_filter' => $this->base_filter,
               'use_buildin_new_dialog' => false,
               'use_buildin_set_tag'=>false,
               'use_buildin_recycle'=>false,
               'use_buildin_export'=>false,
               'use_buildin_import'=>false,
               'use_buildin_filter'=>true,
               'orderBy' => 'createtime desc',
                'finder_aliasname' => 'order_confirm_ourgroup'.$op_id,
                'finder_cols' => '_func_0,column_confirm,order_bn,shop_id,member_id,ship_name,ship_area,total_amount,op_id,group_id,process_status,is_cod,pay_status,ship_status,createtime,paytime,dispatch_time',
            ));
        }
    }

	public function doSendToWms(){
		 $this->display("admin/order/doSendToWms.html");
	}
	
	public function doSendFaHuo(){
		 $this->display("admin/order/doSendToFaHuo.html");
	}
	
	public function saveSendFaHuo(){
		$url='http://erp-preprod.guerlain.d1m.cn/index.php/api';
		$order_bn=$_POST['strOrders'];
		$d_bn=app::get('ome')->model('orders')->db->select("SELECT d.delivery_bn FROM sdb_ome_orders O LEFT JOIN sdb_ome_delivery_order DO ON DO.order_id=O.order_id LEFT JOIN sdb_ome_delivery d ON d.delivery_id=DO.delivery_id WHERE O.order_bn='$order_bn'");
		 $task=time();
		$d_bn=$d_bn['0']['delivery_bn'];
		$logi_no=rand(1,9).rand(1,9).rand(1,9).rand(1,9).rand(1,9).rand(1,9);
		//echo "<pre>";print_r($d_bn);exit();
		$string='delivery_bn='.$d_bn.'&logi_no='.$logi_no.'&logi_id=EMS&status=delivery&weight=22&method=wms.delivery.status_update&node_id=selfwms&task='.$task;
		
		$ch = curl_init();//初始化一个cURL会话

          
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

           curl_setopt($ch, CURLOPT_POST, 1);
            // 把post的变量加上
           curl_setopt($ch, CURLOPT_POSTFIELDS, $string);

            //抓取URL并把它传递给浏览器
            $output = curl_exec($ch);//kernel::log($output);
			//$r=preg_replace("#\\\u(
			//echo "<pre>";print_r(json_decode($output));exit();
		echo '成功';exit();
	}
	
	public function saveSendToWms(){
		$strOrders=$_POST['strOrders'];
	
		
	   $url = 'index.php?app=ome&ctl=admin_order&act=confirm&flt=myown';
	   //echo "<pre>";print_r(count($arrOrders));exit();
       $this->begin($url);
	   //$this->end(false,app::get('base')->_('请填入订单字符串'));exit();
	   if($strOrders==""){
	   	 	$this->end(false,app::get('base')->_('请填入订单字符串'));exit();
	   }
	  $obj=kernel::single('erpapi_oms_order')->RoutePush($strOrders);
	  $this->end(true,app::get('base')->_('保存成功'));
		
	}
	
    public function setDlyCorp(){
        $ids = $_POST['order_id'];
        if(empty($ids)){
            $this->end(false, '请选择订单');
        }

        $combineObj = new omeauto_auto_combine();

        $orderGroup = $combineObj->getOrderGroup($ids);

        $dlyCrop = app::get('ome')->model('dly_corp')->getList('corp_id, name, type, is_cod, weight', array('disabled' => 'false'), 0, -1, 'weight DESC');

        $this->pagedata['orderNum'] = count($ids);
        $this->pagedata['dlyCorp'] = $dlyCrop;
        $this->pagedata['orderGroup'] = json_encode($orderGroup);
        $this->display('admin/order/set_dly.html');
    }

    public function batchExamineSingle() {
        $params = $this->_parseAjaxParams($_POST['ajaxParams']);
        $dlyType = strtoupper($_POST['dlyType']);

        if(empty($params)) {
            echo json_encode(array('flag'=>false, 'type'=>'combine', 'msg'=>'没有要操作的订单！', 'order'=>''));
            return;
        }

        if(empty($dlyType)) {
            echo json_encode(array('flag'=>false, 'type'=>'combine', 'msg'=>'没有正确选择快递公司！', 'order'=>''));
            return;
        }

        $orderObj = &app::get('ome')->model('orders');
        $order = $orderObj->dump(array('order_id'=>$params[0]['orders'][0]));

        $orderAuto = new omeauto_auto_combine();
        $combineOrder = $orderAuto->fetchCombineOrder($order);
        if(count($combineOrder)>1) {
            echo json_encode(array('flag'=>false, 'type'=>'combine', 'order'=>$order));
            return;
        }

        $orderAuto->setAutoValid(true);

        if($dlyType == 'SYSTEM') {
            $result = $orderAuto->process($params);
        } else {
            $orderAuto->setPlugins(array('pay', 'flag', 'member', 'ordermulti', 'examine'));
            $corp = $this->getCorp($dlyType);

            $itemList = $orderAuto->getItemList();
            foreach ($itemList as $key=>$item) {
                $item->setDlyCorp($corp);
            }

            $result = $orderAuto->process($params);
        }

        if(empty($result)) {
            echo json_encode(array('flag'=>false, 'type'=>'combine', 'order'=>$order));
            return;
        }

        echo json_encode(array('flag'=>true, 'type'=>'combine', 'order'=>$order));
    }

    public function batchClaim() {
        $this->begin('index.php?app=ome&ctl=admin_order&act=confirm&flt=ourgroup&view=1');
        $orderIds = $admin = array();

        // 数据验证
        $orderIds = $_POST['order_id'];
        if (empty($orderIds)) {
            $this->end(false, app::get('ome')->_('请选择要操作的数据项。'));
            return;
        }
        if (is_array($orderIds)) {
            foreach ($orderIds as $value) {
                if (!is_numeric($value)) {
                    $this->end(false, app::get('ome')->_('数据类型不正确。'));
                    return;
                }
            }
        } else {
            if (!is_numeric($orderIds)) {
                $this->end(false, app::get('ome')->_('数据类型不正确。'));
                return;
            }
        }

        $admin['account_id'] = $_SESSION['account']['shopadmin'];
        if ($admin['account_id'] <= 0) {
            $this->end(false, app::get('ome')->_('账户ID不能为空。'));
            return;
        }

        // 数据处理
        if(is_array($orderIds) && !empty($orderIds)){
            $orderObj = &$this->app->model("orders");
            if ($orderObj->update(array('op_id' => $admin['account_id']), array('order_id' => $orderIds))){
                $userObj = &app::get('desktop')->model('users');
                $operationLogObj = &app::get('ome')->model('operation_log');

                $userInfo = $userObj->dump(intval($admin['account_id']));
                $memo = '订单被'.$userInfo['name'].'领取';

                $operationLogObj->batch_write_log('order_dispatch@ome',$memo,time(),array('order_id' => $orderIds));
                $this->end(true, app::get('ome')->_('批量领取操作成功。'));
            }else{
                $this->end(false, app::get('ome')->_('批量领取操作失败。'));
            }
        }else{
            $this->end(false, app::get('ome')->_('批量领取操作失败。'));
        }

        unset($order, $admin);
        return;
    }

    public function claim() {
        $this->begin('index.php?app=ome&ctl=admin_order&act=confirm&flt=ourgroup&view=1');
        $order = $admin = array();

        // 数据验证
        $order['id'] = $_GET['order_id'];
        if (empty($order['id'])) {
            $this->end(false, app::get('ome')->_('请选择要操作的数据项。'));
            return;
        }
        if (is_array($order['id'])) {
            foreach ($order['id'] as $value) {
                if (!is_numeric($value)) {
                    $this->end(false, app::get('ome')->_('数据类型不正确。'));
                    return;
                }
            }
        } else {
            if (!is_numeric($order['id'])) {
                $this->end(false, app::get('ome')->_('数据类型不正确。'));
                return;
            }
        }

        $admin['account_id'] = $_SESSION['account']['shopadmin'];
        if ($admin['account_id'] <= 0) {
            $this->end(false, app::get('ome')->_('账户ID不能为空。'));
            return;
        }

        $orderObj = &$this->app->model("orders");
        $result = $orderObj->db->select(sprintf("SELECT count(*) as _count FROM `%s` WHERE order_id IN ('%s') AND (op_id IS NULL OR op_id = 0 OR op_id = '')", $orderObj->table_name(1), implode("','", $order['id'])));
        if (intval($result[0]['_count']) !== 1) {
            $this->end(false, app::get('ome')->_('订单已被领取。'));
            return;
        }

        $combineobj = kernel::single('omeauto_auto_combine');
        $orderInfo = $orderObj->dump($order['id'][0]);
        $orderIds = array();
        $combineOrders = $combineobj->fetchCombineOrder($orderInfo);
        foreach ($combineOrders as $comOrder) {
            if($comOrder['group_id']>0 && $comOrder['op_id']==0){
                $orderIds[] = $comOrder['order_id'];
            }
        }
        unset($combineOrders);

        // 数据处理
        if(is_array($orderIds) && !empty($orderIds)){
            if ($orderObj->update(array('op_id' => $admin['account_id']), array('order_id' => $orderIds))){
                $userObj = &app::get('desktop')->model('users');
                $operationLogObj = &app::get('ome')->model('operation_log');

                $userInfo = $userObj->dump(intval($admin['account_id']));
                $memo = '订单被'.$userInfo['name'].'领取';

                $operationLogObj->batch_write_log('order_dispatch@ome',$memo,time(),array('order_id' => $orderIds));
                $this->end(true, app::get('ome')->_('领取操作成功。'));
            }else{
                $this->end(false, app::get('ome')->_('领取操作失败。'));
            }
        }else{
            $this->end(false, app::get('ome')->_('领取操作失败。'));
        }

        unset($order, $admin);
        return;
    }

    function count_dispatch($data=''){
        if ($_POST){
            $start  = $_POST['start'];
            $end    = $_POST['end'];
            $group_id = $_POST['group'];
            $op_id  = $_POST['operator'];

            $where = '';
            if ($op_id != ''){
                $where .= " AND o.op_id = $op_id ";
            }
            if ($group_id != ''){
                $where .= " AND o.group_id = $group_id ";
            }
            if ($start != '' && $end != ''){
                $s = strtotime($start. ' 00:00:00');
                $e = strtotime($end. ' 23:59:59');
                $where .= " AND (o.dt_begin >= $s AND o.dt_begin <= $e) ";
            }
        }else {
            if ($data){
                if ($data == 'today'){
                    $day_s = strtotime(date('Y-m-d'). ' 00:00:00');
                    $day_e = strtotime(date('Y-m-d'). ' 23:59:59');
                    $where = " AND (o.dt_begin >= $day_s AND o.dt_begin <= $day_e) ";
                }elseif ($data == 'twodays'){
                    $day_s = strtotime('-2 day  00:00:00');
                    $day_e = strtotime(date('Y-m-d'). ' 23:59:59');
                    $where = " AND (o.dt_begin >= $day_s AND o.dt_begin <= $day_e) ";
                }
            }else {
                $where = ' AND 1';
            }
        }
        $oObj = &$this->app->model('orders');

        //all
        $all = $oObj->get_all($where);

        //group
        $group = $oObj->get_group($where);

        //operator
        $operator = $oObj->get_operator($where);
        $groups = $this->app->model('groups')->getList('group_id,name',array('g_type'=>'confirm'),0,-1);
        $ops = $oObj->get_confirm_ops();

        $this->pagedata['groups'] = $groups;
        $this->pagedata['ops'] = $ops;
        $this->pagedata['all'] = $all;
        $this->pagedata['group'] = $group;
        $this->pagedata['operator'] = $operator;
        $this->display('admin/order/count_order.html');
    }

    function dispatching(){
        $combineobj = kernel::single('omeauto_auto_combine');
        $orderObj = &$this->app->model("orders");
        $orders = $orderObj->getList('*', array('order_id' => $_POST['order_id']));

        $orderIds = array();
        foreach ($orders as $order) {
            if ($order['group_id'] == 0 && $order['op_id'] == 0) {
                $orderIds[$order['order_id']] = $orderIds['order_bn'];
            }

            /*
            $combineOrders = $combineobj->fetchCombineOrder($order);
            foreach ($combineOrders as $comOrder) {
                if($comOrder['group_id']==0 && $comOrder['op_id']==0){
                    $orderIds[$comOrder['order_id']] = $comOrder['order_bn'];
                }
            }
            unset($combineOrders);
            */
        }

        $this->pagedata['orderIds'] = $orderIds;
        if (isset($_POST['isSelectedAll'])&&($_POST['isSelectedAll']=='_ALL_')) {
            $this->pagedata['isSelectedAll']     = urlencode(json_encode($_POST));
        }
        $oGroup = &$this->app->model('groups');
        $groups = $oGroup->getList('group_id,name',array('g_type'=>'confirm'));
        $this->pagedata['groups'] = $groups;
        $this->display("admin/order/dispatching.html");
    }

    function dispatchSingle($orderId){
        $orderObj = &$this->app->model("orders");
        $order = $orderObj->dump($orderId);
        $orderIds[$order['order_id']] = $order['order_bn'];
        $this->pagedata['orderIds'] = $orderIds;

        $oGroup = &$this->app->model('groups');
        $groups = $oGroup->getList('group_id,name',array('g_type'=>'confirm'));
        $this->pagedata['groups'] = $groups;
        $this->pagedata['single'] = 1;
        $this->display("admin/order/dispatching.html");
    }

    function do_dispatch(){
        $order_ids = array();
        $filter = array();
        $data['group_id']      = $_POST['new_group_id']?intval($_POST['new_group_id']):0;
        $data['op_id']         = $_POST['new_op_id']?intval($_POST['new_op_id']):0;
        $data['dt_begin']      = time();
        $data['dispatch_time'] = time();

        $orderObj = &$this->app->model("orders");
        $preProcessLib = new ome_preprocess_entrance();
        //是从暂存取拉出来的订单做相应的预处理
        if($_POST['single'] == 1){
            //$preProcessLib = new ome_preprocess_entrance();
            $preProcessLib->process($_POST['order_id'][0],$msg);
            #淘宝全链路 已客审
            kernel::single('ome_order')->sendMessageProduce(1, $_POST['order_id'][0]);

            $orderInfo = $orderObj->dump($_POST['order_id'],'auto_status,abnormal_status');
            if($orderInfo){
                if(($orderInfo['abnormal_status'] & ome_preprocess_const::__HASGIFT_CODE) == ome_preprocess_const::__HASGIFT_CODE){
                    if($orderInfo['auto_status'] == 0){
                        $data['auto_status'] = omeauto_auto_const::__PMTGIFT_CODE;
                    }elseif( ($orderInfo['auto_status'] & omeauto_auto_const::__PMTGIFT_CODE) != omeauto_auto_const::__PMTGIFT_CODE){
                        $data['auto_status'] = $orderInfo['auto_status'] | omeauto_auto_const::__PMTGIFT_CODE;
                    }
                }

                #获取crm基本配置
                $crm_cfg = app::get('crm')->getConf('crm.setting.cfg');
                #检测crm是否开启
                if(!empty($crm_cfg)){
                    $tb_auto_status = $data['auto_status'];
                    if(($orderInfo['abnormal_status'] & ome_preprocess_const::__HASCRMGIFT_CODE) == ome_preprocess_const::__HASCRMGIFT_CODE){
                        if($tb_auto_status == 0){
                            $data['auto_status'] = omeauto_auto_const::__CRMGIFT_CODE;
                        }elseif( ($tb_auto_status & ome_preprocess_const::__HASCRMGIFT_CODE) != ome_preprocess_const::__HASCRMGIFT_CODE){
                            $data['auto_status'] = $tb_auto_status | ome_preprocess_const::__HASCRMGIFT_CODE;
                        }
                    }
                }
            }


            //超卖
            $orderObjectObj = &$this->app->model("order_objects");
            $res = $orderObjectObj->getList('order_id',array('order_id'=>$_POST['order_id'],'is_oversold'=>1),0,-1);
            if($res){
                if($orderInfo){
                    if($orderInfo['auto_status'] == 0){
                        $data['auto_status'] = omeauto_auto_const::__OVERSOLD_CODE;
                    }elseif( ($orderInfo['auto_status'] & omeauto_auto_const::__OVERSOLD_CODE) != omeauto_auto_const::__OVERSOLD_CODE){
                        $data['auto_status'] = $orderInfo['auto_status'] | omeauto_auto_const::__OVERSOLD_CODE;
                    }
                }
            }
        }else{
            //$crm_cfg = app::get('crm')->getConf('crm.setting.cfg');
            #检测crm是否开启,只有开启crm应用时，才执行以下代码
            //if(!empty($crm_cfg)){
                #获取所有的预处理订单
                $arr_order_id = array();
                if(isset($_POST['order_id'])&&is_array($_POST['order_id'])&&(count($_POST['order_id'])>0)) {
                    $arr_order_id = $_POST['order_id'];
                }elseif(isset($_POST['isSelectedAll'])&&$_POST['isSelectedAll']){//全选
                    $params = json_decode(urldecode($_POST['isSelectedAll']),true);
                    if(isset($params['isSelectedAll'])&&($params['isSelectedAll']=='_ALL_')){
                        $params['filter_sql']    = '(op_id is null or op_id=0)';
                        unset($params['app']);
                        unset($params['ctl']);
                        unset($params['act']);
                        unset($params['flt']);
                        unset($params['_finder']);
                        $filter = $params;
                        $_order_id = $orderObj->getList('order_id',$filter);
                        foreach($_order_id as $_id){
                            $arr_order_id[] = $_id['order_id'];
                        }
                    }
                }
                #批量分派处理
                foreach($arr_order_id as $order_id){
                    $preProcessLib->process($order_id,$msg);
                    #淘宝全链路 已客审
                    kernel::single('ome_order')->sendMessageProduce(1, $order_id);
                    $orderInfo = $orderObj->dump($order_id,'auto_status,abnormal_status');
                    if($orderInfo){
                            if(($orderInfo['abnormal_status'] & ome_preprocess_const::__HASCRMGIFT_CODE) == ome_preprocess_const::__HASCRMGIFT_CODE){
                                if($orderInfo['auto_status'] == 0){
                                    $_data['auto_status'] = omeauto_auto_const::__CRMGIFT_CODE;
                                }elseif( ($orderInfo['auto_status'] & ome_preprocess_const::__HASCRMGIFT_CODE) != ome_preprocess_const::__HASCRMGIFT_CODE){
                                    $_data['auto_status'] = $orderInfo['auto_status'] | ome_preprocess_const::__HASCRMGIFT_CODE;
                                }
                                $orderObj->update($_data,array('order_id'=>$order_id));
                                unset($_data);
                            }
                    }
                }
            //}
        }

        //分派过滤条件
        if (isset($_POST['order_id'])&&is_array($_POST['order_id'])&&(count($_POST['order_id'])>0)) {
            $filter['order_id']       = $_POST['order_id'];
            $filter['filter_sql']    = '(op_id is null or op_id=0)';
            $order_ids = array('_ALL_');
        }elseif(isset($_POST['isSelectedAll'])&&$_POST['isSelectedAll']){//全选
            $params = json_decode(urldecode($_POST['isSelectedAll']),true);
            if(isset($params['isSelectedAll'])&&($params['isSelectedAll']=='_ALL_')){
                $params['filter_sql']    = '(op_id is null or op_id=0)';
                unset($params['app']);
                unset($params['ctl']);
                unset($params['act']);
                unset($params['flt']);
                unset($params['_finder']);
                $filter = $params;
                $order_ids = array('_ALL_');
            }
        }
        if(!empty($filter)&&(isset($filter['order_id'])||isset($filter['isSelectedAll']))){
            $filter['process_status'] = array('unconfirmed', 'confirmed', 'splitting', 'is_retrial');//ExBOY加入is_retrial
            $orderObj->filter_use_like = true;
            $orderObj->dispatch($data,$filter,$order_ids);
        }
        echo "<script>$$('.dialog').getLast().retrieve('instance').close();</script>";
    }

    function do_cancel($order_id) {

        $oOrder = &$this->app->model('orders');
        $orderdata = $oOrder->dump($order_id);
        if ($_POST) {
            //danny_freeze_stock_log
            define('FRST_TRIGGER_OBJECT_TYPE','订单：订单人工取消');
            define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：do_cancel');
            $memo = "订单被取消 ".$_POST['memo'];
            $mod = 'sync';
            $oShop = &$this->app->model('shop');
            $c2c_shop_list = ome_shop_type::shop_list();
            $shop_detail = $oShop->dump(array('shop_id'=>$orderdata['shop_id']),'node_id,node_type');
            if(!$shop_detail['node_id'] || in_array($shop_detail['node_type'],$c2c_shop_list) || $orderdata['source'] == 'local'){
                $mod = 'async';
            }

            $sync_rs = $oOrder->cancel($order_id,$memo,true,$mod);

            if($sync_rs['rsp'] == 'success')
            {
                //取消订单发票记录 ExBOY 2014.04.08
                if(app::get('invoice')->is_installed())
                {
                    $Invoice       = &app::get('invoice')->model('order');
                    $Invoice->delete_order($order_id);
                }
                if(app::get('omemagento')->is_installed()){
					$megentoInstance = kernel::service('service.magento_order');
					$megentoInstance->update_status($orderdata['order_bn'],'canceled');
				}

                ### 订单状态回传kafka august.yao 已取消 start ###
                if($orderdata['pay_bn'] == 'cod'){
                    $kafkaQueue = app::get('ome')->model('kafka_queue');
                    $queueData = array(
                        'queue_title' => '订单已取消状态推送',
                        'worker'      => 'ome_kafka_api.sendOrderStatus',
                        'start_time'  => time(),
                        'params'      => array(
                            'status'   => 'cancel',
                            'order_bn' => $orderdata['order_bn'],
                            'logi_bn'  => '',
                            'shop_id'  => $orderdata['shop_id'],
                            'item_info'=> array(),
                            'bill_info'=> array(),
                        ),
                    );
                    $kafkaQueue->save($queueData);
                }
                ### 订单状态回传kafka august.yao 已取消 end ###

                echo "<script>alert('订单取消成功');</script>";
            }else{
                echo "<script>alert('订单取消失败,原因是:".$sync_rs['msg']."');</script>";
            }
            echo "<script>window.finderGroup[$(document.body).getElement('input[name^=_finder\[finder_id\]]').value].refresh();$$('.dialog').getLast().retrieve('instance').close();</script>";
        }
        $this->pagedata['order'] = $orderdata;
        $this->display("admin/order/detail_cancel.html");
    }

    function do_confirm($order_id, $oId = null) {
        if (isset($_GET['find_id'])) {
            $finder_id = $_GET['find_id'];
        } else {
            $finder_id = $_GET['finder_id'];
        }
        $oOrder = &$this->app->model("orders");
        //---获取finder选择条件
        $ini_filter = $_GET['filter'];
        $finder_filter = unserialize(base64_decode($ini_filter));

        $filter = app::get('ome')->model('orders')->_filter($finder_filter);

        if ($order_id == 'up' || $order_id == 'next') {
            if ($order_id == 'up')
                $order_id = $oOrder->getOrderUpNext($oId, $filter, '<'); //$this->_getOrderIdByAction($order_id);
            else
                $order_id = $oOrder->getOrderUpNext($oId, $filter, '>');

            if (empty($order_id)) {
                header("content-type:text/html; charset=utf-8");
                echo "<script>alert('当前条件下的订单多已处理完成！！！');opener.finderGroup['{$finder_id}'].refresh.delay(100,opener.finderGroup['{$finder_id}']);window.close();</script>";
                //echo "<div class='success'><h1>'当前条件下的订单多已处理完成！！！'</h1></div>";
                exit;
            } else {
                $order_id = $order_id['order_id'];
            }
        }

        $order = $oOrder->dump($order_id);

        $this->pagedata['is_splited'] = $order['process_status'] == 'splitting' ? 'false' : 'true';
        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $user_id = $opInfo['op_id'];
        $is_supp = kernel::single('desktop_user')->is_super();

        if ($order['shipping']['is_cod'] == 'false' && $order['pay_status'] == '3') {
            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('请完成付款后，再进行确认');opener.finderGroup['{$finder_id}'].refresh.delay(100,opener.finderGroup['{$finder_id}']);window.close();</script>";
            exit;
        } else {
            if ($order['op_id'] == '' && !$is_supp) {
                $oo['order_id'] = $order_id;
                $oo['op_id'] = $user_id;
                $oOrder->save($oo);
            }
        }

        #判断订单编辑同步状态
        $oOrder_sync = &app::get('ome')->model('order_sync_status');
        $sync_status = $oOrder_sync->getList('order_id,type,sync_status',array('order_id'=>$order_id),0,1);
        if ($sync_status[0]['sync_status'] == '1' && $order['source'] == 'matrix'){
            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('订单编辑同步失败,无法确认生成发货单!');window.close();</script>";
            exit;
        }

        $split_type = $this->app->getConf('ome.order.split_type');
        $oMember = &$this->app->model("members");
        $member = $oMember->dump($order['member_id']);
        //当订单类型是taobao时,获取shop_member_id
        if ($order['shop_type']=='taobao') {

            $shopMember = app::get('ome')->model('shop_members')->dump(array('member_id' => $order['member_id']));
            $member['shop_member_id'] = $shopMember['shop_member_id'];

        }
        //
        $item_list = $oOrder->getItemBranchStore($order_id);

        $object_alias = $oOrder->getOrderObjectAlias($order_id);

        if (!preg_match("/^mainland:/", $order['consignee']['area'])) {
            $region = '';
            $newregion = '';
            foreach (explode("/", $order['consignee']['area']) as $k => $v) {
                $region.=$v . ' ';
            }
        } else {
            $newregion = $order['consignee']['area'];
        }

        //获取当前订单的上下条订单

        $this->pagedata['filter'] = urlencode($ini_filter);

        //获取相关订单，并输入内容
        $combineObj = kernel::single('omeauto_auto_combine');
        $combineOrders = $combineObj->fetchCombineOrder($order);



        $order = $oOrder->dump($order_id);
        //地址html转化
        $order['consignee']['addr'] = str_replace(array("\r\n","\r","\n","'","\""), '',  htmlspecialchars($order['consignee']['addr']));

        $orderIdx = $order['order_combine_idx'];
        $orderHash = $order['order_combine_hash'];

        $flag_edit = 'true';

        foreach ($combineOrders as $k=>$combineOrder) {
            $combineOrders[$k]['mark_text'] = strip_tags(htmlspecialchars($combineOrder['mark_text']));
            $combineOrders[$k]['custom_mark'] = strip_tags(htmlspecialchars($combineOrder['custom_mark']));

            if($combineOrder['isCombine'] == true){
                $isCombinIds[] = $combineOrder['order_id'];
            }
            $combinIds[] = $combineOrder['order_id'];

            if ($order_add_service = kernel::service('service.order.'.$combineOrder['shop_type'])){
                if (method_exists($order_add_service, 'is_edit_view')){
                    $order_add_service->is_edit_view($combineOrder, $flag_edit);
                }
            }

            $combineOrders[$k]['flag_edit'] = $flag_edit;
        }

        $this->pagedata['combineOrders'] = $combineOrders;
        $this->pagedata['jsOrders'] = json_encode($combineOrders);

        if (empty($this->pagedata['combineOrders'])) {

            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('该订单已处理完成');opener.finderGroup['{$finder_id}'].refresh.delay(100,opener.finderGroup['{$finder_id}']);window.close();</script>";
            exit;
        }
        //end edit by hzjsqs

        $order['mark_text'] = unserialize($order['mark_text']);
        $order['custom_mark'] = unserialize($order['custom_mark']);

        //下单时间离当前的时间差
        if ($order['shipping']['is_cod'] == 'true') {
            $difftime = kernel::single('ome_func')->toTimeDiff(time(), $order['createtime']);
        }else{
            $difftime = kernel::single('ome_func')->toTimeDiff(time(), $order['paytime']);
        }

        $order['difftime'] = $difftime['d'] . '天' . $difftime['h'] . '小时' . $difftime['m'] . '分';

        // 匹配(快递)物流公司
        $this->pagedata['defaultExpress'] = $this->getDefaultParseCorp($order);
        //error_log(var_export($this->pagedata['defaultExpress'],true),3,__FILE__.".log");
        // 选择快递公司完毕

        $branch_id = $this->getDefaultBranch($isCombinIds);

        $branch_list = $oOrder->getBranchByOrder($combinIds);

        if ($branch_id[$orderHash]){
            $selected_branch_id = $branch_id[$orderHash];
            
            $branchObj = &app::get('ome')->model('branch');
            $recomm_branch = $branchObj->db->selectrow("select branch_id,name FROM sdb_ome_branch WHERE branch_id=".$selected_branch_id);
            
            $this->pagedata['recommend_branch'] =$recomm_branch;
        }else{
            $selected_branch_id = $branch_list[0]['branch_id'];
        }

        $this->pagedata['selected_branch_id'] = $selected_branch_id;
        $this->pagedata['branch_list'] = $branch_list;

        $orderWeight = array();
        foreach ($combinIds as $combin_order_id) {
            $orderWeight[$combin_order_id] = $this->app->model('orders')->getOrderWeight($combin_order_id);
        }

        $weight = 0;
        foreach ($isCombinIds as $oweight) {
            if($orderWeight[$oweight]==0){
                $weight = 0;
                break;
            }else{
                $weight+=$orderWeight[$oweight];
            }

        }
        //收货地址判断是否包含手机
        $combine_conf = app::get('ome')->getConf('ome.combine.addressconf');
        $this->pagedata['combine_addressconf_mobile'] = strval($combine_conf['mobile']);

        #[发货配置]是否启动拆单 ExBOY
        $deliveryObj    = &app::get('ome')->model('delivery');
        $split_seting   = $deliveryObj->get_delivery_seting();
        
        $split_model    = 0;
        if($split_seting['split'] && $split_seting['split_model'])
        {
            $split_model   = $split_seting['split_model'];
        }
        $this->pagedata['split_model']  = $split_model;
        
        //
        $shopObj = &$this->app->model("shop");
        $shopInfo = $shopObj->dump($order['shop_id'],'name,shop_type');
        $this->pagedata['shopInfo'] = $shopInfo;

        $orderStatus = $combineObj->getStatus($order);
        $this->pagedata['orderStatus'] = $orderStatus;
        $this->pagedata['region'] = $region;
        $this->pagedata['newregion'] = $newregion;
        $this->pagedata['order_id'] = $order_id;
        ome_order_func::order_sdf_extend($item_list);
        $this->pagedata['item_list'] = $item_list;
        $this->pagedata['object_alias'] = $object_alias;


        $this->pagedata['member'] = $member;
		if(!$order['invoice_area']){
			$order['invoice_area'] = $order['consignee']['area'];
		}
		if(!$order['invoice_addr']){
			$order['invoice_addr'] = $order['consignee']['addr'];
		}
		if(!$order['invoice_zip']){
			$order['invoice_zip'] = $order['consignee']['zip'];
		}
		if(!$order['invoice_contact']){
			$order['invoice_contact'] = $order['consignee']['mobile'];
		}
        $this->pagedata['order'] = $order;

        $this->pagedata['curorder'] = $order;
        $this->pagedata['split_type'] = $split_type;
        $this->pagedata['weight'] = $weight;

        $this->pagedata['orderWeight'] = json_encode($orderWeight);

        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['finder_id'] = $finder_id;
        //到不到是否开启
        $arrive_conf = app::get('ome')->getConf('ome.logi.arrived');
        $this->pagedata['arrive_conf'] = intval($arrive_conf);
        //
        #[拆单]使用单独拆单模板 ExBOY
        if($split_model)
        {
            #拆单_货到付款_禁止拆单
            if($order['shipping']['is_cod'] == 'true')
            {
                $this->pagedata['split_model']  = 0;
                $this->pagedata['order_is_cod'] = 'true';//货到付款
                $this->singlepage("admin/order/confirm.html");
                exit;
            }
            
            $flag               = false;
            $chk_repeat_list    = array();
            
            if(!empty($combineOrders[$order_id]['items']))
            {
                foreach ($combineOrders[$order_id]['items'] as $items_type => $item_row)
                {
                    foreach ($item_row as $obj_id => $obj_item) 
                    {
                        foreach ($obj_item['order_items'] as $item_id => $order_item) 
                        {
                            //剔除无效的商品
                            if($order_item['delete'] == 'true' && ($items_type == 'pkg' || $items_type == 'giftpackage')) 
                            {
                                unset($combineOrders[$order_id]['items'][$items_type][$obj_id]);
                                break;
                            }
                            elseif ($order_item['delete'] == 'true')
                            {
                                unset($combineOrders[$order_id]['items'][$items_type][$obj_id][$item_id]);
                                break;
                            }
                            
                            //标记重复的商品
                            $chk_repeat_list[$order_item['product_id']]['num']++;
                            if($chk_repeat_list[$order_item['product_id']]['num'] > 1)
                            {
                                $flag   = true;
                            }
                        }
                    }
                }
            }
            
            #订单部分发货或部分拆分
            if($order['process_status'] == 'splitting' || $order['ship_status'] == '2') 
            {
                $flag   = false;
            }
            
            #记录重复货号[剔除无效商品前无重复货号则无需判断]
            if(!empty($flag) && $_GET['is_repeat'] != '1')
            {
                $chk_repeat_list    = $repeat_product_ids = $repeat_product_bns = array();
                foreach ($combineOrders[$order_id]['items'] as $items_type => $item_row)
                {
                    foreach ($item_row as $obj_id => $obj_item) 
                    {
                        //判断重复出现的[捆绑]商品
                        if($items_type == 'pkg') 
                        {
                            $obj_item['goods_id']   = ($obj_item['goods_id'] ? $obj_item['goods_id'] : $obj_item['shop_goods_id']);
                            
                            $chk_repeat_list[$items_type][$obj_item['goods_id']]['num']++;
                            if($chk_repeat_list[$items_type][$obj_item['goods_id']]['num'] > 1)
                            {
                                $repeat_product_ids[$items_type][$obj_item['goods_id']] = $obj_item['goods_id'];
                                $repeat_product_bns[$obj_item['goods_id']]              = $obj_item['bn'];
                            }
                        }
                        else 
                        {
                            foreach ($obj_item['order_items'] as $item_id => $order_item) 
                            {
                                $chk_repeat_list['goods'][$order_item['product_id']]['num']++;//注：$items_type='goods'固定值
                                if($chk_repeat_list['goods'][$order_item['product_id']]['num'] > 1) 
                                {
                                    $repeat_product_ids['goods'][$order_item['product_id']] = $order_item['product_id'];
                                    $repeat_product_bns[$order_item['product_id']]          = $order_item['bn'];
                                }
                            }
                        }
                    }
                }
                
                #拆单_[普通][赠品][捆绑]商品货号有重复时，则不允许拆分数量
                if(!empty($repeat_product_ids))
                {
                    $this->pagedata['split_model']  = 0;
                    $this->pagedata['repeat_product']  = implode(',', $repeat_product_bns);
                    $this->singlepage("admin/order/confirm.html");
                    exit;
                }
                unset($chk_repeat_list, $repeat_product_ids, $repeat_product_bns);
            }
            $this->pagedata['combineOrders']    = $combineOrders;
            $this->pagedata['jsOrders']         = json_encode($combineOrders);
            
            #发货单详细列表
            $dlyObj    = &app::get('ome')->model('delivery');
            $dly_ids   = $dlyObj->getDeliverIdByOrderId($order_id);
            if(!empty($dly_ids))
            {
                //仓库
                $branch_data   = array();
                foreach ($branch_list as $key => $val)
                {
                    $temp_id   = $val['branch_id'];
                    $branch_data[$temp_id]    = $val['name'];
                }
                $status_text = array ('succ' => '已发货','failed' => '发货失败','cancel' => '已取消','progress' => '等待配货', 
                            'timeout' => '超时','ready' => '等待配货','stop' => '暂停','back' => '打回');
                
                //发货单
                $in_ids    = implode(',', $dly_ids);
                $sql       = "SELECT i.*, d.delivery_bn, d.branch_id, d.logi_id, d.logi_name, d.status, d.delivery_cost_expect, d.is_bind FROM sdb_ome_delivery_items AS i 
                            LEFT JOIN sdb_ome_delivery AS d ON i.delivery_id=d.delivery_id WHERE i.delivery_id in(".$in_ids.")";
               $temp_data  = $oOrder->db->select($sql);
               
               $order_dlylist   = array();
               foreach ($temp_data as $key => $val)
               {
                    $val_dlyid      = $val['delivery_id'];
                    $val_status     = $val['status'];
                    $val_branch_id  = $val['branch_id'];
                       
                    $val['status']      = $status_text[$val_status];//发货状态
                    $val['branch_name'] = $branch_data[$val_branch_id];//仓库       

                    $order_dlylist[$val_dlyid]['list'][]    = $val;
                    $order_dlylist[$val_dlyid]['count']     = count($order_dlylist[$val_dlyid]['list']);
               }
               $this->pagedata['order_dlylist']    = $order_dlylist;
            }
            
            #退款&&退换货记录
            if(in_array($order['pay_status'], array('4', '5', '6', '7')))
            {
                $orderItemObj   = &app::get('ome')->model('order_items');
                $oReship        = &app::get('ome')->model('reship');
                $oRefund_apply  = &app::get('ome')->model('refund_apply');
                
                //退换货记录
                $status_text    = $oReship->is_check;
                
                $sql            = "SELECT r.reship_bn, r.status, r.is_check, r.tmoney, r.return_id, i.* 
                                    FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id 
                                    WHERE r.order_id='".$order_id."' AND r.return_type in('return', 'change') AND r.is_check!='5'";
                $reship_list    = kernel::database()->select($sql);
                if($reship_list)
                {
                    $temp_bn  = array();
                    foreach ($reship_list as $key => $val)
                    {
                        $val['return_type_name']    = ($val['return_type'] == 'return' ? '退货' : '换货');
                        $val['type_name']           = $status_text[$val['is_check']];
                        $val['addon']               = '-';//规格
                        
                        //存储货号查询规格
                        $temp_bn[]        = $val['product_id'];
                        
                        $reship_list[$key]  = $val;
                    }
                    
                    $temp_items = array();
                    $temp_addon = $orderItemObj->getList('product_id, addon', array('order_id'=>$order_id, 'product_id'=>$temp_bn));
                    foreach ($temp_addon as $key => $val)
                    {
                        if($val['addon'])
                        {
                            $temp_items[$val['product_id']] = ome_order_func::format_order_items_addon($val['addon']);;
                        }
                    }                    
                    if($temp_addon)
                    {
                        foreach ($reship_list as $key => $val)
                        {
                            $product_id = $val['product_id'];
                            
                            if($temp_items[$product_id])
                            {
                                $val['addon']       = $temp_items[$product_id];
                            }
                            $reship_list[$key]      = $val;
                        }
                    }
                    
                    unset($temp_bn, $temp_addon, $temp_items);
                }
                $this->pagedata['reship_list'] = $reship_list;
                
                //退款记录
                $refund_apply   = $oRefund_apply->getList('apply_id, refund_apply_bn, money, refunded, create_time, last_modified, status, return_id', 
                                                            array('order_id'=>$order_id, 'disabled'=>'false'));
                if($refund_apply){
                    foreach($refund_apply as $k=>$v){
                        $refund_apply[$k]['status_text'] = ome_refund_func::refund_apply_status_name($v['status']);
                    }
                }
                $this->pagedata['refund_apply'] = $refund_apply;
            }
            
            $this->singlepage("admin/order/confirm_split.html");
        }
        else 
        {
            $this->singlepage("admin/order/confirm.html");
        }
    }

    public function getDefaultBranch($orderIds,$addr=''){
        $combineObj = kernel::single('omeauto_auto_combine');
        $branchPlugObj = new omeauto_auto_plugin_branch();
        $combinGroup = $combineObj->getOrderGroup($orderIds);
        foreach ($combinGroup as $key => $value) {
            $tmp = explode('||', $key);
            $groups[] = array('idx' => $tmp[1], 'hash' => $tmp[0], 'orders' => explode(',', $value['orders']));
        }

        $itemObjects = $combineObj->getItemObject($groups);
        $branch_id = array();
        foreach ($itemObjects as $key => $item) {
            $branchPlugObj->process($item);
            $branch_ids = $item->getBranchId();
            
            $branch_id[$key] = $branch_ids[0];
        }
        
        return $branch_id;
    }

    public function ajaxGetDefaultBranch(){
        $orderIds = json_decode($_POST['orders']);
        //error_log(var_export($_POST,true),3,__FILE__.".log");
        $combineObj = kernel::single('omeauto_auto_combine');
        $branchPlugObj = new omeauto_auto_plugin_branch();
        $combinGroup = $combineObj->getOrderGroup($orderIds);
        foreach ($combinGroup as $key => $value) {
            $tmp = explode('||', $key);
            $groups[] = array('idx' => $tmp[1], 'hash' => $tmp[0], 'orders' => explode(',', $value['orders']));
        }

        $itemObjects = $combineObj->getItemObject($groups);
        foreach ($itemObjects as $key => $item) {
            $branchPlugObj->process($item);
            $default_branch = $item->getBranchId();
            
            $branch_id[$key] = $default_branch[0];
        }
        
        foreach($branch_id as $value){
            
            $branchObj = &app::get('ome')->model('branch');
            //$branch = $branchObj->dump($value,'branch_id,name');
            $branch = $branchObj->db->selectrow("SELECT branch_id,name FROM sdb_ome_branch WHERE branch_id=".$value);
            
            if(is_array($branch) && $branch['branch_id']>0){
                break;
            }else{
                $branch = array('branch_id'=>0,'name'=>'');
            }
        }
        echo json_encode($branch);
    }

    private function getDefaultParseCorp($order) {
        $defaultExpress = array();
        $defaultExpressType = array();

        is_string($order['mark_text']) && $order['mark_text'] = unserialize($order['mark_text']);
        is_string($order['custom_mark']) && $order['custom_mark'] = unserialize($order['custom_mark']);

        $parseEC = new ome_parse_ec_parseEC();

        if (!empty($order['custom_mark'][0]['op_content'])) {
            $parseEC->setContent($order['custom_mark'][0]['op_content']);
            $defaultExpress = $parseEC->parse();
        }

        if (!empty($order['mark_text'][0]['op_content'])) {
            $parseEC->setContent($order['mark_text'][0]['op_content']);
            $md = $parseEC->parse();

            // 以客服为主
            if (!empty($md['yes'])) {
                $defaultExpress['yes'] = $md['yes'];
                $defaultExpress['no'] = $md['no'];
            }

            if (!empty($md['no'])) {
                $defaultExpress['no'] = $md['no'];
            }
        }

        if (is_array($defaultExpress)) {
            foreach ($defaultExpress as $yesOrNo => $express) {
                if (is_array($express)) {
                    foreach ($express as $ec) {
                        foreach ($ec as $eci) {
                            $defaultExpressType[$yesOrNo][] = $eci['type'];
                        }
                    }
                }
            }
        }

        //过滤掉重叠项
        foreach ($defaultExpressType['yes'] as $k => $yType) {
            if (in_array($yType, $defaultExpressType['no'])) {
                unset($defaultExpressType['yes'][$k]);
            }
        }

        if (isset($defaultExpressType['yes'])) {
            if (empty($defaultExpressType['yes'][0])) {
                $defaultExpressType['yes'] = '';
            } else {
                $defaultExpressType['yes'] = $defaultExpressType['yes'][0];
            }
        }

        return $defaultExpressType;
    }

    /**
     * 确认及通生成发货单
     *
     * @param void
     * @return void
     */
    function finish_combine() {

        $this->begin("index.php?app=ome&ctl=admin_order&act=do_confirm&p[0]=" . $_POST['order_id']);

        $act = $_POST['do_action'];
        if ($act == 4 || $act == 5) {
            //订单暂停或恢复
            if (empty($_POST['order_id'])) {
                $this->end(false, '没有要操作的订单！');
            }

            if ($act == 4) {
                //订单暂停
                $rs = app::get('ome')->model('orders')->pauseOrder($_POST['order_id']);
                if ($rs['rsp'] == 'succ') {
                    $this->end(true, '订单暂停成功');
                } else {
                    $this->end(true, '订单暂停失败');
                }
            } else {
                //订单恢复
                if (app::get('ome')->model('orders')->renewOrder($_POST['order_id'])) {
                    $this->end(true, '订单恢复成功');
                } else {
                    $this->end(true, '订单恢复失败');
                }
            }
        } else {
            //检查
            $orders = $_POST['orderIds'];
            $consignee = $_POST['consignee'];
            $logiId = $_POST['logi_id'];
            $consignee['memo'] = $_POST['delivery_remark'];
            
            #[拆单]同类型同货号商品暂时不支持拆单OR货到付款 ExBOY
            $splitting_product = $_POST['left_nums'];
            $is_repeat_product = $_POST['is_repeat_product'];
            
            $oDelivery      = &app::get('ome')->model('delivery');
            $split_seting   = $oDelivery->get_delivery_seting();

            if (empty($orders)) {
                $this->end(false, '你没有选择要操作的订单！');
            }

            if (empty($logiId)) {
                $this->end(false, '请选择快递公司！');
            }

            if (empty($consignee)) {
                $this->end(false, '没有配送地址信息！');
            }
            
            #[拆单]开启$split_seting判断  ExBOY
            if (empty($splitting_product) && $split_seting && $is_repeat_product != 'true')
            {
                $this->end(false,'无商品需要拆分');
            }

            $combineObj = kernel::single('omeauto_auto_combine');
            switch ($act) {
                case 1:
                case 2:
                    // 此方法不存在，目前没有2状态
                    $result = $combineObj->confirm($orders, $consignee);
                    break;
                case 3:
                    if($_POST['has_pro_gifts'] == 1){
                        $orderObj = &$this->app->model('orders');
                        if(count($orders) == 1){
                            $tmp_orderIds = $orders[0];
                        }else{
                            $tmp_orderIds = implode(",", $orders);
                        }
                        //异常状态有多种的时候直接异或可能导致无异常的订单会叠加异常状态to do
                        $orderObj->db->exec("update sdb_ome_orders set abnormal_status = (abnormal_status ^ ".ome_preprocess_const::__HASGIFT_CODE.") where (abnormal_status & ".ome_preprocess_const::__HASGIFT_CODE." = ".ome_preprocess_const::__HASGIFT_CODE.") and order_id in(".$tmp_orderIds.")");
                    }

                    $consignee['branch_id'] = $_POST['branch_id'];
                    //danny_freeze_stock_log
                    define('FRST_TRIGGER_OBJECT_TYPE','发货单：订单确认自动生成发货单');
                    define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：finish_combine');
                    
                    #[拆单]拆分的$splitting_product商品列表 ExBOY
                    $result = $combineObj->mkDelivery($orders, $consignee, $logiId, $splitting_product,true);
                    if (!$result) $this->end(false, '有订单状态发生变化无法完成此操作');
                    
                    #变更退货地址至订单里
                    $oOperation_log = &$this->app->model('operation_log');
                    $oOrder = $this->app->model('orders');
                    $oOrder_items = $this->app->model('order_items');
                    $orderBn_list = array();
                    foreach ($orders as $orderId) {
                        $oldOrder= $oOrder->dump($orderId,"*",array("order_objects"=>array("*",array("order_items"=>array('*')))));
                        $consignee_diff = array_diff_assoc($_POST['consignee'],$oldOrder['consignee']);
                        $orderBn_list[$orderId] = array('order_bn'=>$oldOrder['order_bn'], 'shop_id'=>$oldOrder['shop_id']);
                        if ($consignee_diff) {
                            #修改订单地址
                            $new_order['order_id']   = $orderId;
                            $new_order['consignee'] = $consignee_diff;
                            $oOrder->save($new_order);
                            $oOperation_log->write_log('order_edit@ome',$orderId,"修改订单收货地址");
                            $log_id = $oOperation_log->getList('log_id',array('operation'=>'order_edit@ome','obj_id'=>$orderId),0,1,'log_id DESC');
                            $log_id = $log_id[0]['log_id'];
                            
                            $oOrder->write_log_detail($log_id,$oldOrder);
                            //更新收货地址
                            kernel::single('ome_service_order')->update_shippinginfo($orderId);
                        }
                    }
                   
                    if (!$result) {
                        $this->end(false, '有订单状态发生变化无法完成此操作');
                    }
                    #全链路 已财审
                    $orderLib    = kernel::single("ome_order");
                    //$kafkaApiObj = kernel::single('ome_kafka_api');
                    $kafkaQueue  = app::get('ome')->model('kafka_queue');
                    
                    foreach ($orders as $useOrderId) {
                        #已财审
                        $orderLib->sendMessageProduce(2, $useOrderId);
                        #已通知配货
                        $orderLib->sendMessageProduce(3, $useOrderId);
                        #待配货
                        $orderLib->sendMessageProduce(4, $useOrderId);
                        ### 订单状态回传kafka august.yao 已审核 start ###
                        $queueData = array(
                            'queue_title' => '订单已审核状态推送',
                            'worker'      => 'ome_kafka_api.sendOrderStatus',
                            'start_time'  => time(),
                            'params'      => array(
                                'status'   => 'synced',
                                'order_bn' => $orderBn_list[$useOrderId]['order_bn'],
                                'logi_bn'  => '',
                                'shop_id'  => $orderBn_list[$useOrderId]['shop_id'],
                                'item_info'=> array(),
                                'bill_info'=> array(),
                            ),
                        );
                        $kafkaQueue->save($queueData);
                        //$kafkaApiObj->sendOrderStatus($orderBn_list[$useOrderId]['order_bn'], 'synced', array(), $orderBn_list[$useOrderId]['shop_id']);
                        ### 订单状态回传kafka august.yao 已审核 end ###
                    }
                    
                    break;
                default:
                    $this->end(false, '不正确的ACTION！');
                    break;
            }
        }
        $msg    = '订单处理成功';
        
        /*------------------------------------------------------ */
        //-- [拆单]单个订单“部分拆分”状态，跳转到拆分页面  ExBOY
        /*------------------------------------------------------ */
        if(count($_POST['orderIds']) == 1)
        {
            $order_id       = intval($_POST['orderIds'][0]);
            $oDelivery      = &app::get('ome')->model('delivery');
            $split_seting   = $oDelivery->get_delivery_seting();
            
            #开启拆单
            if($split_seting)
            {
                $oOrder = $this->app->model('orders');
                $oRow   = $oOrder->getlist('process_status', array('order_id'=>$order_id), 0, 1);
                
                //订单_部分拆分
                if($oRow[0]['process_status'] == 'splitting')
                {
                    $msg    = '订单拆分成功';
                }
            }
        }
        
        $this->end(true, $msg);
    }

    /**
     * 对待处理订单退回到暂存区
     */
    function order_buffer($flag){
		if($flag){
			$this->begin("index.php?app=ome&ctl=admin_order&act=confirm&flt=unmyown");
		}else{
			$this->begin("index.php?app=ome&ctl=admin_order&act=dispatch&flt=assigned");
		}
        $filter = $data = array();
        $data['group_id'] = null;
        $data['op_id'] = null;
        $data['process_status'] = 'unconfirmed';
        $data['confirm'] = 'N';
        $data['pause'] = 'false';
        if (is_array($_POST['order_id']) && count($_POST['order_id'])>0) {
            $filter['order_id'] = $_POST['order_id'];
            $filter['archive'] = 0;
            $filter['assigned'] = 'assigned';
            $filter['abnormal'] = 'false';
            $filter['is_fail'] = 'false';
            $filter['status'] = 'active';
            $filter['process_status'] = array('unconfirmed','confirmed');//禁止splitting部分拆分订单退回暂存区  ExBOY
        }elseif(isset($_POST['isSelectedAll'])&&$_POST['isSelectedAll']=='_ALL_'){//全选待处理订单
            $sub_menu = $this->_views_unmyown();
            if(isset($_POST['view'])){
                if(isset($sub_menu[$_POST['view']])){
                    $filter = $sub_menu[$_POST['view']]['filter'];
                    unset($_POST['view']);
                }
            }
            $filter = array_merge($filter,$_POST);
            unset($filter['app']);
            unset($filter['ctl']);
            unset($filter['act']);
            unset($filter['flt']);
            unset($filter['_finder']);
            $filter['archive'] = 0;
            $filter['assigned'] = 'assigned';
            $filter['abnormal'] = 'false';
            $filter['is_fail'] = 'false';
            $filter['status'] = 'active';
            $filter['process_status'] = array('unconfirmed','confirmed');//禁止splitting部分拆分订单退回暂存区  ExBOY
        }
        if(is_array($filter)&&count($filter)>0){
            $orderObj = &$this->app->model("orders");
            $logObj = &$this->app->model('operation_log');
            $logObj->batch_write_log('order_dispatch@ome','订单退回到暂存区',time(),$filter,$opinfo);
            $orderObj->filter_use_like = true;
            $orderObj->update($data,$filter);
        }
        $this->end(true, '订单处理成功'.$num);
    }

    /**
     * @对已经分派且没有被审核的订单进行收回操作
     * @access public
     * @param void
     * @return void
     */
    function order_recover(){
        //因为订单回收没有权限限定，所以单独调用order_goback来进行
        $this->order_goback();
    }
    /**
     * @对已经分派且没有被审核的订单进行退回/收回操作
     * @access public
     * @param void
     * @return void
     */
    function order_goback(){
        $order_id = $_POST['order_id'];
        if(empty($order_id)){
            #解决选定全部时,没有获取到数据的bug
            if($_POST['isSelectedAll'] == '_ALL_'){
                $base_filter['op_id'] = kernel::single('desktop_user')->get_id();
                $base_filter['assigned'] = 'assigned';
                $base_filter['abnormal'] = "false";
                $base_filter['is_fail'] = 'false';
                $base_filter['status'] = 'active';
                $base_filter['process_status'] = array('unconfirmed', 'confirmed', 'splitting');
                $base_filter['archive'] = 0;
                // 超级管理员
                if(kernel::single('desktop_user')->is_super()){
                    if(isset($base_filter['op_id']))
                        unset($base_filter['op_id']);
                
                    if(isset($base_filter['group_id']))
                        unset($base_filter['group_id']);
                }
                $_order_id = $this->app->model('orders')->getList('order_id',$base_filter);
                foreach($_order_id as $v){
                    $order_id[] = $v['order_id'];
                }
            }
        }
        
        if(is_array($order_id) && $order_id)
        {
            #过滤已经通过审核[拆单_部分拆分订单可重新分派] ExBOY
            $filter = array('order_id|in'=>$order_id, 'process_status'=>array('unconfirmed', 'splitting'));
            $order_info = $this->app->model('orders')->getList('order_bn,order_id, confirm, process_status, ship_status, pay_status',$filter);
            
            if($order_info[0]['ship_status'] == '3')
            {
                $this->pagedata['notice'] = '部分退货订单，不能退回未分派';
                $this->pagedata['error'] = true;
            }
            elseif($order_info[0]['process_status'] == 'splitting' && $order_info[0]['pay_status'] == '4')
            {
                $this->end(false,'部分退款订单，不能退回未分派');
            }
            elseif(($order_info[0]['confirm'] == 'N' && $order_info[0]['process_status'] == 'unconfirmed') || ($order_info[0]['process_status'] == 'splitting'))
            {
                
            }
            else 
            {
                $this->pagedata['notice'] = '所选订单已经通过审核，没有符合操作的订单';
                $this->pagedata['error'] = true;
            }
            //回收和退回判断
            if(isset($_GET['action']) && $_GET['action']=='recover'){
                 $this->pagedata['action'] = 'recover';
                 $this->pagedata['action_des'] = '回收';
            }else{
                 $this->pagedata['action_des'] = '退回';
            }
            $this->pagedata['order_info'] = $order_info;
        }else{
            $this->pagedata['notice'] = '没有符合操作的订单';
            $this->pagedata['error'] = true;
        }

        $this->display('admin/order/order_goback.html');
    }

    function do_order_goback(){

        $this->begin("");
        //获取操作的权限
        $permissionOjb = kernel::single('desktop_user');
        if($_POST['doaction']=='recover'){
            $act = '收回';
            $has_permission = $permissionOjb->has_permission('order_dispatch');
        }else{
            $act = '退回';
            $has_permission = $permissionOjb->has_permission('order_goback');
        }

        if(!$has_permission){
            $msg = '无权操作：'.$act;
            $this->end(false,$msg);
        }
        //必填信息验证
        $order_id = $_POST['order_id'];
        $remark   = $_POST['remark'];

        if(!is_array($order_id) || !$order_id){
            $this->end(false,'缺少订单号，请重试');
        }
        #$strlen = iconv_strlen($remark);
        if(!$remark){
            $this->end(false,'订单'.$act.'原因不能为空');
        }

        //过滤通过审核的订单[拆单_部分拆分订单可重新分派 ExBOY]
        $objOrder = $this->app->model('orders');
        $filter = array('order_id|in'=>$order_id, 'process_status'=>array('unconfirmed', 'splitting'));//ExBOY
        
        $order_info = $objOrder->getList('order_id, confirm, process_status, ship_status, pay_status',$filter);
        
        //执行退回操作
        $data = array(
                'group_id' => 0,
                'op_id'    => 0,
                'dispatch_time' =>NULL,
                );
        foreach($order_info as $row)
        {
            #逐个判断订单(排除部分发货、部分退款、部分退货) ExBOY
            if($row['ship_status'] == '3')
            {
                $this->end(false,'部分退货订单，不能退回未分派'.$act);
            }
            elseif($row['process_status'] == 'splitting' && $row['pay_status'] == '4')
            {
                $this->end(false,'部分退款订单，不能退回未分派'.$act);
            }
            elseif(($row['confirm'] == 'N' && $row['process_status'] == 'unconfirmed') || ($row['process_status'] == 'splitting'))
            {
                
            }
            else
            {
                $this->end(false,'订单已经审核不能'.$act);
            }
            
            #[拆单]部分拆分订单可重新分派 ExBOY
            if($row['confirm'] == 'N' && $row['process_status'] == 'unconfirmed')
            {
                $filter = array('order_id'=>$row['order_id'],'confirm'=>'N','process_status'=>'unconfirmed');
                $objOrder->goback($data,$filter,$remark,$act);
            }
            elseif($row['process_status'] == 'splitting' && $row['ship_status'] != '3')
            {
                $filter = array('order_id'=>$row['order_id'],'process_status'=>'splitting');
                $objOrder->goback($data,$filter,$remark,$act);
            }
            
            unset($filter);
        }
        $this->end(true,'订单'.$act.'成功');
    }

    function finish_confirm(){
        $oOrder = &$this->app->model("orders");
        $this->begin("index.php?app=ome&ctl=admin_order&act=do_confirm&p[0]=".$_POST['order_id']);

        #判断订单编辑同步状态
        $oOrder_sync = &app::get('ome')->model('order_sync_status');
        $sync_status = $oOrder_sync->getList('order_id,type,sync_status',array('order_id'=>$_POST['order_id']),0,1);
        if ($sync_status[0]['sync_status'] == '1'){
            $this->end(false, '订单编辑同步失败,无法确认生成发货单');
        }
        $region = $_POST['consignee'];
        list($package,$region_name,$region_id) = explode(':',$region['area']);
        if (!$region_id){
            $is_area = false;
            //非本地标准地区转换
            $area = $region['area'];
            $regionLib = kernel::single('eccommon_regions');
            $regionLib->region_validate($area);
            $is_correct_area = $regionLib->is_correct_region($area);
            if ($is_correct_area == true){
                 $is_area = true;
                 //更新地区字段
                 $order_update = array(
                   'order_id' => $_POST['order_id'],
                   'consignee' => array(
                       'area' => $area
                   ),
                 );
                 $oOrder->save($order_update);
            }
        }else{
            $is_area = true;
        }

        $action = explode("-",$_POST['do_action']);
        if(in_array(1,$action)){
            $order = $oOrder->dump($_POST['order_id'],'pause');
            if ($order['pause'] == 'true'){
                $this->end(false, '请先恢复订单' );
            }
            //订单确认
            if ($is_area == false){
                $this->end(false,'收货地区与系统不匹配，请编辑订单进行修改！');
            }
            $ret = $oOrder->confirm($_POST['order_id']);
            if(!$ret){
                $this->end(false,'该订单已不需要确认');
                return false;
            }
        }
        if(in_array(2,$action)){
            $order = $oOrder->dump($_POST['order_id'],'pause');
            if ($order['pause'] == 'true'){
                $this->end(false, '请先恢复订单' );
            }
            if ($order['process_status'] == 'cancel'){
                $this->end(false, '订单已取消，无法生成发货单' );
            }
            if ($is_area == false){
                $this->end(false,'收货地区与系统不匹配，请编辑订单进行修改！');
            }

            $_postdelivery = json_decode(urldecode($_POST['order_items']),true);
            $products = $_postdelivery['products'];
            $branch_id = $_postdelivery['branch_id'];
            $deliverys = array();
            $dlys = array();
            if ($products){
                foreach ($products as $pk=>$pv){
                    $item_id = $pv['itemid'];
                    $pv['item_id'] = $pv['itemid'];
                    $pv['order_id'] = $_POST['order_id'];
                    $dlys[$branch_id]['delivery_items'][$item_id] = $pv;
                    unset($pv['order_id'],$pv['itemid'],$pv['item_id']);
                    $deliverys[$branch_id]['delivery_items'][$item_id] = $pv;
                }
            }
            $deliverys[$branch_id]['branch_id'] = $branch_id;
            $deliverys[$branch_id]['logi_id'] = $_postdelivery['logi_id'];
            $_POST['delivery'] = $deliverys;
            unset($_postdelivery, $products, $deliverys, $_POST['order_items']);

            $pro_id = array();
            if($_POST['delivery']){
                $new_delivery = $_POST['delivery'];
                foreach($_POST['delivery'] as $branch_id=>$delivery){
                    if (empty($delivery['logi_id'])){
                        $this->end(false, '请选择物流公司');
                    }
                    $new_delivery_items = array();
                    if ($delivery['delivery_items']){
                        foreach($delivery['delivery_items'] as $item){
                            if ($new_delivery_items[$item['product_id']]){
                                $item['number'] += $new_delivery_items[$item['product_id']]['number'];
                                $new_delivery_items[$item['product_id']] = $item;
                            }else{
                                $new_delivery_items[$item['product_id']] = $item;
                            }
                        }

                        if(count($new_delivery_items) == 0){
                            unset($new_delivery[$branch_id]);
                        }else{
                            $new_delivery[$branch_id]['order_items'] = $dlys[$branch_id]['delivery_items'];
                            $new_delivery[$branch_id]['delivery_items'] = $new_delivery_items;
                            $new_delivery[$branch_id]['consignee'] = $_POST['consignee'];
                        }
                        $pro_id[$item['product_id']] += $new_delivery_items[$item['product_id']]['number'];
                    }
                }
            }
            $product = array();
            $name = array();
            $item_list = $oOrder->getItemBranchStore($_POST['order_id']);
            if ($item_list)
            foreach ($item_list as $il){
                if ($il)
                foreach ($il as $var){
                    if ($var)
                    foreach ($var['order_items'] as $v){
                        $name[$v['product_id']] = $v['name'];
                        $product[$v['product_id']] += $v['left_nums'];
                    }
                }
            }
            if ($product){
                foreach ($product as $id => $number){
                    if ($number < $pro_id[$id]){
                        $this->end(false, $name[$id].'：此商品已拆分完');
                        return ;
                    }
                }
            }
            //订单拆分，产生发货单
            $oOrder->mkDelivery($_POST['order_id'],$new_delivery);
            $item_list = $oOrder->getItemBranchStore($_POST['order_id']);
            if ($item_list)
            foreach ($item_list as $il){
                if ($il)
                foreach ($il as $var){
                    if ($var)
                    foreach ($var['order_items'] as $v){
                        if ($v['left_nums'] >0){
                            $this->end(true, '订单拆分成功');
                        }
                    }
                }
            }
            $this->end(true, '订单拆分完成');
        }
        if(in_array(4,$action)){
            //订单暂停
            $rs = $oOrder->pauseOrder($_POST['order_id']);
            if ($rs['rsp'] == 'succ'){
                $this->end(true, '订单暂停成功' );
            }else {
                $this->end(true, '订单暂停失败' );
            }
        }
        if(in_array(5,$action)){
            //订单恢复
            if ($oOrder->renewOrder($_POST['order_id'])){
                $this->end(true, '订单恢复成功');
            }else {
                $this->end(true, '订单恢复失败' );
            }
        }
        $this->end(true, '订单处理成功');
    }

    function abnormal(){
        $op_id = kernel::single('desktop_user')->get_id();
        $this->order_type = 'abnormal';
        $this->finder('ome_mdl_orders',array(
           'title'=>'异常订单',
           'base_filter'=>array('abnormal'=>'true','is_fail'=>'false','archive'=>0, 'process_status|noequal'=>'is_retrial', 'process_status|noequal'=>'is_declare'),//ExBOY
           'use_buildin_new_dialog' => false,
           'use_buildin_set_tag'=>false,
           'use_buildin_recycle'=>false,
           'use_buildin_export'=>false,
           'use_buildin_import'=>false,
           'use_buildin_filter'=>true,
            'finder_aliasname' => 'order_abnormal'.$op_id,
           'use_view_tab'=>true,
           'object_method'=>array('count'=>'countAbnormal','getlist'=>'getlistAbnormal')
        ));
    }

    function do_abnormal($order_id){
        $oAbnormal = &$this->app->model('abnormal');
        $oOrder = &$this->app->model('orders');
        $ordersdetail = $oOrder->dump(array('order_id'=>$order_id),"op_id,group_id");

        //组织分派所需的参数
        $this->pagedata['op_id'] = $ordersdetail['op_id'];
        $this->pagedata['group_id'] = $ordersdetail['group_id'];
        $this->pagedata['dt_begin'] = strtotime(date('Y-m-d',time()));
        $this->pagedata['dispatch_time'] = strtotime(date('Y-m-d',time()));

        if($_POST){
            $flt = $_POST['flt'];
            $origin_act = $_POST['origin_act']!='' ? $_POST['origin_act'] : 'confirm';
            if ($flt){
                //$this->begin("index.php?app=ome&ctl=admin_order&act=".$origin_act."&flt=".$flt);
                
            }else{
                //$this->begin("index.php?app=ome&ctl=admin_order&act=".$origin_act);
            }            $abnormal_data = $_POST['abnormal'];

            $oOrder->set_abnormal($abnormal_data);
            //danny_freeze_stock_log
            define('FRST_TRIGGER_OBJECT_TYPE','发货单：订单异常取消发货单');
            define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：do_abnormal');
            $rs = $oOrder->cancel_delivery($order_id);//取消发货单
           
           if ($rs['rsp'] == 'fail') {

                echo "<script>alert('订单异常取消发货单失败,原因是:".$rs['msg']."');</script>";

            }else{
                echo "<script>alert('设置异常成功');</script>";
            }
            echo "<script>$$('.dialog').getLast().retrieve('instance').close();window.finderGroup[$(document.body).getElement('input[name^=_finder\[finder_id\]]').value].refresh();</script>";
            
        }

        $abnormal = $oAbnormal->getList("*",array("order_id"=>$order_id));

        $oAbnormal_type = &$this->app->model('abnormal_type');

        $abnormal_type = $oAbnormal_type->getList("*");

        $abnormal[0]['abnormal_memo'] = unserialize($abnormal[0]['abnormal_memo']);
        $this->pagedata['abnormal'] = $abnormal[0];
        $this->pagedata['abnormal_type'] = $abnormal_type;
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['set_abnormal'] = true;
        $this->pagedata['flt'] = $_GET['flt'];
        $this->pagedata['origin_act'] = $_GET['origin_act'];
        $this->display("admin/order/detail_abnormal.html");
    }

    //状态冲突
    function conflict(){
        $this->finder('ome_mdl_orders',array(
           'title'=>'状态冲突',
           'base_filter'=>array('pay_status'=>'5','ship_status'=>'1','is_fail'=>'false'),
           'use_buildin_new_dialog' => false,
           'use_buildin_set_tag'=>false,
           'use_buildin_recycle'=>false,
           'use_buildin_export'=>false,
           'use_buildin_import'=>false,
           'use_buildin_filter'=>true,
        ));
    }

    function do_export() {
        $selected = $_POST['order_id'];
        $oOrder = &$this->app->model('orders');
        $isSelected = $_POST['isSelectedAll'];
        //如果是选择了全部
        if ($isSelected == '_ALL_') {

            $order_ids = $oOrder->getOrderId();

        } else {
            if ($selected)
                foreach ($selected as $order_id) {
                $temp_data = $oOrder->order_detail($order_id);
                $order_info = array();
                $order_info['order_id'] = $temp_data['order_id'];

                $export_data[] = $order_info;
            }

        }
    }

    function get_printable_orders($param) {
        $validator  = $this->app->model('validate');
        if (!$validator->valid()) {
            return "validate failed";
        }
        if (!$param['time_from'] && !$param['time_to']) {
            return array();
        }
        $payed = isset($param['payed'])?$param['payed']:1;
        $to_print = isset($param['to_print'])?$param['to_print']:1;
        $time_from = isset($param['time_from'])?$param['time_from']:0;
        $time_to = isset($param['time_to'])?$param['time_to']:time();
        $page = isset($param['page'])?$param['page']:1;
        $limit = isset($param['limit'])?$param['limit']:1;

        $oOrder = $this->app->model('orders');
        $sql = 'SELECT `order_id` from sdb_ome_orders WHERE 1';
        if ($payed) {
            $sql .= ' and `pay_status`=1';
        }
        if ($to_print) {
            $sql .= ' and `print_finish` = \'true\'';
        }
        if ($time_from) {
            $sql .= " and `createtime`> '$time_from'";
        }
        if ($time_to) {
            $sql .= " and `createtime`< '$time_to'";
        }
        $sql .= " limit ".($page-1)*$limit.','.$limit;
        $order_ids = kernel::database()->db->select($sql);
        $return = array();
        foreach ($order_ids as $orderinfo) {
            $return[] = $oOrder->dump($orderinfo['order_id']);
        }
        //记录日志
        return $return;
    }

     /*
    * 查看售后服务对应日志记录
    */

    function show_aftersale_log($return_id){
        $opObj = $this->app->model('operation_log');
        $log = $opObj->read_log(array('obj_id'=>$return_id,'obj_type'=>'return_product@ome'));

        $this->pagedata['log'] = $log;
        $this->display("admin/order/aftersale_log.html");
    }

    /*
     * 追加备注 append_memo
     */

    function append_memo(){

        $Orders = &$this->app->model('orders');
        $orders['order_id'] = $_POST['order']['order_id'];
        if ($_POST['oldmemo']){
            $oldmemo = $_POST['oldmemo'].'<br/>';
        }
        $memo  = $oldmemo.$_POST['order']['mark_text'].'  &nbsp;&nbsp;('.date('Y-m-d H:i:s',time()).' by '.kernel::single('desktop_user')->get_name().')';
        $orders['mark_text'] = $memo;
        $Orders->save($orders);
        echo $memo;
    }

    function view_edit($order_id){

        $oOrder = &$this->app->model('orders');
        $order = $oOrder->dump($order_id);
        $branch_list = $oOrder->getBranchByOrder(array($order_id));
        
        //增加复审订单process_status判断[ExBOY 2014.06.09]
        if($order['process_status'] == 'is_retrial' && $order['pause'] == 'false')
        {
            $edit_order['order_id']  = $order_id;
            $edit_order['pause']     = 'true';
            $oOrder->save($edit_order);
            
            $order['pause']    = 'true';
        }
        elseif($order['process_status'] == 'is_retrial')
        {
            #判断"复审订单" 并且是 "待复审状态"，将不允许编辑提交 ExBOY
            $oRetrial       = &app::get('ome')->model('order_retrial');
            $retrial_row    = $oRetrial->getList('*', array('order_id'=>$order_id, 'status'=>'0'), 0, 1);
            if(!empty($retrial_row))
            {
                header("content-type:text/html; charset=utf-8");
                echo "<script>alert('订单号：".$order['order_bn']." 待复审中，请先审核!');window.close();</script>";
                exit;
            }
        }
        
        if ($order['pause'] == 'false'){
            exit('请先暂停订单');
        }
        if ($order['process_status'] == 'cancel'){
            exit('订单已取消，无法再编辑订单');
        }

        #[拆单]部分拆分订单,获取发货单及订单已拆分数量  ExBOY
        $dlyObj         = &app::get('ome')->model('delivery');//拆单配置
        $split_seting   = $dlyObj->get_delivery_seting();
        
        if($split_seting && $order['process_status'] == 'splitting')
        {
            $temp_data      = array();
            
            //仓库列表
            $sql           = "SELECT branch_id, name FROM sdb_ome_branch";
            $temp_data     = kernel::database()->select($sql);
            foreach ($temp_data as $key => $val)
            {
                $dly_branch[$val['branch_id']] = $val['name'];
            }
            
            //发货单据列表
            $delivery_list  = $delivery_ids = array();
            $sql    = "SELECT d.delivery_id, d.delivery_bn, d.parent_id, d.logi_no, d.logi_name, d.branch_id, d.status, d.is_bind, d.create_time 
                        FROM sdb_ome_delivery_order AS dord LEFT JOIN sdb_ome_delivery AS d ON(dord.delivery_id=d.delivery_id) 
                        WHERE dord.order_id='".$order_id."' AND d.is_bind='false' AND d.disabled='false' AND d.status NOT IN('failed','cancel','back','return_back')";
            
            $delivery_list  = kernel::database()->select($sql);
            $status_text    = array ('succ' => '已发货','failed' => '发货失败','cancel' => '已取消','progress' => '等待配货', 
                                    'timeout' => '超时','ready' => '等待配货','stop' => '暂停','back' => '打回');
            foreach($delivery_list as $k => $v)
            {
                $delivery_list[$k]['branch_name']   = $dly_branch[$v['branch_id']];
                $delivery_list[$k]['status']        = $status_text[$v['status']];
                $delivery_list[$k]['create_time']   = date('Y-m-d H:i:s', $v['create_time']);
                $delivery_ids[]     = $v['delivery_id'];
            }
            $this->pagedata['delivery_list']    = $delivery_list;
        }
        
        $item_list = $oOrder->getItemBranchStore($order_id);

        $combineobj = kernel::single('omeauto_auto_combine');
        $combineOrders = $combineobj->fetchCombineOrder($order);

        if(!preg_match("/^mainland:/", $order['consignee']['area'])){
            $region='';
            $newregion='';
            foreach(explode("/",$order['consignee']['area']) as $k=>$v){
                $region.=$v.' ';
            }
        }else{
            $newregion = $order['consignee']['area'];
        }

        $this->pagedata['region'] = $region;
        $this->pagedata['newregion'] = $newregion;
        $this->pagedata['order_id'] = $order_id;
        $order['custom_mark'] = unserialize($order['custom_mark']);
        if ($order['custom_mark'])
        foreach ($order['custom_mark'] as $k=>$v){
            if (!strstr($v['op_time'], "-")){
                $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                $order['custom_mark'][$k]['op_time'] = $v['op_time'];
            }
        }
        $order['mark_text'] = unserialize($order['mark_text']);
        if ($order['mark_text'])
        foreach ($order['mark_text'] as $k=>$v){
            if (!strstr($v['op_time'], "-")){
                $v['op_time'] = date('Y-m-d H:i:s',$v['op_time']);
                $order['mark_text'][$k]['op_time'] = $v['op_time'];
            }
        }

        if(app::get('omepkg')->is_installed()){
            $flag = true;
        }else{
            $flag = false;

        }

        //订单代销人会员信息
        $oSellagent = &app::get('ome')->model('order_selling_agent');
        $sellagent_detail = $oSellagent->dump(array('order_id'=>$order_id));
        if (!empty($sellagent_detail)){
            $this->pagedata['sellagent'] = $sellagent_detail;
        }
        //发货人信息
        $order_consigner = false;
        if ($order['consigner']){
            foreach ($order['consigner'] as $shipper){
                if (!empty($shipper)){
                    $order_consigner = true;
                    break;
                }
            }
        }
        $oShop = &app::get('ome')->model('shop');
        $shop_detail = $oShop->dump(array('shop_id'=>$order['shop_id']));
        $b2b_shop_list = ome_shop_type::b2b_shop_list();
        if (in_array($shop_detail['node_type'], $b2b_shop_list)){
            $this->pagedata['b2b'] = true;
        }

        //购买人信息
        $memberObj = &app::get('ome')->model('members');
        $members_detail = $memberObj->dump($order['member_id']);

        $this->pagedata['order'] = $order;
        $this->pagedata['member'] = $members_detail;
        ome_order_func::order_sdf_extend($item_list);
        $obj_config = array();
        if ($servicelist = kernel::servicelist('ome.service.order.edit'))
        foreach ($servicelist as $obj =>$instance){
            if (method_exists($instance,'config_list')){
                $tmp_conf = $instance->config_list();
                $obj_config = array_merge($obj_config,empty($tmp_conf)?array():$tmp_conf);
            }
        }
        foreach ($item_list as $obj => $idata){
            
            #[拆单]计算订单商品已拆分数量 ExBOY
            foreach ($idata as $obj_id => $ordObj_list)
            {
                if($ordObj_list['obj_type'] == 'pkg' || $ordObj_list['obj_type'] == 'giftpackage')
                {
                    $idata[$obj_id]['make_nums']   = intval($ordObj_list['quantity'] - $ordObj_list['left_nums']);
                }
                
                foreach ($ordObj_list['order_items'] as $item_id => $ordItem_list)
                {
                    $idata[$obj_id]['order_items'][$item_id]['make_nums']   = intval($ordItem_list['nums'] - $ordItem_list['left_nums']);
                }
            }
            
            if (isset($obj_config[$obj])){
                $obj_config[$obj]['load'] = true;
                $obj_config[$obj]['objs'] = $idata;
            }else {
                $obj_config[$obj] = $obj_config['goods'];
                $obj_config[$obj]['load']   = true;
                $obj_config[$obj]['is_add'] = false;
                $obj_config[$obj]['objs']   = $idata;
            }
        }

        //商品销售价和优惠价格添加2012-6-18
        $price_filter = array('order_id'=>$order_id);
        
        $pmt_prices   = $dlyObj->getPmt_price($price_filter);
        $sale_prices  = $dlyObj->getsale_price($price_filter);
        foreach ($obj_config['goods']['objs'] as &$obj_items) {
            $obj_items['pmt_price']  = $pmt_prices[$obj_items['bn']]['price'];
            $obj_items['sale_price'] = $sale_prices[$obj_items['bn']]['price'];
        }

        if ($flag==false) {
            if (isset($obj_config['pkg'])){
                $obj_config['pkg']['is_add'] = false;
            }
        }
        $conf_list = array();
        if ($obj_config)
        foreach ($obj_config as $name => $conf){
            if ($conf['load']==true) {
                $conf_list[$name] = $conf;
                continue;
            }else if($conf['is_add']==true){
                $conf_list[$name] = $conf;
                $conf_list[$name]['load'] = true;
            }
        }
        $is_super = kernel::single('desktop_user')->is_super();
        #如果不是超级管理员不走这一步
        if(!$is_super){
            #获取网站操作人员id
            $get_id = kernel::single('desktop_user')->get_id();
            #根据操作人员id，获取所的角色
            $role = app::get('desktop')->model('hasrole')->getList('role_id',array('user_id'=>$get_id));
            $role_obj = app::get('desktop')->model('roles');
            $this->pagedata['order_confirm'] = false;
            foreach($role as $v){
                $workgroud = $role_obj->dump(array('role_id'=>$v),'workground');
                $workgroud = unserialize($workgroud['workground']);;
                #检测角色中是否包含审单权限
                if(array_search('order_confirm', $workgroud) !== false){
                    $this->pagedata['order_confirm'] = true;
                    break;
                }
            }
        }else{
            $this->pagedata['order_confirm'] = true;
        }
        //ksort($conf_list);
        $this->pagedata['conf_list'] = $conf_list;
        $this->pagedata['item_list_log'] = base64_encode(serialize($item_list));
        $this->pagedata['item_list'] = $item_list;
        $this->pagedata['branch_list'] = $branch_list;
        $this->pagedata['combineOrders'] = $combineOrders;

        $tbgiftOrderItemsObj = &app::get('ome')->model('tbgift_order_items');
        $tmp_tbgifts = $tbgiftOrderItemsObj->getList('*',array('order_id'=>$order_id),0,-1);
        $this->pagedata['tbgifts'] = $tmp_tbgifts;

        #是否开启复审及复审规则  ExBOY
        $setting_retrial    = $this->get_setting_retrial();
        $this->pagedata['retrial_order']    = $setting_retrial['is_retrial'];
        
        #[拆单]部分拆分订单,调用单独编辑模板 ExBOY
        if(!empty($delivery_list))
        {
            $this->singlepage("admin/order/order_edit_split.html");
        }
        else
        {
            $this->singlepage("admin/order/order_edit.html");
        }
    }

    /**
     * 余单撤消
     */
    function remain_order_cancel(){

        if ($_POST['remain_order_cancel'] == 'do'){

            $order_id = intval($_POST['order_id']);
            $this->begin("index.php?app=ome&ctl=admin_order&act=do_confirm&p[0]=".$order_id);

            $reback_price = $_POST['refund_money'];//退款金额
            $revock_price = $_POST['revock_price'];//撤销商品总额
            $result = kernel::single('ome_order_order')->order_revoke($order_id,$reback_price,$revock_price);
            if ($result != true){
                $result = false;
                $msg = '失败';
            }else{
                $result = true;
                $msg = '成功';
            }
            $this->end($result, app::get('base')->_("余单撤消".$msg));
        }
    }

    /*
     * 显示余单撤消确认页面
     * @param string $order_id 订单号
     * @return 确认页面
     */

    function remain_order_cancel_confirm(){

       $order_id = intval($_GET['order_id']);
       $oOrder = &$this->app->model("orders");
       $oRefund = &$this->app->model("refunds");
       $order = $oOrder->dump($order_id, '*');
       $diff_price = kernel::single('ome_order_func')->order_items_diff_money($order_id);
        if ($order['process_status'] == 'remain_canlel')
            die('未发货商品总额为0,无法再次撤销！');
        
        /*[拆单]开启余单撤消金额为0可以申请退款  ExBOY
        if (!$diff_price)
            die('未发货商品总额为0,无法再次撤销！');
        */
       
       $order['diff_price'] = $diff_price;
       if ($order['payed'] > $diff_price){
           $refund_money = $diff_price;
       }else{
           $refund_money = $order['payed'];
       }
       //已退款金额
       $refunds = $oRefund->getList('money', array('order_id'=>$order_id), 0, -1);
       $refunded = '0';
       if ($refunds){
           foreach ($refunds as $refund_val){
               $refunded += $refund_val['money'];
           }
       }
       $order['refunded'] = $refunded;
       //商品明细
       $item_list = $oOrder->getItemBranchStore($order_id);
       ome_order_func::order_sdf_extend($item_list);
       $this->pagedata['item_list'] = $item_list;

       $order['refund_money'] = $refund_money;
       $this->pagedata['order'] = $order;
       
       /*------------------------------------------------------ */
       //-- [拆单]获取未发货的发货单记录  ExBOY
       /*------------------------------------------------------ */
       $oDelivery       = &app::get('ome')->model('delivery');
       $delivery_ids    = $oDelivery->getDeliverIdByOrderId($order_id);
       
       #[未发货]发货单详情
       if(!empty($delivery_ids))
       {
           $cols        = 'delivery_id, delivery_bn, is_cod, logi_id, logi_no, status, branch_id, 
                                 stock_status, deliv_status, expre_status, verify, process, logi_name';
           $filter      = array('delivery_id'=>$delivery_ids, 'process'=>'false');
           $dly_data    = $oDelivery->getList($cols, $filter, 0, -1);
           
           $status_text = array ('succ' => '已发货','failed' => '发货失败','cancel' => '已取消','progress' => '等待配货', 
                            'timeout' => '超时','ready' => '等待配货','stop' => '暂停','back' => '打回');
           $status_type = array('true'=>'是', 'false'=>'否');
           
           $delivery_list   = array();
           foreach ($dly_data as $key => $val)
           {
               $val['status']      = $status_text[$val['status']];//发货状态
               $val['is_cod']      = $status_type[$val['is_cod']];
               $val['verify']      = $status_type[$val['verify']];
               
               $delivery_list[]     = $val;
           }
           
           if(!empty($delivery_list))
           {
               $this->pagedata['delivery_list']  = $delivery_list;
               $this->pagedata['delivery_flag']  = 'true';
           }
       }
       
       /*------------------------------------------------------ */
       //-- [拆单]退款&&退换货记录  ExBOY
       /*------------------------------------------------------ */
       if(in_array($order['pay_status'], array('4', '5', '6', '7')))
       {
           $orderItemObj   = &app::get('ome')->model('order_items');
           $oReship        = &app::get('ome')->model('reship');
           $oRefund_apply  = &app::get('ome')->model('refund_apply');
           
           //退换货记录
           $status_text    = $oReship->is_check;
           
           $sql       = "SELECT r.reship_bn, r.status, r.is_check, r.tmoney, r.return_id, i.* 
                     FROM sdb_ome_reship as r left join sdb_ome_reship_items as i on r.reship_id=i.reship_id 
                     WHERE r.order_id='".$order_id."' AND r.return_type in('return', 'change') AND r.is_check!='5'";
           $reship_list    = kernel::database()->select($sql);
           if($reship_list)
           {
               $temp_bn  = array();
               foreach ($reship_list as $key => $val)
               {
                   $val['return_type_name']    = ($val['return_type'] == 'return' ? '退货' : '换货');
                   $val['type_name']           = $status_text[$val['is_check']];
                   $val['addon']          = '-';//规格
                   
                   //存储货号查询规格
                   $temp_bn[]        = $val['product_id'];
                   
                   $reship_list[$key]  = $val;
               }
               
               $temp_items = array();
               $temp_addon = $orderItemObj->getList('product_id, addon', array('order_id'=>$order_id, 'product_id'=>$temp_bn));
               foreach ($temp_addon as $key => $val)
               {
                   if($val['addon'])
                   {
                       $temp_items[$val['product_id']] = ome_order_func::format_order_items_addon($val['addon']);;
                    }
               }               
               if($temp_addon)
               {
                   foreach ($reship_list as $key => $val)
                   {
                        $product_id = $val['product_id'];
                        
                        if($temp_items[$product_id])
                        {
                            $val['addon']       = $temp_items[$product_id];
                        }
                        $reship_list[$key]      = $val;
                    }
                }
                unset($temp_bn, $temp_addon, $temp_items);
            }
            $this->pagedata['reship_list'] = $reship_list;
           
           //退款记录
           $refund_apply   = $oRefund_apply->getList('apply_id, refund_apply_bn, money, refunded, create_time, last_modified, status, return_id', 
                                   array('order_id'=>$order_id, 'disabled'=>'false'));
           if($refund_apply){
               foreach($refund_apply as $k=>$v){
                   $refund_apply[$k]['status_text'] = ome_refund_func::refund_apply_status_name($v['status']);
               }
           }
           $this->pagedata['refund_apply'] = $refund_apply;
           $this->pagedata['is_cancel']    = true;
       }
       
       $this->singlepage('admin/order/remain_order_cancel.html');
    }

    /*
     * 余单撤消退款
     * @param string $order_id 订单号
     * @param string $refund_money 退款金额
     * @return 退款窗口
     */

    function remain_order_cancel_refund($order_id='',$refund_money='0'){

        $objOrder = &$this->app->model('orders');
        if ($_POST){
            $this->begin('');
            $orderdata = $objOrder->order_detail($_POST['order_id']);
            if($_POST['refund_money'] > $orderdata['payed'] ){
                $this->end(false,'退款金额不能大于剩余金额');
            }else{
                $return = kernel::single('ome_refund_apply')->refund_apply_add($_POST);
                if ($return['result'] == true){
                    $result  = true;
                }else{
                    $result = false;
                }
                $msg = $return['msg'];
                $this->end($result, app::get('base')->_($msg));
            }
        }else{
            $order = $objOrder->order_detail($order_id);
            $addon['from'] = 'remain_order_cancel';
            $result = kernel::single('ome_refund_apply')->show_refund_html($order_id, '', $refund_money, $addon);
            if ($result['result'] == true){
                return $result;
            }else{
                exit($result['msg']);
            }
        }
    }

    function finish_edit(){
        #[拆单]部分拆分订单,获取发货单及订单已拆分数量  ExBOY
        $dlyObj         = &app::get('ome')->model('delivery');//拆单配置
        $split_seting   = $dlyObj->get_delivery_seting();
        
        $oOrder = &$this->app->model("orders");
        $oShop  = &$this->app->model('shop');

        $order_id       = $_POST['order_id'];
        $order          = $oOrder->dump($order_id);
        $shop_detail    = $oShop->dump(array('shop_id'=>$order['shop_id']), 'node_type');
        $node_type      = $shop_detail['node_type'];

        $is_cost_shipping_chaning = false;
        
        //[ExBOY]订单复审_原始订单信息
        $old_order      = $order;
        
        #判断"复审订单" 并且是 "待复审状态"，将不允许编辑提交 ExBOY
        if($order['process_status'] == 'is_retrial')
        {
           $oRetrial    = &app::get('ome')->model('order_retrial');
           $retrial_row = $oRetrial->getList('*', array('order_id'=>$order_id, 'status'=>'0'), 0, 1);
           
           if(!empty($retrial_row))
           {
               header("content-type:text/html; charset=utf-8");
               echo "<script>alert('订单号：".$order['order_bn']." 待复审中，请先审核!');window.close();</script>";
               exit;
           }
        }
        
        if($_POST['cost_shipping'] == 0){}

        #检测编辑前后，配送费用是否发生了改变
        if( $order['shipping']['cost_shipping'] != $_POST['cost_shipping'] ){
            if($_POST['cost_shipping'] !=='0'){
                #验证配送费用是否是正数
                $re = kernel::single('ome_goods_product')->valiPositive($_POST['cost_shipping']);
                if($re == false){
                    #再次排除类似0.0这种特殊数据
                    if(!preg_match('/^0\.[0]{1,}$/',$_POST['cost_shipping'],$arr)){
                        $this->begin('index.php?app=ome&ctl=admin_order&act=index');
                        $this->end(false, '请录入大于等于0的数值');
                    }
                }
            }
            $is_cost_shipping_chaning = true;
            $order['shipping']['cost_shipping'] = $_POST['cost_shipping'];
        }

        //B2B检测是否允许编辑该订单
        $b2b_shop = ome_shop_type::b2b_shop_list();
        if (in_array($node_type, $b2b_shop)){
            $allow_edit = true;
            if ($allow_edit_service = kernel::service('ome.order.edit')){
                $error = '';
                if(method_exists($allow_edit_service, 'is_allow_edit')){
                    $order_edit_info = array();
                    $order_edit_info['bn'] = $_POST['bn_list'];
                    $order_edit_info['shop_id'] = $_POST['shop_id'];
                    $allow_edit = $allow_edit_service->is_allow_edit($order_edit_info, $error);
                }
            }
            if (!$allow_edit){
                $this->begin('');
                if (empty($error))
                    $error = '保存失败';
                $this->end(false, $error);
            }
        }

        if ($_POST['do_action'] != 0){
            //操作
            $this->begin('');
            //$this->begin("index.php?app=ome&ctl=admin_order&act=view_edit&p[0]=".$_POST['order_id']);

            $pObj           = &$this->app->model("products");
            $oSpec          = &$this->app->model('specification');
            $oOrderItm      = &$this->app->model("order_items");
            $oOrderObj      = &$this->app->model("order_objects");
            $oSpecvalue     = &$this->app->model('spec_values');
            $oOperation_log = &$this->app->model('operation_log');
            $obj_orders_extend = &$this->app->model('order_extend');

            $post = $_POST;
            if ($post['do_action'] != 2){
                if ($order['pause'] == 'false'){
                    $this->end(false, "请先暂停订单");
                }
            }
            $is_address_change  = false;//地址是否变更
            $is_order_change    = false;//是否需要修改
            $is_goods_modify    = false;//是否编辑过商品
            $is_consigner_change = false;
			$is_tax_change  = false;//zjr
			
			$order['tax']['tax_no']=$order['tax_no'];
			$order['tax']['tax_title']=$order['tax_title'];
			$order['tax']['invoice_name']=$order['invoice_name'];
			$order['tax']['invoice_area']=$order['invoice_area'];
			$order['tax']['invoice_contact']=$order['invoice_contact'];
			$order['tax']['invoice_addr']=$order['invoice_addr'];
			$order['tax']['invoice_zip']=$order['invoice_zip'];
			$z_tax= array_diff_assoc($post['tax'],$order['tax']);
			 if (!empty($z_tax)){
                $is_tax_change = true;
            }

			if($post['taxpayer_identity_number']!=$order['taxpayer_identity_number']){
				$oOrder->update(array('taxpayer_identity_number'=>$post['taxpayer_identity_number']),array('order_id'=>$order_id));
			}

            //收货人信息
            $consignee = array_diff_assoc($post['order']['consignee'],$order['consignee']);
            //发货人信息
            $consigner = array_diff_assoc((array)$post['order']['consigner'],(array)$order['consigner']);
            if (!empty($consigner)){
                $is_consigner_change = true;
            }
            if (!empty($consignee)){
                $is_address_change = true;
                $extend_data['order_id'] = $order_id;
                $extend_data['extend_status'] = 'consignee_modified';
                #记录地址发生变更的扩展
                $obj_orders_extend->save($extend_data);
            }
            $goods      = $post['goods'];
            $pkg        = $post['pkg'];
            $gift       = $post['gift'];
            $giftpkg    = $post['giftpkg'];

            $objtype = $post['objtype'];

            if (empty($goods) && empty($pkg) && empty($gift) && empty($giftpkg)){
                $this->end(false, "订单不能没有商品");
            }

            $new_order = $post['order'];
            $total = 0;
            //danny_freeze_stock_log
            //define('FRST_OPER_ID','0');
            //define('FRST_OPER_NAME','');
            define('FRST_TRIGGER_OBJECT_TYPE','订单：订单编辑叫回发货单并重新计算订单商品冻结');
            define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：rebackDeliveryByOrderId');
            if ($objtype && is_array($objtype)){
                //danny_freeze_stock_log
                $GLOBALS['frst_shop_id'] = $order['shop_id'];
                $GLOBALS['frst_shop_type'] = $order['shop_type'];
                $GLOBALS['frst_order_bn'] = $order['order_bn'];
                $rs = kernel::single("ome_order_edit")->process_order_objtype($objtype,$post);
            }else {
                $this->end(false, "订单不能没有商品");
            }
            $obj    = $rs['obj'];
            $new    = $rs['new'];
            $total  = $rs['total'];
            $pmt_goods = $rs['total_pmt_goods'];

            $is_order_change = $rs['is_order_change'];
            $is_goods_modify = $rs['is_goods_modify'];
            ; //是否编辑过商品
            //修改订单折扣金额
            if (strval($order['discount']) != strval($post['discount'])){
                $is_order_change = true;
            }

            if ($is_tax_change==true||$is_order_change == true || $is_address_change == true || $is_consigner_change == true ||$is_cost_shipping_chaning==true){
                //打回已存在的发货单(只打回未发货的发货单 ExBOY)
                $oOrder->rebackDeliveryByOrderId($order_id, true);
                
                #[拆单]打回发货单后_重载订单确认状态 ExBOY
                if(!empty($split_seting))
                {
                    $order          = $oOrder->dump($order_id);
                }

                $objMath    = kernel::single('eccommon_math');
                $pro_total  = $order['cost_item'];
                $diff       = $objMath->number_minus(array($total, $pro_total));
                $discount   = strval($post['discount']);

                $new_order['order_id']      = $order_id;
                $new_order['cost_item']     = $total;
                $new_order['pmt_goods']     = $pmt_goods;
                $new_order['shipping']['cost_shipping'] = $order['shipping']['cost_shipping'];
                $new_order['total_amount']  = $objMath->number_plus(array($total,$order['shipping']['cost_shipping'],$order['shipping']['cost_protect'],$order['cost_tax'],$order['payinfo']['cost_payment']));
                $new_order['total_amount']  = $objMath->number_minus(array($new_order['total_amount'],$pmt_goods,$order['pmt_order']));
                $new_order['total_amount']  = $objMath->number_plus(array($new_order['total_amount'],$discount));
                $new_order['discount']      = $discount;
                $new_order['cur_amount'] = $new_order['total_amount'];

                if ($new_order['total_amount'] < 0){
                    $this->end(false, "订单折扣金额输入有误");
                }
                if ($consignee)
                    $new_order['consignee'] = $consignee;
                if ($consigner)
                    $new_order['consigner'] = $consigner;

                if ($is_goods_modify == true){
                    $new_order['is_modify'] = 'true';
                }
                $new_order['old_amount']     = $order['total_amount'];
                $new_order['confirm']        = 'N';
                $new_order['process_status'] = 'unconfirmed';
                $new_order['pause']          = 'false';
                
                #[拆单]部分拆分订单后确认状态设定 ExBOY
                if(!empty($split_seting) && $order['process_status'] == 'splitting')
                {
                    $get_delivery   = $dlyObj->getDeliverIdByOrderId($order_id);//获取已发货的发货单
                    if(!empty($get_delivery))
                    {
                        $new_order['process_status'] = 'splitting';
                        $old_order['process_status'] = $new_order['process_status'];//复审时保存状态
                    }
                }
                
                $oOperation_log->write_log('order_edit@ome',$_POST['order_id'],"订单修改并恢复");
                
                //将未修改以前的数据存储以便查询
                if($is_address_change ==true || $is_goods_modify == true || $is_order_change == true||$is_cost_shipping_chaning==true){
                    $log_id = $oOperation_log->getList('log_id',array('operation'=>'order_edit@ome','obj_id'=>$_POST['order_id']),0,1,'log_id DESC');
                    $log_id = $log_id[0]['log_id'];
                    $_POST['item_list'] = unserialize(base64_decode($_POST['item_list']));
                    $this->app->model('orders')->write_log_detail($log_id,$_POST);
                }
                /*
                 * 获取订单复审配置
                 * Author: ExBOY
                 * Timer: 2014.05.15
                 */
                $oRetrial   = &app::get('ome')->model('order_retrial');
                
                #[修改前]订单商品明细
                $order_item_list    = $oOrder->getItemBranchStore($order_id);
                
                $old_order['is_goods_modify']   = $is_goods_modify;
                $old_order['is_order_change']   = $is_order_change;
                $old_order['is_address_change'] = $is_address_change;
                $old_order['is_consigner_change'] = $is_consigner_change;
                
                $old_order['item_list']     = $order_item_list;//$_POST['item_list'];
                $old_order['kefu_remarks']  = addslashes($_POST['kefu_remarks']);
                
                $retrial_id = $oRetrial->add_retrial($old_order);
                
                #[ExBOY设置 ]订单复审_process_status状态，并设为异常订单
                if($retrial_id)
                {
                    $new_order['process_status']    = 'is_retrial';//复审状态
                    $new_order['abnormal']          = 'true';
                    $new_order['pause']             = 'true';//订单暂停
                }
				if(!empty($z_tax)){
					$new_order=array_merge($new_order,$z_tax);
				}
               // echo "<pre>";print_r($new_order);print_r($z_tax);exit();
                //更新order
                $oOrder->save($new_order);
                

                //调用公共方法更改订单支付状态(货到付款订单不进行支付状态的变更)
                if ($order['shipping']['is_cod'] != 'true') {
                    kernel::single('ome_order_func')->update_order_pay_status($order_id);
                }
                #货到付款订单,需要编辑下应收金额
                if($order['shipping']['is_cod'] == 'true' && $order['source'] != 'matrix'){
                    $oObj_orextend = &$this->app->model("order_extend");
                    $code_data = array('order_id'=>$order_id,'receivable'=>$new_order['total_amount']);
                    $oObj_orextend->save($code_data);
                }
                if ($is_order_change == true){
                    //更新order_objects,order_items
                    foreach ($obj as $k => $o){
                        $tmp = array();
                        $tmp = $o['items'];
                        unset($o['items']);

                        $oOrderObj->save($o);
                        foreach ($tmp as $oo){
                            $oOrderItm->save($oo);
                        }
                    }
                    if ($new)
                    foreach ($new as $ao){
                        //新增新的object
                        $tmp = array();
                        $tmp = $ao['items'];
                        unset($ao['items']);

                        $oOrderObj->save($ao);
                        foreach ($tmp as $aoo){
                            //新增新的item
                            $aoo['obj_id'] = $ao['obj_id'];

                            $product_id = $aoo['product_id'];
                            $product_info = $pObj->dump(array('product_id'=>$product_id),"spec_desc");
                            $spec_desc = $product_info['spec_desc'];
                            $productattr = array(); $product_attr = array();
                            if ($spec_desc['spec_value_id'])
                            foreach ($spec_desc['spec_value_id'] as $sk=>$sv){
                                 $tmp = array();
                                 $specval = $oSpecvalue->dump($sv,"spec_value,spec_id");
                                 $tmp['value'] = $specval['spec_value'];
                                 $spec = $oSpec->dump($specval['spec_id'],"spec_name");
                                 $tmp['label'] = $spec['spec_name'];
                                 $productattr[] = $tmp;
                            }

                                if ($productattr)
                                    $product_attr['product_attr'] = $productattr;
                                if ($product_attr)
                                    $aoo['addon'] = serialize($product_attr); //货品属性//

                            $oOrderItm->save($aoo);
                        }
                    }



                }
                
               //修改交易收货人信息 API
                if ($is_address_change == true){
                    if ($service_order = kernel::servicelist('service.order')){
                        foreach($service_order as $object=>$instance){
                           if(method_exists($instance, 'update_shippinginfo')){
                              $instance->update_shippinginfo($order_id);
                           }
                        }
                    }
                }
                //订单编辑API
                if ($is_order_change == true){
                    if ($service_order = kernel::servicelist('service.order')){
                        foreach($service_order as $object=>$instance){
                           if(method_exists($instance, 'update_order')){
                              $instance->update_order($order_id);
                           }
                        }
                    }
                }
                //订单恢复状态同步
                if ($service_order = kernel::servicelist('service.order')){
                    foreach($service_order as $object=>$instance){
                        if(method_exists($instance, 'update_order_pause_status')){
                           $instance->update_order_pause_status($order_id, 'false');
                        }
                    }
                }
                
                /*
                 * 复审订单_库存冻结
                 * Author: ExBOY
                 * Timer: 2014.06.05
                 */
                if($retrial_id)
                {
                    $record    = $oRetrial->record_stock_freeze($order_item_list, $retrial_id);//保存冻结库存记录
                }

                //$this->end(true, "修改成功");
            }else{
                //恢复order
                $oOrder->renewOrder($order_id);
            }

            $shopex_list = ome_shop_type::shopex_shop_type();
            $final_total_amount = isset($new_order['total_amount']) ? $new_order['total_amount'] : $post['total_amount'];

            if( ( $order['source'] == 'local' || in_array($order['shop_type'], $shopex_list) ) && (bccomp('0.000', $final_total_amount,3) == 0) && $order['shipping']['is_cod'] != 'true' ){ #0元订单是否需要财审.货到付款0远都需要才审

                kernel::single('ome_order_order')->order_pay_confirm($order['shop_id'],$order_id,$post['total_amount']);

            }

            $this->end(true, "完成");
        }else {
            //判断，校验
            $this->begin('');

            $oOrder     = &$this->app->model("orders");
            $oOrderItm  = &$this->app->model("order_items");
            $oOrderObj  = &$this->app->model("order_objects");
            $pObj       = &$this->app->model("products");
            $goodsObj   = &$this->app->model("goods");
            $order_id   = $_POST['order_id'];
            $order      = $oOrder->dump($order_id);

            if ($order['pause'] == 'false'){
                $this->end(false, '请先暂停订单');
            }
            $post       = $_POST;
            $consignee  = array_diff_assoc($post['order']['consignee'],$order['consignee']);
            
            #[拆单]部分发货 OR 部分退货订单编辑_强制至少保留一个未发货商品 ExBOY
            if($order['ship_status'] == '2' || $order['ship_status'] == '3')
            {
                $sql    = "SELECT order_id, item_id, obj_id, item_type FROM sdb_ome_order_items
                           WHERE order_id='".$order_id."' AND `delete`='false' AND nums = sendnum";
                $send_items    = kernel::database()->select($sql);
                
                #过滤已发货的货品
                if($send_items)
                {
                    foreach ($send_items as $key => $val)
                    {
                        $get_item_type    = ($val['item_type'] == 'product' ? 'goods' : $val['item_type']);
                        $get_obj_id       = $val['obj_id'];
                        $get_item_id      = $val['item_id'];
                        
                        unset($post[$get_item_type]['obj'][$get_obj_id]);
                        
                        #goods
                        unset($post[$get_item_type]['num'][$get_item_id], $post[$get_item_type]['price'][$get_item_id]);
                        
                        #pkg
                        if($get_item_type == 'pkg')
                        {
                            unset($post[$get_item_type]['num'][$get_obj_id], $post[$get_item_type]['price'][$get_obj_id]);
                            unset($post[$get_item_type]['inum'][$get_obj_id], $post[$get_item_type]['iprice'][$get_obj_id]);
                        }
                    }
                    
                    unset($get_item_type, $get_obj_id, $get_item_id);
                }
            }
            
            $goods      = $post['goods'];
            $pkg        = $post['pkg'];
            $gift       = $post['gift'];
            $giftpkg    = $post['giftpkg'];
            $objtype    = $post['objtype'];
            if (empty($goods) && empty($pkg) && empty($gift) && empty($giftpkg)){
                $this->end(false, "订单不能没有商品");
            }

            // 验证是否存在发货单
            /*
            $deliveryIdArr = app::get('ome')->model('delivery')->getDeliverIdByOrderId($order_id);
            if ($deliveryIdArr) {
                $this->end(false,'请先暂停订单后，撤销发货单');
            }*/
  
            if ($objtype && is_array($objtype)){
                //是否有数据提交
                $rs = kernel::single("ome_order_edit")->is_null($objtype,$post);
                if ($rs == true)
                    $this->end(false, "订单不能没有商品");
                //校验数据正确性
                $rs = kernel::single("ome_order_edit")->valid_order_objtype($objtype,$post);
                if ($rs != true && $rs['flag'] == false){
                    $this->end(false, $rs['msg']);
                }
            }else {
                $this->end(false, "订单不能没有商品");
            }
			//zjr
			$jsonData['orders']['newOrders']['consignee']=$post['order']['consignee'];
			$jsonData['orders']['newOrders']['tax']=$post['tax'];
			$jsonData['orders']['oldOrders']['consignee']=$order['consignee'];
			$jsonData['orders']['oldOrders']['tax']['tax_title']=$order['tax_title'];
			$jsonData['orders']['oldOrders']['tax']['tax_no']=$order['tax_no'];
			$jsonData['orders']['oldOrders']['tax']['invoice_name']=$order['invoice_name'];
			$jsonData['orders']['oldOrders']['tax']['invoice_area']=$order['invoice_area'];
			$jsonData['orders']['oldOrders']['tax']['invoice_contact']=$order['invoice_contact'];
			$jsonData['orders']['oldOrders']['tax']['invoice_addr']=$order['invoice_addr'];
			$jsonData['orders']['oldOrders']['tax']['invoice_zip']=$order['invoice_zip'];
			//echo "<pre>";print_r($jsonData);exit();
            $this->end(true,'验证完成',null,$jsonData);
        }
    }

    function getProducts(){
        $pro_id = $_POST['product_id'];

        if (is_array($pro_id)){
            $filter['product_id'] = $pro_id;
        }

        if($_GET['bn']){

           $filter = array(
               'bn|head'=>$_GET['bn']
           );
        }

        if($_GET['name']){
            $filter = array(
               'name|head'=>$_GET['name']
           );
        }
        if($_GET['barcode']){
            $filter = array(
                    'barcode|head'=>$_GET['barcode']
            );
        }
        

        $pObj = &$this->app->model('products');
        $data = $pObj->getList('visibility,product_id,bn,name,price,barcode,spec_info,store,store_freeze',$filter,0,-1);
        $list = array();
        if ($data)
        foreach ($data as $v){
            $v['type'] = 'goods';
            $v['store_minus_freeze']  = $v['store'] - $v['store_freeze'];
            unset($v['store']);
            unset($v['store_freeze']);
            $list[] = $v;
        }

        echo "window.autocompleter_json=".json_encode($list);
    }

    function findProduct(){
        # 商品隐藏
        $filter = array();
        if (!isset($_POST['visibility'])) {
            $filter['visibility'] = 'true';
        }elseif(empty($_POST['visibility'])){
            unset($_POST['visibility']);
        }

        if ($_GET['branch_id']) {
            $filter['branch_id'] = $_GET['branch_id'];
        }
        $object_method = array('count'=>'count','getlist'=>'getlist');
        if ($_POST['branch_id'] || $filter['branch_id']) {
            $filter['product_group'] = true;
            $object_method = array('count'=>'countAnother','getlist'=>'getListAnother');
        }

        $params = array(
            'title'                  => '商品列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_view_tab'           => false,
            'use_buildin_filter'     => true,
            'base_filter'            => $filter,
            'object_method'          => $object_method,
        );
        $this->finder('ome_mdl_products', $params);
    }

    function do_check($order_id, $newtotal, $total){

        $orefapply = &$this->app->model('orders');
        $order = $orefapply->order_detail($order_id);
        $is_cod = $_GET['is_cod'];
        $pay_status = intval($_GET['pay_status']);
        $newtotal = strval($newtotal);
        $total = strval($total);
        $payed = $order['payed'];
        
        #是否开启复审及复审规则  ExBOY
        $setting_retrial    = $this->get_setting_retrial();
        $is_retrial         = 'false';//判断显示退款单
        if($setting_retrial['is_retrial'] == 'true' && $_GET['is_retrial'] == 'true')
        {
            $is_retrial    = 'true';
        }
        $this->pagedata['is_retrial']   = $is_retrial;
        $this->pagedata['is_refund']    = ($payed > $newtotal ? 'true' : 'false');
        
        $is_change = $payed != $newtotal ? 1 : 0;
        if ($payed > $newtotal && $is_retrial != 'true'){
            $refund_money = $payed - $newtotal;

            if ($order['pause'] == 'false'){
                exit("请先暂停订单");
            }
            $addon['from'] = 'order_edit';
            $result = kernel::single('ome_refund_apply')->show_refund_html($order_id, '', $refund_money, $addon);
            if ($result['result'] == true){
                return $result;
            }else{
                exit($result['msg']);
            }
        }else{

            /*------------------------------------------------------ */
            //-- [编辑订单]价格监控ExBOY 2014.05.29
            /*------------------------------------------------------ */
            $product_ids   = trim($_GET['product_ids']);
            $product_ids   = explode(',', $product_ids);
            
            #是复审订单，查询原始订单的总金额
            if($order['process_status'] == 'is_retrial')
            {
                $oSnapshot  = &app::get('ome')->model('order_retrial_snapshot');
                $order_old  = $oSnapshot->getList('tid, retrial_id, order_detail', array('order_id'=>$order_id), 0, 1);
                if(!empty($order_old))
                {
                    $order_old  = $order_old[0];
                    $order_old  = unserialize($order_old['order_detail']);
                    $total      = strval($order_old['total_amount']);
                }
            }
            
            #价格监控
            if(!empty($product_ids))
            {
                $product_list     = array();
                foreach ($product_ids as $key => $val)
                {
                    $temp        = explode('_', $val);
                    $temp_id     = intval($temp[0]);
                    
                    $product_list['ids'][$temp_id]  = $temp_id;
                    $product_list['nums'][$temp_id] = intval($temp[1]);
                }
                unset($product_ids, $temp);
                
                $oRetrial         = &app::get('ome')->model('order_retrial');
                $price_monitor    = $oRetrial->get_product_monitor($product_list, floatval($newtotal));
                
                $this->pagedata['price_monitor'] = $price_monitor;
            }
            
			$viewOrders=array();
			$arrOders=json_decode($_GET['orders'],true);
			$arrOders=$arrOders['orders'];
			foreach($arrOders['newOrders'] as $t=>$type){
				foreach($type as $v=>$value){
					if($arrOders['oldOrders'][$t][$v]!=$value){
						$viewOrders['orders']['newOrders'][$t][$v]['f']=$value;
					}else{
						$viewOrders['orders']['newOrders'][$t][$v]['t']=$value;
					}
				}
			}
			$this->pagedata['newOrdersConsignee'] = $viewOrders['orders']['newOrders']['consignee'];
			$this->pagedata['newOrdersTax'] = $viewOrders['orders']['newOrders']['tax'];
			$this->pagedata['oldOrders'] = $arrOders['oldOrders'];
			//echo "<pre>";print_r($arrOders);print_r($viewOrders);exit();
            #差额[现订单金额-原订单金额] ExBOY
            $diff_money        = round(intval($newtotal - $total), 3);
            $this->pagedata['diff_money']  = $diff_money;
            
            $this->pagedata['is_cod'] = $is_cod;
            $this->pagedata['is_change'] = $is_change;
            $this->pagedata['change_value'] = round($newtotal-$payed, 3);//禁用abs()函数,有负数退款存在
            $this->pagedata['newtotal'] = $newtotal;
            $this->pagedata['total'] = $total;
            $this->pagedata['payed'] = $payed;
            
            $this->display("admin/order/order_edit_check.html");
        }
    }

    function do_refund(){

        $objOrder = &app::get('ome')->model('orders');
        $this->begin("index.php?app=ome&ctl=admin_order&act=do_refund&p[0]=".$_POST['order_id']);
        if($_POST){
            $orderdata = $objOrder->order_detail($_POST['order_id']);
            
            #允许"复审订单"生成退款单  ExBOY 2014.08.11
            if ($orderdata['pause'] == 'false' && $orderdata['process_status'] != 'is_retrial')
            {
                $this->end(false, '请先暂停订单');
            }
            $return = kernel::single('ome_refund_apply')->refund_apply_add($_POST);
            if ($return['result'] == true){
                $result  = true;
            }else{
                $result = false;
            }
            $msg = $return['msg'];

            $this->end($result, app::get('base')->_($msg));
        }
    }

    function addOrder(){
        if ($_POST){
            $this->begin("index.php?app=ome&ctl=admin_order&act=addOrder");
            if (!$_POST['type']){
                $this->end(false,'请选择订单类型');
            }
            $brObj = &$this->app->model('branch');
            $branch_list = $brObj->getBranchByUser();
            /*if (count($branch_list) == 0){
                $this->end(false, '管理员未关联仓库，请先关联仓库');
            }elseif (count($branch_list) > 1){
                $this->end(false, '管理员已关联多个仓库，无法操作');
            }*/
            $type = $_POST['type'];
            if ($type == 'normal'){
                $this->addNormalOrder();
            }else {
                $this->addSaleOrder();
            }
        }else
            $this->page("admin/order/order_choice.html");
    }

    function addNormalOrder(){
        $shopObj = &$this->app->model("shop");
        $shopData = $shopObj->getList('shop_id,name,shop_type');
        $this->pagedata['shopData'] = $shopData;
        $this->pagedata['creatime'] = date("Y-m-d",time());
        $this->page("admin/order/add_normal_order.html");
    }

    function doAddNormalOrder(){
        $this->begin("index.php?app=ome&ctl=admin_order&act=addNormalOrder");
        //danny_freeze_stock_log
        //define('FRST_OPER_ID','0');
        //define('FRST_OPER_NAME','');
        define('FRST_TRIGGER_OBJECT_TYPE','订单：手工新建订单');
        define('FRST_TRIGGER_ACTION_TYPE','ome_ctl_admin_order：doAddNormalOrder');
        $pObj = &$this->app->model("products");
        $oObj = &$this->app->model("orders");


        $post = $_POST;

        $post['consignee']['r_time']    = '任意日期 任意时间段';
        $post['consignee']['area']      = $post['address_id'];

        $post['member_id'] = $post['id'];
        if (!$post['member_id'])
            $this->end(false, '请选择会员');
        if (!$post['cost_shipping'])
            $post['cost_shipping'] = 0;
        if (!$post['discount'])
            $post['discount'] = 0;

        $consignee = $post['consignee'];
        if ($consignee){
            if (!$consignee['name']){
                $this->end(false, '请填写收件人');
            }
            if (!$consignee['area']){
                $this->end(false, '请填写配送三级区域');
            }
            if (!$consignee['addr']){
                $this->end(false, '请填写配送地址');
            }
            if (!$consignee['mobile'] && !$consignee['telephone']){
                $this->end(false, '收件人手机和固定电话必须填写一项');
            }
        }else {
            $this->end(false, '请填写配送地址信息');
        }
        $ship = $_POST['address_id'];
        #检测是不是货到付款
        if($post['is_cod'] == 'true' || $post['is_cod'] == 'false'){
            $is_code = $post['is_cod'];
        }
        $shipping = array();
        if ($ship){
            $shipping = array(
                'shipping_name' => '快递',
                'cost_shipping' => $post['cost_shipping'],
                'is_protect' => 'false',
                'cost_protect' => 0,
                'is_cod' => $is_code?$is_code:'false'
            );
        }else {
            $this->end(false, '请选择物流信息');
        }
        $num = $_POST['num'];
        $price = $_POST['price'];
        if (!$num)
            $this->end(false, '请选择商品');
        $tmp_num = $num;
        $pkg_num = array();
        foreach ($num as $key => $v){
            $no = explode('_',$key);
            if ($no[0] == 'pkg') {
                unset($tmp_num[$key]);
                $pkg_num[$key] = array(
                    'id' => $no[1],
                    'num' => $v
                );
            }
            if ($v < 1 || $v > 499999){
                $this->end(false, '数量必须大于1且小于499999');
            }
        }
        if (!$price)
            $this->end(false, '请选择商品');
        foreach ($price as $v){
            if ($v < 0){
                $this->end(false, '请填写正确的价格');
            }
        }

        $num = $tmp_num;
        $iorder = $post['order'];

        $iorder['consignee'] = $consignee;
        $iorder['shipping'] = $shipping;

        //goods
        if ($num)
        foreach ($num as $k => $i){
            $p = $pObj->dump($k);
            $iorder['order_objects'][] = array(
                'obj_type' => 'goods',
                'obj_alias' => 'goods',
                'goods_id' => $p['goods_id'],
                'bn' => $p['bn'],
                'name' => $p['name'],
                'price' => $price[$k],
                'sale_price'=>$price[$k]*$i,
                'amount' => $price[$k]*$i,
                'quantity' => $i,
                'order_items' => array(
                    array(
                        'product_id' => $p['product_id'],
                        'bn' => $p['bn'],
                        'name' => $p['name'],
                        'price' => $price[$k],
                        'amount' => $price[$k]*$i,
                        'sale_price'=> $price[$k]*$i,
                        'quantity' => $i,
                        'sendnum' => 0,
                        'item_type' => 'product'
                    )
                )
            );
            $weight += $i*$p['weight'];
            $item_cost += $i*$price[$k];
        }
        //pkg
        if ( $pkg_num ) {
            $pkgPobj = &app::get('omepkg')->model('pkg_product');
            $pkgGobj = &app::get('omepkg')->model('pkg_goods');
            foreach ($pkg_num as $key =>$val){
                $pkgprolist = $pkgPobj->getList('*', array('goods_id'=>$val['id']), 0, -1);
                $order_items = array();
                foreach ($pkgprolist as $v){
                    $p = $pObj->dump($v['product_id']);
                    $order_items[] = array(
                        'product_id' => $p['product_id'],
                        'bn' => $p['bn'],
                        'name' => $p['name'],
                        'price' => 0,
                        'amount' => 0,
                        'quantity' => $v['pkgnum'] * $val['num'],
                        'sale_price' => 0,
                        'sendnum' => 0,
                        'item_type' => 'pkg'
                    );
                }
                $pkgg = $pkgGobj->dump( $val['id'] );
                $iorder['order_objects'][] = array(
                    'obj_type' => 'pkg',
                    'obj_alias' => $pkgg['name'],
                    'goods_id' => $p['goods_id'],
                    'bn' => $pkgg['pkg_bn'],
                    'name' => $pkgg['name'],
                    'price' => $price[$key],
                    'sale_price'=>$price[$key] * $val['num'],
                    'amount' => $price[$key] * $val['num'],
                    'quantity' => $val['num'],
                    'order_items' => $order_items
                );
                $weight += $val['num'] * $pkgg['weight'];
                $item_cost += $val['num'] * $price[$key];
            }
        }
        if ($post['customer_memo']){
            $c_memo =  htmlspecialchars($post['customer_memo']);
            $c_memo = array('op_name'=>kernel::single('desktop_user')->get_name(), 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$c_memo);
            $tmp[]  = $c_memo;
            $iorder['custom_mark']  = serialize($tmp);
            $tmp = null;
        }
        if ($post['order_memo']){
            $o_memo =  htmlspecialchars($post['order_memo']);
            $o_memo = array('op_name'=>kernel::single('desktop_user')->get_name(), 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$o_memo);
            $tmp[]  = $o_memo;
            $iorder['mark_text']    = serialize($tmp);
            $tmp = null;
        }

        if($post['shop_id']){
            $shop = explode('*',$post['shop_id']);
            $iorder['shop_id'] = $shop[0];
            $iorder['shop_type'] = $shop[1];
        }else{
            $this->end(false, '请选择来源店铺！');
        }

        $mathLib = kernel::single('eccommon_math');

        $iorder['member_id']    = $post['member_id'];
        $iorder['weight']       = $weight;
        $iorder['title']        = $p['bn'].$p['name'];
        $iorder['itemnum']      = count($iorder['order_objects']);
        $iorder['createtime']   = time();
        $iorder['ip']           = $_SERVER['REMOTE_ADDR'];
        $iorder['cost_item']    = $item_cost;
        $iorder['currency']     = 'CNY';
        $iorder['pmt_order']    = $post['pmt_order'];
        $iorder['discount']     = $post['discount'];
        $iorder['relate_order_bn']     = $post['relate_order_bn'];
        $iorder['total_amount'] = $mathLib->number_plus(array($item_cost,$post['cost_shipping'],$post['discount']));
        $iorder['total_amount'] = $mathLib->number_minus(array($iorder['total_amount'],$post['pmt_order']));
        // $iorder['total_amount'] = $item_cost+$post['cost_shipping']-$post['pmt_order']+$post['discount'];

        $iorder['is_delivery']  = 'Y';
        $iorder['source']  = 'local';//订单来源标识，local为本地新建订单
        $iorder['createway'] = 'local';
        #新建订单时，要开票的
        if($post['is_tax'] == 'true'){
            $iorder['is_tax'] = $post['is_tax'];
            $iorder['tax_title'] = $post['tax_title'];
        }

        if ($iorder['total_amount'] < 0)
            $this->end(false, '订单金额不能小于0');

        $iorder['order_bn'] = $oObj->gen_id();

        //设置订单失败时间
        $iorder['order_limit_time'] = time() + 60*(app::get('ome')->getConf('ome.order.failtime'));

        $oObj->create_order($iorder);
        #货到付款类型订单，增加应收金额
        if($is_code == 'true'){
            $oObj_orextend = &$this->app->model("order_extend");
            $code_data = array('order_id'=>$iorder['order_id'],'receivable'=>$iorder['total_amount'],'sellermemberid'=>$iorder['member_id']);
            $oObj_orextend->save($code_data);
            
        }
        $this->end(true, '创建成功');
    }

    /*function addSaleOrder(){
        $this->begin("index.php?app=ome&ctl=admin_order&act=addSaleOrder");
        $brObj = &$this->app->model('branch');
        //$mbObj = &$this->app->model('members');
        $branch_list = $brObj->getBranchByUser();

        if (count($branch_list) == 0){
            $this->end(false, '管理员未关联仓库，请先关联仓库');
        }elseif (count($branch_list) > 1){
            $this->end(false, '管理员已关联多个仓库，无法操作');
        }

        //$members = $mbObj->getList('member_id,uname','',0,-1);

        //$this->pagedata['member'] = $members;
        $this->pagedata['branch'] = array_shift($branch_list);
        $this->page("admin/order/add_sale_order.html");
    }*/

    function doAddSaleOrder(){
        $this->begin("index.php?app=ome&ctl=admin_order&act=addSaleOrder");

        $pObj = &$this->app->model("products");
        $bpObj = &$this->app->model("branch_product");
        $oObj = &$this->app->model("orders");
        $dObj = &$this->app->model("delivery");

        $post = $_POST;

        $logi_id = $post['logi_id'];
        $logi_no = $post['logi_no'];

        $post['consignee']['r_time']    = '任意日期 任意时间段';
        $post['member_id']          = $post['id'];
        $post['consignee']['area']  = $post['address_id'];

        if (!$post['member_id'])
            $this->end(false, '请选择会员');
        if (!$post['address_id'])
            $this->end(false, '请选择收货地址');
        if (!$logi_id)
            $this->end(false, '请选择物流公司');
        if (!$post['cost_shipping'])
            $post['cost_shipping'] = 0;
        if (!$post['discount'])
            $post['discount'] = 0;

        if ($dObj->existExpressNo($logi_no))

            $this->end(false, '快递单号重复');

        $consignee = $post['consignee'];
        if ($consignee){
            if (!$consignee['name']){
                $this->end(false, '请填写收件人');
            }
            if (!$consignee['area']){
                $this->end(false, '请填写配送三级区域');
            }
            if (!$consignee['addr']){
                $this->end(false, '请填写配送地址');
            }
            if (!$consignee['zip']){
                $this->end(false, '请填写配送邮政编码');
            }
            /*if (!$consignee['telephone']){
                $this->end(false, '请填写收件人电话');
            }*/
            if (!$consignee['mobile']){
                $this->end(false, '请填写收件人手机');
            }
        }else {
            $this->end(false, '请填写配送地址信息');
        }
        $ship = $post['address_id'];
        $corpObj = &$this->app->model('dly_corp');
        $corp = $corpObj->dump($logi_id, 'corp_id,name');

        $shipping = array();
        if ($ship){
            $shipping = array(
                'shipping_name' => $corp['name'],
                'cost_shipping' => $post['cost_shipping'],
                'is_protect' => 'false',
                'cost_protect' => 0,
                'is_cod' => 'true'
            );
        }else {
            $this->end(false, '请选择物流信息');
        }
        $num = $post['num'];
        $price = $post['price'];
        if (!$num)
            $this->end(false, '请选择商品');
        $tmp_num = $num;
        $pkg_num = array();
        $pro = array();
        foreach ($num as $ky => $v){
            if ($v < 1 || $v > 499999){
                $this->end(false, '数量必须大于1且小于499999');
            }
            $no = explode('_',$ky);
            if ($no[0] == 'pkg'){
                unset($tmp_num[$ky]);
                $pkg_num[$ky] = array(
                    'id' => $no[1],
                    'num' => $v
                );
                $pkgplist = app::get('omepkg')->model('pkg_product')->getList('*',array('goods_id'=>$no[1]),0,-1);
                foreach ($pkgplist as $val){
                    $pro[$val['product_id']] = isset($pro[$val['product_id']])?$pro[$val['product_id']]+$v*$val['pkgnum']:$v*$val['pkgnum'];
                    $bp = $bpObj->dump(array('product_id'=>$val['product_id'],'branch_id'=>$post['branch_id']),'store');
                    if ($pro[$val['product_id']] > $bp['store']) {
                        $this->end(false, '捆绑商品库存数量不足');
                    }
                }
                continue;
            }
            $pp = $pObj->dump($ky,'name');
            $bp = $bpObj->dump(array('product_id'=>$ky,'branch_id'=>$post['branch_id']),'store');
            $pro[$ky] = isset($pro[$ky])?$pro[$ky]+$v:$v;
            if ($pro[$ky] > $bp['store']){
                $this->end(false, $pp['name'].':库存数量不足');
            }
        }
        if (!$price)
            $this->end(false, '请选择商品');
        foreach ($price as $v){
            if ($v < 0){
                $this->end(false, '请填写正确的价格');
            }
        }
        $num = $tmp_num;
        $iorder = array();

        $iorder['consignee'] = $consignee;
        $iorder['shipping'] = $shipping;
        $delivery = array();
        $dly_item = array();
        //goods
        foreach ($num as $k => $i){
            $p = $pObj->dump($k);
            $iorder['order_objects'][] = array(
                'obj_type' => 'goods',
                'obj_alias' => 'goods',
                'goods_id' => $p['goods_id'],
                'bn' => $p['bn'],
                'name' => $p['name'],
                'price' => $price[$k],
                'amount' => $price[$k],
                'quantity' => $i,
                'order_items' => array(
                    array(
                        'product_id' => $p['product_id'],
                        'bn' => $p['bn'],
                        'name' => $p['name'],
                        'price' => $price[$k],
                        'amount' => $price[$k],
                        'nums' => $i,
                        'quantity' => $i,
                        'sendnum' => 0,
                        'item_type' => 'product'
                    )
                )
            );

            $dly_item[$p['product_id']] = array(
                'product_id' => $p['product_id'],
                'item_type' => 'product',
                'bn' => $p['bn'],
                'product_name' => $p['name'],
                'number' => $i
            );
            $weight += $i*$p['weight'];
            $item_cost += $i*$price[$k];
        }

        //pkg
        if ( $pkg_num ) {
            $pkgPobj = &app::get('omepkg')->model('pkg_product');
            $pkgGobj = &app::get('omepkg')->model('pkg_goods');
            foreach ($pkg_num as $key =>$val){
                $pkgprolist = $pkgPobj->getList('*', array('goods_id'=>$val['id']), 0, -1);
                foreach ($pkgprolist as $v){
                    $p = $pObj->dump($v['product_id']);
                    $order_items[] = array(
                        'product_id' => $p['product_id'],
                        'bn' => $p['bn'],
                        'name' => $p['name'],
                        'price' => 0,
                        'amount' => 0,
                        'nums' => $v['pkgnum'] * $val['num'],
                        'quantity' => $v['pkgnum'] * $val['num'],
                        'sendnum' => 0,
                        'item_type' => 'pkg'
                    );
                    if (isset($dly_item[$p['product_id']])) {
                        $dly_item[$p['product_id']]['number'] += $v['pkgnum'] * $val['num'];
                    }else {
                        $dly_item[$p['product_id']] = array(
                            'product_id' => $p['product_id'],
                            'bn' => $p['bn'],
                            'product_name' => $p['name'],
                            'number' => $v['pkgnum'] * $val['num']
                        );
                    }
                }
                $pkgg = $pkgGobj->dump( $val['id'] );
                $iorder['order_objects'][] = array(
                    'obj_type' => 'pkg',
                    'obj_alias' => $pkgg['name'],
                    'goods_id' => $p['goods_id'],
                    'bn' => $pkgg['pkg_bn'],
                    'name' => $pkgg['name'],
                    'price' => $price[$key],
                    'amount' => $price[$key] * $val['num'],
                    'quantity' => $val['num'],
                    'order_items' => $order_items
                );
                $weight += $val['num'] * $pkgg['weight'];
                $item_cost += $val['num'] * $price[$key];
            }
        }
        if ($post['customer_memo']){
            $c_memo =  htmlspecialchars($post['customer_memo']);
            $c_memo = array('op_name'=>kernel::single('desktop_user')->get_name(), 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$c_memo);
            $tmp[]  = $c_memo;
            $iorder['custom_mark']  = serialize($tmp);
            $tmp = null;
        }
        if ($post['order_memo']){
            $o_memo =  htmlspecialchars($post['order_memo']);
            $o_memo = array('op_name'=>kernel::single('desktop_user')->get_name(), 'op_time'=>date('Y-m-d H:i',time()), 'op_content'=>$o_memo);
            $tmp[]  = $o_memo;
            $iorder['mark_text']    = serialize($tmp);
            $tmp = null;
        }

        $iorder['member_id']    = $post['member_id'];
        $iorder['weight']       = $weight;
        $iorder['title']        = $p['bn'].$p['name'];
        $iorder['itemnum']      = count($iorder['order_objects']);
        $iorder['createtime']   = time();
        $iorder['ip']           = $_SERVER['REMOTE_ADDR'];
        $iorder['cost_item']    = $item_cost;
        $iorder['currency']     = 'CNY';
        $iorder['pmt_order']    = $post['pmt_order'];
        $iorder['discount']     = $post['discount'];
        $iorder['total_amount'] = $item_cost+$_POST['cost_shipping']-$post['pmt_order']+$post['discount'];
        $iorder['is_delivery']  = 'Y';
        $iorder['order_type']   = 'sale';

        if ($iorder['total_amount'] < 0)
            $this->end(false, '订单金额不能小于0');

        $iorder['order_bn'] = $oObj->gen_id();

        $oObj->create_order($iorder);
        $order_id = $iorder['order_id'];
        //确认
        $oObj->confirm($order_id);

        if ($dly_item)
        foreach ($dly_item as $item){
            $delivery['delivery_items'][] = $item;
        }

        $delivery['member_id']  = $iorder['member_id'];
        $delivery['is_cod']     = $iorder['is_cod'];
        $delivery['new_weight'] = $weight;
        $delivery['logi_id']    = $corp['corp_id'];
        $delivery['logi_name']  = $corp['name'];
        $delivery['branch_id']  = $post['branch_id'];
        $delivery['delivery_cost_actual'] = $post['cost_shiiping'];

        //生成发货单
        $ids = $oObj->mkDelivery($order_id, array($delivery));
        $dObj = $this->app->model("delivery");
        //处理发货单
        if (!$ids)
            $this->end(false, '订单生成失败');
        foreach ($ids as $id){
            //打印完
            $dly = array(
                    'delivery_id' => $id,
                    'stock_status' => true,
                    'expre_status' => true,
                    'deliv_status' => true,
                    'logi_no' => $logi_no
            );
            $dObj->save($dly);
            //校验完成
            $re = $dObj->verifyDelivery(array('delivery_id'=>$id,'is_bind'=>'false'));
            if (!$re)
                $this->end(false, '创建失败');
            //发货
            $dObj->consignDelivery($id,$weight);
        }

        $this->end(true, '创建成功');
    }

    function getMemberAddress($mem_id=0){
        if (!$mem_id){
            $mem_id = $_POST['member_id'];
        }
        $oObj = $this->app->model("orders");
        $list = $oObj->getList('order_id',array('member_id'=>$mem_id),0,-1);
        if ($list){
            $address = array();
            foreach ($list as $v){
                $order = $oObj->dump($v['order_id']);
                $string = $order['consignee'];
                $md5 = md5(serialize($string));
                $tmp = explode(':',$string['area']);
                $string['id'] = $tmp[2];
                $address[$md5] = $string;
                if(count($address)>=10){
                    break;
                }
            }
            sort($address);
            echo json_encode($address);
        }
    }

    function getMembers(){
        $mbObj = &$this->app->model('members');

        if($_POST['mobile']){
            $data = $mbObj->get_member($_POST['mobile'],'mobile');
        }elseif ($_POST['uname']){
            $data = $mbObj->get_member($_POST['uname'],'uname');
        }elseif ($_POST['member_id']){
            $filter = array(
               'member_id'=>$_POST['member_id']
            );
            $data = $mbObj->getList('member_id,uname,area,mobile,email,sex',$filter,0,-1);
        }

        if ($data)
        foreach ($data as $k => $v){
            $data[$k]['sex'] = $v['sex']=='male' ? '男' : '女';
        }

        if ($data){
            echo "window.autocompleter_json=".json_encode($data);
            exit;
        }
        echo "";
    }

    function getCorpArea(){
        $region = $_POST['region'];
        $dcaObj = &$this->app->model('dly_corp_area');
        $dcObj = &$this->app->model('dly_corp');
        if ($region){
            $tmp = explode(':',$region);
            $region_id = $tmp[2];
            $data = $dcaObj->getCorpByRegionId($region_id);
            if (!$data)
                $data = $dcObj->getList('corp_id,name', '', 0, -1);
            echo json_encode($data);
        }else {
            $data = $dcObj->getList('corp_id,name','',0,-1);
            echo json_encode($data);
        }
    }

    function addNewAddress(){
        if (isset($_GET['area'])){
            $this->pagedata['region'] = $_GET['area'];
        }
        $this->display("admin/order/add_new_address.html");
    }

    function getConsingee(){
        $string = $_POST['consignee'];
        if ($string['area']){
            $region = explode(':', $string['area']);
            if (!$region[2]){
                return false;
            }
        }else {
            return false;
        }
        $string['id'] = $region[2];
        echo json_encode($string);
    }

    function getCorps(){
//        error_log(var_export($_POST,true),3,__FILE__.".log");
        $branch_id = $_POST['branch_id'];
        $area = $_POST['area'];
        $weight = $_POST['weight'];
        $shop_type = $_POST['shop_type'];
        $shop_id = $_POST['shop_id'];

        //电子面单来源类型
        $channelObj = &app::get("logisticsmanager")->model('channel');
        $rows = $channelObj->getList("channel_id,channel_type",array('status'=>'true'));
        $channelType = array();
        foreach($rows as $val) {
            $channelType[$val['channel_id']] = $val['channel_type'];
            unset($val);
        }
        unset($rows);

        $oBranch = &$this->app->model("branch");
        $rows = $oBranch->get_corpbyarea($branch_id,$area,$weight,$shop_type,$shop_id);
        //获取店铺信息
        $shopObj = &app::get("ome")->model('shop');
        $shopInfo = $shopObj->dump(array('shop_id' => $shop_id), 'shop_type,addon');
        //过滤掉不适用此店铺的快递公司
        $corpList = array();
        foreach($rows as $k=>$v) {
            if($v['tmpl_type']=='electron' && $channelType[$v['channel_id']]=='wlb' && $v['shop_id']!=$shop_id) {
                continue;
            }
            //过滤京东电子面单
            if ($shop_type != '360buy' && $v['tmpl_type']=='electron' && $channelType[$v['channel_id']]=='360buy' && $v['shop_id']!=$shop_id) {
                continue;
            }
            if ($shop_type == '360buy' && $shopInfo['addon']['type'] != 'SOP' && $v['tmpl_type']=='electron' && $channelType[$v['channel_id']]=='360buy' && $v['shop_id']!=$shop_id ) {
                continue;
            }
            $corpList[] = $v;
        }
        echo json_encode($corpList);
    }

   function do_confirm_delivery_info_edit($order_id){
       if($order_id){
           $oOrder = &$this->app->model("orders");
           $order = $oOrder->dump($order_id);
           $this->pagedata['order'] = $order;
           $this->display('admin/order/confirm/delivery_info_edit.html');
       }
   }

   function do_confirm_delivery_info($order_id){
       if($order_id){
           $oOrder = &$this->app->model("orders");
           $order = $oOrder->dump($order_id);
           $order['mark_text'] = unserialize($order['mark_text']);
           $order['custom_mark'] = unserialize($order['custom_mark']);
           $this->pagedata['order'] = $order;
           $this->display('admin/order/confirm/delivery_info.html');
       }
   }

   function cancelOrder(){

   }

    //订单快照
    function show_operation(){
        
        $log_id  = $_GET['log_id'];
        $order_id = $_GET['order_id'];
        $ooObj = $this->app->model('orders');
        
        $operation_history = $ooObj->read_log_detail($order_id,$log_id);
        $region_detail = $operation_history['order_detail'];
        //兼容 上个版本的 订单快照
        if(isset($region_detail['item_list'])){

            $this->pagedata['operation_history'] = $operation_history;
            $this->pagedata['operation_detail'] = $region_detail;
            if(!preg_match("/^mainland:/", $region_detail['log_area'])){
                $region='';
                $newregion='';
                foreach(explode("/",$region_detail['log_area']) as $k=>$v){
                    $region.=$v.' ';
                }
            }else{
                $newregion = $region_detail['log_area'];
            }
            $this->pagedata['region'] = $region;
            $this->pagedata['newregion'] = $newregion;
            $this->singlepage('admin/order/operations_order_old.html');
        }else{
           
            $this->pagedata['operation_detail'] = $region_detail;
            $this->singlepage('admin/order/operations_order.html');
        }
    }
   /**
   * 获取订单编辑方式
   * @access public
   * @param number $order_id 订单ID
   * @return json
   */
   function update_type($order_id){
       $return = array('rsp'=>'fail','msg'=>'','data'=>'');
       $rs = kernel::single('ome_order')->update_iframe($order_id,$is_request=false);

       $return['rsp'] = $rs['rsp'];
       $return['msg'] = $rs['msg'];
       if (!isset($rs['data']['edit_type'])){
           $rs['data']['edit_type'] = 'local';
       }
       $return['data'] = $rs['data'];
       echo json_encode($return);
       exit;
   }


   /**
   * 订单编辑页面
   * @access public
   * @param number $order_id 订单ID
   * @return json
   */
   function update_iframe($order_id){
        $oOrder = &$this->app->model('orders');
        $order = $oOrder->getRow($order_id);
        $rs = array('rsp'=>'success','msg'=>'');

        if ($order['pause'] == 'false'){
           $rs['rsp'] = 'fail';
           $rs['msg'] = '请先暂停订单';
        }

        if($order['process_status'] == 'splited'){
            //打回已存在的发货单
            $oOrder->rebackDeliveryByOrderId($order_id);
            $new_order['order_id']      = $order_id;
            $new_order['old_amount']     = $order['total_amount'];
            $new_order['confirm']        = 'N';
            $new_order['process_status'] = 'unconfirmed';
            $new_order['pause']          = 'false';
            //更新order
            $oOrder->save($new_order);
        }

         //增加不能编辑状态的判断
        $result = $oOrder->not_allow_edit($order_id);
        if($result['res'] == 'false'){
            $rs['rsp'] = 'fail';
            $rs['msg'] = $result['msg'];
        }

        #存在未处理的退款申请
        $refund_applyObj = &app::get('ome')->model('refund_apply');
        $refund_apply_filter = array('order_id'=>$order_id,'status'=>array('0','1','2','6'));
        $refund_apply_detail = $refund_applyObj->dump($refund_apply_filter, 'apply_id');
        if ($refund_apply_detail){
            $rs['rsp'] = 'fail';
            $rs['msg'] = '退款申请中的订单不允许编辑,请先处理退款申请!';
        }

        if ($rs['rsp'] == 'success'){
            $sh_base_url = kernel::base_url(1);
            $finder_id = $_GET['finder_id'];
            $rs['url'] = $sh_base_url.'/index.php?app=ome&ctl=admin_order&act=update_iframe_api&p[0]='.$order_id.'&p[1]='.$finder_id;
        }

        $this->pagedata['rs'] = $rs;
        $this->pagedata['order_id'] = $order_id;
        $this->singlepage('admin/order/update_iframe.html');
   }


   /**
   * 订单编辑接口
   * @access public
   * @param number $order_id 订单ID
   * @param String $finder_id FINDER_id
   * @return json
   */
   function update_iframe_api($order_id,$finder_id=''){
       $sh_base_url = kernel::base_url(1);
       $notify_url = $sh_base_url.'/index.php?app=ome&ctl=admin_order&act=update_iframe&p[0]='.$order_id.'&finder_id='.$finder_id;
       $ext['notify_url'] = $notify_url;
       $rs = kernel::single('ome_order')->update_iframe($order_id,$queue=true,$ext);
       return $rs;
   }


   /**
   * 更新订单同步状态
   * @access public
   * @param number $order_id 订单ID
   * @param String $sync_status 编辑同步状态
   * @return json
   */
   function set_sync_status($order_id,$sync_status=''){
       $rs = array('rsp'=>'fail','msg'=>'');
       if (in_array($sync_status,array('fail','success'))){
           $orderSync = kernel::single('ome_order');
           if ($orderSync->set_sync_status($order_id,$sync_status)){
               $rs['rsp'] = 'success';
           }
       }
       die(json_encode($rs));
   }

   /**
   * 关闭订单编辑页面后所做操作
   * @access public
   * @param number $order_id 订单ID
   * @param String $is_operator 是否记录操作日志
   * @return bool
   */
   function update_iframe_after($order_id,$is_operator='1'){
       $rs = array('rsp'=>'fail','msg'=>'');
       if (empty($order_id)) die(json_encode($rs));

       #更新订单暂停状态为恢复
       $oOrder = &$this->app->model('orders');
       $oOrder->renewOrder($order_id);

       if ($is_operator == '1'){
           #记录操作日志
           $oOperation_log = &$this->app->model('operation_log');
           $oOperation_log->write_log('order_edit@ome',$order_id,"订单编辑");
       }

       $rs['rsp'] = 'success';
       die(json_encode($rs));
    }

   /**
    * 获取店铺订单信息
    *
    * @return void
    * @author
    **/
    function getShopOrder(){
       $Oshop = $this->app->model('shop');
       $shop = $Oshop->getList('name,shop_id,node_type,node_id,business_type as order_type');

       $shops = array();
       $config = ome_shop_type::get_shoporder_config();
       $allshops = array_keys($config);
       $default_shop = '';
       foreach ($shop as $k=>$v) {
            if(!empty($v['node_id']) && in_array($v['node_type'],$allshops) && ($config[$v['node_type']]=='on')){
                $shops[$v['shop_id']] = array('name'=>$v['name'],'node_type'=>$v['node_type'],'shop_id'=>$v['shop_id']);
                $shops[$v['shop_id']]['order_type'] = ($v['order_type'] == 'zx') ? 'direct' : 'agent';

            }
       }

       $this->pagedata['shops'] = $shops;

       $this->display('admin/order/getshoporder.html');
    }

    function fetchCombineDelivery(){
        $order_id = intval($_POST['order_id']);
        $combine_delivery = $this->app->model('delivery')->fetchCombineDelivery($order_id);

        if($combine_delivery){
            echo json_encode($combine_delivery);
        }
    }

    function combineOrderNotify(){
        $order_id = intval($_GET['order_id']);
        $combine_delivery = $this->app->model('delivery')->fetchCombineDelivery($order_id);

        $this->pagedata['combine_delivery'] = $combine_delivery;
        $this->page('admin/order/order_combinenotify.html');
    }

    /**
     * 暂停订单
     * @
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function pause_order($order_id)
    {
        $oOrders = &app::get('ome')->model('orders');
        if ($_POST) {
            
            $order_id = $_POST['order_id'];
            $rs = $oOrders->pauseOrder($order_id);
            $finder_id = $_POST['finder_id'];
            if ($rs['rsp'] == 'fail') {

              #[拆单]暂停发货单失败_提醒消息
              if($rs['is_split'] == 'true')
              {
                  $this->pagedata['order_id'] = $order_id;
                  $this->pagedata['message'] = $rs['msg'];
                  $this->display('admin/order/order_pause_showmsg.html');
                  exit;
              }
              else 
              {
                echo "<script>alert('订单暂停失败,原因是:".$rs['msg']."');</script>";
              }                
            }else{
                echo "<script>alert('订单暂停成功');</script>";
            }
            echo "<script>$$('.dialog').getLast().retrieve('instance').close();window.finderGroup[$(document.body).getElement('input[name^=_finder\[finder_id\]]').value].refresh();</script>";
        }
        $this->pagedata['order_id'] = $order_id;

        $orders = $oOrders->dump($order_id,'process_status');
        $this->pagedata['orders'] = $orders;
        unset($orders);
        unset($order_id);
        $this->display('admin/order/order_pausenotify.html');
    }

    public function downloadPrintSite() {
        $product_type = isset($_GET['product_type']) ? trim($_GET['product_type']) : 'tp';
        $url = 'http://update.tg.taoex.com/tg.php';
        $http = kernel::single('base_httpclient');
        $secrect = '67C70BDFAF354401D9D2192377D09DC0';
        $params = array(
            'app_key' => 'taoguan',
            'product_type' => $product_type,
            'timestamp' => time(),
            'format' => 'json'
        );
        $sign = strtoupper(md5($this->assemble($params).$secrect));
        $params['sign'] = $sign;
        $result = $http->post($url, $params);
        echo $result;exit;
    }
    
     
    public function errorReportPrintSite() {
        $product_type = isset($_GET['product_type']) ? trim($_GET['product_type']) : 'tp';
        $base_host = kernel::single('base_request')->get_host();
        $url = 'http://update.tg.taoex.com/error_report.php';
        $http = kernel::single('base_httpclient');
        $secrect = '67C70BDFAF354401D9D2192377D09DC0';
        $params = array(
            'app_key' => 'taoguan',
            'product_type' => $product_type,
            'timestamp' => time(),
            'format' => 'json',
            'errmsg' => $_POST['errmsg'],
            'domain' => $base_host, 
        );
        $sign = strtoupper(md5($this->assemble($params).$secrect));
        $params['sign'] = $sign;
        $result = $http->post($url, $params);
        print_r($result);
        echo $result;exit;
    }

    /**
     * 下载控件
     */
    public function diagLoadPrintSite() {
        $this->page('admin/delivery/controllertmpl/diag_load_print_site.html');
    }

    
    protected function assemble($params) {
        if(!is_array($params)) {
            return null;
        }
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params as $pk => $pv) {
            if (is_null($pv)) {
                continue;
            }
            if (is_bool($pv)) {
                $pv = ($pv) ? 1 : 0;
            }
            $sign .= $pk . (is_array($pv) ? $this->assemble($pv) : $pv);
        }
        return $sign;
    }

       /*------------------------------------------------------ */
    //-- 我的异常订单[Author: ExBOY]
    /*------------------------------------------------------ */
    function retrial()
    {
        $op_id = kernel::single('desktop_user')->get_id();
        $this->title = '我的异常订单';
        //$this->base_filter['op_id'] = $op_id;
        $this->base_filter['abnormal'] = 'true';
        $this->base_filter['process_status'] = 'is_retrial';
        
        //超级管理员
        if(kernel::single('desktop_user')->is_super())
        {
            if(isset($this->base_filter['op_id']))
            unset($this->base_filter['op_id']);
            
            if(isset($this->base_filter['group_id']))
            unset($this->base_filter['group_id']);
        }
        
        $params     = array(
            'title' => $this->title,
            'base_filter' => $this->base_filter,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'orderBy' => 'createtime desc',
            'finder_aliasname' => 'order_retrial'.$op_id,
            'finder_cols' => 'column_abnormal_status, column_mark_text, mark_text, abnormal_status, process_status, column_confirm,order_bn,shop_id,member_id,total_amount,is_cod,pay_status,ship_status,createtime,paytime',
        );
        
        $this->finder('ome_mdl_orders', $params);
    }
    /*------------------------------------------------------ */
    //-- 回滚"我的异常订单"[Author: ExBOY]
    /*------------------------------------------------------ */
    function retrial_rollback($order_id)
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        
        #复审订单详情
        $oRetrial  = &app::get('ome')->model('order_retrial');
        $row       = $oRetrial->getList('*', array('order_id'=>$order_id), 0, 1, 'dateline DESC');
        $row       = $row[0];
        $this->pagedata['row']      = $row;
        
        #订单和订单快照信息&&价格监控
        $datalist   = $oRetrial->contrast_order($order_id, $row['id']);

        $this->pagedata['order_profit']         = $datalist['order_profit'];
        $this->pagedata['old_price_monitor']    = $datalist['old_price_monitor'];
        $this->pagedata['new_price_monitor']    = $datalist['new_price_monitor'];
        $this->pagedata['monitor_flag']         = $datalist['monitor_flag'];
        $this->pagedata['setting_is_monitor']   = $datalist['setting_is_monitor'];

        $this->pagedata['order_old']    = $datalist['order_old'];
        $this->pagedata['order_new']    = $datalist['order_new'];
        
        #回滚订单模板标识
        $this->pagedata['rollback']    = true;
        
        $this->singlepage('admin/order/retrial_normal.html');
    }
    /*------------------------------------------------------ */
    //-- 获取订单复审配置及复审规则  ExBOY
    /*------------------------------------------------------ */
    function get_setting_retrial()
    {
        $is_retrial        = &app::get('ome')->getConf('ome.order.is_retrial');
        $setting_retrial   = &app::get('ome')->getConf('ome.order.retrial');

        if($setting_retrial['product'] != '1' && $setting_retrial['order'] != '1' && $setting_retrial['delivery'] != '1')
        {
            $is_retrial     = 'false';
        }
        $setting_retrial['is_retrial']  = $is_retrial;
        
        if($is_retrial == 'false')
        {
            unset($setting_retrial);
        }
        
        return $setting_retrial;
    }
    
    #批量设置备注
    function BatchUpMemo(){
        $this->_request = kernel::single('base_component_request');
        $order_info = $this->_request->get_post();
        #不支持全部备注
        if($order_info['isSelectedAll'] == '_ALL_'){
            echo '暂不支持全部备注!';exit;
        }
        if(empty($order_info['order_id'])){
            echo '请选择订单!';exit;
        }
        #统计批量支付订单数量
        $this->pagedata['order_id'] = serialize($order_info['order_id']);
        $this->display('admin/order/batch_update_memo.html');
    }
    #批量设置备注
    function doBatchUpMemo(){
        $this->begin("index.php?app=ome&ctl=admin_order&act=index");
        $all_order_id = $_POST['order_id'];
        if(!empty($all_order_id)){
            $arr_order_id = unserialize($all_order_id);
        }
        if(empty($all_order_id)){
            $this->end(false,'提交数据有误!');
        }
        $oOrders = $this->app->model('orders');
        $oOperation_log = &app::get('ome')->model('operation_log');
        foreach($arr_order_id as $key=>$order_id){
            $plainData = $memo = array();
            
            $order_info = $oOrders->dump(array('order_id'=>$order_id), 'mark_text,mark_type');
            $oldmem = unserialize($order_info['mark_text']);
            $op_name = kernel::single('desktop_user')->get_name();
            if ($oldmem){
                foreach($oldmem as $k=>$v){
                    $memo[] = $v;
                }
            }
            $newmemo =  htmlspecialchars($_POST['mark_text']);
            $newmemo = array('op_name'=>$op_name, 'op_time'=>date('Y-m-d H:i:s',time()), 'op_content'=>$newmemo);
            $memo[] = $newmemo;
            
            $plainData['order_id'] = $order_id;
            $plainData['mark_text'] = serialize($memo);
            $plainData['mark_type'] = $order_info['mark_type'];
            $oOrders->save($plainData);
            
            //写操作日志
            $memo = "批量修改订单备注";
            //订单留言 API
             foreach(kernel::servicelist('service.order') as $object=>$instance){
                if(method_exists($instance, 'update_memo')){
                    $instance->update_memo($order_id, $newmemo);
                }
            } 
            $oOperation_log->write_log('order_modify@ome',$order_id,$memo);    
        }
        $this->end(true, app::get('base')->_('修改成功'));
    }
     /**
     * [拆单]计算物流费 ExBOY
     *
     * @return void
     * @author chenping
     **/
    public function calFreightCost($shipping_area,$logi_id,$weight)
    {
        if (false !== strpos($shipping_area,'mainland')) list($area_prefix,$area_chs,$area_id) = explode(':',$shipping_area);
        $cost_freight = app::get('ome')->model('delivery')->getDeliveryFreight($area_id,$logi_id,$weight);

        echo $cost_freight;exit;
    } 

    #重新强制获取CRM赠品数据
    public function doRequestCRM(){
        $orders = $_POST['order_id'];
        if(empty($order_id)){
            if($_POST['isSelectedAll'] == '_ALL_'){
                $base_filter['op_id'] = kernel::single('desktop_user')->get_id();
                $base_filter['assigned'] = 'assigned';
                $base_filter['abnormal'] = "false";
                $base_filter['is_fail'] = 'false';
                $base_filter['status'] = 'active';
                $base_filter['process_status'] = array('unconfirmed', 'confirmed', 'splitting');
                $base_filter['archive'] = 0;
                $base_filter['pause'] = 'false';
                $base_filter['order_confirm_filter'] = '(sdb_ome_orders.auto_status & '.omeauto_auto_const::__CRMGIFT_CODE.'='.omeauto_auto_const::__CRMGIFT_CODE.')';
                #超级管理员
                if(kernel::single('desktop_user')->is_super()){
                    if(isset($base_filter['op_id']))
                        unset($base_filter['op_id']);
    
                    if(isset($base_filter['group_id']))
                        unset($base_filter['group_id']);
                }
                $_order_id = $this->app->model('orders')->getList('order_id',$base_filter);
                foreach($_order_id as $v){
                    $orders[] = $v['order_id'];
                }
            }
        }
       $obj_crm = kernel::single('ome_preprocess_crm');
        $this->begin("index.php?app=ome&ctl=admin_order&act=confirm&flt=unmyown");
        if(empty($orders)){
            $this->end(false, app::get('base')->_('请选择单据'));
        }
        foreach($orders as $order_id){
            #重新获取CRM赠品
           $obj_crm->process($order_id,$msg,'doRequestCRM');
        }
        $this->end(true, app::get('base')->_('处理完成'));
    }
    #华强宝物流查询的路由
    function delviery_hqepay(){
        $logi_no = $_POST['logi_no'];
        $order_bn = $_POST['order_bn'];
        $delivery_html = kernel::single('ome_hqepay')->detail_delivery($logi_no,$order_bn);
        echo  $delivery_html;
    }
    
    /**
     * 批量跨境订单
     * @author ExBOY
     */
    function BatchDeclare()
    {
        $this->_request    = kernel::single('base_component_request');
        $order_info        = $this->_request->get_post();
        
        if(empty($order_info['order_id']))
        {
            echo '请选择订单!';exit;
        }
        
        #统计批量支付订单数量
        $this->pagedata['order_ids'] = serialize($order_info['order_id']);
        $this->display('admin/order/batch_update_declare.html');
    }
    
    /**
     * 批量设置为跨境订单
     * @author ExBOY
     */
    function doBatchDeclare()
    {
        $this->begin("index.php?app=ome&ctl=admin_order&act=active");
        
        $order_ids       = array();
        if(!empty($_POST['order_ids']))
        {
            $order_ids    = unserialize($_POST['order_ids']);
        }
        if(empty($order_ids))
        {
            $this->end(false,'提交数据有误!');
        }
        if(count($order_ids) > 100)
        {
            $this->end(false,'最多一次批量新建100个跨境订单!');
        }
        
        /*------------------------------------------------------ */
        //-- 设置为跨境订单
        /*------------------------------------------------------ */
        $oCustoms   = &app::get('customs')->model('orders');
        $result     = $oCustoms->create_declare($order_ids);
        
        if($result['rsp'] == 'succ')
        {
            $this->end(true, $result['msg']);
        }
        else
        {
            $this->end(false, $result['error_msg']);
        }
    }
     
     /**
      * Short description.
      * @param   type    $varname    description
      * @return  type    description
      * @access  public
      * @author cyyr24@sina.cn
      */
     function test()
     {
         
         kernel::single('ome_service_delivery')->delivery(3);
         //kernel::single('ome_service_delivery')->update_logistics_info(3);
     }

	 function sync_ax(){
		 $this->begin();
		 $order_ids = $_POST['order_id'];
		 $objOrder = app::get('ome')->model('orders');
		 $objOrderDelivery = app::get('ome')->model('delivery_order');
		 foreach($order_ids as $order_id){
			 $orders  = $objOrder->dump($order_id);
			 //echo "<pre>";print_r($orders);exit;
			 if($orders['process_status']=='splited'){
				 $delevery_id = $objOrderDelivery->getList('*',array('order_id'=>$order_id));
				// echo "<pre>";print_r($order_id);
				// echo "<pre>";print_r($delevery_id);exit;
				 kernel::single('omeftp_service_deliveryh')->delivery($delevery_id[0]['delivery_id']);
			 }
		 }
		 $this->end(true,'操作成功');
	 }
}
