<?php

class ome_mdl_order_items extends dbeav_model{
    function getItemDetail($bn,$order_id){
         $aGoods = $this->db->select('SELECT i.*,nums-sendnum AS send,sendnum AS resend,p.store FROM sdb_ome_order_items i
            LEFT JOIN sdb_ome_products p ON i.product_id = p.product_id
            WHERE order_id = \''.$order_id.'\' AND i.bn = \''.$bn.'\'');
        return $aGoods[0];
    }

    public function getOrderIdByPbn($product_bn){
        $sql = 'SELECT count(1) as _c FROM sdb_ome_order_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >= 10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT order_id FROM sdb_ome_order_items WHERE bn like \''.addslashes($product_bn).'%\'';
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }

        $sql = 'SELECT order_id FROM sdb_ome_order_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }
    
    public function getOrderIdByFilterLetterType($lettering_type){
        $sql = "SELECT count(1) as _c FROM sdb_ome_order_items WHERE lettering_type = '$lettering_type'";
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >= 10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = "SELECT order_id FROM sdb_ome_order_items WHERE lettering_type = '$lettering_type'";
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }

        $sql = "SELECT order_id FROM sdb_ome_order_items WHERE  lettering_type = '$lettering_type'";
        $rows = $this->db->select($sql);
        return $rows;
    }

    public function getOrderIdByPbarcode($product_barcode){
        $sql = 'SELECT count(1) as _c FROM sdb_ome_order_items as I LEFT JOIN '.
            'sdb_ome_products as P ON I.product_id=P.product_id WHERE P.barcode like \''.addslashes($product_barcode).'%\'';
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >= 10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT order_id FROM sdb_ome_order_items as I LEFT JOIN '.
            'sdb_ome_products as P ON I.product_id=P.product_id WHERE P.barcode like \''.addslashes($product_barcode).'%\'';
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }

        $sql = 'SELECT order_id FROM sdb_ome_order_items as I LEFT JOIN '.
            'sdb_ome_products as P ON I.product_id=P.product_id WHERE P.barcode like \''.addslashes($product_barcode).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }
    /**
     * 通过product_id获得符合条件的冻结库存值
     * @param unknown_type $product_id
     */
    public function getStoreByProductId($product_id,$offset='0',$limit='10'){
        $sql = "SELECT o.order_bn,o.pay_status,o.ship_status,o.createtime,o.order_limit_time,o.paytime,o.shop_id,oi.sendnum,oi.nums
                FROM sdb_ome_order_items as oi,sdb_ome_orders o
                where o.order_id = oi.order_id
                and o.status='active'
                and oi.product_id = $product_id
                and oi.`delete`='false'
                and o.ship_status in ('0','2')
                and oi.sendnum != oi.nums
                LIMIT {$offset},{$limit}
                ";
        $rows = $this->db->select($sql);
        return $rows;
    }
    /**
     * 获取符合条件的冻结库存的 总数
     */
    public function count_order_id($product_id){
    	$sql = "SELECT count(*) AS count
                FROM sdb_ome_order_items as oi,sdb_ome_orders o
                where o.order_id = oi.order_id
                and o.status='active'
                and oi.product_id = $product_id
                and oi.`delete`='false'
                and o.ship_status in ('0','2')
                and oi.sendnum != oi.nums";
    	$rows = $this->db->selectrow($sql);
        return $rows['count'];
    }

    public function getFailOrderByBn($bn=array()){
        $sql = 'SELECT I.order_id FROM sdb_ome_order_items as I LEFT JOIN '.
            'sdb_ome_orders as O ON I.order_id=O.order_id WHERE O.is_fail=\'true\' and O.edit_status=\'true\' and O.archive=\'1\' and O.status=\'active\' and I.bn in (\''.implode('\',\'',$bn).'\') GROUP BY order_id';
        $rows = $this->db->select($sql);
        return $rows;
    }

    /**
    *
    */
    public function getOrderIdByPkgbn($product_bn){
        $sql = 'SELECT count(1) as _c FROM sdb_ome_order_objects WHERE bn like \''.addslashes($product_bn).'%\'';
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >= 10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT order_id FROM sdb_ome_order_objects WHERE bn like \''.addslashes($product_bn).'%\'';
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }

        $sql = 'SELECT order_id FROM sdb_ome_order_objects WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }


    /**
     * 查询货号相关订单
     * @param array filter 
     * @return array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getOrderIdByFilterbn($filter)
    {
        $orderObj = &app::get('ome')->model('orders');
        $searchfilter = $filter;
        $product_bn = $searchfilter['product_bn'];
        unset($searchfilter['product_bn']);
        $order_filter = $orderObj->_filter($searchfilter);
        $order_filter = str_replace('`sdb_ome_orders`','o',$order_filter);
        $sql = 'SELECT count(1) as _c FROM sdb_ome_order_items as i LEFT JOIN sdb_ome_orders as o ON i.order_id=o.order_id WHERE i.bn like \''.addslashes($product_bn).'%\' AND'.$order_filter;
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >= 10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT i.order_id FROM sdb_ome_order_items as i LEFT JOIN sdb_ome_orders as o ON i.order_id=o.order_id WHERE i.bn like \''.addslashes($product_bn).'%\' AND '.$order_filter;
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }

        $sql = 'SELECT i.order_id FROM sdb_ome_order_items as i LEFT JOIN  sdb_ome_orders as o ON i.order_id=o.order_id WHERE i.bn like \''.addslashes($product_bn).'%\' AND '.$order_filter;
        $rows = $this->db->select($sql);
        return $rows;
    }

    
}