<?php
class logistics_ctl_admin_area extends desktop_controller {
    var $workground = 'setting_tools';
    function index(){

        $action=array(
            array(
                'label'  => $this->app->_('添加地区'),
                'href'   => 'index.php?app=logistics&ctl=admin_area&act=addArea',
                'target'=>'dialog::{width:600,height:600,title:\'添加地区\'}',

            ),

        );
        $params=array(
            'title' => '地区管理',

            'actions'=>$action,
            'use_buildin_recycle' => true,

        );
        $this->finder('logistics_mdl_area',$params);
   }

    /**
    *添加大区
    */
    function addArea(){
        $area_id = intval($_GET['area_id']);
        if($area_id){
            $area = $this->app->model('area')->detailArea($area_id);
            $this->pagedata['area'] = $area;
        }
        $this->page('admin/addArea.html');
    }


    public function getRegionById($pregionid)
    {

        $obj_regions_op = kernel::single('eccommon_regions_operation');
        $region = $obj_regions_op->getRegionById($pregionid);
        foreach($region as $k=>$v){
            $region[$k]['child_count'] = 0;
        }
        echo json_encode($region);
    }

    public function showRegionTreeList($serid,$branch_id)
    {

        $area = $this->app->model('area')->getArea();
        foreach($area as $k=>$v){
            foreach($v['area_items'] as $ak=>$av){
                $region_obj = $this->app->model('rule')->chkBranchRegion($branch_id,$av['region_id'],'');

                if($region_obj){

                    $area[$k]['area_items'][$ak]['flag']=1;
                }
            }
        }

        $this->pagedata['area'] = $area;
        $this->singlepage('admin/regionSelect.html');
    }

    public function showRegionList($serid,$multi=false)
    {

        if ($serid)
        {
            $this->pagedata['sid'] = $serid;
        }
        else
        {
            $this->pagedata['sid'] = substr(time(),6,4);
        }

         $this->pagedata['multi'] =  'true';
        $this->singlepage('admin/regionSelect1.html');
    }

    function saveDlArea(){
        $this->begin('index.php?app=logistics&ctl=admin_area&act=index');
        $data = $_POST;
        $area_data = array();
        if($data['area_id']){
            $area_data['area_id'] = $data['area_id'];

        }

        $area_data['local_name'] = $data['local_name'];
        $area_data['region_name'] = $data['region_name'];
        $area_data['region_id'] = $data['p_region_id'];
        $area_data['ordernum'] = $data['ordernum'];
        $msg='';
        $chkregion=$this->app->model('area')->chkRegion($data['p_region_id'],$data['area_id'],$msg);
        if(!$chkregion){
            $this->end(false, $msg.'已存在于其他区中');
        }

        $result = $this->app->model('area')->save($area_data);
        if($result){
            $this->end(true, app::get('eccommon')->_('新建成功').$msg);
        }else{
            $this->end(false, app::get('eccommon')->_('新建失败'));
        }
    }

    function test(){
        set_time_limit(0);
        echo '<pre>';
        $confirm='';
        $branch_id = $_GET['branch_id'];
        $area_id = $_GET['area_id'];
        $weight = $_GET['weight'];
        $corp_ids = kernel::single('logistics_rule')->autoMatchDlyCorp($area_id,$branch_id,$weight,$shop_type);
        print_r($corp_ids);


    }



}
?>