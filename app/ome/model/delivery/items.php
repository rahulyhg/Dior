<?php

class ome_mdl_delivery_items extends dbeav_model{

    /**
     * 创建大发货单对应的发货单详情
     *
     * @param bigint $parent_id
     * @param array() $items
     * 
     * @return boolean
     */
    function insertParentItemByItems($parent_id, $items, $branch_id){
        if (!is_array($items)) return false;
        $ids = implode(',', $items);
        $sql = "SELECT *,SUM(number) AS 'total' FROM sdb_ome_delivery_items WHERE delivery_id in ($ids) GROUP BY product_id";
        //echo $sql;
        $rows = $this->db->select($sql);
        if ($rows){
            //$dly_itemPosObj = &$this->app->model('dly_items_pos');
            foreach ($rows as $item){
                $new_item['delivery_id']       = $parent_id;
                $new_item['shop_product_id']   = $item['shop_product_id'];
                $new_item['product_id']        = $item['product_id'];
                $new_item['bn']                = $item['bn'];
                $new_item['product_name']      = $item['product_name'];
                $new_item['number']            = $item['total'];
                $new_item['verify_num']        = 0;
                
                $this->save($new_item);
                
                /*$pos = $this->db->selectrow("SELECT bp.pos_id FROM sdb_ome_branch_pos AS bp 
                                                      LEFT JOIN sdb_ome_branch_product_pos AS bpp 
                                                            ON(bpp.pos_id=bp.pos_id) 
                                                      WHERE bp.branch_id=".intval($branch_id)." 
                                                            AND bpp.product_id=".$item['product_id']." 
                                                            AND default_pos='true'");
                if (empty($pos['pos_id'])) {
                    trigger_error($item['product_name'].":无默认货位", E_USER_ERROR);
                    return false;
                }
                $pos_id = $pos['pos_id'];
                $items_pos = array('item_id'=>$new_item['item_id'],'pos_id'=>$pos_id,'num'=>$item['total']);
                $dly_itemPosObj->save($items_pos);*/
                
                $new_item=NULL;
            }
            return true;
        }
        return false;
    }
        
    
    /**
     * 校验完成，对发货单对应详情进行更新
     *
     * @param bigint $dly_id
     * 
     * @return boolean
     */
    function verifyItemsByDeliveryId($dly_id){
        $items = $this->getList('item_id,number,verify,verify_num', array('delivery_id'=>$dly_id), 0, -1);
        foreach ($items as $item){
            $data['verify'] = 'true';
            $data['verify_num'] = $item['number'];
            
            if ($this->update($data, array('item_id'=>$item['item_id'])) == false) return false;
            $data = null;
        }
        return true;
    }
    
    /**
     * 重置发货单详情
     *
     * @param bigint $dly_id
     * 
     * @return boolean
     */
    function resumeItemsByDeliveryId($dly_id){
        $items = $this->getList('item_id,number,verify,verify_num', array('delivery_id'=>$dly_id), 0, -1);
        foreach ($items as $item){
            if ($item['verify_num'] === 0 && $item['verify'] == 'false') continue;
            
            $data['verify'] = 'false';
            $data['verify_num'] = 0;
            
            $this->update($data, array('item_id'=>$item['item_id']));
            $data = null;
        }
        return true;
    }
    
    /*
     * 大单校验
     */
    function verifyItemsByDeliveryIdFromPost($dly_id){
        $items = $this->getList('item_id,number,product_id,verify,verify_num', array('delivery_id'=>$dly_id), 0, -1);
        $pObj = &app::get('ome')->model('products');
        foreach ($items as $item){
            $p = $pObj->dump($item['product_id'], 'barcode');
            $num = intval($_POST['number_'. $p['barcode']]);
            $num = $num>$item['number']? $item['number'] : $num;
            $data['verify'] = 'false';
            $data['verify_num'] = $num;
            
            if ($this->update($data, array('item_id'=>$item['item_id'])) == false) return false;
            $data = null;
            $_POST['number_'. $p['barcode']] -= $num;
        }
        return true;
    }

    public function getDeliveryIdByPbn($product_bn){
        $sql = 'SELECT count(1) as _c FROM sdb_ome_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >=10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT delivery_id FROM sdb_ome_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }
        $sql = 'SELECT delivery_id FROM sdb_ome_delivery_items WHERE bn like \''.addslashes($product_bn).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }

    
    public function getDeliveryIdByPbarcode($product_barcode){
        $sql = 'SELECT count(1) as _c FROM sdb_ome_delivery_items as I LEFT JOIN '.
            'sdb_ome_products as P ON I.product_id=P.product_id WHERE P.barcode like \''.addslashes($product_barcode).'%\'';
        $count = $this->db->selectrow($sql);
        if ($count['_c'] >= 10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT delivery_id FROM sdb_ome_delivery_items as I LEFT JOIN '.
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

        $sql = 'SELECT delivery_id FROM sdb_ome_delivery_items as I LEFT JOIN '.
            'sdb_ome_products as P ON I.product_id=P.product_id WHERE P.barcode like \''.addslashes($product_barcode).'%\'';
        $rows = $this->db->select($sql);
        return $rows;
    }
    
    public function getDeliveryIdByFilter($filter){
        $searchfilter = $filter;
        $oDelivery = &app::get('ome')->model('delivery');
        unset($searchfilter['_title_']);
        $product_bn = $filter['product_bn'];
        unset($searchfilter['product_bn']);
        $delivery_filter = $oDelivery->_filter($searchfilter);
        $delivery_filter = str_replace('`sdb_ome_delivery`','d',$delivery_filter);
        $delivery_filter = str_replace('AND expre_status','AND d.expre_status',$delivery_filter);
        $delivery_filter = str_replace('FALSE','false',$delivery_filter);
        $sql = 'SELECT count(1) as _c FROM sdb_ome_delivery_items as i LEFT JOIN sdb_ome_delivery as d on i.delivery_id=d.delivery_id WHERE i.bn like \''.addslashes($product_bn).'%\'  AND '.$delivery_filter;

        $count = $this->db->selectrow($sql);
        if ($count['_c'] >=10000) {
            $offset = 0; $limit = 9000; $list = array();
            $sql = 'SELECT d.delivery_id FROM sdb_ome_delivery_items as i LEFT JOIN sdb_ome_delivery as d on i.delivery_id=d.delivery_id WHERE i.bn like \''.addslashes($product_bn).'%\' AND '.$delivery_filter ;
            $total = floor($count['_c']/$limit);
            for ($i=$total;$i>=0;$i--) {
                $rows = $this->db->selectlimit($sql,$limit,$i*$limit);
                if ($rows) {
                    $list = array_merge_recursive($list,$rows);
                }
            }
            return $list;
        }
        $sql = 'SELECT d.delivery_id FROM sdb_ome_delivery_items as i LEFT JOIN sdb_ome_delivery as d on i.delivery_id=d.delivery_id WHERE i.bn like \''.addslashes($product_bn).'%\' AND '.$delivery_filter ;

        $rows = $this->db->select($sql);

        return $rows;
    }

  
}
?>