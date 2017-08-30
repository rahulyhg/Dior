<?php
class omeauto_ctl_admin_order extends desktop_controller{
    var $name = "订单中心";
    var $workground = "order_center";
    var $order_type = 'all';

    function dispatch(){
        $this->title = '订单调度';
        switch ($_GET['flt'])
        {
            case 'autoassigned':
                $this->order_type = 'autoassigned';
                $this->base_filter = array('abnormal'=>'false','is_fail'=>'false','is_auto'=>'true');
                $this->base_filter['process_status'] = array('unconfirmed','confirmed','splitting','splited','remain_cancel');
                $this->title = '自动处理订单';
                $finder_aliasname = "order_dispatch_autoassigned";
                $finder_cols = "order_bn,process_status,ship_name,ship_area,total_amount";
                break;
        }
        $this->finder('ome_mdl_orders',array(
           'title' => $this->title,
           'actions' => array(
               array('label'=>'订单分派','submit'=>'index.php?app=ome&ctl=admin_order&act=dispatching','target'=>'dialog::{width:400,height:200,title:\'订单分派\'}'),
               array('label'=>'订单分派统计','href'=>'index.php?app=ome&ctl=admin_order&act=count_dispatch','target'=>'dialog::{width:1000,height:400,title:\'订单分派统计\'}'),
           ),
           'base_filter' => $this->base_filter,
           'use_buildin_new_dialog' => false,
           'use_buildin_set_tag'=>false,
           'use_buildin_recycle'=>false,
           'use_buildin_export'=>false,
           'use_buildin_import'=>false,
           'use_buildin_filter'=>true,
           'finder_aliasname'=>$finder_aliasname,
           'finder_cols'=>$finder_cols,
        ));
    }

}
?>