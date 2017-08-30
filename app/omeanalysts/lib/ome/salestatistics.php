<?php
class omeanalysts_ome_salestatistics extends eccommon_analysis_abstract implements eccommon_analysis_interface{

	public $report_type = 'true';
    public $type_options = array(
        'display' => 'true',
    );
    public $detail_options = array(
        'hidden' => true,
    );
    public $graph_options = array(
        'hidden' => true,
    );

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');

        for($i=0;$i<=5;$i++){
            if ($i == 1) continue;
            $val = $i+1;
            $this->_render->pagedata['time_shortcut'][$i] = $val;
        }
    }

	public function get_type(){
        $lab = '店铺';
        $typeObj = $this->app->model('ome_type');
        $data = $typeObj->get_shop();
        $return = array(
            'lab' => $lab,
            'data' => $data,
        );
        return $return;
    }
 	public function finder(){

        return array(
            'model' => 'omeanalysts_mdl_ome_salestatistics',
            'params' => array(
                'actions'=>array(
                     array(
                     	 'class' => 'export',
                         'label' => '生成报表',
                         //'href' => 'index.php?app=omeio&ctl=admin_task&act=create_export&_params[app]=omeanalysts&_params[mdl]=ome_salestatistics',
                         'href'=>'index.php?app=omeanalysts&ctl=ome_salestatistics&act=index&action=export',
                         //'target' => "dialog::{width:400,height:170,title:'生成报表'}",
                         'target'=>'{width:400,height:170,title:\'生成报表\'}'
                     ),
                ),
                'title'=>app::get('omeanalysts')->_('销售统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_salestatistics&act=index&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
                //'use_buildin_selectrow'=>false,
                //'use_buildin_filter'=>true,
            ),
        );
    }

    public function rank(){
    	$filter = $this->_params;
    	//默认获取上月的销售统计
        $filter['time_from'] = isset($filter['time_from']) ? $filter['time_from'] : '222';
        $filter['time_to'] = isset($filter['time_to']) ? $filter['time_to'] : '4444';

        $render = kernel::single('base_render');
        $render->pagedata['timefrom'] = $filter['time_from'];
        $render->pagedata['timeto'] = $filter['time_to'];
        $render->pagedata['type_id'] = $filter['type_id'];
        $render->pagedata['report'] = $filter['report'];
        $render->pagedata['type_id'] = $filter['type_id'] ? $filter['type_id'] : 0;
        $render->pagedata['js_url'] = 'http://'.$_SERVER['HTTP_HOST'].kernel::$base_url.'/app/omeanalysts/statics/js/highcharts.js';
        $html = $render->fetch('ome/salestatistics.html','omeanalysts');
        $this->_render->pagedata['rank_html'] = $html;
    }
}