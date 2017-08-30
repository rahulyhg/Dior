<?php
class iostock_finder_purchase{
   
    var $column_branchbn = '仓库编号';
    function column_branchbn($row){
        $branch_bn = "select branch_bn from sdb_ome_branch where branch_id=".$row['branch_id'];
        $bn = kernel::database()->select($branch_bn);
        return $bn[0]['branch_bn'];
    }
    var $column_productname = '商品名称';
    function column_productname($row){
        $sql = "select name from sdb_ome_products where bn='".$row['bn']."'";
        $name = kernel::database()->select($sql);
        return $name[0]['name'];
    }
    var $addon_cols = 'supplier_name';
    var $column_suppliername = '供应商名称';
    function column_suppliername($row){
        return $row[$this->col_prefix . 'supplier_name'];
    }

}
