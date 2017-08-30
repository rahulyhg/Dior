<?php
class ome_ctl_admin_group extends desktop_controller{
    var $name = "管理员组";
    var $workground = "setting_tools";
    public function __construct($app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
    }

    function index(){
        $this->finder('ome_mdl_groups',array(
           'title'=>'管理员组管理',
           'actions'=>array(
                        array(
                            'label'=>'添加管理员组',
                            'href'=>'index.php?app=ome&ctl=admin_group&act=addgroups&finder_id='.$_GET['finder_id'],
                            'target' => 'dialog::{width:500,height:300,title:\'管理员组\'}',
                        ),
                   array(
                           'label'=>'删除管理员组',
                           //'href'=>'index.php?app=ome&ctl=admin_group&act=addgroups&finder_id='.$_GET['finder_id'],
                           'submit' => 'index.php?app=ome&ctl=admin_group&act=delgroups&finder_id='.$_GET['finder_id'],
                          // 'target' => 'refresh',
                   ),                   
                    ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
         ));
    }

    function save_group(){
        $oGroup_ops = &$this->app->model("group_ops");
        $oGroups = &$this->app->model("groups");
        $this->begin('index.php?app=ome&ctl=admin_group&act=index');
            $groups = $_POST['groups'];
            if(!array_key_exists('description',$groups)){
                $groups['description'] = NULL;
            }
            $oGroups->save($groups);
            if($_POST['groups']['group_id']){
                $oGroup_ops->delete(array('group_id'=>$_POST['groups']['group_id']));
            }
            if ($_POST['groups']['op_id']){
                foreach($_POST['groups']['op_id'] as $k=>$v){
                    $data=array(
                      'group_id'=>$groups['group_id'],
                       'op_id'=>$v
                    );
                    //增加一个操作员只能属于一个订单确认小组的判断
                    $oGroup_ops->delete(array('op_id'=>$v));
                    $oGroup_ops->save($data);
                }
            }
         $this->end(true, app::get('base')->_('保存成功'));
    }
    
     /**
     * 添加管理员
     *
     */
    function addgroups(){
        $oGroups = &$this->app->model("groups");
        
        $operators = $oGroups->get_confirm_ops();
        
        $this->pagedata['operators'] = $operators;
		$this->pagedata['title'] = '添加订单确认小组';
        $this->page("admin/system/groups.html");
    }
    /**/
    function editgroups($group_id){
        $oOperators = &app::get('desktop')->model('users');
        $oGroups = &$this->app->model("groups");
        $oGroup_ops = &$this->app->model("group_ops");
        
        $operators = $oGroups->get_confirm_ops();
        if ($operators)
        foreach($operators as $k=>$v){
           $O_exist = array('group_id'=>$group_id,'op_id'=>$v['user_id']);
            if($oGroup_ops->dump($O_exist)){
                $operators[$k]['checked']='checked=\"checked\"';
            }
        }
        $this->pagedata['operators'] = $operators;
        $this->pagedata['group'] =  $oGroups->dump($group_id);
		$this->pagedata['title'] = '编辑订单确认小组';
        $this->page("admin/system/groups.html");
    }

    function get_op($group_id,$op_id="",$ajax='false'){
        $oGroup = &$this->app->model('groups');
        $ops = $oGroup->get_ops($group_id);
        if($ajax == 'true'){
            $options = "<option value=''>请选择……</option>";
            if($ops && is_array($ops)){
                foreach($ops as $v){
                    if($v['user_id'] == $op_id){
                        $options .= "<option value=".$v['user_id']." selected>".$v['name']."&nbsp;&nbsp;(".$v['login_name'].")</option>";
                    }else{
                        $options .= "<option value=".$v['user_id'].">".$v['name']."&nbsp;&nbsp;(".$v['login_name'].")</option>";
                    }
                }
            }
            echo $options;
        }else{
            return $ops;
        }
    }
    #删除订单确认小组
    function delgroups(){
        $arr_group_id = $this->_request->get_post('group_id');
        $obj_group = $this->app->model('groups');
        $this->begin('index.php?app=ome&ctl=admin_group&act=index');
        $msg = null;
        $rs = $obj_group->checkedGourpInfo($arr_group_id,$msg);
        if(!$rs){
            $this->end(false, $this->app->_($msg));
        }
        if( $obj_group->delete(array('group_id'=>$arr_group_id))){
            $this->end(true, $this->app->_('删除成功'));
        }else{
            $this->end(false, $this->app->_('删除失败'));
        }
    }
}
?>
