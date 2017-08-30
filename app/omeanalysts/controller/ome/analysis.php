<?php
class omeanalysts_ctl_ome_analysis extends desktop_controller{
    public function income(){ //订单金额统计
        kernel::single('omeanalysts_ome_income')->set_params($_POST)->display();
    }

    public function delivery(){ //快递费结算表
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids[0]>0) {
                $default_type = $branch_ids[0];
            } else {
                $default_type = 'false';
            }
        }else{
            $branchs = $oBranch->getList('branch_id,name',array(),0,1);
            if ($branchs[0]['branch_id']>0) {
                $default_type = $branchs[0]['branch_id'];
            } else {
                $default_type = 'false';
            }
        }
        $_POST['type_id'] = $_POST['type_id'] ? $_POST['type_id'] : $default_type;
        $_POST['own_branches'] = $this->getOperBranches();
        kernel::single('omeanalysts_ome_delivery')->set_params($_POST)->display();
    }

    public function cod(){ //货到付款结算表
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids[0]>0) {
                $default_type = $branch_ids[0];
            } else {
                $default_type = 'false';
            }
        }else{
            $branchs = $oBranch->getList('branch_id,name',array(),0,1);
            if ($branchs[0]['branch_id']>0) {
                $default_type = $branchs[0]['branch_id'];
            } else {
                $default_type = 'false';
            }
        }
        $_POST['type_id'] = $_POST['type_id'] ? $_POST['type_id'] : $default_type;
        $_POST['own_branches'] = $this->getOperBranches();
        kernel::single('omeanalysts_ome_cod')->set_params($_POST)->display();
    }


    public function shop(){ //店铺销售情况
        //取消当天实时数据统计，这样做会导致每请求一次增加一批垃圾数据
        /*if(empty($_POST['time_from'])){
          kernel::single('omeanalysts_analysis_shop_shop')->analysis_data();#用于统计当天实时的数据
        }*/
        kernel::single('omeanalysts_ome_shop')->set_params($_POST)->display();
    }
    

    public function products(){ //货品销售情况
        kernel::single('omeanalysts_ctl_ome_goodsale')->mod_query_time();
        kernel::single('omeanalysts_ome_products')->set_params($_POST)->display();
    }

    public function goodsrank(){ //商品销售排行
        kernel::single('omeanalysts_ome_goodsrank')->set_params($_POST)->display();
    }

    public function sales(){ //订单销售情况
        $_POST['own_branches'] = $this->getOperBranches();
        kernel::single('omeanalysts_ome_sales')->set_params($_POST)->display();
    }

    public function store(){ //库存报表
        kernel::single('omeanalysts_ome_store')->set_params($_POST)->display();
    }


    public function aftersale(){ //货品售后问题统计
        kernel::single('omeanalysts_ome_aftersale')->set_params($_POST)->display();
    }

    public function regenerate_report($params = 'shop',$action = 'regenerate'){//重新生成报表
        kernel::single('omeanalysts_analysis_shop_shop')->$action();
    }

    public function branchdelivery(){ //仓库发货情况统计
        $_POST['own_branches'] = $this->getOperBranches();
        kernel::single('omeanalysts_ome_branchdelivery')->set_params($_POST)->display();
    }

    private function getOperBranches(){
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if (count($branch_ids)>0) {
                return $branch_ids;
            } else {
                return array(0);
            }
        }
    }
}