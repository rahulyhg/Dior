<?php
class purchase_ctl_admin_po extends desktop_controller{
    var $name = "采购管理";
    var $workground = "purchase_manager";

    function index(){
        $params = array(
                        'title'=>'采购单',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_filter'=>true,
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
    
    function add(){
        $this->display("admin/purchase/purchase_add.html");
    }
    
    function find(){
        print_r($_POST);
    }
    function need(){
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                //检查仓库号是否属于操作员管辖仓库
                $_key = array_search($_POST['branch_id'],$branch_ids);
                if($_key===FALSE){
                    $params['base_filter']['branch_id'] = 'false';
                }else{
                    //使用前端提交的仓库号
                    $params['base_filter']['branch_id'] = $_POST['branch_id'];
                }
            }else{
                $params['base_filter']['branch_id'] = 'false';
            }
        }
        $this->finder('purchase_mdl_purchase_need',$params);
    }
}
?>
