<?php
class omeanalysts_ome_store extends eccommon_analysis_abstract implements eccommon_analysis_interface{
	public $report_type = 'true';
    public $type_options = array(
        'display' => 'true',
    );
	 public $detail_options = array(
        'hidden' => false,
        'force_ext' => false,
    );

	public $logs_options = array(
		'1' => array(
			'name' => '出货量',
			'flag' => array(),
			'memo' => '已发货量',
			'icon' => 'money.gif',
		),
		'2' => array(
			'name' => '当前库存量',
			'flag' => array(
					0 => '真实库存',
					1 => '预占库存',
					2 => '在途库存',
					),
			'memo' => '真实库存量',
			'icon' => 'money.gif',
		),
		'3' => array(
			'name' => '当前库存价值',
			'flag' => array(),
			'memo' => '当前真实的库存价值',
			'icon' => 'money.gif',
		),
	);
    public $graph_options = array(
        'hidden' => false,
        'iframe_height' => 180,
        'callback' => 'omeanalysts_ome_graph_month',
    );

	public function get_type(){
		$lab = '仓库';
        $typeObj = $this->app->model('ome_type');
        $data = $typeObj->get_branch();
		$return = array(
			'lab' => $lab,
			'data' => $data,
		);
		return $return;
	}


	public function get_logs($time){
		$filter = array(
            'time_from' => date('Y-m-d',$time),
            'time_to' => date('Y-m-d',$time),
        );
        $storeObj = $this->app->model('ome_store');
		$outstock = $storeObj->get_outstock($filter);
		$store = $storeObj->get_store($filter);
		$store_value = $storeObj->get_value($filter);

		$result[] = array('type'=>0, 'target'=>1, 'flag'=>0, 'value'=>$outstock);
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>0, 'value'=>$store['store']);
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>1, 'value'=>$store['store_freeze']);
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>2, 'value'=>$store['arrive_store']);
        $result[] = array('type'=>0, 'target'=>3, 'flag'=>0, 'value'=>$store_value);

        return $result;
	}


	public function ext_detail(&$detail){
		$filter = $this->_params;
		if(isset($filter['report']) && $filter['report']=='month'){
            $time_from = strtotime($filter['time_from']);
            $time_to = explode('-',$filter['time_to']);
            $filter['time_from'] = date("Y-m",$time_from).'-01';
            $filter['time_to'] = date("Y-m-d",mktime(0, 0, 0, $time_to[1]+1, 0, $time_to[0]));
        }
        $filter = array(
            'time_from' => $filter['time_from'],
            'time_to' => $filter['time_to'],
            'type_id' => $filter['type_id'],
        );

		$storeObj = $this->app->model('ome_store');
		$outstock = $storeObj->get_outstock($filter);
		$store = $storeObj->get_store($filter);
		$store_value = $storeObj->get_value($filter);

		$detail['出货量']['value'] = $outstock?$outstock:0;
		$detail['当前库存量']['value'] = $store['store']?$store['store']:0;
		$detail['当前库存价值']['value'] = $store_value?$store_value:0;
		$detail['当前预占库存量']['value'] = $store['store_freeze']?$store['store_freeze']:0;
		$detail['当前预占库存量']['memo'] = '当前已售出未发货的库存量';
		$detail['当前预占库存量']['icon'] = 'money.gif';
		$detail['当前在途库存量']['value'] = $store['arrive_store']?$store['arrive_store']:0;
		$detail['当前在途库存量']['memo'] = '当前已采购未入库的库存量';
		$detail['当前在途库存量']['icon'] = 'money.gif';
	}

	public function finder() {
		return array(
			'model' => 'omeanalysts_mdl_ome_store',
			'params' => array(
				'actions' => array(
					array(
					'label'=>app::get('omeanalysts')->_('生成报表'),
					'class'=>'export',
					'icon'=>'add.gif',
					'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=store&action=export',
					'target'=>'{width:400,height:170,title:\'生成报表\'}'),
				),
				'title' =>app::get('omeanalysts')->_('库存统计'),
				'use_buildin_recycle'=>false,
				'use_buildin_selectrow'=>false,

			),
		);
	}
}
?>