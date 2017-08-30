<?php
class wms_ctl_admin_branchpos extends desktop_controller{
    var $name = "仓库货位管理";
    var $workground = "storage_center";

    function index(){
       $params = array(
            'title'=>'货位管理',
            'actions'=>array(
                            array('label'=>'添加货位','href'=>'index.php?app=wms&ctl=admin_branchpos&act=addpos','target'=>'_blank'),
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

    function addpos($branch_id=0){
        $oBranch_pos = &app::get('ome')->model("branch_pos");
        $oBranch = &app::get('ome')->model("branch");
        $selfwms_id = kernel::single('wms_branch')->getBranchByselfwms();
       
        $branch_list = $oBranch->getList('*',array('wms_id'=>$selfwms_id),0,-1);
        
        if($_POST){
            $this->begin('index.php?app=wms&ctl=admin_branchpos&act=addpos&p[0]='.$_POST['branch_id']);
            $branch_pos = $oBranch_pos->dump(array('store_position'=>$_POST['store_position'],'branch_id'=>$_POST['branch_id']), 'pos_id');
            if($branch_pos['pos_id']){
                $this->end(false, app::get('base')->_('货位已存在'));
            }
            $_POST['stock_threshold'] = !$_POST['stock_threshold'] ? 0 : intval($_POST['stock_threshold']);

            $oBranch_pos->save($_POST);

            $this->end(true, app::get('base')->_('保存成功'),'index.php?app=wms&ctl=admin_branchpos&act=index');
        }
        $this->pagedata['branch_id'] = $branch_id;
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
        $this->pagedata['is_super']   = $is_super;

        $this->pagedata['title'] = '添加货位';
        $this->singlepage("admin/branch/branch_pos.html");
    }

}


?>