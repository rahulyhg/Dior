<?php
class ome_mdl_payment_shop extends dbeav_model{
    public function getShopByPayBn($pay_bn){
        $sql = 'SELECT pay_bn,name FROM sdb_ome_payment_shop AS P 
            LEFT JOIN sdb_ome_shop AS S ON P.shop_id=S.shop_id WHERE pay_bn=\''.addslashes($pay_bn).'\'';
        $rows = $this->db->select($sql);
        return $rows;
    }
}