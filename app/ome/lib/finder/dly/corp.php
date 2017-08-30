<?php

class ome_finder_dly_corp {

    var $detail_basic = "物流公司详情";

    function detail_basic($corp_id) {
        $render = app::get('ome')->render();
        $oCorp = &app::get('ome')->model("dly_corp");
        $dly_info = $oCorp->dump($corp_id);
        $dly_info['area_fee_conf'] = unserialize($dly_info['area_fee_conf']);
        $dly_info['protect_rate'] = $dly_info['protect_rate'] * 100;
        $render->pagedata['dt_info'] = $dly_info;

        return $render->fetch("admin/system/dly_corp_detail.html");
    }

    var $addon_cols = "corp_id,disabled,prt_tmpl_id,branch_id,all_branch";
    var $column_edit = "操作";
    var $column_edit_width = "70";

    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=ome&ctl=admin_dly_corp&act=editdly_corp&p[0]=' . $row[$this->col_prefix . 'corp_id'] . '&finder_id=' . $finder_id . '" target="_blank">编辑</a>';
    }

    var $column_used = "当前状态";
    var $column_used_width = "70";
    function column_used($row) {

        if ($row[$this->col_prefix . 'disabled'] == 'false') {

            $ret = '<span style="color:green;">已启用</span>';
        } else {

            $ret = '<span style="color:red;">已停用</span>';
        }

        return $ret;
    }

    var $column_branch = "所属仓库";
    var $column_branch_width = "70";
    function column_branch($row) {

        $tpl_id = $row[$this->col_prefix . 'branch_id'];
        $tmpl = app::get('ome')->model('branch')->dump($tpl_id);

        if (is_array($tmpl)) {
            $ret = $tmpl['name'];
        } elseif($row[$this->col_prefix.'all_branch']=='true') {
            $ret = '全部仓库';
        } else {
            $ret = '<span style="color:red;">未设置</span>';
        }

        return $ret;
    }

    var $column_tmpl = "使用模板";
    var $column_tmpl_width = "70";
    function column_tmpl($row) {

        $tpl_id = $row[$this->col_prefix . 'prt_tmpl_id'];
        if (app::get('logisticsmanager')->is_installed()) {
            //新版控件打印
            $tmpl = app::get('logisticsmanager')->model('express_template')->dump($tpl_id);
            $editStr = is_array($tmpl) ? sprintf('<a class="lnk" target="_blank" href="index.php?app=logisticsmanager&ctl=admin_express_template&act=editTmpl&p[0]=%s&finder_id=%s">%s</a>',$tpl_id,$_GET['_finder']['finder_id'],$tmpl['template_name']) : '';
        } else {
            //老版falsh打印
            $tmpl = app::get('wms')->model('print_tmpl')->dump($tpl_id);
            $ret = sprintf('<a class="lnk" target="_blank" href="index.php?app=wms&ctl=admin_delivery_print&act=editTmpl&p[0]=%s&finder_id=%s">%s</a>',$tpl_id,$_GET['_finder']['finder_id'],$tmpl['prt_tmpl_title']);
        }

        if (is_array($tmpl)) {
            $ret = $editStr;
        } else {
            $ret = '<span style="color:red;">未设置</span>';
        }

        return $ret;
    }
}

?>