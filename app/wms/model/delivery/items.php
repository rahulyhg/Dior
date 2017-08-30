<?php
class wms_mdl_delivery_items extends dbeav_model{
    public function getDeliveryIdByPbn($product_bn){
        $sql = 'SELECT count(1) as _c FROM sdb_wms_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >=10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT delivery_id FROM sdb_wms_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }
        $sql = 'SELECT delivery_id FROM sdb_wms_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }
}
