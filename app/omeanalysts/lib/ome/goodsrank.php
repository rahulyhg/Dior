<?php
class omeanalysts_ome_goodsrank extends eccommon_analysis_abstract implements eccommon_analysis_interface{
	public $type_options = array(
        'display' => 'true',
    );
	public $detail_options = array(
        'hidden' => true,
        'force_ext' => false,
    );

	public $graph_options = array(
		'hidden' => true,
	);

    public $orderby =  array(
         '龙虎榜（前100）' =>array(
            '销售量'=>array(
               'orderby'=>'sale_num',
               'ranktype'=>'up',
            ),
            '销售额'=>array(
               'orderby'=>'sale_amount',
               'ranktype'=>'up',
            ),
            '退换货量'=>array(
               'orderby'=>'reship_num',
               'ranktype'=>'up',
            ),
            '退换货率'=>array(
               'orderby'=>'reship_ratio',
               'ranktype'=>'up',
            ),
            '毛利'=>array(
               'orderby'=>'gross_sales',
               'ranktype'=>'up',
            ),
            '毛利率'=>array(
               'orderby'=>'gross_sales_rate',
               'ranktype'=>'up',
            ),
        ),
         '吊尾榜（后100）' =>array(
            '销售量'=>array(
               'orderby'=>'sale_num',
               'ranktype'=>'down',
            ),
            '销售额'=>array(
               'orderby'=>'sale_amount',
               'ranktype'=>'down',
            ),
            '退换货量'=>array(
               'orderby'=>'reship_num',
               'ranktype'=>'down',
            ),
            '退换货率'=>array(
               'orderby'=>'reship_ratio',
               'ranktype'=>'down',
            ),
            '毛利'=>array(
               'orderby'=>'gross_sales',
               'ranktype'=>'down',
            ),
            '毛利率'=>array(
               'orderby'=>'gross_sales_rate',
               'ranktype'=>'down',
            ),
        ),
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

    public function __construct(&$app)
    {
        parent::__construct($app);  //$filter['time_from']
        $_GET['filter']['from'] = array(
                'ranktype'=>$_POST['ranktype'],
                'orderby'=>$_POST['orderby'],
                );
        $this->_extra_view = array('omeanalysts' => 'ome/goodsrank_extra_view.html');
    }

	public function finder() {
		$params =  array(
            'model' => 'omeanalysts_mdl_ome_goodsrank',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('omeanalysts')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=goodsrank&action=export',
                        'target'=>'{width:600,height:300,title:\'生成报表\'}'),
                ),
                'title'=>app::get('omeanalysts')->_('商品销售排行<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=goodsrank&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>true,
            ),
        );
		#增加报表导出权限
		$is_export = kernel::single('desktop_user')->has_permission('analysis_export');
		if(!$is_export){
		    unset($params['params']['actions']);
		}
		return $params;
	}

	public function rank() {
		    $filter = $this->_params;

        $filter['time_from'] = isset($filter['time_from'])?$filter['time_from']:'';
        $filter['time_to'] = isset($filter['time_to'])?$filter['time_to']:'';

        $render = kernel::single('base_render');

        $render->pagedata['timefrom'] = $filter['time_from'];
        $render->pagedata['timeto'] = $filter['time_to'];
        $render->pagedata['type_id'] = $filter['type_id'];
        $render->pagedata['cur_orderby'] = $filter['orderby'];
        $render->pagedata['cur_ranktype'] = $filter['ranktype'];
        $render->pagedata['orderby'] = $this->orderby;
        $html = $render->fetch('ome/goodsrank.html','omeanalysts');

        $this->_render->pagedata['rank_html'] = $html;
	}
}
?>