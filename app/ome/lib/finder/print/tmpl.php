<?php
class ome_finder_print_tmpl{
    var $addon_cols = "prt_tmpl_id";
    var $column_print = "快递单模板操作";
    var $column_print_width = "125";

    function column_print($row){
        $id = $row['prt_tmpl_id'];
        $printObj = &app::get('ome')->model('print_tmpl');

        $data = $printObj->dump($id);


        $width = 0;
        $finder_id = $_GET['_finder']['finder_id'];
        $button = <<<EOF
        <a href="index.php?app=ome&ctl=admin_delivery_print&act=editTmpl&p[0]=$id&finder_id=$finder_id" class="lnk" target="_blank">编辑</a> |
EOF;
        $button2 = <<<EOF
        <span onclick="window.open('index.php?app=ome&ctl=admin_delivery_print&act=downloadTmpl&p[0]=$id')" class="lnk">下载</span> |
EOF;
        $button3 = <<<EOF
        <a href="index.php?app=ome&ctl=admin_delivery_print&act=addSameTmpl&p[0]=$id&finder_id=$finder_id" class="lnk" target="_blank">添加相似</a>
EOF;
        $string = '';
        $string .= $button;
        $width += 50;

        $string .= $button2;
        $width += 50;

        $string .= $button3;
        $width += 80;

        return $string;
    }

}
?>