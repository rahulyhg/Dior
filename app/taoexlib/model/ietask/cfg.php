<?php
class taoexlib_mdl_ietask_cfg extends dbeav_model{

   function getCfg($ietask_cfg_id,$field = array('*')){
    	$row = $this->db->selectrow('select '.implode(',',$field).' from sdb_taoexlib_ietask_cfg where ietask_cfg_id ='.$ietask_cfg_id);
		
    	return $row;    	
   }
   
   function getCfgList($ietask_cfg_id,$field = array('*')){
    	$rows = $this->db->select('select '.implode(',',$field).' from sdb_taoexlib_ietask_cfg');
		
    	return $rows;    	
   }
}
?>