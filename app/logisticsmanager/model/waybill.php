<?php
class logisticsmanager_mdl_waybill extends dbeav_model {
    
    function modifier_status($row){
            $status = '';
            if ($row=='0') {
                $status = '可用';
            }else if($row=='1'){
                $status = '已用';
            }else if($row=='2'){
                $status = '作废';
            }
            return $status;
       
    }
}

?>