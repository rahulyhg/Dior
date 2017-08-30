<?php
class ome_finder_branch_pos {

    function __construct(){

        if ($_GET['act']=='view'){
            unset($this->column_edit);
        }else {
            unset($this->column_product_name);
            unset($this->column_product_bn);
        }
    }

    var $addon_cols = "pos_id";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_branch_pos&act=edit_pos&p[0]='.$row['pos_id'].'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
    }

    var $column_product_name = "货品名称";
    var $column_product_name_width = "300";
    var $column_product_name_order = COLUMN_IN_TAIL;//排在列尾
    function column_product_name($row){
        if ($row['sku_property']) $str = "(".$row['sku_property'].")";
        return $row['name'].$str;
    }

    var $column_product_bn = "货号";
    var $column_product_bn_width = "150";
    var $column_product_bn_order = COLUMN_IN_TAIL;//排在列尾
    function column_product_bn($row){
        return $row['bn'];
    }
}
?>