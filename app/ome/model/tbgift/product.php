<?php

class ome_mdl_tbgift_product extends dbeav_model{
    function getAllProduct($id){
        return $this->db->select("SELECT * FROM sdb_ome_tbgift_product where goods_id = '".$id."'");
    }

    function getproduct($id){
        return $this->db->select("SELECT * FROM sdb_ome_tbgift_product where goods_id = '".$id."'");
    }
}
?>