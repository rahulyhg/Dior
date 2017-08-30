<?php
class logistics_finder_rule {
    var $addon_cols = "rule_id,branch_id";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    function __construct(){

        if($_GET['branch_id']){
            $branch_rule = &app::get('logistics')->model('branch_rule')->dump(array('branch_id'=>$_GET['branch_id']),'type');
            if($branch_rule['type']=='other'){
                unset($this->column_edit);
            }
        }
    }
    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];

        $ret= "&nbsp;<a href='index.php?app=logistics&ctl=admin_area_rule&act=area_rule_list&rule_id={$row[rule_id]}&finder_id={$finder_id}' target=\"_blank\">编辑</a>";
        return $ret;
    }

    var $detail_basic='默认规则';
    function detail_basic($rule_id){
        $render = app::get('logistics')->render();

        #规则
        $rule = &app::get('logistics')->model('rule')->detailRule($rule_id,1);

        $render->pagedata['rule'] = $rule;
        return $render->fetch('admin/detail_rule_list.html');
    }

    var $detail_basics='下属特殊地区规则';
    function detail_basics($rule_id){


        $render = app::get('logistics')->render();




        $render->pagedata['rule_id']=$rule_id;

        return $render->fetch('admin/detail_area_rule_list.html');
    }

}

?>