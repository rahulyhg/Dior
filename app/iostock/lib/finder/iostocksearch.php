<?php

class iostock_finder_iostocksearch{
    var $column_branch_id = '仓库编号';
    function column_branch_id($row){
        $branchObj = app::get('ome')->model('branch');
        $branch = $branchObj->dump(array('branch_id'=>$row['branch_id']),'branch_bn');
      return $branch['branch_bn'];
    }

    var $column_name = '货品名称';
    function column_name($row){
       $proObj = app::get('ome')->model('products');
       $name = $proObj->dump(array('bn'=>$row['bn']),'name');
      return $name['name'];
    }

    var $addon_cols = 'supplier_name,nums';
    var $column_supplier = '供应商';
    var $column_supplier_width = 150;
    function column_supplier($row){
      return $row[$this->col_prefix . 'supplier_name'];
    }
    
    var $column_nums = "出入库数量";
    //var $column_nums_width = "80";
    function column_nums($row){
    	$iostock_instance = kernel::service('ome.iostock');
     	if($iostock_instance->getIoByType($row['type_id'])){
     		return '+'.	$row[$this->col_prefix .'nums'];
     	}else{
     		return '-'.	$row[$this->col_prefix .'nums'];
     	}
    }

}