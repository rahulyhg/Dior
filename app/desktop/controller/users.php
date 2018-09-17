<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class desktop_ctl_users extends desktop_controller{
    
    var $workground = 'desktop_ctl_system';

    public function __construct($app)
    {
        parent::__construct($app);
        //$this->member_model = $this->app->model('members');
        header("cache-control: no-store, no-cache, must-revalidate");
    }
    function index(){
        $this->finder('desktop_mdl_users',array(
            'title'=>app::get('desktop')->_('操作员管理'),
            'actions'=>array(
                array('label'=>app::get('desktop')->_('添加管理员'),'href'=>'index.php?ctl=users&act=addnew','target'=>'dialog::{width:650,height:550,title:\''.app::get('desktop')->_('添加管理员').'\'}'),
            ),'use_buildin_export'=>false
            ));
    }
    
    function addnew(){
        $roles = $this->app->model('roles');
        $users = $this->app->model('users');
        if($_POST){
            $this->begin('index.php?app=desktop&ctl=users&act=index');
            $_POST['pam_account']['login_name'] = trim($_POST['pam_account']['login_name']);
            $_POST['op_no'] = strtoupper(trim($_POST['op_no']));
            if($users->validate($_POST,$msg)){
                if($_POST['super']==0 && (!$_POST['role'])){
                    $this->end(false,app::get('desktop')->_('请至少选择一个工作组'));
                }
                elseif($_POST['super'] == 0 && ($_POST['role'])){
                    foreach($_POST['role'] as $roles)
                    $_POST['roles'][]=array('role_id'=>$roles);
                }
				if($_POST['sys_type']=='local'){
					$_POST['pam_account']['login_password'] =						pam_encrypt::get_encrypted_password($_POST['pam_account']['login_password'],pam_account::get_account_type($this->app->app_id));
				}else{
					$_POST['pam_account']['login_password'] = $_POST['pam_account']['login_password'];
				}
                $_POST['pam_account']['account_type'] = pam_account::get_account_type($this->app->app_id);
				
                if($users->save($_POST)){
					foreach(kernel::servicelist('desktop_useradd') as $key=>$service){
						if($service instanceof desktop_interface_useradd){
							$service->useradd($_POST);
						}
					}
                    if($_POST['super'] == 0){   //是超管就不保存
                        $this->save_ground($_POST);
                    }
                    $this->end(true,app::get('desktop')->_('保存成功'));
                }else{
                        $this->end(false,app::get('desktop')->_('保存失败'));
                    }
                
            }
            else{
                $this->end(false,__($msg));
            }   
        }   
        else{
            $workgroups = $roles->getList('*');
            foreach ($workgroups as $workgroup) {
                if($this->get_show_branch($workgroup['role_id'])){
                    $workgroup_branch[] = $workgroup;
                }else{
                    $workgroup_order[] = $workgroup;
                }
            }
            $this->pagedata['workgroup_branch'] = $workgroup_branch;
            $this->pagedata['workgroup_order']  = $workgroup_order;
            $this->pagedata['workgroup']        = $workgroups;
            $this->display('users/users_add.html');
        }     
    }

      
    ####修改密码
    function chkpassword(){
        $this->begin('index.php?app=desktop&ctl=users&act=index');
        $users = $this->app->model('users');
        if($_POST){
            $sdf = $users->dump($_POST['user_id'],'*',array( ':account@pam'=>array('*'),'roles'=>array('*') ));
            $old_password = $sdf['account']['login_password'];
			$super_row = $users->getList('user_id',array('super'=>'1'));
			$filter['account_id'] = $super_row[0]['user_id'];
            $filter['account_type'] = pam_account::get_account_type($this->app->app_id);
            $filter['login_password'] = pam_encrypt::get_encrypted_password(trim($_POST['old_login_password']),pam_account::get_account_type($this->app->app_id));
         $pass_row = app::get('pam')->model('account')->getList('account_id',$filter);
            if(!$pass_row){
                $this->end(false,app::get('desktop')->_('超级管理员密码不正确'));         
            }
            elseif($_POST['new_login_password']!=$_POST['pam_account']['login_password']){
                $this->end(false,app::get('desktop')->_('两次密码不一致')); 
            }
            else{
                $_POST['pam_account']['account_id'] = $_POST['user_id'];
				if($sdf['sys_type']=='local'){
					$_POST['pam_account']['login_password'] = pam_encrypt::get_encrypted_password(trim($_POST['new_login_password']),pam_account::get_account_type($this->app->app_id));
				}else{
					$_POST['pam_account']['login_password'] = $_POST['new_login_password'];
				}
                $users->save($_POST);
                $this->end(true,app::get('desktop')->_('密码修改成功'));
            }
        }
        $this->pagedata['user_id'] = $_GET['id'];
        $this->page('users/chkpass.html');         

    }
    
    /**
    * This is method saveUser
    * 添加编辑
    * @return mixed This is the return value description
    *
    */
    
    function saveUser(){
        $this->begin();
         $users = $this->app->model('users');
        $roles=$this->app->model('roles');
        $workgroup=$roles->getList('*');
        $param_id = $_POST['account_id'];
        if(!$param_id) $this->end(false, app::get('desktop')->_('编辑失败,参数丢失！'));
        $sdf_users = $users->dump($param_id); 
         if(!$sdf_users) $this->end(false, app::get('desktop')->_('编辑失败,参数错误！'));
          //if($sdf_users['super']==1) $this->end(false, app::get('desktop')->_('不能编辑超级管理员！'));
        if($_POST){
            $_POST['name'] = trim($_POST['name']);
            $_POST['pam_account']['account_id'] = $param_id;
            $_POST['op_no'] = strtoupper(trim($_POST['op_no']));
            if($sdf_users['super']==1){
                $users->editUser($_POST);
                //保存成功后加判断是否启用状态有变更
                 if ($sdf_users && ($sdf_users['status']!=$_POST['status'])) {
                     $_inner_key = sprintf("account_user_%s",$sdf_users['user_id']);
                     $user_data=$users->dump($sdf_users['user_id'],'*',array( ':account@pam'=>array('*') ));
                     cachecore::store($_inner_key, $user_data, 60*15);
                 }
                 $this->end(true, app::get('desktop')->_('编辑成功！'));
            }
            elseif($_POST['super'] == 0 && $_POST['role']){
                foreach($_POST['role'] as $roles){
                    $_POST['roles'][]=array('role_id'=>$roles);
                }
                $users->editUser($_POST);
                $users->save_per($_POST);
                //保存成功后加判断是否启用状态有变更
                 if ($sdf_users && ($sdf_users['status']!=$_POST['status'])) {
                     $_inner_key = sprintf("account_user_%s",$sdf_users['user_id']);
                     $user_data=$users->dump($sdf_users['user_id'],'*',array( ':account@pam'=>array('*') ));
                     cachecore::store($_inner_key, $user_data, 60*15);
                 }
                 $this->end(true, app::get('desktop')->_('编辑成功！'));
            }
            else{
                 $this->end(false, app::get('desktop')->_('请至少选择一个工作组！'));
            }
        }
    }
    /**
    * This is method edit
    * 添加编辑
    * @return mixed This is the return value description
    *
    */
         
    function edit($param_id){
        $users = $this->app->model('users');
        $roles=$this->app->model('roles');
        $workgroup=$roles->getList('*');
        $user = kernel::single('desktop_user');
        $sdf_users = $users->dump($param_id); 
        if(empty($sdf_users)) return app::get('desktop')->_('无内容');   
        $hasrole=$this->app->model('hasrole');
        foreach($workgroup as $key=>$group){
            $rolesData=$hasrole->getList('*',array('user_id'=>$param_id,'role_id'=>$group['role_id']));
            if($rolesData){
                $check_id[] = $group['role_id'];
                $workgroup[$key]['checked']="true";
            }
            else{
                $workgroup[$key]['checked']="false";
            }
        }
        $workgroups = $workgroup; 
        foreach ($workgroups as $workgroup) {
            if($this->get_show_branch($workgroup['role_id'])){
                $workgroup_branch[] = $workgroup;
            }else{
                $workgroup_order[] = $workgroup;
            }
        }
        #echo('<pre>');print_r($workgroup_branch);exit;
        $this->pagedata['workgroup_branch'] = $workgroup_branch;
        $this->pagedata['workgroup_order']  = $workgroup_order;
        #$this->pagedata['workgroup']        = $workgroups;
        $this->pagedata['account_id'] = $param_id;
        $this->pagedata['op_no'] = $sdf_users['op_no'];
        $this->pagedata['name'] = $sdf_users['name'];
        $this->pagedata['super'] = $sdf_users['super'];
        $this->pagedata['status'] = $sdf_users['status'];
        $this->pagedata['ismyself'] = $user->user_id===$param_id?'true':'false';
        if(!$sdf_users['super']){
            $this->pagedata['per'] = $users->detail_per_group($check_id,$param_id);
        }
        //云生意或套件
        if(app::get('bizsuite')->is_actived()){
            $bind = app::get('bizsuite')->model('relation')->getList('shop_id',array('node_type'=>'bizsuite','status'=>'bind'));
        }
        
        if((app::get('suitclient')->is_installed() && app::get('suitclient')->getConf('client_id')) || $bind){
            $this->pagedata['ban_edit'] = true;
        }else{
            $this->pagedata['ban_edit'] = false;
        }
        #登陆人员不是超级管理员，不能修改超级管理员密码
        if(($user->user_id !=1) && ($param_id == 1)){
            $this->pagedata['ban_edit'] = true;
        }
        $this->page('users/users_detail.html');
            
    }
        
    //获取工作组细分
    function detail_ground(){
        //获取订单角色中的选中项
        $check_group_id = json_decode($_POST['checkedName_group']);
        //获取仓库角色中的选择项
        $check_brach_id = json_decode($_POST['checkedName_branch']);
        //获取仓库或订单角色类型
        if(isset($_POST['role'])){
            $role =$_POST['role'];
        }
        $role_id = $_POST['name'];
        $check_id = json_decode($_POST['checkedName']);
        //设置仓库角色常量
        define('BRANCH_INFO',2);
        //订单角色常量
        define('GROUP_INFO',3);
        //仓库角色流程（可能包含双重权限）
        if($role==BRANCH_INFO){
            $backDate = $this->getBackInfo($role,$check_id);
            //点击仓库角色选项时，同时也要获取订单角色选项，查看是否有订单确认小组下拉框，如果有,则保存此下拉框信息
            if($check_group_id){
                $group_info = $this->getBackInfo(1,$check_group_id);
            }
            //对返回数据判断是否是仓库角色
            if(is_array($backDate)){
                //判断仓库角色中是否包含有订单角色选项
                if(!$backDate['group_info']){
                    //如果没有，则直接获取订单确认小组下拉框
                    $backDate['group_info'] = $group_info;
                }
                echo json_encode($backDate);
            }
        }elseif($role == GROUP_INFO){
            //订单角色，有选中
            if(!empty($check_id)){
                //仓库角色有选中
                $_backDate = $this->getBackInfo($role,$check_id);
                if(!empty($check_brach_id)){
                    //获取仓库角色信息
                    $backDate = $this->getBackInfo(BRANCH_INFO,$check_brach_id);
                    if(empty($backDate['group_info'])){
                        if(!empty($_backDate)){
                            //仓库角色没有订单角色，已经选中的订单角色选项包含订单角色
                            $backDate['group_info'] = $_backDate;
                        }else{
                            $backDate['group_info'] = null;
                        }
                    }
                    if(empty($backDate['branch_info'])){
                        $backDate['branch_info'] = null;
                    }
                    echo json_encode($backDate);
                }else{
                    //只有订单一种角色
                    $backDate = $this->getBackInfo($role,$check_id);
                    echo json_encode(array('group_info'=>$backDate));
                }
            }else{
                //仓库角色没有选中,即:订单与仓库角色选项同时为空
                if(empty($check_brach_id)){
                    echo json_encode(array('group_info'=>'','branch_info'=>''));
                }else{
                    //仓库角色有选中项
                    $backDate = $this->getBackInfo(BRANCH_INFO,$check_brach_id);
                    if(empty($backDate['group_info'])){
                        $backDate['group_info'] = null;
                    }
                    if(empty($backDate['branch_info'])){
                        $backDate['branch_info'] = null;
                    }
                    echo json_encode($backDate);
                }
            }
        }
    }
    protected function getBackInfo($role = null,$check_id){
        define('BRANCH_INFO',2);
        $roles = $this->app->model('roles');
        $menus =$this->app->model('menus');
        if($role==BRANCH_INFO){
            //仓库角色，没有任何选中项
            if(empty($check_id)){
                return  array('group_info'=>'','branch_info'=>'');
                exit;
            }
        }else{
            //非仓库角色
             if(!$check_id) {
             echo '';exit;
            } 
        }
        $aPermission =array();
        /* if(!$check_id) {
            echo '';exit;
        } */
        foreach($check_id as $val){
            $result = $roles->dump($val);
            $data = unserialize($result['workground']);
            foreach((array)$data as $row){
                $aPermission[] = $row;
            }
        }
        $aPermission = array_unique($aPermission);
        if(!$aPermission){
            echo '';exit;
        }
        $addonmethod = array();
        foreach((array)$aPermission as $val){
            $sdf = $menus->dump(array('menu_type' => 'permission','permission' => $val));
            $addon = unserialize($sdf['addon']);
            if($addon['show']&&$addon['save']){  //如果存在控制
                if(!in_array($addon['show'],$addonmethod)){
                    $access = explode(':',$addon['show']);
                    $classname = $access[0];
                    $method = $access[1];
                    $obj = kernel::single($classname);
                    //仓库角色
                    if($role==BRANCH_INFO){
                        //检测是否包含订单确认
                        if('show_group'==$method){
                            $group_info.=$obj->$method()."<br />";
                        }
                        //检测是否包含仓库选择
                        if('show_branch'==$method){
                            $branch_info.=$obj->$method()."<br />";
                        }
                    }else{
                            //订单角色(包含其他角色)
                            $html.=$obj->$method()."<br />";
                        }
                }
                $addonmethod[] = $addon['show'];
            }else{
                echo '';
            }
        }
        //仓库角色的返回数据
        if($role==BRANCH_INFO){
            return $backDate =  array('group_info'=>$group_info,'branch_info'=>$branch_info);
        }else{
            //订单角色(包含其他角色)的返回数据
            return $backDate = $html;
    } 
    }
   
    //保存工作组细分
    function save_ground($aData){
        $workgrounds = $aData['role'];
        $menus = $this->app->model('menus');
        $roles =  $this->app->model('roles');
        foreach($workgrounds as $val){
            $result = $roles->dump($val);
            $data = unserialize($result['workground']);
            foreach((array)$data as $row){
                $aPermission[] = $row;
            } 
        }
        $aPermission = array_unique($aPermission);
        if($aPermission){
            $addonmethod = array();
            foreach((array)$aPermission as $key=>$val){
                $sdf = $menus->dump(array('menu_type' => 'permission','permission' => $val));
                $addon = unserialize($sdf['addon']);
                if($addon['show']&&$addon['save']){  //如果存在控制 
                    if(!in_array($addon['save'],$addonmethod)){
                        $access = explode(':',$addon['save']);
                        $classname = $access[0];
                        $method = $access[1];
                        $obj = kernel::single($classname);
                        $obj->$method($aData['user_id'],$aData);
                    }  
                    $addonmethod[] = $addon['save'];
                }   
            }
            }
        }
        
      /**
       * 获取仓库权限分组
       *
       * @param  void
       * @return void
       * @author 
       **/
    public function get_show_branch($role_id)
    {
        $roles = $this->app->model('roles');
        $menus =$this->app->model('menus');
        $result = $roles->dump($role_id);
        $data = unserialize($result['workground']);
        foreach((array)$data as $row){
            $aPermission[] = $row;
        }
        $aPermission = array_unique($aPermission);
        if(!$aPermission){
            return false;
        } 
        $addonmethod = array();
        foreach((array)$aPermission as $val){
            $sdf = $menus->dump(array('menu_type' => 'permission','permission' => $val));
            $addon = unserialize($sdf['addon']);
            if($addon['show']=='ome_roles:show_branch'){
                return ture;
            }
        }

        return false;
    }

}
