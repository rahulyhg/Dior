<?php
class iostock_ctl_admin_changeorderreturns extends desktop_controller{
    public $name = "出入库管理";
    public $workground = "iostock_center";

    public function index(){
        $this->title = '换货入库';
        $finder_cols = "branch_id,column_iostockbn,column_member_name,original_bn,create_time,oper,bn,column_name,nums,iostock_price,column_amount";
//    仓库，入库单号、(会员姓名)、发货单号、入库时间、售后申请人、商品货号、(商品名称)、退货数量、退货单价、(退货金额)
      $filter = array('type_id'=>array('31'),'iostock_bn'=>array($_SESSION['bn']));
      unset($_SESSION['bn']);
        $params = array(
            'title'=>$this->title,
            'base_filter' => $filter,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>true,
            'finder_cols'=>$finder_cols,
            'orderBy' => 'create_time desc',
        );
        $this->finder('iostock_mdl_changeorderreturns',$params);
    }

    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=orderreturns.csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $pObj = &$this->app->model('changeorderreturns');
        $title1 = $pObj->exportTemplate('main');
        $title2 = $pObj->exportTemplate('item');
        echo '"'.implode('","',$title1).'"';
        echo "\n\n";
        echo '"'.implode('","',$title2).'"';
    }
}