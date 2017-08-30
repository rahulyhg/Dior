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
class customs_finder_setting
{
    public $addon_cols = 'company_id,branch_ids';//调用字段[不能有空格]
    
    var $type_list = array();
    function __construct()
    {
        $oCustoms    = app::get('customs')->model('orders');
        $this->type_list    = $oCustoms->get_typename();
    }
    
    /*------------------------------------------------------ */
    //-- 编辑
    /*------------------------------------------------------ */
    var $column_edit  = '编辑';
    var $column_edit_order = 5;
    var $column_edit_width = '60';
    function column_edit($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=customs&ctl=admin_setting&act=editor&sid='.$row['sid'].'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
    }
    
    /*------------------------------------------------------ */
    //-- 详细列表
    /*------------------------------------------------------ */
    var $detail_edit    = '基本信息';
    function detail_edit($id)
    {
        $render      = app::get('customs')->render();
        $oSetting    = kernel::single("customs_mdl_setting");
        $items       = $oSetting->getList('*', array('sid' => $id), 0, 1);
        $items       = $items[0];
        
        #业务类型
        $oSchema        = $oSetting->schema;
        $custom_type    = $oSchema['columns']['custom_type']['type'];
        
        $items['custom_type']    = $custom_type[$items['custom_type']];
        $items['disabled']       = ($items['disabled'] == 'false' ? '否' : '是');
        
        $render->pagedata['item'] = $items;
        $render->display('admin/setting_detail.html');
    }
    
    /*------------------------------------------------------ */
    //-- 格式化字段
    /*------------------------------------------------------ */
    #电子口岸
    var $column_company_id = '电子口岸';
    var $column_company_id_width = '120';
    var $column_company_id_order = 30;
    function column_company_id($row)
    {
        if(!empty($row['_0_company_id']))
        {
            $company_name    = $this->type_list['company_id'][$row['_0_company_id']];
        }
        
        return $company_name;
    }
    
    #仓库
    var $column_branch_id = '发货仓库';
    var $column_branch_id_width = '120';
    var $column_branch_id_order = 30;
    function column_branch_id($row)
    {
        if(!empty($row['_0_branch_ids']))
        {
            $branch_list    = unserialize($row['_0_branch_ids']);
            $branch_id      = $branch_list[0]['branch_id'];
            
            $oBranch        = &app::get('ome')->model('branch');
            $branch         = $oBranch->dump($branch_id, 'name');
        }
        
        return $branch['name'];
    }
    
    /*------------------------------------------------------ */
    //-- 显示行样式[粗体：highlight-row]
    //-- [加绿：list-even 加黄：selected 加红：list-warning]
    /*------------------------------------------------------ */
    function row_style($row)
    {
        $style = '';
        if($row[$this->col_prefix . 'bind_status'] == 'true' && $row[$this->col_prefix . 'disabled'] == 'false')
        {
            
        }
        elseif($row[$this->col_prefix . 'bind_status'] == 'false' || $row[$this->col_prefix . 'disabled'] == 'true')
        {
            $style .= ' list-warning ';
        }
        
        return $style;
    }
}