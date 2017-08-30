<?php
class ome_mdl_tbgift_goods extends dbeav_model{
    var $has_many = array(
        'product' => 'tbgift_product:replace'
    );

    function getGiftById($id){
        return $this->db->select("SELECT * FROM sdb_ome_tbgift_goods where goods_id = '".$id."'");
    }

    function checkGiftByBn($bn){
        return $this->db->select("SELECT goods_id FROM sdb_ome_tbgift_goods where gift_bn = '".addslashes($bn)."'");
    }

    function getGiftByBn($bn){
        return $this->db->selectrow("SELECT goods_id FROM sdb_ome_tbgift_goods where gift_bn = '".addslashes($bn)."'");
    }
}
?>