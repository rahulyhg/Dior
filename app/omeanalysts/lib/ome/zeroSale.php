<?php
class omeanalysts_ome_zeroSale extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $report_type = false;
	protected $_title = '零销售产品分析';

	public $detail_options = array(
      'hidden' => true,
      'force_ext' => false,
    );

	public $graph_options = array(
		'hidden' => true,
	);

    public $type_options = array(
        'display' => true,
    );

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_render = kernel::single('desktop_controller');
        $this->_render->pagedata['report'] = 'month';
        $year = $month = array();
        for($i='2010';$i<=date('Y');$i++){
            $year[] = $i;
        }
        for($i='1';$i<='12';$i++){
            $month[] = $i;
        }
        $this->_render->pagedata['year'] = $year;
        $this->_render->pagedata['month'] = $month;
        $this->_render->pagedata['from_selected'] = explode('-',$_POST['time_from']);
        $this->_render->pagedata['to_selected'] = explode('-',$_POST['time_to']);

        $this->_extra_view = array('omeanalysts' => 'ome/extra_view.html');
    }

    public function get_type(){
        $lab = '线上仓库';
        $funcObj = kernel::single('omeanalysts_func');
        $data = $funcObj->branch_list();
        $return = array(
            'lab' => $lab,
            'data' => $data,
        );
        return $return;
    }

    public function finder(){
        $filter = $this->_params;
        $base_query_string = 'time_from='.$filter['time_from'].'&time_to='.$filter['time_to'];
        return array(
            'model' => 'omeanalysts_mdl_ome_zeroSale',
            'params' => array(
                'actions'=>array(
                    array(
                         'label' => '导出',
                         'href' => 'index.php?app=omeio&ctl=admin_task&act=create_export&_params[app]=omeanalysts&_params[mdl]=analysis_zeroSale&_params[time_from]='.$filter['time_from'].'&_params[time_to]='.$filter['time_to'],
                         'target' => "dialog::{width:400,height:170,title:'生成报表'}",
                     ),
                ),
                'title'=>app::get('omeanalysts')->_('零销售产品分析<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_zeroSale&act=index&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
				'use_buildin_selectrow'=>false,
                'base_query_string'=>$base_query_string,
			),
       );
    }
}