<?php
class giftcard_ctl_admin_setting extends desktop_controller{

	public function __construct($app){
		parent::__construct($app);
	}

	public function index(){
		//echo "<pre>";print_r($this->app->getConf("giftcard_setting"));exit();
		$this->pagedata['giftcard_setting'] = $this->app->getConf("giftcard_setting");
		$this->page('admin/setting.html');
	}

	public function save(){
		$this->begin('index.php?app=giftcard&ctl=admin_setting&act=index');
		$flag = app::get('giftcard')->setConf('giftcard_setting',$_POST);
        if( $flag){
            $this->end(true,app::get('giftcard')->_('保存成功'));
        }else{
            $this->end(false,app::get('giftcard')->_('保存失败'));
        }
	}
}
