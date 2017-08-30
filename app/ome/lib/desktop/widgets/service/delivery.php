<?php
class ome_desktop_widgets_service_delivery{

    public function get_menugroup(){

        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $deliveryObj = &app::get('wms')->model('delivery');
        $data['label'] = '发货单';
        $data['type'] = 'delivery';
        $btncombi = $deliCfgLib->btnCombi();
        $basicFilter = array('status'=>array('0','3'),'type'=>'normal');
        #$unprintFilter = array('type'=>'normal','pause'=>'FALSE' ,'verify'=>'FALSE' ,'process'=>'FALSE','deli_cfg|notin'=>array('single','multi'));
        //$unprintFilter = array('type'=>'normal','pause'=>'FALSE' ,'verify'=>'FALSE' ,'process'=>'FALSE');
        switch ($btncombi) {
            case '1_1':
                $unprintFilter['todo'] = '1';
                break;
            case '1_0':
                $unprintFilter['todo'] = '2';
                break;
            case '0_1':
                $unprintFilter['todo'] = '3';
                break;
            case '0_0':
                $unprintFilter['todo'] = '4';
                break;
        }
        #获取自建仓库
        $ownerBranch = array();
        $ownerBranch = kernel::single('ome_branch_type')->getOwnBranchIds();

        #非管理员取管辖仓
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $oBranch = &app::get('ome')->model('branch');
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids) {
                $tmp_filter['ext_branch_id'] = $branch_ids;
            } else {
                $tmp_filter['ext_branch_id'] = false;
            }
        }
        #非管理员取管辖仓与自建仓的交集
        $tmp_filter['ext_branch_id'] = $tmp_filter['ext_branch_id']?array_intersect($tmp_filter['ext_branch_id'], $ownerBranch) : $ownerBranch;
        $basicFilter['ext_branch_id'] = $tmp_filter['ext_branch_id'];
        $data['value'][0] = array(
            'count' => $deliveryObj->count(array_merge($unprintFilter,$basicFilter)),
            'link' => 'index.php?app=wms&ctl=admin_receipts_print&act=index&status=0&sku=',
            'label' => '待打印',
        );
        $uncheckFilter = array('expre_status' => 'TRUE','pause' => 'FALSE','verify' => 'FALSE','process' => 'FALSE',);
        $btncombi_single = $deliCfgLib->btnCombi('single');
        $btncombi_multi = $deliCfgLib->btnCombi('multi');
        $btncombi_basic = $deliCfgLib->btnCombi();
        $uncheckFilter['print_finish'] = array(
            ''=> $btncombi_basic,
            'single' => $btncombi_single,
            'multi' => $btncombi_multi,
        );
        $data['value'][1] = array(
            'count' => $deliveryObj->count(array_merge($uncheckFilter,$basicFilter)),
            'link' => 'index.php?app=wms&ctl=admin_receipts_print&act=index&status=1',
            'label' => '待校验',
        );
        $unprocessFilter = array('process'=>'false','pause'=>'false','type' => 'normal');
        $data['value'][2] = array(
            'count' => $deliveryObj->count(array_merge($unprocessFilter,$basicFilter)),
            'link' => 'index.php?app=wms&ctl=admin_receipts_print&act=index&status=4',
            'label' => '待发货',
        );
        return $data;
    }
}