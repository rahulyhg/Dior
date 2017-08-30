<?php
class omeanalysts_ctl_ome_income extends desktop_controller{
    public $__finder_params = array();

    function _views(){
        $pay_filter = array_merge($this->__finder_params,array('payments'=>'true'));
        $refunds_filter = array_merge($this->__finder_params,array('refunds'=>'false'));
        //error_log(var_export($refunds_filter,true),3,__FILE__.".log");
        $mdl_order = $this->app->model('ome_income');
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>$this->__finder_params,'optional'=>false),
            1 => array('label'=>app::get('base')->_('收款'),'filter'=>$pay_filter,'optional'=>false),
            2 => array('label'=>app::get('base')->_('退款'),'filter'=>$refunds_filter,'optional'=>false),
        );
        $i=0;
        foreach($sub_menu as $k=>$v){
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=omeanalysts&ctl=ome_analysis&act=income&view='.$i++;
        }
        return $sub_menu;
    }
}