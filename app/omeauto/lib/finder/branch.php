<?php
class omeauto_finder_branch {
    //var $addon_cols = "oid,config,memo,disabled,defaulted";
    var $column_confirm = '操作';
    var $column_confirm_width = "100";

    function column_confirm($row) {
        $btn = '';
        $btn .= "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=omeauto&ctl=branchbind&act=setBind&p[0]={$row[branch_id]}&finder_id={$_GET[_finder][finder_id]}',{width:760,height:400,title:'绑定备货仓库'}); \">设置</a>";
        return $btn;
    }
}