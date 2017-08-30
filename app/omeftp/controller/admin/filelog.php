<?php
class omeftp_ctl_admin_filelog extends desktop_controller{

	public function __construct($app){
		parent::__construct($app);
	}

	public function index(){
		$this->finder('omeftp_mdl_filelog', array(
            'title' => '文件读写日志',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
        ));
	}

}
