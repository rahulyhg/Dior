<?php
class taoexlib_ctl_admin_smslog extends desktop_controller {
    var $workground = 'rolescfg';
    function _views() {
		$mdl_order = $this->app->model('smslog');
		$sub_menu = array();
        $sub_menu[] = array(
            'label' => '全部',
            'filter' => array(),
            'optional' => false,
            'type' => 'weekly',
			'addon'=>$mdl_order->count(),
        );
		$sub_menu[] = array(
            'label' => '发送失败',
            'filter' => array('status'=>0),
            'optional' => false,
            'type' => 'weekly',
			'addon'=>$mdl_order->count(array('status'=>0)),
        );
        return $sub_menu;

    }
	
	
	
	function index(){
        $this->finder('taoexlib_mdl_smslog',
			array(
			'title'=>'短信日志',
			'use_buildin_set_tag'=>true,
			'use_buildin_tagedit'=>true,
			'use_buildin_filter'=>true,
			'use_buildin_recycle'=>true,
			'use_buildin_set_tag'=>true,
			'use_buildin_filter'=>true,
			)
		);
    } 
}