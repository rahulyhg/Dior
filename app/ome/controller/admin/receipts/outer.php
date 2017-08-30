<?php
/*
 * 第三方发货
 *
 */
class ome_ctl_admin_receipts_outer extends ome_ctl_admin_receipts_print {

    var $name        = "发货中心";
    var $workground  = "delivery_center";
    var $dlyCorp_tab = 'show';


    public function _views() {
        if($this->dlyCorp_tab == 'hidden'){
           return '';
        }

        $status = kernel::single('base_component_request')->get_get('status');
        $sku = kernel::single('base_component_request')->get_get('sku');

        $query = array(
            'app'    => 'ome',
            'ctl'    => 'admin_receipts_outer',
            'act'    => 'index',
            'status' => $status,
            'sku'    => $sku,
        );

        # 所有第三方仓
        $outerBranch = array();
        $oBranch = $this->app->model('branch');
        $tmpBranchList = $oBranch->getList('branch_id',array('owner'=>'2'));
        foreach ($tmpBranchList as $key => $value) {
            $outerBranch[] = $value['branch_id'];
        }
        unset($tmpBranchList);

        $sub_menu = $this->getView($status);
        $i = 0;
        $mdl_order = $this->app->model('delivery');
        foreach ($sub_menu as $k => $v) {
            //非管理员取管辖仓与自建仓的交集
            $v['filter']['ext_branch_id'] = $v['filter']['ext_branch_id'] ? array_intersect($v['filter']['ext_branch_id'], $outerBranch) : $outerBranch;
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $mdl_order->count($v['filter']);
            $query['view'] = $i++;
            $query['logi_id'] = urlencode($v['filter']['logi_id']);
            $sub_menu[$k]['href'] = 'index.php?' . http_build_query($query);
        }
        return $sub_menu;
    }

    /**
     * 第三方发货单列表
     *
     * @author chenping<chenping@shopex.cn> 
     **/
    public function index()
    {
        if (isset($_POST['delivery_bn']) && $_POST['delivery_bn']) {
            $deliveryObj = &$this->app->model('delivery');
            $rows = $deliveryObj->getParentIdBybn($_POST['delivery_bn']);
            if ($rows) {
                foreach ($rows as $val) {
                    $deliveryId[] = $val['parent_id'];
                }
                $filter['extend_delivery_id'] = $deliveryId;
            }
        }
        $filter['type'] = 'normal';

        //分析status的filter条件
        $tmp_filter = $this->analyseStatus($_GET['status']);
        $filter = array_merge($filter, $tmp_filter);

        $oBranch = &app::get('ome')->model('branch');
        # 所有第三方仓
        $outerBranch = array();
        $tmpBranchList = $oBranch->getList('branch_id',array('owner'=>'2'));
        foreach ($tmpBranchList as $key => $value) {
            $outerBranch[] = $value['branch_id'];
        }
        unset($tmpBranchList);
        # 发货配置
        $deliCfgLib = kernel::single('ome_delivery_cfg');
        # 获取操作员管辖仓库
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids) {
                $filter['ext_branch_id'] = $_POST['branch_id'] ? $_POST['branch_id'] : $branch_ids;
                $_brach_id = array_intersect($filter['ext_branch_id'], $outerBranch);
                if(!empty($_brach_id)){
                    $filter['ext_branch_id'] = $_brach_id; //取管辖仓与第三方仓的交集
                }else{
                    $filter['ext_branch_id'] = 'false';
                }
            } else {
                $filter['ext_branch_id'] = 'false';
            }
        } else {
            $filter['ext_branch_id'] = $outerBranch;
        }
        #第三方已发货
        if($_GET['view'] == 1){
            $filter['status'] = array('succ');
        }
         #第三方未发货
        if($_GET['view'] == 2){
            $filter['status'] = array('ready','progress');
        }
        # 发货配置
        $deliCfgLib = kernel::single('ome_delivery_cfg');
        $params = array(
            'title'                  => '第三方发货单列表',
            'actions'                => array(
             'stock' => array(
                    'label' => '打印备货单',
                    'submit' => 'index.php?app=ome&ctl=admin_receipts_print&act=toPrintStock' . $attach,
                    'target' => "_blank",
                ),
                'delie' => array(
                    'label' => '打印发货单',
                    //'submit' => 'index.php?app=ome&ctl=admin_receipts_print&act=toPrintMergeNew' . $attach,
                    'submit' => 'index.php?app=ome&ctl=admin_receipts_print&act=toPrintMerge' . $attach,
                    'target' => '_blank',
                ),
                'merge' => array(
                    'label' => '联合打印',
                    'submit' => 'index.php?app=ome&ctl=admin_receipts_print&act=toMergePrint' . $attach,
                    'target' => '_blank',
                ),
                'process'=>array(
                    'label'  => '导出未发货',
                    'submit' => 'index.php?app=ome&ctl=admin_receipts_outer&act=index&action=export',
                    'target' => 'dialog::{title:\'导出未发货\',width:400,height:200}',
                ),
                'import'=>array(
                    'label'  => '快速发货导入',
                    'href'   => 'index.php?app=ome&ctl=admin_receipts_outer&act=index&action=import',
                    'target' => 'dialog::{title:\'快速发货导入\',width:400,height:200}',
                ),
                'export'=>array(
                    'label'  => '导出',
                    'submit' => 'index.php?app=omedlyexport&ctl=ome_delivery&act=index&action=export',
                    'target' => 'dialog::{width:400,height:170,title:\'导出\'}',
                ),
            ),
            'base_filter'            => $filter,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_recycle'    => false,
            'use_buildin_export'     => false,
            'use_buildin_import'     => false,
            'use_buildin_filter'     => true,
            'use_view_tab'           => true,
            'object_method'          => array('count' => 'count_logi_no', 'getlist' => 'getlist_logi_no'),
        );
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
        $is_export = kernel::single('desktop_user')->has_permission('process_receipts_print_export');
        if(!$is_export){
            unset($params['actions']['export']);
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

        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            $panel->setId('delivery_finder_top');
            $panel->setTmpl('admin/finder/finder_panel_filter.html');
            $panel->show('ome_mdl_delivery_outerlogi', $params);
        }
        #已发货，只留下导出按钮
        if($_GET['view'] == '1'){
            unset($params['actions']['import'],$params['actions']['process']);
        }

        $this->finder('ome_mdl_delivery_outerlogi',$params);
    }
}