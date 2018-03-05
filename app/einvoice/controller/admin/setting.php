<?php
class einvoice_ctl_admin_setting extends desktop_controller{

	public function __construct($app){
		parent::__construct($app);
	}

	public function index(){
		
		$this->pagedata['setting'] = app::get('ome')->getConf("einvoice.setting");
		//echo 'dd';exit;
		$this->page('admin/setting.html');
	}

	public function save(){
		$this->begin('index.php?app=einvoice&ctl=admin_setting&act=index');
		$flag = app::get('ome')->setConf('einvoice.setting',$_POST);
        if( $flag){
            $this->end(true,app::get('omeftp')->_('保存成功'));
        }else{
            $this->end(false,app::get('omeftp')->_('保存失败'));
        }
	}

}
