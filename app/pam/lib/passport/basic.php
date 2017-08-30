<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class pam_passport_basic implements pam_interface_passport{

    function __construct(){
        kernel::single('base_session')->start();
        $this->init();
    }
    
    function init(){
        if($ret = app::get('pam')->getConf('passport.'.__CLASS__)){
            return $ret;
        }else{
            $ret = $this->get_setting();
            $ret['passport_id']['value'] = __CLASS__;
            $ret['passport_name']['value'] = $this->get_name();
            $ret['shopadmin_passport_status']['value'] = 'true';
            $ret['site_passport_status']['value'] = 'true';
            $ret['passport_version']['value'] = '1.5';
            app::get('pam')->setConf('passport.'.__CLASS__,$ret);
            return $ret;        
        }
    }
    function get_name(){
        return app::get('pam')->_('用户登录');
    }

    function get_login_form($auth, $appid, $view, $ext_pagedata=array()){
        $render = app::get('pam')->render();
        $render->pagedata['callback'] = $auth->get_callback_url(__CLASS__);
        if($auth->is_enable_vcode()){
            $render->pagedata['show_varycode'] = 'true';
            $render->pagedata['type'] = $auth->type;
        }
        if(isset($_SESSION['last_error']) && ($auth->type == $_SESSION['type'])){
            $render->pagedata['error_info'] = $_SESSION['last_error'];
            unset($_SESSION['last_error']);
            unset($_SESSION['type']);
        }
        if($ext_pagedata){
            foreach($ext_pagedata as $key => $v){
                $render->pagedata[$key] = $v;
            }
        }
        return $render->fetch($view,$appid);
    }

    function login($auth,&$usrdata)
    {
        if($auth->is_enable_vcode())
        {
               $key = $auth->appid;  
            if(!base_vcode::verify($key,intval($_POST['verifycode'])))
            {
                $usrdata['log_data'] = app::get('pam')->_('验证码不正确！');
                $_SESSION['error'] = app::get('pam')->_('验证码不正确！');
                return false;
            }
        }
        if(!$_POST['uname'] || ($_POST['password']!=='0' && !$_POST['password']))
        {
            $usrdata['log_data'] = app::get('pam')->_('验证失败！');
            $_SESSION['error'] = app::get('pam')->_('用户名或密码错误');
            $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
            return false;
        }
        $rows = app::get('pam')->model('account')->getList('*',array(
        'login_name'=>$_POST['uname'],
        'login_password'=>pam_encrypt::get_encrypted_password($_POST['password'],$auth->type),
        'account_type' => $auth->type,
        'disabled' => 'false',
        ),0,1);   
        
        if($rows[0])
        {
            if($_POST['remember'] === "true") setcookie('pam_passport_basic_uname',$_POST['uname'],time()+365*24*3600,'/');
            else setcookie('pam_passport_basic_uname','',0,'/');
            $usrdata['log_data'] = app::get('pam')->_('用户').$_POST['uname'].app::get('pam')->_('验证成功！');
            unset($_SESSION['error_count'][$auth->appid]);
            return $rows[0]['account_id'];
        }
        else
        {
            $usrdata['log_data'] = app::get('pam')->_('用户').$_POST['uname'].app::get('pam')->_('验证失败！');
            $_SESSION['error'] = app::get('pam')->_('用户名或密码错误');
            $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
            return false;
        }
    }
    
    function loginout($auth,$backurl="index.php"){
        unset($_SESSION['account'][$auth->type]);
        unset($_SESSION['last_error']);
        #Header('Location: '.$backurl);
    }

    function get_data(){
    }

    function get_id(){
    }

    function get_expired(){
    }
    
    
    function get_config(){
        $ret = app::get('pam')->getConf('passport.'.__CLASS__);
        if($ret && isset($ret['shopadmin_passport_status']['value']) && isset($ret['site_passport_status']['value'])){
            return $ret;
        }else{
            $ret = $this->get_setting();
            $ret['passport_id']['value'] = __CLASS__;
            $ret['passport_name']['value'] = $this->get_name();
            $ret['shopadmin_passport_status']['value'] = 'true';
            $ret['site_passport_status']['value'] = 'true';
            $ret['passport_version']['value'] = '1.5';
            app::get('pam')->setConf('passport.'.__CLASS__,$ret);
            return $ret;        
        }
    }
    
    function set_config(&$config){
        $save = app::get('pam')->getConf('passport.'.__CLASS__);
        if(count($config))
            foreach($config as $key=>$value){
                if(!in_array($key,array_keys($save))) continue;
                $save[$key]['value'] = $value;
            }
            $save['shopadmin_passport_status']['value'] = 'true';
            
        return app::get('pam')->setConf('passport.'.__CLASS__,$save);
         
    }

    function get_setting(){
        return array(
            'passport_id'=>array('label'=>app::get('pam')->_('通行证id'),'type'=>'text','editable'=>false),
            'passport_name'=>array('label'=>app::get('pam')->_('通行证'),'type'=>'text','editable'=>false),
            'shopadmin_passport_status'=>array('label'=>app::get('pam')->_('后台开启'),'type'=>'bool','editable'=>false),
            'site_passport_status'=>array('label'=>app::get('pam')->_('前台开启'),'type'=>'bool'),
            'passport_version'=>array('label'=>app::get('pam')->_('版本'),'type'=>'text','editable'=>false),
        );
    }
    
    


}
