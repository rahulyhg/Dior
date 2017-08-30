<?php
class console_ctl_admin_purchaselist extends desktop_controller{
    var $name = "采购管理";
    var $workground = "console_center";

    function index(){
        $params = array(
                        'title'=>'采购订单列表',

                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'finder_cols'=>'column_edit,supplier_id,emergency,name,arrive_time,operator,deposit,purchase_time,check_status,check_time,po_status,po_type,statement,eo_status,branch_id,check_operator',
                        'orderBy' => 'emergency asc,purchase_time desc'
                    );

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $params['base_filter']['branch_id'] = $branch_ids;
            }else{
                $params['base_filter']['branch_id'] = 'false';
            }
        }


        $this->finder('purchase_mdl_po', $params);
    }



}