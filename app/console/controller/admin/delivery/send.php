<?php
class console_ctl_admin_delivery_send extends desktop_controller {

    var $name = "发货单发送仓库列表";
    var $workground = "console_center";


    /**
     *
     * 发货单列表
     */
    function index(){

        $base_filter = array(); 
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'title'=>'发货单发送仓库列表',
            'base_filter' => $base_filter,
        );

        
        $this->finder('console_mdl_delivery_send', $params);
    }

    //未发货 已发货 全部
    function _views(){
        $oDelivery = app::get('console')->model('delivery_send');
        $base_filter = array();
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'optional'=>false),
            1 => array('label'=>app::get('base')->_('未发起'),'filter'=>array('sync'=>'none'),'optional'=>false),
            2 => array('label'=>app::get('base')->_('发起中'),'filter'=>array('sync'=>'sending'),'optional'=>false),
            3 => array('label'=>app::get('base')->_('运行中'),'filter'=>array('sync'=>'running'),'optional'=>false),
            4 => array('label'=>app::get('base')->_('失败'),'filter'=>array('sync'=>'fail'),'optional'=>false),
            5 => array('label'=>app::get('base')->_('成功'),'filter'=>array('sync'=>'success'),'optional'=>false),
           
        );
        foreach($sub_menu as $k=>$v){
          
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $oDelivery->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=console&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }

        return $sub_menu;
    }

    
   
}
