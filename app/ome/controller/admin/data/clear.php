<?php
class ome_ctl_admin_data_clear extends desktop_controller{
	var $name = "数据管理";
    var $workground = 'setting_tools';
	
    function index(){
    	$user = kernel::single('desktop_user')->get_login_name();
    	$this->pagedata['user'] = $user;
        $this->page('admin/data/logi.html');
    }
    /**
     * 验证当前登录的是否为超级管理员
     */
    function logi(){
    	$this->begin('index.php?app=ome&ctl=admin_data_clear&act=index');    
        $post = $_POST;
        //$uObj = app::get('desktop')->model('users');
        //$super = $uObj->dump(array('name'=>$post['name']),'super');
        $is_super = kernel::single('desktop_user')->is_super();
        if($is_super){
            $aObj = app::get('pam')->model('account');
            $pwd = $aObj->dump(array('login_name'=>$post['name']),'login_password');
            $pwd = $pwd['login_password'];
            if(md5($post['pwd'])==$pwd){
            	$this->do_clear($post['name']);
            	$this->end('true','清除成功！');
            }else{
                $this->end(false,'您输入的密码错误！');
            }
        }else{
            $this->end(false,'您无权进行此操作');
        }       
    }
    /**
     * 验证成功后清除数据，单独处理sdb_pam_account跟 sdb_desktop_users表
     * 保留预定义的角色，删除自己增加的管理员
     * 保留打印模板，操作日志类型，我的仓库
     */
    function do_clear($flag){    
        if($flag){
	        foreach(kernel::servicelist('data_clear') as $obj => $instance){
	        	if(method_exists($instance,'data_clear')){
	        		$instance->data_clear();
	        	}
	        }
        }else{
        	$this->end('false','操作错误！');
        }
        
    }
}