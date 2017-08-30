<?php
class ome_finder_abnormal_type{
    var $detail_basic = "订单异常类型详情";
    
    function detail_basic($type_id){
        $render = app::get('ome')->render();
        $oAbnormal = &app::get('ome')->model("abnormal_type");
        $render->pagedata['abnormal']=$oAbnormal->dump($type_id);
        return $render->fetch("admin/system/abnormal_detail.html");
    }

    var $addon_cols = "type_id";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_setting&act=editabnormal&p[0]='.$row[$this->col_prefix.'type_id'].'&finder_id='.$finder_id.' " target="dialog::{width:450,height:150,title:\'编辑异常类型\'}">编辑</a>';
    }
}
?>