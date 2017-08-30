<?php
/**
	* ShopEx licence
	*
	* @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
	* @license  http://ecos.shopex.cn/ ShopEx License
	* @version osc---hanbingshu sanow@126.com
	* @date 2012-08-06
	* 仓库货品成本MODEL
*/
class tgstockcost_mdl_branch_product extends dbeav_model
{

    var $has_export_cnf = true;

    /**
     * 队列导出名称
     *
     * @var string
     **/
    public  $export_name = '进销存';

	var $_instance=null;
	function __construct($app)
	{
		parent::__construct($app);
		if(!$this->_instance)$this->_instance = kernel::single("tgstockcost_instance_branchproduct");

	}

	public function get_schema()
	{
		$schema_obj = kernel::single("tgstockcost_schema_stockcost");
        $schema = $schema_obj->get_schema();

        foreach($schema['columns'] as $schema_k=>$val)
        {
           if($schema_k != 'time_from'){
               $schema['default_in_list'][] = $schema_k;
               $schema['in_list'][]         = $schema_k;
           }
        }

        return $schema;
	}


	/*
	*获取FINDER列表上仓库货品表数据
	*/
	function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null)
	{
		return $this->_instance->stock_getList($cols, $filter, $offset, $limit, $orderType);
	}

	//报表导出 ====begin
    public function export_params(){       
        //处理filter
        $filter = $this->export_filter;
        if ($post = unserialize($_POST['params'])) {
            $filter['time_from'] = $post['time_from'];
            $filter['time_to'] = $post['time_to'];
            $filter['branch_id'] = $post['branch_id'];
			$filter['brand_id'] = $post['brand_id'];
        }
        $params = array(
            'filter' => $filter,
            'limit' => 1000,
            'get_data_method' => 'get_productIostock_data',
            'single'=> array(
                'main'=> array(
                    'filename' => '库存收发汇总',
                ),
            ),
        );
        return $params;
    }

	/*导出标题头部*/
	public function get_productIostock_data_title()
	{
		$title['main'] = array(
            '*:货号',
            '*:名称',
            '*:商品类型',
            '*:品牌',
            '*:规格',
            '*:单位',
            '*:期初数量',
            '*:期初单位成本',
            '*:期初库存成本',
            '*:入库数量',
            '*:入库单位成本',
            '*:入库库存成本',
            '*:出库数量',
            '*:出库单位成本',
            '*:出库库存成本',
            '*:结存数量',
            '*:结存单位成本',
            '*:结存库存成本',
        );
		return $title;
	}

	/*导出数据*/
	public function get_productIostock_data($filter,$offset,$limit,&$data)
	{
		$list = $this->getList("*",$filter,$offset,$limit);
		foreach($list as $list_k=>$list_v)
		{
			$data['main'][] = array(
								'*:货号'=>$list_v['product_bn'],
								'*:名称'=>$list_v['product_name'],
								'*:商品类型'=>$list_v['type_name'],
								'*:品牌'=>$list_v['brand_name'],
								'*:规格'=>$list_v['spec_info'],
								'*:单位'=>$list_v['unit'],
								'*:期初数量'=>$list_v['start_nums'],
								'*:期初单位成本'=>$list_v['start_unit_cost'],
								'*:期初库存成本'=>$list_v['start_inventory_cost'],
								'*:入库数量'=>$list_v['in_nums'],
								'*:入库单位成本'=>$list_v['in_unit_cost'],
								'*:入库库存成本'=>$list_v['in_inventory_cost'],
								'*:出库数量'=>$list_v['out_nums'],
								'*:出库单位成本'=>$list_v['out_unit_cost'],
								'*:出库库存成本'=>$list_v['out_inventory_cost'],
								'*:结存数量'=>$list_v['store'],
								'*:结存单位成本'=>$list_v['unit_cost'],
								'*:结存库存成本'=>$list_v['inventory_cost'],
            );
		}
	}

	/*组织导出数据 OCS 不调用*/
	function fgetlist_csv(&$data,$filter,$offset,$exportType =1,$pass_data=false){

		return $this->_instance->fgetlist_csv($data,$filter,$offset,$exportType,$pass_data);
	}

	function count($filter=null){
		return $this->_instance->stock_count($filter);
	}

    function exportName(&$data){
    	 $this->_instance->exportName($data);
    }	

    function export_csv($data){
        return $this->_instance->export_csv($data);
    }

    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'report';
        if ($logParams['app'] == 'tgstockcost' && $logParams['ctl'] == 'stocksummary') {
            $type .= '_purchaseReport_stockSummaryAnalysis';
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'report';
        if ($logParams['app'] == 'tgstockcost' && $logParams['ctl'] == 'stocksummary') {
            $type .= '_purchaseReport_stockSummaryAnalysis';
        }
        $type .= '_import';
        return $type;
    }

    //根据查询条件获取导出数据
    public function getExportDataByCustom($fields, $filter, $has_detail, $curr_sheet, $start, $end){

        //根据选择的字段定义导出的第一行标题
        if($curr_sheet == 1){
            $data['content']['main'][] = $this->getExportTitle($fields);
        }

        $bpLib = kernel::single("tgstockcost_taog_branchproduct");

        //为了调用出oschema变量
        $bpLib->io_title();

        $list = $bpLib->getproductIostock($filter,$start,$end);
        if(!$list) return false;

        foreach($list['main'] as $k=>$aFilter){
            foreach ($bpLib->oSchema['csv']['main'] as $kk => $v) {
	        	$iostockRow[$v] = $aFilter[$v];
            }
            
            $exptmp_data = array();
            foreach (explode(',', $fields) as $key => $col) {
                if(isset($iostockRow[$col])){
                    $iostockRow[$col] = mb_convert_encoding($iostockRow[$col], 'GBK', 'UTF-8');
                    $exptmp_data[] = $iostockRow[$col];
                }
            }

            $data['content']['main'][] = implode(',', $exptmp_data);
        }

        return $data;
    }
}