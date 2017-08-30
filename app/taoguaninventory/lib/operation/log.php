<?php
class taoguaninventory_operation_log{
    	function get_operations(){
	       $operations = array(
             'inventory_modify' => array('name'=> '盘点单修改','type' => 'inventory@taoguaninventory'),
        );
        return array('taoguaninventory'=>$operations);
     }
}
?>