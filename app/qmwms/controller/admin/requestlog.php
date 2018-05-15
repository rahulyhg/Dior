<?php 
class qmwms_ctl_admin_requestlog extends desktop_controller{
    public function __construct($app){
        parent::__construct($app);
    }

    public function index(){
        $this->finder('qmwms_mdl_qmrequest_log',array(
            'title'=>'接口请求日志',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true
        ));
    }

}
