<?php
class logisticsmanager_operation_log{
	    
    /**
     * 定义当前APP下的操作日志的所有操作名称列表
     * type键值由表名@APP名称组成
     * @access public
     * @return Array
     */
    function get_operations(){
        $operations = array(
           'waybill_import' => array('name'=> '导入电子面单号','type' => 'channel@logisticsmanager'),
          
        );
        
        return array('logisticsmanager'=>$operations);
    }
}
?>