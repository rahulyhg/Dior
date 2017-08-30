<?php
class ome_finder_return_product_problem{
    var $detail_basic = "售后问题详情";
    
    function detail_basic($problem_id){
        $render = app::get('ome')->render();
        $oProblem = &app::get('ome')->model("return_product_problem");
        $problem = $oProblem->dump($problem_id);
        $render->pagedata['problem'] = $problem;
        return $render->fetch("admin/system/product_problem_detail.html");

    }
    
    var $addon_cols = "problem_id";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_setting&act=editproblem&p[0]='.$row[$this->col_prefix.'problem_id'].'&finder_id='.$finder_id.' " target="dialog::{width:450,height:150,title:\'新建异常类型\'}">编辑</a>';
    }
}
?>