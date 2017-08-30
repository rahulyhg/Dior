<?php
class purchase_ctl_admin_appropriation extends desktop_controller{
    var $name = "调拔单管理";
    var $workground = "storage_center";
    function index(){
        
        $params = array(
		   'actions' => array(
                array(
                    'label'=>'导出模板',
                    'href'=>'index.php?app=purchase&ctl=admin_appropriation&act=exportTemplate',
                    'target'=>'_bank'
                )
            ),
                        'title'=>'调拔单',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>true,
                        'use_buildin_filter'=>true,
                    );
        /*
         * 获取操作员管辖仓库
         */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $oApp = &$this->app->model('appropriation_items');
                $app_list = $oApp->getList('appropriation_id', array('to_branch_id'=>$branch_ids), 0,-1);
                if ($app_list)
                foreach ($app_list as $p){
                    $applist[] = $p['appropriation_id'];
                }
                if ($applist){
                    $applist = array_unique($applist);
                    $params['base_filter']['appropriation_id'] = $applist;
                }else{
                    $params['base_filter']['appropriation_id'] = 'false';
                }
            }else{
                $params['base_filter']['appropriation_id'] = 'false';
            }
        }
                    
        $this->finder('purchase_mdl_appropriation', $params);
    }
	
    function exportTemplate(){
        header("Content-Type: text/csv");  
        header("Content-Disposition: attachment; filename=allocation".date('YmdHis').".csv");  
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');  
        header('Expires:0');
        header('Pragma:public');
        $appropriationObj = &$this->app->model('appropriation');
        $title1 = $appropriationObj->exportTemplate('appropriation');
        echo '"'.implode('","',$title1).'"';
    }
}
?>