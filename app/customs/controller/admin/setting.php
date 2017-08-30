<?php
/**
 +----------------------------------------------------------
 * 跨境申报设置
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2015-04-18 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class customs_ctl_admin_setting extends desktop_controller
{
    /*------------------------------------------------------ */
    //-- 设置列表
    /*------------------------------------------------------ */
    function index()
    {
        $this->title    = '跨境申报列表';
        $base_filter    = array();
        
        $params    = array(
                'actions' => array(
                                array(
                                    'label'=>app::get('customs')->_('新增跨境电子口岸'),
                                    'href'=>"index.php?app=customs&ctl=admin_setting&act=add",
                                    'target'=>'_blank',
                                ),
                                array(
                                        'label'=>app::get('customs')->_('删除并解绑'),
                                        'submit'=>"index.php?app=customs&ctl=admin_setting&act=del",
                                        'target'=>'dialog::{width:500,height:150,title:\'删除选择的电子口岸\'}"'
                                ),
                            ),
                'title' => $this->title,
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_tagedit'=>true,
                'use_buildin_import' => false,
                'use_buildin_export'=>false,
                'allow_detail_popup'=>false,
                'use_buildin_recycle'=>false,
                'use_view_tab'=>true,
                'base_filter' => $base_filter,
        );
        
        $this->finder('customs_mdl_setting', $params);
    }
    
    /*------------------------------------------------------ */
    //-- 新增
    /*------------------------------------------------------ */
    function add()
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        $oCustoms   = app::get('customs')->model('orders');
        
        $oItem      = kernel::single('customs_mdl_setting');
        
        #业务类型
        $oSchema        = $this->app->model('setting')->schema;
        $custom_type    = $oSchema['columns']['custom_type']['type'];
        
        #电子口岸
        $company_id     = $oCustoms->get_typename('company_id');
        
        #跨境申报仓库
        $oBranch        = &app::get('ome')->model('branch');
        $branch         = $oBranch->getList('branch_id, branch_bn, name, online, owner', array('is_declare'=>'true', 'disabled'=>'false'));
        
        $this->pagedata['branch']         = $branch;
        $this->pagedata['company_id']     = $company_id;
        $this->pagedata['custom_type']    = $custom_type;
        $this->singlepage('admin/setting_edit.html');
    }
    
    /*------------------------------------------------------ */
    //-- 编辑
    /*------------------------------------------------------ */
    function editor()
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        $oCustoms    = app::get('customs')->model('orders');
        
        $sid    = intval($_GET['sid']);
        
        $row        = array();
        $oItem      = kernel::single('customs_mdl_setting');
        $row        = $oItem->getList('*', array('sid'=>$sid), 0, 1);
        $row        = $row[0];
        
        if(empty($row))
        {
            header("content-type:text/html; charset=utf-8");
            echo "<script>alert('无效操作...');window.close();</script>";
            exit;
        }
        
        #电子口岸
        $company_id     = $oCustoms->get_typename('company_id');
        
        #业务类型
        $oSchema        = $this->app->model('setting')->schema;
        $custom_type    = $oSchema['columns']['custom_type']['type'];
        
        #跨境申报仓库
        $oBranch        = &app::get('ome')->model('branch');
        $branch         = $oBranch->getList('branch_id, branch_bn, name, online, owner', array('is_declare'=>'true', 'disabled'=>'false'));
        
        #选择的仓库
        $branch_id    = 0;
        if(!empty($row['branch_ids']))
        {
            $branch_sel    = unserialize($row['branch_ids']);
            $branch_id     = $branch_sel[0]['branch_id'];
            $this->pagedata['branch_id']         = $branch_id;
        }
        
        $this->pagedata['branch']         = $branch;
        $this->pagedata['item']           = $row;
        $this->pagedata['company_id']     = $company_id;
        $this->pagedata['custom_type']    = $custom_type;
        $this->singlepage('admin/setting_edit.html');
    }
    
    /*------------------------------------------------------ */
    //-- 保存数据
    /*------------------------------------------------------ */
    function save()
    {
        $this->begin('index.php?app=customs&ctl=admin_setting&act=index');
        $oSetting    = kernel::single("customs_mdl_setting");
        
        //
        $data   = $_POST['item'];
        unset($data['title']);
        
        $data['bind_status']   = ($data['bind_status'] == 'true' ? 'true' : 'false');
        $data['lastdate']      = time();
        
        if(empty($data['sid']))
        {
            $data['dateline']    = time();
        }
        
        //check
        if(empty($data['company_id']) || empty($data['company_code']) || empty($data['company_name']))
        {
           $this->end(false, '设置填写有误，请检查！');
        }
        if(empty($data['username']) || empty($data['password']) || empty($data['branch_id']))
        {
            $this->end(false, '设置填写有误，请检查！');
        }
        
        #发货仓库序列化
        $branch_data[0]        = array('branch_id' => $data['branch_id'], 'is_default' => 'true');
        $data['branch_ids']    = serialize($branch_data);
        
        #新增OR更新
        $result = true;
        $sid    = $data['sid'];
        if(empty($sid))
        {
            $sid    = $oSetting->insert($data);
        }
        else 
        {
            $result    = $oSetting->save($data);
        }
        $data    = $oSetting->dump($sid, '*');
        
        #绑定店铺
        if($data['bind_status'] == 'false')
        {
            $nbRpcObj       = kernel::single('customs_rpc_request_ningbo');
            $bind_status    = $nbRpcObj->bind($data['sid']);
                
            if($bind_status)
            {
                $data['bind_status'] = 'true';
                $data['disabled']    = 'false';
                
                $oSetting->save($data);
            }
        }
        
        #返回
        if($result)
        {
            $this->setCache();
            $this->end(true, '新建成功！');
        }else{
            $this->end(false, '新建失败');
        }
    }
    
    /*------------------------------------------------------ */
    //-- 设置[生成缓存]
    /*------------------------------------------------------ */
    function setCache($data=null)
    {
        if(empty($data))
        {
            $data   = array();
            $oItem  = &$this->app->model('setting');
            $data   = $oItem->getList('*');
        }
    
        $this->app->setConf('customs.setting', $data);
    }
    
    /*------------------------------------------------------ */
    //-- 删除并解绑
    /*------------------------------------------------------ */
    function del()
    {
        $oSetting    = &app::get('customs')->model('setting');
        
        $this->_request    = kernel::single('base_component_request');
        $data              = $this->_request->get_post();
        
        if(empty($data['sid']))
        {
            echo '请选择操作的电子口岸';
            exit;
        }
        
        $filter    = array('sid'=>$data['sid']);
        
        #查询可申报订单
        $count         = $oSetting->count($filter);
        
        $limit    = 10;
        if($count > $limit)
        {
            echo '已选择 '.$count.' 条记录，系统每次只允许批量操作 '.$limit.' 条!';
            exit;
        }
        
        #统计数量
        $this->pagedata['count']  = $count;
        $this->pagedata['sid']    = serialize($data['sid']);
        $this->display('admin/del_setting.html');
    }
    function to_del()
    {
        $this->begin('index.php?app=customs&ctl=admin_setting&act=index');
        $oSetting    = &app::get('customs')->model('setting');
        
        $sid        = array();
        if(!empty($_POST['sid']))
        {
            $sid    = unserialize($_POST['sid']);
        }
        if(empty($sid))
        {
            $this->end(false,'提交数据有误!');
        }
        
        #查询可申报订单
        $filter      = array('sid'=>$sid);
        $shopList    = $oSetting->getList('*', $filter, 0, -1);
        
        if(empty($shopList))
        {
            $this->end(false,'没有可操作的数据!');
        }
        
        #解绑
        $nbRpcObj       = kernel::single('customs_rpc_request_ningbo');
        foreach ($shopList as $key => $val)
        {
            if($val['bind_status'] == 'true')
            {
                $bind_status    = $nbRpcObj->unbind($val['sid']);
            }
        }
        
        #删除
        $result    = $oSetting->delete($filter);
        
        $this->setCache();
        $this->end(true, '删除成功');
    }
}