<?php
class qmwms_ctl_admin_queue extends desktop_controller{

    public function __construct($app){
        parent::__construct($app);
    }

    public function index(){
        $base_filter = array('status' =>'2');
        $params      = array(
            'title'                  => 'wms队列错误日志',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'    => false,
            'use_buildin_filter'     => true,
            'base_filter'            => $base_filter,
            'use_buildin_recycle'    => false,
        );
        $this->finder('qmwms_mdl_queue', $params);
    }











}
?>