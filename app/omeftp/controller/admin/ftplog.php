<?php
class omeftp_ctl_admin_ftplog extends desktop_controller{

	public function __construct($app){
		parent::__construct($app);
	}

	public function index(){
		$this->finder('omeftp_mdl_ftplog', array(
            'title' => 'ftp上传日志',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
			'base_filter'=>array(
				'io_type'=>'out',
			),
			'orderBy'=>'createtime desc',
        ));
	}

	public function dindex(){
		$this->finder('omeftp_mdl_ftplog', array(
            'title' => 'ftp上传日志',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
			'base_filter'=>array(
				'io_type'=>'in',
			),
			'orderBy'=>'createtime desc',
        ));
	}


	public  function retry($log_id){
		$this->begin('index.php?app=omeftp&ctl=admin_ftplog&act=index');
		
		$log_mdl = app::get('omeftp')->model('ftplog');

		$log_info = $log_mdl->dump($log_id);

		$ftp_operate = kernel::single('omeftp_ftp_operate');

		$params['remote'] = $log_info['file_ftp_route'];
		$params['local'] = $log_info['file_local_route'];
		$params['resume'] = 0;

		$ftp_flag = $ftp_operate->push($params,$msg);
		if($ftp_flag){

			$log_data['ftp_log_id'] = $log_id;
			$log_data['status'] = 'succ';
			$log_mdl->save($log_data);
			$this->end(true,'重试成功');
		}else{
			$this->end(false,'重试失败');
		}
	}

	public  function pull_file(){
		$ftp_operate = kernel::single('omeftp_response_delivery');

		$list = $ftp_operate->down_load($dir);

		//echo "<pre>";print_r($list);exit;

	}
	public function downFile($log_id){
		$log_mdl = app::get('omeftp')->model('ftplog');

		$log_info = $log_mdl->dump($log_id);

		$filename = $log_info['file_local_route'];

		$filename = $filename;
        $file_size = filesize($filename);
        $file_name = basename($filename); 
        header("Content-type:text/html;charset=utf-8"); 
        Header("Content-type: application/octet-stream"); 
        Header("Accept-Ranges: bytes"); 
        Header("Accept-Length:".$file_size); 
        Header("Content-Disposition: attachment; filename=".$file_name); 
        echo file_get_contents($filename);
	}
	
}
