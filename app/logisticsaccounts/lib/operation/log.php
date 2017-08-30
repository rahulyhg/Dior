<?php
class logisticsaccounts_operation_log{
    	function get_operations(){
	       $operations = array(
             'statements_modify' => array('name'=> '结算单修改','type' => 'statements@logisticsaccounts'),
             'statements_create' => array('name'=> '结算单创建','type' => 'statements@logisticsaccounts'),
        );
        return array('taoguan'=>$operations);
     }
}
?>