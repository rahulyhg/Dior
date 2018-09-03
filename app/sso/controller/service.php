<?php
/**
 * Created by PhpStorm.
 * User: D1M
 * Date: 2018/03/13
 * Time: 14:20
 */
class sso_ctl_service extends base_controller{

    public function index(){
		$params = $_GET;
		if($params['userName']&&$params['timestamp']&&$params['sign']){
			
			if(date('Ymd',time())!=date('Ymd',$params['timestamp'])){
				$this->errorPage('请求已超时');
			}

			 $rows = app::get('pam')->model('account')->getList('*',array(
				'login_name'=>$params['userName'],
				'account_type' => 'shopadmin',
				'disabled' => 'false',
				),0,1);

			 if($rows){
				 $sign = strtoupper(md5($rows[0]['login_password'].'&userName='.$params['userName'].'&timestamp='.$params['timestamp'].'&'.$rows[0]['login_password']));
				 //echo "<pre>";print_r($sign);exit;
				 if($sign==$params['sign']){

					 $auth = pam_auth::instance('shopadmin');
					 $auth->set_appid('desktop');
					 $auth->account()->update('pam_passport_basic', $rows[0]['account_id'], array('account_type'=>'shopadmin','log_data'=>'用户验证成功'));
					$_SESSION['type'] = 'shopadmin';
                    $_SESSION['login_time'] = time();

					$url =  kernel::base_url(true);

					header('Location:'.$url);
				 }else{
					 $this->errorPage('验证失败');
				 }
			 }else{
				 $this->errorPage('userName 不存在');
			 }

		}else{
			if(!$params['userName']){
				$this->errorPage('缺少必要参数：userName');
			}

			if(!$params['timestamp']){
				$this->errorPage('缺少必要参数：timestamp');
			}

			if(!$params['sign']){
				$this->errorPage('缺少必要参数：sign');
			}
		}
    }


	function errorPage($msg){
		$this->pagedata['msg'] = $msg;
		$this->display('error.html');
	}
}