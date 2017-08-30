<?php
class omeftp_ctl_admin_ftpsetting extends desktop_controller{

	public function __construct($app){
		parent::__construct($app);
	}

	public function index(){
		$this->pagedata['support_ftp'] = extension_loaded('ftp');
		
		$this->pagedata['ftp_server'] = $this->app->getConf("ftp_service_setting");
		//echo 'dd';exit;
		$this->page('admin/setting.html');
	}

	public function save(){
		$this->begin('index.php?app=omeftp&ctl=admin_ftpsetting&act=index');
		$flag = app::get('omeftp')->setConf('ftp_service_setting',$_POST);
        if( $flag){
            $this->end(true,app::get('omeftp')->_('保存成功'));
        }else{
            $this->end(false,app::get('omeftp')->_('保存失败'));
        }
	}

	public function check(){
		app::get('omeftp')->setConf('ftp_service_setting',$_GET);
        $policyObj = kernel::single('omeftp_sftp');
        $this->begin('index.php?app=omeftp&ctl=admin_ftpsetting&act=index');
		
        $ret = $policyObj->check($_GET);
        if($ret){
            $this->end(true,app::get('omeftp')->_('检查通过'));
        }else{
            $this->end(false,$ret);
        }
	}

	public function ax_save(){
		$this->begin('index.php?app=omeftp&ctl=admin_ftpsetting&act=aindex');
		$flag = app::get('omeftp')->setConf('AX_SETTING',$_POST);
		if( $flag){
            $this->end(true,app::get('omeftp')->_('保存成功'));
        }else{
            $this->end(false,app::get('omeftp')->_('保存失败'));
        }
	}

	public function test_up(){
		
		app::get('omeftp')->setConf('AX_Header',$_GET['ax_header']);

		app::get('omeftp')->setConf('AX_SETTING',$_GET);
		$this->begin('index.php?app=omeftp&ctl=admin_ftpsetting&act=axindex');
		$this->end(true,app::get('omeftp')->_('上传成功'));
		$file_obj = kernel::single('omeftp_type_txt');
		if(AX_DIR){
			$file_params['file'] = AX_DIR.'/'.'CN_DI_'.date('YmdHis',time()).'.txt';
		}else{
			$file_params['file'] = PUBLIC_DIR.'/ax/'.'CN_DI_'.date('YmdHis',time()).'.txt';
		}
		$file_params['method'] = 'a';
		$file_params['data'] = $_GET['ax_header']."\n".$_GET['ax_test'];
		$file_params['data'] = $file_obj->characet($file_params['data']);
	//	echo "<pre>";print_r($file_params);exit;
		if($file_obj->toWrite($file_params,$msg)){
			
			$params['remote'] = $file_obj->getFileName($file_params['file']);
			$params['local'] = $file_params['file'];
			$params['resume'] = 0;
			$ftp_operate = kernel::single('omeftp_ftp_operate');
			$return = $ftp_operate->push($params,$msg);
			if(!$return){
				$this->end(false,$msg);
			}
			$this->end(true,app::get('omeftp')->_('上传成功'));
		}else{
			$this->end(false,$msg);
		}
	}

	public function axindex(){
		$ax = app::get('omeftp')->getConf('AX_Header');
		$ax_setting = app::get('omeftp')->getConf('AX_SETTING');
		$this->pagedata['ax_header'] = $ax;
		$this->pagedata['ax_setting'] = $ax_setting;
		$this->page('admin/ax/setting.html');
	}
	


}
