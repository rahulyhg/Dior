<?php
class logisticsaccounts_finder_statements{

    var $addon_cols = "sid,status";
    var $column_edit = "操作";
    var $column_edit_width = "100";
    //var $detail_basic = "详情";
    var $detail_log='操作日志';
    function column_edit($row){
        $find_id = $_GET['_finder']['finder_id'];
        $sid = $row['sid'];
    if($row['status']=='0'){
    $button= <<<EOF
    <a href="index.php?app=logisticsaccounts&ctl=admin_statements&act=edit&sid=$sid&finder_id=$find_id" class="lnk" " target="_blank">编辑</a>&nbsp;
    &nbsp;
EOF;

    }else{
        $button= <<<EOF
    <a href="index.php?app=logisticsaccounts&ctl=admin_statements&act=edit&sid=$sid&view=1&finder_id=$find_id" class="lnk" " target="_blank">明细</a>&nbsp;
    &nbsp;
EOF;
    }
    return $button;
    }
//
//    function detail_basic($sid){
//        $render = app::get('logisticsaccounts')->render();
//        $statementsObj = &app::get('logisticsaccounts')->model('statements');
//        $statements = $statementsObj->get_statements($sid);
//
//
//        $render->pagedata['statements'] = $statements;
//        unset($statements);
//        return $render->fetch('detail_statements.html');
//    }

    function detail_log($sid){
        $render = app::get('logisticsaccounts')->render();
        $opObj  = &app::get('ome')->model('operation_log');
        $logdata = $opObj->read_log(array('obj_id'=>$sid,'obj_type'=>'statements@logisticsaccounts'), 0, -1);
        foreach($logdata as $k=>$v){
            $logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['log'] = $logdata;
        return $render->fetch('operation_log.html');
    }


}
?>