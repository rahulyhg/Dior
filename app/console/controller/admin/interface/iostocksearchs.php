<?php
class console_ctl_admin_interface_iostocksearchs extends desktop_controller{
    var $name = "库存异动查询";
    var $workground = "interface_iostocksearchs_center";


    public function index()
    {
        $iostocksearchs = kernel::single('console_finder_interface_iostocksearchs');
        //$user_branch = kernel::single("ome_userbranch");
        //$branch_id= $user_branch->get_user_branch_id();
        if($branch_id)$base_filter['branch_id'] = $branch_id;
        $iostocksearchs->set_extra_view(array('eccommon' => 'analysis/extra_view.html'));
        $_POST['store_name'] =  $branch_id;
        $iostocksearchs->set_params($_POST)->display();
    }
}
?>
