<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class desktop_ctl_roles extends desktop_controller{
    
    var $workground = 'desktop_ctl_system';    
    
    public function __construct($app)
    {
        parent::__construct($app);
        $this->obj_roles = kernel::single('desktop_roles');
        header("cache-control: no-store, no-cache, must-revalidate");
    }
    
    function index(){
        $this->finder('desktop_mdl_roles',array(
            'title'=>app::get('desktop')->_('角色'),
            'actions'=>array(
                            array('label'=>app::get('desktop')->_('新建角色'),'href'=>'index.php?ctl=roles&act=addnew','target'=>'dialog::{title:\''.app::get('desktop')->_('新建角色').'\'}'),
                        )
            ));
    }

    function addnew(){
        $workgrounds = app::get('desktop')->model('menus')->getList('*',array('menu_type'=>'workground','disabled'=>'false','display'=>'true'));
        $this->pagedata['workgrounds'] = $workgrounds;
        $widgets = app::get('desktop')->model('menus')->getList('*',array('menu_type'=>'widgets'));
        $this->pagedata['widgets'] = $widgets;
        foreach($workgrounds as $k => $v)
        {
            $workgrounds[$k]['permissions'] = $this->obj_roles->get_permission_per($v['menu_id'],array());
        }
        
        $this->pagedata['workgrounds'] = $workgrounds;
        $this->pagedata['adminpanels'] = $this->obj_roles->get_adminpanel(null,array());
        $this->pagedata['others'] = $this->obj_roles->get_others();
        $this->page('users/add_roles.html');
    }
    
    function save()
    {
        $this->begin();
        $roles = $this->app->model('roles');
        if($roles->validate($_POST,$msg))
        {
            if($roles->save($_POST))
                $this->end(true,app::get('desktop')->_('保存成功'));
            else
                $this->end(false,app::get('desktop')->_('保存失败')); 
            
        }
        else
        {
            $this->end(false,$msg);
        }
    }
    
    
    function edit($roles_id){
        $param_id = $roles_id;
        $this->begin();
        if($_POST){
            if($_POST['role_name']==''){
                 $this->end(false,app::get('desktop')->_('工作组名称不能为空'));
            }
            if(!$_POST['workground']){
                //$_POST['workground'] = '';
                $this->end(false,app::get('desktop')->_('请至少选择一个权限'));
            }
            $opctl = &$this->app->model('roles');
            $result = $opctl->check_gname($_POST['role_name']);
            if($result && ($result!=$_POST['role_id'])) {$this->end(false,app::get('desktop')->_('该工作组名称已存在'));}
            if($opctl->save($_POST)){
                 $this->end(true,app::get('desktop')->_('保存成功'));
            }else{
               $this->end(false,app::get('desktop')->_('保存失败'));
            }
        
            }
        else{
            $opctl = &$this->app->model('roles');
            $menus = $this->app->model('menus');
            $sdf_roles = $opctl->dump($param_id);
            $this->pagedata['roles'] = $sdf_roles;
            $workground = unserialize($sdf_roles['workground']);
            foreach((array)$workground as $v){
                #$sdf = $menus->dump($v);
                $menuname = $menus->getList('*',array('menu_type' =>'menu','permission' => $v));
                foreach($menuname as $val){
                    $menu_workground[] = $val['workground'];
                }
            }
            $menu_workground = array_unique((array)$menu_workground);
            #print_r($menu_workground);exit;
            $workgrounds = app::get('desktop')->model('menus')->getList('*',array('menu_type'=>'workground','disabled'=>'false','display'=>'true'));
            foreach($workgrounds as $k => $v){
                $workgrounds[$k]['permissions'] = $this->obj_roles->get_permission_per($v['menu_id'],$workground);
                if(in_array($v['workground'],(array)$menu_workground)){
                    $workgrounds[$k]['checked'] = 1;
                    
                }
            }

            $widgets = app::get('desktop')->model('menus')->getList('*',array('menu_type'=>'widgets'));
            
            foreach($widgets as $key=>$widget){
                if(in_array($widget['addon'],$workground))
                    $widgets[$key]['checked'] = true;
            }

            $this->pagedata['widgets'] = $widgets;
            $this->pagedata['workgrounds'] = $workgrounds;
            $this->pagedata['adminpanels'] = $this->obj_roles->get_adminpanel($param_id,$workground);#print_r($workgrounds);exit;
            $this->pagedata['others'] = $this->obj_roles->get_others($workground);
            $this->page('users/edit_roles.html');
            }
    }
    
}
