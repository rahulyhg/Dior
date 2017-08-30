<?php

class ome_finder_branch {

    /**
     * 是否仓库配置
     * 
     * @var boolean
     */
    private $isConfig = false;

    /**
     * 订单分组缓存
     * 
     * @var array 
     */
    static $orderTypes = null;

    /**
     * 析构
     */
    function __construct() {

        //根据APP做判断
        if ($_REQUEST['app'] == 'ome') {
            $this->isConfig = false;
        } else {
            $this->isConfig = true;
        }

        if (self::$orderTypes === null) {

            $types = app::get('omeauto')->model('order_type')->getList('tid,name,disabled');
            foreach ((array) $types as $t) {
                self::$orderTypes[$t['tid']] = $t;
            }
        }
    }

    var $detail_basic = "仓库详情";

    function detail_basic($branch_id) {
        $render = app::get('ome')->render();
        $branchObj = &app::get('ome')->model('branch');

        $render->pagedata['branch'] = $branchObj->dump($branch_id);

        return $render->fetch('admin/system/branch_detail.html');
    }

    var $addon_cols = "branch_id,area_conf,defaulted";
    var $column_edit = "操作";
    var $column_edit_width = "100";

    function column_edit($row) {
        $finder_id = $_GET['_finder']['finder_id'];

        if (!$this->isConfig) {
            return '<a href="index.php?app=ome&ctl=admin_branch&act=editbranch&p[0]=' . $row[$this->col_prefix . 'branch_id'] . '&p[1]=true&_finder[finder_id]=' . $finder_id . '&finder_id=' . $finder_id . '" target="_blank">编辑</a>';
        } else {

            if ($row['_0_defaulted'] == 'false') {
//                $ret = "&nbsp;<a href='javascript:voide(0);' onclick=\"new Dialog('index.php?app=omeauto&ctl=autobranch&act=edit&p[0]={$row[branch_id]}&finder_id={$finder_id}',{width:760,height:400,title:'仓库相关订单分组设置'}); \">设置</a>";
                $ret = "&nbsp;&nbsp;<a href='index.php?app=omeauto&ctl=autobranch&act=setDefault&p[0]={$row[branch_id]}&finder_id={$finder_id}' target='download'>默认</a>";
            } else {
                $ret .= "&nbsp;&nbsp;<a href='index.php?app=omeauto&ctl=autobranch&act=removeDefault&p[0]={$row[branch_id]}&finder_id={$finder_id}' target='download'>取消默认</a>";
            }
            return $ret;
        }
    }

//    var $column_order = "订单分组";
//    var $column_order_width = "250";
//
//    function column_order($row) {
//
//        $html = '';
//        $title = '';
//        if ($row['_0_defaulted'] == 'false') {
//            if (!empty($row['_0_area_conf'])) {
//                $config = unserialize($row['_0_area_conf']);
//                foreach ($config as $tid) {
//
//                    if (self::$orderTypes[$tid]['disabled'] == 'false') {
//                        $title .= self::$orderTypes[$tid]['name'] . "<br/>";
//                        $html .= sprintf("<a href=\"javascript:voide(0);\" onclick=\"new Dialog('index.php?app=omeauto&ctl=order_type&act=edit&p[0]=%s&finder_id=%s',{width:760,height:480,title:'修改分组规则'}); \">%s</a>&nbsp;&nbsp;", $tid, $_GET[_finder][finder_id], self::$orderTypes[$tid]['name']);
//                    } else {
//                        $html .= "<span style='color:#DDDDDD;' title='该规则已经暂停使用'>" . self::$orderTypes[$tid]['name'] . "</span>";
//                    }
//                }
//            }
//        } else {
//            $title = '所有未分组订单';
//            $html = '<a href="javascript:voide(0);">所有未分组订单</a>';
//        }
//        if ($title <> '') {
//            return "<div onmouseover='bindFinderColTip(event)' rel='{$title}'>" . $html . "<div>";
//        } else {
//            return $html;
//        }
//    }

}

?>