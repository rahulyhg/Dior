<?php

class iostock_finder_inventory{
    var $column_name = '商品名称';
    function column_name($row){
       $proObj = app::get('ome')->model('products');
       $name = $proObj->dump(array('bn'=>$row['bn']),'name');
      return $name['name'];
    }

    var $column_amount = '盈亏金额';
    function column_amount($row){
        $ectoolObj = app::get('eccommon')->model('currency');
        $amount = $ectoolObj->formatNumber($row['iostock_price']*$row['nums']);
        return $amount;
    }
}