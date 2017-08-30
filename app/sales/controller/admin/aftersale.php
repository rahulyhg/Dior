<?php
class sales_ctl_admin_aftersale extends desktop_controller{

     var $name = '单据';
     var $workground = 'invoice_center';

     function index(){
        $this->title = '售后单';        

        $params = array(
            'title'=>$this->title,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
            'orderBy'=>'aftersale_time desc',
        );

        if( isset($_GET['view']) && $_GET['view']!=0 ){
            $params['use_buildin_export'] = true;
        }
        #增加售后单导出权限
        $is_export = kernel::single('desktop_user')->has_permission('bill_export');
        $params['use_buildin_export'] = $is_export;


        $this->finder('sales_mdl_aftersale',$params);
    }

    function _views(){
        $mdl_aftersale = $this->app->model('aftersale');

        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array(),'optional'=>false),
            1 => array('label'=>app::get('base')->_('退货单'),'filter'=>array('return_type' => 'return'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('换货单'),'filter'=>array('return_type' => 'change'),'optional'=>false),
            3 => array('label'=>app::get('base')->_('退款单'),'filter'=>array('return_type' => 'refund'),'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_aftersale->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=sales&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }
        return $sub_menu;
    }
}