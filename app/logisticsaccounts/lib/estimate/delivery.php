<?php
class logisticsaccounts_estimate_delivery extends eccommon_analysis_abstract implements eccommon_analysis_interface{
      public $type_options = array(
        'display' => 'true',
    );

    function __construct(&$app){
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');

        $this->_render->pagedata['from_selected'] = explode('-',$_POST['time_from']);
        $this->_render->pagedata['to_selected'] = explode('-',$_POST['time_to']);

        $this->_extra_view = array('logisticsaccounts' => 'extra_view.html');
    }

    public function get_type(){
        $lab = '物流公司';
        $typeObj = logisticsaccounts_estimate::delivery();
        $data = $typeObj->logi_list();
        $brObj = &app::get('ome')->model('branch');

        $is_super = kernel::single('desktop_user')->is_super();

        if ($is_super){
            $branch_list = $brObj->getList('branch_id,name','',0,-1);
        }else{
            $branch_list = $brObj->getBranchByUser();
        }
        $return = array(
            'lab' => $lab,
            'data' => $data,
            'branch_list'=>$branch_list,
        );
        return $return;
    }

    public $graph_options = array(
        'hidden' => true,
    );


     public function ext_detail(&$detail){
        $filter = $this->_params;

        $filter = array(
            'time_from' => $filter['time_from'],
            'time_to' => $filter['time_to'],
           // 'logi_id' => $filter['logi_id'],
        );


    }


    public function finder(){

        $params =  array(
                'actions'=>array(
                    array(
                        'label'=>app::get('logisticsaccounts')->_('获取发货单数据'),
                        'href'=>'index.php?app=logisticsaccounts&ctl=admin_estimate&act=import'),
                          array(
                        'label'=>app::get('logisticsaccounts')->_('导出'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=logisticsaccounts&ctl=admin_estimate&act=index&action=export',
                        'target'=>'{width:400,height:170,title:\'导出\'}'),
                ),
                'title'=>app::get('logisticsaccounts')->_('预估单'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_view_tab'=>true,
                'use_buildin_filter'=>true,
            );

        return array(
            'model' => 'logisticsaccounts_mdl_estimate',
            'params' => $params,
        );
    }
}