<?php
class ome_ctl_admin_member extends desktop_controller{
    var $name = "会员";
    var $workground = "invoice_center";

    function index(){
        $this->finder('ome_mdl_members',array(
            'title' => '会员管理',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'actions' => array(
                    array(
                        'label' => '添加会员',
                        'href' => 'index.php?app=ome&ctl=admin_member&act=addMember',
                        'target' => "_blank",
                    ),
            ),
       ));
    }
    
    function addMember(){                                                                                                                         
        $this->display("admin/member/add_member.html");
    }
    
    function doAddMember(){
        //$this->begin("index.php?app=ome&ctl=admin_member&act=addMember");
        $post = $_POST;
    
        /*if (!$post['account']['uname']){
            $this->end(false, '请填写用户名');
        }
        if (!$post['contact']['phone']['telephone']){
            $this->end(false, '请填写电话号码');
        }
        if (!$post['profile']['gender']){
            $this->end(false, '请选择性别');
        }
        if (!$post['contact']['phone']['mobile']){
            $this->end(false, '请填写手机号码');
        }
        */
        $mObj = &$this->app->model("members");
        
        $member = $mObj->dump(array('uname'=>$post['account']['uname']),'member_id');
        
        if ($member){
            echo 'false';
            exit;
        }
        $area = $post['area'];
        $mem = $post;
        if ($mObj->save($mem)){
            $data = $mObj->getList('member_id,uname,area,mobile,email,sex',array('member_id'=>$mem['member_id']),0,-1);
            if ($data)
            foreach ($data as $k => $v){
                $data[$k]['sex'] = ($v['sex']=='male') ? '男' : '女';
            }
            echo json_encode($data);
        }else{
            echo 'false';
        }

        //$this->end(true, '保存成功');
    }
    
    
}
?>
