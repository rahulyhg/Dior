<?php

class purchase_mdl_po_items extends dbeav_model{

    public function getPoIdByPbn($product_bn){
        $sql = 'SELECT po_id FROM sdb_purchase_po_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }

    public function getPoIdByPbarcode($product_barcode){
        $sql = 'SELECT po_id FROM sdb_purchase_po_items WHERE barcode like \''.addslashes($product_barcode).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }

}