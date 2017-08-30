<?php
class ome_ctl_admin_refund extends desktop_controller{
    var $name = "退款单";
    var $workground = "invoice_center";

    function index(){
       #增加单据导出权限
       $is_export = kernel::single('desktop_user')->has_permission('bill_export');
       $this->finder('ome_mdl_refunds',array(
            'title' => '退款单',
            'actions' => array(),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>$is_export,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
       ));
    }
}
?>