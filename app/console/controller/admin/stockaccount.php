<?php
class console_ctl_admin_stockaccount extends desktop_controller{
    var $name = "库存对账查询";
    var $workground = "console_center";
    public function index(){
        $account = kernel::single('console_finder_stockaccount');
        $account->set_extra_view(array('console' => 'admin/analysis/account_items_time_header.html'));
        if(empty($_POST['time_from'])){
            $_POST['time_from'] = $_POST['time_to'] = date('Y-m-d');
        }

        $account->set_params($_POST)->display();
    }
}
?>
