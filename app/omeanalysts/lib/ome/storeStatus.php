<?php
class omeanalysts_ome_storeStatus extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $report_type = false;
    protected $_title = '库存状况综合分析';

    public $detail_options = array(
      'hidden' => true,
      'force_ext' => true,
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
        $funcObj = kernel::single('omeanalysts_func');
        $data = $funcObj->branch_list();
        $return = array(
          'branch_id'=>array(
            'lab' => '线上仓库',
            'data' => $data,
          ),
        );

        return $return;
    }

    public function finder(){

        $filter = $this->_params;
        $base_query_string = 'time_from='.$filter['time_from'].'&time_to='.$filter['time_to'];
        $params =  array(
            'model' => 'omeanalysts_mdl_ome_storeStatus',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('omeanalysts')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=omeanalysts&ctl=ome_storeStatus&act=index&action=export',
                        'target'=>'{width:600,height:300,title:\'生成报表\'}'),
                ),
                'title'=>app::get('omeanalysts')->_('库存状况综合分析<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_storeStatus&act=index&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_selectrow'=>false,
                'base_query_string'=>$base_query_string,
            ),
       );
        #增加报表导出权限
		$is_export = kernel::single('desktop_user')->has_permission('analysis_export');
		if(!$is_export){
		    unset($params['params']['actions']);
		}
		return $params;
    }

    public function headers(){
        $this->_render->pagedata['title'] = $this->_title;
        $this->_render->pagedata['time_from'] = $this->_params['time_from'];
        $this->_render->pagedata['time_to'] = $this->_params['time_to'];
        $this->_render->pagedata['today'] = date("Y-m-d");
        $this->_render->pagedata['yesterday'] = date("Y-m-d", time()-86400);
        if(isset($this->analysis_config)){
            $this->_render->pagedata['this_week_from'] = date("Y-m-d", time()-(date('w')?date('w')-$this->analysis_config['setting']['week']:7-$this->analysis_config['setting']['week'])*86400);
        }else{
            $this->_render->pagedata['this_week_from'] = date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400);
        }
        $this->_render->pagedata['this_week_to'] = date("Y-m-d", strtotime($this->_render->pagedata['this_week_from'])+86400*7-1);
        $this->_render->pagedata['last_week_from'] = date("Y-m-d", strtotime($this->_render->pagedata['this_week_from'])-7*86400);
        $this->_render->pagedata['last_week_to'] = date("Y-m-d", strtotime($this->_render->pagedata['last_week_from'])+86400*7-1);
        $this_month_t = date('t');
        $this->_render->pagedata['this_month_from'] = date("Y-m-" . 01);
        $this->_render->pagedata['this_month_to'] = date("Y-m-" . $this_month_t);
        $last_month_t = date('t', strtotime("last month"));
        $this->_render->pagedata['last_month_from'] = date("Y-m-" . 01, strtotime("last month"));
        $this->_render->pagedata['last_month_to'] = date("Y-m-" . $last_month_t, strtotime("last month"));
        $this->_render->pagedata['layout'] = $this->layout;

        if($this->report_type == 'true'){
            $this->_render->pagedata['report'] = $this->_params['report'];
            $this->_render->pagedata['report_type'] = $this->report_type;
            $this->_render->pagedata['month'] = array(1,2,3,4,5,6,7,8,9,10,11,12);
            for($i = 2000;$i<=date("Y",time());$i++){
                $year[] = $i;
            }
            $this->_render->pagedata['year'] = $year;
            $this->_render->pagedata['from_selected'] = explode('-',$this->_params['time_from']);
            $this->_render->pagedata['to_selected'] = explode('-',$this->_params['time_to']);
        }

        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $type_selected = array(
                                'branch_id'=>$this->_params['branch_id'],
                            );
            $this->_render->pagedata['type_selected'] = $type_selected;
        }
    }

    public function rank() {
        $filter = $this->_params;
        $filter['time_from'] = isset($filter['time_from'])?$filter['time_from']:'';
        $filter['time_to'] = isset($filter['time_to'])?$filter['time_to']:'';

        $render = kernel::single('base_render');
        $render->pagedata['time_from'] = $filter['time_from'];
        $render->pagedata['time_to'] = $filter['time_to'];
        $render->pagedata['branch_id'] = $filter['branch_id'];
        $html = $render->fetch('ome/store_status.html','omeanalysts');
        $this->_render->pagedata['rank_html'] = $html;
    }

}