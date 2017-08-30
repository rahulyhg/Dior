<?php

class taoexlib_mdl_queue extends dbeav_model {
    var $defaultOrder = array('queue_id DESC');
    
    function modifier_spend_time($row){
    	if(is_null($row))return '-';
    	$row = $row ? $row : 0;
        return  $row .'秒';
    }
    

}