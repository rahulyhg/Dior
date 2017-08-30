<?php
class logisticsaccounts_crontab_getscript{
    function get(){
        $base_path = 'lib/crontab';
        $script = array(
            $base_path."/delivery.php",
            );
        return $script;
    }
}
?>