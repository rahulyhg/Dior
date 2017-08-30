<?php
class ome_ctl_admin_branch_pos extends desktop_controller{
    var $name = "仓库货位管理";
    var $workground = "storage_center";

    function index(){
       $params = array(
            'title'=>'货位管理',
            'actions'=>array(
                            array('label'=>'添加货位','href'=>'index.php?app=ome&ctl=admin_branch&act=addpos','target'=>'_blank'),
                            array('label'=>'货位批量添加','href'=>'index.php?app=ome&ctl=admin_branch_pos&act=batch_addpos','target'=>'_blank'),
                           /*
                            array(
                                'label' => '导出模板',
                                'href' => 'index.php?app=ome&ctl=admin_branch_pos&act=exportTemplate',
                                'target' => '_blank',
                            ),*/
                        ),
            'use_buildin_new_dialog' => false,
            'use_buildin_export'=>true,
            'use_buildin_import'=>true,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
       );

       // 获取操作员管辖仓库
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $params['base_filter']['branch_id'] = $branch_ids;
            }else{
                $params['base_filter']['branch_id'] = 'false';
            }
        }

       $branch_id = intval($_GET['branch_id']);
       if($branch_id){
             if (!$is_super){
                  if (in_array($branch_id,$branch_ids)){
               $params['base_filter']['branch_id'] = $branch_id;
                  }else{
                    $params['base_filter']['branch_id'] = '-';
                 }
             }else{
                    $params['base_filter']['branch_id'] = $branch_id;
             }
       }
       if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);

            $panel->setId('ome_branch_pos_finder_top');
            $panel->setTmpl('admin/finder/finder_branch_panel_filter.html');

            $panel->show('ome_mdl_branch_pos', $params);

        }
       $this->finder('ome_mdl_branch_pos',$params);
    }

    /*
     * 导出模板
     */
    function exportTemplate($type=null,$title=null){
            header("Content-Type: text/csv");
            $title = ($title==null)?'货位':$title;
            $filename = $title.'导入模板.csv';
            $encoded_filename = urlencode($filename);
            $encoded_filename = str_replace("+", "%20", $encoded_filename);

            $ua = $_SERVER["HTTP_USER_AGENT"];
            if (preg_match("/MSIE/", $ua)) {
                header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
            } else if (preg_match("/Firefox/", $ua)) {
                header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');
            $posObj = &$this->app->model('branch_pos');
            $type = ($type==null)?'branch_pos':$type;
            $title = $posObj->exportTemplate($type);
            //获取仓库名称
            $oBranch = &app::get('ome')->model('branch');
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $branch = $oBranch->Get_name($branch_ids[0]['branch_id']);
            }else{
                $branch = $oBranch->Get_branchlist();
                $branch = $branch[0]['name'];
            }
            echo '"'.implode('","',$title).'"';
        }

    /*
     * 仓库编辑
     *
     * @param int $branch_id
     *
     */
    function edit_pos($pos_id){
        $oBranch_pos = &$this->app->model("branch_pos");
        $oBranch = &app::get('ome')->model("branch");
        $branch_list=$oBranch->Get_branchlist();

        if($_POST){
            $this->begin('index.php?app=ome&ctl=admin_branch&act=edit_pos&p[0]='.$_POST['branch_id']);
            $_POST['store_position'] = strtoupper($_POST['store_position']);
            if ($_POST['store_position2']<>$_POST['store_position']){
                $branch_pos = $oBranch_pos->dump(array('store_position'=>$_POST['store_position'],'branch_id'=>$_POST['branch_id']), '*');
                if($branch_pos['pos_id']){
                    $this->end(false, app::get('base')->_('货位已存在'));
                    //echo '此货号名称已存在';
                }
            }
            $oBranch_pos->save($_POST);
            $this->end(true, app::get('base')->_('保存成功'),'index.php?app=ome&ctl=admin_branch_pos&act=index');
        }
        $is_super = 1;
        $this->pagedata['is_super']   = $is_super;
        $this->pagedata['branch_id'] = $branch_id;
        $this->pagedata['branch_list'] = $branch_list;
        $this->pagedata['pos'] = $oBranch_pos->dump($pos_id);
        $this->singlepage("admin/system/branch_pos.html");
    }

    function addpos($branch_id=0){
        $oBranch_pos = &$this->app->model("branch_pos");
        if($_POST){
            $this->begin('index.php?app=ome&ctl=admin_branch_pos');
            $oBranch_pos->save($_POST);
            $this->end(true,'添加成功');
        }
        $oBranch = &app::get('ome')->model("branch");
        $branch_list=$oBranch->Get_branchlist();

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
          $branch_list_byuser = $oBranch->getBranchByUser();
        }
        $this->pagedata['branch_list_byuser'] = $branch_list_byuser;
        $is_super = 1;
        $this->pagedata['is_super']   = $is_super;

        $this->pagedata['branch_id'] = $branch_id;
        $this->pagedata['branch_list'] = $branch_list;
        $this->singlepage("admin/system/branch_pos.html");
    }
    /*批量添加货位*/
    function batch_addpos(){
        $oBranch = &$this->app->model("branch");
        $branch_list=$oBranch->Get_branchlist();
        $this->pagedata['branch_list'] = $branch_list;

        //获取仓库模式
        //$branch_mode = &app::get('ome')->getConf('ome.branch.mode');
        //$this->pagedata['branch_mode'] = $branch_mode;

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
          $branch_list_byuser = $oBranch->getBranchByUser();
        }
        $this->pagedata['branch_list_byuser'] = $branch_list_byuser;
        $is_super = 1;
        $this->pagedata['is_super']   = $is_super;

        $this->singlepage("admin/system/batch_branch_pos.html");
    }

    function do_batch_pos(){
        $this->begin('index.php?app=ome&ctl=admin_branch_pos');
        $oBranch_pos = &$this->app->model("branch_pos");
        $start = $_POST['start'];
        $end = $_POST['end'];
        $num = $_POST['num'];
        $branch_id = $_POST['branch_id'];
        $pos_length = intval($_POST['pos_length']);
        if ($pos_length>4 || $pos_length<0){
            $this->end(false,'长度不可以大于4');
        }
        for($i=1;$i<=$num;$i++){
            $bn = str_pad($i,$pos_length,'0',STR_PAD_LEFT);
           
            $store_position=$start.$bn.$end;
            $store_position = strtoupper($store_position);
            $bp = $oBranch_pos->dump(array('branch_id'=>$branch_id,'store_position'=>$store_position));
            if (!$bp){
                $pos_data = array(
                    'branch_id'=>$branch_id,
                    'store_position'=>$store_position
                );
                $oBranch_pos->save($pos_data);
            }
        }
        $this->end(true,'批量添加成功');

    }

    function view()
    {

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();

        $branch_mode = &app::get('ome')->getConf('ome.branch.mode');
        if ($branch_mode=='single'){
            $cols = 'store_position,column_product_bn,column_product_name';
        }else{
            $cols = 'store_position,branch_id,column_product_bn,column_product_name';
        }

        $this->workground = 'storage_center';
        $params = array(
            'title'=>'货位整理',
            'actions'=>array(
                            array(
                                'label' => '导出模板',
                                'href' => 'index.php?app=ome&ctl=admin_branch_pos&act=exportTemplate&p[0]=export_branch_pos&p[1]=货位整理',
                                'target' => '_blank',
                            ),
                        ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>true,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
			'use_buildin_selectrow'=>true,
            'finder_aliasname'=>'branch_pos_finder',
            'finder_cols'=>$cols,
            'orderBy' => 'bp.pos_id desc ',
            'object_method' => array(
                'count'=>'finder_count',   //获取数量的方法名
                'getlist'=>'finder_list',   //获取列表的方法名
            ),
        );

        // 获取操作员管辖仓库
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $params['base_filter']['branch_id'] = $branch_ids;
            }else{
                $params['base_filter']['branch_id'] = 'false';
            }
        }

        $branch_id = intval($_GET['branch_id']);
        if($branch_id){
             if (!$is_super){
                  if (in_array($branch_id,$branch_ids)){
               $params['base_filter']['branch_id'] = $branch_id;
                  }else{
                    $params['base_filter']['branch_id'] = '-';
                 }
             }else{
                    $params['base_filter']['branch_id'] = $branch_id;
             }
        }

        $this->finder('ome_mdl_branch_pos',$params);
    }
}

