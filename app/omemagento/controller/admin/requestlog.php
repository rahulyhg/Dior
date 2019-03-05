<?php
class omemagento_ctl_admin_requestlog extends desktop_controller{

	public function __construct($app){
		parent::__construct($app);
	}

	public function index(){
		
		
		$this->finder('omemagento_mdl_request_log', array(
            'title' => '请求日志',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
			//'base_filter'=>array('status'=>'fail'),
        ));
	}

	public function _views(){
		$mdl_log = app::get('omemagento')->model('request_log');
        $base_filter = array('status'=>'fail');//跨境申报 ExBOY

		$failCount = $mdl_log->count($base_filter);
		$successCount = $mdl_log->count(array('status'=>'success'));
		$allCount = $mdl_log->count();
        $failCount5 = $mdl_log->count(array('status'=>'fail','retry|bthan'=>'5'));
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'optional'=>false,'addon'=>$allCount),
            1 => array('label'=>app::get('base')->_('失败请求'),'filter'=>$base_filter,'optional'=>false,'addon'=>$failCount),
            2 => array('label'=>app::get('base')->_('成功请求'),'filter'=>array('status'=>'success'),'optional'=>false,'addon'=>$successCount),
            5 => array('label'=>app::get('base')->_('5次失败请求'),'filter'=>array('status'=>'fail','retry|bthan'=>'4'),'optional'=>false,'addon'=>$failCount5),
            
        );
        return $sub_menu;
	}

	public function retry($log_id){
		$this->begin('index.php?app=omemagento&ctl=admin_requestlog&act=index');
		$log_mdl = app::get('omemagento')->model('request_log');

		$log_info = $log_mdl->dump($log_id);
		//echo "<pre>";print_r($log_info);exit;
		
		$params = unserialize($log_info['original_params']);
//echo "<pre>";print_r($params);exit;
		$method = $params['method'];
		unset($params['method']);
		$flag = kernel::single('omemagento_service_request')->retry_request($method,$params,$log_id,$log_info['retry']);
		if($flag){
			$this->end(true,'重试成功！');
		}else{
			$this->end(false,'重试失败！');
		}
	}
}