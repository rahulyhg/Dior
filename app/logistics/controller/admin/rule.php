<?php
    class logistics_ctl_admin_rule extends desktop_controller {
        var $workground = 'setting_tools';
        var $defaultWorkground = 'setting_tools';

        /**
        *显示仓库
        */
        function index(){

            $branchMode = app::get('ome')->getConf('ome.branch.mode');

            $oBranch = &app::get('ome')->model('branch');
            $branch = $oBranch->getList('branch_id,name',array('is_deliv_branch'=>'true'),0,-1);
            if(count($branch)==1){
                $data = array(
                    'branch_id'=>$branch[0]['branch_id'],
                    'set_rule'=>'custom'
                );

                $result = $this->app->model('branch_rule')->create($data);
                $this->splash('success','index.php?app=logistics&ctl=admin_rule&act=ruleList&branch_id='.$branch[0]['branch_id'],'保存成功');
            }else{
                $oBranch = &app::get('ome')->model('branch');
                $is_super = kernel::single('desktop_user')->is_super();
                if (!$is_super){
                    $branch_list = $oBranch->getBranchByUser();

                }else{
                    $branch_list = $oBranch->getList('branch_id,name',array('is_deliv_branch'=>'true'),0,-1);
                }

                $this->pagedata['branch_list'] = $branch_list;
                unset($branch_list);

                $this->page('admin/branch.html');
            }
        }
        /**
        *仓库对应规则列表
        */
        function ruleList(){
            $data = $_POST;
            $branch_id = intval($_GET['branch_id']);
            if($branch_id==''){
                echo '请选择仓库';
                exit;
            }
            if($branch_id) {
                $_SESSION['branch_id'] = $branch_id;
            } elseif($_GET['filter']['branch_id']) {
                $_SESSION['branch_id'] = $_GET['filter']['branch_id'];
            }


            $branch = &app::get('ome')->model('branch')->getList('name', array('branch_id'=>$_SESSION['branch_id']));
            $oBranch = &app::get('ome')->model('branch');
            $branch_list = $oBranch->getList('branch_id,name',array('is_deliv_branch'=>'true'),0,-1);
            $this->pagedata['branch_list'] = $branch_list;
            $title = "<span style='color:red;'>".$branch[0]['name']."</span>物流公司优先设置规则(必须先在<a href='index.php?app=ome&ctl=admin_dly_corp&act=index'>\"物流公司管理\"</a>内建立物流公司与仓库的关系)&nbsp;&nbsp;&nbsp;&nbsp;<a href='http://top.shopex.cn/ecos/tpl/tghelp.zip' target='_blank'>帮助手册</a>";
            #获取仓库规则类型
            $branch_rule = $this->app->model('branch_rule')->getlist('type,parent_id',array('branch_id'=>$_SESSION['branch_id']));

            if($branch_rule[0]['type']=='custom'){
                $base_filter['branch_id'] = $_SESSION['branch_id'];
                $action = array(
                            array(
                            'label'  => '新建规则',
                            'href'   => 'index.php?app=logistics&ctl=admin_rule&act=addRule&branch_id='.$_SESSION['branch_id'],
                            'target' => 'dialog::{width:800,height:400,title:\'新建规则\'}',
                            ),

                            array(
                            'label'  => '删除规则',
                            'submit'   => 'index.php?app=logistics&ctl=admin_rule&act=deleteRule',
                            'target' => 'dialog::{width:500,height:200,title:\'删除规则\'}',
                            ),
                            );
            }else if($branch_rule[0]['type']=='other'){
                $parent_id=0;
                $this->app->model('branch_rule')->getBranchRuleParentId($_SESSION['branch_id'],$parent_id);

                if($parent_id!=0){
                    $base_filter['branch_id'] = $parent_id;

                    $branch = &app::get('ome')->model('branch')->getList('name', array('branch_id'=>$parent_id));
                    $title.="(父级仓库:".$branch[0]['name'].")";
                    $action = array(
                                array(
                                'label'  => $this->app->_('解除关联'),
                                'href'   => 'index.php?app=logistics&ctl=admin_rule&act=unbindRule&branch_id='.$_SESSION['branch_id'],
                                'target' => 'dialog::{width:400,height:200,title:\'选择仓库\'}',
                                ),

                                );
                }else{
                            $base_filter['branch_id'] = $_SESSION['branch_id'];

                             $action = array(
                                array(
                                'label'  => $this->app->_('复用仓库规则'),
                                'href'   => 'index.php?app=logistics&ctl=admin_rule&act=copyRule&branch_id='.$_SESSION['branch_id'],
                                'target' => 'dialog::{width:600,height:400,title:\'选择仓库\'}',
                                ),

                                );

                }

            }else{
                $action = array();
            }
            $params=array(
                'title' => $title,
                'base_filter' => $base_filter,
                'actions'=>$action,
                'finder_aliasname' => 'rule_'.$branch_id,
                'use_buildin_recycle' => false,

            );

            $this->finder('logistics_mdl_rule',$params);
        }

        /**
        *新增规则
        */
        function addRule(){
            $branch_id = $_GET['branch_id'];

            //电子面单来源类型
            $channelObj = &app::get("logisticsmanager")->model('channel');
            $rows = $channelObj->getList("channel_id,channel_type",array('status'=>'true'));
            $channelType = array();
            foreach($rows as $val) {
                $channelType[$val['channel_id']] = $val['channel_type'];
                unset($val);
            }
            unset($rows);

            //物流公司信息
            $braObj = &app::get('ome')->model('branch');
            $dly_corp = $braObj->get_corp($branch_id,'');
            array_push($dly_corp,array('corp_id'=>'-1','name'=>'人工审单'));
            $dlyCorpNormal = $electronIds = array();
            foreach($dly_corp as $key=>$val) {
                if($val['tmpl_type'] != 'electron') {
                    $dlyCorpNormal[] = $val;
                } else {
                    if($channelType[$val['channel_id']] == 'wlb') {
                        $electronIds[] = $val['corp_id'];
                        $dly_corp[$key]['name'] .= '(电)';
                    } else {
                        $dlyCorpNormal[] = $val;
                        $dly_corp[$key]['name'] .= '('.$channelType[$val['channel_id']].')';
                    }
                }
            }

            $this->pagedata['dly_corp'] = $dly_corp;
            $this->pagedata['dlyCorpNormal'] = $dlyCorpNormal;
            $this->pagedata['dlyCorpNormalJson'] = json_encode($dlyCorpNormal);
            $this->pagedata['electronIds'] = json_encode($electronIds);
            $this->pagedata['dly_corp_list'] = json_encode($dly_corp);
            $this->pagedata['elecIds'] = $electronIds;
            unset($dly_corp);
            $this->pagedata['branch_id'] = $branch_id;
            $this->page('admin/create_rule.html');
        }

        /**
        *获取仓库规则信息
        */
        function getBranchRule(){
            $branch_id = $_GET['branch_id'];
            if($branch_id){
                $branch_rule = $this->app->model('branch_rule')->getlist('type',array('branch_id'=>$branch_id),0,1);
                if($branch_rule){
                    echo json_encode($branch_rule[0]);
                }
            }
        }

        /**
        *保存仓库规则
        */
        function saveRule(){
            $this->begin();

            $data = $_POST;
            $rule = $this->app->model('rule')->getlist('rule_id',array('rule_name'=>$data['rule_name'],'branch_id'=>$data['branch_id']));
            if($rule){
                $this->end(false,'规则名称已存在');
            }

            #判断区域是否已存在

            $regionRule = $this->app->model('rule')->chkBranchRegion($data['branch_id'],$data['p_region_id'],'');
            if($regionRule){
                $this->end(false,'此仓库区域已有相同的规则建立');
            }
            $rule_id = $this->app->model('rule')->createRule($data);

            if($rule_id){

                $this->end(true,'保存成功','index.php?app=logistics&ctl=admin_rule&act=ruleList&branch_id='.$data['branch_id']);
                //exit;
            }
        }
        /**
        * 查询规则名称是否存在
        */
        function checkRuleName(){
            $rule_name = trim($_GET['rule_name']);
            $branch_id = $_GET['branch_id'];
            $rule = $this->app->model('rule')->getlist('rule_id',array('rule_name'=>$rule_name,'branch_id'=>$branch_id));
            if($rule){
                echo json_encode(array('message'=>'已存在'));
            }
        }
        /**
        * 复制仓库规则
        */
        function copyRule(){

            if($_POST['oper']=='edit'){

                $this->begin('index.php?app=logistics&ctl=admin_rule&act=ruleList&branch_id='.$_POST['branch_id'].'&_finder[finder_id]='.$_GET['finder_id'].'');
                $rule_data = array();
                $rule_data['branch'] = $_POST['branch'];
                $rule_data['branch_id'] = $_POST['branch_id'];

                $this->app->model('rule')->updateRule($rule_data);
                $this->end(true,'设置成功');
            }else{
                $oBranch = &app::get('ome')->model('branch');
                $branch_list = $oBranch->getList('branch_id,name',array('is_deliv_branch'=>'true'),0,-1);

                $branch_id = $_GET['branch_id'];
                foreach($branch_list as $k=>$v){
                    if($v['branch_id']==$branch_id){
                        unset($branch_list[$k]);
                    }

                    $rule = $this->app->model('branch_rule')->dump(array('branch_id'=>$v['branch_id']),'type');

                    if($rule['type']=='other'){
                        unset($branch_list[$k]);
                    }
                }

                $this->pagedata['finder_id'] = $_GET['finder_id'];
                $this->pagedata['branch_list'] = $branch_list;
                $this->pagedata['branch_id'] = $branch_id;
                $this->page('admin/copyrule.html');
            }
        }

        /**
        * 解绑仓库规则
        */
        function unbindRule(){
            if($_POST['oper']=='edit'){
                $this->begin('index.php?app=logistics&ctl=admin_rule&act=ruleList&branch_id='.$_POST['branch_id'].'&_finder[finder_id]='.$_GET['finder_id'].'');
                $rule_data = array();
                $rule_data['branch'] = 0;
                $rule_data['branch_id'] = $_POST['branch_id'];

                $this->app->model('rule')->updateRule($rule_data);
                $this->end(true,'解除成功');

            }else{
                $branch_id = $_GET['branch_id'];
                $this->pagedata['branch_id'] = $branch_id;
                $this->pagedata['finder_id'] = $_GET['finder_id'];
                $this->page('admin/unbindrule.html');
            }
        }

        /**
        * 删除一级地区确认

        */
        function confirmDeleteRule(){
            $this->display('admin/confirmDeleteRule.html');
        }


        /**
        *删除规则
        */
        function doDeleteRule(){

            $this->begin();
            $data = $_POST;
            if($data['deleteareaflag']=='0'){
                $this->app->model('rule')->deleteRule($data['rule_id'],'','default',1);
                $this->app->model('rule')->deleteRule($data['rule_id'],'','other',0);
            }else{
                $this->app->model('rule')->deleteRule($data['rule_id'],'','',1);
            }
            $rule_id = explode(',',$data['rule_id']);
            foreach($rule_id as $rk=>$v){
                $this->app->model('rule')->delete(array('rule_id'=>$v));
            }
            $this->end(true,app::get('desktop')->_('删除成功'));
        }

        /**
        *
        */
        function deleteRule(){
            $finder_id = $_GET['finder_id'];

            $data = $_POST;
            if(empty($data)){
                echo '请选择';
            }else{

                $this->pagedata['data'] = implode(',',$data['rule_id']);
                $this->page('admin/deleteRule.html');
            }
        }


        function help(){
            echo '帮助';
        }



    }

?>