<?php
class logisticsaccounts_task{
    function pre_uninstall(){
        app::get('logisticsaccounts')->setConf('logisticsaccounts.delivery.downtime','');
    }
}

?>