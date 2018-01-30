<?php
class einvoice_ctl_admin_log extends desktop_controller{

	public function __construct($app){
		parent::__construct($app);
	}

	public function index(){
		$this->finder('einvoice_mdl_request_log', array(
            'title' => '请求日志',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
			'orderBy' =>'createtime DESC',
			//'base_filter'=>array('status'=>'fail'),
        ));
	}

	

}
