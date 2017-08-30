<?php
class wms_ctl_admin_receipts_print extends desktop_controller {

    var $workground = "wms_delivery";

    var $dlyCorp_tab = 'show';

    /**
     * 添加发货/单据打印下的顶部view
     */
    function _views() {
        if($this->dlyCorp_tab == 'hidden'){
           return '';
        }

        $status = kernel::single('base_component_request')->get_get('status');
        $sku = kernel::single('base_component_request')->get_get('sku');

        $query = array(
            'app'    => 'wms',
            'ctl'    => 'admin_receipts_print',
            'act'    => 'index',
            'status' => $status,
            'sku'    => $sku,
        );

        //所有自建仓
        $ownerBranch = array();
        $ownerBranch = kernel::single('ome_branch_type')->getOwnBranchIds();

        $sub_menu = $this->getView($status);
        $i = 0;
        $mdl_order = app::get('wms')->model('delivery');
        foreach ($sub_menu as $k => $v) {
            //非管理员取管辖仓与自建仓的交集
            $v['filter']['ext_branch_id'] = $v['filter']['ext_branch_id'] ? array_intersect($v['filter']['ext_branch_id'], $ownerBranch) : $ownerBranch;
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $query['view'] = $i++;
            $query['logi_id'] = urlencode($v['filter']['logi_id']);
            $sub_menu[$k]['href'] = 'index.php?' . http_build_query($query);
            unset($v);
        }
        return $sub_menu;
    }

    function getView($status) {
        $oDlycorp = &app::get('ome')->model('dly_corp');
        $submenu = $oDlycorp->getList('corp_id,name',array('disabled'=>'false'));

        if (empty($submenu))
            return $submenu;

        $tmp_filter = array('type' => 'normal');
        $s_filter = $this->analyseStatus($status);
        $tmp_filter = array_merge($tmp_filter, $s_filter);

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids) {
                $tmp_filter['ext_branch_id'] = $_POST['branch_id'] ? $_POST['branch_id'] : $branch_ids;
            } else {
                $tmp_filter['ext_branch_id'] = 'false';
            }
        }

        $c = 1;
        $sub_menu[0] = array(
            'label' => $tmp_filter['_title_'],
            'filter' => $tmp_filter,
            'optional' => false
        );
        
        #第三方发货时，显示已发货、未发货
        if($_GET['ctl'] == 'admin_receipts_outer') {
            $c = 3;#物流公司view在未发货之后
            $outer_filter = $tmp_filter;
            #让已发货、未发货显示在全部之后
            $outer = $this->shipStatus();
            foreach($outer as $key=>$v){
                if($key == 'succ'){
                    $outer_filter['status'] = array(3);
                }else{
                    #所有不属于发货成功的，都是未发货
                    $outer_filter['status'] = array(0);
                    //unset($tmp_filter['status']);
                }
                $sub_menu[] = array(
                        'label' => app::get('base')->_($v),
                        'filter' => $outer_filter,
                        'optional' => false
                );
            }
        }

        foreach ($submenu as $keys => $values) {
            $sub_menu[$c] = array(
                'label' => $values['name'],
                'filter' => array_merge($tmp_filter, array('logi_id' => $values['corp_id'])),
                'optional' => false
            );
            $c++;
        }
        return $sub_menu;
    }

    public function notice()
    {
        $this->page('admin/receipts/notice.html');
    }

    function index() {
        if($_GET['status']=='' || $_GET['status']==5){
            $this->dlyCorp_tab = 'hidden';
        }
        # 操作员ID号
        $op_id  = $this->user->get_id();
        $sku = kernel::single('base_component_request')->get_get('sku');

        $cfgr = app::get('wms')->getConf('wms.delivery.cfg.radio');

        if(empty($cfgr) && $_GET['status'] == 0) $cfgr = 1;
        if ($cfgr == 2 && isset($sku) && $sku == '') {

            $jumpto = app::get('wms')->base_url(1).'index.php?app=wms&ctl=admin_receipts_print&act=index&status=0&sku=single';
            $this->pagedata['msg'] = '对不起！由于您设置了按品类打印，请去单品，多品打印！将单品和多品分开打印有助于提升效率！';
            $this->pagedata['jumpto'] = $jumpto;
            $this->pagedata['wait'] = 3;
            $this->display('splash/notice.html','desktop');
            exit;
        }

        if ($cfgr == 1 && isset($sku) && in_array($sku,array('single','multi'))) {
            $jumpto = app::get('wms')->base_url(1).'index.php?app=wms&ctl=admin_receipts_print&act=index&status=0&sku=';
            $this->pagedata['msg'] = '对不起！由于您设置了经典打印，请去待打印打印！';
            $this->pagedata['jumpto'] = $jumpto;
            $this->pagedata['wait'] = 3;
            $this->display('splash/notice.html','desktop');
            exit;
        }

        # 发货配置
        $deliCfgLib = kernel::single('wms_delivery_cfg');

        $title = '';

        $filter['type'] = 'normal';

        //分析status的filter条件
        $tmp_filter = $this->analyseStatus($_GET['status']);
        $filter = array_merge($filter, $tmp_filter);

    //获取操作员管辖仓库
        if($_GET['btype']==2){
            //导出的时候走这里取第三方仓
            $ownerBranch = array();
        $ownerBranch = kernel::single('ome_branch_type')->getOtherBranchLists();
        }else{
            //所有自建仓
            $ownerBranch = array();
            $ownerBranch = kernel::single('ome_branch_type')->getOwnBranchIds();
        }

        # 获取操作员管辖仓库
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $oBranch = &app::get('ome')->model('branch');
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids) {
                $filter['ext_branch_id'] = $_POST['branch_id'] ? $_POST['branch_id'] : $branch_ids;

                $filter['ext_branch_id'] = array_intersect($filter['ext_branch_id'], $ownerBranch);
            } else {
                $filter['ext_branch_id'] = 'false';
            }
        } else {
            $filter['ext_branch_id'] = $ownerBranch;
        }
        $attach = '&status=' . $_GET['status'] . '&logi_id=' . $_GET['logi_id'];
        if(isset($sku)) $attach .= '&sku='.$sku;
        $use_buildin_import = false;
        $user = kernel::single('desktop_user');

        if ($user->has_permission('wms_process_receipts_print_export')) {
            $use_buildin_import  = true;
        }
        $params = array(
            'title' => $filter['_title_'],
            'actions' => array(
                'stock' => array(
                    'label' => '打印备货单',
                    'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintStock' . $attach,
                    'target' => "_blank",
                ),
                'delie' => array(
                    'label' => '打印发货单',
                    'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintMerge' . $attach,
                    'target' => '_blank',
                ),
                'merge' => array(
                    'label' => '联合打印',
                    'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toMergePrint' . $attach,
                    'target' => '_blank',
                ),
                'expre' => array(
                    'label' => '打印快递单',
                    'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintShip' . $attach,
                    'target' => '_blank',
                ),
            ),
            'base_filter' => $filter,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => $use_buildin_import ,
            'use_buildin_import' => false,
            'use_buildin_filter' => true,
            'max_actions' => 8,
            'use_view_tab' => true,
            'finder_aliasname' => 'delivery_print' . $op_id,
            //从载方法 以解决 发货中未录入快递单号不能过滤的bug
            'object_method' => array('count' => 'count_logi_no', 'getlist' => 'getlist_logi_no'),
        );

        //发货模板配置
        $delivery_cfg = app::get('wms')->getConf('wms.delivery.status.cfg');
        
        //发货单控件风格
        if (isset($delivery_cfg['set']['wms_delivery_print_mode']) && $delivery_cfg['set']['wms_delivery_print_mode'] == 1) {
            $params['actions']['delie']['submit'] = 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintMergeNew' . $attach;
        }

        //备货控件风格
        if (isset($delivery_cfg['set']['wms_stock_print_mode']) && $delivery_cfg['set']['wms_stock_print_mode'] == 1) {
            $params['actions']['stock']['submit'] = 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintStockNew' . $attach;
        }
    
        if (app::get('logisticsmanager')->is_installed()) {
            $params['actions']['newexpre'] = array(
                'label'  => '打印快递单',
                'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintExpre' . $attach,
                'target' => '_blank',
            );
            unset($params['actions']['expre']);
        }

        //判断未打印的列表页可以设置列表分页到500
        if($_GET['status'] == 0){
            $this->max_plimit = 500;
            $params['plimit_in_sel'] = array(500,400,300,200,100,50,20,10);

            if(app::get('ome')->getConf('delivery.bycreatetime'.$op_id) == 1){
                $label = '按默认排序显示';
                $order_val = 0;
            }else{
                $label = '按成单时间显示';
                $order_val = 1;
            }
            $params['actions']['orderbycreatetime'] = array('label'=>$label,'href'=>'index.php?app=wms&ctl=admin_receipts_print&act=orderbycreatetime&p[0]='.$sku.'&p[1]='.$order_val.'&p[2]='.$op_id);
        }

        //选择显示打印的按钮
        $showStockBtn = $deliCfgLib->analyse_btn_status('stock',$sku);
        if ($showStockBtn == false) {
            unset($params['actions']['stock']);
        }
        $showDelieBtn = $deliCfgLib->analyse_btn_status('delie',$sku);
        if ($showDelieBtn == false) {
            unset($params['actions']['delie']);
        }
        $showMergeBtn = $deliCfgLib->analyse_btn_status('merge',$sku);
        if ($showMergeBtn == false) {
            unset($params['actions']['merge']);
        }

        //暂停列表不显示按钮
        if ($_GET['status'] == 6) {
            unset($params['actions']['stock']);
            unset($params['actions']['delie']);
            unset($params['actions']['style']);
            unset($params['actions']['expre']);
            unset($params['actions']['merge']);
        }

        # 如果是第一个TAB 弹出对话框
        if ($_GET['view'] == 0 || empty($_GET['view'])) {
            foreach ($params['actions'] as $key => $act) {
                $act['confirm'] = "我们强烈建议打印任务都在进入各物流公司的分页夹后进行，在全部里只进行查找等操作及显示结果的处理，以避免出现不该发生的错误。你还确定要进行打印操作吗？\n\n注意：操作前请确认打印机中的面单和要打印的单据相匹配。";
                $params['actions'][$key] = $act;
            }
        }

        # 在列表上方添加搜索
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            $panel->setId('delivery_finder_top');
            $panel->setTmpl('admin/finder/finder_panel_filter.html');
            $panel->show('wms_mdl_delivery', $params);
        }

        # 多打印模板--独立按钮
        if ($_GET['status'] != 6) {
            $otmplModel = app::get('ome')->model('print_otmpl');
            $filter = array('disabled'=>'false','aloneBtn'=>'true','open'=>'true','type'=>array('delivery','stock'));
            $aloneBtnList = $otmplModel->getList('id,btnName,type',$filter);

            $typeAct = array('delivery'=>'toPrintMerge','stock'=>'toPrintStock');
            foreach ($aloneBtnList as $key=>$value) {
                $params['actions']['aloneBtn'.$key] = array(
                    'label' => $value['btnName'],
                    'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act='. $typeAct[$value['type']] . $attach.'&otmplId='.$value['id'],
                    'target' => '_blank',
                );
            }
            //独立按钮
            $expressObj = app::get('logisticsmanager')->model('express_template');
            $alone_filter = array('aloneBtn'=>'true','status'=>'true','template_type'=>array('delivery','stock'));
            $alonexpressList = $expressObj->getList('template_id,btnName,template_type',$alone_filter);
            
            $typeelectl = array('delivery'=>'toPrintMergeNew','stock'=>'toPrintStockNew');
            foreach ($alonexpressList as $k=>$v ) {
                $params['actions']['aloneBtn'.$k] = array(
                    'label' => $v['btnName'],
                    'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act='. $typeelectl[$v['template_type']] . $attach.'&otmplId='.$v['template_id'],
                    'target' => '_blank',
                );
            }
        }

        # 批量更换物流按钮
        $params['actions']['changeDly'] = array(
            'label' => '批量更换物流',
            'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toChangeDly' . $attach,
            'target' => 'dialog::{title:\'批量更换物流公司\',width:680,height:500}',
        );

        $this->finder('wms_mdl_delivery', $params);
    }

    /*
     * 分析状态
     */

    function analyseStatus($status, $type = 'normal') {
        $sku = kernel::single('base_component_request')->get_get('sku');
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        if ($type == 'normal') {
            switch ($status) {
                case '':
                    $title = '全部';
                    $filter = array();
                    $filter['status'] = array('0','3');
                    break;
                case 0:
                    if ($sku == 'single') {
                        $title = '单品打印';
                    }elseif ($sku == 'multi') {
                        $title = '多品打印';
                    }else{
                        $title = '待打印';
                    }

                    $btncombi = $deliCfgLib->btnCombi($sku);
                    switch ($btncombi) {
                        case '1_1':
                            $filter['todo'] = "1";
                            $filter['status'] = array('0');
                            $filter['process_status'] = array(0,1);
                            break;
                        case '1_0':
                            $filter['todo'] = "2";
                            $filter['status'] = array('0');
                            $filter['process_status'] = array(0,1);
                            break;
                        case '0_1':
                            $filter['todo'] = "3";
                            $filter['status'] = array('0');
                            $filter['process_status'] = array(0,1);
                            break;
                        case '0_0':
                            $filter['todo'] = "4";
                            $filter['status'] = array('0');
                            $filter['process_status'] = array(0,1);
                            break;
                    }
                    break;
                case 1:
                    $title = '已打印';
                    $filter['process_status'] = 1;
                    $filter['status'] = array('0');
                    $btncombi_single = $deliCfgLib->btnCombi('single');
                    $btncombi_multi = $deliCfgLib->btnCombi('multi');
                    $btncombi_basic = $deliCfgLib->btnCombi();
                    $filter['print_finish'] = array(
                        ''=> $btncombi_basic,
                        'single' => $btncombi_single,
                        'multi' => $btncombi_multi,
                    );
                    break;
                case 2:
                    $title = '未录入物流单号';
                    $filter['no_logi_no'] = true;
                    $filter['status'] = array('0');
                    break;
                case 3:
                    $title = '已校验';
                    $filter['process_status'] = 3;
                    $filter['status'] = array('0');
                    break;
                case 4:
                    $title = '未发货';
                    $filter['status'] = array('0');
                    break;
                case 5:
                    $title = '已发货';
                    $filter['status'] = 3;
                    break;
                case 6:
                    $title = '暂停列表';
                    $filter['status'] = 2;
                    break;
            }
        } elseif ($type == 'refunded') {
            switch ($status) {
                case '':
                    $title = '未发货';
                    $filter['status'] = array('0');
                    break;
                case 1:
                    $title = '未发货';
                    $filter['status'] = array('0');
                    break;
                case 2:
                    $title = '已发货';
                    $filter['status'] = 3;
                    break;
            }
        }
        // 打印类型
        if ($sku) {
            if ($sku == 'single') {
                $filter['skuNum'] = 1;
            }elseif ($sku == 'multi') {
                $filter['skuNum|than'] = 1;
            }
        }

        //默认条件
        $filter['disabled'] = 'false';

        $schema = app::get('ome')->model('delivery')->schema;
        if(isset($_POST['status']) && $schema['columns']['status']['type'][$_POST['status']]){
            $filter['status'] = $_POST['status'];
        }

        $filter['_title_'] = $title;

        return $filter;
    }

    function processFilter() {
        //来源于
        if ($_GET['from'] && $_GET['from'] == 'refunded') {
            //原样寄回
            $filter['type'] = 'reject';
            //TODO 可能通过getView()方法实现 （待升到1.3 moontools）
            $tmp = $this->analyseStatus($_GET['status'], 'refunded'); //判断为哪种列表
            $filter = array_merge($filter, $tmp);
        } else {
            //正常发货
            $filter['type'] = 'normal';
            //TODO 可能通过getView()方法实现 （待升到1.3 moontools）
            $tmp = $this->analyseStatus($_GET['status']); //判断为哪种列表
            $filter = array_merge($filter, $tmp);
        }
        //解析 view条件
        if ($_GET['logi_id']) {
            $filter['logi_id'] = urldecode($_GET['logi_id']);
        }
        /*
         * 可扩展条件
         */

        return $filter;
    }

    /**
     * 处理发货单ID
     */
    function processDeliveryId() {
        $delivery_ids = $_REQUEST['delivery_id'];
        $isSelectAll = $_REQUEST['isSelectedAll'];
        $filter = $this->processFilter();

        $printShip = in_array($_GET['act'], array('toPrintShip','toPrintExpre')) ? true : false;
        
        $logi = array();

        $dlyObj = &app::get('wms')->model('delivery');
        $dlyCheckLib = kernel::single('wms_delivery_check');

        if ($isSelectAll == '_ALL_') {
            //所有数据
            $ids = $dlyObj->getList('delivery_id,logi_id,branch_id', $filter, 0, -1);
            $dly_ids = array();
            $branch = array();
            if ($ids) {
                foreach ($ids as $id) {
                    if ($printShip){
                        $logi[$id['logi_id']]++;
                    }

                    $dly_ids[] = $id['delivery_id'];
                    $branch[$id['branch_id']] = $id['delivery_id'];
                }
                if (count($logi) > 1)
                    exit("当前系统不支持同时打印二种不同快递类型的单据，请重新选择后再试。");
                if (count($branch) > 1)
                    exit("当前系统不支持同时打印二个仓库的单据，请重新选择后再试。");
                return $dly_ids;
            }
            exit("无数据");
        }else {
            $delivery_ids = array_filter($delivery_ids); //去除值 为空，null，FALSE的key和value
            //选择的数据
            if ($delivery_ids) {
                if (is_array($delivery_ids)) {
                    $filter_['delivery_id'] = $delivery_ids;
                    $ids = $dlyObj->getList('delivery_id,logi_id,branch_id', $filter_, 0, -1);
                    $dly_ids = array();
                    $branch = array();
                    if ($ids) {
                        foreach ($ids as $id) {
                            if ($printShip){
                                $logi[$id['logi_id']]++;
                            }

                            //$dly_ids[] = $id['delivery_id'];
                            $branch[$id['branch_id']] = $id['delivery_id'];
                        }

                        if (count($logi) > 1){
                            exit("当前系统不支持同时打印二种不同快递类型的单据，请重新选择后再试。");
                        }

                        if (count($branch) > 1){
                            exit("当前系统不支持同时打印二个仓库的单据，请重新选择后再试。");
                        }
                        return $delivery_ids;
                    }
                    exit("无数据");
                }else {
                    return array($delivery_ids);
                }
            } else {
                exit("请选择数据");
            }
        }
    }

    /*
     * 联合打印
     */
    function toMergePrint() {
        $_err = 'false';
        //单品、多品标识
        $sku = kernel::single('base_component_request')->get_get('sku');
        $sku = $sku ? $sku : '';

        $now_print_type = 'merge';

        //获取当前待打印的发货单过滤条件
        $filter_condition = $this->getPreparePrintIds();

        $PrintLib = kernel::single('wms_delivery_print');
        $print_data = $PrintLib->getPrintDatas($filter_condition,$now_print_type,$sku,true,$msg);
        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->headerErrorMsgDisply($msg['error_msg']);
        }

        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->message($msg['warn_msg']);
            exit;
        }

        $PrintMergeLib = kernel::single('wms_delivery_print_merge');
        $format_data = $PrintMergeLib->format($print_data, $sku,$_err);
        $this->pagedata = $format_data;

        //发货单拼接
        $this->pagedata['vid'] = implode(',', $print_data['ids']);
        //是否存在错误信息
        $this->pagedata['err'] = $_err;
        $this->pagedata['errBns'] = $print_data['errBns'];
        $this->pagedata['errIds'] = $print_data['errIds'];
        $this->pagedata['errInfo'] = $print_data['errInfo'];
        $this->pagedata['idents'] = $print_data['identInfo']['items'];
        $this->pagedata['ident'] = join(',', $print_data['identInfo']['idents']);
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['time'] = date("Y-m-d H:i:s");
        $this->pagedata['title'] = '联合打印单打印';
        $this->pagedata['sku'] = $sku;

        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],$now_print_type,$this);
    }

    /**
     * 打印发货单
     *
     */
    function toPrintMerge() {
        $_err = 'false';

        # 多品单品标识
        $sku = kernel::single('base_component_request')->get_get('sku');
        $sku = $sku ? $sku : '';

        $now_print_type = 'delivery';

        //获取当前待打印的发货单过滤条件
        $filter_condition = $this->getPreparePrintIds();

        $PrintLib = kernel::single('wms_delivery_print');
        $print_data = $PrintLib->getPrintDatas($filter_condition,$now_print_type,$sku,true,$msg);
        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->headerErrorMsgDisply($msg['error_msg']);
        }

        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->message($msg['warn_msg']);
            exit;        }

        $PrintDlyLib = kernel::single('wms_delivery_print_delivery');
        $format_data = $PrintDlyLib->format($print_data, $sku,$_err);
        $this->pagedata = $format_data;

        $this->pagedata['errBns'] = $print_data['errBns'];
        $this->pagedata['sku'] = $sku;
        $this->pagedata['err'] = $_err;
        $this->pagedata['idents'] = $print_data['identInfo']['items'];
        $this->pagedata['ident'] = join(',', $print_data['identInfo']['idents']);
        $this->pagedata['errIds'] = $print_data['errIds'];
        $this->pagedata['errInfo'] = $print_data['errInfo'];
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['time'] = date("Y-m-d H:i:s");
        $this->pagedata['title'] = '发货单打印';  

        if(app::get('invoice')->is_installed())
        {
            $inOrder   = &app::get('invoice')->model('order');
            $inOrder->update_batch_number($this->pagedata['allItems'], $print_data['identInfo']);
        }

        //改用新打印模板机制 chenping
        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],$now_print_type,$this);
    }

    /**
     * 新版打印发货单
     *
     */
    function toPrintMergeNew() {
        $_err = 'false';
        
        //多品单品标识
        $sku = kernel::single('base_component_request')->get_get('sku');
        $sku = $sku ? $sku : '';

        $now_print_type = 'delivery';
        $now_print_mode = 'new';

        //获取当前待打印的发货单过滤条件
        $filter_condition = $this->getPreparePrintIds();

        $PrintLib = kernel::single('wms_delivery_print');
        $print_data = $PrintLib->getPrintDatas($filter_condition,$now_print_type,$sku,true,$msg);
        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->headerErrorMsgDisply($msg['error_msg']);
        }

        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->message($msg['warn_msg']);
            exit;
        }
        
        $PrintDlyLib = kernel::single('wms_delivery_print_newdelivery');
        $format_data = $PrintDlyLib->format($print_data, $sku,$_err);
        $deliveryDataObj = kernel::single('wms_print_data_newdelivery');
        foreach ($format_data['items'] as $delivery) {
           $logi_name = $delivery['logi_name'];
            $allItems[] = $deliveryDataObj->getElectronOrder($delivery);
        }
        $jsondata = '';
        if ($allItems) {
            $jsondata = $PrintDlyLib->arrayToJson($allItems);

        }
        

        $this->pagedata['err'] = $_err;
        $this->pagedata['title'] = '发货单打印';
        //物流公司

        $this->pagedata['vid'] = implode(',', $print_data['ids']);
        $this->pagedata['logi_name'] = $logi_name;
        //打印数量
        $this->pagedata['count'] = count($allItems);
        //随机数
        $this->pagedata['uniqid'] = uniqid();
        //组织控件打印数据
        $this->pagedata['jsondata'] = $jsondata;
        $this->pagedata['totalPage'] = count($allItems);

        ome_print_controltmpl::instance($now_print_type, $this)->printOTmpl($_GET['otmplId']);
    }

    /**
     * 打印新版本数据结构重组
     *
     * @return void
     * @author chenping<chenping@shopex.cn>
     **/
    private function format_print_delivery($delivery)
    {
        $format_delivery_items = array();
        foreach ($delivery['delivery_items'] as $key => $value) {
            $format_delivery_items[$value['item_id']] = $value;
        }

        $orderObjModel = app::get('ome')->model('order_objects');
        $orderObjList = $orderObjModel->getList('*',array('order_id'=>array_keys($delivery['delivery_order'])));
        $format_order_objects = array();
        foreach ($orderObjList as $key => $value) {
            $format_order_objects[$value['obj_id']] = $value;
        }

        $orderItemModel = app::get('ome')->model('order_items');
        $orderItemList = $orderItemModel->getList('item_id,obj_id,bn,price,pmt_price,sale_price,amount,weight,nums as number',array('order_id'=>array_keys($delivery['delivery_order']),'delete'=>'false'));
        $format_order_items = array();
        foreach ($orderItemList as $key => $value) {
            $format_order_items[$value['item_id']] = $value;
        }

        $omepkgObj = &app::get('omepkg')->model('pkg_goods');
        //是否打印前端商品名称
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $is_print_front = (1 == $deliCfgLib->getValue('wms_delivery_is_printdelivery',$sku)) ? true : false;

        $deliItemDetailModel = app::get('ome')->model('delivery_items_detail');
        $deliItemDetailList = $deliItemDetailModel->getList('*',array('delivery_id'=>$delivery['delivery_id']));
        $data = array();
        foreach ($deliItemDetailList as $key => $value) {
            $order_object = $format_order_objects[$value['order_obj_id']];
            $order_item = $format_delivery_items[$value['delivery_item_id']];
            if (!$order_item) { continue; }

            $order_item = array_merge($order_item,$format_order_items[$value['order_item_id']]);

            if (isset($data[$order_object['obj_type']][$order_object['bn']])) {
                $obj_id_list = $data[$order_object['obj_type']][$order_object['bn']]['obj_id_list'];
                if (!in_array($order_object['obj_id'], $obj_id_list)) {
                    $obj_id_list[] = $order_object['obj_id'];
                    $data[$order_object['obj_type']][$order_object['bn']]['obj_id_list'] = $obj_id_list;
                    $data[$order_object['obj_type']][$order_object['bn']]['quantity'] += $order_object['quantity'];
                    $data[$order_object['obj_type']][$order_object['bn']]['amount'] += $order_object['amount'];
                    $data[$order_object['obj_type']][$order_object['bn']]['sale_price'] += $order_object['sale_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['pmt_price'] += $order_object['pmt_price'];
                }

                if (isset($data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']])) {
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['number'] += $order_item['number'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['pmt_price'] += $order_item['pmt_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['sale_price'] += $order_item['sale_price'];
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']]['amount'] += $order_item['amount'];
                } else {
                    $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']] = $order_item;
                }
            } else {
                $order_object['obj_id_list'][] = $order_object['obj_id'];
                $data[$order_object['obj_type']][$order_object['bn']] = $order_object;
                $data[$order_object['obj_type']][$order_object['bn']]['order_items'][$value['bn']] = $order_item;

                if($order_object['obj_type']=='pkg'){
                    $pkg = $omepkgObj->getlist('name',array('pkg_bn'=>$order_object['bn']),0,1);
                    if (!$is_print_front) {
                        $data[$order_object['obj_type']][$order_object['bn']]['product_name'] = $pkg[0]['name'];
                        $data[$order_object['obj_type']][$order_object['bn']]['name'] = $pkg[0]['name'];
                    }else{
                        $data[$order_object['obj_type']][$order_object['bn']]['product_name'] = $order_object['name'];
                    }
                }
            }
        }
        $delivery['delivery_items'] = $data;

        return $delivery;
    }

    /**
     * 打印快递单
     *
     * 修改 加了一个补打快递单的开关 wujian@shopex.cn 2012年3月14日
     */
    function toPrintShip($afterPrint=true) {
        $dlyObj = &app::get('wms')->model('delivery');
        $dlyBillObj = &app::get('wms')->model('delivery_bill');
        $orderObj = &app::get('ome')->model('orders');
        $dly_corpObj = &app::get('ome')->model('dly_corp');
        $pObj = &app::get('ome')->model('products');
        $order_sellagentObj = &app::get('ome')->model('order_selling_agent');
        $omeDeliveryObj = app::get('ome')->model('delivery');
        $_err = 'false';

        $ids = $this->processDeliveryId();

        //打印排序
        if($afterPrint){
            $ids = $dlyObj->printOrderByByIds($ids);
        }

        //批次号 by sy
        if($afterPrint){
            $idents = $this->_getPrintQueue($ids);
        }

        $idsAll = $this->parsePrintIds($ids);

        $allItems = array();

        $ids = $idsAll['ids'];

        # 单品、多品标识
        $sku = kernel::single('base_component_request')->get_get('sku');
        $sku = $sku ? $sku : '';

        # 给发货单加上相应配置项
        if($ids) {
            $this->updateDeliCfg($ids,$sku);
        }
        //备注显示方式
        $markShowMethod = &app::get('ome')->getConf('ome.order.mark');
        $express_company_no = '';

        if ($ids){
            $name = implode(',', $ids);
        }

        if ($ids) {
            //sort($ids);

            $idds = $ids;
            unset($ids);
            $rows = array();
            foreach ($idds as $id) {
                $outer_id = $dlyObj->getOuterIdById($id);
                $data = $omeDeliveryObj->dump($outer_id, '*', array('delivery_order' => array('*'), 'delivery_items' => array('*')));
                $data['wms_delivery_id'] = $id;
                if ($data['parent_id'] != 0) {
                    $_err = 'true';
                    continue;
                }
                $num = 0;
                $err = '';
                if ($data) {
                    //批次号
                    $allItems[$data['delivery_id']] = $data;


                    foreach ($data['delivery_items'] as $k => $i) {
                        $num += $i['number'];
                        $p = $pObj->dump($i['product_id']);
                        $data['delivery_items'][$k]['product_name'] = $p['name'];
                        $data['delivery_items'][$k]['addon'] = $p['spec_info'];
                        $data['delivery_items'][$k]['bn_dbvalue'] = $data['delivery_items'][$k]['bn'];
                    }
                    $o_bn = array();
                    $mark_text = array();
                    $custom_mark = array();
                    $total_amount = array();
                    foreach ($data['delivery_order'] as $v) {
                        $order = $orderObj->dump($v['order_id'], 'order_bn,mark_text,custom_mark,total_amount');

                        if ($order['mark_text']) {
                            $mark = unserialize($order['mark_text']);
                            if (is_array($mark) || !empty($mark)){
                                if($markShowMethod == 'all'){
                                    foreach ($mark as $im) {
                                        $mark_text[] = $im['op_content'];
                                    }
                                }else{
                                    $mark = array_pop($mark);

                                    $mark_text[] = $mark['op_content'];
                                }
                            }
                        }

                        if ($order['custom_mark']) {
                            $custommark = unserialize($order['custom_mark']);
                            if (is_array($custommark) || !empty($custommark)){
                                if($markShowMethod == 'all'){
                                    foreach ($custommark as $im) {
                                        $custom_mark[] = $im['op_content'];
                                    }
                                }else{
                                    $mark = array_pop($custommark);
                                    $custom_mark[] = $mark['op_content'];
                                }
                            }
                        }
                        $o_bn[] = $order['order_bn'];
                        $total_amount[] = $order['total_amount'];
                    }

                    $shop = $omeDeliveryObj->getShopInfo($data['shop_id']);
                    //发货人信息
                    $shipper_detail = $orderObj->dump(array('order_id' => $v['order_id']), 'consigner_name,consigner_area,consigner_addr,consigner_zip,consigner_email,consigner_mobile,consigner_tel');
                    $row = $dly_corpObj->dump($data['logi_id'], 'prt_tmpl_id,type');
                    $data['prt_tmpl_id'] = $row['prt_tmpl_id'];
                    $data['shopinfo'] = $shop;
                    $data['order_memo'] = implode(',', $mark_text);
                    $data['order_custom'] = implode(',', $custom_mark);
                    $data['order_count'] = $num;
                    $data['order_bn'] = implode(',', $o_bn);
                    $data['order_total_amount'] = implode(',', $total_amount);
                    //去除多余的三级区域
                    $reg = preg_quote(trim($data['consignee']['province']));
                    if (!empty($data['consignee']['city'])) {
                        $reg .= '.*?' . preg_quote(trim($data['consignee']['city']));
                    }
                    if (!empty($data['consignee']['district'])) {
                        $reg .= '.*?' . preg_quote(trim($data['consignee']['district']));
                    }

                    $data['consignee']['addr'] = preg_replace('/' . $reg . '/is', '', $data['consignee']['addr']);

                    //快递公式
                    if (!$express_company_no) {
                        $express_company_no = strtoupper($row['type']);
                        $logi_name = $data['logi_name'];
                    }
                    $rows['delivery'][] = $data;

                    //快递单关联程序,取wms信息
                    $itm['delivery_id'] = $id;
                    $deliveryInfo = $dlyObj->dump($id);
                    $itm['delivery_bn'] = $deliveryInfo['delivery_bn'];
                    $itm['consignee']['name'] = $deliveryInfo['consignee']['name'];
                    $idd[] = $itm;

                    $dlyBillInfo = $dlyBillObj->dump(array('delivery_id'=>$id,'type'=>1),'logi_no');
                    $logid[$id] = $dlyBillInfo['logi_no'];
                    $ids[] = $id;
                } else {
                    $_err = 'true';
                }
            }

        }

        $rows['dly_tmpl_id'] = $data['prt_tmpl_id'];
        $rows['order_number'] = count($ids);
        $rows['name'] = $name;
        //物流公司标识
        $this->pagedata['print_logi_id'] = $data['logi_id'];
        //商品名称和规格取前台,是合并发货单,取第一个订单的货品名称
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $is_print_front = (1 == $deliCfgLib->getValue('wms_delivery_is_printship')) ? true : false;

        if ($ids && $is_print_front) {
            $arrPrintProductName = $omeDeliveryObj->getPrintProductName($ids);
            if (!empty($arrPrintProductName)) {
                foreach ($rows['delivery'] as $k => $row) {
                    foreach ($row['delivery_items'] as $k2 => $v) {
                        $bncode = md5($row['shop_id'].$v['bn']);
                        $row['delivery_items'][$k2]['product_name'] = $arrPrintProductName[$bncode]['name'];
                        //$row['delivery_items'][$k2]['name'] = $arrPrintProductName[$v['bn']]['name'];
                        $row['delivery_items'][$k2]['addon'] = $arrPrintProductName[$bncode]['addon'];
                        $row['delivery_items'][$k2]['spec_info'] = $arrPrintProductName[$bncode]['addon'];
                        $row['delivery_items'][$k2]['store_position'] = $arrPrintProductName[$bncode]['store_position'];
                    }
                    $rows['delivery'][$k] = $row;
                }
            }
        } elseif($ids) {
            // 货位的获取
            $tmp_product_ids = array();
            foreach ($rows['delivery'] as $k => $row) {
                foreach ($row['delivery_items'] as $k2 => $v) {
                    $tmp_product_ids[] = $v['product_id'];

                    $bpro_key = $row['branch_id'].$v['product_id'];
                    $rows['delivery'][$k]['delivery_items'][$k2]['store_position'] = &$bpro[$bpro_key];
                }
            }
            // 货品货位有关系
            $bppModel = app::get('ome')->model('branch_product_pos');
            $bppList = $bppModel->getList('product_id,pos_id,branch_id',array('product_id'=>$tmp_product_ids));

            // 如果货位存在
            if ($bppList) {
                // 货位信息
                $tmp_pos_ids = array();
                foreach ($bppList as $key=>$value) {
                    $tmp_pos_ids[] = $value['pos_id'];
                }

                $posModel = app::get('ome')->model('branch_pos');
                $posList = $posModel->getList('pos_id,branch_id,store_position',array('pos_id'=>$tmp_pos_ids));

                $newPosList = array();
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
        }

        $delivery_cfg = app::get('wms')->getConf('wms.delivery.status.cfg');
        #检测是否开启打印捆绑商品按钮
        $delivey_order = array();
        if($delivery_cfg['set']['print_pkg_goods']){
            foreach($rows['delivery'] as $key=>&$delivery){
                #获取发货单上有捆绑商品item_id
                $pkg_item_id = $orderObj->getPkgItemId($delivery['delivery_id']);
                foreach( $pkg_item_id as $item_id){
                    if(isset($delivery['delivery_items'][$item_id])){
                        #删除这批打印数据中包含捆绑商品的货品信息
                        unset($delivery['delivery_items'][$item_id]);
                    }
                }
            }
        }
        if ($rows['delivery'])
            foreach ($rows['delivery'] as $val) {
                //获取快递单打印模板的servivce定义
                $data = array();
                foreach (kernel::servicelist('wms.service.template') as $object => $instance) {
                    if (method_exists($instance, 'getElementContent')) {
                        $tmp = $instance->getElementContent($val);
                    }
                    $data = array_merge($data, $tmp);
                }
                $mydata[] = $data;
            }

        $printTmpl = &app::get('wms')->model('print_tmpl');

        $errDly = $dlyObj->getList('delivery_id,delivery_bn', array('delivery_id' => $idsAll['errIds']));
        foreach($errDly as $val){
            $errBns[$val['delivery_id']] = $val['delivery_bn'];
        }
        $this->pagedata['errBns'] = $errBns;

        $this->pagedata['data'] = addslashes($omeDeliveryObj->array2xml2($mydata, 'data'));
        $this->pagedata['order_number'] = $rows['order_number'];
        $this->pagedata['prt_tmpl'] = $printTmpl->dump($rows['dly_tmpl_id'], 'prt_tmpl_width,prt_tmpl_offsety,prt_tmpl_offsetx,prt_tmpl_height,prt_tmpl_data,file_id');
        /* 修改的地方 */
        if ($this->pagedata['prt_tmpl']['file_id']) {
            $this->pagedata['tmpl_bg'] = 'index.php?app=wms&ctl=admin_delivery_print&act=showPicture&p[0]=' . $this->pagedata['prt_tmpl']['file_id'];
        }
        $this->pagedata['err'] = $_err;
        $this->pagedata['vid'] = $rows['name'];

        //批次号
        $this->pagedata['allItems'] = $allItems;
        $this->pagedata['idents'] = $idents['items'];
        $this->pagedata['ident'] = join(',', $idents['idents']);
        $this->pagedata['errIds'] = $idsAll['errIds'];
        $this->pagedata['errInfo'] = $idsAll['errInfo'];
        $items = array();
        foreach ($rows['delivery'] as $row) {
            $items[$row['delivery_id']] = $row;
        }
        $this->pagedata['items'] = $items;
        $this->pagedata['sku'] = $sku;//单品 多品标识
        $this->pagedata['dpi'] = 96;
        $this->pagedata['count'] = sizeof($ids);
        $this->pagedata['ids'] = $ids;
        $this->pagedata['idd'] = $idd;
        $this->pagedata['logid'] = $logid;
        $this->pagedata['logi_name'] = $logi_name;

        $this->pagedata['express_company_no'] = $express_company_no;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['title'] = '快递单打印';

        if(!$afterPrint){
            $this->pagedata['b_id'] = $_REQUEST['b_id'];

            $dlyBillObj = &app::get('wms')->model('delivery_bill');
            $billFilter = array(
                'b_id'=>$_REQUEST['b_id'],
            );
            $this->pagedata['bill_logi_no'] = $dlyBillObj->getList('b_id,logi_no',$billFilter);
            $this->pagedata['delibery_bill_flag'] = 'delibery_bill_flag';
        }
        
        /*
         * 更新发票订单批次号
         * ExBOY
         * 2014.04.09
         */
        if(app::get('invoice')->is_installed())
        {
            $inOrder   = &app::get('invoice')->model('order');
            $inOrder->update_batch_number($allItems, $idents);
        }

        /**
        *新增新版本打印
        */
        //获取打印版本配置信息
        $logicfg = kernel::single('ome_print_logicfg')->getLogiCfg();

        if($logicfg[$express_company_no]){
            $logiVersionFlag = 1;
            $this->pagedata['logicfg'] = $logicfg[$express_company_no];
            $print_logi_version = app::get('ome')->getConf('print_logi_version_'.$this->pagedata['print_logi_id']);

            $this->pagedata['print_logi_version'] = intval($print_logi_version);
        }
        $this->pagedata['logiVersionFlag'] = $logiVersionFlag;
        $params = array('order_bn'=>$o_bn);
        wms_print_tmpl_express::instance($express_company_no,$this)->setParams($params)->getTmpl();

    }

    /**
     *
     * 补打物流单
     */
     public function addPrintShip(){
        if(count($_REQUEST['b_id']) > 0){
            $this->addPrintShipNoData();
            exit;
        }
        $num = $_REQUEST['num'];
        $str = $_REQUEST['delivery_id'];

        //写入日志
        $opObj = &app::get('ome')->model('operation_log');
        $opObj->write_log('delivery_bill_print@wms', $_REQUEST['delivery_id'], '补打快递单('.$num.')份');
        
        //增加子数据
        //更新主物流单delivery中logi_number,delivery_logi_number信息
        $dlyObj     = &app::get('wms')->model('delivery');
        $dlyBillObj = app::get('wms')->model('delivery_bill');

        //京东面单补打
        $channel_info = kernel::single('wms_delivery_print_ship')->getWaybillType($str);
        $logi_no_fill = false;
        $logi_no_total = 1;
        if (in_array($channel_info['channel_type'],array('360buy'))) {
            $delivery_data = $dlyBillObj->dump(array('delivery_id' => $str,'type'=>'1'),'logi_no');
            $logi_no_fill = true;
            $delivery_num = $dlyBillObj->count(array('delivery_id' => $str,'type'=>'2'));
            if ($delivery_num>0) {
                
                $dlyBillObj->db->exec("DELETE FROM sdb_wms_delivery_bill WHERE delivery_id=".$str." AND `type`='2'");
            }
            $num+=$delivery_num;

        }
        //京东
        $logi_no_num = 1;
        $logi_no_total+=$num;
        for($i=0;$i<$num;$i++){
            $data = array('delivery_id' => $str,'create_time'=>time(),'type'=>2);
            if ($logi_no_fill) {
                $logi_no_num++;
                $data['logi_no'] = $delivery_data['logi_no'].'-'.$logi_no_num.'-'.$logi_no_total.'-';
            }
            $b_id[] = $dlyBillObj->insert($data);
        }

        $delivery = app::get('wms')->model('delivery');
        $logi_number = $dlyBillObj->count(array('delivery_id' => $str));
        $sql = "update sdb_wms_delivery set logi_number=".$logi_number." where delivery_id=".$str;
        $delivery->db->exec($sql);


        $_REQUEST['b_id'] = $b_id;
        $_REQUEST['delivery_id'] = array();
        for($i=0;$i<$num;$i++){
            $_REQUEST['delivery_id'][]=$str;
        }

        //屏蔽toPrintShip 打印排序 和 批次号
        if (app::get('logisticsmanager')->is_installed()) {
            $this->toPrintExpre(false);
        } else {
            $this->toPrintShip(false);
        }
     }

    /**
     *
     * 补打物流单(无需更新数据)
     */
     public function addPrintShipNoData(){
         if(is_array($_REQUEST['b_id'])){
            if(count($_REQUEST['b_id']) == 1){
                $tmp_str = $_REQUEST['b_id'][0];
                unset($_REQUEST['b_id']);
                $_REQUEST['b_id'] = $tmp_str;
            }else{
                $tmp_arr = $_REQUEST['b_id'];
                unset($_REQUEST['b_id']);
                foreach($tmp_arr as $k =>$val){
                    $_REQUEST['b_id'] .= $val.",";
                }
                $_REQUEST['b_id'] = substr($_REQUEST['b_id'],0,-1);
            }
         }
        $str = $_REQUEST['delivery_id'];
        $b_id = explode(',',$_REQUEST['b_id']);

        $dlyObj = &app::get('wms')->model('delivery');
        $delivery_bill = app::get('wms')->model('delivery_bill');

        $flag = $dlyObj->dump(array('delivery_id' => $str));
        $filter = array(
            'b_id' => $b_id,
            'delivery_id'=>$str
        );
        $datanum = $delivery_bill->count($filter);
        if(count($b_id)>$datanum||!$flag){
            die('错误');
        }
        $num = $datanum;
        $_REQUEST['b_id'] = $b_id;
        $_REQUEST['delivery_id'] = array();
        for($i=0;$i<$num;$i++){
            $_REQUEST['delivery_id'][]=$str;
        }

        //屏蔽toPrintShip 打印排序 和 批次号
        //var_dump($_REQUEST);
        if (app::get('logisticsmanager')->is_installed()) {
            $this->toPrintExpre(false);
        } else {
            $this->toPrintShip(false);
        }
     }

    /*
     * 关联物流单号
     * wujian@shopex.cn
     * 2012年3月19日
     */
     function insertAfterExpress(){
         $arr = $_POST;
        $arrc = count($arr["id"]);
        $uniquec = count(array_unique($arr["id"]));
        $opObj = &app::get('ome')->model('operation_log');


        if($arrc>$uniquec){
            echo '物流号中有重复的值';
        }else{
             //保存对应物流公司所用打印版本

            $flag = true;
            $delivery_bill = app::get('wms')->model('delivery_bill');
            $delivery = app::get('ome')->model('delivery');
            foreach($arr["id"] as $key=>$value){
                $filter['logi_no'] = $value;
                $deliveryCount = $delivery->count($filter);
                $deliveryBillCount = $delivery_bill->count($filter);
                if ($deliveryCount > 0 || $deliveryBillCount > 0){
                    echo '已有此物流号:'.$value;
                    $flag = false;
                    die;
                }
            }
            if($flag){
                app::get('ome')->setConf('print_logi_version_'.$arr['print_logi_id'], intval($arr['logi_version']));

                foreach($arr["id"] as $key=>$value){
                    $delivery_bill->update(array("logi_no"=>$value),array('b_id'=>$key));
                    $dlybillinfo = $delivery_bill->dump(array('b_id'=>$key));
                    $logstr = '录入快递单号:'.$value;
                    $opObj->write_log('delivery_bill_add@ome', $dlybillinfo['delivery_id'], $logstr);
                }
                echo 'SUCC';
            }
        }
     }

    /**
     * 批量更改物流
     *
     * @param void
     * @return void
     */
    function toChangeDly() {
        //如果是全选的情况下，该功能代码还未兼容
        $ids = $_POST['delivery_id'];
        if (empty($ids)) {
            die('没有选择任何可操作的发货单。');
        }

        $dlyCrop = app::get('ome')->model('dly_corp')->getList('corp_id, name, type, is_cod, weight', array('disabled' => 'false'), 0, -1, 'weight DESC');

        $deliveryObj = app::get('wms')->model('delivery');
        $branchObj = app::get('ome')->model('branch');

        $deliverys = $deliveryObj->getList('delivery_id, branch_id, delivery_bn', array('delivery_id' => $ids));
        $branchDatas = array();
        foreach ($deliverys as $delivery) {
            $branchDatas[$delivery['branch_id']]['delivery'][$delivery['delivery_id']] = $delivery['delivery_bn'];
            $branchDatas[$delivery['branch_id']]['count']++;
        }
        foreach ($branchDatas as $key => $branchData) {
            $branchDatas[$key]['branch'] = $branchObj->dump($key, 'branch_id,name');
            $branchDatas[$key]['corp'] = $branchObj->get_corp($key);
        }

        $this->pagedata['ids'] = join(',', $ids);
        $this->pagedata['branchDatas'] = $branchDatas;
        $this->pagedata['dlyCorp'] = $dlyCrop;
        $this->pagedata['orderCnt'] = count($ids);
        $this->display('admin/delivery/change_dly.html');
    }

    /**
     * 更换物流公司
     *
     */
    function doChangeDly() {
        $this->begin();
        $branchDatas = $_POST['branchData'];
        $corpObj = app::get('ome')->model('dly_corp');
        $deliveryObj = app::get('wms')->model('delivery');
        $deliveryBillObj = app::get('wms')->model('delivery_bill');
        $opObj = app::get('ome')->model('operation_log');
        $waybillObj = kernel::single('logisticsmanager_service_waybill');
        $wmsCommonLib = kernel::single('wms_common');
        $rows = $corpObj->getList();
        foreach($rows as $val) {
            $corpList[$val['corp_id']] = $val;
        }
        foreach ($branchDatas as $branch) {
            $branch_id = $branch['branch'];
            $newCorp = $branch['newCorp'];
            $deliveryIds = $branch['delivery'];
            if (!$branch_id || !$newCorp || !$deliveryIds) {
                $this->end(false, '请确定新的物流！');
            } else {
                $corp = $corpObj->dump($newCorp);

                $data['logi_id'] = $newCorp;
                $data['logi_name'] = $corp['name'];
                $data['logi_no'] = null;
                $data['status'] = 0;
                $data['process_status'] = 0;

                //未校验，未发货的有效发货单才可以修改
                $filter['delivery_id'] = $deliveryIds;
                $filter['branch_id'] = $branch_id;
                $filter['status'] = 0;
                $filter['process_status'] = array(0,1);

                // 获取WMS
                $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id);

                $logs = array();
                if(is_array($deliveryIds)){
                    foreach ($deliveryIds as $did) {
                        $dly_data = $deliveryObj->dump($did);
                        $bill_data = $deliveryBillObj->dump(array('delivery_id'=>$did,'type'=>'1'));
                        //回收电子面单
                        if ($bill_data['logi_no'] && $corpList[$dly_data['logi_id']]['tmpl_type'] == 'electron') {
                            //直连物流：EMS、申通、京东回收
                            
                            $waybillObj->recycle_waybill($bill_data['logi_no'],$corpList[$dly_data['logi_id']]['channel_id'],$did);
                        }
                        //重新计算预估物流费
                        $arrArea = explode(':', $dly_data['consignee']['area']);
                        $area_id = $arrArea[2];
                        $price = $wmsCommonLib->getDeliveryFreight($area_id,$newCorp,$dly_data['net_weight']);

                        $logs[] = array('did' => $did, 'cnt' =>  '修改物流:' .$dly_data['logi_name']. ' => ' .$corp['name'],'outer_delivery_bn' => $dly_data['outer_delivery_bn'],'delivery_cost_expect' => $price);
                    }
                }
                if ($deliveryObj->update($data, $filter)) {
                   //
                  
                    foreach($logs as $log) {
                        //更新自有仓库发货单预估物流费
                        $deliveryObj->update(array('delivery_cost_expect' => $log['delivery_cost_expect']),array('delivery_id' => $log['did']));
                        $deliveryBillObj->update(array('logi_no'=>null,'delivery_cost_expect' => $log['delivery_cost_expect']),array('delivery_id' => $log['did'], 'type' => 1));

                        // 返回物流公司
                        $tmp_data = array(
                            'delivery_bn' => $log['outer_delivery_bn'],
                            'logi_id' => $newCorp,
                            'logi_name' => $corp['name'],
                            'logi_no' => null,
                            'status' => 0,
                            'process_status' => 0,
                            'delivery_cost_expect' => $log['delivery_cost_expect'],
                            'action' => 'updateDetail',
                        );
                        $res = kernel::single('wms_event_trigger_delivery')->doUpdate($wms_id, $tmp_data, true);

                        $opObj->write_log('delivery_logi@wms', $log['did'], $log['cnt']);
                    }
                }
            }
        }
        $this->end(true, '更换物流成功！');
    }

    /**
     * 更新发货单的打印状态
     *
     * @param string $type
     * @param int $dly_id
     *
     */
    function setPrintStatus() {
        $current_otmpl_name = $_POST['current_otmpl_name'] ? $_POST['current_otmpl_name'] : '默认';
        $type = $_POST['type'];
        $str_id = $_POST['str'];
        $dlyObj = &app::get('wms')->model('delivery');
        $tmp_id = array_filter(explode(',', $str_id));

        if (!$this->_checkPrintQueue($tmp_id, $msg)) {
            echo $msg; exit;
        }

        $dlys = $dlyObj->getList('*', array('delivery_id' => $tmp_id), 0, -1);
        $dly = array();
        $arr_s = array(1,2);

        foreach ($dlys as $k => $delivery) {
            if (in_array($delivery['status'], $arr_s)){
                continue;
            }

            //if ($delivery['status'] == 'ready')  $dly[$k]['status'] = 'progress';
            $dly[$k]['delivery_id'] = $delivery['delivery_id'];
            $send_flag = false;
            switch ($type) {
                case 'express':
                    if (($delivery['print_status'] & 4) != 4) {
                        $send_flag = true;
                    }
                    $dly[$k]['print_status'] = $delivery['print_status'] | 4;
                    $dly[$k]['_log_'] = 'delivery_expre@wms';
                    $dly[$k]['_memo_'] = '快递单打印';
                    //如果是当当物流订单，将订单号更新为物流单号
                    $logi_id = $delivery['logi_id'];
                    $dly_corpObj = &app::get('ome')->model('dly_corp');
                    $orderObj = &app::get('ome')->model('orders');
                    $dly_corp = $dly_corpObj->dump($logi_id,'type');
                    if ($dly_corp['type'] == 'DANGDANG') {
                        $orderIds = $dlyObj->getOrderIdByDeliveryId($delivery['delivery_id']);
                        if ($orderIds)
                        $ids = implode(',', $orderIds);
                        if ($orderIds)
                        foreach ($orderIds as $oid)
                        {
                            $order = $orderObj->dump($oid,'order_bn');
                            $order_bn= $order['order_bn'];
                        }
                        $dly[$k]['logi_no'] = $order_bn;
                    }
                    break;
                case 'stock':
                    if (($delivery['print_status'] & 1) != 1) {
                        $send_flag = true;
                    }
                    $dly[$k]['print_status'] = $delivery['print_status'] | 1;
                    $dly[$k]['_log_'] = 'delivery_stock@wms';
                    $dly[$k]['_memo_'] = "备货单打印（打印模板： $current_otmpl_name ）";
                    break;
                case 'delivery':
                    if (($delivery['print_status'] & 2) != 2) {
                        $send_flag = true;
                    }
                    $dly[$k]['print_status'] = $delivery['print_status'] | 2;
                    $dly[$k]['_log_'] = 'delivery_deliv@wms';
                    $dly[$k]['_memo_'] = "发货单商品信息打印（打印模板： $current_otmpl_name ）";
                    break;
                case 'stock_dly':
                    if ((($delivery['print_status'] & 1) != 1) && (($delivery['print_status'] & 2) != 2)) {
                        $send_flag = true;
                    }
                    $dly[$k]['print_status'] = $delivery['print_status'] | 1;
                    $dly[$k]['print_status'] = $dly[$k]['print_status'] | 2;
                    $dly[$k]['_isMerge_'] = true;
                    $dly[$k]['_log_'][0] = 'delivery_stock@wms';
                    $dly[$k]['_log_'][1] = 'delivery_deliv@wms';
                    $dly[$k]['_memo_'][0] = "备货单打印（打印模板： $current_otmpl_name ）";
                    $dly[$k]['_memo_'][1] = "发货单商品信息打印（打印模板： $current_otmpl_name ）";
                    break;
            }
        }
        $opObj = &app::get('ome')->model('operation_log');
        foreach ($dly as $k => $v) {
            $_dly = $v;
            $dlyObj->save($_dly);

            $delivery = $dlyObj->dump($v['delivery_id'], 'outer_delivery_bn,print_status,process_status,branch_id');

            $print_status = true;
            //根据打印单据配置及当前状态判断发货单打印状态
            $deliCfgLib = kernel::single('wms_delivery_cfg');
            $checkStock = $deliCfgLib->analyse_btn_status('stock');
            if($checkStock == true && ($delivery['print_status'] & 1) != 1){
                $print_status = false;
            }

            $checkDelie = $deliCfgLib->analyse_btn_status('delie');
            if($checkDelie == true && ($delivery['print_status'] & 2) != 2){
                $print_status = false;
            }

            $checkMerge = $deliCfgLib->analyse_btn_status('merge');
            if($checkMerge == true && ((($delivery['print_status'] & 1) != 1) || (($delivery['print_status'] & 2) != 2))){
                $print_status = false;
            }

            if(($delivery['print_status'] & 4) != 4){
                $print_status = false;
            }

            if($print_status){
                $tmp_status = $delivery['process_status'] | 1;
                $data = array('process_status'=>$tmp_status,'delivery_id'=>$v['delivery_id']);
                $dlyObj->save($data);

                //同步打印状态到oms
                $wms_id = kernel::single('ome_branch')->getWmsIdById($delivery['branch_id']);
                $data = array(
                    'delivery_bn' => $delivery['outer_delivery_bn'],
                    'stock_status' => ($delivery['print_status'] & 1) == 1 ? 'true' : 'false',
                    'deliv_status' => ($delivery['print_status'] & 2) == 2 ? 'true' : 'false',
                    'expre_status' => ($delivery['print_status'] & 4) == 4 ? 'true' : 'false',
                );
                $res = kernel::single('wms_event_trigger_delivery')->doPrint($wms_id, $data, true);
            }

            if ($v['_isMerge_']) {//联合打印
                foreach ($v['_log_'] as $key => $val) {
                    $opObj->write_log($val, $v['delivery_id'], $v['_memo_'][$key]);
                }
            } else {
                $opObj->write_log($v['_log_'], $v['delivery_id'], $v['_memo_']);
            }
        }
        ob_clean();
        echo 'true';
    }

    /**
     * 打印备货单
     *
     */
    function toPrintStock() {
        $_err = 'false';
        # 发货配置类型
        $sku = kernel::single('base_component_request')->get_get('sku');
        $sku = $sku ? $sku : '';

        $now_print_type = 'stock';

        //获取当前待打印的发货单过滤条件
        $filter_condition = $this->getPreparePrintIds();

        $PrintLib = kernel::single('wms_delivery_print');
        $print_data = $PrintLib->getPrintDatas($filter_condition,$now_print_type,$sku,true,$msg);
        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->headerErrorMsgDisply($msg['error_msg']);
        }

        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->message($msg['warn_msg']);
            exit;
        }
        
        $PrintStockLib = kernel::single('wms_delivery_print_stock');
        $format_data = $PrintStockLib->format($print_data, $sku,$_err);
        $this->pagedata = $format_data;

        $this->pagedata['errBns'] = $print_data['errBns'];
        $this->pagedata['err'] = $_err;
        $this->pagedata['allItems'] = $print_data['deliverys'];
        $this->pagedata['idents'] = $print_data['identInfo']['items'];
        $this->pagedata['ident'] = join(',', $print_data['identInfo']['idents']);
        $this->pagedata['errIds'] = $print_data['errIds'];
        $this->pagedata['errInfo'] = $print_data['errInfo'];
        $this->pagedata['time'] = date("Y-m-d H:i:s");
        $this->pagedata['sku'] = $sku;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['title'] = '备货单打印';

        if(app::get('invoice')->is_installed())
        {
            $inOrder   = &app::get('invoice')->model('order');
            $inOrder->update_batch_number($print_data['deliverys'], $print_data['identInfo']);
        }

        kernel::single('ome_print_otmpl')->printOTmpl($_GET['otmplId'],$now_print_type,$this);
    }

    /**
     * 备货单新版
     */
    function toPrintStockNew() {
        $_err = 'false';
        # 发货配置类型
        $sku = kernel::single('base_component_request')->get_get('sku');
        $sku = $sku ? $sku : '';

        $now_print_type = 'stock';

        //获取当前待打印的发货单过滤条件
        $filter_condition = $this->getPreparePrintIds();

        $PrintLib = kernel::single('wms_delivery_print');
        $print_data = $PrintLib->getPrintDatas($filter_condition,$now_print_type,$sku,true,$msg);
        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->headerErrorMsgDisply($msg['error_msg']);
        }

        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->message($msg['warn_msg']);
            exit;
        }

        $PrintStockLib = kernel::single('wms_delivery_print_stock');
        $format_data = $PrintStockLib->format($print_data, $sku,$_err);
        $this->pagedata = $format_data;

        //备货打印json数据
         $jsondata = $PrintStockLib->arrayToJson($format_data['rows'], $print_data['identInfo']['idents'], $this->pagedata);

        $this->pagedata['errBns'] = $print_data['errBns'];
        $this->pagedata['err'] = $_err;
        $this->pagedata['allItems'] = $print_data['deliverys'];
        $this->pagedata['idents'] = $print_data['identInfo']['items'];
        $this->pagedata['ident'] = join(',', $print_data['identInfo']['idents']);
        $this->pagedata['errIds'] = $print_data['errIds'];
        $this->pagedata['errInfo'] = $print_data['errInfo'];
        $this->pagedata['time'] = date("Y-m-d H:i:s");
        $this->pagedata['sku'] = $sku;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['title'] = '备货单打印';
        $this->pagedata['jsondata'] = $jsondata;
        $this->pagedata['count'] = sizeof($print_data['ids']);
        $this->pagedata['totalPage'] = count($printData);

        if(app::get('invoice')->is_installed())
        {
            $inOrder   = &app::get('invoice')->model('order');
            $inOrder->update_batch_number($print_data['deliverys'], $print_data['identInfo']);
        }

        ome_print_controltmpl::instance($now_print_type, $this)->printOTmpl($_GET['otmplId']);
    }

    /**
     * 录入快递单号
     *
     */
    function insertExpress() {
        if (empty($_POST['id'])) {
            exit("请录入快递单号");
        }
        $ids = $_POST['id'];
        $dlyObj = &app::get('wms')->model('delivery');
        $deliveryBillObj = &app::get('wms')->model('delivery_bill');
        $errmsg = '';
        $idds = array();

        if ($ids){
            foreach ($ids as $k => $i) {
                $i = $i ? trim($i) : null;
                $delivery = $dlyObj->dump($k);
                $bn = $delivery['delivery_bn'];
                $arr_s = array(1,2,3);
                if (in_array($delivery['status'], $arr_s)) {
                    $errmsg .= "发货单" . $bn . "相关信息不能修改\n";
                    unset($ids[$k]);
                }

                if(empty($i)){
                    exit("物流单号不能为空，发货单为" . $bn);
                }

                if ($dlyObj->existExpressNo($i, $k)) {
                    exit("物流单号已存在，发货单为" . $bn);
                }
                $dlyBillInfo = $deliveryBillObj->dump(array('delivery_id'=>$k,'type'=>1),'b_id');
                $idds[$k]['b_id'] = $dlyBillInfo['b_id'];
                $idds[$k]['branch_id'] = $delivery['branch_id'];
                $idds[$k]['outer_delivery_bn'] = $delivery['outer_delivery_bn'];
            }
        }

        $opObj = &app::get('ome')->model('operation_log');
        if ($ids){
            foreach ($ids as $key => $item) {
                $dlyLog = array();
                $item = $item ? trim($item) : null;
                $data['b_id'] = $idds[$key]['b_id'];
                $data['logi_no'] = $item;
                $data['type'] = 1;

                if ($item && $key) {
                    $deliveryBillObj->save($data);

                    //信息变更更新到oms
                    $wms_id = kernel::single('ome_branch')->getWmsIdById($idds[$key]['branch_id']);
                    $tmp_data = array(
                        'delivery_bn' => $idds[$key]['outer_delivery_bn'],
                        'logi_no' => $data['logi_no'],
                        'action' => 'addLogiNo',
                    );
                    $res = kernel::single('wms_event_trigger_delivery')->doUpdate($wms_id, $tmp_data, true);
                }

                $opObj->write_log('delivery_logi_no@wms', $key, '录入快递单号:'.$item);
            }
        }

        app::get('ome')->setConf('print_logi_version_'.$_POST['print_logi_id'], intval($_POST['logi_version']));
        if($errmsg && !empty($errmsg)){
            $errmsg .= "\n请将以上报错的打印单据作废，其它单据保存成功";
            exit($errmsg);
        }
        echo "SUCC";
    }

    /**
     * 保存发货单详情信息
     *
     */
    function doDetail() {
        $status = $_POST['status'] ? $_POST['status'] : 0;
        $ctl = $_POST['ctl'];
        $this->begin();
        if (empty($_POST['dly'])) {
            $this->end(false, '保存失败');
        }
        if ($_POST['dly']['logi_id'] == '' || empty($_POST['dly']['logi_id'])) {
            $this->end(false, '请选择物流公司');
        }

        //获取wms发货通知单原始信息
        $deliveryObj = &app::get('wms')->model('delivery');
        $delivery = $deliveryObj->dump($_POST['dly']['delivery_id']);
        $delivery['logi_no'] = kernel::single('wms_delivery_bill')->getPrimaryLogiNoById($_POST['dly']['delivery_id']);
        
        $arr_s      = array('cancel', 'back','stop','return_back');
        if (in_array($delivery['status'], $arr_s) && $_POST['dly']['logi_no'] ){
            $this->end(false,'发货单已撤销不能修改');
        }

        //取oms的发货通知单编号和ID
        $omeDlyObj = &app::get('ome')->model('delivery');
        $omeDlyInfo = $omeDlyObj->dump(array('delivery_bn'=>$delivery['outer_delivery_bn']),'delivery_id,delivery_bn');

        //物流公司改变 物流单号不改变     将物流单号置空 重新计算物流费用
        $doObj = &app::get('ome')->model('delivery_order');
        $oObj = &app::get('ome')->model('orders');
        $Objdly_corp = &app::get('ome')->model('dly_corp');
        $wmsCommonLib = kernel::single('wms_common');

        $corp = $Objdly_corp->dump($_POST['dly']['logi_id']);

        $order_ids = $doObj->getlist('order_id', array('delivery_id' => $omeDlyInfo['delivery_id']), 0, -1);

        if(count($order_ids) == 1){
            $orders = $oObj->dump(array('order_id' => $order_ids[0]['order_id']), 'order_bn,shop_type');
        }

        if ($delivery['logi_id'] != $_POST['dly']['logi_id']) {

            if ($delivery['logi_no'] == $_POST['dly']['logi_no']) {
                $_POST['dly']['logi_no'] = NULL;
                $data['logi_no'] = NULL;
                if ($corp['type'] == 'DANGDANG') {
                    $data['logi_no'] = $orders['order_bn'];
                }
            } else {
                $data['logi_no'] = $_POST['dly']['logi_no'];
            }

            //如果切换了物流公司且用的是当当的判断是否合并发货单
            if ($corp['type'] == 'DANGDANG') {
                if (count($order_ids)>1){
                    $this->end(false, '此发货单是合并发货单,不可以选择当当物流!');
                }
                if ($orders['shop_type']!='dangdang') {
                    $this->end(false, '非当当店铺订单,不可以选择当当物流!');
                }

            }


            if ( $corp['type'] == 'AMAZON' && $orders['shop_type']!='amazon' ) {
                $this->end(false, '此发货单是非亚马逊店铺订单,不可以选择亚马逊物流!');

            } //todo
        //计算预计物流费用
            $area = $_POST['dly_count'];
            $arrArea = explode(':', $delivery['consignee']['area']);

            $area_id = $arrArea[2];

            $price = $wmsCommonLib->getDeliveryFreight($area_id,$_POST['dly']['logi_id'],$delivery['net_weight']);
            $data['delivery_cost_expect'] = $price;

            //计算物流报价费用
            if ($delivery['logi_id']) {
                $dly_corp = $Objdly_corp->dump($_POST['dly']['logi_id']);
                $logi_name = $dly_corp['name'];
                //计算保价费用
                $protect = $dly_corp['protect'];
                if ($protect == 'true') {
                    $is_protect = 'true';
                    $protect_rate = $dly_corp['protect_rate']; //保价费率
                    $protect_price = $protect_rate * $delivery['net_weight'];
                    $minprice = $dly_corp['minprice']; //最低报价费用
                    if ($protect_price < $minprice) {
                        $cost_protect = $minprice;
                    } else {
                        $cost_protect = $protect_price;
                    }
                }
            }
            $data['cost_protect'] = $cost_protect ? $cost_protect : 0;
            $data['is_protect'] = $is_protect ? $is_protect : 'false';
        }
 
        if ($_POST['dly']['logi_no'] == '') {
            $_POST['dly']['logi_no'] = NULL;
        } else {
            if ($deliveryObj->existExpressNo($_POST['dly']['logi_no'], $_POST['dly']['delivery_id'])) {
                $this->end(false, '已有此物流单号');
            }
        }
        $_POST['dly']['logi_no'] = $_POST['dly']['logi_no'] ? trim($_POST['dly']['logi_no']) : null;
        $dly['logi_id'] = $_POST['dly']['logi_id'];
        $dly['logi_no'] = $_POST['dly']['logi_no'];
        $dly['logi_name'] = $corp['name'];
        $dly['memo'] = $_POST['dly']['memo'];

        $result = $deliveryObj->update($dly, array('delivery_id' => $_POST['dly']['delivery_id']));

        //添加发货单修改物流单号的日志
        if ($_POST['dly']['logi_no'] && $_POST['dly']['delivery_id']) {
            $dlyLog['delivery_id'] = $_POST['dly']['delivery_id'];
            $dlyLog['logi_id'] = $_POST['dly']['logi_id'];
            $dlyLog['logi_no'] = $_POST['dly']['logi_no'];
            $dlyLog['logi_name'] = $corp['name'];
            $dlyLog['create_time'] = time();
            $dlyLogObj = &app::get('wms')->model('delivery_log');
            if (!$dlyLogObj->dump(array('delivery_id' => $dlyLog['delivery_id'], 'logi_no' => $dlyLog['logi_no']))) {
                $dlyLogObj->save($dlyLog);
            }
        }

        //物流单号 或是 物流公司改变的时候 判断发货状态
        if ($delivery['logi_id'] != $_POST['dly']['logi_id'] || (!empty($delivery['logi_no']) && $delivery['logi_no'] != $_POST['dly']['logi_no'])) {
            //未发货的发货单变更物流单号应重新打印快递单
            if ($delivery['process'] == 'false') {
                $msg = '物流信息已改变，您应该从新打印快递单';
            }
        }

        $data['delivery_id'] = $_POST['dly']['delivery_id'];

        //根据真实重量计算实际物流运费
        if($_POST['weight']) {
            $arrArea = explode(':', $delivery['consignee']['area']);

            $area_id = $arrArea[2];
            $data['delivery_cost_actual'] = $wmsCommonLib->getDeliveryFreight($area_id,$_POST['dly']['logi_id'],$_POST['weight']);//修改重量时更新物流费用
        }
        $data['weight']=$_POST['weight'];//新增修改重量

        $deliveryObj->save($data);

        //仓储发货单主表信息变更，明细主记录也变更
        $deliveryBillObj = &app::get('wms')->model('delivery_bill');
        $deliveryBillObj->update(array('logi_no'=>$dly['logi_no'], 'delivery_cost_expect' => $data['delivery_cost_expect'], 'delivery_cost_actual' => $data['delivery_cost_actual'], 'weight' => $data['weight']),array('delivery_id' => $data['delivery_id'], 'type' => 1));

        if ($result) {
            if ($result === 1) {
                
                #[拆单]修改发货单详情加入发货单号_物流运单号 ExBOY
                $log_msg       = '修改发货单详情';
                $log_msg       .= (empty($delivery['delivery_bn']) ? '' : '，发货单号：'.$delivery['delivery_bn']);
                $log_msg       .= (empty($delivery['delivery_bn']) ? '' : '，物流单号：'.$_POST['dly']['logi_no']);
                
                $opObj = &app::get('ome')->model('operation_log');
                $opObj->write_log('delivery_modify@wms', $_POST['dly']['delivery_id'], $log_msg);
                //信息变更更新到oms
                $wms_id = kernel::single('ome_branch')->getWmsIdById($delivery['branch_id']);
                $data = array(
                    'delivery_bn' => $delivery['outer_delivery_bn'],
                    'weight' => $data['weight'],
                    'delivery_cost_actual' => $data['delivery_cost_actual'] ? $data['delivery_cost_actual'] : $delivery['delivery_cost_actual'],
                    'delivery_cost_expect' => $data['delivery_cost_expect'],
                    'logi_id' => $_POST['dly']['logi_id'],
                    'logi_no' => $_POST['dly']['logi_no'],
                    'logi_name' => $corp['name'],
                    'memo' => $_POST['dly']['memo'],
                    'action' => 'updateDetail',
                );
                
                $res = kernel::single('wms_event_trigger_delivery')->doUpdate($wms_id, $data, true);
            }
            $this->end(true, '保存成功' . $msg);
        }
        $this->end(false, '保存失败');
    }

    /**
     * 保存货品货位详情信息
     *
     */
    function doItemDetail() {
        $this->begin();
        if (empty($_POST['num']) || empty($_POST['pos'])) {
            $this->end(false, '请填写数量');
        }
        $dly_id = $_POST['delivery_id'];
        $number = $_POST['num'];
        $pos = $_POST['pos'];
        $Objdly = &app::get('ome')->model('delivery');
        $delivery = $Objdly->dump($dly_id);
        $arr_s = array('succ', 'cancel', 'back', 'stop','return_back');
        if (in_array($delivery['status'], $arr_s) || $delivery['process'] == 'true') {
            $this->end(false, '发货单相关信息不能修改');
        }
        if ($number)
            foreach ($number as $key => $item) {
                $count = $item;
                foreach ($pos[$key] as $k => $i) {
                    $total += $i;
                }
                if ($total != $count) {
                    $this->end(false, '保存失败，填写总数不正确');
                }
                $count = 0;
                $total = 0;
            }

        $Objpos = &app::get('ome')->model('dly_items_pos');
        if ($pos)
            foreach ($pos as $id => $row) {
                $Objpos->delete(array('item_id' => $id)); //更新前先做删除
            }
        //插入货品货位
        if ($pos)
            foreach ($pos as $key => $item) {
                foreach ($item as $k => $i) {
                    if ($i <= 0) {
                        continue;
                    }
                    $data['item_id'] = $key;
                    $data['num'] = $i;
                    $data['pos_id'] = $k;
                    $Objpos->save($data);
                    $data = '';
                }
            }
        $opObj = &app::get('ome')->model('operation_log');
        $opObj->write_log('delivery_position@ome', $_POST['delivery_id'], '发货单货位录入');
        $this->end(true, '保存成功', 'index.php?app=wms&ctl=admin_receipts_print&act=index');
    }

    /**
     * 打印页面初始化 获取打印批次号
     *
     * @return Array
     */
    function _getPrintQueue($ids) {
        if (!$result = $this->_checkPrintQueue($ids, $msg)) {
            $this->message($msg);
            exit;
        }
        $queueObj = kernel::single('ome_queue');
        $queue = $queueObj->fetchPrintQueue($ids);

        return $queue;
    }

    /**
     * 检查是否能同批次打印
     *
     * @return bool
     */
    function _checkPrintQueue($ids, &$msg) {
        if (!empty($ids)) sort($ids);

        # 批量打印限制数量
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $sku = kernel::single('base_component_request')->get_get('sku');
        if($sku==''){
            $sku = kernel::single('base_component_request')->get_post('sku');
        }

        $batch_print_nums = $deliCfgLib->getValue('wms_batch_print_nums',$sku);
        if (count($ids) > $batch_print_nums) {
            $msg = "所选发货单号数量已超过批量打印数量 (" . $batch_print_nums . ")！";
            return false;
        }

        $delivery_check_ident = &app::get('wms')->getConf('wms.delivery.check_ident');
        $delivery_check_ident = $delivery_check_ident ? $delivery_check_ident : 'on';
        $queueObj = kernel::single('ome_queue');
        if ($queueObj->isExistsQueueItems($ids, $existsQueueItems)) {
            if (count($ids) != count($existsQueueItems)) {
                $msg = "已生成批次号的发货单不能和未生成的发货单一起打印！";
            } else {
                $error = array();
                foreach ($existsQueueItems as $k => $v) {
                    if (!in_array($v, $error)) {
                        $error[] = $v;
                    }
                }
                $msg = "发货单号已存在有不相同的批次号：<br/>" . join('<br/>',$error);
            }
            if ($delivery_check_ident == 'on') {
                return false;
            } else {
                $this->pagedata['existsIdents'] = str_replace($msg,'<br/>','&nbsp;&nbsp;');
            }
        }

        return true;
    }

    function message($msg) {

        $this->pagedata['err'] = 'true';
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['time'] = date("Y-m-d H:i:s");
        $this->pagedata['msg'] = $msg;
        $this->singlepage('admin/delivery/message.html');
        $this->display('admin/delivery/print.html');
    }

    function parsePrintIds($ids) {
        $result = array(
            'ids' => array(), //可用于打印的ID
            'errIds' => array(), //不能胜于打印的数据
            'errInfo' => array(), //所有错误信息
        );

        $dlyObj = & app::get('wms')->model('delivery');
        $dlyCheckLib = kernel::single('wms_delivery_check');

        foreach ($ids as $id) {
            $hasError = false;
            //检查当前订单的状态是不是可以打印
            if (!$dlyCheckLib->checkOrderStatus($id, true, $errMsg)) {
                //状态有问题的订单是肯定不要打印的
                $result['errIds'][] = $id;
                $result['errInfo'][$id] = $errMsg;
                $hasError = true;
            } else {
                $result['ids'][] = $id;
            }
            if (!$hasError) {
                //检查库存(除原样寄回发货单)
                $dly = $dlyObj->dump($id, '*', array('delivery_items' => array('*')));
                if ($dly['type'] == 'normal') {
                    foreach ($dly['delivery_items'] as $item) {
                        $re = $dlyObj->existStockIsPlus($item['product_id'], $item['number'], $item['item_id'], $dly['branch_id'], $err, $item['bn']);
                        if (!$re) {
                            $result['errIds'][] = $id;
                            $result['errInfo'][$id] .= $err . "&nbsp;,&nbsp;";
                            $hasError = true;
                        }
                    }
                }
                //库存有问题的单据认为是要打印的
                if (!in_array($id, $result['ids']))
                    $result['ids'][] = $id;
            }
        }

        if (empty($result['ids'])) {
            if (!empty($result['errIds'])) {
                $msg = sprintf("你所选择的 %d 张单据状态异常，无法打印，本次操作中止！", count($result['errIds']));
            } else {
                $msg = '你没有选择要打印的单据，请重新选择后再试！';
            }
            $this->message($msg);
            exit;
        }

        return $result;
    }

    /**
     * 设置订单样式
     * @param null
     * @return null
     */
    public function showPrintStyle() {
        $this->path[] = array('text' =>'订单打印格式设置');
        $dbTmpl = app::get('ome')->model('print_tmpl_diy');
        $stockPrintTxt = $dbTmpl->get('wms', '/admin/delivery/stock_print');
        $deliveryPrintTxt = $dbTmpl->get('wms', '/admin/delivery/delivery_print');
        $mergePrintTxt = $dbTmpl->get('wms', '/admin/delivery/merge_print');
        $contentPurchase = $dbTmpl->get('purchase', '/admin/purchase/purchase_print');
        $contentPurchaseEo = $dbTmpl->get('purchase', '/admin/eo/eo_print');
        $contentPurchaseReturn = $dbTmpl->get('purchase', '/admin/returned/return_print');

        $this->pagedata['styleContent'] = $stockPrintTxt;
        $this->pagedata['styleContentDelivery'] = $deliveryPrintTxt;
        $this->pagedata['styleContentMerge'] = $mergePrintTxt;
        $this->pagedata['styleContentPurchase'] = $contentPurchase;
        $this->pagedata['styleContentPurchaseEo'] = $contentPurchaseEo;
        $this->pagedata['styleContentPurchaseReturn'] = $contentPurchaseReturn;
        $this->page('admin/delivery/printstyle.html');
    }

    /**
     * 保存订单打印样式
     * @param null
     * @return null
     */
    public function savePrintStyle() {
        $current_print = $_POST['current_print'];
        $dbTmpl = app::get('ome')->model('print_tmpl_diy');
        switch ($current_print) {
            case 'txtContent':
                $dbTmpl->set('wms', '/admin/delivery/stock_print', $_POST["txtContent"]);
                break;
            case 'txtContentDelivery':
                $dbTmpl->set('wms', '/admin/delivery/delivery_print', $_POST["txtContentDelivery"]);
                break;
            case 'txtContentMerge':
                $dbTmpl->set('wms', '/admin/delivery/merge_print', $_POST["txtContentMerge"]);
                break;
            case 'txtContentPurchase':
                $dbTmpl->set('purchase', '/admin/purchase/purchase_print', $_POST["txtContentPurchase"]);
                break;
            case 'txtContentPurchaseEo':
                $dbTmpl->set('purchase', '/admin/eo/eo_print', $_POST["txtContentPurchaseEo"]);
                break;
            case 'txtContentPurchaseReturn':
                $dbTmpl->set('purchase', '/admin/returned/return_print', $_POST["txtContentPurchaseReturn"]);
                break;
        }

        echo "<script>$$('.dialog').getLast().retrieve('instance').close();</script>";
    }

    /**
     * rebackPrintStyle
     *
     * @access public
     * @return void
     */
    public function rebackPrintStyle() {
        $current_print = $_POST['current_print'];
        $dbTmpl = app::get('ome')->model('print_tmpl_diy');
        switch ($current_print) {
            case 'txtContent':
                $dbTmpl->clear('wms', '/admin/delivery/stock_print');
                break;
            case 'txtContentDelivery':
                $dbTmpl->clear('wms', '/admin/delivery/delivery_print');
                break;
            case 'txtContentMerge':
                $dbTmpl->clear('wms', '/admin/delivery/merge_print');
                break;
            case 'txtContentPurchase':
                $dbTmpl->clear('purchase', '/admin/purchase/purchase_print');
                break;
            case 'txtContentPurchaseEo':
                $dbTmpl->clear('purchase', '/admin/eo/eo_print');
                break;
            case 'txtContentPurchaseReturn':
                $dbTmpl->clear('purchase', '/admin/returned/return_print');
                break;
        }

        echo "<script>$$('.dialog').getLast().retrieve('instance').close();</script>";
    }

    /**
     * 根据发货单ID修改它对映的发货配置
     * 一旦打印了任何一种单据就不
     *
     * @author chenping<chenping@shopex.cn>
     */
    private function updateDeliCfg($deliIds,$sku='') {
        $filter = array(
            'delivery_id' => $deliIds,
            'stock_status' => 'false',
            'deliv_status' => 'false',
            'expre_status' => 'false',
        );
        $data = array(
            'deli_cfg' => $sku,
        );
        $deliModel = app::get('wms')->model('delivery');
        $deliModel->update($data,$filter);
    }

    public function orderbycreatetime($sku,$order_val,$op_id){
        $this->begin('index.php?app=wms&ctl=admin_receipts_print&act=index&status=0&sku='.$sku);

        if($order_val == 1){
            $value = 1;
        }else{
            $value = 0;
        }
        app::get('ome')->setConf('delivery.bycreatetime'.$op_id,$value);
        $this->end(true,'设置成功');
    }

    /**
     * @description 外部物流单号导入页
     * @access public
     * @param void
     * @author chenping<chenping@shopex.cn>
     * @return void
     */
    public function outerLogiIO()
    {
        $this->display('admin/delivery/outer_logi_io.html');
    }

    /**
     * @description 导出外部运单号模板
     * @access public
     * @param void
     * @author chenping<chenping@shopex.cn>
     * @return void
     */
    public function outerLogiTemplate()
    {
        $filename = "外部运单号模板".date('Y-m-d').".csv";
        $encoded_filename = urlencode($filename);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);
        $ua = $_SERVER["HTTP_USER_AGENT"];
        header("Content-Type: text/csv");
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox$/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $obj = app::get('ome')->model('delivery_outerlogi');
        $title = $obj->io_title();
        foreach ($title as $key=>&$value) {
            $value = kernel::single('base_charset')->utf2local($value);
        }
        echo '"'.implode('","',$title).'"';
    }

    public function shipStatus(){
        return array('succ'=> '已发货','unsucc'=> '未发货');#已发货状态以外的，都是未发货
    }

    /**
     * 打印新版快递单
     *
     * 修改 加了一个补打快递单的开关 wujian@shopex.cn 2012年3月14日
     */
    function toPrintExpre($afterPrint=true) {
        $_err = 'false';

        /* 单品、多品标识 */
        $sku = kernel::single('base_component_request')->get_get('sku');
        $sku = $sku ? $sku : '';

        $now_print_type = 'ship';

        //获取当前待打印的发货单过滤条件
        $filter_condition = $this->getPreparePrintIds();

        $PrintLib = kernel::single('wms_delivery_print');
        $print_data = $PrintLib->getPrintDatas($filter_condition,$now_print_type,$sku,$afterPrint,$msg);
        if(isset($msg['error_msg']) && $msg['error_msg']){
            $this->headerErrorMsgDisply($msg['error_msg']);
        }

        if(isset($msg['warn_msg']) && $msg['warn_msg']){
            $this->message($msg['warn_msg']);
            exit;
        }
        
        $waybillObj = kernel::single('logisticsmanager_service_waybill');
        $channel_extObj =  &app::get("logisticsmanager")->model("waybill_extend");
        $deliveryObj = &app::get('wms')->model('delivery');
        $dlyBillObj = &app::get('wms')->model('delivery_bill');
        $ids = $print_data['ids'];

        //防止并发打印重复获取运单号
        $_inner_key = sprintf("print_ids_%s", md5(implode(',',$ids)));
        $aData = cachecore::fetch($_inner_key);
        if ($aData === false) {
            cachecore::store($_inner_key, 'printed', 5);
        }else{
            $this->message("选中的发货单已在打印快递单中，请不要重复打印！！！如没有打印完成，请稍后重试！！！");
            exit;
        }

        //电子面单处理流程
        if ($ids) {
            $channel_info = kernel::single('wms_delivery_print_ship')->getWaybillType($ids[0]);
            //获取申通电子面单现改为打印时就获取
            if (in_array($channel_info['channel_type'],array('sto'))) {
                $wbParams = array(
                    'channel_id' => $channel_info['channel_id'],
                );
                $waybillObj->request_waybill($wbParams);
                unset($wbParams);
            }
            //
            //非补打处理流程
            if($afterPrint){
                //如果是补打的不走直连一个个取，因为是以订单号为唯一的，需人工添加运动号
                //如果是直连电子面单，判断运单号是否足够，不够的直连去取运单号,只能单个单个取，取完后重新刷新当前页面重新走当前控制器
                if ($channel_info['type'] == 'direct') {
                    //检查要打的快递单运单号是否都有了
                    $zlFinish = kernel::single('wms_delivery_print_ship')->checkAllHasLogiNo($ids);
                    if ($zlFinish == false && !isset($_GET['isdown'])) {
                        $this->getElectronLogiNo($_GET, $ids, $channel_info);
                    }
                }

                //判断当前发货单非直连取运单，从缓存池取到后立即更新
                foreach($print_data['deliverys'] as $k => $dly) {
                    if (!$dly['logi_no'] && $channel_info['type'] != 'normal') {
                        if (in_array($channel_info['channel_type'],array('sto'))) {
                            //从缓存库获取电子面单
                            $tmp_logi_no = '';
                            $wbParams = array(
                                'channel_id' => $channel_info['channel_id'],
                            );
                            
                            $tmp_logi_no = $waybillObj->get_waybill($wbParams);
                            
                            if($tmp_logi_no){
                                $dlyBillObj->db->exec("update sdb_wms_delivery_bill set logi_no='".$tmp_logi_no."' where delivery_id =".$dly['delivery_id']." and type = 1 and (logi_no is null or logi_no ='')");
                                $logiUpdate = $dlyBillObj->db->affect_row();
                                if($logiUpdate > 0){
                                    //电子面单获取后顺便请求ome模块更新物流单号
                                    $wms_id = kernel::single('ome_branch')->getWmsIdById($dly['branch_id']);
                                    $tmp_data = array(
                                        'delivery_bn' => $dly['outer_delivery_bn'],
                                        'logi_no' => $tmp_logi_no,
                                        'action' => 'addLogiNo',
                                    );
                                    $res = kernel::single('wms_event_trigger_delivery')->doUpdate($wms_id, $tmp_data, true);

                                    $print_data['deliverys'][$k]['logi_no'] = $tmp_logi_no;
                                    $dly['logi_no'] = $tmp_logi_no;
                                }else{
                                    //如果取物流单号更新失败，则标记该发货单不打印，记录错误信息
                                    $print_data['errIds'][] = $dly['delivery_id'];
                                    $print_data['errBns'][$dly['delivery_id']] = $dly['delivery_bn'];
                                    $print_data['errInfo'][$dly['delivery_id']] = '更新电子面单号:'.$tmp_logi_no.'失败，请检查此单号是否已使用！';
                                    unset($print_data['deliverys'][$k]);
                                    if($key = array_search($dly['delivery_id'],$ids)){
                                        unset($print_data['ids'][$key]);
                                    }
                                    continue;
                                }
                                usleep(1000);
                            }
                            unset($wbParams);
                        
                            //判断是否有大头笔申通，否则去获取
                            if ($tmp_logi_no && $logiUpdate) {
                                $channel_ext = $channel_extObj->get_position($tmp_logi_no);
                                if ($channel_ext['position']=='') {
                                    $channelTypeObj = kernel::single('logisticsmanager_service_' . $channel_info['channel_type']);
                                    $delivery_params = array('delivery_id'=>$dly['delivery_id'],'logi_no'=>$tmp_logi_no);
                                    $channelTypeObj->delivery($delivery_params);
                                }
                            }
                        }else{
                            //获取店铺信息
                            $shopObj = &app::get("ome")->model('shop');
                            $shopInfo = $shopObj->dump(array('shop_id' => $dly['shop_id']), 'shop_type,addon');
                            if ($channel_info['channel_type']=='ems'  || ($channel_info['channel_type']=='wlb' && $channel_info['shop_id'] == $dly['shop_id']) || ($channel_info['channel_type'] == '360buy' && $shopInfo['addon']['type'] == 'SOP')) {
                                $wbParams = array(
                                    'channel_id' => $channel_info['channel_id'],
                                );
                                //打一次接口获取电子面单
                                if(!$waybillRpc[$channel_info['channel_id']]) {
                                    $waybillObj->request_waybill($wbParams);
                                    $waybillRpc[$channel_info['channel_id']] = true;
                                }
                                //从缓存库获取电子面单
                                $logi_no = '';
                                $wbParams['shop_id'] = $dly['shop_id'];
                                $logi_no = $waybillObj->get_waybill($wbParams);
                                if($logi_no){
                                    $dlyBillObj->db->exec("update sdb_wms_delivery_bill set logi_no='".$logi_no."' where delivery_id =".$dly['delivery_id']." and type = 1 and (logi_no is null or logi_no ='')");
                                    $logiUpdate = $dlyBillObj->db->affect_row();
                                    if($logiUpdate > 0){
                                        //电子面单获取后顺便请求ome模块更新物流单号
                                        $wms_id = kernel::single('ome_branch')->getWmsIdById($dly['branch_id']);
                                        $tmp_data = array(
                                            'delivery_bn' => $dly['outer_delivery_bn'],
                                            'logi_no' => $logi_no,
                                            'action' => 'addLogiNo',
                                        );
                                        $res = kernel::single('wms_event_trigger_delivery')->doUpdate($wms_id, $tmp_data, true);

                                        $print_data['deliverys'][$k]['logi_no'] = $logi_no;
                                        $dly['logi_no'] = $logi_no;
                                    }else{
                                        $print_data['errIds'][] = $dly['delivery_id'];
                                        $print_data['errBns'][$dly['delivery_id']] = $dly['delivery_bn'];
                                        $print_data['errInfo'][$dly['delivery_id']] = '更新电子面单号:'.$logi_no.'失败，请检查此单号是否已使用！';
                                        unset($print_data['deliverys'][$k]);
                                        if($key = array_search($dly['delivery_id'],$ids)){
                                            unset($print_data['ids'][$key]);
                                        }
                                        continue;
                                    }
                                    usleep(1000);
                                } else {
                                    $print_data['errIds'][] = $dly['delivery_id'];
                                    $print_data['errBns'][$dly['delivery_id']] = $dly['delivery_bn'];
                                    $print_data['errInfo'][$dly['delivery_id']] = '获取电子面单号失败22！';
                                    unset($print_data['deliverys'][$k]);
                                    if($key = array_search($dly['delivery_id'],$ids)){
                                        unset($print_data['ids'][$key]);
                                    }
                                    continue;
                                }
                                unset($wbParams);
                            }elseif ($channel_info && ($channel_info['channel_type'] == 'taobao')) {
                                //直连电子面单
                                $wbParams = array(
                                    'channel_id' => $channel_info['channel_id'],
                                );
                            }else {
                                $print_data['errIds'][] = $dly['delivery_id'];
                                $print_data['errBns'][$dly['delivery_id']] = $dly['delivery_bn'];
                                $print_data['errInfo'][$dly['delivery_id']] = '未找到获取电子面单号的来源！';
                                unset($print_data['deliverys'][$k]);
                                if($key = array_search($dly['delivery_id'],$ids)){
                                    unset($print_data['ids'][$key]);
                                }
                                continue;
                            }
                        }
                    }

                    //如果运单号有，是申通没大头笔信息就去获取一次
                    if (in_array($channel_info['channel_type'],array('sto'))) {
                        $channel_ext = $channel_extObj->get_position($dly['logi_no']);
                        if ($channel_ext['position']=='') {
                            $channelTypeObj = kernel::single('logisticsmanager_service_sto' );
                            $delivery_params = array('delivery_id'=>$dly['delivery_id'],'logi_no'=>$dly['logi_no']);
                            $channelTypeObj->delivery($delivery_params);
                        }
                    }
                }
            }elseif(!$afterPrint && count($_REQUEST['b_id'])>0){
                $childrenIds = $_REQUEST['b_id'];
                $main_dly_id = $ids[0];
                $main_dly_bn = $print_data['deliverys'][$main_dly_id]['delivery_bn'];
                $now_shop_id = $print_data['deliverys'][$main_dly_id]['shop_id'];

                //直连的其中顺丰和韵达也取补打物流单
                if ($channel_info['type'] == 'direct') {
                    //检查要打的快递单运单号是否都有了
                    $zlFinish = kernel::single('wms_delivery_print_ship')->checkAllHasLogiNo($main_dly_id,$afterPrint,$childrenIds);
                    if ($zlFinish == false && !isset($_GET['isdown'])) {
                        $this->getElectronLogiNo($_GET, $ids, $channel_info,$afterPrint,$childrenIds);
                    }
                }
               
                foreach((array)$childrenIds as $k => $childrenId){
                    $bill_id = $main_dly_id."-".$childrenId;
                    $bill_bn = $main_dly_bn."-".$childrenId;
                    
                    $children = $dlyBillObj->dump(array('b_id'=>$childrenId,'type'=>2),'b_id,logi_no,status');
                    //检查子单状态
                    if($children['status'] && $children['status'] != '0') {
                        $print_data['errIds'][] = $bill_id;
                        $print_data['errBns'][$bill_id] = $bill_bn;
                        $print_data['errInfo'][$bill_id] = '补打的物流单号状态不对，请检查此单号是否为未发货！';
                        continue;
                    }
                    
                    if (!$children['logi_no'] && $channel_info['type'] != 'normal') {
                        if (in_array($channel_info['channel_type'],array('sto'))) {
                            //从缓存库获取电子面单
                            $tmp_logi_no = '';
                            $wbParams = array(
                                'channel_id' => $channel_info['channel_id'],
                            );
                            
                            $tmp_logi_no = $waybillObj->get_waybill($wbParams);

                            if($tmp_logi_no){
                                $logiUpdate = $dlyBillObj->update(array('logi_no'=>$tmp_logi_no), array('log_id'=>$childrenId,'delivery_id'=>$main_dly_id,'type'=>2));
                                if(!$logiUpdate) {
                                    $print_data['errIds'][] = $bill_id;
                                    $print_data['errBns'][$bill_id] = $bill_bn;
                                    $print_data['errInfo'][$bill_id] = '更新电子面单号:'.$tmp_logi_no.'失败，请检查此单号是否已使用！';
                                    continue;
                                }
                                usleep(1000);
                            }
                            unset($wbParams);
                        
                            //判断是否有大头笔申通，否则去获取
                            if ($tmp_logi_no) {
                                $channel_ext = $channel_extObj->get_position($tmp_logi_no);
                                if ($channel_ext['position']=='') {
                                    $channelTypeObj = kernel::single('logisticsmanager_service_' . $channel_info['channel_type']);
                                    $delivery_params = array('delivery_id'=>$main_dly_id,'logi_no'=>$tmp_logi_no);
                                    $channelTypeObj->delivery($delivery_params);
                                }
                            }
                        }else{
                            //获取店铺信息
                            $shopObj = &app::get("ome")->model('shop');
                            $shopInfo = $shopObj->dump(array('shop_id' => $now_shop_id), 'shop_type,addon');
                            if ($channel_info['channel_type']=='ems'  || ($channel_info['channel_type']=='wlb' && $channel_info['shop_id'] == $now_shop_id) || ($channel_info['channel_type'] == '360buy' && $shopInfo['addon']['type'] == 'SOP')) {
                                $wbParams = array(
                                    'channel_id' => $channel_info['channel_id'],
                                );
                                //打一次接口获取电子面单
                                if(!$waybillRpc[$channel_info['channel_id']]) {
                                    $waybillObj->request_waybill($wbParams);
                                    $waybillRpc[$channel_info['channel_id']] = true;
                                }
                                //从缓存库获取电子面单
                                $tmp_logi_no = '';
                                $tmp_logi_no = $waybillObj->get_waybill($wbParams);
                                
                                if($tmp_logi_no){
                                    $logiUpdate = $dlyBillObj->update(array('logi_no'=>$tmp_logi_no), array('log_id'=>$childrenId,'delivery_id'=>$main_dly_id,'type'=>2));
                                    if(!$logiUpdate) {
                                        $print_data['errIds'][] = $bill_id;
                                        $print_data['errBns'][$bill_id] = $bill_bn;
                                        $print_data['errInfo'][$bill_id] = '更新电子面单号:'.$tmp_logi_no.'失败，请检查此单号是否已使用！';
                                        continue;
                                    }
                                    usleep(1000);
                                } else {
                                    $print_data['errIds'][] = $bill_id;
                                    $print_data['errBns'][$bill_id] = $bill_bn;
                                    $print_data['errInfo'][$bill_id] = '获取电子面单号失败1！';
                                    continue;
                                }
                                unset($wbParams);
                            }elseif ($channel_info && ($channel_info['channel_type'] == 'taobao')) {
                                //to do
                            }else {
                                $print_data['errIds'][] = $bill_id;
                                $print_data['errBns'][$bill_id] = $bill_bn;
                                $print_data['errInfo'][$bill_id] = '未找到获取电子面单号的来源！';
                                continue;
                            }
                        }
                    }

                    //如果运单号有，是申通没大头笔信息就去获取一次
                    if (in_array($channel_info['channel_type'],array('sto'))) {
                        $channel_ext = $channel_extObj->get_position($children['logi_no']);
                        if ($channel_ext['position']=='') {
                            $channelTypeObj = kernel::single('logisticsmanager_service_sto' );
                            $delivery_params = array('delivery_id'=>$main_dly_id,'logi_no'=>$children['logi_no']);
                            $channelTypeObj->delivery($delivery_params);
                        }
                    }
                    //复制子单重的发货单信息，并记录赋值获取到的当前运单号
                    $print_data['deliverys'][$bill_id] = $print_data['deliverys'][$main_dly_id];
                    $print_data['deliverys'][$bill_id]['logi_no'] = isset($children['logi_no']) ? $children['logi_no'] : $tmp_logi_no;
                    unset($childrenId,$children);
                }
                 //判断是单个补打，还是全部补打
                $delivery_data = $deliveryObj->dump($main_dly_id,'logi_number');
                
                if (in_array($channel_info['channel_type'],array('360buy')) && count($childrenIds)==$delivery_data['logi_number']-1){
                }else{
                    //子单循环结束将原有主物流单信息删除
                    unset($print_data['deliverys'][$main_dly_id]);
                }
            }
        }
        
        $PrintShipLib = kernel::single('wms_delivery_print_ship');
        $format_data = $PrintShipLib->format($print_data, $sku,$_err);
        
        $this->pagedata = $format_data;

        if ($format_data['delivery']) {
            foreach ($format_data['delivery'] as $val) {
                //获取快递单打印模板的servivce定义
                $data = array();
                foreach (kernel::servicelist('wms.service.template') as $object => $instance) {
                    if (method_exists($instance, 'getElementContent')) {
                        $tmp = $instance->getElementContent($val);
                    }
                    $data = array_merge($data, $tmp);
                }
                $mydata[] = $data;
            }
        }

        $jsondata = $PrintShipLib->arrayToJson($mydata);

        //组织控件打印数据
        $this->pagedata['jsondata'] = $jsondata;
        $this->pagedata['data'] = addslashes($deliveryObj->array2xml2($mydata, 'data'));
        $this->pagedata['totalPage'] = count($mydata);
        
        $templateObj = &app::get("logisticsmanager")->model('express_template');
        //获取快递面单
        $this->pagedata['printTmpl'] = $templateObj->dump($format_data['dly_tmpl_id']);
        /* 修改的地方 */
        if ($this->pagedata['printTmpl']['file_id']) {
            $this->pagedata['tmpl_bg'] = 'index.php?app=ome&ctl=admin_delivery_print&act=showPicture&p[0]=' . $this->pagedata['printTmpl']['file_id'];
        }

        //获取有问题的单据号
        $this->pagedata['errBns'] = $print_data['errBns'];
        $this->pagedata['err'] = $_err;

        //批次号
        $this->pagedata['idents'] = $print_data['identInfo']['items'];
        $this->pagedata['ident'] = join(',', $print_data['identInfo']['idents']);
        $this->pagedata['errIds'] = $print_data['errIds'];
        $this->pagedata['errInfo'] = $print_data['errInfo'];
        $items = array();
        foreach ($format_data['delivery'] as $row) {
            $items[$row['delivery_id']] = $row;
        }

        $this->pagedata['items'] = $items;
        $this->pagedata['sku'] = $sku;//单品 多品标识
        $this->pagedata['dpi'] = 96;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['title'] = '快递单打印';
        $this->pagedata['uniqid'] = uniqid();

        if(!$afterPrint){
            $this->pagedata['b_id'] = $_REQUEST['b_id'];

            $billFilter = array(
                'b_id'=>$_REQUEST['b_id'],
            );
            $this->pagedata['bill_logi_no'] = $dlyBillObj->getList('b_id,logi_no',$billFilter);
            $this->pagedata['delibery_bill_flag'] = 'delibery_bill_flag';
        }
        
        if(app::get('invoice')->is_installed())
        {
            $inOrder   = &app::get('invoice')->model('order');
            $inOrder->update_batch_number($this->pagedata['allItems'], $print_data['identInfo']);
        }

        //获取打印版本配置信息
        $logicfg = kernel::single('ome_print_logicfg')->getLogiCfg();
        if($logicfg[$express_company_no]){
            $logiVersionFlag = 1;
            $this->pagedata['logicfg'] = $logicfg[$express_company_no];
            $print_logi_version = app::get('ome')->getConf('print_logi_version_'.$this->pagedata['print_logi_id']);

            $this->pagedata['print_logi_version'] = intval($print_logi_version);
        }
        $this->pagedata['logiVersionFlag'] = $logiVersionFlag;
        $params = array('order_bn'=>$this->pagedata['o_bn']);
        ome_print_tmpl_express::instance($express_company_no,$this)->setParams($params)->getTmpl();
    }

    function covertNullToString(&$items) {
        foreach ($items as $k => &$v) {
            if ($v === null) {
                $v = "";
            }
            elseif (is_array($v)) {
                $this->covertNullToString($v);
            }
            else {
                $v = strval($v);
            }
        }
        return $items;
    }

    /**
     * 检查发货类型
     **/
    public function checkDeliveryType($id) {
        $dlyObj = &app::get('wms')->model('delivery');
        $channelObj = &app::get("logisticsmanager")->model("channel");
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $data = $dlyObj->dump($id, '*');
        $dlyCorp = $dlyCorpObj->dump($data['logi_id'], 'prt_tmpl_id,type,tmpl_type,channel_id,shop_id');
        $tpye = 'normal';
        //获取电子面单渠道
        $type = 'normal';
        $return = array('type' => 'normal');
        if ($dlyCorp['tmpl_type'] == 'electron') {
            $cFilter = array(
                'channel_id' => $dlyCorp['channel_id'],
                'status'=>'true',
            );
            $channel = $channelObj->dump($cFilter);
            $zlList = array('taobao', 'sf', 'yunda');
            if ($channel && in_array($channel['channel_type'], $zlList)) {
                $return = array('type' => 'zl', 'channel_type' => $channel['channel_type'], 'channel_id' => $channel['channel_id']);
            }
        }
        return $return;
    }

    /**
     * 检查物流单是否已经获取
     */
    public function checkWaybillOrderFinish($deliveryIds) {
        $dlyBillObj = &app::get('wms')->model('delivery_bill');
        $deliveryIdStr = implode(',', $deliveryIds);
        $sql = "SELECT count(logi_no) as _count FROM `sdb_wms_delivery_bill` where delivery_id IN (". $deliveryIdStr . ") and type=1";
        $result = $dlyBillObj->db->select($sql);
        $status = false;
        if ($result) {
            $count = $result[0]['_count'];
            if (count($deliveryIds) == $count) {
                $status = true;
            }
        }
        return $status;
    }

    /**
     * 获取电子面单运单号
     */
    public function getElectronLogiNo($params, $ids, $channel,$afterprint = true,$childrens ='') {
        $urlParams = json_encode($params);
        $postIds = json_encode($ids);
        $request_uri = kernel::single('base_component_request')->get_request_uri() . '&isdown=1';
        $this->pagedata['urlParams'] = $urlParams;
        $this->pagedata['postIds'] = $postIds;
        //$this->pagedata['count'] = $count;
        $this->pagedata['channel'] = $channel;
        //非补打标记为1，补打为2
        $this->pagedata['afterprint'] = ($afterprint == true) ? 1 : 2;
        $this->pagedata['request_uri'] = base64_encode($request_uri);
        if($afterprint){
            $this->singlepage('admin/delivery/controllertmpl/getelectronlogino.html');exit;
        }else{
            $cIds = json_encode($childrens);
            $this->pagedata['cIds'] = $cIds;
            $this->singlepage('admin/delivery/controllertmpl/getelectronlogino_bill.html');exit;
        }
    }

    /**
     *运单号异步页面
     */
    public function async_logino_page() {
        $channel_id = $_GET['channel_id'];
        $request_uri = base64_decode($_GET['request_uri']);
        $this->pagedata['channel_id'] = $channel_id;
        $this->pagedata['request_uri'] = $request_uri;

        $ids = explode(',', urldecode($_GET['itemIds']));

        //2为补打
        if($_GET['after_print'] == 2){
            $afterprint = $_GET['after_print'];
            $cIds = explode(',', urldecode($_GET['cIds']));
            $count = count($cIds);
            $this->pagedata['count'] = $count;
            $this->pagedata['postIds'] = json_encode($cIds);
            $this->pagedata['delivery_id'] = $ids[0];
            $this->display('admin/delivery/controllertmpl/async_bill_logino_page.html');
        }else{
            $this->pagedata['postIds'] = json_encode($ids);
            $count = count($ids);
            $this->pagedata['count'] = $count;
            $this->display('admin/delivery/controllertmpl/async_logino_page.html');
        }
    }

    public function getWaybillLogiNo() {
        $waybillObj = kernel::single('logisticsmanager_service_waybill');
        $channel_id = $_POST['channel_id'];
        $delivery_id = $_POST['id'];
        #检查发货单是否已经获取运单号
        $params = array(
            'delivery_id' => $delivery_id,
            'channel_id' => $channel_id
        );
        $result  = $waybillObj->getWaybillLogiNo($params);
        if ($result['rsp'] == 'succ' && $result['data'][0]['logi_no']) {
            $result = array(
                'rsp' => 'succ',
                'logi_no' => $result['data'][0]['logi_no'],
                'delivery_id' => $delivery_id,
                'delivery_bn' => $result['data'][0]['delivery_bn']
            );
        }
        else {
            $result = array(
                'rsp' => 'fail',
                'delivery_id' => $delivery_id,
                'delivery_bn' => $result['data'][0]['delivery_bn']
            );
        }
        echo json_encode($result);
    }

    public function getExtLogiNo() {
        $waybillObj = kernel::single('logisticsmanager_service_waybill');
        $channel_id = $_POST['channel_id'];
        $c_id = $_POST['cid'];
        $d_id = $_POST['did'];
        #检查发货单是否已经获取运单号
        $params = array(
            'c_id' => $c_id,
            'delivery_id' => $d_id,
            'channel_id' => $channel_id
        );
        $result  = $waybillObj->getWaybillLogiNo($params);
        if ($result['rsp'] == 'succ' && $result['data'][0]['logi_no']) {
            $result = array(
                'rsp' => 'succ',
                'logi_no' => $result['data'][0]['logi_no'],
                'delivery_id' => $d_id,
                'b_id' => $c_id,
            );
        }
        else {
            $result = array(
                'rsp' => 'fail',
                'delivery_id' => $d_id,
                'b_id' => $c_id,
            );
        }
        echo json_encode($result);
    }
    

    #处理淘宝分销类型订单备注
    public function fomate_tbfx_memo($memo = null,$markShowMethod ='last'){
        return '留言：'.preg_replace('/(买家|分销商|系统).*\(\d{4}-\d{1,2}-\d{1,2}\s{0,}\d{1,2}:\d{1,2}:\d{1,2}\)\s{0,}\(.*\)\s{0,}[:|：]/isU', '', $memo);
    }
    
    /*
     * 获取当前准备打印的发货单号
     */
    public function getPreparePrintIds(){
        $delivery_ids = $_REQUEST['delivery_id'];
        $isSelectAll = $_REQUEST['isSelectedAll'];
        $printIds = array('filter'=>'');

        $filter = $this->processFilter();
        
        //待打印,为避免重复打印，在后台，把相关打印字段加入到过滤条件中
        $filter_sql = null;
        if(($_GET['status']==='0') && ($_GET['sku'] == '')){
            $filter_sql =  $this->getfiltersql();
        }

        if ($isSelectAll == '_ALL_') {
            if($filter_sql){
                $filter['filter_sql'] = $filter_sql;
            }

            $printIds['filter'] = $filter;
            return $printIds;

        }else {
            //去除值 为空，null，FALSE的key和value
            $delivery_ids = array_filter($delivery_ids);
            if ($delivery_ids) {
                if (is_array($delivery_ids)) {
                    $filter['delivery_id'] = $delivery_ids;
                    if($filter_sql){
                        $filter['filter_sql'] = $filter_sql;
                    }

                    $printIds['filter'] = $filter;
                    return $printIds;

                }else {
                    $printIds['filter'] = array('delivery_id'=>$delivery_ids);
                    return $printIds;
                }
            } else {
                $this->headerErrorMsgDisply("请选择数据");
            }
        }
    }

    #为避免重复打印，在后台，把打印相关字段加入到过滤条件中
    function getfiltersql(){
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $btncombi = $deliCfgLib->btnCombi($_GET['sku']);
        #根据发货配置，只筛选未打印的
        switch ($btncombi) {
            case '1_1':
                $filter_sql= "((print_status & 1) !=1 or (print_status & 2) !=2 or (print_status & 4) !=4)";
                break;
            case '1_0':
                $filter_sql= "((print_status & 1) !=1 or (print_status & 4) !=4)";
                break;
            case '0_1':
                $filter_sql= "((print_status & 2) !=2 or (print_status & 4) !=4)";
                break;
            case '0_0':
                $filter_sql= "((print_status & 4) !=4)";
                break;
        }
        return $filter_sql;
    }

    /**
     * 显示信息
     */
    public function headerErrorMsgDisply($msg) {
        header("Content-type: text/html; charset=utf-8");
        exit($msg);
    }

    /**
     * 返回物流公司来源.
     * @param   delivery_id
     * @return  array
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function getDeliveryType($id) {
        $deliveryObj = &app::get('wms')->model('delivery');
        $channelObj = &app::get("logisticsmanager")->model("channel");
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $data = $deliveryObj->dump($id, '*');
        $dlyCorp = $dlyCorpObj->dump($data['logi_id'], 'prt_tmpl_id,type,tmpl_type,channel_id,shop_id');
        $tpye = 'normal';
        //获取电子面单渠道
        $type = 'normal';
        $channel = array('type' => 'normal');
        
        if ($dlyCorp['tmpl_type'] == 'electron') {
            $cFilter = array(
                'channel_id' => $dlyCorp['channel_id'],
                'status'=>'true',
            );
            $channel = $channelObj->dump($cFilter);
            
        }
        return $channel;
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
        $waybillObj = kernel::single('logisticsmanager_service_waybill');
    
       
        $deliveryObj = app::get('wms')->model('delivery');
        $dly_data = $deliveryObj->dump(21);
        print_r($dly_data);
                        //回收电子面单
                        if ($dly_data['logi_no']) {
                            //直连物流：EMS、申通、京东回收
                            
                            $waybillObj->recycle_waybill($dly_data['logi_no'],1);
                        }
    }
}