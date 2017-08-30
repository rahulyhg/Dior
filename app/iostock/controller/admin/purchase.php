<?php
class iostock_ctl_admin_purchase extends desktop_controller{

     function index(){
        $this->title = '采购入库';
        $filter = array('type_id'=>array('1'),'iostock_bn'=>array($_SESSION['bn']));
        unset($_SESSION['bn']);
        $finder_cols = "branch_id,column_branchbn,iostock_bn,column_suppliername,original_bn,create_time,oper,bn,column_productname,nums,iostock_price,settlement_money";
        $params = array(
            'title'=>$this->title,
            'base_filter' => $filter,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>true,
            'orderBy' => 'create_time desc',
            'finder_cols'=>$finder_cols,
        );
        $this->finder('iostock_mdl_purchase',$params);
    }

    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=purchase.csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $pObj = &$this->app->model('purchase');
        $title1 = $pObj->exportTemplate('main');
        $title2 = $pObj->exportTemplate('item');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";
        echo '"'.implode('","',$title2).'"';
    }
}