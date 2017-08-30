<?php
class console_ctl_admin_delivery_back extends desktop_controller {

    var $name = "发货单追回列表";
    var $workground = "console_center";


    /**
     *
     * 发货单列表
     */
    function index(){

        $user = kernel::single('desktop_user');
        
        $actions = array();
       
       $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('return_back'),
        );
        $base_filter = array_merge($base_filter,$_GET);
        
        
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'actions' => $actions,
            'title'=>'发货单',
            'base_filter' => $base_filter,
        );

        
        $this->finder('console_mdl_delivery', $params);
    }

   

    
    
    function cancel_list()
    {
        $user = kernel::single('desktop_user');
        
        $actions = array();
       
       $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('cancel','back'),
        );
        $base_filter = array_merge($base_filter,$_GET);
        
        //if($user->has_permission('console_process_receipts_print_export')){
    
            $actions[] =  array(
            'label'=>'导出',
            'submit'=>'index.php?app=omedlyexport&ctl=ome_delivery&act=index&action=export&status=cancel',
            'target'=>'dialog::{width:600,height:300,title:\'导出\'}'
            );
       //}
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'actions' => $actions,
            'title'=>'取消发货单',
            'base_filter' => $base_filter,
        );

        
        $this->finder('console_mdl_delivery', $params);
    }
   
}
