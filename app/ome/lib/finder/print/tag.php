<?php
class ome_finder_print_tag{
    var $column_confirm = "操作";
    var $column_confirm_width = "60";
    function column_confirm($row){
        $id = $row['tag_id'];
        $finder_id = $_GET['_finder']['finder_id'];
        $button = <<<EOF
        <a href="index.php?app=ome&ctl=admin_print_termini&act=edit&p[0]=$id&finder_id=$finder_id" class="lnk" target="dialog::{width:600,height:430,title:'编辑大头笔'}">编辑</a>
EOF;
        $string = $button;
        return $string;
    }

}
?>