<?php

class taoguanallocate_mdl_appropriation_items extends dbeav_model{

	public function getOrderIdByPbn($product_bn){
        $sql = 'SELECT appropriation_id FROM sdb_taoguanallocate_appropriation_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }

    public function getOrderIdByPbarcode($product_barcode){
        $sql = 'SELECT appropriation_id FROM sdb_taoguanallocate_appropriation_items as I LEFT JOIN '.
            'sdb_ome_products as P ON I.product_id=P.product_id WHERE P.barcode like \''.addslashes($product_barcode).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }
   
}