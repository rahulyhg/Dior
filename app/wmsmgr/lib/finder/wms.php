<?php
class wmsmgr_finder_wms{
    var $addon_cols = "channel_id,channel_name,node_id,channel_type,node_type";
    var $column_edit = "操作";
    var $column_edit_width = "120";
    var $column_edit_order = "1";
    function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        $node_type = $row[$this->col_prefix.'node_type'];
        $channel_type  = $row[$this->col_prefix.'channel_type'];
        $node_id = $row[$this->col_prefix.'node_id'];
        $channel_id = $row[$this->col_prefix.'channel_id'];
        $channel_name = $row[$this->col_prefix.'channel_name'];

        $adapter = kernel::single('wmsmgr_func')->getAdapterByChannelId($channel_id);

        $edit_btn = '<a href="index.php?app=wmsmgr&ctl=admin_wms&act=edit&p[0]='.$row[$this->col_prefix.'channel_id'].'&finder_id='.$finder_id.'" target="dialog::{width:500,height:300,title:\'第三方仓储\'}">编辑</a>';
        
        $certi_id = base_certificate::get('certificate_id');
        $api_url = kernel::base_url(true).kernel::url_prefix().'/api';
        $callback_url = urlencode(kernel::openapi_url('openapi.wmsmgr','bindCallback',array('channel_id'=>$channel_id)));
        $app_id = "ome";
        $api_url = urlencode($api_url);

        $bind_btn = empty($node_id) ? 
                ' | <a href="index.php?app=wmsmgr&ctl=admin_wms&act=apply_bindrelation&p[0]='.$app_id.'&p[1]='.$callback_url.'&p[2]='.$api_url.'" target="_blank">申请绑定</a>' : ' | 已绑定';

        if ($extend_button){
            $extend_button = ' | '.implode(' | ',$extend_button);
        }else{
            $extend_button = '';
        }

        if($adapter == "matrixwms"){
            return $edit_btn.$bind_btn.$extend_button.$expressBtn;
        }else{
            return $edit_btn.$extend_button.$expressBtn;
        }
    }

    var $detail_company = '物流公司编码配置';
    function detail_company($wms_id){
        $render = app::get('wmsmgr')->render();
        $express_relation_mdl = app::get('wmsmgr')->model('express_relation');
        if($_POST){
            $wms_express_bn = $_POST['wms_express_bn'];
            foreach($wms_express_bn as $k=>$v){
                if ($k && $v) {
                    $sdata = array('wms_id'=>$wms_id,'sys_express_bn'=>$k,'wms_express_bn'=>$v);
                    $rs = $express_relation_mdl->save($sdata);
                }
                
            }
        }
        $dly_corp_mdl = app::get('ome')->model('dly_corp');
        $sys_express_corp = $dly_corp_mdl->getlist('*');
        $wms = $express_relation_mdl->getlist('*',array('wms_id'=>$wms_id));
        foreach($wms as $v){
            $wmsBn[$v['sys_express_bn']] = $v['wms_express_bn'];
        }
        $render->pagedata['sys_express_corp'] = $sys_express_corp;
        $render->pagedata['wms_id'] = $wms_id;
        $render->pagedata['wmsBn'] = $wmsBn;
        return $render->fetch("exitExpress.html");
    }

    var $detail_shop = '店铺售达方编号';
    function detail_shop($wms_id){
        $render = app::get('wmsmgr')->render();
        if($_POST){
            $shop_config = $_POST['shop_config'];
            app::get('finance')->setConf('shop_config_'.$wms_id,$shop_config);
        }
        $shop_config = app::get('finance')->getConf('shop_config_'.$wms_id);

        $shopMdl = app::get('ome')->model('shop');
        $shop_list = $shopMdl->getlist('*');
        foreach($shop_list as $k=>&$v){
            $v['wms_code'] = isset($shop_config[$v['shop_bn']]) ? $shop_config[$v['shop_bn']] : $v['shop_bn'];
        }
        $render->pagedata['shop_list'] = $shop_list;
        return $render->fetch("shop_config.html");
    }

    var $detail_branch = '仓库编码配置';
    function detail_branch($wms_id){
        $render = app::get('wmsmgr')->render();
        $oBranch_relation = app::get('wmsmgr')->model('branch_relation');
        $oBranch = app::get('ome')->model('branch');
        if($_POST){
            $wms_branch_bn = $_POST['wms_branch_bn'];
            foreach($wms_branch_bn as $k=>$v){
                $sdata = array('wms_id'=>$wms_id,'sys_branch_bn'=>$k,'wms_branch_bn'=>$v);
                $rs = $oBranch_relation->save($sdata);
            }
        }
        
        $branch_list = $oBranch->getlist('*',array('wms_id'=>$wms_id));
        $render->pagedata['branch_list'] = $branch_list;
        unset($branch_list);
        
        $branch_relation_list = $oBranch_relation->getlist('*',array('wms_id'=>$wms_id));
        $wms_branch = array();
        foreach ($branch_relation_list as $branch ) {
            $wms_branch[$branch['sys_branch_bn']] = $branch['wms_branch_bn'];
        }

        $render->pagedata['wms_branch'] = $wms_branch;
        unset($wms_branch,$branch_relation_list);
        return $render->fetch("branch_config.html");
    }

    var $detail_monthaccount = '月结号';
    
    /**
     * Short description.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function detail_monthaccount ($wms_id)
    {
        $render = app::get('wmsmgr')->render();
        if($_POST){
            $monthaccount = $_POST['monthaccount'];
            app::get('wmsmgr')->setConf('monthaccount_'.$wms_id,$monthaccount);
        }
        $monthaccount = app::get('wmsmgr')->getConf('monthaccount_'.$wms_id);
         $render->pagedata['monthaccount'] = $monthaccount;
        $render->pagedata['wms_id'] = $wms_id;
        return $render->fetch("monthaccount.html");
    }
}