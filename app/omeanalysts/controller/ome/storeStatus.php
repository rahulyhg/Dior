<?php
class omeanalysts_ctl_ome_storeStatus extends desktop_controller{

    var $name = "库存状况综合分析";

    public function index(){
        //kernel::single('omeanalysts_ctl_ome_goodsale')->mod_query_time();
        if(empty($_POST)){
            $_POST['time_from'] = date("Y-m-1");
            $_POST['time_to'] = date("Y-m-d",time()-24*60*60);
        }
        $_POST['own_branches'] = $this->getOperBranches();
        kernel::single('omeanalysts_ome_storeStatus')->set_params($_POST)->display();
    }

    public function get_map_data() {
        $filter = $_GET;
        $filter['type'] = 'map';
        $storeStatusModel = $this->app->model('ome_storeStatus');
        $branchModel = &app::get('ome')->model('branch');
        $list = $storeStatusModel->getList('*',$filter);

        if ($list){
            $categories = $data2 = array();
            foreach ($list as $value){
                //$branch_detail = $branchModel->dump(array('branch_id'=>$value['branch_id']),'name');
                $categories[] = '\''.$value['branch_name'].'\'';
                $data2[] = $value['turnover_rate'];
            }
        }

        $this->pagedata['title'] = '\'\'';
        $this->pagedata['categories'] = '['.implode(',',$categories).']';
        $this->pagedata['data']='[{name: \'周转率%\', data: ['.implode(',',$data2).'], dataLabels:{formatter:function(){return this.y + "%"}}}]';
        $this->display("ome/map.html");
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