<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class ome_mdl_print_queue extends dbeav_model {

    function findQueue($ident, $field='*') {
        $queue = $this->db->selectRow('SELECT ' . $field . ' FROM ' . $this->table_name(1) . ' WHERE ident="' . $ident . '"');

        if ($queue) {
            return $queue;
        } else {
            return false;
        }
    }
    function findQueueDeliveryId($ident = null){
        $row = $this->db->select('SELECT delivery_id FROM ' . app::get('ome')->model('print_queue_items')->table_name(1) . ' WHERE ident="' . $ident . '"');
        if(empty($row)){
            return false;
        }
        foreach($row as $val){
            $arr_delivery_id[]= $val['delivery_id'];
        }
        $str_delivery_id = implode(',',$arr_delivery_id);
        return $str_delivery_id;
    }

    function findIdent($delivery_id) {
        $row = $this->db->selectRow('SELECT ident FROM ' . app::get('ome')->model('print_queue_items')->table_name(1) . ' WHERE delivery_id="' . $delivery_id . '"');

        if ($row) {
            return $row['ident'];
        } else {
            return false;
        }
    }

    function findQueueItems($ident) {
        $rows = $this->db->select('SELECT * FROM ' . app::get('ome')->model('print_queue_items')->table_name(1) . ' WHERE ident="' . $ident . '"');

        if ($rows) {
            return $rows;
        } else {
            return false;
        }
    }

    function findQueueItem($ident, $ident_dly) {
        $row = $this->db->selectRow('SELECT * FROM ' . app::get('ome')->model('print_queue_items')->table_name(1) . ' WHERE ident="' . $ident . '" AND ident_dly="' . $ident_dly . '"');

        if ($row) {
            return $row;
        } else {
            return false;
        }
    }

    function findFullIdent($delivery_id) {
        $row = $this->db->selectRow('SELECT ident,ident_dly FROM ' . app::get('ome')->model('print_queue_items')->table_name(1) . ' WHERE delivery_id="' . $delivery_id . '"');

        if ($row) {
            return $row['ident'] . '_' . $row['ident_dly'];
        } else {
            return false;
        }
    }
    function findIdendlyById($delivery_id = null){
        $sql = "SELECT ident_dly FROM sdb_ome_print_queue_items WHERE delivery_id=".$delivery_id;
        $row = $this->db->selectRow($sql);
        if($row){
            return $row;
        }else{
            return false;
        }
    }

    /**
     * 
     * 根据发货单id得到打印过的批次号
     * @param unknown_type $delivery_ids
     */
    function getVaildQueueItems($delivery_ids) {
        $rows = $this->db->select('select q_item.* from sdb_ome_print_queue_items as q_item left join sdb_wms_delivery as d  on q_item.delivery_id = d.delivery_id where q_item.delivery_id in(' . implode(',', $delivery_ids) . ') and (d.print_status>0)');

        return $rows;
    }

    /**
     * 
     * 根据发crc得到打印过的批次号
     * @param unknown_type $delivery_ids
     */
    function getVaildQueue($crc) {
        $rows = $this->db->select('select q.* from sdb_ome_print_queue as q left join sdb_ome_print_queue_items as q_item on q.ident = q_item.ident left join sdb_wms_delivery as d  on q_item.delivery_id = d.delivery_id where q.crc = ' . $crc . ' and (d.print_status>0)');

        return $rows;
    }

    function isVaildQueue($md5) {
        $row = $this->db->selectRow('select q.* from sdb_ome_print_queue as q left join sdb_ome_print_queue_items as q_item on q.ident = q_item.ident left join sdb_wms_delivery as d  on q_item.delivery_id = d.delivery_id where q.md5 = "' . $md5 . '" and (d.print_status>0)');

        if ($row) {
            return true;
        } else {
            return false;
        }
    }

    function deletePrintQueueByIdent($ident) {
        $this->db->exec('delete from sdb_ome_print_queue where ident="' . $ident . '"');
        $this->db->exec('delete from sdb_ome_print_queue_items where ident="' . $ident . '"');

        return true;
    }

    function emptyPrintQueueByIds($ids) {
        $rows = $this->db->select('select ident from sdb_ome_print_queue_items where delivery_id in(' . implode(',', $ids) . ')');
        $deleteIdent = array();
        foreach ($rows as $row) {
            $deleteIdent[] = $row['ident'];
        }

        $this->db->exec('delete from sdb_ome_print_queue where ident in ("' . implode('","', $deleteIdent) . '")');
        $this->db->exec('delete from sdb_ome_print_queue_items where ident in ("' . implode('","', $deleteIdent) . '")');

        return true;
    }
    
    function emptyPrintQueueFromIds($ids) {
        
        return $this->db->select('delete from sdb_ome_print_queue_items where delivery_id in(' . implode(',', $ids) . ')');
    }

    function getExistsIdentsInIds($ids) {
        
        if (empty($ids))
            return array();
        //$row = $this->db->get('select q.* from sdb_ome_print_queue as q left join sdb_ome_print_queue_items as q_item on q.ident = q_item.ident left join sdb_ome_delivery as d  on q_item.delivery_id = d.delivery_id where q.md5 = "' . $md5 . '" and (d.stock_status="true" or d.deliv_status="true" or d.expre_status="true")');
        $items = $this->db->select('select q_item.* from sdb_ome_print_queue_items as q_item left join sdb_wms_delivery as d  on q_item.delivery_id = d.delivery_id where q_item.delivery_id in (' . join(',',$ids) . ') and (d.print_status>0)');
    
        $result = array('items' => array(), 'idents' => array());
        foreach($items as $item) {
            
            $result['items'][$item['delivery_id']] = sprintf('%s_%s',$item['ident'], $item['ident_dly']);
            if (!in_array($item['ident'], $result['idents'])) {
                $result['idents'][] = $item['ident'];
            }
        }
        
        return $result; 
    }
}