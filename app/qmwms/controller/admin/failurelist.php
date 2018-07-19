<?php 
class qmwms_ctl_admin_failurelist extends desktop_controller{
    public function __construct($app){
        parent::__construct($app);
    }

    public function index(){
        $this->finder('qmwms_mdl_qmrequest_log',array(
            'title'=>'失败的接口请求',
            'actions'=>array(
                array('label'=>'重新发送','submit'=>'index.php?app=qmwms&ctl=admin_failurelist&act=resent','target'=>''),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
            'base_filter'=>array('status'=>'failure','log_type'=>'向奇门发起请求','task_name|noequal'=>'inventory.query'),
            'orderBy' => 'last_modified  desc',
        ));
    }

    public function resent(){
        $this->begin('index.php?app=qmwms&ctl=admin_failurelist&act=index');
        if($_POST){
            $idsArr = $_POST['log_id'];
            kernel::single('qmwms_request_resend')->resent($idsArr);
        }
        $this->end(true, app::get('ome')->_('操作成功。'));
    }

}
