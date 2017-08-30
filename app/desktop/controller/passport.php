<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class desktop_ctl_passport extends desktop_controller{
    
    var $login_times_error=3;


    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
    }
    
    function index(){
    	$params = json_decode(urldecode($_GET['params']), true);
        
        // begin 转换分销王登录参数
        if($params['login_from']=='b2b'){
            $params['params'] = str_replace(array('_',',','~'),array('+','/','='),$params['params']);
            $params['saas_params'] = $params['params'];
            $params['saas_ts'] = $params['ts'];
            $params['saas_appkey'] = $params['appkey'];
            $params['saas_sign'] = $params['sign'];
        }
        // end
        
        if ($params['saas_params'] && $params['saas_appkey'] && $params['saas_ts'] && $params['saas_sign']) {
            $params['type'] = pam_account::get_account_type($this->app->app_id);
            foreach (kernel::servicelist('login_trust') as $service) {
                if ($service->login($params)) {
                    $this->redirect('index.php');
                    exit();
                }
            }
        }
        
		// 登录之前的预先验证 	
		$obj_services = kernel::servicelist('app_pre_auth_use');
		foreach ($obj_services as $obj){
			if (method_exists($obj, 'pre_auth_uses') && method_exists($obj, 'login_verify')){
				if (!$obj->pre_auth_uses()){
					$this->pagedata['desktop_login_verify'] = $obj->login_verify();
				}
			}
		}
		// end
        
		
		//检查证书是否合法,从而判定产品功能是否可用。比如b2c功能
		$certCheckObj = kernel::service("product_soft_certcheck");
		if(is_object($certCheckObj) && method_exists($certCheckObj,"check"))
		$certCheckObj->check();
        $auth = pam_auth::instance(pam_account::get_account_type($this->app->app_id));
        $auth->set_appid($this->app->app_id);
        $auth->set_redirect_url($_GET['url']);
      	$this->pagedata['desktop_url'] = kernel::router()->app->base_url(1);
		$this->pagedata['cross_call_url'] =base64_encode( kernel::router()->app->base_url(1).
		'index.php?ctl=passport&act=cross_call'
		);
		
		$conf = base_setup_config::deploy_info();
        foreach(kernel::servicelist('passport') as $k=>$passport){
            if($auth->is_module_valid($k,$this->app->app_id)){
                $this->pagedata['passports'][] = array(
                        'name'=>$auth->get_name($k)?$auth->get_name($k):$passport->get_name(),
                        'html'=>$passport->get_login_form($auth,'desktop','basic-login.html',$pagedata),     
                    );
            }
        }
        
        // 如果是分销王，直接转到分销王登录页面
        $server_name = $_SERVER['SERVER_NAME'];
        if(stristr($server_name,'tg.test.taoex.com') || stristr($server_name,B2B_TG_URL)) {
            $msg = array('msg'=>'请登录系统','url'=>trim($server_name));
            $msg = json_encode($msg);
            $msg = base64_encode($msg);
            header("location: ".B2B_API_URL."?act=logout&msg=".$msg);
            exit;
        }
        //如果是旺旺精灵，转到改造后的页面
        if($params['suitelogin'] == 'mini'){
            $this->pagedata['passports'] = null;
            $this->pagedata['passports'][] = array(
                    'name'=>$auth->get_name($k)?$auth->get_name($k):$passport->get_name(),
                    'html'=>$passport->get_login_form($auth,'desktop','wwgenius-basic-login.html',$pagedata),
            );
            $this->pagedata['product_key'] = $conf['product_key'];
            $this->display('wwgenius-login.html');
            exit;
        }
        $this->pagedata['product_key'] = $conf['product_key'];
        $this->display('login.html');
    }
    
    function gen_vcode(){
        $vcode = kernel::single('base_vcode');
        $vcode->length(4);
        $vcode->verify_key($this->app->app_id);
        $vcode->display();
    }
    
	function cross_call(){
		header('Content-Type: text/html;charset=utf-8');
		echo '<script>'.base64_decode($_REQUEST['script']).'</script>';
	}

    function logout($backurl='index.php'){
        
        // begin 分销王退出
        if(stristr(trim($_SERVER['SERVER_NAME']),B2B_TG_URL)) {
            $msg = array('msg'=>'退出系统成功','url'=>trim($_SERVER['SERVER_NAME']));
            $msg = json_encode($msg);
            $msg = base64_encode($msg);
            $backurl = (B2B_API_URL."?act=logout&msg=".$msg);
            $this->begin('javascript:Cookie.dispose("basicloginform_password");Cookie.dispose("basicloginform_autologin");
			top.location="'.$backurl.'"');
        }else{
            $this->begin('javascript:Cookie.dispose("basicloginform_password");Cookie.dispose("basicloginform_autologin");
			top.location="'.kernel::router()->app->base_url(1).'"');
        }
        // end
        
        $this->user->login();
        $this->user->logout();
        $auth = pam_auth::instance(pam_account::get_account_type($this->app->app_id));
        foreach(kernel::servicelist('passport') as $k=>$passport){
    	  if($auth->is_module_valid($k,$this->app->app_id))
            $passport->loginout($auth,$backurl);
        }
        kernel::single('base_session')->destory();
		$this->end('true',app::get('desktop')->_('已成功退出系统,正在转向...'));
        /* $this->redirect('');*/
		
    }
     

}
