<?php
/**
 * Created by PhpStorm.
 * User: D1M
 * Date: 2018/03/13
 * Time: 14:20
 */
class sso_ctl_service{

    public function index(){
		$params = $_GET;
		if($params['userName']&&$params['timestamp']&&$params['sign']){
			
			if(date('Ymd',time())!=date('Ymd',$params['timestamp'])){
				echo json_encode(array('status'=>'fail','msg'=>'请求以超时'));exit;
			}

			 $rows = app::get('pam')->model('account')->getList('*',array(
				'login_name'=>$params['userName'],
				'account_type' => 'shopadmin',
				'disabled' => 'false',
				),0,1);

			 if($rows){
				 $sign = strtoupper(md5('test001&userName='.$params['userName'].'&timestamp='.$params['timestamp'].'&test001'));
				 //echo "<pre>";print_r($sign);exit;
				 if($sign==$params['sign']){

					 $auth = pam_auth::instance('shopadmin');
					 $auth->set_appid('desktop');
					 $auth->account()->update('pam_passport_basic', $rows[0]['account_id'], array('account_type'=>'shopadmin','log_data'=>'用户验证成功'));
					$_SESSION['type'] = 'shopadmin';
                    $_SESSION['login_time'] = time();

					$url =  kernel::base_url();

					header('Location:'.$url);
				 }else{
					 echo json_encode(array('status'=>'fail','msg'=>'验证失败'));exit;
				 }
			 }else{
				 echo json_encode(array('status'=>'fail','msg'=>'userName 不存在'));exit;
			 }

		}else{
			if(!$params['userName']){
				echo json_encode(array('status'=>'fail','msg'=>'缺少必要参数：userName'));exit;
			}

			if(!$params['timestamp']){
				echo json_encode(array('status'=>'fail','msg'=>'缺少必要参数：timestamp'));exit;
			}

			if(!$params['sign']){
				echo json_encode(array('status'=>'fail','msg'=>'缺少必要参数：sign'));exit;
			}
		}
		echo "<pre>";print_r($params);exit;
    }
}