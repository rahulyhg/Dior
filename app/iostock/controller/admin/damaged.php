<?php
class iostock_ctl_admin_damaged extends desktop_controller{
     function index(){
        $this->title = '商品残损';
        $finder_cols = "column_outname,column_inname,iostock_bn,original_bn,oper,create_time,operater,memo";
        $filter = array('type_id'=>array('5','50'),'iostock_bn'=>array($_SESSION['bn1'],$_SESSION['bn2']));
        unset($_SESSION['bn1']);
        unset($_SESSION['bn2']);
        $params = array(
            'title'=>$this->title,
            'base_filter' => $filter,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>true,
            'orderBy'=>'create_time desc',
            'finder_cols'=>$finder_cols,
        );
        $this->finder('iostock_mdl_damaged',$params);
    }

    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=damaged.csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $pObj = &$this->app->model('damaged');
        $title1 = $pObj->exportTemplate('main');
        $title2 = $pObj->exportTemplate('item');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";
        echo '"'.implode('","',$title2).'"';
    }

}