<?php
interface logisticsaccounts_interface_estimate {
    public function delivery_list($now_time,$last_time,$offset,$limit);
    public function get_total($now_time,$last_time);

    public function branch_list();
    public function get_branch($branch_id);
    public function shop_list();
    public function get_shop($shop_id);
    public function logi_list();
    public function get_logi($logi_id);
    public function get_loginame($logi_name);


}

?>