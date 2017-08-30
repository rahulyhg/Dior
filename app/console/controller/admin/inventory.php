<?php
class console_ctl_admin_inventory extends desktop_controller{
    var $workground = "console_center";
    function index(){
        $params = array(
            'title'=>'盘点单查看',
            'use_buildin_recycle'=>false,
            'orderBy' => 'inventory_date desc',
        );
        
        $this->finder('console_mdl_inventory',$params);
    }
    
    function view_item(){
        $base_filter = array();
        if ($_GET['inventory_id']){
            $base_filter = array('inventory_id'=>$_GET['inventory_id']);
        }
        $params = array(
            'title'=>'盘点单详情',
            'use_buildin_recycle'=>false,
            'use_buildin_selectrow'=>false,
            'base_filter'=>$base_filter,
        );
        $this->finder('console_mdl_inventory_items',$params);
    }
}