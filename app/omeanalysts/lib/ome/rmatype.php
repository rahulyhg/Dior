<?php
class omeanalysts_ome_rmatype extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $report_type ='false';
	protected $_title = '商品售后量统计';
	public $detail_options = array(
      'hidden' => true,
      'force_ext' => false,
    );

	public $graph_options = array(
		'hidden' => true,
	);
	public $type_options = array(
        'display' => 'true',
    );
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



  /*  public function finder(){
        $filter = $this->_params;
        if(isset($filter['report']) && $filter['report']=='month'){
            $time_from = strtotime($filter['time_from']);
            $time_to = explode('-',$filter['time_to']);
            $filter['time_from'] = date("Y-m-d",$time);
            $filter['time_to'] = date("Y-m-d",$filter['time_to']);
        }
        $base_query_string = 'time_from='.$filter['time_from'].'&time_to='.$filter['time_to'];
        return array(
            'model' => '',
            'params' => array(
                'title'=>app::get('omeanalysts')->_('商品售后统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=admin_goodrma&act=index&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>false,
				'use_buildin_selectrow'=>false,
                'base_query_string'=>$base_query_string,
			),
            );
    }*/
	/**
     * 重写设置报表统计的参数
     * @param array 需要设置的参数
     * @return object 本类对象
     */
    public function set_params($params)
    {
        $this->_params = $params;
        if(isset($this->analysis_config)){
            $time_from = date("Y-m-d", time()-(date('w')?date('w')-$this->analysis_config['setting']['week']:7-$this->analysis_config['setting']['week'])*86400);
        }else{
            $time_from = date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400);
        }
        $time_to = date("Y-m-d", strtotime($time_from)+86400*7-1);
        $this->_params['time_from'] = ($this->_params['time_from']) ? $this->_params['time_from'] : $time_from;
        $this->_params['time_to'] = ($this->_params['time_to']) ? $this->_params['time_to'] : $time_to;
        $this->_params['order_status'] = $this->analysis_config['filter']['order_status'];
        return $this;
    }

	public function display($fetch=false)
    {
        parent::headers();
        parent::detail();
        parent::graph();
        $this->rank();
        $content = $this->_render->page('analysis/extra_view.html', 'eccommon');
        if($fetch){
            return $content;
        }else{
            echo $content;
        }

	}
	public function rank() {
		$filter = $this->_params;
        $filter['time_from'] = isset($filter['time_from'])?$filter['time_from']:'';
        $filter['time_to'] = isset($filter['time_to'])?$filter['time_to']:'';

        $render = kernel::single('base_render');

        $render->pagedata['timefrom'] = $filter['time_from'];
        $render->pagedata['timeto'] = $filter['time_to'];
        $render->pagedata['type_id'] = $filter['type_id'];
        $html = $render->fetch('ome/rmatype.html','omeanalysts');

        $this->_render->pagedata['rank_html'] = $html;
	}


}