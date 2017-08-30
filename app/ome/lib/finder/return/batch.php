<?php
class ome_finder_return_batch{
    

    function __construct($app)
    {
        $this->app = $app;
    }

    

    var $column_edit = "操作";
    var $column_edit_width = "200";
    function column_edit($row){

        return '<a target="dialog::{width:700,height:400,title:\'编辑\'}" href="index.php?app=ome&ctl=admin_return_batch&act=set&p[0]='.$row['batch_id'].'&finder_id='.$_GET['_finder']['finder_id'].'">编辑</a>  ';
    }
    
    
}
?>