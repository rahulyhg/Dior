<?php
class ome_ctl_admin_branch_product_pos extends desktop_controller{
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

                            'pos_bind'=>array(
                                'label' => '解绑',
                                'href' => 'javascript:void(0);',
                                'confirm'=>app::get('ome')->_('确定解除货品与货位的绑定关系吗？'),
                                'submit' => 'index.php?app=ome&ctl=admin_branch_product_pos&act=index&action=dorecycle',
                            ),
                            'csv_pos_bind'=>array(
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
       #增加货位解绑权限
       $pos_bind = kernel::single('desktop_user')->has_permission('pos_export');
       if(!$pos_bind){
          unset($params['actions']['pos_bind']);
       }
       #CSV文件导入解绑
       $csv_pos_bind = kernel::single('desktop_user')->has_permission('csv_pos_export');
       if(!$csv_pos_bind){
          unset($params['actions']['csv_pos_bind']);
       }

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
             //仓储-日常管理-货位整理-模板-导出-操作日志
            $logParams = array(
                'app' => $this->app->app_id,
                'ctl' => trim($_GET['ctl']),
                'act' => trim($_GET['act']),
                'modelFullName' => '',
                'type' => 'export',
                'params' => array(),
            );
            ome_operation_log::insert('warehouse_dailyManager_posTidy_template_export', $logParams);
            $posObj = &$this->app->model('branch_product_pos');
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

        function delpos($pos_id, $product_id) {
             $this->begin('index.php?app=ome&ctl=admin_branch_product_pos&act=index');
             $posObj = &$this->app->model('branch_product_pos');
             $posObj->del_branch_product_pos($product_id, $pos_id);
             $this->end(true, '已解绑');
        }

    /**
    * 解除货位与商品关系
    *
    * sunjing@shopex.cn
    */
    function unbundPos() {
        $branch_list = &app::get('ome')->model('branch')->getAllBranchs('branch_id,name,disabled');
        $this->pagedata['branch_list'] = $branch_list;

        unset($branch_list);
        echo $this->page('admin/branch/product/import_pos.html');
    }

    /**
    * 执行导入
    *
    * sunjing@shopex.cn
    */
    function doImport() {
        $msgList = array();
        $result = kernel::single('ome_branch_product_pos_to_import')->process($_POST,$msgList);
        header("content-type:text/html; charset=utf-8");
         //仓储-日常管理-CSV文件导入解绑货位-导入
        $logParams = array(
            'app' => $this->app->app_id,
            'ctl' => trim($_GET['ctl']),
            'act' => trim($_GET['act']),
            'modelFullName' => '',
            'type' => 'import',
            'params' => array(),
        );
        ome_operation_log::insert('warehouse_dailyManager_csv_import', $logParams);
        if($result['rsp']=='succ'){

            echo json_encode(array('result' => 'succ', 'msg' =>'上传成功','errormsg'=>(array)$msgList));
        }else{
            echo json_encode(array('result' => 'fail', 'msg' =>$result['res']));
        }
    }
}

