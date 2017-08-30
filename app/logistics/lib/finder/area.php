<?php
class logistics_finder_area {
    var $addon_cols = "area_id";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];
        $ret = "&nbsp;<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=logistics&ctl=admin_area&act=index&act=addArea&area_id={$row[area_id]}&finder_id={$finder_id}',{width:500,height:400,title:'编辑地区'}); \">编辑</a>";

        return $ret;
    }

}

?>