<?php
class iostock_finder_changeorderreturns{
    var $column_member_name = '会员姓名';
    function column_member_name($row){
        $salesObj = app::get('ome')->model('sales');
        $member_id = $salesObj->dump(array('iostock_bn'=>$row['iostock_bn']),'member_id');
        $memberObj = app::get('ome')->model('members');
        $member_uname = $memberObj->dump(array('member_id'=>$member_id['member_id']),'uname');
        return $member_uname['account']['uname'];
    }

    var $column_name = '商品名称';
    function column_name($row){
       $proObj = app::get('ome')->model('products');
       $name = $proObj->dump(array('bn'=>$row['bn']),'name');
      return $name['name'];
    }

    var $column_amount = '退货金额';
    function column_amount($row){
        $ectoolObj = app::get('eccommon')->model('currency');
        $amount = $ectoolObj->formatNumber($row['iostock_price']*$row['nums']);
        return $amount;
    }
    var $addon_cols = 'iostock_bn';
    var $column_iostockbn = '销售出库单号';
    var $column_iostockbn_width = 150;
    function column_iostockbn($row){
        return $row[$this->col_prefix.'iostock_bn'];
    }
}