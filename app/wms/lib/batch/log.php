<?php
class wms_batch_log{

    function getStatus($status){
        $status_array = array(
          '0' => '等待中',
          '1' => '已处理',
          '2' => '处理中',

        );
        return $status_array[$status];
    }

    function get_List($log_type,$log_id,$status){
        $db = kernel::database();
        $log_id = implode(',',$log_id);
        $sqlstr = '';
        if($log_type){
            $sqlstr[]=' log_type=\''.$log_type.'\'';
        }
        if($log_id){
            $sqlstr[]=' log_id in ('.$log_id.')';
        }
        if($sqlstr){
            $sqlstr= 'WHERE '.implode(' AND ',$sqlstr);
        }

        $sql = 'SELECT * FROM sdb_wms_batch_log '.$sqlstr.' ORDER BY log_id DESC';

        return $db->select($sql);
    }
}
?>