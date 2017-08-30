<?php
class purchase_finder_eo{
    var $detail_basic = "库存单详情";

    function detail_basic($eo_id){
        $render = app::get('purchase')->render();
        $oEo = &app::get('purchase')->model("eo");
        $eo = $oEo->eo_detail($eo_id);
        $eo['memo'] = kernel::single('ome_func')->format_memo($eo['detail']['memo']);
        $render->pagedata['eo'] = $eo;
        return $render->fetch('admin/eo/eo_detail.html');
    }

    var $addon_cols = "eo_id";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    function column_edit($row){
        $find_id = $_GET['_finder']['finder_id'];
        $id = $row[$this->col_prefix.'eo_id'];
        $poObj = &app::get('purchase')->model('eo');
        $data = $poObj->dump($id);

        $button = <<<EOF
         <a href="index.php?app=purchase&ctl=admin_eo&act=eo_cancel&p[0]=$id&find_id=$find_id" class="lnk" target="_blank">采购退货</a>

EOF;
       $button22 = <<<EOF
        &nbsp;&nbsp;<a href="index.php?app=purchase&ctl=admin_eo&act=printeo&p[0]=$id" target="_bank" class="lnk">打印</a>
EOF;
        $string = '';
        if ($data['status'] != '3'){

            $string .= $button;
        }
        $string.=$button22 ;
        return $string;
    }

}