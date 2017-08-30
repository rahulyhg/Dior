<?php
/**
 * 防止并发导致数据插入失败
 * @copyright Copyright (c) 2011, shopex. inc
 * @author sy
 * 
 */

class ome_mdl_concurrent extends dbeav_model{
    
   function is_pass($id,$type){
       if(@$this->db->exec('INSERT INTO sdb_ome_concurrent(`id`,`type`,`current_time`)VALUES("'.$id.'","'.$type.'","'.time().'")')){
           return true;
       }else{
           return false;
       }
   }
    
}