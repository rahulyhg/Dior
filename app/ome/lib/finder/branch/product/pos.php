<?php
class ome_finder_branch_product_pos {

    var $addon_cols = "pos_id";
  /*
    var $column_edit = "操作";
    var $column_edit_width = "100";
    function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_branch_product_pos&act=delpos&p[0]='.$row['pos_id'].'&p[1]='.$row['product_id'].'&finder_id='.$finder_id.'">解绑</a>';
    }
    */
    var $column_store_position = "货位";
    var $column_store_position_width = "150";
    function column_store_position($row){
       return $row['store_position'];
    }

    var $column_product_name = "货品名称";
    var $column_product_name_width = "300";
    function column_product_name($row){
        if ($row['sku_property']) $str = "(".$row['sku_property'].")";
        return $row['name'].$str;
    }
    var $column_productBn = "货号";
    var $column_productBn_width = "150";
    function column_productBn($row){
        $obj_products = &app::get('ome')->model('products');
        $rs = $obj_products->dump($row['product_id'],'bn');
        return $rs['bn'];
    }

    var $column_product_bn = "条形码";
    var $column_product_bn_width = "150";
    function column_product_bn($row){
        return $row['barcode'];
    }

    var $column_branch_name = "仓库";
    var $column_branch_name_width = "150";
    function column_branch_name($row){
      $brObj = &app::get('ome')->model('branch');
      $aRow = $brObj->dump($row['branch_id'], 'name');
        return $aRow['name'];
    }

    var $column_spec_info = "规格";
    function column_spec_info($row){
        return $row['spec_info'];
    }
}