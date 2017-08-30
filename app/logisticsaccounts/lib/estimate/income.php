<?php
class logisticsaccounts_estimate_delivery extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $type_options = array(
        'display' => 'true',
    );


    public $graph_options = array(
        'hidden' => true,
    );


    public function ext_detail(&$detail){
        $filter = $this->_params;


    }

    public function finder(){
        return array(
            'model' => 'logisticsaccounts_mdl_estimate',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('logisticsaccounts')->_('获取数据'),

                        'href'=>'index.php?app=logisticsaccounts&ctl=admin_estimate&act=import'),
                ),
                'title'=>app::get('logisticsaccounts')->_('预估单'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_view_tab'=>true,
            ),
        );
    }
}