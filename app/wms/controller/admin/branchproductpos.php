<?php
class wms_ctl_admin_branchproductpos extends desktop_controller{
     var $name = "仓库货位管理";
    var $workground = "storage_center";

    function index(){
        $branch_mode = &app::get('ome')->getConf('ome.branch.mode');
        if ($branch_mode=='single'){
            $cols = 'column_store_position,column_product_bn,column_product_name';
        }else{
            $cols = 'column_store_position,column_branch_name,column_product_bn,column_product_name';
        }
       $params = array(
            'title'=>'货位整理',
            'actions'=>array(
                            array(
                                'label' => '导出模板',
                                'href' => 'index.php?app=ome&ctl=admin_branch_product_pos&act=exportTemplate&p[0]=export_branch_pos&p[1]=货位整理',
                                'target' => '_blank',
                            ),

                            array(
                                'label' => '解绑',
                                'href' => 'javascript:void(0);',
                                'confirm'=>app::get('ome')->_('确定解除货品与货位的绑定关系吗？'),
                                'submit' => 'index.php?app=ome&ctl=admin_branch_product_pos&act=index&action=dorecycle',
                            ),
                            array(
                                'label' => 'csv文件导入解绑货位',
                                'href' => 'index.php?app=ome&ctl=admin_branch_product_pos&act=unbundPos',
                                'target'=>'dialog::{width:600,height:300,title:\'csv文件批量导入解绑货位\'}',

                            ),
                        ),
            'use_buildin_new_dialog' => false,
            'use_buildin_export'=>true,
            'use_buildin_import'=>true,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_selectrow'=>true,
            'finder_cols'=>$cols,
            'orderBy' => 'bpp.pos_id desc ',
            'object_method' => array(
                'count'=>'finder_count',   //获取数量的方法名
                'getlist'=>'finder_list',   //获取列表的方法名
            ),
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

       $branch_id = intval($_REQUEST['branch_id']);
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
        #列表新增仓库搜索
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);

            $panel->setId('ome_branch_product_pos_finder_top');
            $panel->setTmpl('admin/finder/finder_branch_panel_filter.html');

            $panel->show('ome_mdl_branch_product_pos', $params);

        }
       $this->finder('ome_mdl_branch_product_pos',$params);
    }
}


?>