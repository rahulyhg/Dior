<?php
class ome_ctl_admin_payment extends desktop_controller{
    var $name = "支付单";
    var $workground = "invoice_center";

   function index(){
       #增加单据导出权限
       $is_export = kernel::single('desktop_user')->has_permission('bill_export');
       $this->finder('ome_mdl_payments',array(
            'title' => '收款单',
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