<?php
class ome_finder_gtype{

    var $column_control = '类型操作';
    function column_control($row){
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href=\'index.php?app=ome&ctl=admin_goods_type&act=edit&p[0]='.$row['type_id'].'&finder_id='.$finder_id.'\'" target="_blank">编辑</a>';
    }


}
