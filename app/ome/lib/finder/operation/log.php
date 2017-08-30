<?php

class ome_finder_operation_log {
    var $addon_cols = 'operation,op_id,op_name,operate_time,memo,ip,obj_id';
    
    public $column_opname = '操作者';
    public $column_opname_order = 50;
    public $column_opname_order_field = "op_name";
    public $column_opname_width = 120;
    public function column_opname($row) {
        $op_name = $row[$this->col_prefix . 'op_name'];
        return $op_name;
    }

    public $column_operatetime = '操作时间';
    public $column_operatetime_order = 60;
    public $column_operatetime_order_field = "operate_time";
    public $column_operatetime_width = 140;
    public function column_operatetime($row) {
        $operate_time = $row[$this->col_prefix . 'operate_time'];
        return date('Y-m-d H:i:s', $operate_time);
    }

    public $column_memo = '操作对象';
    public $column_memo_order = 60;
    public $column_memo_order_field = "memo";
    public $column_memo_width = 320;
    public function column_memo($row) {
        $memo = $row[$this->col_prefix . 'memo'];
        $text = explode('{|||}', $memo);
        return $text[0];
    }
    public $column_obj_id = '操作ID';
    public $column_obj_id_order = 70;
    public $column_obj_id_order_field = "obj_id";
    public $column_obj_id_width = 120;
    public function column_obj_id($row) {
        $id = $row[$this->col_prefix . 'obj_id'];
        return $id;
    }

    public $column_ip = '操作IP';
    public $column_ip_order = 70;
    public $column_ip_order_field = "ip";
    public $column_ip_width = 120;
    public function column_ip($row) {
        $ip = $row[$this->col_prefix . 'ip'];
        return $ip;
    }
}