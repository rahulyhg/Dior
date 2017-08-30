<?php

class omepkg_mdl_pkg_product extends dbeav_model{
    function getAllProduct($id){
        return $this->db->select("SELECT * FROM sdb_omepkg_pkg_product where goods_id = '".$id."'"); 
    }
    
    function getproduct($id){
        return $this->db->select("SELECT * FROM sdb_omepkg_pkg_product where goods_id = '".$id."'"); 
    }
}
?>