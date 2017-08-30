<?php
class ome_finder_specification{

    var $column_control = '规格操作';
    function column_control($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href=\'index.php?app=ome&ctl=admin_specification&act=edit&p[0]='.$row['spec_id'].'&finder_id='.$finder_id.'\' target="_blank">编辑</a>';
    }

}
