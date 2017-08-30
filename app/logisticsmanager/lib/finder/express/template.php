<?php
class logisticsmanager_finder_express_template{
    var $addon_cols = "template_id,is_default";
    var $column_confirm = "操作";
    var $column_confirm_width = "100";
    var $column_confirm_order = COLUMN_IN_HEAD;
    function column_confirm($row){
        $id = $row[$this->col_prefix.'template_id'];

        $finder_id = $_GET['_finder']['finder_id'];
        $type = $row['template_type'];

        switch ($type) {
            case 'delivery':
                $act = 'editDeliveryTmpl';
                $act1 = 'copyDeliveryTmpl';
                break;
            case 'stock':
                $act = 'editStockTmpl';
                $act1 = 'copyStockTmpl';
                break;
            default:
                $act = 'editTmpl';
                $act1 = 'copyTmpl';
                break;
        }
        $button = <<<EOF
        <a href="index.php?app=logisticsmanager&ctl=admin_express_template&act=$act&p[0]=$id&finder_id=$finder_id" class="lnk" target="_blank">编辑</a>
EOF;
$button.= <<<EOF
        <a href="index.php?app=logisticsmanager&ctl=admin_express_template&act=$act1&p[0]=$id&finder_id=$finder_id" class="lnk" target="_blank">复制</a>
EOF;
if (!in_array($type,array('delivery','stock'))) {
    

$button.= <<<EOF
        <span onclick="window.open('index.php?app=logisticsmanager&ctl=admin_express_template&act=downloadTmpl&p[0]=$id')" class="lnk">下载</span> 
EOF;
}
$string = '';
        $string .= $button;

        return $string;
    }
    
    var $column_isdefault = "是否默认";
    var $column_isdefault_width = "60";
    var $column_isdefault_order = '90';
    var $column_isdefault_order_field = 'is_default';
    public function column_isdefault($row) {
        $is_default = $row[$this->col_prefix.'is_default'];
        $title = '';
        if ($is_default == 'false') {
            $title = '否';
        }
        else {
            $title = '是';
        }
        return $title;
    }

}
?>