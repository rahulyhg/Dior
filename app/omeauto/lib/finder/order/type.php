<?php

class omeauto_finder_order_type {

    static $oRlue = null;
    function __construct() {
        if (self::$oRlue === null) {
            $rows = app::get('omeauto')->model('autoconfirm')->getList('oid,name,disabled');//, array('disabled' => 'false'));
            foreach((array)$rows as $t) {
                self::$oRlue['oc'][$t['oid']] = $t;
            }
            $rows = app::get('omeauto')->model('autodispatch')->getList('oid,name,disabled');//, array('disabled' => 'false'));
            foreach((array)$rows as $t) {
                self::$oRlue['od'][$t['oid']] = $t;
            }
            $rows = app::get('ome')->model('branch')->getList('branch_id,name,disabled', array('is_deliv_branch' => 'true'));
            foreach((array)$rows as $t) {
                self::$oRlue['bd'][$t['branch_id']] = $t;
            }
        }
    }
    
    var $addon_cols = "tid,oid,did,bid,config,memo,disabled,group_type";
    
    var $column_confirm = '操作';
    var $column_confirm_width = "80";
    function column_confirm($row) {
        $btn = '';
        if($row['_0_group_type']=='order' || $row['_0_group_type']=='branch'){
            if ($row['_0_disabled'] == 'true') { 
                $btn .= "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定要启用指定规则的使用吗？？？\\n\\n注意：你还可以随时暂停指定规则。\")) {href=\"index.php?app=omeauto&ctl=order_type&act=setStatus&p[0]={$row[tid]}&p[1]=true&finder_id={$_GET[_finder][finder_id]}\";}'>启用</a>"; 
            } else {
                $btn .= "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定要暂时停止指定规则的使用吗？？？\\n\\n注意：你还可以随时启用指定规则。\")) {href=\"index.php?app=omeauto&ctl=order_type&act=setStatus&p[0]={$row[tid]}&p[1]=false&finder_id={$_GET[_finder][finder_id]}\";}'>暂停</a>"; 
                //$btn .= "<a href='javascript:void(0);' onclick='new ConfrimBox(\"你确定要暂时停止指定规则的使用吗？？？\\n\\n注意：你还可以随时启用指定规则。\", {url:\"index.php?app=omeauto&ctl=autodispatch&act=switch&p[0]={$row[oid]}\", method:\"get\", onComplete:function(data){json=JSON.decode(data);if(json.flag==true){ window.location.reload();}else{alert(json.msg);}} }).show();'>暂停</a>";
            }
            
            $btn .= "&nbsp;<a href='javascript:voide(0);' onclick=\"new Dialog('index.php?app=omeauto&ctl=order_type&act=edit&p[0]={$row[tid]}&finder_id={$_GET[_finder][finder_id]}',{width:760,height:480,title:'修改分组规则'}); \">修改</a>";
            if ($row['_0_group_type']=='branch') {
                 $btn .= "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=omeauto&ctl=autobranchset&act=setBind&p[0]={$row[tid]}&finder_id={$_GET[_finder][finder_id]}',{width:760,height:500,title:'设置仓库'}); \">设置</a>";
            }
        } else if($row['_0_group_type']=='sms'){
            if ($row['_0_disabled'] == 'true') { 
                $btn .= "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定要启用指定规则的使用吗？？？\\n\\n注意：你还可以随时暂停指定规则。\")) {href=\"index.php?app=taoexlib&ctl=admin_sms_rule&act=setStatus&p[0]={$row[tid]}&p[1]=true&finder_id={$_GET[_finder][finder_id]}\";}'>启用</a>"; 
            } else {
                $btn .= "<a href='javascript:void(0);' target='download' onclick='if(confirm(\"你确定要暂时停止指定规则的使用吗？？？\\n\\n注意：你还可以随时启用指定规则。\")) {href=\"index.php?app=taoexlib&ctl=admin_sms_rule&act=setStatus&p[0]={$row[tid]}&p[1]=false&finder_id={$_GET[_finder][finder_id]}\";}'>暂停</a>"; 
                //$btn .= "<a href='javascript:void(0);' onclick='new ConfrimBox(\"你确定要暂时停止指定规则的使用吗？？？\\n\\n注意：你还可以随时启用指定规则。\", {url:\"index.php?app=omeauto&ctl=autodispatch&act=switch&p[0]={$row[oid]}\", method:\"get\", onComplete:function(data){json=JSON.decode(data);if(json.flag==true){ window.location.reload();}else{alert(json.msg);}} }).show();'>暂停</a>";
            }
            
            $btn .= "&nbsp;|&nbsp;<a href='javascript:voide(0);' onclick=\"new Dialog('index.php?app=taoexlib&ctl=admin_sms_rule&act=edit_rule&p[0]={$row[tid]}&finder_id={$_GET[_finder][finder_id]}',{width:760,height:480,title:'修改分组规则'}); \">修改</a>";

        }
        

        return $btn;
    }
    function getsampleno($tid){
        $sr         = app::get('taoexlib')->model('sms_bind');
        $srinfo = $sr->select()->columns()->where('tid=?',$tid)->instance()->fetch_row();
        $sms_sample = app::get('taoexlib')->model('sms_sample');
        $srinfo = $sms_sample->select()->columns()->where('id=?',$srinfo['id'])->instance()->fetch_row();
        return $srinfo['id'];
    }
    var $column_autoconfirm = '自动审单规则';
    var $column_autoconfirm_width = "150";
    function column_autoconfirm($row) {
        
        $oid = intval($row['_0_oid']);
        if ( $oid > 0) {
            if (self::$oRlue['oc'][$oid]['disabled'] == 'false') {
                return "<a href='javascript:voide(0);' onclick=\"new Dialog('index.php?app=omeauto&ctl=autoconfirm&act=edit&p[0]={$oid}&finder_id={$_GET[_finder][finder_id]}',{width:750,height:480,title:'修改审单规则'}); \">".self::$oRlue['oc'][$oid]['name']."</a>";
            } else {
                return "<span style='color:#DDDDDD;' title='该规则已经暂停使用'>(".self::$oRlue['oc'][$oid]['name'].")</span>";
            }
        } else {
            
            return "";
        }
    }
    
    var $column_autodispatch = '自动分配规则';
    var $column_autodispatch_width = "150";
    function column_autodispatch($row) {
        
        $did = intval($row['_0_did']);
        if ( $did > 0) {
            if (self::$oRlue['od'][$did]['disabled'] == 'false') {
                return "<a href='javascript:voide(0);' onclick=\"new Dialog('index.php?app=omeauto&ctl=autodispatch&act=edit&p[0]={$did}&finder_id={$_GET[_finder][finder_id]}',{width:760,height:400,title:'修改自动分派规则'}); \">".self::$oRlue['od'][$did]['name']."</a>";
            } else {
                return "<span style='color:#DDDDDD;' title='该规则已经暂停使用'>(".self::$oRlue['od'][$did]['name'].")</span>";
            }
        } else {
            
            return "";
        }
    }
    
    var $column_autobranch = '发货仓库';
    var $column_autobranch_width = "150";
    function column_autobranch($row) {

        $bid = intval($row['_0_bid']);
        if ( $bid > 0) {
            if (self::$oRlue['bd'][$bid]['disabled'] == 'false') {
                return "".self::$oRlue['bd'][$bid]['name']."";
            } else {
                return "<span style='color:#DDDDDD;' title='该仓库已经暂停使用'>(".self::$oRlue['od'][$did]['name'].")</span>";
            }
        } else {
            
            return "";
        }
    }
    
    var $column_memo = '简单说明';
    var $column_memo_width = "250";
    function column_memo($row) {
        
        return $row['_0_memo'];
    }
    
    var $column_content = '类型内容';
    var $column_content_width = "250";
    function column_content($row) {
        
        $html = '';
        if (!empty($row['_0_config'])) {
            
            foreach ($row['_0_config'] as $row) {
                
                $role = json_decode($row, true);
                $html .= $role['caption'] . "<br/>";
            }
        }

        return "<div onmouseover='bindFinderColTip(event)' rel='{$html}'>" . str_replace("<br/>", "&nbsp;;&nbsp;", $html) . "<div>";
    }
    
    var $column_disabled = '是否启用';
    var $column_disabled_width = "80";
    function column_disabled($row) {
        if ($row['_0_disabled'] == 'true') {
            return "<span style='color:red;'>停用</span>";
        } else {
           return "<span style='color:green;'>启用</span>"; 
        }
    }
}